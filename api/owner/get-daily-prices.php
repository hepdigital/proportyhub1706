<?php
// api/owner/get-daily-prices.php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/init.php';

// Güvenlik ve oturum kontrolü
$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'tesis_sahibi') {
    http_response_code(403); exit(json_encode(['error' => 'Yetkisiz erişim.']));
}

$unit_type_id = $_GET['unit_type_id'] ?? null;
$start = $_GET['start'] ?? null;
$end = $_GET['end'] ?? null;

if (!$unit_type_id || !$start || !$end) {
    http_response_code(400); exit(json_encode(['error' => 'Eksik parametreler.']));
}

try {
    $pricing = new Pricing();
    $daily_prices = $pricing->calculateDailyPrices($unit_type_id, $start, $end);
    
    $events = [];
    foreach ($daily_prices as $date => $price) {
        $events[] = [
            'title' => number_format($price, 0) . ' ₺',
            'start' => $date,
            'allDay' => true,
            'backgroundColor' => '#16a34a', // Fiyat rengi
            'borderColor' => '#16a34a'
        ];
    }
    
    echo json_encode($events);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Hesaplama sırasında hata oluştu: ' . $e->getMessage()]);
}