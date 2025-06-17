<?php
$property_class = new Property();
$owner_id = $_SESSION['user_id'];
$properties = $property_class->getAll($owner_id);

$status = $_GET['status'] ?? '';
$message = '';
if ($status === 'deleted') {
    $message = 'Tesis başarıyla silindi.';
}
?>

<?php if ($message): ?>
<div class="alert alert-success mb-6">
    <i data-feather="check-circle"></i>
    <span><?php echo htmlspecialchars($message); ?></span>
</div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Tesislerim</h2>
    <a href="index.php?page=property-edit" class="btn btn-primary">
        <i data-feather="plus"></i>
        <span>Yeni Tesis Ekle</span>
    </a>
</div>

<?php if (empty($properties)): ?>
    <div class="card text-center py-12">
        <i data-feather="frown" class="mx-auto h-12 w-12 text-gray-400"></i>
        <h3 class="mt-2 text-lg font-medium text-gray-900">Henüz tesis eklemediniz.</h3>
        <p class="mt-1 text-sm text-gray-500">Başlamak için "Yeni Tesis Ekle" butonunu kullanabilirsiniz.</p>
    </div>
<?php else: ?>
    <div class="properties-grid">
        <?php foreach ($properties as $prop): ?>
        <div class="property-card">
            <div class="property-card-image">
                <?php if (!empty($prop['cover_photo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($prop['cover_photo_path']); ?>" alt="<?php echo htmlspecialchars($prop['name']); ?>">
                <?php else: ?>
                    <div class="placeholder">
                        <i data-feather="image" class="w-12 h-12"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="property-card-content">
                <div class="flex-grow">
                    <?php if (!empty($prop['property_types'])): ?>
                        <p class="text-xs font-bold text-primary-600 uppercase tracking-wider mb-1"><?php echo htmlspecialchars($prop['property_types']); ?></p>
                    <?php endif; ?>

                    <h3 class="font-bold text-xl text-gray-900"><?php echo htmlspecialchars($prop['name']); ?></h3>

                    <div class="space-y-3 mt-4 text-sm text-gray-600">
                        <div class="flex items-center gap-2">
                            <i data-feather="map-pin" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                            <span><?php echo htmlspecialchars($prop['address_district'] ?? ''); ?>, <?php echo htmlspecialchars($prop['address_province'] ?? 'Adres Belirtilmemiş'); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-feather="grid" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                            <span><strong><?php echo $prop['unit_count']; ?></strong> adet ünite</span>
                        </div>
                         <div class="flex items-center gap-2">
                            <i data-feather="percent" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
                            <span>Komisyon: <strong>%<?php echo htmlspecialchars(number_format($prop['commission_rate'], 2)); ?></strong></span>
                        </div>
                                <?php if (!empty($prop['amenities_list'])): ?>
            <div class="flex items-center gap-2 flex-wrap pt-3 mt-3 border-t border-gray-100">
                <?php
                $amenities = explode('|', $prop['amenities_list']);
                foreach ($amenities as $amenity_str):
                    // Hata kontrolü için explode sonucunu kontrol edelim
                    $parts = explode(':', $amenity_str, 2);
                    $name = $parts[0] ?? '';
                    $icon = $parts[1] ?? 'circle';
                ?>
                    <span class="flex items-center gap-1.5 bg-gray-100 text-gray-800 px-2 py-1 rounded-full text-xs font-medium">
                        <i data-feather="<?php echo htmlspecialchars($icon); ?>" class="w-3.5 h-3.5"></i>
                        <span><?php echo htmlspecialchars($name); ?></span>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
                    </div>
                </div>

                <div class="property-card-actions flex justify-end gap-2">
    
    <a href="index.php?page=unit-types&property_id=<?php echo $prop['id']; ?>" class="btn btn-secondary">
        <i data-feather="key" class="w-4 h-4"></i>
        <span>Oda Tipleri</span>
    </a>

    <a href="index.php?page=property-edit&id=<?php echo $prop['id']; ?>" class="btn btn-primary">
        <i data-feather="edit-3" class="w-4 h-4"></i>
        <span>Tesis Ayarları</span>
    </a>
                       <form method="POST" action="index.php?page=properties" onsubmit="return confirm('Bu tesisi ve tüm bağlı verilerini kalıcı olarak silmek istediğinize emin misiniz?');">
                    <input type="hidden" name="action" value="delete_property">
                    <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                    <button type="submit" class="btn btn-secondary hover:bg-red-100 hover:text-red-600">
                        <i data-feather="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
</div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
    feather.replace();
</script>