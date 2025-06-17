<?php
// api/manual-update.php

session_start(); // Oturum kontrolü için gerekli

$user = new User();
if (!$user->isLoggedIn()) {
    jsonResponse(['error' => 'Lütfen giriş yapın.'], 401);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$unit_id = $data['unit_id'] ?? null;
$date = $data['date'] ?? null;
$is_available = $data['is_available'] ?? true; // is_available 'false' gelirse dolu, 'true' ise müsait

if (!$unit_id || !$date) {
    jsonResponse(['error' => 'Eksik parametre.'], 400);
    exit;
}

// Güvenlik: Kullanıcının bu üniteye sahip olup olmadığını kontrol et
$unitModel = new Unit();
$unitData = $unitModel->getById($unit_id);
if (!$unitData || $unitData['owner_id'] != $_SESSION['user_id']) {
    jsonResponse(['error' => 'Bu işlem için yetkiniz yok.'], 403);
    exit;
}

// Veritabanını güncelle
$calendar = new Calendar();
$result = $calendar->updateAvailability(
    $unit_id,
    $date,
    $is_available,
    $is_available ? null : 'manual_block',
    'manual'
);

if ($result) {
    jsonResponse(['success' => true]);
} else {
    jsonResponse(['error' => 'Veritabanı güncellenemedi.'], 500);
}