<?php
// api/update-from-sheet.php

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!defined('GOOGLE_APPS_SCRIPT_SECRET') || !isset($data['secret']) || !hash_equals(GOOGLE_APPS_SCRIPT_SECRET, $data['secret'])) {
        jsonResponse(['error' => 'Yetkisiz erişim.'], 403);
        exit;
    }
    
    if (!isset($data['unit_id'], $data['date'], $data['status'])) {
        jsonResponse(['error' => 'Eksik parametreler.'], 400);
        exit;
    }

    $hubUnitId = (int)$data['unit_id'];
    $date = $data['date'];
    $status = strtolower(trim($data['status']));

    $unit = new Unit();
    $unitData = $unit->getById($hubUnitId);
    if (!$unitData) {
        jsonResponse(['error' => 'Böyle bir ünite bulunamadı: ' . $hubUnitId], 404);
        exit;
    }
    $hubPropertyId = $unitData['property_id'];
    
    $property = new Property();
    $propertyData = $property->getById($hubPropertyId);
    if (!$propertyData) {
        jsonResponse(['error' => 'Üniteye bağlı tesis bulunamadı.'], 404);
        exit;
    }

    $is_available = in_array($status, ['müsait', 'available', '']);

    $calendar = new Calendar();
    $result = $calendar->updateAvailability(
        $hubUnitId,
        $date,
        $is_available,
        $is_available ? null : 'g-sheet-' . date('Ymd'),
        'google_sheet_push'
    );

    if ($result) {
        // Kendi veritabanımız güncellendi, şimdi bu değişikliği WordPress'e PUSH edelim.
        if ($propertyData['sync_type'] === 'wordpress' && !empty($unitData['wp_room_id'])) {
            $update_payload = [[
                'room_id'     => (int)$unitData['wp_room_id'],
                'unit_number' => (int)$unitData['unit_number'],
                'date'        => $date,
                'is_available' => $is_available
            ]];
            // Property nesnesini kullanarak PUSH işlemini yap
            $property->pushUpdateToWordPress($propertyData, $update_payload);
        }
        
        jsonResponse(['success' => true, 'message' => "Unit $hubUnitId, date $date updated."]);
    } else {
        jsonResponse(['error' => 'Veritabanı güncellenirken bir hata oluştu.'], 500);
    }

} catch (Exception $e) {
    jsonResponse(['error' => 'Sunucu tarafında bir hata oluştu: ' . $e->getMessage()], 500);
}