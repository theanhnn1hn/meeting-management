<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();

$user_id = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    $_SESSION['error'] = "Không tìm thấy thông báo";
    header("Location: danh-sach.php");
    exit;
}

// Get notification
$stmt = $pdo->prepare("
    SELECT tb.*, u.ho_ten as nguoi_gui_ten, u.email as nguoi_gui_email
    FROM thong_bao tb
    LEFT JOIN users u ON tb.nguoi_gui_id = u.id
    WHERE tb.id = ? AND tb.nguoi_nhan_id = ?
");
$stmt->execute([$id, $user_id]);
$notif = $stmt->fetch();

if (!$notif) {
    $_SESSION['error'] = "Không tìm thấy thông báo";
    header("Location: danh-sach.php");
    exit;
}

// Mark as read
if ($notif['da_doc'] == 0) {
    $stmt = $pdo->prepare("UPDATE thong_bao SET da_doc = 1, ngay_doc = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

$page_title = "Chi tiết thông báo";
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="../../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="danh-sach.php">Thông báo</a></li>
                    <li class="breadcrumb-item active">Chi tiết</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
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
                        </div>
                        <div>
                            <a href="danh-sach.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Quay lại
                            </a>
                            <button class="btn btn-sm btn-danger" id="deleteBtn">
                                <i class="fas fa-trash"></i> Xóa
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <h4 class="mb-3"><?php echo htmlspecialchars($notif['tieu_de']); ?></h4>
                    
                    <div class="alert alert-light border">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    <i class="fas fa-user"></i> <strong>Người gửi:</strong> 
                                    <?php echo htmlspecialchars($notif['nguoi_gui_ten'] ?? 'Hệ thống'); ?>
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">
                                    <i class="far fa-clock"></i> <strong>Thời gian:</strong>
                                    <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($notif['ngay_doc']): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-success">
                                        <i class="fas fa-check-circle"></i> Đã đọc lúc: 
                                        <?php echo date('d/m/Y H:i', strtotime($notif['ngay_doc'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-content mt-4">
                        <?php echo nl2br(htmlspecialchars($notif['noi_dung'])); ?>
                    </div>
                    
                    <?php if ($notif['lien_ket']): ?>
                        <div class="mt-4">
                            <a href="<?php echo htmlspecialchars($notif['lien_ket']); ?>" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Xem chi tiết
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Related notifications -->
            <?php
            // Get related notifications (same type, recent)
            $stmt = $pdo->prepare("
                SELECT id, tieu_de, created_at, da_doc
                FROM thong_bao
                WHERE nguoi_nhan_id = ? 
                AND id != ? 
                AND loai = ?
                ORDER BY created_at DESC
                LIMIT 5
            ");
            $stmt->execute([$user_id, $id, $notif['loai']]);
            $related = $stmt->fetchAll();
            ?>
            
            <?php if ($related): ?>
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Thông báo liên quan</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($related as $rel): ?>
                                <a href="chi-tiet.php?id=<?php echo $rel['id']; ?>" 
                                   class="list-group-item list-group-item-action <?php echo $rel['da_doc'] == 0 ? 'list-group-item-info' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if ($rel['da_doc'] == 0): ?>
                                                <span class="badge bg-primary me-2">Mới</span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($rel['tieu_de']); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo time_elapsed_string($rel['created_at']); ?>
                                        </small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Stats -->
            <div class="card mb-3">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Thống kê thông báo</h6>
                </div>
                <div class="card-body">
                    <?php
                    // Get stats
                    $stmt = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN da_doc = 0 THEN 1 ELSE 0 END) as unread,
                            SUM(CASE WHEN da_doc = 1 THEN 1 ELSE 0 END) as read
                        FROM thong_bao
                        WHERE nguoi_nhan_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    $stats = $stmt->fetch();
                    
                    // Stats by type
                    $stmt = $pdo->prepare("
                        SELECT loai, COUNT(*) as count
                        FROM thong_bao
                        WHERE nguoi_nhan_id = ?
                        GROUP BY loai
                        ORDER BY count DESC
                    ");
                    $stmt->execute([$user_id]);
                    $type_stats = $stmt->fetchAll();
                    ?>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Tổng số</span>
                            <strong><?php echo number_format($stats['total']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-primary">
                            <span>Chưa đọc</span>
                            <strong><?php echo number_format($stats['unread']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between text-success">
                            <span>Đã đọc</span>
                            <strong><?php echo number_format($stats['read']); ?></strong>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-2">Theo loại:</h6>
                    <?php foreach ($type_stats as $ts): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="badge bg-<?php echo $badge_class[$ts['loai']] ?? 'secondary'; ?>">
                                <?php echo $loai_text[$ts['loai']] ?? $ts['loai']; ?>
                            </span>
                            <strong><?php echo number_format($ts['count']); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card">
                <div class="card-header bg-light">
                    <h6 class="mb-0">Thao tác nhanh</h6>
                </div>
                <div class="card-body">
                    <a href="danh-sach.php?da_doc=0" class="btn btn-sm btn-primary w-100 mb-2">
                        <i class="fas fa-envelope"></i> Xem chưa đọc
                    </a>
                    <a href="danh-sach.php?loai=<?php echo $notif['loai']; ?>" class="btn btn-sm btn-info w-100 mb-2">
                        <i class="fas fa-filter"></i> Lọc cùng loại
                    </a>
                    <button class="btn btn-sm btn-success w-100" id="markAllReadBtn">
                        <i class="fas fa-check-double"></i> Đánh dấu tất cả đã đọc
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Delete notification
    $('#deleteBtn').click(function() {
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
                    id: <?php echo $id; ?>,
                    csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
                }, function(response) {
                    if (response.success) {
                        toastr.success('Đã xóa');
                        window.location.href = 'danh-sach.php';
                    } else {
                        toastr.error(response.message || 'Có lỗi xảy ra');
                    }
                }, 'json');
            }
        });
    });
    
    // Mark all as read
    $('#markAllReadBtn').click(function() {
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
                        setTimeout(() => {
                            window.location.href = 'danh-sach.php';
                        }, 1000);
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
