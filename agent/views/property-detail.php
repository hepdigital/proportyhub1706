<?php
// agent/views/property-detail.php (NİHAİ VERSİYON)

// HATA ÇÖZÜMÜ: Eşleştirme dizileri döngülerden önce burada oluşturuluyor.
$type_map = array_column($all_property_types, 'name', 'id');
$amenity_map = array_column($all_amenities, null, 'id');
$payment_option_map = array_column($all_payment_options, null, 'id');
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($property_data['name']); ?></h2>
    <a href="index.php?page=properties" class="btn btn-secondary">
        <i data-feather="arrow-left" class="w-4 h-4"></i>
        <span>Tesis Listesine Geri Dön</span>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2 space-y-8">
        <div class="card p-0 overflow-hidden">
            <?php if (!empty($property_data['cover_photo_path'])): ?>
                <img src="<?php echo htmlspecialchars($property_data['cover_photo_path']); ?>" alt="<?php echo htmlspecialchars($property_data['name']); ?>" class="w-full h-80 object-cover">
            <?php else: ?>
                <div class="w-full h-80 flex items-center justify-center bg-gray-100 text-gray-400">
                    <i data-feather="image" class="w-20 h-20"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="card">
            <h3 class="text-xl font-bold mb-4">Konaklama Birimleri (<?php echo count($units); ?>)</h3>
            <div class="overflow-x-auto">
                <table class="data-table w-full">
                    <thead><tr><th>Birim Adı</th><th>Ünite No</th></tr></thead>
                    <tbody>
                        <?php if (empty($units)): ?>
                            <tr><td colspan="2" class="text-center text-gray-500 py-4">Bu tesise henüz birim eklenmemiş.</td></tr>
                        <?php else: ?>
                            <?php foreach($units as $unit): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($unit['name']); ?></td>
                                <td><?php echo htmlspecialchars($unit['unit_number']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card">
            <h3 class="font-bold mb-4">İletişim Bilgileri</h3>
            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3"><i data-feather="user" class="w-5 h-5 text-gray-400"></i>
                    <div>
                        <p class="text-gray-500">Yetkili Kişi</p>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($property_data['contact_person_name'] ?? '-'); ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-3"><i data-feather="phone" class="w-5 h-5 text-gray-400"></i>
                    <div>
                        <p class="text-gray-500">Telefon Numarası</p>
                        <p class="font-medium text-gray-800"><?php echo htmlspecialchars($property_data['contact_phone'] ?? '-'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3 class="font-bold mb-4">Tesis Özellikleri</h3>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-2">Tesis Türü</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($selected_types as $type_map_item): ?>
                            <span class="badge"><?php echo htmlspecialchars($type_map[$type_map_item['type_id']] ?? 'Bilinmiyor'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500 mb-2">Hizmetler</p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($selected_amenities as $amenity_map_item): ?>
                            <span class="badge">
                                <i data-feather="<?php echo htmlspecialchars($amenity_map[$amenity_map_item['amenity_id']]['icon'] ?? 'circle'); ?>" class="w-3.5 h-3.5 mr-1"></i>
                                <?php echo htmlspecialchars($amenity_map[$amenity_map_item['amenity_id']]['name'] ?? 'Bilinmiyor'); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
             <h3 class="font-bold mb-4">Rezervasyon & Ödeme</h3>
             <div class="space-y-4 text-sm">
                 <div class="flex items-center justify-between"><span class="text-gray-600">Komisyon Oranı:</span><span class="font-bold text-lg text-primary-600">%<?php echo htmlspecialchars(number_format($property_data['commission_rate'], 2)); ?></span></div>
                 <div class="flex items-center justify-between"><span class="text-gray-600">Rezervasyon Onayı:</span><span class="font-medium"><?php echo ($property_data['reservation_type'] == 'auto') ? 'Otomatik' : 'Manuel'; ?></span></div>
                 <div class="flex items-center justify-between"><span class="text-gray-600">Evcil Hayvan:</span><span class="font-medium"><?php echo !empty($property_data['pets_allowed']) ? 'Kabul Ediliyor' : 'Kabul Edilmiyor'; ?></span></div>
                 <div>
                    <p class="text-gray-600 mb-2">Ödeme Seçenekleri:</p>
                     <div class="flex flex-wrap gap-2">
                         <?php foreach($selected_payment_options as $option_map_item): ?>
                            <span class="badge">
                                <i data-feather="<?php echo htmlspecialchars($payment_option_map[$option_map_item['payment_option_id']]['icon'] ?? 'circle'); ?>" class="w-3.5 h-3.5 mr-1"></i>
                                <?php echo htmlspecialchars($payment_option_map[$option_map_item['payment_option_id']]['name'] ?? 'Bilinmiyor'); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                 </div>
             </div>
        </div>
    </div>
</div>

<script>
    feather.replace();
</script>

<style>
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background-color: var(--gray-100);
        color: var(--gray-800);
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }
</style>