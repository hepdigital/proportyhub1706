document.addEventListener('DOMContentLoaded', function() {
    const page = new URLSearchParams(window.location.search).get('page') || 'dashboard';

    if (page === 'dashboard') {
        initDashboardChart();
    }
    
    if (page === 'properties') {
        initPropertiesPage();
    }
    
    if (page === 'calendar') {
        initCalendarPage();
    }
});

// DASHBOARD Fonksiyonları
function initDashboardChart() {
    const ctx = document.getElementById('bookingsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            // Bu veriler API'dan dinamik olarak çekilebilir.
            labels: ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran'],
            datasets: [{
                label: 'Aylık Rezervasyonlar',
                data: [12, 19, 3, 5, 2, 3],
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });
}

// TESİSLERİM Sayfası Fonksiyonları
function initPropertiesPage() {
    const propertyModal = new bootstrap.Modal(document.getElementById('propertyModal'));
    
    document.getElementById('addPropertyBtn').addEventListener('click', () => {
        document.getElementById('propertyForm').reset();
        document.getElementById('propertyId').value = '';
        document.getElementById('modalTitle').textContent = 'Yeni Tesis Ekle';
    });

    // Not: Gerçek bir uygulamada save, edit, delete işlemleri için
    // API uç noktaları oluşturulmalı ve fetch ile çağrılmalıdır.
    // Bu kısım, mevcut API yapınıza göre entegre edilmelidir.
}