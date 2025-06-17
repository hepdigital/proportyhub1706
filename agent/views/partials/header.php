<?php
$page_titles = [
    'dashboard' => 'Panelim',
    'calendar' => 'Takvim',
    'properties' => 'Tesislerim',
    'property-edit' => 'Tesis Yönetimi',
    'hizmetler' => 'Ek Hizmetler'
];
$current_title = $page_titles[$page] ?? 'Panel';
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
    
    <link rel="stylesheet" href="assets/css/agent-style.css?v=<?php echo time(); ?>">

    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
                    <li>
                        <a href="index.php?page=dashboard" class="<?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                            <i data-feather="home"></i> <span>Panelim</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=calendar" class="<?php echo ($page === 'calendar') ? 'active' : ''; ?>">
                            <i data-feather="calendar"></i> <span>Takvim</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=properties" class="<?php echo ($page === 'properties' || $page === 'property-edit') ? 'active' : ''; ?>">
                            <i data-feather="briefcase"></i> <span>Tesislerim</span>
                        </a>
                    </li>
                    <li>
                        <a href="index.php?page=hizmetler" class="<?php echo ($page === 'hizmetler') ? 'active' : ''; ?>">
                            <i data-feather="tag"></i> <span>Ek Hizmetler</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <div class="sidebar-footer">
             <a href="logout.php">
                <i data-feather="log-out"></i> <span>Çıkış Yap</span>
             </a>
        </div>
    </aside>

    <!-- MOBİL İÇİN ARKA PLAN KARARTMA -->
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- ANA İÇERİK ALANI -->
    <div class="main-content-area" id="main-content-area">
        <header class="main-header">
            <!-- Mobil Menü Butonu -->
            <button class="mobile-menu-button" id="mobile-menu-toggle">
                <i data-feather="menu"></i>
            </button>
            
            <h1 class="flex-grow text-center md:text-left"><?php echo htmlspecialchars($current_title); ?></h1>

            <div class="user-menu">
                <span>Merhaba, <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Kullanıcı'); ?></strong></span>
            </div>
        </header>

        <main class="page-content">
