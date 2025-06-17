<?php
$notification_class = new Notification();
$owner_id = $_SESSION['user_id'];
$notification_class->markAllAsRead($owner_id);
$notifications = $notification_class->getNotifications($owner_id);
?>

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold">Bildirimler</h2>
</div>

<div class="card">
    <div class="notification-list">
        <?php if (empty($notifications)): ?>
            <div class="text-center py-12 text-gray-500">
                <i data-feather="bell-off" class="mx-auto h-12 w-12 text-gray-400"></i>
                <p class="mt-2">Henüz bildiriminiz bulunmuyor.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo $notification['is_read'] ? 'is-read' : ''; ?>" 
                     <?php if ($notification['reservation_id']): ?>
                         data-notification-id="<?php echo $notification['id']; ?>"
                         onclick="openReservationDetails(this)"
                         style="cursor: pointer;"
                     <?php endif; ?>
                >
                    <div class="notification-link-content">
                        <div class="notification-icon">
                            <i data-feather="<?php echo $notification['reservation_id'] ? 'zap' : 'bell'; ?>"></i>
                        </div>
                        <div class="notification-content">
                            <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <p class="notification-time"><?php echo (new DateTime($notification['created_at']))->format('d.m.Y H:i'); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Rezervasyon Detayları için Modal Penceresi -->
<div id="details-modal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden items-center justify-center z-50 p-4">
    <div class="card w-full max-w-2xl animate-fade-in-up relative">
        <button onclick="closeModal()" class="absolute top-4 right-4 text-gray-500 hover:text-gray-800"><i data-feather="x"></i></button>
        <div id="modal-content" class="space-y-6">
            <!-- İçerik AJAX ile doldurulacak -->
            <div class="text-center py-10"><i data-feather="loader" class="animate-spin h-8 w-8 text-primary-600"></i></div>
        </div>
    </div>
</div>


<script>
    feather.replace();
    const modal = document.getElementById('details-modal');
    const modalContent = document.getElementById('modal-content');

    function openReservationDetails(element) {
        const notificationId = element.dataset.notificationId;
        if (!notificationId) return;

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modalContent.innerHTML = `<div class="text-center py-10"><i data-feather="loader" class="animate-spin h-8 w-8 text-primary-600"></i></div>`;
        feather.replace();

        fetch(`/owner/ajax/get_notification_details.php?id=${notificationId}`)
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const data = result.data;
                    modalContent.innerHTML = `
                        <div>
                            <h3 class="text-xl font-bold text-gray-800">${data.unit_type_name} - ${data.unit_name}</h3>
                            <p class="text-sm text-gray-500">Acente: <strong>${data.agent_name}</strong></p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="font-semibold text-gray-700 border-b pb-2">Misafir Bilgileri</h4>
                                <div class="info-row"><i data-feather="user"></i><span>${data.guest_name}</span></div>
                                <div class="info-row"><i data-feather="phone"></i><span>${data.guest_phone || '-'}</span></div>
                                <div class="info-row"><i data-feather="mail"></i><span>${data.guest_email || '-'}</span></div>
                            </div>
                            <div class="space-y-4">
                                <h4 class="font-semibold text-gray-700 border-b pb-2">Finansal Detaylar</h4>
                                <div class="info-row"><i data-feather="log-in"></i><span><strong>Giriş:</strong> ${data.start_date_formatted}</span></div>
                                <div class="info-row"><i data-feather="log-out"></i><span><strong>Çıkış:</strong> ${data.end_date_formatted}</span></div>
                                <div class="info-row"><i data-feather="tag"></i><span><strong>Satış Fiyatı:</strong> ${data.total_price_formatted} ₺</span></div>
                                <div class="info-row text-red-600"><i data-feather="percent"></i><span><strong>Komisyon:</strong> ${data.commission_amount_formatted} ₺</span></div>
                                <div class="info-row text-green-600"><i data-feather="check-circle"></i><span><strong>Net Kazanç:</strong> ${data.net_income_formatted} ₺</span></div>
                            </div>
                        </div>
                    `;
                    feather.replace();
                } else {
                    modalContent.innerHTML = `<p class="text-red-600">${result.error}</p>`;
                }
            })
            .catch(error => {
                modalContent.innerHTML = `<p class="text-red-600">Bir hata oluştu: ${error.message}</p>`;
            });
    }

    function closeModal() {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
</script>

<style>
    /* Önceki stillere ek olarak */
    .notification-link-content { display: flex; align-items: center; padding: 1rem; }
    .info-row { display: flex; align-items: center; gap: 0.75rem; font-size: 0.9rem; }
    .info-row i { width: 18px; height: 18px; color: var(--gray-400); }
</style>
