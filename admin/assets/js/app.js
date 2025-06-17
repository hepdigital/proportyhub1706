// API URL
const API_URL = window.location.origin + '/api/index.php'; // Yönlendirici olarak her zaman index.php'yi hedefleyelim

// DOM yüklendiğinde çalışacak ana fonksiyon
document.addEventListener('DOMContentLoaded', function() {
    // Sadece Dashboard sayfasındaysa istatistikleri yükle
    if (document.querySelector('.dashboard')) {
        loadDashboardStats();
        // Dashboard'da periyodik güncelleme başlat
        setInterval(loadDashboardStats, 300000); // 5 dakika
    }
    
    // Tüm sayfalarda çalışacak event listener'ları başlat
    initEventListeners();
});

// Tüm olay dinleyicilerini (event listeners) başlatan merkezi fonksiyon
function initEventListeners() {
    const syncTypeSelect = document.querySelector('select[name="sync_type"]');
    
    // Eğer Senkronizasyon Tipi dropdown'ı sayfada varsa...
    if (syncTypeSelect) {
        // Sayfa ilk yüklendiğinde mevcut seçime göre alanları ve butonları ayarla
        toggleSyncFields(syncTypeSelect.value);

        // Kullanıcı seçimi her değiştirdiğinde tekrar ayarla
        syncTypeSelect.addEventListener('change', function() {
            toggleSyncFields(this.value);
        });
    }
}

/**
 * Senkronizasyon Tipi'ne göre form alanlarını ve buton metinlerini dinamik olarak yönetir.
 * @param {string} syncType 'hub', 'wordpress' veya 'ical' değeri
 */
function toggleSyncFields(syncType) {
    const wordpressFields = document.querySelectorAll('.wordpress-fields');
    const icalUrlField = document.querySelector('.ical-url-field'); // Ünite ekleme formundaki iCal alanı
    const unitAddForm = document.querySelector('.unit-add-form-section');

    // WordPress alanlarının görünürlüğü
    wordpressFields.forEach(el => {
        el.style.display = syncType === 'wordpress' ? '' : 'none';
    });
    
    // Ünite ekleme formundaki iCal URL alanının görünürlüğü
    if (icalUrlField) {
        icalUrlField.style.display = syncType === 'ical' ? '' : 'none';
    }
    
    // Manuel Ünite Ekleme formunun tamamının görünürlüğü
    if (unitAddForm) {
        unitAddForm.style.display = syncType === 'wordpress' ? 'none' : '';
    }

    // Ana Kaydet/Oluştur butonunun metnini değiştir
    const saveButton = document.getElementById('save-property-button');
    if (saveButton) {
        const isEditMode = saveButton.hasAttribute('data-edit-mode');
        if (syncType === 'wordpress') {
            saveButton.textContent = isEditMode ? 'Değişiklikleri Kaydet ve Üniteleri Yeniden Çek' : 'Tesisi Oluştur ve Üniteleri Çek';
        } else {
            saveButton.textContent = isEditMode ? 'Değişiklikleri Kaydet' : 'Tesisi Oluştur ve Devam Et';
        }
    }
}

// Dashboard istatistiklerini yükle
async function loadDashboardStats() {
    try {
        const response = await fetch(`${API_URL}?endpoint=properties`);
        const properties = await response.json();
        
        let totalUnits = 0;
        properties.forEach(prop => {
            totalUnits += parseInt(prop.unit_count) || 0;
        });
        
        document.getElementById('total-units').textContent = totalUnits;
        document.getElementById('sync-today').textContent = '0'; // Bu değer dinamik olarak loglardan hesaplanabilir
    } catch (error) {
        console.error('İstatistikler yüklenemedi:', error);
    }
}

// Tesis senkronize et
async function syncProperty(propertyId) {
    if (!confirm('Bu tesisin tüm ünitelerini senkronize etmek istiyor musunuz? Bu işlem, mevcut müsaitlik durumunu ana kaynaktan gelen veriyle günceller.')) return;
    
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Senkronize ediliyor...';
    
    try {
        const response = await fetch(`${API_URL}?endpoint=sync`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ property_id: propertyId })
        });
        
        const result = await response.json();
        
        if (response.ok) {
            alert('Senkronizasyon tamamlandı!');
            location.reload();
        } else {
            throw new Error(result.error || 'Senkronizasyon başarısız');
        }
    } catch (error) {
        alert('Hata: ' + error.message);
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}

// Ünite senkronize et
async function syncUnit(unitId) {
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Senkronize ediliyor...';
    
    try {
        const response = await fetch(`${API_URL}?endpoint=sync`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ unit_id: unitId })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            alert('Ünite senkronize edildi!');
            location.reload();
        } else {
            throw new Error(result.error || 'Senkronizasyon başarısız');
        }
    } catch (error) {
        alert('Hata: ' + error.message);
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}

// Ünite sil
async function deleteUnit(unitId) {
    // Bu fonksiyonun çalışması için ana index.php'de 'delete_unit' aksiyonunun eklenmesi gerekir.
    if (confirm('Bu üniteyi ve tüm müsaitlik verilerini kalıcı olarak silmek istediğinize emin misiniz?')) {
        window.location.href = `index.php?page=property-add&id=${getPropertyIdFromUrl()}&action=delete_unit&unit_id=${unitId}`;
    }
}

// URL'den property ID'sini almak için yardımcı fonksiyon
function getPropertyIdFromUrl() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('id');
}