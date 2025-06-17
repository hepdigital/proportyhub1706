<?php

// 1. UYGULAMAYI BAŞLAT
// Bu dosya /admin/ klasöründe olduğu için, ana dizine çıkmak için '__DIR__ . /../' kullanır.
require_once __DIR__ . '/../includes/init.php';

// Burada gelecekte bir admin oturum kontrolü yapılabilir.
/*
$user = new User();
if (!$user->isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../owner/login.php'); // Admin değilse login'e at
    exit();
}
*/

// 2. SAYFA İŞLEMLERİNİ (POST/GET AKSİYONLARI) YÖNET
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

$property = new Property();
$unit = new Unit();

// GET ile gelen silme/aksiyon işlemlerini yönet
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($page === 'properties' && $action === 'delete' && $id) {
        $property->delete($id);
        redirect('index.php?page=properties&status=deleted');
    }
    if ($page === 'property-add' && $action === 'generate_ical_key' && $id) {
        $property->generateIcalKey($id);
        redirect('index.php?page=property-add&id=' . $id . '&status=ical_key_generated');
    }
    if ($page === 'property-add' && $action === 'delete_unit' && $id) {
        $unit_id_to_delete = $_GET['unit_id'] ?? null;
        if ($unit_id_to_delete) {
            $unit_data = $unit->getById($unit_id_to_delete);
            if ($unit_data && $unit_data['property_id'] == $id) {
                $unit->delete($unit_id_to_delete);
            }
        }
        redirect('index.php?page=property-add&id=' . $id . '&status=unit_deleted');
    }
}

// POST ile gelen form verilerini işle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($page === 'property-add') {
        if (isset($_POST['save_property'])) {
            $data = [
                'name' => $_POST['property_name'],
                'wp_site_url' => $_POST['wp_site_url'] ?? null,
                'sync_type' => $_POST['sync_type'],
                'api_key' => $_POST['api_key'] ?? null,
                'google_sheet_id' => $_POST['google_sheet_id'] ?? null,
                'api_key_generate' => isset($_POST['generate_new_key']) ? true : false
            ];
            
            $target_property_id = $id;
            if ($id) {
                $property->update($id, $data);
            } else {
                $new_property_id = $property->create($data);
                $target_property_id = $new_property_id;
            }

            $property_data_after_save = $property->getById($target_property_id);
            if ($property_data_after_save['sync_type'] === 'wordpress' && !empty($property_data_after_save['wp_site_url']) && !empty($property_data_after_save['api_key'])) {
                // ... (WordPress ünite çekme kodunuz) ...
            }
            redirect('index.php?page=property-add&id=' . $target_property_id . '&status=saved');

        } elseif (isset($_POST['add_unit_group']) && $id) {
            $type_name = trim($_POST['unit_type_name']);
            $quantity = (int)$_POST['unit_quantity'];
            $ical_url = isset($_POST['ical_url']) && !empty($_POST['ical_url']) ? trim($_POST['ical_url']) : null;

            if (!empty($type_name) && $quantity > 0) {
                $last_unit_number = $unit->getMaxUnitNumber($id);
                for ($i = 1; $i <= $quantity; $i++) {
                    $new_unit_number = $last_unit_number + $i;
                    $unit->create([
                        'property_id' => $id,
                        'name' => $type_name . ' - Ünite ' . $new_unit_number,
                        'unit_number' => $new_unit_number,
                        'ical_url' => $ical_url,
                        'wp_room_id' => null
                    ]);
                }
            }
            redirect('index.php?page=property-add&id=' . $id . '&status=units_added');
        }
    }
}

// ==================================================================
// 3. SAYFA GÖRÜNÜMÜNÜ OLUŞTURMA BÖLÜMÜ
// ==================================================================
include 'views/header.php';

switch ($page) {
    case 'properties':
        include 'views/properties.php';
        break;
    case 'property-add':
        include 'views/property-add.php';
        break;
    case 'calendar':
        include 'views/calendar.php';
        break;
    case 'dashboard':
    default:
        include 'views/dashboard.php';
        break;
}

include 'views/footer.php';