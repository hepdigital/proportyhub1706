<?php

class Sync {
    private $db;
    private $calendar;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->calendar = new Calendar();
    }

    /**
     * Belirtilen bir üniteyi, tanımlanmış senkronizasyon tipine göre senkronize eder.
     * @param int $unit_id
     * @return bool
     */
    public function syncUnit($unit_id) {
        $stmt = $this->db->prepare("
            SELECT u.*, p.sync_type, p.wp_site_url, p.api_key, p.google_sheet_id, p.id as property_id
            FROM units u
            JOIN properties p ON u.property_id = p.id
            WHERE u.id = :unit_id
        ");
        $stmt->execute([':unit_id' => $unit_id]);
        $unit_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$unit_data) {
            self::log(null, $unit_id, 'sync_error', 'error', 'Ünite veya bağlı olduğu tesis bulunamadı.');
            return false;
        }

        // Akıllı Senkronizasyon: Tesisin senkronizasyon tipi 'hub' ise, harici senkronizasyon yapma.
        if ($unit_data['sync_type'] === 'hub') {
             self::log($unit_data['property_id'], $unit_id, 'sync_skipped', 'info', 'Tesis tipi "Doğrudan Yönetim" olduğu için senkronizasyon atlandı.');
             return true; // Hata değil, başarılı bir atlama işlemi.
        }

        // Senkronizasyondan önce, SADECE senkronize edilecek kaynağa ait eski verileri temizle.
        // Bu, manuel olarak eklenen kayıtlara dokunmaz.
        $this->clearSyncSourceData($unit_id, $unit_data['sync_type']);
        
        $result = false;
        if ($unit_data['sync_type'] === 'wordpress') {
            $result = $this->syncUnitFromWordPress($unit_data);
        } else if ($unit_data['sync_type'] === 'ical') {
            $result = $this->syncUnitFromIcal($unit_data);
        }

        // Başarılı senkronizasyon sonrası Google Sheet'e gönder.
        if ($result === true && !empty($unit_data['google_sheet_id'])) {
            $unitModel = new Unit();
            $unitModel->pushAvailabilityToSheet($unit_id, $unit_data['property_id']);
        }

        return $result;
    }

    private function syncUnitFromIcal($unit_data) {
        try {
            if (empty($unit_data['ical_url'])) {
                throw new Exception('iCal URL bulunamadı veya geçersiz.');
            }
            
            $context = stream_context_create(['http' => ['timeout' => 30]]);
            $ical_content = @file_get_contents($unit_data['ical_url'], false, $context);
            if ($ical_content === false) {
                throw new Exception("iCal URL okunamadı: " . $unit_data['ical_url']);
            }
            
            $events = $this->calendar->parseIcal($ical_content);

            foreach ($events as $event) {
                if (isset($event['DTSTART']) && isset($event['DTEND'])) {
                    $start = new DateTime($event['DTSTART']);
                    $end = new DateTime($event['DTEND']);
                    while ($start < $end) {
                        $this->calendar->updateAvailability($unit_data['id'], $start->format('Y-m-d'), false, $event['UID'] ?? null, 'ical');
                        $start->modify('+1 day');
                    }
                }
            }

            $this->updateLastSyncTime($unit_data['id']);
            self::log($unit_data['property_id'], $unit_data['id'], 'ical_sync', 'success', count($events) . ' iCal etkinliği işlendi.');
            return true;

        } catch (Exception $e) {
            self::log($unit_data['property_id'], $unit_data['id'], 'ical_sync', 'error', $e->getMessage());
            return false;
        }
    }

    private function syncUnitFromWordPress($unit_data) {
        try {
            if (empty($unit_data['wp_site_url']) || empty($unit_data['api_key']) || empty($unit_data['wp_room_id'])) {
                throw new Exception('WordPress URL, API Key veya WP Oda ID eksik.');
            }
            
            $wp_api_url = rtrim($unit_data['wp_site_url'], '/') . '/wp-json/wp-reservation/v1/rooms/' . $unit_data['wp_room_id'] . '/availability-feed';
            
            $args = ['http' => ['method' => 'GET', 'header' => "X-Property-Hub-Key: " . $unit_data['api_key'], 'ignore_errors' => true]];
            $context = stream_context_create($args);
            $response_json = @file_get_contents($wp_api_url, false, $context);

            if ($response_json === false) {
                throw new Exception("WordPress API'sine bağlanılamadı.");
            }

            $response_data = json_decode($response_json, true);
            if (!isset($response_data['reservations'])) {
                 throw new Exception("WordPress API'sinden geçersiz yanıt alındı: " . ($response_data['message'] ?? 'Detay yok'));
            }

            if (!empty($response_data['reservations'])) {
                foreach($response_data['reservations'] as $res) {
                    if ($res['unit_number'] != $unit_data['unit_number']) continue;
                    $res_start = new DateTime($res['start_date']);
                    $res_end = new DateTime($res['end_date']);
                    while($res_start < $res_end) {
                        $this->calendar->updateAvailability($unit_data['id'], $res_start->format('Y-m-d'), false, $res['uid'], 'wordpress');
                        $res_start->modify('+1 day');
                    }
                }
            }
            
            if (!empty($response_data['blocked_dates'])) {
                foreach($response_data['blocked_dates'] as $blocked) {
                     if ($blocked['unit_number'] != $unit_data['unit_number']) continue;
                     $this->calendar->updateAvailability($unit_data['id'], $blocked['date'], false, $blocked['uid'], 'wordpress_manual_block');
                }
            }
            
            $this->updateLastSyncTime($unit_data['id']);
            self::log($unit_data['property_id'], $unit_data['id'], 'wordpress_sync', 'success', 'WordPress müsaitlik durumu başarıyla senkronize edildi.');
            return true;

        } catch (Exception $e) {
            self::log($unit_data['property_id'], $unit_data['id'], 'wordpress_sync', 'error', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Bir senkronizasyon başlamadan önce, o kaynağa ait eski verileri temizler.
     * Diğer kaynaklardan (örn: Google Sheet, manual) gelen dolulukları korur.
     */
    private function clearSyncSourceData($unit_id, $source_to_clear) {
        $start_date = date('Y-m-d');
        
        $sources_to_delete = [];
        if ($source_to_clear === 'wordpress') {
            $sources_to_delete = ['wordpress', 'wordpress_push', 'wordpress_manual_block'];
        } elseif ($source_to_clear === 'ical') {
            $sources_to_delete = ['ical'];
        }

        if (empty($sources_to_delete)) {
            return;
        }

        $placeholders = rtrim(str_repeat('?,', count($sources_to_delete)), ',');
        
        $stmt = $this->db->prepare("
            DELETE FROM availability 
            WHERE unit_id = ? 
            AND date >= ?
            AND sync_source IN ($placeholders)
        ");
        
        $params = array_merge([$unit_id, $start_date], $sources_to_delete);
        $stmt->execute($params);
    }
    
    /**
     * Bir tesise ait tüm üniteler için senkronizasyonu tetikler.
     * @param int $property_id
     * @return array
     */
    public function syncProperty($property_id) {
        $unitModel = new Unit();
        $units = $unitModel->getByProperty($property_id);

        if (empty($units)) {
            self::log($property_id, null, 'property_sync', 'warning', 'Bu tesise ait senkronize edilecek aktif ünite bulunamadı.');
            return [];
        }

        $results = [];
        foreach ($units as $unit_data) {
            $results[$unit_data['id']] = $this->syncUnit($unit_data['id']);
        }
        return $results;
    }

    /**
     * Bir ünitenin son senkronizasyon zamanını günceller.
     * @param int $unit_id
     */
    private function updateLastSyncTime($unit_id) {
        $stmt = $this->db->prepare("UPDATE units SET last_sync = NOW() WHERE id = :id");
        $stmt->execute([':id' => $unit_id]);
    }

    /**
     * Senkronizasyon işlemlerini veritabanına loglar.
     * @param int|null $property_id
     * @param int|null $unit_id
     * @param string $sync_type
     * @param string $status 'success', 'error', 'info', 'warning'
     * @param string $message
     */
    public static function log($property_id, $unit_id, $sync_type, $status, $message) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                INSERT INTO sync_logs (property_id, unit_id, sync_type, status, message, created_at) 
                VALUES (:property_id, :unit_id, :sync_type, :status, :message, NOW())
            ");
            $stmt->execute([
                ':property_id' => $property_id,
                ':unit_id'     => $unit_id,
                ':sync_type'   => $sync_type,
                ':status'      => $status,
                ':message'     => mb_substr($message, 0, 65530, 'UTF-8')
            ]);
        } catch (PDOException $e) {
            error_log("SYNC LOGGING FAILED: " . $e->getMessage());
        }
    }
}
