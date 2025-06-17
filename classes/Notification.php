<?php
// classes/Notification.php

class Notification {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Yeni bir bildirim oluşturur.
     * @param int $owner_id Bildirimi alacak kullanıcı ID'si.
     * @param string $message Gösterilecek mesaj.
     * @param string|null $link Tıklama linki.
     * @param int|null $reservation_id İlgili rezervasyonun ID'si.
     * @return bool
     */
    public function create($owner_id, $message, $link = null, $reservation_id = null) {
        $sql = "INSERT INTO notifications (owner_id, reservation_id, message, link) VALUES (:owner_id, :res_id, :message, :link)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':owner_id' => $owner_id,
            ':res_id' => $reservation_id,
            ':message' => $message,
            ':link' => $link
        ]);
    }
    
    /**
     * Tıklandığında rezervasyon detaylarını modal'da göstermek için gerekli tüm veriyi çeker.
     * @param int $notification_id
     * @param int $owner_id Güvenlik için, bildirimin bu sahibe ait olduğunu doğrular.
     * @return array|false
     */
    public function getReservationDetailsForNotification($notification_id, $owner_id) {
        $sql = "
            SELECT
                r.guest_name,
                r.guest_phone,
                r.guest_email,
                r.start_date,
                r.end_date,
                r.total_price,
                r.commission_amount,
                (r.total_price - r.commission_amount) as net_income,
                agent.name as agent_name,
                u.name as unit_name,
                ut.name as unit_type_name
            FROM notifications n
            JOIN reservations r ON n.reservation_id = r.id
            LEFT JOIN users agent ON r.booked_by_agent_id = agent.id
            LEFT JOIN units u ON r.unit_id = u.id
            LEFT JOIN unit_types ut ON r.unit_type_id = ut.id
            WHERE n.id = :notification_id AND n.owner_id = :owner_id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':notification_id' => $notification_id, ':owner_id' => $owner_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUnreadCount($owner_id) {
        $sql = "SELECT COUNT(id) FROM notifications WHERE owner_id = :owner_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':owner_id' => $owner_id]);
        return (int) $stmt->fetchColumn();
    }

    public function getNotifications($owner_id) {
        $sql = "SELECT * FROM notifications WHERE owner_id = :owner_id ORDER BY created_at DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':owner_id' => $owner_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function markAllAsRead($owner_id) {
        $sql = "UPDATE notifications SET is_read = 1 WHERE owner_id = :owner_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':owner_id' => $owner_id]);
    }
}
