<?php
// PHP veri çekme kodları aynı kalır...
$property = new Property();
$owner_id = $_SESSION['user_id'];
$properties = $property->getAll($owner_id);
$selected_property_id = $_GET['property_id'] ?? ($properties[0]['id'] ?? null);
$units = [];
if ($selected_property_id) {
    $unit_class = new Unit();
    $units = $unit_class->getByProperty($selected_property_id);
}
?>
<!-- HATA ÇÖZÜMÜ: Flatpickr Türkçe dil dosyası eklendi -->
<script src="https://npmcdn.com/flatpickr/dist/l10n/tr.js"></script>

<div class="card">
    <div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
        <div class="flex items-center gap-4">
            <h3 class="text-lg font-bold">Takvim Yönetimi</h3>
            <button id="add-reservation-btn" class="btn btn-primary">
                <i data-feather="plus" class="w-4 h-4"></i>
                <span class="hidden md:inline">Rezervasyon Ekle</span>
            </button>
        </div>
        <div class="flex items-center gap-2">
            <label for="property-filter" class="text-sm font-medium">Tesis:</label>
            <select id="property-filter" class="form-control w-full md:w-auto">
                 <?php if (empty($properties)): ?>
                    <option>Henüz tesis eklenmemiş.</option>
                <?php else: ?>
                    <?php foreach ($properties as $prop): ?>
                        <option value="<?php echo $prop['id']; ?>" <?php if ($prop['id'] == $selected_property_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($prop['name']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
    
    <div id="calendar-container"></div>
</div>

<!-- Modal Penceresi -->
<div id="reservation-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="card w-full max-w-lg animate-fade-in-up">
        <h3 id="modal-title" class="text-xl font-bold mb-4"></h3>
        <form id="reservation-form">
            <input type="hidden" name="action" id="form-action">
            <input type="hidden" name="reservation_uid" id="form-reservation-uid">

            <div class="form-group">
                <label for="unit_id">Konaklama Birimi</label>
                <select name="unit_id" id="form-unit-id" class="form-control" required>
                    <?php foreach($units as $unit): ?>
                        <option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label for="start_date">Giriş Tarihi</label>
                    <input type="text" name="start_date" id="form-start-date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="end_date">Çıkış Tarihi</label>
                    <input type="text" name="end_date" id="form-end-date" class="form-control" required>
                </div>
            </div>
             <div class="form-group">
                <label for="guest_name">Müşteri Adı (veya Not)</label>
                <input type="text" name="guest_name" id="form-guest-name" class="form-control" placeholder="Örn: Ahmet Yılmaz veya 'Bloke Edildi'">
            </div>
            
            <div class="flex justify-between items-center mt-6">
                <button type="button" id="delete-event-btn" class="btn text-red-600 hover:bg-red-100 hidden">
                     <i data-feather="trash-2" class="w-4 h-4 mr-2"></i> Sil
                </button>
                <div>
                    <button type="button" id="close-modal-btn" class="btn btn-secondary mr-2">İptal</button>
                    <button type="submit" id="save-event-btn" class="btn btn-primary">Kaydet</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    feather.replace();
    const propertyFilter = document.getElementById('property-filter');
    const calendarEl = document.getElementById('calendar-container');
    const modal = document.getElementById('reservation-modal');
    const reservationForm = document.getElementById('reservation-form');
    
    propertyFilter.addEventListener('change', () => { window.location.href = `index.php?page=calendar&property_id=${propertyFilter.value}`; });

    const selectedPropertyId = '<?php echo $selected_property_id; ?>';
    if (!selectedPropertyId) {
        calendarEl.innerHTML = "<div class='text-center p-8 text-gray-500'>Lütfen takvimi görüntülemek için bir tesis seçin.</div>";
        return;
    }
    
    // HATA ÇÖZÜMÜ: Flatpickr'ı Türkçe yerelleştirme ile başlat
    const flatpickrConfig = {
        dateFormat: "Y-m-d",
        altInput: true,
        altFormat: "d F Y",
        locale: "tr"
    };
    const startDatePicker = flatpickr("#form-start-date", flatpickrConfig);
    const endDatePicker = flatpickr("#form-end-date", flatpickrConfig);

    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'tr',
        initialView: 'dayGridMonth',
        firstDay: 1,
        buttonText: { today: 'Bugün', month: 'Ay', week: 'Hafta', list: 'Liste' },
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        events: `../api/owner/calendar-events.php?property_id=${selectedPropertyId}`,
        selectable: true,
        editable: true,
        
        selectAllow: (selectInfo) => selectInfo.start >= new Date(new Date().setHours(0, 0, 0, 0)),
        eventAllow: (dropInfo, draggedEvent) => dropInfo.start >= new Date(new Date().setHours(0, 0, 0, 0)),
        dayCellDidMount: (arg) => { if (arg.date < new Date(new Date().setHours(0, 0, 0, 0))) { arg.el.style.backgroundColor = 'rgba(243, 244, 246, 0.7)'; } },
        eventContent: (arg) => {
            let unitName = arg.event.extendedProps.unit_name || '';
            let guestName = arg.event.title || '';
            let shortUnitName = unitName.replace(' - Ünite ', '-');
            return { html: `<div class="p-1" style="font-size: 0.8em; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><b>${guestName}</b><div style="color: rgba(255,255,255,0.9);">${shortUnitName}</div></div>` };
        },

        select: (info) => openModal('create', info),
        eventClick: (info) => openModal('update', info),
        eventDrop: (info) => updateEvent(info.event),
        eventResize: (info) => updateEvent(info.event)
    });
    calendar.render();

    document.getElementById('add-reservation-btn').addEventListener('click', function() {
        const today = new Date();
        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);
        openModal('create', { start: today, end: tomorrow });
    });

    function openModal(action, info) {
        reservationForm.reset();
        document.getElementById('form-action').value = action;
        const modalTitle = document.getElementById('modal-title');
        const deleteBtn = document.getElementById('delete-event-btn');
        
        if (action === 'create') {
            modalTitle.textContent = 'Yeni Rezervasyon Ekle';
            deleteBtn.classList.add('hidden');
            document.getElementById('form-reservation-uid').value = '';
            startDatePicker.setDate(info.start, true);
            const inclusiveEndDate = new Date(info.end);
            inclusiveEndDate.setDate(inclusiveEndDate.getDate() - 1);
            endDatePicker.setDate(inclusiveEndDate, true);
        } else {
            modalTitle.textContent = 'Rezervasyonu Düzenle';
            deleteBtn.classList.remove('hidden');
            const event = info.event;
            document.getElementById('form-reservation-uid').value = event.extendedProps.reservation_uid;
            document.getElementById('form-unit-id').value = event.extendedProps.unit_id;
            document.getElementById('form-guest-name').value = event.title;
            if (event.start) startDatePicker.setDate(event.start, true);
            if(event.end) {
                 const inclusiveEndDate = new Date(event.end);
                 inclusiveEndDate.setDate(inclusiveEndDate.getDate() - 1);
                 endDatePicker.setDate(inclusiveEndDate, true);
            } else {
                 endDatePicker.setDate(event.start, true);
            }
        }
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        feather.replace();
    }

    document.getElementById('close-modal-btn').addEventListener('click', () => modal.classList.add('hidden'));

    reservationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const data = Object.fromEntries(new FormData(this).entries());
        const endDate = new Date(data.end_date);
        endDate.setDate(endDate.getDate() + 1);
        data.end_date = endDate.toISOString().split('T')[0];
        
        fetch('../api/owner/manage-reservation.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(res => res.json()).then(result => {
            if (result.success) { modal.classList.add('hidden'); calendar.refetchEvents(); } 
            else { alert('Hata: ' + (result.error || 'Bilinmeyen bir hata oluştu.')); }
        });
    });

    document.getElementById('delete-event-btn').addEventListener('click', function() {
        if (!confirm('Bu kaydı silmek istediğinize emin misiniz?')) return;
        const data = { action: 'delete', reservation_uid: document.getElementById('form-reservation-uid').value };
        fetch('../api/owner/manage-reservation.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(res => res.json()).then(result => {
            if (result.success) { modal.classList.add('hidden'); calendar.refetchEvents(); } 
            else { alert('Hata: ' + (result.error || 'Silme işlemi başarısız.')); }
        });
    });

    function updateEvent(event) {
        let endDateStr = event.end ? event.end.toISOString().split('T')[0] : new Date(new Date(event.start).setDate(event.start.getDate() + 1)).toISOString().split('T')[0];
        const data = {
            action: 'update',
            reservation_uid: event.extendedProps.reservation_uid,
            guest_name: event.title,
            start_date: event.startStr,
            end_date: endDateStr, 
            unit_id: event.extendedProps.unit_id
        };
        fetch('../api/owner/manage-reservation.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
        .then(res => res.json()).then(result => { if (!result.success) { alert('Hata: ' + result.error); event.revert(); } });
    }
});
</script>
