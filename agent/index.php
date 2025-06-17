<?php
// agent/index.php

// 1. UYGULAMAYI BAŞLAT
require_once __DIR__ . '/../includes/init.php';

// 2. OTURUM VE YETKİ KONTROLÜ
$user = new User();
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'acente') {
    header('Location: login.php');
    exit();
}
$agent_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'dashboard';


// --- POST İŞLEMLERİNİ YÖNETME ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Ayarlar sayfasındaki profil güncelleme işlemi
    if ($page === 'settings' && $action === 'update_profile') {
        // HATA DÜZELTMESİ: Formdan gelmeyebilecek alanlar için null coalescing operatörü (??) kullanıldı.
        // Bu, "Undefined array key" uyarısını engeller.
        $data = [
            'agency_name' => $_POST['agency_name'] ?? null,
            'agency_province_id' => $_POST['agency_province_id'] ?? null,
            'agency_district_id' => $_POST['agency_district_id'] ?? null
        ];
        $user->updateAgentProfile($agent_id, $data);
        redirect('index.php?page=settings&status=success');
        exit(); // Yönlendirme sonrası kodun çalışmasını durdurmak önemlidir.
    }
    
    // Rezervasyon oluşturma işlemi
    if ($action === 'create_reservation') {
        $reservation_class = new Reservation();
        $data = [
            'agent_id' => $agent_id,
            'property_id' => $_POST['property_id'] ?? null,
            'unit_type_id' => $_POST['unit_type_id'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'guest_name' => $_POST['guest_name'] ?? null,
            'guest_phone' => $_POST['guest_phone'] ?? null,
            'guest_email' => $_POST['guest_email'] ?? null
        ];
        $result = $reservation_class->createAgentReservation($data);
        
        $_SESSION['flash_message'] = $result;
        redirect('index.php?page=properties');
        exit(); // Yönlendirme sonrası kodun çalışmasını durdurmak önemlidir.
    }
}


// --- SAYFAYA ÖZEL VERİLERİ ÇEK (GET REQUESTS) ---
if ($page === 'properties') {
    $property_class_for_view = new Property();
    $db_instance = Database::getInstance()->getConnection();
    
    $provinces_stmt = $db_instance->query("SELECT DISTINCT address_province FROM properties WHERE address_province IS NOT NULL AND address_province != '' ORDER BY address_province ASC");
    $filter_provinces = $provinces_stmt->fetchAll(PDO::FETCH_COLUMN);
    $feature_class = new Feature();
    $categorized_features = $feature_class->getAllCategoriesWithFeatures();
    
    $filters = [];
    if (!empty($_GET['province'])) $filters['province'] = $_GET['province'];
    if (!empty($_GET['start_date'])) $filters['start_date'] = $_GET['start_date'];
    if (!empty($_GET['end_date'])) $filters['end_date'] = $_GET['end_date'];
    if (!empty($_GET['guest_count'])) $filters['guest_count'] = $_GET['guest_count'];
    if (!empty($_GET['features']) && is_array($_GET['features'])) $filters['features'] = $_GET['features'];

    $properties = $property_class_for_view->findAvailableProperties($filters);
    
    $pricing_class = new Pricing();
    foreach ($properties as &$prop) {
        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $price_info = $pricing_class->calculateDailyPrices($prop['unit_type_id'], $filters['start_date'], $filters['end_date']);
            $total_price = 0;
            $current_date = new DateTime($filters['start_date']);
            $end_date_obj = new DateTime($filters['end_date']);
            while($current_date < $end_date_obj) {
                $date_str = $current_date->format('Y-m-d');
                $total_price += $price_info[$date_str] ?? $prop['base_price'];
                $current_date->modify('+1 day');
            }
            $prop['total_price'] = $total_price;
            $prop['commission_amount'] = ($total_price * $prop['commission_rate']) / 100;
        }
    }
    unset($prop);
}

if ($page === 'settings') {
    $agent_data = $user->findById($agent_id);
    $db_instance = Database::getInstance()->getConnection();
    $provinces_stmt = $db_instance->query("SELECT id, name FROM iller ORDER BY name ASC");
    $all_provinces = $provinces_stmt->fetchAll(PDO::FETCH_ASSOC);
}


// --- GÖRÜNÜM (VIEW) DOSYALARINI YÜKLE ---
include __DIR__ . '/views/partials/header.php';
$allowed_pages = ['dashboard', 'properties', 'property-detail', 'calendar', 'error', 'settings']; 
if (in_array($page, $allowed_pages)) {
    $page_path = __DIR__ . "/views/{$page}.php";
    if (file_exists($page_path)) {
        include $page_path;
    } else {
        echo "<div class='p-8'><div class='alert alert-error'><strong>Hata:</strong> Sayfa dosyası bulunamadı: {$page}.php</div></div>";
    }
} else {
    include __DIR__ . '/views/dashboard.php';
}
include __DIR__ . '/views/partials/footer.php';

