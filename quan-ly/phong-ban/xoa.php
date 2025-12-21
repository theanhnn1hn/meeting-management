<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_permission('manage_phong_ban');

$phong_id = (int)($_GET['id'] ?? 0);

if ($phong_id <= 0) {
    redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Thiếu thông tin', 'error');
}

// Lấy thông tin phòng
$stmt = $pdo->prepare("SELECT * FROM phong_ban WHERE id = ?");
$stmt->execute([$phong_id]);
$phong = $stmt->fetch();

if (!$phong) {
    redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Không tìm thấy phòng ban', 'error');
}

// Kiểm tra còn user không
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phong_ban_id = ?");
$stmt->execute([$phong_id]);
$user_count = $stmt->fetchColumn();

if ($user_count > 0) {
    redirect(
        BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 
        'Không thể xóa phòng ban còn ' . $user_count . ' người dùng. Vui lòng chuyển người dùng sang phòng khác trước', 
        'error'
    );
}

// Xử lý xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("DELETE FROM phong_ban WHERE id = ?");
        $stmt->execute([$phong_id]);
        
        log_activity('Xóa phòng ban', 'phong_ban', $phong_id, 'Mã: ' . $phong['ma_phong']);
        
        redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Xóa phòng ban thành công', 'success');
    } catch (PDOException $e) {
        redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Lỗi xóa phòng ban: ' . $e->getMessage(), 'error');
    }
}

$page_title = 'Xóa phòng ban';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Xác nhận xóa phòng ban</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i> Bạn có chắc chắn muốn xóa phòng ban này?
                </div>
                
                <table class="table">
                    <tr>
                        <th>Mã phòng:</th>
                        <td><?= e($phong['ma_phong']) ?></td>
                    </tr>
                    <tr>
                        <th>Tên phòng:</th>
                        <td><?= e($phong['ten_phong']) ?></td>
                    </tr>
                </table>
                
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/phong-ban/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Xóa phòng ban
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
