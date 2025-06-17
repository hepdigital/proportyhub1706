<?php
$property = new Property();
$unit = new Unit();
$calendar = new Calendar();

$total_properties = count($property->getAll());
$active_properties = count($property->getAll(1));

// Son senkronizasyon logları
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SELECT * FROM sync_logs ORDER BY created_at DESC LIMIT 10");
$recent_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard">
    <h2>Dashboard</h2>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $total_properties; ?></h3>
            <p>Toplam Tesis</p>
        </div>
        <div class="stat-card">
            <h3><?php echo $active_properties; ?></h3>
            <p>Aktif Tesis</p>
        </div>
        <div class="stat-card">
            <h3 id="total-units">-</h3>
            <p>Toplam Ünit</p>
        </div>
        <div class="stat-card">
            <h3 id="sync-today">-</h3>
            <p>Bugünkü Senkronizasyon</p>
        </div>
    </div>
    
    <div class="recent-activity">
        <h3>Son Senkronizasyon Aktiviteleri</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tesis</th>
                    <th>Tip</th>
                    <th>Durum</th>
                    <th>Mesaj</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_logs as $log): ?>
                <tr>
                    <td><?php echo formatDate($log['created_at']); ?></td>
                    <td>
                        <?php 
                        if ($log['property_id']) {
                            $prop = $property->getById($log['property_id']);
                            echo $prop['name'] ?? 'Bilinmiyor';
                        }
                        ?>
                    </td>
                    <td><?php echo $log['sync_type']; ?></td>
                    <td><span class="status status-<?php echo $log['status']; ?>"><?php echo $log['status']; ?></span></td>
                    <td><?php echo $log['message']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>