<?php
// agent/views/properties.php (Gelişmiş Filtreleme ve Sonuç Listesi)

// Kontrolcüden gelen değişkenler: $properties, $filter_provinces, $categorized_features, $filters
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/tr.js"></script>


<h2 class="text-2xl font-bold mb-6">Tesis Ara & Rezerve Et</h2>

<?php
// Rezervasyon sonrası başarı/hata mesajını göster
if (isset($_SESSION['flash_message'])) {
    $message_data = $_SESSION['flash_message'];
    $message_type = $message_data['success'] ? 'success' : 'error';
    $icon = $message_data['success'] ? 'check-circle' : 'alert-triangle';
    echo "<div class='alert alert-{$message_type} mb-6'><i data-feather='{$icon}'></i><span>{$message_data['message']}</span></div>";
    unset($_SESSION['flash_message']);
}
?>

<div class="card mb-8">
    <form method="GET" id="filter-form">
        <input type="hidden" name="page" value="properties">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Tarih Filtresi -->
            <div class="form-group">
                <label for="start_date">Giriş Tarihi</label>
                <input type="text" id="start_date" name="start_date" class="form-control" placeholder="Tarih seçin..." value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="end_date">Çıkış Tarihi</label>
                <input type="text" id="end_date" name="end_date" class="form-control" placeholder="Tarih seçin..." value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">
            </div>
            
            <!-- Lokasyon Filtresi -->
            <div class="form-group">
                <label for="province-filter">İl</label>
                <select id="province-filter" name="province" class="form-control">
                    <option value="">Tüm İller</option>
                    <?php foreach ($filter_provinces as $province): ?>
                        <option value="<?php echo htmlspecialchars($province); ?>" <?php echo (($filters['province'] ?? '') == $province) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Kişi Sayısı Filtresi -->
            <div class="form-group">
                <label for="guest_count">Kişi Sayısı</label>
                <input type="number" id="guest_count" name="guest_count" class="form-control" min="1" value="<?php echo htmlspecialchars($filters['guest_count'] ?? '1'); ?>">
            </div>
        </div>

        <!-- Özellik Filtreleri -->
        <div class="mt-6">
             <label class="font-semibold text-gray-700 mb-3 block">Oda Özellikleri</label>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-x-6 gap-y-3">
            <?php foreach($categorized_features as $category): if(empty($category['features'])) continue; ?>
                <?php foreach($category['features'] as $feature): ?>
                <label class="flex items-center gap-2 cursor-pointer text-sm">
                    <input type="checkbox" name="features[]" value="<?php echo $feature['id']; ?>" <?php echo in_array($feature['id'], $filters['features'] ?? []) ? 'checked' : ''; ?>>
                    <span><?php echo htmlspecialchars($feature['name']); ?></span>
                </label>
                <?php endforeach; ?>
            <?php endforeach; ?>
            </div>
        </div>

        <div class="text-right mt-6 border-t pt-4">
            <a href="index.php?page=properties" class="btn btn-secondary">Filtreyi Temizle</a>
            <button type="submit" class="btn btn-primary">
                <i data-feather="search" class="w-4 h-4"></i> Müsait Tesisleri Bul
            </button>
        </div>
    </form>
</div>

<!-- Sonuçlar -->
<div class="space-y-6">
    <?php if (empty($properties)): ?>
        <div class="card text-center py-12">
            <i data-feather="search" class="mx-auto h-12 w-12 text-gray-400"></i>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Arama Kriterlerinize Uygun Sonuç Bulunamadı</h3>
            <p class="mt-1 text-sm text-gray-500">Lütfen filtreleri değiştirerek tekrar deneyin. Özellikle tarih seçimi yapmanız önerilir.</p>
        </div>
    <?php else: ?>
        <?php foreach ($properties as $prop): ?>
            <div class="card flex flex-col md:flex-row items-stretch gap-6">
                <div class="w-full md:w-56 h-48 md:h-auto flex-shrink-0">
                    <img src="<?php echo htmlspecialchars($prop['cover_photo_path'] ?? 'https://placehold.co/400x400/f3f4f6/9ca3af?text=Fotoğraf+Yok'); ?>" 
                         alt="<?php echo htmlspecialchars($prop['property_name']); ?>" 
                         class="w-full h-full object-cover rounded-lg">
                </div>

                <div class="flex-grow flex flex-col">
                    <h3 class="font-bold text-xl text-gray-900"><?php echo htmlspecialchars($prop['property_name']); ?></h3>
                    <p class="font-semibold text-primary-600"><?php echo htmlspecialchars($prop['unit_type_name']); ?></p>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <i data-feather="map-pin" class="w-4 h-4"></i>
                        <span><?php echo htmlspecialchars($prop['address_district'] ?? ''); ?>, <?php echo htmlspecialchars($prop['address_province'] ?? ''); ?></span>
                    </div>
                     <div class="flex items-center gap-3 text-sm text-gray-500 mt-1">
                        <span class="flex items-center gap-1"><i data-feather="users" class="w-4 h-4"></i> <?php echo $prop['capacity']; ?> Kişi</span>
                    </div>
                    <!-- Özellikler ve diğer detaylar buraya eklenebilir -->
                    <div class="flex-grow"></div> <!-- Boşluk bırakmak için -->
                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-end justify-between">
                         <?php if (isset($prop['total_price'])): ?>
                         <div>
                            <p class="text-sm text-gray-500">Toplam Fiyat</p>
                            <p class="font-bold text-2xl text-gray-800"><?php echo number_format($prop['total_price'], 2, ',', '.'); ?> ₺</p>
                            <p class="text-xs text-green-600 font-semibold">Komisyon Kazancı: <?php echo number_format($prop['commission_amount'], 2, ',', '.'); ?> ₺</p>
                        </div>
                        <?php endif; ?>
                        <!-- REZERVASYON BUTONU -->
                        <button class="btn btn-primary" onclick="openReservationModal(<?php echo htmlspecialchars(json_encode($prop), ENT_QUOTES); ?>)">
                            Rezervasyon Yap
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- REZERVASYON MODAL PENCERESİ -->
<div id="reservation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden items-center justify-center z-50 p-4">
    <div class="card w-full max-w-lg animate-fade-in-up relative">
        <button onclick="closeReservationModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800"><i data-feather="x"></i></button>
        <h3 class="text-xl font-bold mb-2" id="modal-property-name"></h3>
        <p class="font-semibold text-primary-600 mb-4" id="modal-unit-type-name"></p>
        
        <form method="POST" action="index.php">
            <input type="hidden" name="page" value="properties">
            <input type="hidden" name="action" value="create_reservation">
            <input type="hidden" name="property_id" id="modal-property-id">
            <input type="hidden" name="unit_type_id" id="modal-unit-type-id">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($filters['start_date'] ?? ''); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($filters['end_date'] ?? ''); ?>">

            <h4 class="font-semibold text-gray-700 mb-3">Misafir Bilgileri</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="form-group"><label for="guest_name">Adı Soyadı</label><input type="text" name="guest_name" class="form-control" required></div>
                <div class="form-group"><label for="guest_phone">Telefon</label><input type="tel" name="guest_phone" class="form-control"></div>
            </div>
             <div class="form-group"><label for="guest_email">E-posta</label><input type="email" name="guest_email" class="form-control"></div>

            <div class="text-right mt-6">
                <button type="button" onclick="closeReservationModal()" class="btn btn-secondary">İptal</button>
                <button type="submit" class="btn btn-primary">Rezervasyonu Onayla</button>
            </div>
        </form>
    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
        
        // Tarih seçicileri (Flatpickr) başlat
        const fpConfig = {
            dateFormat: "Y-m-d",
            locale: "tr",
            minDate: "today"
        };
        flatpickr("#start_date", fpConfig);
        flatpickr("#end_date", fpConfig);
    });

    const modal = document.getElementById('reservation-modal');

    function openReservationModal(propertyData) {
        // Formu doldur
        document.getElementById('modal-property-name').textContent = propertyData.property_name;
        document.getElementById('modal-unit-type-name').textContent = propertyData.unit_type_name;
        document.getElementById('modal-property-id').value = propertyData.property_id;
        document.getElementById('modal-unit-type-id').value = propertyData.unit_type_id;

        // Formdaki tarih alanları zaten dolu olduğu için tekrar doldurmaya gerek yok
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        feather.replace(); // Modal içindeki ikonları render et
    }

    function closeReservationModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>
