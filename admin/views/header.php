<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="/../admin/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <h1 class="logo"><?php echo SITE_NAME; ?></h1>
            <ul class="nav-menu">
                <li><a href="index.php" <?php echo $page == 'dashboard' ? 'class="active"' : ''; ?>>Dashboard</a></li>
                <li><a href="index.php?page=properties" <?php echo $page == 'properties' ? 'class="active"' : ''; ?>>Tesisler</a></li>
                <li><a href="index.php?page=calendar" <?php echo $page == 'calendar' ? 'class="active"' : ''; ?>>Takvim</a></li>
            </ul>
        </div>
    </nav>
    <main class="main-content">
        <div class="container">