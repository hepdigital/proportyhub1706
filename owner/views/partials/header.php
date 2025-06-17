<?php
$page_titles = [
    'dashboard' => 'Panelim',
    'calendar' => 'Takvim',
    'properties' => 'Tesislerim',
    'property-edit' => 'Tesis Yönetimi',
    'hizmetler' => 'Ek Hizmetler',
    'notifications' => 'Bildirimler' // Yeni başlık eklendi
];
$current_title = $page_titles[$page] ?? 'Panel';

// --- BİLDİRİM SAYISINI ÇEKME ---
// Bu kod bloğu yeni eklendi.
$notification_class = new Notification();
$unread_count = $notification_class->getUnreadCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($current_title); ?> - Property Hub</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/css/owner-style.css?v=<?php echo time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
</head>
<body>

<div class="owner-panel-wrapper">
    <!-- KENAR ÇUBUĞU (SIDEBAR) -->
    <aside class="sidebar" id="sidebar">
        <div>
            <div class="sidebar-header">
                <a href="index.php" class="logo">Property<span>Hub</span></a>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="index.php?page=dashboard" class="<?php echo ($page === 'dashboard') ? 'active' : ''; ?>"><i data-feather="home"></i> <span>Panelim</span></a></li>
                    <li><a href="index.php?page=calendar" class="<?php echo ($page === 'calendar') ? 'active' : ''; ?>"><i data-feather="calendar"></i> <span>Takvim</span></a></li>
                    <li><a href="index.php?page=properties" class="<?php echo ($page === 'properties' || $page === 'property-edit') ? 'active' : ''; ?>"><i data-feather="briefcase"></i> <span>Tesislerim</span></a></li>
                    <!-- YENİ BİLDİRİM MENÜSÜ EKLENDİ -->
                    <li>
                        <a href="index.php?page=notifications" class="<?php echo ($page === 'notifications') ? 'active' : ''; ?>">
                            <i data-feather="bell"></i> 
                            <span>Bildirimler</span>
                            <?php if ($unread_count > 0): ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li>
    <a href="index.php?page=reports" class="<?php echo ($page === 'reports') ? 'active' : ''; ?>">
        <i data-feather="bar-chart-2"></i> 
        <span>Raporlar</span>
    </a>
</li>
                    <li><a href="index.php?page=hizmetler" class="<?php echo ($page === 'hizmetler') ? 'active' : ''; ?>"><i data-feather="tag"></i> <span>Ek Hizmetler</span></a></li>
                </ul>
            </nav>
        </div>
        <div class="sidebar-footer">
             <a href="logout.php"><i data-feather="log-out"></i> <span>Çıkış Yap</span></a>
        </div>
    </aside>

    <!-- ANA İÇERİK ALANI -->
    <div class="main-content-area" id="main-content-area">
        <header class="main-header">
            <button class="mobile-menu-button" id="mobile-menu-toggle"><i data-feather="menu"></i></button>
            <h1 class="flex-grow text-center md:text-left"><?php echo htmlspecialchars($current_title); ?></h1>
            <div class="user-menu">
                <span>Merhaba, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Kullanıcı'); ?></strong></span>
            </div>
        </header>
        <main class="page-content">
