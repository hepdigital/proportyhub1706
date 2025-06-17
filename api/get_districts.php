<?php
// api/get_districts.php

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/init.php';

// Güvenlik ve oturum kontrolü (isteğe bağlı ama önerilir)
$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'tesis_sahibi') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Yetkisiz erişim.']);
    exit();
}

$province_id = $_GET['province_id'] ?? null;

if (!$province_id || !is_numeric($province_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Geçersiz İl ID.']);
    exit();
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT name FROM ilceler WHERE province_id = :province_id ORDER BY name ASC");
    $stmt->execute([':province_id' => $province_id]);
    // Sadece ilçe isimlerini içeren bir dizi döndürmek yeterli
    $districts = $stmt->fetchAll(PDO::FETCH_COLUMN); 
    
    echo json_encode(['success' => true, 'districts' => $districts]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("get_districts API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Sunucu hatası oluştu.']);
}