<?php
/**
 * Property Hub Ana Giriş Noktası
 * Bu dosya, ziyaretçileri varsayılan olarak Tesis Sahibi Paneli'nin
 * giriş ekranına yönlendirir. Süper Admin paneline /admin/ adresi üzerinden erişilir.
 */

// config.php dosyasını yükleyerek SITE_URL sabitine erişelim
require_once 'config.php';

// Ziyaretçiyi Tesis Sahibi giriş ekranına yönlendir
header('Location: ' . rtrim(SITE_URL, '/') . '/owner/login.php');
exit();