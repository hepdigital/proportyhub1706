<?php
// Veri çekme kodları
$property = new Property();
$owner_id = $_SESSION['user_id'];
$properties = $property->getAll($owner_id);
$total_properties = count($properties);
$unit = new Unit();
$total_units = 0;
if ($total_properties > 0) {
    foreach ($properties as $prop) {
        $total_units += count($unit->getByProperty($prop['id']));
    }
}
$db = Database::getInstance()->getConnection();
$log_stmt = $db->prepare("SELECT l.*, p.name as property_name FROM sync_logs l LEFT JOIN properties p ON l.property_id = p.id WHERE p.owner_id = :owner_id ORDER BY l.created_at DESC LIMIT 5");
$log_stmt->execute([':owner_id' => $owner_id]);
$recent_logs = $log_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="stats-grid mb-6">
    <!-- İstatistik Kartları (Yeniden Düzenlendi) -->
    <div class="card">
        <div class="flex items-center">
            <div class="icon-wrapper bg-primary-100">
                <i data-feather="briefcase" class="text-primary-600"></i>
            </div>
            <div>
                <p class="stat-title">Toplam Tesis</p>
                <p class="stat-value"><?php echo $total_properties; ?></p>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="flex items-center">
            <div class="icon-wrapper bg-green-100">
                <i data-feather="grid" class="text-green-600"></i>
            </div>
            <div>
                <p class="stat-title">Toplam Ünite</p>
                <p class="stat-value"><?php echo $total_units; ?></p>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="flex items-center">
            <div class="icon-wrapper bg-yellow-100">
                <i data-feather="bar-chart-2" class="text-yellow-600"></i>
            </div>
            <div>
                <p class="stat-title">Doluluk (30 Gün)</p>
                <p class="stat-value" id="occupancy-rate">- %</p>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="flex items-center">
            <div class="icon-wrapper bg-red-100">
                <i data-feather="alert-triangle" class="text-red-600"></i>
            </div>
            <div>
                <p class="stat-title">Hata Kaydı</p>
                <p class="stat-value" id="error-count">0</p>
            </div>
        </div>
    </div>
</div>

<div class="grid gap-6 grid-cols-1 lg:grid-cols-3">
    <!-- Doluluk Oranı Grafiği -->
    <div class="card lg:col-span-2">
        <h3 class="font-bold mb-4">Yaklaşan 7 Günün Doluluk Grafiği</h3>
        <div style="height: 300px;">
            <canvas id="occupancyChart"></canvas>
        </div>
    </div>

    <!-- Son Aktiviteler -->
    <div class="card">
        <h3 class="font-bold mb-4">Son Aktiviteler</h3>
        <ul class="space-y-4">
            <?php if (empty($recent_logs)): ?>
                <li class="text-gray-500 text-sm">Görüntülenecek aktivite bulunamadı.</li>
            <?php else: ?>
                <?php foreach ($recent_logs as $log): ?>
                    <li class="flex items-start">
                        <div class="bg-gray-100 p-2 rounded-full mr-3 mt-1">
                             <i data-feather="<?php echo ($log['status'] === 'success' ? 'check-circle' : 'alert-circle'); ?>" 
                                class="w-4 h-4 <?php echo ($log['status'] === 'success' ? 'text-green-600' : 'text-red-600'); ?>"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($log['property_name'] ?? 'Genel'); ?> - <?php echo htmlspecialchars($log['sync_type']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($log['message'], 0, 50)); ?>...</p>
                        </div>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    feather.replace();
    
    fetch('../api/owner/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('occupancy-rate').textContent = data.stats.occupancy_rate_30_days + ' %';
                document.getElementById('error-count').textContent = data.stats.error_log_count;
                renderOccupancyChart(data.chart_data);
            }
        })
        .catch(error => console.error('Dashboard verileri yüklenemedi:', error));

    function renderOccupancyChart(chartData) {
        const ctx = document.getElementById('occupancyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartData.labels,
                datasets: [{
                    label: 'Dolu Ünite Sayısı',
                    data: chartData.data,
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1 } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }
});
</script>
