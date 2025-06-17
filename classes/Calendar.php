<?php
class Calendar {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * iCalendar (.ics) formatındaki metin içeriğini ayrıştırarak etkinlikleri bir diziye dönürür.
     * @param string $ical_content .ics dosyasının içeriği
     * @return array Etkinlik dizisi
     */
    public function parseIcal($ical_content) {
        // Bu fonksiyonda değişiklik yok, olduğu gibi kalabilir.
        $events = [];
        $lines = explode("\n", $ical_content);
        $event = null;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === 'BEGIN:VEVENT') { $event = []; } 
            elseif ($line === 'END:VEVENT' && $event !== null) { $events[] = $event; $event = null; } 
            elseif ($event !== null) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $key = trim($key);
                    if (strpos($key, ';') !== false) { list($key, ) = explode(';', $key, 2); }
                    if (in_array($key, ['DTSTART', 'DTEND', 'SUMMARY', 'UID'])) { $event[$key] = trim($value); }
                }
            }
        }
        return $events;
    }
    
    /**
     * Belirli bir ünite için belirtilen tarih aralığındaki müsaitlik durumunu veritabanından alır.
     * @param int $unit_id
     * @param string $start_date 'Y-m-d' formatında başlangıç tarihi
     * @param string $end_date 'Y-m-d' formatında bitiş tarihi
     * @return array Tarihlerin müsaitlik durumunu içeren dizi
     */
    public function getAvailability($unit_id, $start_date, $end_date) {
        // Bu fonksiyonda değişiklik yok, olduğu gibi kalabilir.
        $stmt = $this->db->prepare("SELECT date, is_available, reservation_id FROM availability WHERE unit_id = :unit_id AND date BETWEEN :start_date AND :end_date ORDER BY date ASC");
        $stmt->execute([':unit_id' => $unit_id, ':start_date' => $start_date, ':end_date' => $end_date]);
        $availability = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $availability[$row['date']] = ['available' => (bool)$row['is_available'], 'reservation_id' => $row['reservation_id']];
        }
        return $availability;
    }
    
    /**
     * Belirli bir ünitenin belirli bir tarihteki müsaitlik durumunu günceller veya yeni kayıt ekler.
     * Bu versiyon, karmaşık olan 'reservation_uid' mantığını içermez.
     * @param int $unit_id
     * @param string $date 'Y-m-d' formatında tarih
     * @param bool $is_available
     * @param string|null $reservation_id Rezervasyon adı veya notu
     * @param string $source Kaydın kaynağı (örn: 'manual', 'ical')
     * @return bool
     */
     public function updateAvailability($unit_id, $date, $is_available, $reservation_id = null, $source = 'ical', $reservation_uid = null) {
    $stmt = $this->db->prepare("
        INSERT INTO availability (unit_id, date, is_available, reservation_id, sync_source, reservation_uid)
        VALUES (:unit_id, :date, :is_available, :reservation_id, :source, :reservation_uid)
        ON DUPLICATE KEY UPDATE 
            is_available = VALUES(is_available),
            reservation_id = VALUES(reservation_id),
            sync_source = VALUES(sync_source),
            reservation_uid = VALUES(reservation_uid)
    ");

    return $stmt->execute([
        ':unit_id' => $unit_id,
        ':date' => $date,
        ':is_available' => $is_available ? 1 : 0,
        ':reservation_id' => $reservation_id,
        ':source' => $source,
        ':reservation_uid' => $reservation_uid
    ]);
}
}
