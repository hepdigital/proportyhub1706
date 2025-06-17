<?php
// agent/index.php

// 1. UYGULAMAYI BAŞLAT
require_once __DIR__ . '/../includes/init.php';

// 2. OTURUM VE YETKİ KONTROLÜ
$user = new User();

// Eğer kullanıcı giriş yapmamışsa VEYA rolü 'acente' değilse, login sayfasına yönlendir.
if (!$user->isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'acente') {
    header('Location: login.php');
    exit();
}

// 3. GEREKLİ DEĞİŞKENLERİ HAZIRLA
$agent_id = $_SESSION['user_id'];
$page = $_GET['page'] ?? 'dashboard'; // Varsayılan sayfa dashboard olsun.


// 4. SAYFAYA ÖZEL VERİLERİ ÇEK (SADECE GÖRÜNÜM İÇİN)
// Acente panelinde POST işlemleri olmadığı için o bölüm tamamen kaldırıldı.

// Tesisler sayfası istenirse, veritabanından tüm aktif tesisleri çek.
if ($page === 'properties') {
    $property_class_for_view = new Property();
    $db_instance = Database::getInstance()->getConnection();

    // Filtreleme formu için seçenekleri hazırla
    $provinces_stmt = $db_instance->query("SELECT DISTINCT address_province FROM properties WHERE address_province IS NOT NULL AND address_province != '' ORDER BY address_province ASC");
    $filter_provinces = $provinces_stmt->fetchAll(PDO::FETCH_COLUMN);
    $filter_amenities = $property_class_for_view->getAllMasterData('amenities');

    // Kullanıcıdan gelen filtreleri al
    $filters = [];
    if (!empty($_GET['province'])) {
        $filters['province'] = $_GET['province'];
    }
    if (!empty($_GET['amenities']) && is_array($_GET['amenities'])) {
        $filters['amenities'] = $_GET['amenities'];
    }
    
    // Tesisleri filtreleyerek getir
    $properties = $property_class_for_view->getAllForAgents($filters); 
}

// Tesis detay sayfası istenirse, o tesise ait detayları çek.
if ($page === 'property-detail' && isset($_GET['id'])) {
    $property_class_for_view = new Property();
    $property_id_for_view = $_GET['id'];
    $property_data = $property_class_for_view->getById($property_id_for_view);
    
    if(!$property_data) {
        $page = 'error';
    } else {
        // Gerekli tüm verileri hazırlıyoruz
        $unit_class_for_view = new Unit();
        $units = $unit_class_for_view->getByProperty($property_id_for_view);
        
        // Seçili olanlar
        $selected_types = $property_class_for_view->getRelationsForProperty($property_id_for_view, 'property_to_type_map');
        $selected_amenities = $property_class_for_view->getRelationsForProperty($property_id_for_view, 'property_to_amenity_map');
        $selected_payment_options = $property_class_for_view->getRelationsForProperty($property_id_for_view, 'property_to_payment_option_map');

        // Ana listeler (isim ve ikonları eşleştirmek için)
        $all_property_types = $property_class_for_view->getAllMasterData('property_types');
        $all_amenities = $property_class_for_view->getAllMasterData('amenities');
        $all_payment_options = $property_class_for_view->getAllMasterData('payment_options');
    }
}


// 5. GÖRÜNÜM (VIEW) DOSYALARINI YÜKLE

// Arayüzün üst kısmını (header) dahil et
include __DIR__ . '/views/partials/header.php';

// Acentenin görmesine izin verilen sayfaların listesi
$allowed_pages = ['dashboard', 'properties', 'property-detail', 'calendar', 'error']; 

if (in_array($page, $allowed_pages)) {
    $page_path = __DIR__ . "/views/{$page}.php";
    if (file_exists($page_path)) {
        include $page_path;
    } else {
        // Sayfa dosyası fiziksel olarak yoksa hata göster
        echo "<div class='p-8'><div class='alert alert-error'><strong>Hata:</strong> Sayfa dosyası bulunamadı: {$page}.php</div></div>";
    }
} else {
    // İzin verilmeyen bir sayfa istenirse, ana sayfaya (dashboard) yönlendir.
    include __DIR__ . '/views/dashboard.php';
}

// Arayüzün alt kısmını (footer) dahil et
include __DIR__ . '/views/partials/footer.php';