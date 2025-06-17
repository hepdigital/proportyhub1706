<?php
// Veritabanı ayarları
define('DB_HOST', 'localhost');
define('DB_NAME', 'oteldepo_phub');
define('DB_USER', 'oteldepo_phub');
define('DB_PASS', '~4e7o$rHRzLeyx8r');

// Genel ayarlar
define('SITE_URL', 'https://propertyhub.vibesmode.xyz/');
define('SITE_NAME', 'Property Hub');
define('TIMEZONE', 'Europe/Istanbul');

// Google Sheets API Ayarları
define('GOOGLE_APPLICATION_CREDENTIALS', __DIR__ . '/google-api-credentials.json');
define('GOOGLE_SPREADSHEET_ID', '16Ce2uyLqyzMwV9n4o_iqlKGQexeM9JH4IlOZdLjHVOg');
define('GOOGLE_SHEET_DEFAULT_RANGE', '1');
define('GOOGLE_APPS_SCRIPT_SECRET', '12345');

// Hata gösterimi (production'da false yapın)
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

date_default_timezone_set(TIMEZONE);

// FAZLADAN '}' KARAKTERİ BURADAN KALDIRILDI