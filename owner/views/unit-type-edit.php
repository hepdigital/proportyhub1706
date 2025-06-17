<?php
// Kontrolcü (owner/index.php) tarafından hazırlanan ve bu görünümün ihtiyaç duyduğu tüm değişkenler:
// $unit_type_data, $property_id, $edit_mode, $categorized_features, $selected_features, $photos

// Başarı/hata mesajlarını hazırlama
$status = $_GET['status'] ?? '';
$message = '';
$message_type = 'success';

if ($status === 'type_updated') $message = 'Oda tipi başarıyla güncellendi.';
if ($status === 'media_updated') $message = 'Medya dosyaları başarıyla güncellendi.';

?>
<style>
    /* Sekmeli arayüz için basit stiller */
    .tabs { display: flex; border-bottom: 2px solid var(--gray-200); margin-bottom: 1.5rem; }
    .tab-link { padding: 0.75rem 1.5rem; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -2px; color: var(--gray-500); font-weight: 600; transition: all 0.2s; }
    .tab-link:hover { color: var(--primary-600); }
    .tab-link.active { color: var(--primary-600); border-color: var(--primary-600); }
    .tab-content { display: none; }
    .tab-content.active { display: block; animation: fadeIn 0.4s; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Galeri Stilleri */
    .gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 1rem; }
    .gallery-item { position: relative; border: 3px solid transparent; border-radius: 0.75rem; overflow: hidden; aspect-ratio: 1 / 1; cursor: grab; background-color: var(--gray-100); }
    .gallery-item:active { cursor: grabbing; }
    .gallery-item.is-cover { border-color: var(--primary-500); box-shadow: 0 0 15px -2px var(--primary-500); }
    .gallery-item img { width: 100%; height: 100%; object-fit: cover; }
    .gallery-item-actions { position: absolute; top: 0; right: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.6), transparent); padding: 0.5rem; display: flex; gap: 0.5rem; border-bottom-left-radius: 0.5rem; opacity: 0; visibility: hidden; transition: all 0.2s; }
    .gallery-item:hover .gallery-item-actions { opacity: 1; visibility: visible; }
    .gallery-item-actions .btn { padding: 0.4rem; height: auto; width: auto; line-height: 1; color: white; background: rgba(255,255,255,0.2); backdrop-filter: blur(2px); border-radius: 50%; }
    .gallery-item-actions .btn:hover { background: rgba(255,255,255,0.3); }
    .gallery-item.sortable-ghost { opacity: 0.4; }
</style>

<div class="flex justify-between items-center mb-4">
    <div>
        <h2 class="text-2xl font-bold">Oda Tipi Yönetimi</h2>
        <p class="text-gray-500 mt-1">Oda Tipi: <strong><?php echo htmlspecialchars($unit_type_data['name']); ?></strong></p>
    </div>
    <a href="index.php?page=unit-types&property_id=<?php echo $property_id; ?>" class="btn btn-secondary"><i data-feather="arrow-left" class="w-4 h-4"></i><span>Geri Dön</span></a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> mb-6">
    <i data-feather="check-circle"></i><span><?php echo htmlspecialchars($message); ?></span>
</div>
<?php endif; ?>

<div class="tabs">
    <div class="tab-link active" onclick="openTab(event, 'tab-basic')">Temel Bilgiler</div>
    <div class="tab-link" onclick="openTab(event, 'tab-units')">Fiziksel Birimler</div> <div class="tab-link" onclick="openTab(event, 'tab-features')">Özellikler & Donanım</div>
    <?php if ($edit_mode): ?>
    <div class="tab-link" onclick="openTab(event, 'tab-media')">Medya & Fotoğraflar</div>
    <?php endif; ?>
</div>

<form method="POST" action="index.php?page=unit-type-edit&id=<?php echo $unit_type_data['id']; ?>" id="unit-type-form">
    <input type="hidden" name="action" value="update_unit_type">
    <input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>">
    <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">

    <div id="tab-basic" class="tab-content active">
    <div class="card">
        <div class="grid md:grid-cols-2 gap-6">
            <div class="form-group"><label for="name">Oda Tipi Adı</label><input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($unit_type_data['name'] ?? ''); ?>" required></div>
            
            <div class="form-group">
                <label for="capacity">Kişi Sayısı (Kapasite)</label>
                <input type="number" id="capacity" name="capacity" class="form-control" value="<?php echo htmlspecialchars($unit_type_data['capacity'] ?? 2); ?>" min="1" required>
            </div>

            <div class="form-group"><label for="size_sqm">Metrekare (m²)</label><input type="number" id="size_sqm" name="size_sqm" class="form-control" value="<?php echo htmlspecialchars($unit_type_data['size_sqm'] ?? ''); ?>"></div>
            <div class="form-group"><label for="bedroom_count">Yatak Odası Sayısı</label><input type="number" id="bedroom_count" name="bedroom_count" class="form-control" value="<?php echo htmlspecialchars($unit_type_data['bedroom_count'] ?? 1); ?>" min="0"></div>
            <div class="form-group"><label for="bathroom_count">Banyo Sayısı</label><input type="number" id="bathroom_count" name="bathroom_count" class="form-control" value="<?php echo htmlspecialchars($unit_type_data['bathroom_count'] ?? 1); ?>" min="0"></div>
            <div class="md:col-span-2 form-group"><label class="flex items-center gap-2 cursor-pointer"><input type="checkbox" name="has_kitchen" value="1" <?php echo !empty($unit_type_data['has_kitchen']) ? 'checked' : ''; ?>><span>Mutfak Var</span></label></div>
        </div>
    </div>
</div>
<div id="tab-units" class="tab-content">
    <div class="card">
        <h3 class="font-bold text-lg mb-4">Fiziksel Birimler (Oda Numaraları)</h3>
        <p class="text-sm text-gray-500 mb-6">Bu oda tipine ait fiziksel oda ve bungalovları buradan yönetebilirsiniz. Müsaitlik takvimi bu birimler üzerinden çalışır.</p>

        <div class="space-y-3 mb-6">
            <?php if (empty($units)): ?>
                <p class="text-gray-500">Bu oda tipine bağlı fiziksel birim bulunmuyor.</p>
            <?php else: ?>
                <?php foreach($units as $unit): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border">
                        <span class="font-medium"><?php echo htmlspecialchars($unit['name']); ?></span>
                        <form method="POST" action="index.php?page=unit-type-edit&id=<?php echo $unit_type_data['id']; ?>" onsubmit="return confirm('Bu birimi kalıcı olarak silmek istediğinize emin misiniz?');">
                            <input type="hidden" name="action" value="delete_single_unit">
                            <input type="hidden" name="unit_id" value="<?php echo $unit['id']; ?>">
                            <input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>">
                            <button type="submit" class="btn btn-secondary p-2 h-8 w-8 hover:bg-red-100 hover:text-red-600">
                                <i data-feather="trash-2" class="w-4 h-4"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <hr>
        <form method="POST" action="index.php?page=unit-type-edit&id=<?php echo $unit_type_data['id']; ?>" class="mt-6">
            <input type="hidden" name="action" value="add_single_unit">
            <input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>">
            <h4 class="font-semibold mb-2">Yeni Birim Ekle</h4>
            <div class="flex items-end gap-4">
                <div class="form-group flex-grow mb-0">
                    <label for="new_unit_name" class="text-sm">Yeni Birim Adı / Numarası</label>
                    <input type="text" id="new_unit_name" name="new_unit_name" class="form-control" placeholder="Örn: Oda 101 veya Çam Bungalov" required>
                </div>
                <button type="submit" class="btn btn-secondary">Ekle</button>
            </div>
        </form>
    </div>
</div>
    <div id="tab-features" class="tab-content">
        <div class="card">
            <?php foreach($categorized_features as $category_id => $category): ?>
                <div class="mb-8">
                    <h4 class="font-bold text-lg mb-4 pb-2 border-b"><?php echo htmlspecialchars($category['category_name']); ?></h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <?php if(!empty($category['features'])) foreach($category['features'] as $feature): 
                            $feature_id = $feature['id'];
                            $is_checked = isset($selected_features[$feature_id]);
                        ?>
                        <label class="flex items-center gap-2 p-3 border rounded-lg hover:bg-gray-50 cursor-pointer">
                            <input type="checkbox" name="features[<?php echo $feature_id; ?>]" <?php echo $is_checked ? 'checked' : ''; ?>>
                            <i data-feather="<?php echo htmlspecialchars($feature['icon']); ?>" class="w-4 h-4 text-gray-600"></i>
                            <span><?php echo htmlspecialchars($feature['name']); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<div id="tab-media" class="tab-content">
    <div class="card">
        <h3 class="font-bold text-lg mb-4">Galeri Fotoğrafları</h3>
        <p class="text-sm text-gray-500 mb-4">Fotoğrafları sürükleyerek sıralayabilirsiniz. Yıldız ikonuna basarak kapak fotoğrafını belirleyebilirsiniz.</p>
        <div class="gallery-grid mb-6" id="gallery-container">
            <?php foreach($photos as $photo): ?>
                <div class="gallery-item <?php echo ($unit_type_data['cover_photo_id'] == $photo['id']) ? 'is-cover' : ''; ?>" data-id="<?php echo $photo['id']; ?>">
                    <img src="<?php echo htmlspecialchars($photo['photo_path']); ?>" alt="Galeri Fotoğrafı">
                    <div class="gallery-item-actions">
                        <button type="submit" form="set-cover-form-<?php echo $photo['id']; ?>" class="btn" title="Kapak Yap"><i data-feather="star"></i></button>
                        <button type="submit" form="delete-photo-form-<?php echo $photo['id']; ?>" class="btn hover:text-red-500" title="Sil"><i data-feather="trash-2"></i></button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <hr class="my-6">
        <h4 class="font-bold mb-2">Yeni Fotoğraflar Yükle</h4>
        <form method="POST" action="index.php?page=unit-type-edit&id=<?php echo $unit_type_data['id']; ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_gallery_photos">
            <input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>">
            <input type="file" name="gallery_photos[]" class="form-control" multiple accept="image/jpeg, image/png, image/webp" required>
            <p class="text-sm text-gray-500 mt-2">Birden fazla fotoğraf seçmek için Ctrl (Windows) veya Cmd (Mac) tuşunu basılı tutun.</p>
            <button type="submit" class="btn btn-secondary mt-2">Yükle</button>
        </form>
    </div>
</div>

<div class="text-right mt-6">
    <button type="submit" form="unit-type-form" class="btn btn-primary btn-lg">Değişiklikleri Kaydet</button>
</div>

<?php foreach($photos as $photo): ?>
    <form method="POST" action="index.php?page=unit-type-edit&id=<?php echo $unit_type_data['id']; ?>" id="set-cover-form-<?php echo $photo['id']; ?>"><input type="hidden" name="action" value="set_cover_photo"><input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>"><input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>"></form>
    <form method="POST" action="index.php?page=unit-type-edit&id=<?php echo $unit_type_data['id']; ?>" id="delete-photo-form-<?php echo $photo['id']; ?>" onsubmit="return confirm('Bu fotoğrafı silmek istediğinize emin misiniz?');"><input type="hidden" name="action" value="delete_photo"><input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>"><input type="hidden" name="photo_id" value="<?php echo $photo['id']; ?>"></form>
<?php endforeach; ?>

<script>
// Sekme geçiş fonksiyonu
function openTab(evt, tabName) {
    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    const targetTab = document.getElementById(tabName);
    if (targetTab) {
        targetTab.style.display = "block";
    }
    evt.currentTarget.classList.add("active");
}

document.addEventListener('DOMContentLoaded', function() {
    feather.replace();

    // Galeri sıralama fonksiyonu
    const galleryContainer = document.getElementById('gallery-container');
    if (galleryContainer && typeof Sortable !== 'undefined') {
        new Sortable(galleryContainer, {
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: function (evt) {
                const items = evt.to.children;
                const order = Array.from(items).map(item => item.dataset.id);
                
                // Sıralamayı sunucuya göndermek için AJAX (Fetch) kullanılır.
                // Bu özellik için index.php'ye 'update_photo_order' adında yeni bir action eklenmelidir.
                console.log("Yeni fotoğraf sıralaması:", order);
                // fetch('index.php', { method: 'POST', body: JSON.stringify({ action: 'update_photo_order', order: order }) });
            }
        });
    }

    // Sayfa URL'sinde ?tab=media varsa, doğrudan o sekmeyi aç
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'media') {
        document.querySelector('.tab-link[onclick*="tab-media"]').click();
    }
});
</script>