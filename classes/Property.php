<?php
class Property {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Tüm tesisleri veya belirli bir sahibe ait tesisleri listeler.
     * @param int|null $owner_id
     * @return array
     */
     
    public function getAllForAgents($filters = []) {
    $sql = "
        SELECT 
            p.*, 
            (SELECT COUNT(u.id) FROM units u WHERE u.property_id = p.id) as unit_count,
            (SELECT GROUP_CONCAT(pt.name SEPARATOR ', ') 
             FROM property_to_type_map ptm
             JOIN property_types pt ON ptm.type_id = pt.id
             WHERE ptm.property_id = p.id) as property_types,
            (SELECT GROUP_CONCAT(CONCAT(a.name, ':', a.icon) SEPARATOR '|')
             FROM property_to_amenity_map pam
             JOIN amenities a ON pam.amenity_id = a.id
             WHERE pam.property_id = p.id) as amenities_list
        FROM properties p
    ";

    $where_clauses = ['p.status = 1']; // Her zaman sadece aktif tesisleri getir
    $params = [];

    // İl filtresi
    if (!empty($filters['province'])) {
        $where_clauses[] = 'p.address_province = :province';
        $params[':province'] = $filters['province'];
    }

    // Tesis Hizmetleri filtresi
    if (!empty($filters['amenities']) && is_array($filters['amenities'])) {
        foreach ($filters['amenities'] as $index => $amenity_id) {
            // Her bir hizmet için tesisin o hizmete sahip olup olmadığını kontrol et
            $where_clauses[] = "EXISTS (SELECT 1 FROM property_to_amenity_map pam{$index} WHERE pam{$index}.property_id = p.id AND pam{$index}.amenity_id = :amenity_id{$index})";
            $params[":amenity_id{$index}"] = $amenity_id;
        }
    }

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(' AND ', $where_clauses);
    }
    
    $sql .= " ORDER BY p.name ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getAll($owner_id = null) {
    // GROUP_CONCAT ile tesis türleri ve hizmetleri tek sorguda birleştirilir.
    $sql = "
        SELECT 
            p.*, 
            (SELECT COUNT(u.id) FROM units u WHERE u.property_id = p.id) as unit_count,
            
            (SELECT GROUP_CONCAT(pt.name SEPARATOR ', ') 
             FROM property_to_type_map ptm
             JOIN property_types pt ON ptm.type_id = pt.id
             WHERE ptm.property_id = p.id) as property_types,

            (SELECT GROUP_CONCAT(CONCAT(a.name, ':', a.icon) SEPARATOR '|')
             FROM property_to_amenity_map pam
             JOIN amenities a ON pam.amenity_id = a.id
             WHERE pam.property_id = p.id) as amenities_list

        FROM properties p
    ";
    
    $params = [];
    if ($owner_id !== null) {
        $sql .= " WHERE p.owner_id = :owner_id";
        $params[':owner_id'] = $owner_id;
    }
    
    $sql .= " ORDER BY p.name ASC";
    
    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
    
    /**
     * Belirtilen ID'ye sahip tesisi getirir.
     * @param int $id
     * @return array|false
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM properties WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Yeni bir tesis ve bağlı verilerini oluşturur.
     * @param array $data Formdan gelen tüm verileri içeren dizi
     * @return string|false Başarılıysa yeni tesisin ID'si, değilse false.
     */
    public function create($data) {
        $this->db->beginTransaction();
        try {
            $api_key = 'ph_key_' . bin2hex(random_bytes(20));
            $ical_key = bin2hex(random_bytes(32));
            
            $sql = "INSERT INTO properties (owner_id, name, api_key, ical_export_key, commission_rate, cover_photo_path, address_province, address_district, address_full, contact_person_name, contact_phone, reservation_type, pets_allowed, paid_child_age) 
                    VALUES (:owner_id, :name, :api_key, :ical_export_key, :commission_rate, :cover_photo_path, :address_province, :address_district, :address_full, :contact_person_name, :contact_phone, :reservation_type, :pets_allowed, :paid_child_age)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':owner_id' => $data['owner_id'],
                ':name' => $data['name'],
                ':api_key' => $api_key,
                ':ical_export_key' => $ical_key,
                ':commission_rate' => $data['commission_rate'] ?? 15.00,
                ':cover_photo_path' => $data['cover_photo_path'] ?? null,
                ':address_province' => $data['address_province'] ?? null,
                ':address_district' => $data['address_district'] ?? null,
                ':address_full' => $data['address_full'] ?? null,
                ':contact_person_name' => $data['contact_person_name'] ?? null,
                ':contact_phone' => $data['contact_phone'] ?? null,
                ':reservation_type' => $data['reservation_type'] ?? 'manual',
                ':pets_allowed' => $data['pets_allowed'] ?? 0,
                ':paid_child_age' => empty($data['paid_child_age']) ? null : $data['paid_child_age']
            ]);
            $property_id = $this->db->lastInsertId();

            $this->updateRelations($property_id, 'property_to_type_map', 'type_id', $data['property_types'] ?? []);
            $this->updateRelations($property_id, 'property_to_amenity_map', 'amenity_id', $data['amenities'] ?? []);
            $this->updateRelations($property_id, 'property_to_payment_option_map', 'payment_option_id', $data['payment_options'] ?? []);

            $this->db->commit();
            return $property_id;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('Property Creation Failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mevcut bir tesisi ve bağlı verilerini günceller.
     * @param int $id Güncellenecek tesisin ID'si
     * @param array $data Formdan gelen tüm verileri içeren dizi
     * @return bool Başarı durumu
     */
    public function update($id, $data) {
        $this->db->beginTransaction();
        try {
            // SQL SORGUSU TÜM ALANLARI İÇERECEK ŞEKİLDE GÜNCELLENDİ
            $sql = "UPDATE properties SET 
                        name = :name, 
                        commission_rate = :commission_rate, 
                        cover_photo_path = :cover_photo_path, 
                        address_province = :address_province, 
                        address_district = :address_district, 
                        address_full = :address_full, 
                        contact_person_name = :contact_person_name, 
                        contact_phone = :contact_phone, 
                        reservation_type = :reservation_type, 
                        pets_allowed = :pets_allowed, 
                        paid_child_age = :paid_child_age
                    WHERE id = :id AND owner_id = :owner_id";
            
            $stmt = $this->db->prepare($sql);
            // EXECUTE İÇERİSİNDEKİ TÜM PARAMETRELER EKLENDİ
            $stmt->execute([
                ':name' => $data['name'],
                ':commission_rate' => $data['commission_rate'] ?? 15.00,
                ':cover_photo_path' => $data['cover_photo_path'] ?? null,
                ':address_province' => $data['address_province'] ?? null,
                ':address_district' => $data['address_district'] ?? null,
                ':address_full' => $data['address_full'] ?? null,
                ':contact_person_name' => $data['contact_person_name'] ?? null,
                ':contact_phone' => $data['contact_phone'] ?? null,
                ':reservation_type' => $data['reservation_type'] ?? 'manual',
                ':pets_allowed' => $data['pets_allowed'] ?? 0,
                ':paid_child_age' => empty($data['paid_child_age']) ? null : $data['paid_child_age'],
                ':id' => $id,
                ':owner_id' => $data['owner_id']
            ]);

            // İlişkisel verileri güncelle (bu kısım doğruydu)
            $this->updateRelations($id, 'property_to_type_map', 'type_id', $data['property_types'] ?? []);
            $this->updateRelations($id, 'property_to_amenity_map', 'amenity_id', $data['amenities'] ?? []);
            $this->updateRelations($id, 'property_to_payment_option_map', 'payment_option_id', $data['payment_options'] ?? []);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Property Update Failed for ID $id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * İlişkisel eşleştirme tablolarını güncelleyen özel bir metot.
     * @param int $property_id
     * @param string $table_name
     * @param string $relation_column
     * @param array $relation_ids
     */
    private function updateRelations($property_id, $table_name, $relation_column, $relation_ids) {
        $stmt_delete = $this->db->prepare("DELETE FROM {$table_name} WHERE property_id = :property_id");
        $stmt_delete->execute([':property_id' => $property_id]);
        
        if (!empty($relation_ids) && is_array($relation_ids)) {
            $sql_insert = "INSERT INTO {$table_name} (property_id, {$relation_column}) VALUES (:property_id, :relation_id)";
            $stmt_insert = $this->db->prepare($sql_insert);
            foreach ($relation_ids as $relation_id) {
                $stmt_insert->execute([
                    ':property_id' => $property_id,
                    ':relation_id' => (int)$relation_id
                ]);
            }
        }
    }
    
    /**
     * Belirtilen ID'ye sahip tesisi siler.
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM properties WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
    
    // --- Helper Metotları (Frontend'de seçenekleri listelemek için) ---
    // BU METOTLAR YENİ EKLENDİ VE GEREKLİ
    
    public function getAllMasterData($table_name) {
        // Güvenlik için izin verilen tablo isimleri
        $allowed_tables = ['property_types', 'amenities', 'payment_options'];
        if (!in_array($table_name, $allowed_tables)) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT * FROM {$table_name} ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getRelationsForProperty($property_id, $table_name) {
        // Güvenlik için izin verilen tablo isimleri
        $allowed_tables = ['property_to_type_map', 'property_to_amenity_map', 'property_to_payment_option_map'];
        if (!in_array($table_name, $allowed_tables)) {
            return [];
        }
        $stmt = $this->db->prepare("SELECT * FROM {$table_name} WHERE property_id = :property_id");
        $stmt->execute([':property_id' => $property_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
 * Belirli bir tesise ait tüm oda tiplerini listeler.
 * @param int $property_id
 * @return array
 */
public function getUnitTypes($property_id) {
    $sql = "
        SELECT 
            ut.*, 
            (SELECT COUNT(u.id) FROM units u WHERE u.unit_type_id = ut.id) as unit_count,
            (SELECT p.photo_path FROM unit_type_photos p WHERE p.id = ut.cover_photo_id) as cover_photo_path,
            (SELECT GROUP_CONCAT(f.name SEPARATOR ', ') 
             FROM unit_type_to_feature_map map
             JOIN features f ON map.feature_id = f.id
             WHERE map.unit_type_id = ut.id AND f.name IN ('Havuz', 'Özel Havuz', 'Jakuzi', 'Şömine', 'Ateş Çukuru')) as main_features
        FROM unit_types ut
        WHERE ut.property_id = :property_id
        ORDER BY ut.name ASC
    ";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':property_id' => $property_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getFeaturesForUnitType($unit_type_id) {
    $sql = "SELECT feature_id, value FROM unit_type_to_feature_map WHERE unit_type_id = :unit_type_id";
    $stmt = $this->db->prepare($sql);
    $stmt->execute([':unit_type_id' => $unit_type_id]);
    // feature_id'yi anahtar yaparak daha kolay erişilebilir bir dizi döndür
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

/**
 * Bir oda tipinin özelliklerini günceller (önce siler, sonra ekler).
 * @param int $unit_type_id
 * @param array $features
 * @return bool
 */
public function updateFeaturesForUnitType($unit_type_id, $features) {
    // Önce mevcut tüm özellikleri sil
    $stmt_delete = $this->db->prepare("DELETE FROM unit_type_to_feature_map WHERE unit_type_id = :unit_type_id");
    $stmt_delete->execute([':unit_type_id' => $unit_type_id]);

    if (empty($features) || !is_array($features)) {
        return true; // Eklenecek özellik yoksa işlemi bitir.
    }

    // Gelen yeni özellikleri ekle
    $sql_insert = "INSERT INTO unit_type_to_feature_map (unit_type_id, feature_id, value) VALUES (:unit_type_id, :feature_id, :value)";
    $stmt_insert = $this->db->prepare($sql_insert);

    foreach ($features as $feature_id => $value) {
        $stmt_insert->execute([
            ':unit_type_id' => $unit_type_id,
            ':feature_id' => $feature_id,
            // Boolean özellikler için değer 1, diğerleri için formdan gelen değer
            ':value' => is_numeric($value) ? $value : (($value === 'on' || $value === true) ? '1' : $value)
        ]);
    }
    return true;
}
}