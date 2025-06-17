<?php
// Form gönderimi sonrası durum mesajları
$status = $_GET['status'] ?? '';
$message = '';
$message_type = 'success';

if ($status === 'saved') {
    $message = 'Tesis başarıyla kaydedildi.';
}
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> mb-6">
    <i data-feather="<?php echo $message_type === 'success' ? 'check-circle' : 'alert-triangle'; ?>"></i>
    <span><?php echo htmlspecialchars($message); ?></span>
</div>
<?php endif; ?>

<form method="POST" action="index.php?page=property-edit<?php echo $edit_mode ? '&id='.$property_id_for_view : ''; ?>" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_property">
    
    <input type="hidden" name="address_province_name" id="address_province_name">

    <?php if ($edit_mode): ?>
        <input type="hidden" name="property_id" value="<?php echo $property_id_for_view; ?>">
    <?php endif; ?>

    <div class="card mb-6">
        <div class="flex items-center gap-3 mb-4">
            <i data-feather="briefcase" class="w-6 h-6 text-primary-600"></i>
            <h3 class="text-xl font-bold">Temel Tesis Bilgileri</h3>
        </div>
        
        <div class="form-group">
            <label for="property_name">Tesis Adı</label>
            <input type="text" id="property_name" name="property_name" class="form-control" value="<?php echo htmlspecialchars($property_data['name'] ?? '', ENT_QUOTES); ?>" required>
        </div>

        <div class="form-group">
            <label>Tesis Türü</label>
            <div class="flex grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                <?php 
                $selected_type_ids = array_column($selected_types, 'type_id');
                foreach($all_property_types as $type): ?>
                <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="property_types[]" value="<?php echo $type['id']; ?>" <?php echo in_array($type['id'], $selected_type_ids) ? 'checked' : ''; ?>>
                    <span><?php echo htmlspecialchars($type['name']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="commission_rate">Komisyon Oranı (%)</label>
                <input type="number" step="0.01" id="commission_rate" name="commission_rate" class="form-control" value="<?php echo htmlspecialchars($property_data['commission_rate'] ?? '15.00', ENT_QUOTES); ?>">
            </div>
            <div class="form-group">
                <label for="cover_photo">Kapak Fotoğrafı</label>
                <?php if (!empty($property_data['cover_photo_path'])): ?>
                    <div class="mb-2">
                        <img src="<?php echo htmlspecialchars($property_data['cover_photo_path']); ?>" alt="Mevcut Kapak Fotoğrafı" class="h-24 rounded-lg border">
                        <p class="text-xs text-gray-500 mt-1">Yeni bir fotoğraf yüklerseniz bu değiştirilecektir.</p>
                    </div>
                <?php endif; ?>
                <input type="file" id="cover_photo" name="cover_photo" class="form-control" accept="image/jpeg, image/png, image/webp">
            </div>
        </div>
        
        <div class="form-group">
            <label>Adres</label>
            <div class="grid md:grid-cols-2 gap-6 mt-2">
                <div>
                    <label for="province-select" class="text-sm font-medium mb-1 block">İl</label>
                    <select id="province-select" class="form-control">
                        <option value="">İl Seçiniz...</option>
                        <?php foreach($all_provinces as $province): ?>
                            <option value="<?php echo $province['id']; ?>" data-name="<?php echo htmlspecialchars($province['name']); ?>" <?php echo (($property_data['address_province'] ?? '') == $province['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($province['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="district-select" class="text-sm font-medium mb-1 block">İlçe</label>
                    <select id="district-select" name="address_district" class="form-control" disabled>
                        <option value="">Önce İl Seçiniz...</option>
                    </select>
                </div>
            </div>
            <textarea name="address_full" class="form-control mt-4" placeholder="Açık adres (Sokak, Mahalle, No vb.)..."><?php echo htmlspecialchars($property_data['address_full'] ?? ''); ?></textarea>
        </div>
    </div>
    <div class="card mb-6">
        <div class="flex items-center gap-3 mb-4"><i data-feather="user" class="w-6 h-6 text-primary-600"></i><h3 class="text-xl font-bold">İletişim Bilgileri</h3></div>
        <div class="grid md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="contact_person_name">Yetkili Kişi Adı</label>
                <input type="text" id="contact_person_name" name="contact_person_name" class="form-control" value="<?php echo htmlspecialchars($property_data['contact_person_name'] ?? '', ENT_QUOTES); ?>">
            </div>
            <div class="form-group">
                <label for="contact_phone">Telefon Numarası</label>
                <input type="tel" id="contact_phone" name="contact_phone" class="form-control" placeholder="05xx xxx xx xx" value="<?php echo htmlspecialchars($property_data['contact_phone'] ?? '', ENT_QUOTES); ?>">
            </div>
        </div>
    </div>

    <div class="card mb-6">
        <div class="flex items-center gap-3 mb-4"><i data-feather="settings" class="w-6 h-6 text-primary-600"></i><h3 class="text-xl font-bold">Rezervasyon Ayarları</h3></div>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
            <div class="form-group">
                <label>Rezervasyon Onayı</label>
                <div class="flex gap-4 mt-2">
                    <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer flex-1"><input type="radio" name="reservation_type" value="manual" <?php echo (($property_data['reservation_type'] ?? 'manual') == 'manual') ? 'checked' : ''; ?>> <span>Manuel</span></label>
                    <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer flex-1"><input type="radio" name="reservation_type" value="auto" <?php echo (($property_data['reservation_type'] ?? '') == 'auto') ? 'checked' : ''; ?>> <span>Otomatik</span></label>
                </div>
            </div>
            <div class="form-group">
                <label>Evcil Hayvan</label>
                <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer mt-2"><input type="checkbox" name="pets_allowed" value="1" <?php echo !empty($property_data['pets_allowed']) ? 'checked' : ''; ?>> <span>Kabul ediliyor</span></label>
            </div>
            <div class="form-group">
                <label for="paid_child_age">Ücretli Çocuk Yaşı</label>
                <input type="number" id="paid_child_age" name="paid_child_age" class="form-control" value="<?php echo htmlspecialchars($property_data['paid_child_age'] ?? '6', ENT_QUOTES); ?>" min="0">
            </div>
        </div>
        <div class="form-group mt-6">
            <label>Tesis Hizmetleri</label>
            <div class="flex grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                <?php
                    $selected_amenities_ids = array_column($selected_amenities, 'amenity_id');
                    foreach($all_amenities as $amenity):
                ?>
                <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" <?php echo in_array($amenity['id'], $selected_amenities_ids) ? 'checked' : ''; ?>>
                    <i data-feather="<?php echo htmlspecialchars($amenity['icon'] ?? 'circle'); ?>" class="w-4 h-4"></i>
                    <span><?php echo htmlspecialchars($amenity['name']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="form-group mt-6">
            <label>Ödeme Seçenekleri</label>
            <div class="flex grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                <?php
                    $selected_payment_ids = array_column($selected_payment_options, 'payment_option_id');
                    foreach($all_payment_options as $option):
                ?>
                <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                    <input type="checkbox" name="payment_options[]" value="<?php echo $option['id']; ?>" <?php echo in_array($option['id'], $selected_payment_ids) ? 'checked' : ''; ?>>
                    <i data-feather="<?php echo htmlspecialchars($option['icon'] ?? 'circle'); ?>" class="w-4 h-4"></i>
                    <span><?php echo htmlspecialchars($option['name']); ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="text-right">
        <button type="submit" class="btn btn-primary btn-lg"><?php echo $edit_mode ? 'Değişiklikleri Kaydet' : 'Tesisi Oluştur'; ?></button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();

    const provinceSelect = document.getElementById('province-select');
    const districtSelect = document.getElementById('district-select');
    const provinceNameInput = document.getElementById('address_province_name');
    
    // Düzenleme modunda, kaydedilmiş ilçe adını al
    const initiallySelectedDistrict = "<?php echo htmlspecialchars($property_data['address_district'] ?? ''); ?>";
    
    // Sayfa yüklendiğinde, eğer bir il zaten seçiliyse, onun ilçelerini getir.
    if (provinceSelect.value) {
        // Seçili ilin adını gizli input'a yaz
        const selectedOption = provinceSelect.options[provinceSelect.selectedIndex];
        if(selectedOption) {
            provinceNameInput.value = selectedOption.dataset.name;
        }
        fetchDistricts(provinceSelect.value, initiallySelectedDistrict);
    }

    // İl seçimi değiştiğinde
    provinceSelect.addEventListener('change', function() {
        const provinceId = this.value;
        const selectedOption = this.options[this.selectedIndex];
        
        // Gizli input'u güncelle
        provinceNameInput.value = selectedOption ? (selectedOption.dataset.name || '') : '';
        
        fetchDistricts(provinceId, null);
    });

    function fetchDistricts(provinceId, selectedDistrictName) {
        if (!provinceId) {
            districtSelect.innerHTML = '<option value="">Önce İl Seçiniz...</option>';
            districtSelect.disabled = true;
            return;
        }

        districtSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        districtSelect.disabled = true;

        fetch(`/api/index.php?endpoint=get_districts&province_id=${provinceId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.districts.length > 0) {
                    districtSelect.innerHTML = '<option value="">İlçe Seçiniz...</option>';
                    data.districts.forEach(districtName => {
                        const option = document.createElement('option');
                        option.value = districtName;
                        option.textContent = districtName;
                        if (districtName === selectedDistrictName) {
                            option.selected = true;
                        }
                        districtSelect.appendChild(option);
                    });
                    districtSelect.disabled = false;
                } else {
                    districtSelect.innerHTML = '<option value="">İlçe bulunamadı.</option>';
                }
            })
            .catch(error => {
                console.error('İlçeler yüklenirken hata oluştu:', error);
                districtSelect.innerHTML = '<option value="">Bir hata oluştu.</option>';
            });
    }
});
</script>