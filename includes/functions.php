<?php
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isAjax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function formatDate($date) {
    return date('d.m.Y', strtotime($date));
}

function getDatesInRange($start_date, $end_date) {
    $dates = [];
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    while ($current <= $end) {
        $dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }
    
    return $dates;
}

/**
 * Google Sheets URL'sinden Spreadsheet ID'sini ayıklar.
 * @param string $url
 * @return string|null
 */
function extractSheetIdFromUrl($url) {
    if (preg_match('/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $matches)) {
        return $matches[1];
    }
    // Eğer direkt ID girilmişse, onu döndür
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }
    return null;
}