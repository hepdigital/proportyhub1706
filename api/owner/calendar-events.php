<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// --- Security Check ---
$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'tesis_sahibi') {
    echo json_encode([]); 
    exit();
}
$owner_id = $_SESSION['user_id'];
$property_id = $_GET['property_id'] ?? null;
if (!$property_id) {
    echo json_encode([]); 
    exit();
}
$property = new Property();
$prop_data = $property->getById($property_id);
if (!$prop_data || $prop_data['owner_id'] != $owner_id) {
    echo json_encode([]); 
    exit();
}

// --- Data Fetching ---
$db = Database::getInstance()->getConnection();
$start_param = $_GET['start'] ?? date('Y-m-d', strtotime('-1 month'));
$end_param = $_GET['end'] ?? date('Y-m-d', strtotime('+2 months'));

$stmt = $db->prepare("
    SELECT
        a.id, a.unit_id, a.date, a.reservation_id, a.sync_source,
        a.reservation_uid, u.name as unit_name
    FROM availability a
    JOIN units u ON a.unit_id = u.id
    WHERE u.property_id = :property_id
      AND a.is_available = 0
      AND a.date BETWEEN :start AND :end
      AND a.reservation_uid IS NOT NULL
    ORDER BY a.unit_id, a.reservation_uid, a.date ASC
");
$stmt->execute([':property_id' => $property_id, ':start' => $start_param, ':end' => $end_param]);
$all_days = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Group by reservation_uid ---
$grouped = [];
foreach ($all_days as $row) {
    $uid = $row['reservation_uid'];
    if (!isset($grouped[$uid])) {
        $grouped[$uid] = [
            'reservation_id' => $row['reservation_id'],
            'unit_id' => $row['unit_id'],
            'unit_name' => $row['unit_name'],
            'dates' => [],
        ];
    }
    $grouped[$uid]['dates'][] = $row['date'];
}

// --- Formatting for FullCalendar ---
$events = [];
$unit_colors = [];
$colors = ['#3b82f6', '#16a34a', '#ca8a04', '#dc2626', '#8b5cf6', '#db2777', '#0d9488'];
$color_index = 0;

foreach ($grouped as $reservation_uid => $group) {
    sort($group['dates']);
    $start_date = $group['dates'][0];
    $end_date = (new DateTime(end($group['dates'])))->modify('+1 day')->format('Y-m-d');

    $unit_id = $group['unit_id'];

    if (!isset($unit_colors[$unit_id])) {
        $unit_colors[$unit_id] = $colors[$color_index % count($colors)];
        $color_index++;
    }

    $events[] = [
        'id' => $group['reservation_id'] ?? uniqid(),
        'title' => $group['reservation_id'] ?? 'Dolu',
        'start' => $start_date,
        'end' => $end_date,
        'backgroundColor' => $unit_colors[$unit_id],
        'borderColor' => $unit_colors[$unit_id],
        'extendedProps' => [
            'unit_id' => $unit_id,
            'unit_name' => $group['unit_name'],
            'reservation_uid' => $reservation_uid
        ]
    ];
}

echo json_encode($events);
?>
