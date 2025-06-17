<?php
// owner/views/pricing-rules.php (NİHAİ VERSİYON)

// Kontrolcüden gelen değişkenler: $unit_type_data, $pricing_rules, $property_id
$status = $_GET['status'] ?? '';
$message = '';
if ($status === 'defaults_updated') $message = 'Varsayılan fiyatlar başarıyla güncellendi.';
if ($status === 'rule_created') $message = 'Yeni fiyat kuralı başarıyla eklendi.';
if ($status === 'rule_deleted') $message = 'Fiyat kuralı başarıyla silindi.';

?>

<?php if ($message): ?>
<div class="alert alert-success mb-6"><i data-feather="check-circle"></i><span><?php echo htmlspecialchars($message); ?></span></div>
<?php endif; ?>


<div class="flex justify-between items-center mb-6">
    <div>
        <h2 class="text-2xl font-bold">Fiyatlandırma Yönetimi</h2>
        <p class="text-gray-500">Oda Tipi: <strong><?php echo htmlspecialchars($unit_type_data['name']); ?></strong></p>
    </div>
    <a href="index.php?page=unit-types&property_id=<?php echo $property_id; ?>" class="btn btn-secondary">
        <i data-feather="arrow-left" class="w-4 h-4"></i>
        <span>Oda Tiplerine Dön</span>
    </a>
</div>
<div class="card mb-8">
    <h3 class="font-bold text-lg mb-4">Fiyat Önizleme Takvimi</h3>
    <p class="text-sm text-gray-500 mb-4">Fiyatı değiştirmek istediğiniz günün üzerine tıklayın.</p>
    <div id="price-calendar"></div>
</div>

<div class="card">
    </div>


<div id="price-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="card w-full max-w-sm animate-fade-in-up">
        <h3 id="modal-title" class="text-xl font-bold mb-4">Fiyat Güncelle</h3>
        <form id="price-form">
            <input type="hidden" name="action" value="update_single_day_price">
            <input type="hidden" name="unit_type_id" value="<?php echo htmlspecialchars($unit_type_data['id']); ?>">
            <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($property_id); ?>">
            <input type="hidden" name="date" id="form-date">

            <div class="form-group">
                <label for="form-price">Yeni Gecelik Fiyat (₺)</label>
                <input type="number" name="price" id="form-price" class="form-control" step="0.01" required>
            </div>
            
            <div class="flex justify-end items-center mt-6 gap-2">
                <button type="button" id="close-modal-btn" class="btn btn-secondary">İptal</button>
                <button type="submit" id="save-price-btn" class="btn btn-primary">Kaydet</button>
            </div>
        </form>
    </div>
</div>
<div class="card mb-6">
    <h3 class="font-bold text-lg mb-4">Varsayılan Fiyat Ayarları</h3>
    <p class="text-sm text-gray-500 mb-4">Bu oda tipinin, herhangi bir özel kural uygulanmadığında geçerli olacak standart fiyatlarıdır.</p>
    <form method="POST" action="index.php?page=pricing-rules&unit_type_id=<?php echo $unit_type_data['id']; ?>">
        <input type="hidden" name="action" value="update_base_prices">
        <input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>">
        <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
        <div class="grid md:grid-cols-3 gap-6">
            <div class="form-group">
                <label for="base_price">Temel Fiyat (₺)</label>
                <input type="number" name="base_price" id="base_price" class="form-control" step="0.01" value="<?php echo htmlspecialchars($unit_type_data['base_price']); ?>" required>
            </div>
            <div class="form-group">
                <label for="price_per_extra_person">Ekstra Kişi Başı Fiyat (₺)</label>
                <input type="number" name="price_per_extra_person" id="price_per_extra_person" class="form-control" step="0.01" value="<?php echo htmlspecialchars($unit_type_data['price_per_extra_person']); ?>">
            </div>
            <div class="text-right self-end">
                <button type="submit" class="btn btn-secondary">Varsayılanları Kaydet</button>
            </div>
        </div>
    </form>
</div>

<div class="card mb-6">
    <h3 class="font-bold text-lg mb-4">Yeni Kural Ekle</h3>
    <form method="POST" action="index.php?page=pricing-rules&unit_type_id=<?php echo $unit_type_data['id']; ?>">
        <input type="hidden" name="action" value="create_pricing_rule">
        <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
        <input type="hidden" name="unit_type_id" value="<?php echo $unit_type_data['id']; ?>">

        <div class="grid md:grid-cols-3 gap-x-6 gap-y-4">
            <div class="form-group"><label for="name">Kural Adı</label><input type="text" name="name" id="name" class="form-control" placeholder="Örn: Yaz Sezonu" required></div>
            <div class="form-group"><label for="price">Gecelik Fiyat (₺)</label><input type="number" name="price" id="price" class="form-control" step="0.01" required></div>
            <div class="form-group"><label for="type">Kural Tipi</label><select name="type" id="rule-type-select" class="form-control"><option value="date_range">Tarih Aralığı</option><option value="day_of_week">Haftanın Günü</option></select></div>
            
            <div id="date-range-fields" class="md:col-span-3 grid md:grid-cols-3 gap-x-6 gap-y-4">
                <div class="form-group"><label for="start_date">Başlangıç Tarihi</label><input type="date" name="start_date" id="start_date" class="form-control"></div>
                <div class="form-group"><label for="end_date">Bitiş Tarihi</label><input type="date" name="end_date" id="end_date" class="form-control"></div>
            </div>

            <div id="day-of-week-fields" class="md:col-span-3 hidden">
                <label class="font-medium text-sm mb-2 block">Geçerli Günler</label>
                <div class="grid grid-cols-4 lg:grid-cols-7 gap-2">
                    <?php $days = [1=>'Pzt', 2=>'Sal', 3=>'Çar', 4=>'Per', 5=>'Cum', 6=>'Cmt', 7=>'Paz']; ?>
                    <?php foreach($days as $num => $day): ?>
                        <label class="flex items-center gap-2 p-2 border rounded-lg cursor-pointer"><input type="checkbox" name="days_of_week[]" value="<?php echo $num; ?>"><span><?php echo $day; ?></span></label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group"><label for="min_stay">Min. Gece</label><input type="number" name="min_stay" id="min_stay" class="form-control" value="1" min="1"></div>
            <div class="form-group"><label for="priority">Öncelik</label><input type="number" name="priority" id="priority" class="form-control" value="0"><small class="text-xs text-gray-500">Çakışan kurallarda büyük sayı önceliklidir.</small></div>
            <div class="md:col-span-3 text-right"><button type="submit" class="btn btn-primary">Kuralı Ekle</button></div>
        </div>
    </form>
</div>

<div class="card">
    <h3 class="font-bold text-lg mb-6">Mevcut Fiyat Kuralları</h3>
    <div class="overflow-x-auto">
        <table class="data-table w-full">
            <thead><tr><th>Kural Adı</th><th>Koşul</th><th>Fiyat</th><th>Min. Gece</th><th>Öncelik</th><th class="action-cell">İşlem</th></tr></thead>
            <tbody>
                <?php if(empty($pricing_rules)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-gray-500">Henüz fiyat kuralı eklenmemiş.</td></tr>
                <?php else: foreach($pricing_rules as $rule): ?>
                    <tr>
                        <td class="font-medium"><?php echo htmlspecialchars($rule['name']); ?></td>
                        <td><?php 
                            if($rule['type'] == 'date_range') echo htmlspecialchars($rule['start_date']) . ' - ' . htmlspecialchars($rule['end_date']);
                            else {
                                $rule_days = explode(',', $rule['days_of_week']);
                                $day_names = [];
                                foreach($rule_days as $day_num) $day_names[] = $days[$day_num];
                                echo implode(', ', $day_names);
                            }
                        ?></td>
                        <td class="font-bold"><?php echo number_format($rule['price'], 2); ?> ₺</td>
                        <td><?php echo $rule['min_stay']; ?></td>
                        <td><?php echo $rule['priority']; ?></td>
                        <td class="action-cell">
                            <form method="POST" onsubmit="return confirm('Bu kuralı silmek istediğinize emin misiniz?');">
                                <input type="hidden" name="action" value="delete_pricing_rule">
                                <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                <button type="submit" class="btn btn-secondary p-2 h-9 w-9 hover:bg-red-100 hover:text-red-600"><i data-feather="trash-2" class="w-4 h-4"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
feather.replace();
// Kural tipine göre form alanlarını göster/gizle
const ruleTypeSelect = document.getElementById('rule-type-select');
const dateRangeFields = document.getElementById('date-range-fields');
const dayOfWeekFields = document.getElementById('day-of-week-fields');

ruleTypeSelect.addEventListener('change', function() {
    if (this.value === 'date_range') {
        dateRangeFields.classList.remove('hidden');
        dayOfWeekFields.classList.add('hidden');
    } else {
        dateRangeFields.classList.add('hidden');
        dayOfWeekFields.classList.remove('hidden');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('price-calendar');
    const unitTypeId = <?php echo json_encode($unit_type_data['id']); ?>;
    let calendar; // Takvim nesnesini global olarak erişilebilir yapalım

    // --- Modal Yönetimi ---
    const modal = document.getElementById('price-modal');
    const priceForm = document.getElementById('price-form');
    const closeModalBtn = document.getElementById('close-modal-btn');
    
    function openModal(date, currentPrice) {
        document.getElementById('modal-title').textContent = `${date} Fiyatını Güncelle`;
        document.getElementById('form-date').value = date;
        document.getElementById('form-price').value = parseFloat(currentPrice) || 0;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    closeModalBtn.addEventListener('click', closeModal);

    priceForm.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);

        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(result => {
            if(result.success) {
                closeModal();
                calendar.refetchEvents(); // Takvimi yenile
            } else {
                alert('Hata: ' + (result.error || 'Fiyat güncellenemedi.'));
            }
        });
    });


    // --- FullCalendar Yapılandırması ---
    calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'tr',                   // DİL: Türkçe oldu
        initialView: 'dayGridMonth',
        firstDay: 1,                    // GÜN: Pazartesiden başlıyor
        contentHeight: 'auto',          // YÜKSEKLİK: İçeriğe göre otomatik ayarlanıyor
        
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,dayGridWeek'
        },
        buttonText: {
            today: 'Bugün',
            month: 'Ay',
            week: 'Hafta'
        },
        
        events: `/api/index.php?endpoint=get-daily-prices&unit_type_id=${unitTypeId}`,
        
        eventDidMount: function(info) {
            info.el.style.fontWeight = 'bold';
            info.el.style.fontSize = '0.9em';
        },
        
        // GÜNCELLEME: Bir güne tıklandığında modal'ı aç
        dateClick: function(info) {
            const clickedDate = info.dateStr;
            const eventsOnDay = calendar.getEvents().filter(event => {
                // FullCalendar'ın tarih karşılaştırması için tarihleri standartlaştır
                const eventStart = new Date(event.startStr).setHours(0,0,0,0);
                const clickDate = new Date(clickedDate).setHours(0,0,0,0);
                return eventStart === clickDate;
            });
            
            let currentPrice = '<?php echo $unit_type_data['base_price']; ?>'; // Varsayılan fiyat
            if (eventsOnDay.length > 0) {
                // Etkinlik başlığından fiyatı al (örn: "1500 ₺")
                currentPrice = eventsOnDay[0].title.replace(/[^0-9\.]/g, '');
            }
            
            openModal(clickedDate, currentPrice);
        }
    });

    calendar.render();
});
</script>