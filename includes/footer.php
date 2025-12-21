        </main>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Flatpickr -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/vn.js"></script>
<!-- Toastr -->
<script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- Sortable.js -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
// =====================================================
// GLOBAL SETTINGS
// =====================================================

// Toastr config
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 5000
};

// Flatpickr config
flatpickr.localize(flatpickr.l10ns.vn);

// DataTables Vietnamese
$.extend(true, $.fn.dataTable.defaults, {
    language: {
        "decimal": "",
        "emptyTable": "Không có dữ liệu",
        "info": "Hiển thị _START_ đến _END_ của _TOTAL_ bản ghi",
        "infoEmpty": "Hiển thị 0 đến 0 của 0 bản ghi",
        "infoFiltered": "(lọc từ _MAX_ bản ghi)",
        "lengthMenu": "Hiển thị _MENU_ bản ghi",
        "loadingRecords": "Đang tải...",
        "processing": "Đang xử lý...",
        "search": "Tìm kiếm:",
        "zeroRecords": "Không tìm thấy bản ghi nào",
        "paginate": {
            "first": "Đầu",
            "last": "Cuối",
            "next": "Sau",
            "previous": "Trước"
        }
    }
});

// =====================================================
// NOTIFICATION SYSTEM
// =====================================================

let notificationPollInterval = <?= NOTIFICATION_POLL_INTERVAL ?> * 1000;
let notificationTimer;

// Toggle notification dropdown
$('#notificationBell').click(function(e) {
    e.stopPropagation();
    const dropdown = $('#notificationDropdown');
    
    if (dropdown.is(':visible')) {
        dropdown.fadeOut(200);
    } else {
        dropdown.fadeIn(200);
        loadNotifications();
    }
});

// Close dropdown when clicking outside
$(document).click(function(e) {
    if (!$(e.target).closest('#notificationDropdown, #notificationBell').length) {
        $('#notificationDropdown').fadeOut(200);
    }
});

// Load notifications via AJAX
function loadNotifications() {
    $.ajax({
        url: '<?= BASE_URL ?>/includes/notification-ajax.php',
        method: 'GET',
        data: { action: 'get_notifications', limit: 10 },
        success: function(response) {
            const data = JSON.parse(response);
            renderNotifications(data.notifications);
        },
        error: function() {
            $('#notificationList').html('<div class="text-center py-3 text-danger">Lỗi tải thông báo</div>');
        }
    });
}

// Render notifications
function renderNotifications(notifications) {
    const list = $('#notificationList');
    
    if (notifications.length === 0) {
        list.html('<div class="text-center py-4 text-muted"><i class="bi bi-bell-slash fs-2"></i><p class="mt-2">Không có thông báo</p></div>');
        return;
    }
    
    let html = '';
    const notifTypes = <?= json_encode($GLOBALS['NOTIFICATION_TYPES']) ?>;
    
    notifications.forEach(function(notif) {
        const type = notifTypes[notif.loai] || {icon: 'bi-bell', color: 'secondary'};
        const unreadClass = notif.da_doc == 0 ? 'unread' : '';
        
        html += `
            <div class="notification-item ${unreadClass}" onclick="markAsRead(${notif.id}, '${notif.lien_ket || '#'}')">
                <div class="d-flex">
                    <div class="notification-icon bg-${type.color} bg-opacity-10 text-${type.color} me-3">
                        <i class="${type.icon}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${notif.tieu_de}</h6>
                        <p class="mb-1 small text-muted">${notif.noi_dung || ''}</p>
                        <span class="notification-time">${notif.time_ago}</span>
                    </div>
                </div>
            </div>
        `;
    });
    
    list.html(html);
}

// Mark notification as read
function markAsRead(notifId, url) {
    $.post('<?= BASE_URL ?>/includes/notification-ajax.php', {
        action: 'mark_as_read',
        id: notifId
    }, function() {
        updateNotificationBadge();
        if (url && url !== '#') {
            window.location.href = url;
        }
    });
}

// Update notification badge
function updateNotificationBadge() {
    $.get('<?= BASE_URL ?>/includes/notification-ajax.php?action=count_unread', function(response) {
        const data = JSON.parse(response);
        const badge = $('#notificationBadge');
        
        if (data.count > 0) {
            if (badge.length) {
                badge.text(data.count);
            } else {
                $('#notificationBell').append(`<span class="notification-badge" id="notificationBadge">${data.count}</span>`);
            }
        } else {
            badge.remove();
        }
    });
}

// Poll for new notifications
function startNotificationPolling() {
    notificationTimer = setInterval(function() {
        updateNotificationBadge();
    }, notificationPollInterval);
}

// Show toast for urgent notifications on page load
function showUrgentNotifications() {
    $.get('<?= BASE_URL ?>/includes/notification-ajax.php?action=get_urgent', function(response) {
        const data = JSON.parse(response);
        
        if (data.notifications && data.notifications.length > 0) {
            data.notifications.forEach(function(notif) {
                const type = notif.loai === 'qua_han' ? 'error' : 'warning';
                toastr[type](notif.noi_dung, notif.tieu_de, {
                    timeOut: 10000,
                    onclick: function() {
                        if (notif.lien_ket) {
                            window.location.href = notif.lien_ket;
                        }
                    }
                });
            });
        }
    });
}

// Initialize on page load
$(document).ready(function() {
    startNotificationPolling();
    showUrgentNotifications();
});

// =====================================================
// COMMON FUNCTIONS
// =====================================================

// Confirm delete
function confirmDelete(message = 'Bạn có chắc chắn muốn xóa?') {
    return Swal.fire({
        title: 'Xác nhận',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    });
}

// Format number
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Format file size
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}
</script>

<!-- Page-specific scripts will be added here -->
<?php if (isset($extra_js)): ?>
    <?= $extra_js ?>
<?php endif; ?>

</body>
</html>
