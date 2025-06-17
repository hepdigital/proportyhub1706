<?php
require_once 'includes/init.php';

// Gerekli parametreleri al ve temizle
$type = $_GET['type'] ?? null; // 'room' (birleştirilmiş) veya 'unit' (bireysel)
$key = $_GET['key'] ?? null;
$id = $_GET['id'] ?? null; // type=unit için unit_id, type=room için wp_room_id
$group_id = $_GET['group_id'] ?? null; // type=room için kullanılır (hem wp_room_id hem de manuel grup adı olabilir)

// URL yapısına göre ID'yi ayarla
if ($type === 'room' && $group_id) {
    $id_to_process = $group_id;
} elseif ($type === 'unit' && $id) {
    $id_to_process = (int)$id;
} else {
    http_response_code(400); 
    exit('Eksik veya hatalı parametre.');
}

$propertyModel = new Property();
$unitModel = new Unit();
$db = Database::getInstance()->getConnection();

// --- Güvenlik Kontrolü ---
$stmt_prop_check = $db->prepare("SELECT id FROM properties WHERE ical_export_key = :key");
$stmt_prop_check->execute([':key' => $key]);
$property_check = $stmt_prop_check->fetch(PDO::FETCH_ASSOC);

if (!$property_check) {
    http_response_code(403); 
    exit('Geçersiz güvenlik anahtarı.');
}
$property_id = $property_check['id'];
// --- Güvenlik Kontrolü Bitişi ---

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="calendar.ics"');

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//PropertyHub//v1.0//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";

switch ($type) {
    // --- ODA TİPİ BAZINDA BİRLEŞTİRİLMİŞ TAKVİM ---
    case 'room':
        $unit_ids_in_group = [];
        // Gelen group_id'nin numerik olup olmadığına bakarak WP odası mı manuel mi olduğunu anla
        if (is_numeric($id_to_process)) {
            // WordPress odası (wp_room_id)
            $stmt = $db->prepare("SELECT id FROM units WHERE property_id = ? AND wp_room_id = ?");
            $stmt->execute([$property_id, (int)$id_to_process]);
            $unit_ids_in_group = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // Manuel olarak eklenmiş oda grubu (grup adına göre)
            $stmt = $db->prepare("SELECT id FROM units WHERE property_id = ? AND name LIKE ?");
            $stmt->execute([$property_id, urldecode($id_to_process) . ' - Ünite %']);
            $unit_ids_in_group = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        $total_units_for_group = count($unit_ids_in_group);

        if ($total_units_for_group > 0) {
            $placeholders = rtrim(str_repeat('?,', $total_units_for_group), ',');
            $stmt_booked = $db->prepare("
                SELECT date FROM availability 
                WHERE unit_id IN ($placeholders) AND is_available = 0 
                GROUP BY date 
                HAVING COUNT(DISTINCT unit_id) >= ?
            ");
            $params = array_merge($unit_ids_in_group, [$total_units_for_group]);
            $stmt_booked->execute($params);
            $full_booked_days = $stmt_booked->fetchAll(PDO::FETCH_ASSOC);

            foreach ($full_booked_days as $day) {
                echo "BEGIN:VEVENT\r\n";
                echo "UID:" . md5($day['date'] . $id_to_process . $key) . "@propertyhub.vibesmode.xyz\r\n";
                echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
                echo "DTSTART;VALUE=DATE:" . str_replace('-', '', $day['date']) . "\r\n";
                echo "DTEND;VALUE=DATE:" . str_replace('-', '', date('Y-m-d', strtotime($day['date'] . ' +1 day'))) . "\r\n";
                echo "SUMMARY:Dolu / Müsait Değil\r\n";
                echo "END:VEVENT\r\n";
            }
        }
        break;

    // --- BİREYSEL ÜNİTE TAKVİMİ ---
    case 'unit':
        $unit_id = (int)$id_to_process;
        $stmt = $db->prepare("SELECT * FROM availability WHERE unit_id = :unit_id AND is_available = 0");
        $stmt->execute([':unit_id' => $unit_id]);
        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($reservations as $res) {
            echo "BEGIN:VEVENT\r\n";
            echo "UID:" . ($res['reservation_id'] ?? md5($res['date'] . $unit_id)) . "@propertyhub.vibesmode.xyz\r\n";
            echo "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
            echo "DTSTART;VALUE=DATE:" . str_replace('-', '', $res['date']) . "\r\n";
            echo "DTEND;VALUE=DATE:" . str_replace('-', '', date('Y-m-d', strtotime($res['date'] . ' +1 day'))) . "\r\n";
            echo "SUMMARY:Rezerve (" . ($res['sync_source'] ?? '') . ")\r\n";
            echo "END:VEVENT\r\n";
        }
        break;
}

echo "END:VCALENDAR\r\n";