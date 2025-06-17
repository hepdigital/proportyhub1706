<?php
// owner/index.php (NİHAİ VERSİYON)

require_once __DIR__ . '/../includes/init.php';

$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'tesis_sahibi') {
    header('Location: login.php');
    exit();
}

$owner_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'dashboard';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $property_class = new Property();
    
    // Güvenlik kontrolü
    $property_id_from_post = $_POST['property_id'] ?? null;
    $is_owner = false;
    if ($property_id_from_post) {
        $prop_data = $property_class->getById($property_id_from_post);
        if ($prop_data && $prop_data['owner_id'] == $owner_id) {
            $is_owner = true;
        }
    }

    switch ($action) {
        case 'save_property':
            $property_id = $_POST['property_id'] ?? null;
            
            $data = [
                'owner_id' => $owner_id,
                'name' => $_POST['property_name'],
                'commission_rate' => $_POST['commission_rate'],
                'address_province' => $_POST['address_province_name'], // İl adı
                'address_district' => $_POST['address_district'],     // İlçe adı
                'address_full' => $_POST['address_full'],
                'contact_person_name' => $_POST['contact_person_name'],
                'contact_phone' => $_POST['contact_phone'],
                'reservation_type' => $_POST['reservation_type'],
                'pets_allowed' => isset($_POST['pets_allowed']) ? 1 : 0,
                'paid_child_age' => $_POST['paid_child_age'],
                'property_types' => $_POST['property_types'] ?? [],
                'amenities' => $_POST['amenities'] ?? [],
                'payment_options' => $_POST['payment_options'] ?? []
            ];

            if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/uploads/covers/';
                if (!is_dir($upload_dir)) { mkdir($upload_dir, 0775, true); }
                $file_extension = pathinfo($_FILES['cover_photo']['name'], PATHINFO_EXTENSION);
                $unique_filename = bin2hex(random_bytes(16)) . '.' . $file_extension;
                $upload_file = $upload_dir . $unique_filename;
                if (move_uploaded_file($_FILES['cover_photo']['tmp_name'], $upload_file)) {
                    $data['cover_photo_path'] = '/owner/uploads/covers/' . $unique_filename;
                }
            } else if ($property_id) {
                $existing_data = $property_class->getById($property_id);
                $data['cover_photo_path'] = $existing_data['cover_photo_path'];
            }

            if ($property_id) {
                 if($is_owner) { $property_class->update($property_id, $data); }
            } else {
                 $property_id = $property_class->create($data);
            }
            redirect("index.php?page=property-edit&id={$property_id}&status=saved");
            break;

        case 'delete_property':
            if ($property_id_from_post && $is_owner) {
                $property_class->delete($property_id_from_post);
                redirect('index.php?page=properties&status=deleted');
            } else {
                redirect('index.php?page=properties&status=auth_error');
            }
            break;
        
            case 'update_unit_details':
                $unit_class = new Unit();
        $unit_id = $_POST['unit_id'] ?? null;
        if ($unit_id && $is_owner) { // is_owner kontrolü, ünitenin bu kullanıcıya ait olduğunu doğrular
            $unit_data = [
                'base_price' => $_POST['base_price'],
                'capacity' => $_POST['capacity'],
                'price_per_extra_person' => $_POST['price_per_extra_person']
            ];
            $unit_class = new Unit();
            $unit_class->updateDetails($unit_id, $unit_data);
            redirect("index.php?page=property-edit&id={$property_id_from_post}&status=unit_updated");
        } else {
            redirect("index.php?page=property-edit&id={$property_id_from_post}&status=auth_error");
        }
        break;
        
    case 'add_units':
        // HATA ÇÖZÜMÜ: $unit_class burada yeniden oluşturuluyor.
        $unit_class = new Unit(); 
        
        if ($property_id_from_post && $is_owner) {
            $type_name = trim($_POST['unit_type_name']);
            $quantity = (int)$_POST['unit_quantity'];

            if (!empty($type_name) && $quantity > 0) {
                $last_unit_number = $unit_class->getMaxUnitNumber($property_id_from_post);
                for ($i = 1; $i <= $quantity; $i++) {
                    $new_unit_number = $last_unit_number + $i;
                    $unit_class->create([
                        'property_id' => $property_id_from_post,
                        'name' => $type_name . ' - Ünite ' . $new_unit_number,
                        'unit_number' => $new_unit_number
                    ]);
                }
            }
            redirect("index.php?page=property-edit&id={$property_id_from_post}&status=units_added");
        } else {
            redirect("index.php?page=property-edit&id={$property_id_from_post}&status=auth_error");
        }
        break;
            
        case 'delete_unit':
            $unit_class = new Unit();
            $unit_id_to_delete = $_POST['unit_id'] ?? null;
            // DÜZELTME: Yönlendirme için property_id'yi POST'tan almak daha güvenli olabilir.
            $property_id_redirect = $_POST['property_id'] ?? ($_GET['id'] ?? null);
            
            $unit_data = $unit_class->getById($unit_id_to_delete);
            if ($unit_data) {
                $unit_prop_data = $property_class->getById($unit_data['property_id']);
                if($unit_prop_data && $unit_prop_data['owner_id'] == $owner_id){
                     $unit_class->delete($unit_id_to_delete);
                     redirect("index.php?page=property-edit&id={$property_id_redirect}&status=unit_deleted");
                }
            }
            redirect("index.php?page=property-edit&id={$property_id_redirect}&status=error");
            break;
            
        case 'create_unit_type':
    // Yetki kontrolü
    if (isset($_POST['property_id'])) {
        $property_class_check = new Property();
        $prop_data_check = $property_class_check->getById($_POST['property_id']);
        if ($prop_data_check && $prop_data_check['owner_id'] == $owner_id) {
            
            $unit_class = new Unit();
            $data = [
                'property_id' => $_POST['property_id'],
                'name' => $_POST['name'],
                'capacity' => $_POST['capacity'],
                'bedroom_count' => $_POST['bedroom_count'],
                'bathroom_count' => $_POST['bathroom_count'],
                'size_sqm' => $_POST['size_sqm'],
                'has_kitchen' => isset($_POST['has_kitchen']) ? 1 : 0,
                'features' => $_POST['features'] ?? []
            ];
            
            // 1. Ana Oda Tipi oluşturulur.
            $new_unit_type_id = $unit_class->createUnitType($data);
            
            if ($new_unit_type_id) {
                // 2. Belirtilen adette fiziksel birim (unit) oluşturulur.
                $quantity = (int)($_POST['quantity'] ?? 1);
                $last_unit_number = $unit_class->getMaxUnitNumber($_POST['property_id']);

                for ($i = 1; $i <= $quantity; $i++) {
                    $new_unit_number = $last_unit_number + $i;
                    $unit_class->create([
                        'property_id' => $_POST['property_id'],
                        'unit_type_id' => $new_unit_type_id, // Yeni oluşturulan tipin ID'si
                        'name' => $_POST['name'] . ' - Birim ' . $new_unit_number,
                        'unit_number' => $new_unit_number
                    ]);
                }
                
                // 3. Kullanıcı, detayları ekleyebileceği düzenleme sayfasına yönlendirilir.
                redirect("index.php?page=unit-type-edit&id={$new_unit_type_id}&status=type_created");
            }
        }
    }
    // Hata durumunda geri yönlendir...
    redirect("index.php?page=unit-types&property_id={$_POST['property_id']}&status=error");
    break;
    
    case 'create_pricing_rule':
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    $property_id_for_rule = $_POST['property_id'] ?? null;
    // Yetki kontrolü yapılmalı...
    $pricing_class = new Pricing();
    $pricing_class->createRule($_POST);
    redirect("index.php?page=pricing-rules&unit_type_id={$unit_type_id}&status=rule_created");
    break;

case 'delete_pricing_rule':
    $rule_id = $_POST['rule_id'] ?? null;
    $unit_type_id_redirect = $_GET['unit_type_id'] ?? null; // Yönlendirme için
    $pricing_class = new Pricing();
    $pricing_class->deleteRule($rule_id, $owner_id);
    redirect("index.php?page=pricing-rules&unit_type_id={$unit_type_id_redirect}&status=rule_deleted");
    break;
    
        case 'update_unit_type':
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    $property_id_redirect = $_POST['property_id'] ?? null;
    
    if ($unit_type_id && $property_id_redirect) {
        $unit_class = new Unit();
        $basic_data = [
            'name' => $_POST['name'],
            'capacity' => $_POST['capacity'], // EKLENDİ
            'bedroom_count' => $_POST['bedroom_count'],
            'bathroom_count' => $_POST['bathroom_count'],
            'size_sqm' => $_POST['size_sqm'],
            'has_kitchen' => isset($_POST['has_kitchen']) ? 1 : 0,
        ];
        $unit_class->updateUnitType($unit_type_id, $basic_data);

        $property_class = new Property();
        $features_data = $_POST['features'] ?? [];
        $property_class->updateFeaturesForUnitType($unit_type_id, $features_data);
    }
    redirect("index.php?page=unit-type-edit&id={$unit_type_id}&status=type_updated");
    break;
    
    case 'update_base_prices':
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    $property_id_redirect = $_POST['property_id'] ?? null;

    if ($unit_type_id && $property_id_redirect) {
        // Yetki kontrolü yapılabilir
        $unit_class = new Unit();
        $data = [
            'base_price' => $_POST['base_price'],
            'price_per_extra_person' => $_POST['price_per_extra_person']
        ];
        $unit_class->updateUnitType($unit_type_id, $data);
        redirect("index.php?page=pricing-rules&unit_type_id={$unit_type_id}&status=defaults_updated");
    }
    break;
    
    case 'upload_gallery_photos':
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    if ($unit_type_id && isset($_FILES['gallery_photos'])) {
        // Yetki kontrolü yapılmalı...
        $unit_class = new Unit();
        $unit_class->handlePhotoUploads($_FILES['gallery_photos'], $unit_type_id);
    }
    redirect("index.php?page=unit-type-edit&id={$unit_type_id}&tab=media");
    break;

case 'delete_photo':
    $photo_id = $_POST['photo_id'] ?? null;
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    if ($photo_id && $unit_type_id) {
        // Yetki kontrolü yapılmalı...
        $unit_class = new Unit();
        $unit_class->deletePhoto($photo_id);
    }
    redirect("index.php?page=unit-type-edit&id={$unit_type_id}&tab=media");
    break;

case 'set_cover_photo':
    $photo_id = $_POST['photo_id'] ?? null;
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    if ($photo_id && $unit_type_id) {
        // Yetki kontrolü...
        $unit_class = new Unit();
        $unit_class->setCoverPhoto($unit_type_id, $photo_id);
    }
    redirect("index.php?page=unit-type-edit&id={$unit_type_id}&tab=media");
    break;
    
    case 'delete_unit_type':
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    if ($unit_type_id && $is_owner) { // is_owner, bu tesisin size ait olduğunu doğrular
        $unit_class = new Unit();
        $unit_class->deleteUnitType($unit_type_id);
    }
    redirect("index.php?page=unit-types&property_id={$property_id_from_post}&status=type_deleted");
    break;
    case 'add_single_unit':
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    if ($unit_type_id) {
        // Yetki kontrolü yapılmalı...
        $unit_class = new Unit();
        $unit_type_data = $unit_class->getUnitTypeById($unit_type_id);
        if ($unit_type_data) {
            $last_unit_number = $unit_class->getMaxUnitNumber($unit_type_data['property_id']);
            $unit_class->create([
                'property_id' => $unit_type_data['property_id'],
                'unit_type_id' => $unit_type_id,
                'name' => $_POST['new_unit_name'],
                'unit_number' => $last_unit_number + 1
            ]);
        }
    }
    redirect("index.php?page=unit-type-edit&id={$unit_type_id}&tab=units");
    break;

case 'delete_single_unit':
    $unit_id = $_POST['unit_id'] ?? null;
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    if ($unit_id) {
        // Yetki kontrolü yapılmalı...
        $unit_class = new Unit();
        $unit_class->delete($unit_id);
    }
    redirect("index.php?page=unit-type-edit&id={$unit_type_id}&tab=units");
    break;
    
    case 'update_single_day_price':
    // Bu aksiyon AJAX ile çağrılacağı için JSON yanıtı döndürmeli
    header('Content-Type: application/json');
    
    $unit_type_id = $_POST['unit_type_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $price = $_POST['price'] ?? null;
    
    // Yetki kontrolü yapılmalı...
    if (!$unit_type_id || !$date || !isset($price)) {
        echo json_encode(['success' => false, 'error' => 'Eksik parametreler.']);
        exit();
    }

    // Bu tarihe özel, yüksek öncelikli yeni bir kural oluşturuyoruz.
    $pricing_class = new Pricing();
    $data = [
        'property_id' => $_POST['property_id'],
        'unit_type_id' => $unit_type_id,
        'name' => 'Özel Gün Fiyatı - ' . $date,
        'type' => 'date_range',
        'start_date' => $date,
        'end_date' => $date,
        'price' => $price,
        'min_stay' => 1,
        'priority' => 99 // Tek günlük özel fiyatların her zaman en öncelikli olmasını sağlar
    ];
    $success = $pricing_class->createOrUpdatePriceForDate($unit_type_id, $date, $price, $_POST['property_id']);

    echo json_encode(['success' => $success]);
    exit(); // AJAX isteğini sonlandır
    break;
    }
    
    
}


// ---- GÖRÜNÜM (VIEW) YÜKLEME ----
if(isset($_GET['status']) && $_GET['status'] == 'defaults_updated'){
}
if ($page === 'pricing-rules' && isset($_GET['unit_type_id'])) {
    $unit_class_for_view = new Unit(); // UnitType bilgilerini çekmek için geçici olarak bunu kullanabiliriz
    $pricing_class_for_view = new Pricing();
    
    $unit_type_id = $_GET['unit_type_id'];
    $unit_type_data = $unit_class_for_view->getUnitTypeById($unit_type_id); // Bu metodu Unit.php'ye eklememiz gerekecek
    
    if (!$unit_type_data) {
        $page = 'error';
    } else {
        $property_id = $unit_type_data['property_id'];
        // Yetki kontrolü
        $property_class = new Property();
        $property_data_check = $property_class->getById($property_id);
        if (!$property_data_check || $property_data_check['owner_id'] != $owner_id) {
            $page = 'error';
        } else {
            $pricing_rules = $pricing_class_for_view->getRulesForUnitType($unit_type_id);
        }
    }
}

if ($page === 'unit-type-edit' && isset($_GET['id'])) {
    $property_class_for_view = new Property();
    $unit_class_for_view = new Unit();
    $feature_class_for_view = new Feature();
    
    $unit_type_id = $_GET['id'] ?? null;
    // Yeni ekleme modunda property_id'yi URL'den al, düzenlemede ise veritabanından gelenle doğrula
    $property_id = $_GET['property_id'] ?? ($unit_type_data['property_id'] ?? null);
    $edit_mode = !is_null($unit_type_id);

    // DÜZELTME: Tüm değişkenleri boş diziler olarak başlatıyoruz.
    $unit_type_data = [];
    $photos = [];
    $selected_features = [];
    
    // Bu özellik listesi her iki modda da gereklidir.
    $categorized_features = $feature_class_for_view->getAllCategoriesWithFeatures();

    if ($edit_mode) {
        // --- DÜZENLEME MODU ---
        $unit_type_data = $unit_class_for_view->getUnitTypeById($unit_type_id);
        
        if (!$unit_type_data) {
            $page = 'error';
        } else {
            $property_id = $unit_type_data['property_id'];
            $property_data_check = $property_class_for_view->getById($property_id);
            if (!$property_data_check || $property_data_check['owner_id'] != $owner_id) {
                $page = 'error';
            } else {
                $photos = $unit_class_for_view->getPhotosForUnitType($unit_type_id);
                $selected_features = $property_class_for_view->getFeaturesForUnitType($unit_type_id);
                $units = $unit_class_for_view->getByUnitType($unit_type_id); // YENİ EKLENEN SATIR
            }
        }
    } else {
        // --- YENİ EKLEME MODU ---
        if (!$property_id) {
            $page = 'error'; // Hangi tesise ekleneceği bilinmiyorsa hata ver.
        }
    }
    
    // Sayfa başlığında vs. kullanmak için ana tesis bilgisini alalım.
    if ($property_id) {
        $property_data = $property_class_for_view->getById($property_id);
    }
}

if ($page === 'unit-types' && isset($_GET['property_id'])) {
    $property_class_for_view = new Property();
    $property_id = $_GET['property_id'];
    $property_data = $property_class_for_view->getById($property_id);

    // Yetki kontrolü
    if (!$property_data || $property_data['owner_id'] != $owner_id) {
        $page = 'error';
    } else {
        $unit_types = $property_class_for_view->getUnitTypes($property_id);
    }
}

if ($page === 'property-edit') {
    $property_class_for_view = new Property();
    $unit_class_for_view = new Unit(); // Unit sınıfını burada oluşturuyoruz
    
    $property_id_for_view = $_GET['id'] ?? null;
    $edit_mode = !is_null($property_id_for_view);
    
    // Değişkenleri varsayılan olarak boş dizilerle tanımlayalım
    $property_data = null;
    $units = [];
    $selected_types = [];
    $selected_amenities = [];
    $selected_payment_options = [];

    if ($edit_mode) {
        $property_data = $property_class_for_view->getById($property_id_for_view);
        
        if (!$property_data || $property_data['owner_id'] != $owner_id) {
             $page = 'error'; 
        } else {
            // DÜZELTME: Üniteleri burada, kontrolcüde çekiyoruz
            $units = $unit_class_for_view->getByProperty($property_id_for_view);
            
            $selected_types = $property_class_for_view->getRelationsForProperty($property_id_for_view, 'property_to_type_map');
            $selected_amenities = $property_class_for_view->getRelationsForProperty($property_id_for_view, 'property_to_amenity_map');
            $selected_payment_options = $property_class_for_view->getRelationsForProperty($property_id_for_view, 'property_to_payment_option_map');
        }
    }

    // Form için gerekli tüm ana listeleri veritabanından çek
    $all_property_types = $property_class_for_view->getAllMasterData('property_types');
    $all_amenities = $property_class_for_view->getAllMasterData('amenities');
    $all_payment_options = $property_class_for_view->getAllMasterData('payment_options');

    // İl listesini çek
    $db_for_provinces = Database::getInstance()->getConnection();
    $provinces_stmt = $db_for_provinces->query("SELECT id, name FROM iller ORDER BY name ASC");
    $all_provinces = $provinces_stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($page === 'unit-type-add' && isset($_GET['property_id'])) {
    $property_class_for_view = new Property();
    $feature_class_for_view = new Feature();

    $property_id = $_GET['property_id'];
    $property_data = $property_class_for_view->getById($property_id);

    if (!$property_data || $property_data['owner_id'] != $owner_id) {
        $page = 'error';
    } else {
        // Formda gösterilecek tüm özellikleri ve kategorileri çek
        $categorized_features = $feature_class_for_view->getAllCategoriesWithFeatures();
    }
}

include __DIR__ . '/views/partials/header.php';

$allowed_pages = ['dashboard', 'calendar', 'properties', 'property-edit', 'hizmetler', 'error', 'unit-types', 'pricing-rules', 'unit-type-edit', 'unit-type-add'];
if (in_array($page, $allowed_pages)) {
    $page_path = __DIR__ . "/views/{$page}.php";
    if (file_exists($page_path)) {
        include $page_path;
    } else {
        echo "<div class='p-8'><div class='alert alert-error'><strong>Hata:</strong> Sayfa dosyası bulunamadı.</div></div>";
    }
} else {
    include __DIR__ . '/views/dashboard.php';
}

include __DIR__ . '/views/partials/footer.php';