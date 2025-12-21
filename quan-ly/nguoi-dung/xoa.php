<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_permission('manage_users');

if (!isset($_GET['id'])) {
    redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Thiếu thông tin', 'error');
}

$user_id = (int)$_GET['id'];

// Không cho phép xóa chính mình
if ($user_id == $_SESSION['user_id']) {
    redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Không thể xóa tài khoản của chính mình', 'error');
}

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Không tìm thấy người dùng', 'error');
}

// Kiểm tra user có công việc không
$stmt = $pdo->prepare("SELECT COUNT(*) FROM noi_dung WHERE nguoi_trinh_id = ?");
$stmt->execute([$user_id]);
$count_noi_dung = $stmt->fetchColumn();

if ($count_noi_dung > 0) {
    redirect(
        BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 
        'Không thể xóa người dùng đã có ' . $count_noi_dung . ' nội dung. Vui lòng chuyển công việc sang người khác trước', 
        'error'
    );
}

// Xử lý xóa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        log_activity('Xóa người dùng', 'users', $user_id, 'Username: ' . $user['username']);
        
        redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Xóa người dùng thành công', 'success');
    } catch (PDOException $e) {
        redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Lỗi xóa người dùng: ' . $e->getMessage(), 'error');
    }
}

$page_title = 'Xóa người dùng';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Xác nhận xóa người dùng</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i> Bạn có chắc chắn muốn xóa người dùng này?
                </div>
                
                <table class="table">
                    <tr>
                        <th>Họ tên:</th>
                        <td><?= e($user['ho_ten']) ?></td>
                    </tr>
                    <tr>
                        <th>Username:</th>
                        <td><?= e($user['username']) ?></td>
                    </tr>
                    <tr>
                        <th>Email:</th>
                        <td><?= e($user['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Chức vụ:</th>
                        <td><?= e($GLOBALS['ROLES'][$user['chuc_vu']]) ?></td>
                    </tr>
                </table>
                
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/nguoi-dung/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Xóa người dùng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
