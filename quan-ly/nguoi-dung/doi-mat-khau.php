<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

// Chánh VP có thể đổi MK cho tất cả, Trưởng phòng cho user trong phòng
$user_id = (int)($_GET['id'] ?? 0);

if ($user_id <= 0) {
    redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Thiếu thông tin', 'error');
}

// Lấy thông tin user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Không tìm thấy người dùng', 'error');
}

// Kiểm tra quyền
$can_change = false;
if ($_SESSION['chuc_vu'] === ROLE_CHANH_VP) {
    $can_change = true;
} elseif (in_array($_SESSION['chuc_vu'], [ROLE_TRUONG_PHONG]) && $user['phong_ban_id'] == $_SESSION['phong_ban_id']) {
    $can_change = true;
}

if (!$can_change) {
    redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Bạn không có quyền đổi mật khẩu người dùng này', 'error');
}

$error = '';
$success = '';

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp';
        } elseif (strlen($new_password) < 6) {
            $error = 'Mật khẩu phải có ít nhất 6 ký tự';
        } else {
            try {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $user_id]);
                
                log_activity('Đổi mật khẩu người dùng', 'users', $user_id, 'Username: ' . $user['username']);
                
                redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Đổi mật khẩu thành công', 'success');
            } catch (PDOException $e) {
                $error = 'Lỗi đổi mật khẩu: ' . $e->getMessage();
            }
        }
    }
}

$page_title = 'Đổi mật khẩu';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-key"></i> Đổi mật khẩu</h5>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= e($error) ?></div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <strong>Người dùng:</strong> <?= e($user['ho_ten']) ?> (<?= e($user['username']) ?>)
                </div>
                
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Tối thiểu 6 ký tự</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" required>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/nguoi-dung/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-key"></i> Đổi mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
