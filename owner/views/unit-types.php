<?php
// owner/views/unit-types.php

// Kontrolcü (owner/index.php) tarafından hazırlanan değişkenler burada kullanılır:
// $property_id, $property_data, $unit_types

$status = $_GET['status'] ?? '';
$message = '';
$message_type = 'success'; // Varsayılan mesaj tipi

if ($status === 'type_created') $message = 'Oda tipi başarıyla oluşturuldu.';
if ($status === 'type_deleted') $message = 'Oda tipi başarıyla silindi.';
if ($status === 'type_updated') $message = 'Oda tipi başarıyla güncellendi.';

?>
<style>
    /* Bu sayfaya özel, özellik rozetleri için küçük bir stil */
    .feature-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem; /* 6px */
        background-color: var(--gray-100);
        color: var(--gray-700);
        padding: 0.25rem 0.625rem; /* 4px 10px */
        border-radius: 9999px;
        font-size: 0.8rem; /* 13px */
        font-weight: 500;
        line-height: 1;
    }
</style>

<?php if ($message): ?>
<div class="alert alert-<?php echo $message_type; ?> mb-6">
    <i data-feather="check-circle"></i>
    <span><?php echo htmlspecialchars($message); ?></span>
</div>
<?php endif; ?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold">Oda Tipi Yönetimi</h2>
        <p class="text-gray-500">Tesis: <strong><?php echo htmlspecialchars($property_data['name']); ?></strong></p>
    </div>
    <div class="flex items-center gap-2">
         <a href="index.php?page=properties" class="btn btn-secondary">
            <i data-feather="arrow-left" class="w-4 h-4"></i>
            <span>Tesislerime Dön</span>
        </a>
        <a href="index.php?page=unit-type-add&property_id=<?php echo $property_id; ?>" class="btn btn-primary">
            <i data-feather="plus" class="w-4 h-4"></i>
            <span>Yeni Oda Tipi Ekle</span>
        </a>
    </div>
</div>

<div class="space-y-4">
    <?php if (empty($unit_types)): ?>
        <div class="card text-center py-12">
            <i data-feather="package" class="mx-auto h-12 w-12 text-gray-400"></i>
            <h3 class="mt-2 text-lg font-medium text-gray-900">Bu tesise ait oda tipi bulunamadı.</h3>
            <p class="mt-1 text-sm text-gray-500">Başlamak için sağ üstteki "Yeni Oda Tipi Ekle" butonunu kullanabilirsiniz.</p>
        </div>
    <?php else: ?>
        <?php foreach($unit_types as $type): ?>
        <div class="card flex flex-col sm:flex-row items-center gap-5 p-4 mb-6">
            <div class="w-32 h-32 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
                <?php if (!empty($type['cover_photo_path'])): ?>
                    <img src="<?php echo htmlspecialchars($type['cover_photo_path']); ?>" alt="<?php echo htmlspecialchars($type['name']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400"><i data-feather="camera-off" class="w-10 h-10"></i></div>
                <?php endif; ?>
            </div>

            <div class="flex-grow text-center sm:text-left">
                <h3 class="font-bold text-lg text-gray-900"><?php echo htmlspecialchars($type['name']); ?></h3>
                
                <div class="flex flex-wrap justify-center sm:justify-start items-center gap-x-4 gap-y-1 text-sm text-gray-500 mt-2">
                    <span class="flex items-center gap-1.5"><i data-feather="users" class="w-4 h-4"></i><?php echo htmlspecialchars($type['capacity']); ?> Kişi</span>
                    <span class="flex items-center gap-1.5"><i data-feather="package" class="w-4 h-4"></i><?php echo htmlspecialchars($type['unit_count']); ?> Adet Birim</span>
                    <span class="flex items-center gap-1.5"><i data-feather="dollar-sign" class="w-4 h-4"></i><strong><?php echo htmlspecialchars(number_format($type['base_price'], 0)); ?> ₺</strong> / geceden başlayan</span>
                </div>
                
                <?php if(!empty($type['main_features'])): ?>
                <div class="flex flex-wrap justify-center sm:justify-start items-center gap-2 mt-3">
                    <?php
                        // Özellikleri virgülle ayırıp her birini rozet olarak gösterelim
                        $features = explode(',', $type['main_features']);
                        foreach($features as $feature_name):
                    ?>
                    <span class="feature-badge">
                        <i data-feather="check-circle" class="w-3.5 h-3.5 text-primary-600"></i>
                        <span><?php echo htmlspecialchars(trim($feature_name)); ?></span>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="w-full sm:w-auto flex items-center justify-center sm:justify-end gap-2 mt-4 sm:mt-0 flex-shrink-0">
                <a href="index.php?page=pricing-rules&unit_type_id=<?php echo $type['id']; ?>" class="btn btn-primary">
                    <i data-feather="dollar-sign" class="w-4 h-4"></i><span>Fiyatlar</span>
                </a>
                <a href="index.php?page=unit-type-edit&id=<?php echo $type['id']; ?>" class="btn btn-secondary">
                    <i data-feather="edit" class="w-4 h-4"></i><span>Düzenle</span>
                </a>
                <form method="POST" action="index.php?page=unit-types&property_id=<?php echo $property_id; ?>" onsubmit="return confirm('Bu oda tipini ve bağlı tüm birimleri kalıcı olarak silmek istediğinize emin misiniz? Bu işlem geri alınamaz.');">
                    <input type="hidden" name="action" value="delete_unit_type">
                    <input type="hidden" name="unit_type_id" value="<?php echo $type['id']; ?>">
                    <button type="submit" class="btn btn-secondary hover:bg-red-100 hover:text-red-600">
                        <i data-feather="trash-2" class="w-4 h-4"></i>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    feather.replace();
</script>