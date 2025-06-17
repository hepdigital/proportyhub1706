<?php
// agent/views/settings.php
// Kontrolcüden gelen değişkenler: $agent_data, $all_provinces
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Profil ve Ayarlar</h2>
</div>

<?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
<div class="alert alert-success mb-6">
    <i data-feather="check-circle"></i>
    <span>Profil bilgileriniz başarıyla güncellendi.</span>
</div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="index.php?page=settings">
        <input type="hidden" name="action" value="update_profile">
        
        <div class="grid md:grid-cols-2 gap-6">
            <div class="form-group"><label for="agency_name">Acente Adı</label><input type="text" id="agency_name" name="agency_name" class="form-control" value="<?php echo htmlspecialchars($agent_data['agency_name'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="email">E-posta Adresi (Değiştirilemez)</label><input type="email" id="email" name="email" class="form-control bg-gray-100" value="<?php echo htmlspecialchars($agent_data['email'] ?? ''); ?>" readonly></div>
        </div>

        <h3 class="text-lg font-semibold mt-8 mb-4 border-t pt-6">Konum Bilgileri</h3>
        <div class="grid md:grid-cols-2 gap-6">
             <div class="form-group">
                <label for="province-select">İl</label>
                <!-- DÜZELTME: Select elementinin `name` attribute'ü artık ilin ID'sini gönderiyor -->
                <select id="province-select" name="agency_province_id" class="form-control" required>
                    <option value="">İl Seçiniz...</option>
                    <?php foreach($all_provinces as $province): ?>
                        <option value="<?php echo $province['id']; ?>" <?php echo (($agent_data['agency_province_id'] ?? '') == $province['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="district-select">İlçe</label>
                <!-- DÜZELTME: Select elementinin `name` attribute'ü artık ilçenin ID'sini gönderiyor -->
                <select id="district-select" name="agency_district_id" class="form-control" required disabled>
                    <option value="">Önce İl Seçiniz...</option>
                </select>
            </div>
        </div>
        
        <div class="text-right mt-6">
            <button type="submit" class="btn btn-primary btn-lg">Değişiklikleri Kaydet</button>
        </div>
    </form>
</div>

<!-- DÜZELTME: JavaScript, API'den gelen {id, name} verisini işleyecek şekilde güncellendi -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();

    const provinceSelect = document.getElementById('province-select');
    const districtSelect = document.getElementById('district-select');
    const initiallySelectedDistrictId = "<?php echo htmlspecialchars($agent_data['agency_district_id'] ?? ''); ?>";

    function fetchDistricts(provinceId, selectedDistrictId) {
        if (!provinceId) {
            districtSelect.innerHTML = '<option value="">Önce İl Seçiniz...</option>';
            districtSelect.disabled = true;
            return;
        }
        
        districtSelect.disabled = true;
        districtSelect.innerHTML = '<option value="">Yükleniyor...</option>';

        fetch(`/api/get_districts.php?province_id=${provinceId}`)
            .then(response => response.json())
            .then(data => {
                districtSelect.innerHTML = '<option value="">İlçe Seçiniz...</option>';
                if (data.success && Array.isArray(data.districts)) {
                    data.districts.forEach(district => {
                        const option = document.createElement('option');
                        option.value = district.id; // DEĞER OLARAK ID
                        option.textContent = district.name; // GÖRÜNEN METİN OLARAK İSİM
                        if (district.id == selectedDistrictId) { // == ile karşılaştırma yapılıyor
                            option.selected = true;
                        }
                        districtSelect.appendChild(option);
                    });
                    districtSelect.disabled = false;
                } else {
                    districtSelect.innerHTML = '<option value="">İlçe bulunamadı.</option>';
                }
            }).catch(err => {
                districtSelect.innerHTML = '<option value="">Hata oluştu.</option>';
            });
    }

    if (provinceSelect.value) {
        fetchDistricts(provinceSelect.value, initiallySelectedDistrictId);
    }
    
    provinceSelect.addEventListener('change', function() {
        fetchDistricts(this.value, null);
    });
});
</script>
