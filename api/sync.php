<?php
$sync = new Sync();

switch ($method) {
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['unit_id'])) {
            // Tek unit senkronize et
            $result = $sync->syncUnit($data['unit_id']);
            jsonResponse(['success' => $result]);
        } elseif (isset($data['property_id'])) {
            // Tüm property'yi senkronize et
            $results = $sync->syncProperty($data['property_id']);
            jsonResponse(['results' => $results]);
        } else {
            jsonResponse(['error' => 'unit_id or property_id required'], 400);
        }
        break;
        
    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}