<?php
// Bu dosyanın en başındaki tüm POST işlem kodları ana index.php'ye taşındı.
// Burada sadece görünüm için gerekli değişkenleri hazırlıyoruz.
$property = new Property();
$unit = new Unit();

$edit_mode = isset($_GET['id']) && is_numeric($_GET['id']);
$property_id = $edit_mode ? (int)$_GET['id'] : null;

$property_data = $edit_mode ? $property->getById($property_id) : null;
$units = $edit_mode ? $unit->getByProperty($property_id) : [];

// Mevcut sync tipini bir değişkene alalım, kod tekrarını önlemek için.
$sync_type = $property_data['sync_type'] ?? 'hub';
?>

<div class="property-form-page">
    <h2><?php echo $edit_mode ? 'Tesis Düzenle' : 'Yeni Tesis Ekle'; ?></h2>
    
    <form method="POST" action="index.php?page=property-add<?php echo $edit_mode ? '&id='.$property_id : ''; ?>">
        <div class="form-section">
            <h3>Tesis Bilgileri</h3>
            
            <div class="form-group">
                <label for="property_name">Tesis Adı</label>
                <input type="text" id="property_name" name="property_name" value="<?php echo htmlspecialchars($property_data['name'] ?? '', ENT_QUOTES); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="sync_type">Yönetim ve Senkronizasyon Tipi</label>
                <select id="sync_type" name="sync_type">
                    <option value="hub" <?php if($sync_type == 'hub') echo 'selected'; ?>>Doğrudan Yönetim (Hub & Google Sheet)</option>
                    <option value="wordpress" <?php if($sync_type == 'wordpress') echo 'selected'; ?>>WordPress ile Senkronize</option>
                    <option value="ical" <?php if($sync_type == 'ical') echo 'selected'; ?>>iCal ile Senkronize</option>
                </select>
                <small class="form-text text-muted">Tesisin müsaitlik verilerinin ana kaynağını seçin.</small>
            </div>
            
            <div class="form-group wordpress-fields">
                <label for="wp_site_url">WordPress Site URL</label>
                <input type="url" id="wp_site_url" name="wp_site_url" value="<?php echo htmlspecialchars($property_data['wp_site_url'] ?? '', ENT_QUOTES); ?>" placeholder="https://sitenizinadresi.com">
            </div>
            <div class="form-group wordpress-fields">
                <label>API Key</label>
                <input type="text" value="<?php echo htmlspecialchars($property_data['api_key'] ?? 'API Anahtarı, tesisi kaydettikten sonra otomatik üretilir.', ENT_QUOTES); ?>" readonly>
                <input type="hidden" name="api_key" value="<?php echo htmlspecialchars($property_data['api_key'] ?? '', ENT_QUOTES); ?>">
                <?php if ($edit_mode && $sync_type === 'wordpress'): ?>
                    <label style="display:inline-block; margin-top:10px;">
                        <input type="checkbox" name="generate_new_key" value="1"> Yeni bir API Anahtarı üret
                    </label>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="google_sheet_id">Google Sheet Linki veya ID'si (Opsiyonel)</label>
                <input type="text" id="google_sheet_id" name="google_sheet_id" class="form-control" value="<?php echo htmlspecialchars($property_data['google_sheet_id'] ?? '', ENT_QUOTES); ?>">
            </div>
            
            <button type="submit" name="save_property" class="btn btn-primary" id="save-property-button">
                <?php echo $edit_mode ? 'Değişiklikleri Kaydet' : 'Tesisi Oluştur ve Devam Et'; ?>
            </button>
        </div>
    </form>
    
    <?php if ($edit_mode): ?>
    <div class="form-section unit-add-form-section">
            <h3>Üniteler (Manuel Ekleme)</h3>
            <p>Bu tesise ait aynı tipteki odaları (örn: 4 adet Deluxe Bungalov) tek seferde ekleyebilirsiniz.</p>
            
            <form method="POST" action="index.php?page=property-add&id=<?php echo $property_id; ?>">
                <div class="form-row">
                    <div class="form-group" style="flex-grow: 2;">
                        <input type="text" name="unit_type_name" placeholder="Ünite Tipi Adı (Örn: Deluxe Bungalov)" required>
                    </div>
                    <div class="form-group">
                        <input type="number" name="unit_quantity" placeholder="Adet" value="1" min="1" required>
                    </div>
                    <div class="form-group ical-url-field" style="flex-grow: 2;">
                        <input type="url" name="ical_url" placeholder="iCal URL (Tümü için Ortak)">
                    </div>
                    <button type="submit" name="add_unit_group" class="btn">Ünite Grubunu Ekle</button>
                </div>
            </form>
        </div>

        <?php if (!empty($units)): ?>
        <div class="form-section">
            <h3>Mevcut Üniteler</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Ünite Adı</th>
                        <th>Ünite No</th>
                        <th><?php echo ($sync_type === 'wordpress') ? 'WordPress Oda ID' : 'Son Senkronizasyon'; ?></th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($units as $unit_data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($unit_data['name'], ENT_QUOTES); ?></td>
                        <td><?php echo htmlspecialchars($unit_data['unit_number'], ENT_QUOTES); ?></td>
                        <td><?php echo ($sync_type === 'wordpress') ? htmlspecialchars($unit_data['wp_room_id'] ?? 'N/A') : ($unit_data['last_sync'] ? formatDate($unit_data['last_sync']) : 'Hiç'); ?></td>
                        <td>
                            <?php if ($sync_type !== 'hub'): ?>
                                <button class="btn btn-sm" onclick="syncUnit(<?php echo $unit_data['id']; ?>)">Senkronize Et</button>
                            <?php endif; ?>
                            <a href="index.php?page=property-add&id=<?php echo $property_id; ?>&action=delete_unit&unit_id=<?php echo $unit_data['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu üniteyi silmek istediğinize emin misiniz?')">Sil</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
<div class="form-section">
    <h3>iCal Export Linkleri</h3>
    
    <?php if (empty($property_data['ical_export_key'])): ?>
        <p>iCal linklerini oluşturmak için lütfen bir güvenlik anahtarı üretin.</p>
        <a href="index.php?page=property-add&id=<?php echo $property_id; ?>&action=generate_ical_key" class="btn">Güvenlik Anahtarı Üret</a>
    <?php else: ?>
        <p>Bu linkleri acenteler ve diğer platformlarla takvim senkronizasyonu için kullanabilirsiniz.</p>
        
        <?php
        // Üniteleri wp_room_id'ye (WordPress) veya ana isme (Manuel) göre grupla
        $rooms_grouped = [];
        foreach ($units as $unit_data) {
            $base_name = preg_replace('/ - Ünite \d+$/', '', $unit_data['name']);
            $group_key = !empty($unit_data['wp_room_id']) ? 'wp_' . $unit_data['wp_room_id'] : 'manual_' . $base_name;
            $rooms_grouped[$group_key][] = $unit_data;
        }

        if (!empty($rooms_grouped)):
            foreach ($rooms_grouped as $group_key => $room_units):
                $room_name = htmlspecialchars(preg_replace('/ - Ünite \d+$/', '', $room_units[0]['name']));
                
                if (strpos($group_key, 'wp_') === 0) {
                    $link_id = (int)str_replace('wp_', '', $group_key);
                } else {
                    $link_id = urlencode(str_replace('manual_', '', $group_key));
                }
        ?>
            <div class="form-group" style="border-top: 1px solid #eee; padding-top: 15px; margin-top: 15px;">
                <label><strong><?php echo $room_name; ?> Müsaitlik Takvimi (<?php echo count($room_units); ?> Ünite)</strong></label>
                <input type="text" readonly 
                       value="<?php echo SITE_URL . 'ical/export/' . $property_data['ical_export_key'] . '/room/' . $link_id . '.ics'; ?>" 
                       onclick="this.select();">
                <small>Bu link, bu konaklama birimine ait tüm üniteler dolu olduğunda o günü "dolu" gösterir.</small>
            </div>
        <?php 
            endforeach;
        else:
            echo "<p><em>iCal linki üretmek için önce en az bir ünite eklenmelidir.</em></p>";
        endif;
        ?>
        <hr>
        <a href="index.php?page=property-add&id=<?php echo $property_id; ?>&action=generate_ical_key" class="btn btn-sm" onclick="return confirm('Mevcut linkler geçersiz olacak. Yeni bir güvenlik anahtarı üretmek istediğinize emin misiniz?');">Güvenlik Anahtarını Yenile</a>
    <?php endif; ?>
</div>
        
    <?php endif; ?>
</div>