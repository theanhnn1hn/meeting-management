<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Phân trang
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Filters
$loai_filter = $_GET['loai'] ?? '';
$trang_thai = isset($_GET['da_doc']) ? (int)$_GET['da_doc'] : -1;

// Build query
$where = ["user_id = ?"];
$params = [$user_id];

if ($loai_filter) {
    $where[] = "loai = ?";
    $params[] = $loai_filter;
}

if ($trang_thai !== -1) {
    $where[] = "da_doc = ?";
    $params[] = $trang_thai;
}

$where_sql = implode(' AND ', $where);

// Get total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM thong_bao WHERE $where_sql");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $per_page);

// Get notifications
$stmt = $pdo->prepare("
    SELECT tb.*
    FROM thong_bao tb
    WHERE $where_sql
    ORDER BY tb.created_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Count unread
$stmt = $pdo->prepare("SELECT COUNT(*) FROM thong_bao WHERE user_id = ? AND da_doc = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetchColumn();

$page_title = "Thông báo";
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-bell"></i> Thông báo (<?php echo $unread_count; ?> chưa đọc)</h4>
                <div>
                    <button class="btn btn-sm btn-success" id="markAllRead">
                        <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
                    </button>
                    <button class="btn btn-sm btn-danger" id="deleteAllRead">
                        <i class="fas fa-trash"></i> Xóa đã đọc
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-3">
                            <select name="loai" class="form-select form-select-sm">
                                <option value="">Tất cả loại</option>
                                <option value="noi_dung_moi" <?php echo $loai_filter == 'noi_dung_moi' ? 'selected' : ''; ?>>Nội dung mới</option>
                                <option value="phe_duyet" <?php echo $loai_filter == 'phe_duyet' ? 'selected' : ''; ?>>Phê duyệt</option>
                                <option value="tu_choi" <?php echo $loai_filter == 'tu_choi' ? 'selected' : ''; ?>>Từ chối</option>
                                <option value="gop_y" <?php echo $loai_filter == 'gop_y' ? 'selected' : ''; ?>>Góp ý</option>
                                <option value="doc_viec" <?php echo $loai_filter == 'doc_viec' ? 'selected' : ''; ?>>Đốc việc</option>
                                <option value="deadline" <?php echo $loai_filter == 'deadline' ? 'selected' : ''; ?>>Deadline</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="da_doc" class="form-select form-select-sm">
                                <option value="-1">Tất cả trạng thái</option>
                                <option value="0" <?php echo $trang_thai === 0 ? 'selected' : ''; ?>>Chưa đọc</option>
                                <option value="1" <?php echo $trang_thai === 1 ? 'selected' : ''; ?>>Đã đọc</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-filter"></i> Lọc
                            </button>
                        </div>
                        <div class="col-md-2">
                            <a href="danh-sach.php" class="btn btn-sm btn-secondary w-100">
                                <i class="fas fa-redo"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="card">
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Không có thông báo</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notif): ?>
                                <a href="chi-tiet.php?id=<?php echo $notif['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $notif['da_doc'] == 0 ? 'list-group-item-info' : ''; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <?php if ($notif['da_doc'] == 0): ?>
                                                    <span class="badge bg-primary me-2">Mới</span>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $badge_class = [
                                                    'noi_dung_moi' => 'success',
                                                    'phe_duyet' => 'primary',
                                                    'tu_choi' => 'danger',
                                                    'gop_y' => 'warning',
                                                    'doc_viec' => 'danger',
                                                    'deadline' => 'danger'
                                                ];
                                                $loai_text = [
                                                    'noi_dung_moi' => 'Nội dung mới',
                                                    'phe_duyet' => 'Phê duyệt',
                                                    'tu_choi' => 'Từ chối',
                                                    'gop_y' => 'Góp ý',
                                                    'doc_viec' => 'Đốc việc',
                                                    'deadline' => 'Deadline'
                                                ];
                                                ?>
                                                <span class="badge bg-<?php echo $badge_class[$notif['loai']] ?? 'secondary'; ?>">
                                                    <?php echo $loai_text[$notif['loai']] ?? $notif['loai']; ?>
                                                </span>
                                                
                                                <small class="text-muted ms-2">
                                                    <i class="far fa-clock"></i>
                                                    <?php echo time_ago($notif['created_at']); ?>
                                                </small>
                                            </div>
                                            
                                            <h6 class="mb-1"><?php echo htmlspecialchars($notif['tieu_de']); ?></h6>
                                            <p class="mb-1 text-muted small"><?php echo htmlspecialchars(mb_substr($notif['noi_dung'], 0, 150)); ?>...</p>
                                        </div>
                                        
                                        <div class="ms-3">
                                            <button class="btn btn-sm btn-outline-danger delete-notif" 
                                                    data-id="<?php echo $notif['id']; ?>"
                                                    onclick="event.preventDefault(); event.stopPropagation();">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="card-footer">
                        <nav>
                            <ul class="pagination pagination-sm mb-0 justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $loai_filter ? '&loai=' . $loai_filter : ''; ?><?php echo $trang_thai !== -1 ? '&da_doc=' . $trang_thai : ''; ?>">Trước</a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $loai_filter ? '&loai=' . $loai_filter : ''; ?><?php echo $trang_thai !== -1 ? '&da_doc=' . $trang_thai : ''; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $loai_filter ? '&loai=' . $loai_filter : ''; ?><?php echo $trang_thai !== -1 ? '&da_doc=' . $trang_thai : ''; ?>">Sau</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Mark all as read
    $('#markAllRead').click(function() {
        Swal.fire({
            title: 'Xác nhận',
            text: 'Đánh dấu tất cả thông báo đã đọc?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../api/notification-actions.php', {
                    action: 'mark_all_read',
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                }, function(response) {
                    if (response.success) {
                        toastr.success('Đã cập nhật');
                        location.reload();
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra');
                    }
                }, 'json');
            }
        });
    });
    
    // Delete all read
    $('#deleteAllRead').click(function() {
        Swal.fire({
            title: 'Xác nhận xóa',
            text: 'Xóa tất cả thông báo đã đọc?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../api/notification-actions.php', {
                    action: 'delete_all_read',
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                }, function(response) {
                    if (response.success) {
                        toastr.success('Đã xóa');
                        location.reload();
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra');
                    }
                }, 'json');
            }
        });
    });
    
    // Delete single notification
    $('.delete-notif').click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Xác nhận xóa',
            text: 'Xóa thông báo này?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../api/notification-actions.php', {
                    action: 'delete',
                    id: id,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                }, function(response) {
                    if (response.success) {
                        toastr.success('Đã xóa');
                        location.reload();
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra');
                    }
                }, 'json');
            }
        });
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
