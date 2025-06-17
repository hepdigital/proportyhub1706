<?php
require_once __DIR__ . '/../includes/init.php'; // Ana init dosyanızın yolu

header('Content-Type: application/json');

// Basit bir güvenlik token'ı (isteğe bağlı ama önerilir)
// Bu token'ı Google Apps Script'ten gelen istekte de göndermelisiniz.
define('GOOGLE_APPS_SCRIPT_TOKEN', 'COK_GIZLI_BIR_TOKEN_DEGERI');

$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestMethod !== 'POST') {
    jsonResponse(['error' => 'Invalid request method.'], 405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Token kontrolü
/*
if (!isset($input['token']) || $input['token'] !== GOOGLE_APPS_SCRIPT_TOKEN) {
    jsonResponse(['error' => 'Unauthorized.'], 401);
    exit;
}
*/

if (!isset($input['unitId'], $input['date'], $input['isAvailable'])) {
    jsonResponse(['error' => 'Missing required parameters (unitId, date, isAvailable).'], 400);
    exit;
}

$unitId = filter_var($input['unitId'], FILTER_VALIDATE_INT);
$date = $input['date']; // Y-m-d formatında gelmeli, validasyon eklenebilir
$isAvailable = filter_var($input['isAvailable'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
$reservationId = isset($input['reservationId']) ? trim($input['reservationId']) : null;

if ($unitId === false || $isAvailable === null) {
    jsonResponse(['error' => 'Invalid data types for unitId or isAvailable.'], 400);
    exit;
}

// Tarih formatını doğrula (basit kontrol)
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) {
    jsonResponse(['error' => 'Invalid date format. Use YYYY-MM-DD.'], 400);
    exit;
}

try {
    $calendar = new Calendar();
    $result = $calendar->updateAvailability(
        $unitId,
        $date,
        $isAvailable,
        $reservationId,
        'google_sheets' // Sync source olarak belirt
    );

    if ($result) {
        // Veritabanı güncellendi. Şimdi bu değişikliğin diğer yerlere yansıması gerekebilir.
        // Örneğin, eğer bu köprü kendi iCal feed'lerini üretiyorsa, o feed'ler güncellenmeli.
        // Veya bu değişiklik tekrar Google Sheet'e "teyit" olarak gönderilmemeli (sonsuz döngüden kaçın).

        // Başka bir ünite için iCal senkronizasyonunu tetiklememek için dikkatli olun.
        // Sadece loglama veya bildirim yapılabilir.
        $syncLogger = new Sync(); // Log metodu için
        $unitModel = new Unit();
        $unitData = $unitModel->getById($unitId);
        $propertyId = $unitData['property_id'] ?? null;

        $syncLogger->log(
            $propertyId,
            $unitId,
            'google_sheets_webhook',
            'success',
            "DB updated from Google Sheets: Date $date, Available: " . ($isAvailable ? 'Yes' : 'No')
        );

        jsonResponse(['success' => true, 'message' => 'Availability updated in database.']);
    } else {
        jsonResponse(['error' => 'Failed to update database.'], 500);
    }

} catch (Exception $e) {
    error_log("Error in google_sheets_webhook.php: " . $e->getMessage());
    jsonResponse(['error' => 'An internal error occurred: ' . $e->getMessage()], 500);
}