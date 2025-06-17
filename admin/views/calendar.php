<?php
$property = new Property();
$unit = new Unit();
$calendar = new Calendar();

// Tesis seçimi GET parametresinden alınır
$selected_property = $_GET['property_id'] ?? null;
$month = $_GET['month'] ?? date('Y-m');

// HİÇBİR PARAMETRE VERMEDEN ÇAĞIRILAN getAll() TÜM TESİSLERİ GETİRİR
$properties = $property->getAll(); 
$units = $selected_property ? $unit->getByProperty($selected_property) : [];

// Takvim için tarih aralığı
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));
?>

<div class="calendar-page">
    <h2>Müsaitlik Takvimi</h2>
    
    <div class="calendar-filters">
        <form method="GET">
            <input type="hidden" name="page" value="calendar">
            <select name="property_id" onchange="this.form.submit()">
                <option value="">-- Tesis Seçin --</option>
                <?php foreach ($properties as $prop): ?>
                <option value="<?php echo $prop['id']; ?>" <?php echo $selected_property == $prop['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prop['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <input type="month" name="month" value="<?php echo $month; ?>" onchange="this.form.submit()">
        </form>
    </div>
    
    <?php if ($selected_property && !empty($units)): ?>
    <div class="calendar-grid">
        <?php foreach ($units as $unit_data): ?>
        <?php
        $availability = $calendar->getAvailability($unit_data['id'], $start_date, $end_date);
        $dates = getDatesInRange($start_date, $end_date);
        ?>
        <div class="unit-calendar">
            <h3><?php echo htmlspecialchars($unit_data['name']); ?></h3>
            <div class="calendar-days">
                <?php foreach ($dates as $date): ?>
                <?php
                $is_available = $availability[$date]['available'] ?? true;
                $day_class = $is_available ? 'available' : 'reserved';
                ?>
                <div class="calendar-day <?php echo $day_class; ?>" title="<?php echo formatDate($date); ?>">
                    <?php echo date('d', strtotime($date)); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <div class="calendar-legend">
        <span class="legend-item"><span class="calendar-day available"></span> Müsait</span>
        <span class="legend-item"><span class="calendar-day reserved"></span> Rezerve</span>
    </div>
    <?php elseif ($selected_property): ?>
    <p>Bu tesis için ünite bulunamadı.</p>
    <?php else: ?>
    <p>Lütfen takvimi görüntülemek için bir tesis seçin.</p>
    <?php endif; ?>
</div>