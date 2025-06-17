<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// --- GÜVENLİK KONTROLÜ ---
$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'owner') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$owner_id = $_SESSION['user_id'];
$db = Database::getInstance()->getConnection();

try {
    // --- GENEL İSTATİSTİKLER ---
    
    // Hatalı logların sayısı
    $error_log_stmt = $db->prepare("
        SELECT COUNT(l.id) FROM sync_logs l
        JOIN properties p ON l.property_id = p.id
        WHERE p.owner_id = :owner_id AND l.status = 'error'
    ");
    $error_log_stmt->execute([':owner_id' => $owner_id]);
    $error_log_count = $error_log_stmt->fetchColumn();

    // Sahibin tüm unit ID'lerini al
    $units_stmt = $db->prepare("
        SELECT u.id FROM units u
        JOIN properties p ON u.property_id = p.id
        WHERE p.owner_id = :owner_id
    ");
    $units_stmt->execute([':owner_id' => $owner_id]);
    $unit_ids = $units_stmt->fetchAll(PDO::FETCH_COLUMN);
    $total_owner_units = count($unit_ids);
    
    // 30 günlük doluluk oranı
    $occupancy_rate_30_days = 0;
    if ($total_owner_units > 0) {
        $start_date_30 = date('Y-m-d');
        $end_date_30 = date('Y-m-d', strtotime('+29 days'));
        
        $placeholders = implode(',', array_fill(0, $total_owner_units, '?'));

        $booked_days_stmt = $db->prepare("
            SELECT COUNT(id) FROM availability
            WHERE unit_id IN ($placeholders)
            AND date BETWEEN ? AND ? AND is_available = 0
        ");
        $params = array_merge($unit_ids, [$start_date_30, $end_date_30]);
        $booked_days_stmt->execute($params);
        $total_booked_room_nights = $booked_days_stmt->fetchColumn();
        
        $total_possible_room_nights = $total_owner_units * 30;
        if ($total_possible_room_nights > 0) {
            $occupancy_rate_30_days = round(($total_booked_room_nights / $total_possible_room_nights) * 100);
        }
    }


    // --- GRAFİK VERİSİ (SONRAKİ 7 GÜN) ---
    $chart_labels = [];
    $chart_data = [];
    if ($total_owner_units > 0) {
        $placeholders_chart = implode(',', array_fill(0, $total_owner_units, '?'));
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $chart_labels[] = date('d M', strtotime($date)); // '13 Haz' formatında

            $booked_on_date_stmt = $db->prepare("
                SELECT COUNT(id) FROM availability
                WHERE unit_id IN ($placeholders_chart)
                AND date = ? AND is_available = 0
            ");
            $params_chart = array_merge($unit_ids, [$date]);
            $booked_on_date_stmt->execute($params_chart);
            $chart_data[] = $booked_on_date_stmt->fetchColumn();
        }
    }

    // --- JSON ÇIKTISI ---
    echo json_encode([
        'success' => true,
        'stats' => [
            'error_log_count' => (int)$error_log_count,
            'occupancy_rate_30_days' => (int)$occupancy_rate_30_days,
        ],
        'chart_data' => [
            'labels' => $chart_labels,
            'data' => $chart_data,
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
