<?php
// classes/Report.php

class Report {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Belirtilen tarih aralığı için temel performans metriklerini hesaplar.
     * @param int $owner_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getDashboardStats($owner_id, $start_date, $end_date) {
        $sql = "
            SELECT
                COUNT(id) as total_reservations,
                SUM(total_price) as total_revenue,
                SUM(commission_amount) as total_commission,
                SUM(total_price - commission_amount) as net_income,
                SUM(DATEDIFF(end_date, start_date)) as total_booked_nights
            FROM reservations
            WHERE property_id IN (SELECT id FROM properties WHERE owner_id = :owner_id)
            AND start_date <= :end_date AND end_date >= :start_date
              AND status = 'confirmed'
        ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':owner_id' => $owner_id, ':start_date' => $start_date, ':end_date' => $end_date]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $total_units_stmt = $this->db->prepare("SELECT COUNT(u.id) FROM units u JOIN properties p ON u.property_id = p.id WHERE p.owner_id = :owner_id");
        $total_units_stmt->execute([':owner_id' => $owner_id]);
        $total_units = $total_units_stmt->fetchColumn();
        
        $period_days = (new DateTime($end_date))->diff(new DateTime($start_date))->days + 1;
        $total_possible_nights = $total_units * $period_days;
        
        $stats['occupancy_rate'] = 0;
        if ($total_possible_nights > 0 && ($stats['total_booked_nights'] ?? 0) > 0) {
            $stats['occupancy_rate'] = round(($stats['total_booked_nights'] / $total_possible_nights) * 100);
        }

        return $stats;
    }

    /**
     * Acentelerin performansını analiz eder.
     * @param int $owner_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getAgentPerformance($owner_id, $start_date, $end_date) {
        $sql = "
            SELECT u.name as agent_name, COUNT(r.id) as reservation_count, SUM(r.total_price) as total_revenue, SUM(r.commission_amount) as total_commission
            FROM reservations r JOIN users u ON r.booked_by_agent_id = u.id
            WHERE r.property_id IN (SELECT id FROM properties WHERE owner_id = :owner_id)
            AND r.start_date <= :end_date AND r.end_date >= :start_date AND r.status = 'confirmed'
            GROUP BY r.booked_by_agent_id, u.name ORDER BY total_revenue DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':owner_id' => $owner_id, ':start_date' => $start_date, ':end_date' => $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Oda tiplerinin performansını analiz eder.
     * @param int $owner_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    public function getRoomTypePerformance($owner_id, $start_date, $end_date) {
        $sql = "
            SELECT ut.name as unit_type_name, p.name as property_name, COUNT(r.id) as reservation_count, SUM(r.total_price) as total_revenue
            FROM reservations r JOIN unit_types ut ON r.unit_type_id = ut.id JOIN properties p ON r.property_id = p.id
            WHERE r.property_id IN (SELECT id FROM properties WHERE owner_id = :owner_id)
            AND r.start_date <= :end_date AND r.end_date >= :start_date AND r.status = 'confirmed'
            GROUP BY r.unit_type_id, ut.name, p.name ORDER BY total_revenue DESC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':owner_id' => $owner_id, ':start_date' => $start_date, ':end_date' => $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Yaklaşan 5 rezervasyonu getirir. (YENİ METOT)
     * @param int $owner_id
     * @return array
     */
    public function getUpcomingReservations($owner_id) {
        $sql = "
            SELECT r.guest_name, r.start_date, r.end_date, ut.name as unit_type_name
            FROM reservations r
            JOIN unit_types ut ON r.unit_type_id = ut.id
            WHERE r.property_id IN (SELECT id FROM properties WHERE owner_id = :owner_id)
            AND r.start_date >= CURDATE() AND r.status = 'confirmed'
            ORDER BY r.start_date ASC
            LIMIT 5
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':owner_id' => $owner_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Son 6 ayın gelir verisini grafik için hazırlar. (YENİ METOT)
     * @param int $owner_id
     * @return array
     */
    public function getMonthlyRevenueChartData($owner_id) {
        $labels = [];
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = new DateTime("first day of -$i months");
            $labels[] = $date->format('M Y'); // Etiket: 'Haz 2025'
            
            $startOfMonth = $date->format('Y-m-01');
            $endOfMonth = $date->format('Y-m-t');

            $sql = "
                SELECT SUM(total_price - commission_amount) as net_income
                FROM reservations
                WHERE property_id IN (SELECT id FROM properties WHERE owner_id = :owner_id)
                AND start_date <= :end_date AND end_date >= :start_date AND status = 'confirmed'
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':owner_id' => $owner_id, ':start_date' => $startOfMonth, ':end_date' => $endOfMonth]);
            $result = $stmt->fetchColumn();
            $data[] = $result ?? 0;
        }
        return ['labels' => $labels, 'data' => $data];
    }
}
