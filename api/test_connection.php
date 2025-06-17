<?php
// api/test_connection.php (GEÇİCİ DEBUG VERSİYONU 2)

$data = json_decode(file_get_contents('php://input'), true);
$apiKey = $data['api_key'] ?? null;

if (!$apiKey) {
    jsonResponse(['error' => 'API Anahtarı WordPress\'ten gelmedi.'], 400);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    if (!$db) {
        jsonResponse(['error' => 'DEBUG: Veritabanı bağlantısı kurulamadı! (getConnection() null döndürdü)'], 500);
        exit;
    }
} catch (Exception $e) {
    jsonResponse(['error' => 'DEBUG: Veritabanı bağlantısında EXCEPTION oluştu: ' . $e->getMessage()], 500);
    exit;
}

$property = new Property();
$propertyData = $property->getByApiKey($apiKey);

if ($propertyData && $propertyData['status'] == 1) {
    jsonResponse([
        'success' => true,
        'message' => 'Bağlantı başarılı!',
        'data' => [
            'property_id'   => (int)$propertyData['id'],
            'property_name' => $propertyData['name']
        ]
    ]);
} else {
    // Veritabanı sorgusundan sonuç dönmediğini varsayalım
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM properties");
    $stmt->execute();
    $total_properties = $stmt->fetchColumn();

    jsonResponse([
        'error' => 'DEBUG: API Anahtarı veritabanında bulunamadı veya tesis aktif değil. (DB Bağlantısı OK, Toplam Tesis: ' . $total_properties . ')'
    ], 401);
}