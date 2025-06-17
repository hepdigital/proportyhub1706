<?php
$property = new Property();
$unit = new Unit();

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            // Tek tesis detayı
            $property_data = $property->getById($_GET['id']);
            if ($property_data) {
                $property_data['units'] = $unit->getByProperty($_GET['id']);
                jsonResponse($property_data);
            } else {
                jsonResponse(['error' => 'Property not found'], 404);
            }
        } else {
            // Tüm tesisler
            $properties = $property->getAll();
            foreach ($properties as &$prop) {
                $prop['unit_count'] = count($unit->getByProperty($prop['id']));
            }
            jsonResponse($properties);
        }
        break;
        
    case 'POST':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $property->create($data);
        jsonResponse(['id' => $id, 'message' => 'Property created']);
        break;
        
    case 'DELETE':
        if (isset($_GET['id'])) {
            $property->delete($_GET['id']);
            jsonResponse(['message' => 'Property deleted']);
        }
        break;
}