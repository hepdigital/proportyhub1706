<?php
// owner/views/dashboard.php
// Kontrolcüden gelen değişkenler: $dashboard_stats, $upcoming_reservations, $revenue_chart_data, $recent_notifications

// Türkçe ay adları için diziler
$turkish_months_full = [
    'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart', 'April' => 'Nisan',
    'May' => 'Mayıs', 'June' => 'Haziran', 'July' => 'Temmuz', 'August' => 'Ağustos',
    'September' => 'Eylül', 'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
];
// YENİ EKLENDİ: Türkçe ay kısaltmaları
$turkish_months_short = [
    'Jan' => 'Oca', 'Feb' => 'Şub', 'Mar' => 'Mar', 'Apr' => 'Nis', 'May' => 'May', 'Jun' => 'Haz',
    'Jul' => 'Tem', 'Aug' => 'Ağu', 'Sep' => 'Eyl', 'Oct' => 'Eki', 'Nov' => 'Kas', 'Dec' => 'Ara'
];

$current_month_english = date('F');
$current_month_turkish = $turkish_months_full[$current_month_english];
?>

<h2 class="text-2xl font-bold mb-6"><?php echo $current_month_turkish . ' ' . date('Y'); ?> Ayı İstatistikleri</h2>

<!-- Temel Metrikler -->
<div class="stats-grid mb-8">
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-blue-100"><i data-feather="dollar-sign" class="text-blue-600"></i></div><div><p class="stat-title">Bu Ayki Ciro</p><p class="stat-value"><?php echo number_format($dashboard_stats['total_revenue'] ?? 0, 2, ',', '.'); ?> ₺</p></div></div></div>
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-green-100"><i data-feather="check-circle" class="text-green-600"></i></div><div><p class="stat-title">Net Kazanç</p><p class="stat-value"><?php echo number_format($dashboard_stats['net_income'] ?? 0, 2, ',', '.'); ?> ₺</p></div></div></div>
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-purple-100"><i data-feather="hash" class="text-purple-600"></i></div><div><p class="stat-title">Rezervasyon Sayısı</p><p class="stat-value"><?php echo number_format($dashboard_stats['total_reservations'] ?? 0); ?></p></div></div></div>
    <div class="card"><div class="flex items-center"><div class="icon-wrapper bg-yellow-100"><i data-feather="trending-up" class="text-yellow-600"></i></div><div><p class="stat-title">Aylık Doluluk</p><p class="stat-value">%<?php echo number_format($dashboard_stats['occupancy_rate'] ?? 0); ?></p></div></div></div>
</div>

<!-- Detaylı Analiz Bölümü -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Gelir Grafiği (Sol Taraf) -->
    <div class="lg:col-span-2 card mb-6 mt-6">
        <h3 class="font-bold text-lg mb-4">Son 6 Aylık Net Kazanç Grafiği</h3>
        <div class="h-80">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <!-- Hızlı Aksiyonlar (Sağ Taraf) -->
    <div class="space-y-8">
        <!-- Yaklaşan Rezervasyonlar -->
        <div class="card mb-6">
            <h3 class="font-bold text-lg mb-4">Yaklaşan Rezervasyonlar</h3>
            <ul class="space-y-4">
                <?php if(empty($upcoming_reservations)): ?>
                    <li class="text-center py-4 text-gray-500 text-sm">Yaklaşan rezervasyon bulunmuyor.</li>
                <?php else: foreach($upcoming_reservations as $res): ?>
                    <li class="flex items-center">
                        <div class="flex-shrink-0 bg-gray-100 text-primary-600 rounded-lg w-12 h-12 flex flex-col items-center justify-center font-bold mr-4">
                            <!-- DÜZENLENDİ: İngilizce ay kısaltması Türkçe'ye çevrildi -->
                            <span class="text-xs">
                                <?php 
                                    $month_english_short = date('M', strtotime($res['start_date']));
                                    echo $turkish_months_short[$month_english_short] ?? $month_english_short;
                                ?>
                            </span>
                            <span class="text-lg leading-none"><?php echo date('d', strtotime($res['start_date'])); ?></span>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($res['guest_name']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($res['unit_type_name']); ?></p>
                        </div>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
        
        <!-- Son Bildirimler -->
        <div class="card mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-bold text-lg">Son Bildirimler</h3>
                <a href="index.php?page=notifications" class="btn btn-secondary btn-sm">Tümünü Gör</a>
            </div>
             <ul class="space-y-4">
                 <?php if(empty($recent_notifications)): ?>
                    <li class="text-center py-4 text-gray-500 text-sm">Yeni bildirim bulunmuyor.</li>
                <?php else: foreach(array_slice($recent_notifications, 0, 3) as $notif): ?>
                     <li class="flex items-start">
                         <div class="flex-shrink-0 bg-blue-100 text-blue-600 rounded-full w-8 h-8 flex items-center justify-center mr-3 mt-1">
                            <i data-feather="bell" class="w-4 h-4"></i>
                         </div>
                         <div>
                            <p class="text-sm text-gray-700"><?php echo htmlspecialchars($notif['message']); ?></p>
                         </div>
                     </li>
                <?php endforeach; endif; ?>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    feather.replace();
    
    const revenueChartCtx = document.getElementById('revenueChart');
    if (revenueChartCtx) {
        const chartData = <?php echo json_encode($revenue_chart_data); ?>;
        
        // DÜZENLENDİ: Grafik etiketlerini Türkçeleştirmek için yeni kod bloğu
        const turkishMonthsShort = {
            'Jan': 'Oca', 'Feb': 'Şub', 'Mar': 'Mar', 'Apr': 'Nis', 'May': 'May', 'Jun': 'Haz',
            'Jul': 'Tem', 'Aug': 'Ağu', 'Sep': 'Eyl', 'Oct': 'Eki', 'Nov': 'Kas', 'Dec': 'Ara'
        };
        const translatedLabels = chartData.labels.map(label => {
            const parts = label.split(' '); // "May 2025" -> ["May", "2025"]
            const monthAbbr = parts[0];
            const year = parts[1];
            return (turkishMonthsShort[monthAbbr] || monthAbbr) + ' ' + year;
        });
        
        const ctx = revenueChartCtx.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(59, 130, 246, 0.4)');
        gradient.addColorStop(1, 'rgba(59, 130, 246, 0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: translatedLabels, // DÜZENLENDİ: Çevrilmiş etiketler kullanıldı
                datasets: [{
                    label: 'Net Kazanç',
                    data: chartData.data,
                    backgroundColor: gradient,
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(59, 130, 246, 1)',
                    pointBorderColor: '#fff',
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(59, 130, 246, 1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { 
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return value.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY', minimumFractionDigits: 0 }); }
                        }
                    }
                },
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                         callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) { label += ': '; }
                                if (context.parsed.y !== null) {
                                    label += new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
