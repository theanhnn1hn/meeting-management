<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Hồ sơ cá nhân';
$current_user = get_auth_user();
$error = '';
$success = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $ho_ten = sanitize($_POST['ho_ten'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $dien_thoai = sanitize($_POST['dien_thoai'] ?? '');
        
        if (empty($ho_ten)) {
            $error = 'Vui lòng nhập họ tên';
        } else {
            try {
                // Kiểm tra email trùng (nếu đổi email)
                if ($email !== $current_user['email'] && !empty($email)) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    if ($stmt->fetch()) {
                        $error = 'Email đã được sử dụng';
                    }
                }
                
                if (empty($error)) {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET ho_ten = ?, email = ?, dien_thoai = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$ho_ten, $email, $dien_thoai, $_SESSION['user_id']]);
                    
                    // Cập nhật session
                    $_SESSION['ho_ten'] = $ho_ten;
                    
                    log_activity('Cập nhật hồ sơ', 'users', $_SESSION['user_id']);

                    $success = 'Cập nhật thông tin thành công';
                    $current_user = get_auth_user(); // Reload
                }
            } catch (PDOException $e) {
                $error = 'Lỗi cập nhật: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý đổi mật khẩu
if (isset($_POST['change_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin mật khẩu';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu xác nhận không khớp';
        } elseif (strlen($new_password) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        } else {
            // Kiểm tra mật khẩu cũ
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!password_verify($old_password, $user['password'])) {
                $error = 'Mật khẩu cũ không đúng';
            } else {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $_SESSION['user_id']]);
                
                log_activity('Đổi mật khẩu', 'users', $_SESSION['user_id']);
                
                $success = 'Đổi mật khẩu thành công';
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="bi bi-person-circle"></i> Hồ sơ cá nhân</h2>
        <p class="text-muted">Quản lý thông tin tài khoản của bạn</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle"></i> <?= e($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Thông tin cá nhân -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-person"></i> Thông tin cá nhân</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên đăng nhập</label>
                        <input type="text" class="form-control" value="<?= e($current_user['username']) ?>" disabled>
                        <small class="text-muted">Không thể thay đổi tên đăng nhập</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                        <input type="text" name="ho_ten" class="form-control" 
                               value="<?= e($current_user['ho_ten']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?= e($current_user['email'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Điện thoại</label>
                        <input type="text" name="dien_thoai" class="form-control" 
                               value="<?= e($current_user['dien_thoai'] ?? '') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Chức vụ</label>
                        <input type="text" class="form-control" 
                               value="<?= e($GLOBALS['ROLES'][$current_user['chuc_vu']]) ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phòng ban</label>
                        <input type="text" class="form-control" 
                               value="<?= e($current_user['ten_phong'] ?? 'Chưa gắn phòng') ?>" disabled>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Cập nhật thông tin
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Đổi mật khẩu -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-key"></i> Đổi mật khẩu</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="change_password" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu hiện tại <span class="text-danger">*</span></label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="new_password" class="form-control" 
                               minlength="6" required>
                        <small class="text-muted">Tối thiểu 6 ký tự</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Xác nhận mật khẩu mới <span class="text-danger">*</span></label>
                        <input type="password" name="confirm_password" class="form-control" 
                               minlength="6" required>
                    </div>
                    
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-shield-lock"></i> Đổi mật khẩu
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Thông tin đăng nhập -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Thông tin đăng nhập</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">
                    <strong>Lần đăng nhập cuối:</strong><br>
                    <?= $current_user['last_login'] ? format_datetime($current_user['last_login']) : 'Chưa có' ?>
                </p>
                <p class="mb-0">
                    <strong>Tài khoản tạo lúc:</strong><br>
                    <?= format_datetime($current_user['created_at']) ?>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
