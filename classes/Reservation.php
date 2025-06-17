<?php
// classes/Reservation.php

class Reservation {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Acente tarafından gelen bir rezervasyon talebini işler, veritabanına kaydeder
     * ve ilgili güncellemeleri yapar.
     *
     * @param array $data Rezervasyon verileri (agent_id, property_id, unit_type_id, start_date, end_date, guest_name vb.)
     * @return array Başarı veya hata durumu ve mesajı içeren bir dizi.
     */
    public function createAgentReservation($data) {
        $this->db->beginTransaction();
        try {
            // Adım 1: Gerekli verileri kontrol et
            $required_fields = ['agent_id', 'property_id', 'unit_type_id', 'start_date', 'end_date', 'guest_name'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Eksik parametre: " . $field);
                }
            }

            // Adım 2: Belirtilen tarihlerde ve oda tipinde MÜSAİT bir FİZİKSEL BİRİM (unit) bul.
            $available_unit_id = $this->findAvailableUnit($data['unit_type_id'], $data['start_date'], $data['end_date']);
            if (!$available_unit_id) {
                throw new Exception("Seçilen tarihlerde bu oda tipinde müsait birim bulunamadı.");
            }

            // Adım 3: Fiyatı ve komisyonu hesapla
            $pricing_info = $this->calculatePricingForStay($data['unit_type_id'], $data['start_date'], $data['end_date']);
            $property_info = (new Property())->getById($data['property_id']);
            $commission_rate = $property_info['commission_rate'] ?? 0;
            $commission_amount = ($pricing_info['total_price'] * $commission_rate) / 100;

            // Adım 4: `reservations` tablosuna ana kaydı ekle
            $reservation_uid = 'AGENT-' . strtoupper(bin2hex(random_bytes(8)));
            $sql_insert_res = "
                INSERT INTO reservations 
                    (reservation_uid, property_id, unit_type_id, unit_id, booked_by_agent_id, guest_name, guest_phone, guest_email, start_date, end_date, total_price, commission_rate, commission_amount, status)
                VALUES 
                    (:uid, :prop_id, :utype_id, :unit_id, :agent_id, :g_name, :g_phone, :g_email, :start, :end, :price, :com_rate, :com_amount, 'confirmed')
            ";
            $stmt_res = $this->db->prepare($sql_insert_res);
            $stmt_res->execute([
                ':uid' => $reservation_uid,
                ':prop_id' => $data['property_id'],
                ':utype_id' => $data['unit_type_id'],
                ':unit_id' => $available_unit_id,
                ':agent_id' => $data['agent_id'],
                ':g_name' => $data['guest_name'],
                ':g_phone' => $data['guest_phone'] ?? null,
                ':g_email' => $data['guest_email'] ?? null,
                ':start' => $data['start_date'],
                ':end' => $data['end_date'],
                ':price' => $pricing_info['total_price'],
                ':com_rate' => $commission_rate,
                ':com_amount' => $commission_amount
            ]);
            $new_reservation_id = $this->db->lastInsertId();

            // Adım 5: `availability` tablosundaki ilgili günleri rezerve (dolu) olarak işaretle
            $this->blockAvailability($available_unit_id, $data['start_date'], $data['end_date'], $new_reservation_id);

            // Adım 6: Tesis sahibine bildirim oluştur
            $agent_user = (new User())->findById($data['agent_id']);
            $agent_name = $agent_user['name'] ?? 'Bilinmeyen Acente';
            $unit_type_info = (new Unit())->getUnitTypeById($data['unit_type_id']);
            $notification_message = "{$agent_name}, '{$unit_type_info['name']}' odanız için yeni bir rezervasyon oluşturdu.";
            (new Notification())->create(
                $property_info['owner_id'],
                $notification_message,
                '#', // Link artık JS ile yönetileceği için # yapabiliriz
                $new_reservation_id // Yeni eklenen rezervasyonun ID'sini de gönderiyoruz
                );

            $this->db->commit();
            return ['success' => true, 'message' => 'Rezervasyon başarıyla oluşturuldu.'];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Belirtilen oda tipi ve tarih aralığı için müsait olan ilk fiziksel birimin ID'sini bulur.
     */
    private function findAvailableUnit($unit_type_id, $start_date, $end_date) {
        $sql = "
            SELECT u.id FROM units u
            WHERE u.unit_type_id = :unit_type_id
            AND NOT EXISTS (
                SELECT 1 FROM availability a
                WHERE a.unit_id = u.id AND a.is_available = 0 AND a.date >= :start_date AND a.date < :end_date
            )
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':unit_type_id' => $unit_type_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date
        ]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Konaklama için toplam fiyatı hesaplar.
     */
    private function calculatePricingForStay($unit_type_id, $start_date, $end_date) {
        $pricing_class = new Pricing();
        $daily_prices = $pricing_class->calculateDailyPrices($unit_type_id, $start_date, $end_date);
        
        $total_price = 0;
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        
        while($current_date < $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            $total_price += $daily_prices[$date_str] ?? 0;
            $current_date->modify('+1 day');
        }

        return ['total_price' => $total_price];
    }

    /**
     * Belirli bir birimin tarihlerini `availability` tablosunda dolu olarak işaretler.
     */
    private function blockAvailability($unit_id, $start_date, $end_date, $reservation_id) {
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        $calendar_class = new Calendar();

        while ($current_date < $end_date_obj) {
            $calendar_class->updateAvailability(
                $unit_id, 
                $current_date->format('Y-m-d'), 
                false,              // is_available = false (dolu)
                $reservation_id,    // Yeni `reservations` tablosuna bağlantı
                'agent_booking'     // Kaynağı belirt
            );
            $current_date->modify('+1 day');
        }
    }
}
