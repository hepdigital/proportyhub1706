<?php
// classes/Pricing.php (YENİ DOSYA)

class Pricing {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Belirli bir oda tipine ait tüm fiyat kurallarını getirir.
     * @param int $unit_type_id
     * @return array
     */
        public function getRulesForUnitType($unit_type_id) {
        $stmt = $this->db->prepare("SELECT * FROM pricing_rules WHERE unit_type_id = :unit_type_id ORDER BY priority DESC, name ASC");
        $stmt->execute([':unit_type_id' => $unit_type_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Yeni bir fiyat kuralı oluşturur.
     * @param array $data
     * @return bool
     */
    public function createRule($data) {
        $sql = "INSERT INTO pricing_rules (property_id, unit_type_id, name, type, start_date, end_date, days_of_week, price, min_stay, priority) 
                VALUES (:property_id, :unit_type_id, :name, :type, :start_date, :end_date, :days_of_week, :price, :min_stay, :priority)";
        
        // DÜZELTME: Sadece 'days_of_week' bir dizi ise implode yap, değilse null ata.
        // Bu, tek günlük fiyat eklerken oluşan PHP uyarısını engeller.
        $days_of_week_str = null;
        if (isset($data['days_of_week']) && is_array($data['days_of_week'])) {
            $days_of_week_str = implode(',', $data['days_of_week']);
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':property_id' => $data['property_id'],
            ':unit_type_id' => $data['unit_type_id'],
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':start_date' => ($data['type'] == 'date_range') ? ($data['start_date'] ?? null) : null,
            ':end_date' => ($data['type'] == 'date_range') ? ($data['end_date'] ?? null) : null,
            ':days_of_week' => ($data['type'] == 'day_of_week') ? $days_of_week_str : null,
            ':price' => $data['price'],
            ':min_stay' => $data['min_stay'] ?? 1,
            ':priority' => $data['priority'] ?? 0
        ]);
    }

    /**
     * Belirtilen bir kuralı siler.
     * @param int $rule_id
     * @param int $owner_id (Güvenlik için)
     * @return bool
     */
    public function deleteRule($rule_id, $owner_id) {
        // Güvenlik: Silinmek istenen kuralın bu owner'a ait bir tesise bağlı olduğunu doğrula.
        $sql = "DELETE pr FROM pricing_rules pr JOIN properties p ON pr.property_id = p.id WHERE pr.id = :rule_id AND p.owner_id = :owner_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':rule_id' => $rule_id, ':owner_id' => $owner_id]);
    }
    
        public function calculateDailyPrices($unit_type_id, $start_date, $end_date) {
        $unit_type_stmt = $this->db->prepare("SELECT base_price FROM unit_types WHERE id = :id");
        $unit_type_stmt->execute([':id' => $unit_type_id]);
        $base_price = $unit_type_stmt->fetchColumn();

        $rules = $this->getRulesForUnitType($unit_type_id);

        $daily_prices = [];
        $current_date = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);

        while ($current_date <= $end_date_obj) {
            $date_str = $current_date->format('Y-m-d');
            $day_of_week = $current_date->format('N'); // 1 (Pzt) - 7 (Pzr)
            
            $final_price = $base_price;

            foreach ($rules as $rule) {
                $rule_applies = false;
                if ($rule['type'] === 'date_range' && !empty($rule['start_date']) && !empty($rule['end_date']) && $date_str >= $rule['start_date'] && $date_str <= $rule['end_date']) {
                    $rule_applies = true;
                } elseif ($rule['type'] === 'day_of_week' && !empty($rule['days_of_week']) && in_array($day_of_week, explode(',', $rule['days_of_week']))) {
                    $rule_applies = true;
                }

                if ($rule_applies) {
                    $final_price = $rule['price'];
                    break; 
                }
            }
            $daily_prices[$date_str] = $final_price;
            $current_date->modify('+1 day');
        }

        return $daily_prices;
    }

public function createOrUpdatePriceForDate($unit_type_id, $date, $price, $property_id) {
        $stmt_find = $this->db->prepare("SELECT id FROM pricing_rules WHERE unit_type_id = :unit_type_id AND start_date = :date AND end_date = :date AND priority = 99");
        $stmt_find->execute([':unit_type_id' => $unit_type_id, ':date' => $date]);
        $existing_rule_id = $stmt_find->fetchColumn();

        if ($existing_rule_id) {
            $stmt_update = $this->db->prepare("UPDATE pricing_rules SET price = :price WHERE id = :id");
            return $stmt_update->execute([':price' => $price, ':id' => $existing_rule_id]);
        } else {
            return $this->createRule([
                'property_id' => $property_id,
                'unit_type_id' => $unit_type_id,
                'name' => 'Özel Fiyat: ' . $date,
                'type' => 'date_range',
                'start_date' => $date,
                'end_date' => $date,
                'price' => $price,
                'min_stay' => 1,
                'priority' => 99,
                'days_of_week' => null // 'days_of_week' için null gönderiyoruz
            ]);
        }
}
}