<?php
// agent/views/properties.php (NİHAİ LİSTE GÖRÜNÜMÜ)

// Kontrolcüden gelen değişkenler burada kullanılır:
// $properties, $filter_provinces, $filter_amenities
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Tesisler</h2>
    </div>

<div class="card mb-6">
    <form method="GET">
        <input type="hidden" name="page" value="properties">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="province-filter" class="font-medium text-sm">İl</label>
                <select id="province-filter" name="province" class="form-control mt-1">
                    <option value="">Tüm İller</option>
                    <?php foreach ($filter_provinces as $province): ?>
                        <option value="<?php echo htmlspecialchars($province); ?>" <?php echo (($_GET['province'] ?? '') == $province) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($province); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="md:col-span-2">
                <label class="font-medium text-sm">Tesis Hizmetleri</label>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-2 mt-2">
                    <?php foreach ($filter_amenities as $amenity): ?>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="amenities[]" value="<?php echo $amenity['id']; ?>" <?php echo in_array($amenity['id'], $_GET['amenities'] ?? []) ? 'checked' : ''; ?>>
                            <span class="text-sm"><?php echo htmlspecialchars($amenity['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="text-right mt-4">
            <a href="index.php?page=properties" class="btn btn-secondary">Filtreyi Temizle</a>
            <button type="submit" class="btn btn-primary">Filtrele</button>
        </div>
    </form>
</div>


<div class="space-y-4">
    <?php if (empty($properties)): ?>
        <div class="card text-center py-12">
            <i data-feather="search" class="mx-auto h-12 w-12 text-gray-400"></i>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Sonuç Bulunamadı.</h3>
            <p class="mt-1 text-sm text-gray-500">Lütfen filtre kriterlerinizi değiştirerek tekrar deneyin.</p>
        </div>
    <?php else: ?>
        <?php foreach ($properties as $prop): ?>
            <div class="card flex flex-col md:flex-row items-center gap-6">
                <div class="w-full md:w-48 h-48 md:h-32 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                    <?php if (!empty($prop['cover_photo_path'])): ?>
                        <img src="<?php echo htmlspecialchars($prop['cover_photo_path']); ?>" alt="<?php echo htmlspecialchars($prop['name']); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center text-gray-400"><i data-feather="image" class="w-10 h-10"></i></div>
                    <?php endif; ?>
                </div>

                <div class="flex-grow">
                    <?php if (!empty($prop['property_types'])): ?>
                        <p class="text-xs font-bold text-primary-600 uppercase tracking-wider"><?php echo htmlspecialchars($prop['property_types']); ?></p>
                    <?php endif; ?>
                    <h3 class="font-bold text-xl text-gray-900 mt-1"><?php echo htmlspecialchars($prop['name']); ?></h3>
                    <div class="flex items-center gap-2 text-sm text-gray-500 mt-1">
                        <i data-feather="map-pin" class="w-4 h-4"></i>
                        <span><?php echo htmlspecialchars($prop['address_district'] ?? ''); ?>, <?php echo htmlspecialchars($prop['address_province'] ?? 'Adres Belirtilmemiş'); ?></span>
                    </div>

                    <?php if (!empty($prop['amenities_list'])): ?>
                        <div class="flex items-center gap-3 flex-wrap mt-3">
                            <?php 
                                $amenities = explode('|', $prop['amenities_list']);
                                foreach (array_slice($amenities, 0, 5) as $amenity_str): // En fazla 5 hizmet göster
                                    $parts = explode(':', $amenity_str, 2);
                            ?>
                                <span class="flex items-center gap-1.5 text-xs" title="<?php echo htmlspecialchars($parts[0]); ?>">
                                    <i data-feather="<?php echo htmlspecialchars($parts[1] ?? 'circle'); ?>" class="w-4 h-4 text-gray-500"></i>
                                </span>
                            <?php endforeach; if(count($amenities) > 5) echo '<span class="text-xs text-gray-500">...</span>'; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="w-full md:w-auto flex md:flex-col justify-between md:justify-center items-center md:items-end md:text-right gap-4 mt-4 md:mt-0 border-t md:border-t-0 md:border-l pt-4 md:pt-0 md:pl-6 border-gray-100">
                    <div>
                        <span class="font-bold text-lg"><?php echo $prop['unit_count']; ?></span>
                        <span class="text-sm text-gray-500">ünite</span>
                    </div>
                    <a href="index.php?page=property-detail&id=<?php echo $prop['id']; ?>" class="btn btn-primary">
                        <span>Detayları Gör</span>
                        <i data-feather="arrow-right" class="w-4 h-4"></i>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    feather.replace();
</script>