<?php
class Unit {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getByProperty($property_id) {
        $stmt = $this->db->prepare("
            SELECT * FROM units 
            WHERE property_id = :property_id 
            ORDER BY unit_number ASC
        ");
        $stmt->bindParam(':property_id', $property_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM units WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function getMaxUnitNumber($property_id) {
        $stmt = $this->db->prepare("SELECT MAX(unit_number) FROM units WHERE property_id = :property_id");
        $stmt->execute([':property_id' => $property_id]);
        return (int)$stmt->fetchColumn();
    }
    
    public function create($data) {
    // DÜZELTME: SQL sorgusuna 'unit_type_id' alanı eklendi.
    $stmt = $this->db->prepare("
        INSERT INTO units (property_id, unit_type_id, name, unit_number) 
        VALUES (:property_id, :unit_type_id, :name, :unit_number)
    ");
    
    // DÜZELTME: execute içerisine ':unit_type_id' parametresi eklendi.
    $stmt->execute([
        ':property_id' => $data['property_id'],
        ':unit_type_id' => $data['unit_type_id'],
        ':name' => $data['name'],
        ':unit_number' => $data['unit_number'] ?? 1
    ]);
    return $this->db->lastInsertId();
}
    
    public function update($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE units 
            SET name = :name, ical_url = :ical_url, last_sync = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':ical_url' => $data['ical_url']
        ]);
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM units WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Belirli bir tesise ait, WordPress'ten senkronize edilmiş tüm üniteleri siler.
     */
    public function deleteWpUnitsByProperty($property_id) {
        $sql = "DELETE FROM units WHERE property_id = :property_id AND wp_room_id IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':property_id' => $property_id]);
    }
    
    /**
     * Belirtilen ünitenin güncel müsaitlik durumunu Google Sheet'e gönderir.
     * @param int $unit_id Güncellenecek ünitenin ID'si
     * @param int $property_id Bağlı olduğu tesisin ID'si (loglama için)
     */
    public function pushAvailabilityToSheet($unit_id, $property_id) {
        // Üniteye bağlı tesisin Google Sheet ID'si var mı diye kontrol et
        $stmt = $this->db->prepare("SELECT google_sheet_id FROM properties WHERE id = :property_id");
        $stmt->execute([':property_id' => $property_id]);
        $property_data = $stmt->fetch(PDO::FETCH_ASSOC);

        $spreadsheetId_or_url = $property_data['google_sheet_id'] ?? null;
        if (empty($spreadsheetId_or_url)) {
            return; // Google Sheet ayarlanmamışsa bir şey yapma.
        }

        $spreadsheetId = extractSheetIdFromUrl($spreadsheetId_or_url);
        if (!$spreadsheetId) {
            Sync::log($property_id, $unit_id, 'google_sheets_push', 'error', 'Geçersiz Google Sheet ID veya URL formatı.');
            return;
        }

        try {
            // Veritabanından en güncel müsaitlik durumunu çek
            $calendarDb = new Calendar();
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d', strtotime('+365 days'));
            $availabilityFromDb = $calendarDb->getAvailability($unit_id, $startDate, $endDate);

            $sheetData = [];
            $currentDate = new DateTime($startDate);
            $endDateDt = new DateTime($endDate);

            while ($currentDate <= $endDateDt) {
                $dateStr = $currentDate->format('Y-m-d');
                $dbEntry = $availabilityFromDb[$dateStr] ?? null;
                $isAvailable = $dbEntry ? (bool)$dbEntry['available'] : true;
                $reservationId = $dbEntry ? $dbEntry['reservation_id'] : '';
                $sheetData[] = [$dateStr, $isAvailable ? 'Müsait' : 'Rezerve', $reservationId];
                $currentDate->modify('+1 day');
            }

            $googleSheetsService = new GoogleSheetsService();
            $googleSheetsService->updateAvailabilityForUnit($spreadsheetId, $unit_id, $sheetData);
            
            Sync::log($property_id, $unit_id, 'google_sheets_push', 'success', 'Google Sheet güncellendi.');

        } catch (Exception $e) {
            // Google API'den gelen hatayı yakala ve bizim log tablomuza yazdır.
            Sync::log($property_id, $unit_id, 'google_sheets_push', 'error', 'Google API HATA: ' . $e->getMessage());
        }
    }
    
    public function createUnitType($data) {
    $this->db->beginTransaction();
    try {
        // DÜZELTME: SQL sorgusu, artık sadece yeni formdan gelen alanları içeriyor.
        $sql = "INSERT INTO unit_types (property_id, name, bedroom_count, bathroom_count, size_sqm, has_kitchen, capacity, base_price, price_per_extra_person) 
                VALUES (:property_id, :name, :bedroom_count, :bathroom_count, :size_sqm, :has_kitchen, :capacity, :base_price, :price_per_extra_person)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':property_id' => $data['property_id'],
            ':name' => $data['name'],
            ':bedroom_count' => $data['bedroom_count'] ?? 1,
            ':bathroom_count' => $data['bathroom_count'] ?? 1,
            ':size_sqm' => empty($data['size_sqm']) ? null : $data['size_sqm'],
            ':has_kitchen' => $data['has_kitchen'] ?? 0,
            // Fiyat ve kapasiteyi varsayılan değerlerle ekleyelim
            ':capacity' => $data['capacity'] ?? 2,
            ':base_price' => $data['base_price'] ?? 0,
            ':price_per_extra_person' => $data['price_per_extra_person'] ?? 0
        ]);
        $unit_type_id = $this->db->lastInsertId();

        // Özellikleri kaydetmek için Property sınıfındaki metodu çağırabiliriz.
        if (!empty($data['features'])) {
            $property_class = new Property();
            $property_class->updateFeaturesForUnitType($unit_type_id, $data['features']);
        }

        $this->db->commit();
        return $unit_type_id;

    } catch (Exception $e) {
        $this->db->rollBack();
        error_log('Unit Type Creation Failed: ' . $e->getMessage());
        return false;
    }
}

    /**
 * Belirli bir ünitenin temel fiyat, kapasite ve ekstra kişi ücretini günceller.
 * @param int $unit_id
 * @param array $data
 * @return bool
 */
public function updateDetails($unit_id, $data) {
    $stmt = $this->db->prepare("
        UPDATE units 
        SET 
            base_price = :base_price,
            capacity = :capacity,
            price_per_extra_person = :price_per_extra_person
        WHERE id = :id
    ");
    return $stmt->execute([
        ':base_price' => $data['base_price'],
        ':capacity' => (int)$data['capacity'],
        ':price_per_extra_person' => $data['price_per_extra_person'],
        ':id' => (int)$unit_id
    ]);
}

public function getUnitTypeById($id) {
    $stmt = $this->db->prepare("SELECT * FROM unit_types WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
public function updateUnitType($unit_type_id, $data) {
    // DÜZELTME: Güncellenecek alanlar listesine yeni eklenen sütunlar dahil edildi.
    $fields = ['name', 'bedroom_count', 'bathroom_count', 'size_sqm', 'has_kitchen'];
    $set_parts = [];
    $params = [':id' => $unit_type_id];

    foreach ($fields as $field) {
        // 'has_kitchen' bir checkbox olduğu için farklı kontrol ediliyor.
        if ($field === 'has_kitchen') {
            $set_parts[] = "has_kitchen = :has_kitchen";
            $params[":has_kitchen"] = isset($data[$field]) ? 1 : 0;
        } elseif (isset($data[$field])) {
            $set_parts[] = "{$field} = :{$field}";
            $params[":{$field}"] = $data[$field];
        }
    }

    if (empty($set_parts)) {
        return true; // Güncellenecek bir şey yoksa başarılı say
    }

    $sql = "UPDATE unit_types SET " . implode(', ', $set_parts) . " WHERE id = :id";
    $stmt = $this->db->prepare($sql);
    
    return $stmt->execute($params);
}

public function getPhotosForUnitType($unit_type_id) {
    $stmt = $this->db->prepare("SELECT * FROM unit_type_photos WHERE unit_type_id = :unit_type_id ORDER BY display_order ASC");
    $stmt->execute([':unit_type_id' => $unit_type_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function handlePhotoUploads($files, $unit_type_id) {
    $upload_dir = __DIR__ . '/../owner/uploads/gallery/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0775, true); }

    foreach ($files['tmp_name'] as $key => $tmp_name) {
        if ($files['error'][$key] === UPLOAD_ERR_OK) {
            $file_extension = pathinfo($files['name'][$key], PATHINFO_EXTENSION);
            $unique_filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
            $physical_destination = $upload_dir . $unique_filename;

            if (move_uploaded_file($tmp_name, $physical_destination)) {
                $db_path = '/owner/uploads/gallery/' . $unique_filename;
                $stmt = $this->db->prepare("INSERT INTO unit_type_photos (unit_type_id, photo_path) VALUES (:unit_type_id, :photo_path)");
                $stmt->execute([':unit_type_id' => $unit_type_id, ':photo_path' => $db_path]);
            }
        }
    }
}

public function deletePhoto($photo_id) {
    // Önce sunucudan dosyayı sil, sonra veritabanından kaydı
    $stmt = $this->db->prepare("SELECT photo_path FROM unit_type_photos WHERE id = :id");
    $stmt->execute([':id' => $photo_id]);
    $path = $stmt->fetchColumn();
    if ($path) {
        // Proje ana dizininden yola çıkarak tam yolu oluştur
        $full_path = realpath(__DIR__ . '/..' . $path);
        if (file_exists($full_path)) {
            unlink($full_path);
        }
    }
    $stmt_delete = $this->db->prepare("DELETE FROM unit_type_photos WHERE id = :id");
    return $stmt_delete->execute([':id' => $photo_id]);
}

public function setCoverPhoto($unit_type_id, $photo_id) {
    $stmt = $this->db->prepare("UPDATE unit_types SET cover_photo_id = :photo_id WHERE id = :unit_type_id");
    return $stmt->execute([':photo_id' => $photo_id, ':unit_type_id' => $unit_type_id]);
}

public function deleteUnitType($unit_type_id) {
    // Bu oda tipine bağlı tüm birimler ve diğer ilişkili veriler
    // veritabanındaki "ON DELETE CASCADE" kuralı sayesinde otomatik olarak silinecektir.
    $stmt = $this->db->prepare("DELETE FROM unit_types WHERE id = :id");
    return $stmt->execute([':id' => $unit_type_id]);
}
public function getByUnitType($unit_type_id) {
    $stmt = $this->db->prepare("SELECT * FROM units WHERE unit_type_id = :unit_type_id ORDER BY name ASC");
    $stmt->execute([':unit_type_id' => $unit_type_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
}