<?php
// Bu dosyanın en başındaki tüm PHP işlem kodları ana index.php'ye taşındı.
$property = new Property();
$properties = $property->getAll();
?>

<div class="properties-page">
    <div class="page-header">
        <h2>Tesisler</h2>
        <a href="index.php?page=property-add" class="btn btn-primary">Yeni Tesis Ekle</a>
    </div>
    
    <div class="properties-grid">
        <?php foreach ($properties as $prop): ?>
        <div class="property-card" data-id="<?php echo $prop['id']; ?>">
            <h3><?php echo htmlspecialchars($prop['name']); ?></h3>
            <div class="property-info">
                <p><strong>Tip:</strong> <?php echo $prop['sync_type']; ?></p>
                <p><strong>Durum:</strong> 
                    <span class="status status-<?php echo $prop['status'] ? 'success' : 'error'; ?>">
                        <?php echo $prop['status'] ? 'Aktif' : 'Pasif'; ?>
                    </span>
                </p>
                <?php if ($prop['wp_site_url']): ?>
                <p><strong>Site:</strong> <a href="<?php echo $prop['wp_site_url']; ?>" target="_blank">Ziyaret Et</a></p>
                <?php endif; ?>
            </div>
            <div class="property-actions">
                <button class="btn btn-sm btn-sync" onclick="syncProperty(<?php echo $prop['id']; ?>)">Senkronize Et</button>
                <a href="index.php?page=property-add&id=<?php echo $prop['id']; ?>" class="btn btn-sm">Düzenle</a>
                <a href="index.php?page=properties&action=delete&id=<?php echo $prop['id']; ?>" 
                   class="btn btn-sm btn-danger" 
                   onclick="return confirm('Bu tesisi silmek istediğinize emin misiniz?')">Sil</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>