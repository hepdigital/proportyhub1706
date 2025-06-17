<?php
class GoogleSheetsService {
    private $client;
    private $service;

    public function __construct() {
        if (!defined('GOOGLE_APPLICATION_CREDENTIALS') || !file_exists(GOOGLE_APPLICATION_CREDENTIALS)) {
            throw new Exception("Google API kimlik bilgileri bulunamadı veya yapılandırılmadı.");
        }
        $this->client = new Google\Client();
        $this->client->setAuthConfig(GOOGLE_APPLICATION_CREDENTIALS);
        $this->client->addScope(Google\Service\Sheets::SPREADSHEETS);
        $this->service = new Google\Service\Sheets($this->client);
    }

    public function updateRange($spreadsheetId, $range, $values) {
        if (empty($spreadsheetId)) return false;
        $body = new Google\Service\Sheets\ValueRange(['values' => $values]);
        $params = ['valueInputOption' => 'USER_ENTERED'];
        return $this->service->spreadsheets_values->update($spreadsheetId, $range, $body, $params);
    }

    public function clearRange($spreadsheetId, $range) {
        if (empty($spreadsheetId)) return false;
        $body = new Google\Service\Sheets\ClearValuesRequest();
        return $this->service->spreadsheets_values->clear($spreadsheetId, $range, $body);
    }

    public function updateAvailabilityForUnit($spreadsheetId, $unitId, $availabilityData) {
        $unitSheetName = 'Unit_' . $unitId;
        try {
            // Önce mevcut veriyi temizle (Başlık satırını korumak için A2'den başla)
            $this->clearRange($spreadsheetId, $unitSheetName . '!A2:C');

            if (!empty($availabilityData)) {
                // Sadece veri satırlarını ekle
                $this->updateRange($spreadsheetId, $unitSheetName . '!A2', $availabilityData);
            }
        } catch (Exception $e) {
            error_log("Google Sheets API error for unit $unitId on spreadsheet '$spreadsheetId': " . $e->getMessage());
        }
    }
}