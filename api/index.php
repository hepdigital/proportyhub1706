<?php
require_once '../includes/init.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

$endpoint = $_GET['endpoint'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($endpoint) {
        case 'properties':
            require_once 'properties.php';
            break;
        case 'sync':
    require_once 'sync.php';
    break;
case 'test-connection':
    require_once 'test_connection.php';
    break;
case 'update-reservation':
    require_once 'update-reservation.php';
    break;
    case 'get-daily-prices':
    require_once 'owner/get-daily-prices.php';
    break;
case 'update-from-sheet': // YEN力 ENDPOINT
    require_once 'update-from-sheet.php';
    break;
            case 'get_districts': // YENİ EKLENEN BÖLÜM
            require_once 'get_districts.php';
            break;
default:
            jsonResponse(['error' => 'Endpoint not found'], 404);
    }
} catch (Exception $e) {
    jsonResponse(['error' => $e->getMessage()], 500);
}