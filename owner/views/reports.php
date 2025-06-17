<?php
// owner/views/reports.php
// Kontrolcüden gelen değişkenler: $stats, $agent_performance, $room_type_performance, $start_date_filter, $end_date_filter
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/tr.js"></script>

<div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
    <h2 class="text-2xl font-bold">Performans Raporları</h2>
    <form method="GET" class="flex items-center gap-2">
        <input type="hidden" name="page" value="reports">
        <input type="text" name="start_date" id="start_date_filter" class="form-control" value="<?php echo $start_date_filter; ?>">
        <input type="text" name="end_date" id="end_date_filter" class="form-control" value="<?php echo $end_date_filter; ?>">
        <button type="submit" class="btn btn-primary"><i data-feather="filter"></i></button>
    </form>
</div>

<!-- Temel Metrikler -->
<div class="stats-grid mb-6">
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-blue-100"><i data-feather="hash" class="text-blue-600"></i></div><div><p class="stat-title">Toplam Rezervasyon</p><p class="stat-value"><?php echo number_format($stats['total_reservations'] ?? 0); ?></p></div></div></div>
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-green-100"><i data-feather="dollar-sign" class="text-green-600"></i></div><div><p class="stat-title">Toplam Ciro</p><p class="stat-value"><?php echo number_format($stats['total_revenue'] ?? 0, 2, ',', '.'); ?> ₺</p></div></div></div>
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-red-100"><i data-feather="percent" class="text-red-600"></i></div><div><p class="stat-title">Ödenen Komisyon</p><p class="stat-value"><?php echo number_format($stats['total_commission'] ?? 0, 2, ',', '.'); ?> ₺</p></div></div></div>
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-yellow-100"><i data-feather="trending-up" class="text-yellow-600"></i></div><div><p class="stat-title">Doluluk Oranı</p><p class="stat-value">%<?php echo number_format($stats['occupancy_rate'] ?? 0); ?></p></div></div></div>
</div>

<!-- Detaylı Raporlar -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Acente Performansı -->
    <div class="card mb-6">
        <h3 class="font-bold text-lg mb-4">Acente Performansı</h3>
        <div class="overflow-x-auto">
            <table class="data-table w-full">
                <thead><tr><th>Acente Adı</th><th>Rez. Sayısı</th><th>Ciro</th><th>Komisyon</th></tr></thead>
                <tbody>
                    <?php if(empty($agent_performance)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-gray-500">Bu tarih aralığında acente verisi bulunamadı.</td></tr>
                    <?php else: foreach($agent_performance as $agent): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($agent['agent_name']); ?></td>
                            <td><?php echo number_format($agent['reservation_count']); ?></td>
                            <td class="font-semibold"><?php echo number_format($agent['total_revenue'], 2, ',', '.'); ?> ₺</td>
                            <td class="text-red-600"><?php echo number_format($agent['total_commission'], 2, ',', '.'); ?> ₺</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Oda Tipi Performansı -->
    <div class="card">
        <h3 class="font-bold text-lg mb-4">Oda Tipi Performansı</h3>
         <div class="overflow-x-auto">
            <table class="data-table w-full">
                <thead><tr><th>Oda Tipi</th><th>Tesis</th><th>Rez. Sayısı</th><th>Ciro</th></tr></thead>
                <tbody>
                     <?php if(empty($room_type_performance)): ?>
                        <tr><td colspan="4" class="text-center py-4 text-gray-500">Bu tarih aralığında oda tipi verisi bulunamadı.</td></tr>
                    <?php else: foreach($room_type_performance as $room): ?>
                        <tr>
                            <td class="font-medium"><?php echo htmlspecialchars($room['unit_type_name']); ?></td>
                            <td class="text-sm text-gray-600"><?php echo htmlspecialchars($room['property_name']); ?></td>
                            <td><?php echo number_format($room['reservation_count']); ?></td>
                            <td class="font-semibold"><?php echo number_format($room['total_revenue'], 2, ',', '.'); ?> ₺</td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
        
        const fpConfig = {
            dateFormat: "Y-m-d",
            locale: "tr"
        };
        flatpickr("#start_date_filter", fpConfig);
        flatpickr("#end_date_filter", fpConfig);
    });
</script>
