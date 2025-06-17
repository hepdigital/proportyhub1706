<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// --- Security Check ---
$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'tesis_sahibi') {
    jsonResponse(['success' => false, 'error' => 'Unauthorized access.'], 403);
    exit();
}
$owner_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['action'])) {
    jsonResponse(['success' => false, 'error' => 'Invalid request.'], 400);
    exit();
}

$db = Database::getInstance()->getConnection();
$calendar = new Calendar();
$action = $data['action'];

try {
    // --- Helper Function ---
    function deleteReservationsByUid($db, $reservation_uid, $owner_id) {
        if (empty($reservation_uid)) return false;
        // GÜVENLİK: Sadece bu sahibe ait rezervasyonların silindiğinden emin ol.
        $stmt = $db->prepare("
            DELETE a FROM availability a
            JOIN units u ON a.unit_id = u.id
            JOIN properties p ON u.property_id = p.id
            WHERE a.reservation_uid = :reservation_uid AND p.owner_id = :owner_id
        ");
        return $stmt->execute([':reservation_uid' => $reservation_uid, ':owner_id' => $owner_id]);
    }

    switch ($action) {
        case 'create':
        case 'update':
            $unit_id = $data['unit_id'] ?? null;
            $start_date_str = $data['start_date'] ?? null;
            $end_date_str_exclusive = $data['end_date'] ?? null;
            $guest_name = trim($data['guest_name'] ?? 'Manuel Blok');
            if (empty($guest_name)) $guest_name = 'Manuel Blok';
            $reservation_uid = $data['reservation_uid'] ?? null;

            // HATA ÇÖZÜMÜ: Yetki kontrolünü daha basit ve doğru bir sorgu ile yap
            $unit_check_stmt = $db->prepare("SELECT p.owner_id FROM units u JOIN properties p ON u.property_id = p.id WHERE u.id = ?");
            $unit_check_stmt->execute([$unit_id]);
            $unit_owner_id = $unit_check_stmt->fetchColumn();

            if($unit_owner_id != $owner_id){
                jsonResponse(['success' => false, 'error' => 'You do not have permission for this unit.'], 403);
                exit();
            }
            
            if ($action === 'update' && !empty($reservation_uid)) {
                deleteReservationsByUid($db, $reservation_uid, $owner_id);
            }

            if ($action === 'create' || empty($reservation_uid)) {
                $reservation_uid = 'manual-' . bin2hex(random_bytes(8));
            }

            $start_date = new DateTime($start_date_str);
            $end_date = new DateTime($end_date_str_exclusive);
            
            $current_date = clone $start_date;
            while ($current_date < $end_date) {
                $calendar->updateAvailability($unit_id, $current_date->format('Y-m-d'), false, $guest_name, 'manual', $reservation_uid);
                $current_date->modify('+1 day');
            }
            
            jsonResponse(['success' => true]);
            break;

        case 'delete':
            $reservation_uid_to_delete = $data['reservation_uid'] ?? null;
            if (!$reservation_uid_to_delete) { jsonResponse(['success' => false, 'error' => 'Reservation UID is missing.'], 400); exit(); }
            if (deleteReservationsByUid($db, $reservation_uid_to_delete, $owner_id)) { jsonResponse(['success' => true]); } 
            else { jsonResponse(['success' => false, 'error' => 'Could not delete reservation.']); }
            break;
    }
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
