<?php
// owner/views/unit-type-add.php (YENİ DOSYA)

// Kontrolcü (owner/index.php) tarafından hazırlanan değişkenler:
// $property_id, $property_data, $categorized_features
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold">Yeni Oda Tipi Oluştur</h2>
        <p class="text-gray-500 mt-1">Tesis: <strong><?php echo htmlspecialchars($property_data['name']); ?></strong></p>
    </div>
    <a href="index.php?page=unit-types&property_id=<?php echo $property_id; ?>" class="btn btn-secondary"><i data-feather="arrow-left" class="w-4 h-4"></i><span>Geri Dön</span></a>
</div>

<form method="POST" action="index.php?page=unit-type-add" id="unit-type-form">
    <input type="hidden" name="action" value="create_unit_type">
    <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">

<div class="card mb-6">
    <h3 class="font-bold text-lg mb-4">Temel Bilgiler</h3>
    <div class="grid md:grid-cols-2 gap-6">
        <div class="form-group">
            <label for="name">Oda Tipi Adı</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="Örn: Deluxe Havuzlu Bungalov" required>
        </div>
        
        <div class="form-group">
            <label for="quantity">Ünite Sayısı</label>
            <input type="number" id="quantity" name="quantity" class="form-control" value="1" min="1" required>
            <p class="text-sm text-gray-500 mt-1">Bu oda tipinden kaç adet oluşturulacak?</p>
        </div>

        <div class="form-group">
            <label for="capacity">Kişi Sayısı (Kapasite)</label>
            <input type="number" id="capacity" name="capacity" class="form-control" value="2" min="1" required>
            <p class="text-sm text-gray-500 mt-1">Her bir ünitenin maksimum konaklama kapasitesi.</p>
        </div>

        <div class="form-group">
            <label for="size_sqm">Metrekare (m²)</label>
            <input type="number" id="size_sqm" name="size_sqm" class="form-control">
        </div>

        <div class="form-group">
            <label for="bedroom_count">Yatak Odası Sayısı</label>
            <input type="number" id="bedroom_count" name="bedroom_count" class="form-control" value="1" min="0">
        </div>

        <div class="form-group">
            <label for="bathroom_count">Banyo Sayısı</label>
            <input type="number" id="bathroom_count" name="bathroom_count" class="form-control" value="1" min="0">
        </div>

        <div class="md:col-span-2 form-group">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="has_kitchen" value="1"><span>Mutfak Var</span>
            </label>
        </div>
    </div>
</div>


    <div class="card">
        <h3 class="font-bold text-lg mb-4">Özellikler & Donanım</h3>
        <?php foreach($categorized_features as $category_id => $category): ?>
            <div class="mb-6 last:mb-0">
                <h4 class="font-semibold mb-3 pb-2 border-b"><?php echo htmlspecialchars($category['category_name']); ?></h4>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php if(!empty($category['features'])) foreach($category['features'] as $feature): ?>
                    <label class="flex items-center gap-2 p-2 border rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="features[<?php echo $feature['id']; ?>]">
                        <i data-feather="<?php echo htmlspecialchars($feature['icon']); ?>" class="w-4 h-4 text-gray-600"></i>
                        <span><?php echo htmlspecialchars($feature['name']); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="text-right mt-6">
        <button type="submit" class="btn btn-primary btn-lg">Oda Tipi Oluştur ve Devam Et</button>
    </div>
</form>

<script>
    feather.replace();
</script>