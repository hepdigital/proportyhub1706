<?php
require_once __DIR__ . '/../../includes/init.php';
header('Content-Type: application/json');

// Güvenlik: Sadece giriş yapmış tesis sahipleri erişebilir.
$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'tesis_sahibi') {
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit();
}
$owner_id = $_SESSION['user_id'];
$notification_id = $_GET['id'] ?? null;

if (!$notification_id) {
    echo json_encode(['success' => false, 'error' => 'Bildirim ID eksik.']);
    exit();
}

$notification_class = new Notification();
$details = $notification_class->getReservationDetailsForNotification($notification_id, $owner_id);

if ($details) {
    // Verileri frontend için formatlayalım
    $details['start_date_formatted'] = date('d F Y', strtotime($details['start_date']));
    $details['end_date_formatted'] = date('d F Y', strtotime($details['end_date']));
    $details['total_price_formatted'] = number_format($details['total_price'], 2, ',', '.');
    $details['commission_amount_formatted'] = number_format($details['commission_amount'], 2, ',', '.');
    $details['net_income_formatted'] = number_format($details['net_income'], 2, ',', '.');
    echo json_encode(['success' => true, 'data' => $details]);
} else {
    echo json_encode(['success' => false, 'error' => 'Detaylar bulunamadı veya bu bildirimi görme yetkiniz yok.']);
}
