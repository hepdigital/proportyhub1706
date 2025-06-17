<?php
// api/update-reservation.php (NİHAİ VERSİYON)

try {
    // Adım 1: Gelen isteği al
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        jsonResponse(['error' => 'Hata: Geçersiz istek.'], 400);
        exit;
    }

    // Adım 2: Gerekli parametreleri doğrula
    if (!isset($data['api_key'], $data['hub_property_id'], $data['reservation_data'])) {
        jsonResponse(['error' => 'Hata: Gerekli parametreler eksik.'], 400);
        exit;
    }

    $apiKey = $data['api_key'];
    $hubPropertyId = (int)$data['hub_property_id'];
    $resData = $data['reservation_data'];

    // Adım 3: API Anahtarını ve Tesisi doğrula
    $property = new Property();
    $propertyData = $property->getById($hubPropertyId);

    if (!$propertyData || !hash_equals($propertyData['api_key'], trim($apiKey))) {
        jsonResponse(['error' => 'Hata: Geçersiz API Anahtarı veya Tesis ID.'], 401);
        exit;
    }

    // Adım 4: Eşleşen üniteyi Ara Yazılım veritabanında bul
    $unit = new Unit();
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id FROM units WHERE property_id = :property_id AND wp_room_id = :wp_room_id AND unit_number = :unit_number");
    $stmt->execute([
        ':property_id' => $hubPropertyId,
        ':wp_room_id'  => (int)$resData['wp_room_id'],
        ':unit_number' => (int)$resData['unit_number']
    ]);
    $hubUnit = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$hubUnit) {
        jsonResponse(['error' => 'Hata: Eşleşen ünite Ara Yazılımda bulunamadı.'], 404);
        exit;
    }

    // Adım 5: Ara Yazılım takvimini güncelle
    $hubUnitId = (int)$hubUnit['id'];
    $is_available = in_array($resData['status'], ['cancelled', 'refunded', 'failed']);

    $startDate = new DateTime($resData['start_date']);
    $endDate = new DateTime($resData['end_date']);
    $calendar = new Calendar();
    
    while ($startDate < $endDate) {
        $calendar->updateAvailability(
            $hubUnitId,
            $startDate->format('Y-m-d'),
            $is_available,
            $resData['uid'],
            'wordpress_push'
        );
        $startDate->modify('+1 day');
    }
    
    // =============================================================
    // ADIM 6: GOOGLE SHEET GÜNCELLEMESİNİ ANINDA TETİKLE
    // =============================================================
    // Ara Yazılım veritabanı güncellendiğine göre, şimdi Google Sheet'i de güncelleyelim.
    // $unit nesnesi zaten yukarıda oluşturulmuştu.
    $unit->pushAvailabilityToSheet($hubUnitId, $hubPropertyId);
    // =============================================================
    
    // Her şey başarılıysa, WordPress'e başarı mesajı gönder
    jsonResponse(['success' => true, 'message' => 'Availability updated in Hub and pushed to Google Sheet.']);

} catch (Exception $e) {
    // Beklenmedik bir PHP hatası olursa, bunu da WordPress'e bildir
    jsonResponse(['error' => 'Sunucu tarafında kritik bir hata oluştu: ' . $e->getMessage()], 500);
}