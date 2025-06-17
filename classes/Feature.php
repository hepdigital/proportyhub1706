<?php
// classes/Feature.php (YENİ DOSYA)

class Feature {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Tüm özellik kategorilerini, içerdikleri özelliklerle birlikte getirir.
     * @return array
     */
    public function getAllCategoriesWithFeatures() {
        $sql = "
            SELECT 
                fc.id as category_id, fc.name as category_name,
                f.id as feature_id, f.name as feature_name, f.icon as feature_icon, f.type as feature_type
            FROM feature_categories fc
            LEFT JOIN features f ON fc.id = f.category_id
            ORDER BY fc.display_order, f.name
        ";
        $stmt = $this->db->query($sql);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Veriyi kategorilere göre gruplayalım
        $categorized = [];
        foreach ($results as $row) {
            $categorized[$row['category_id']]['category_name'] = $row['category_name'];
            if ($row['feature_id']) {
                $categorized[$row['category_id']]['features'][] = [
                    'id' => $row['feature_id'],
                    'name' => $row['feature_name'],
                    'icon' => $row['feature_icon'],
                    'type' => $row['feature_type']
                ];
            }
        }
        return $categorized;
    }
}