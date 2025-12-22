<?php
// =====================================================
// AUTHENTICATION - LOGIN PAGE (OPTIMIZED)
// =====================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Nếu đã đăng nhập, redirect đến dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/dashboard/');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin';
    } else {
        try {
            // Kiểm tra thông tin người dùng
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND trang_thai = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Thiết lập Session đăng nhập
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['ho_ten'] = $user['ho_ten'];
                $_SESSION['chuc_vu'] = $user['chuc_vu'];
                $_SESSION['phong_ban_id'] = $user['phong_ban_id'];
                $_SESSION['last_activity'] = time();
                
                // Cập nhật thời gian đăng nhập cuối
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                // Lưu nhật ký hoạt động bằng hàm dùng chung
                log_activity('Đăng nhập', 'users', $user['id']);
                
                header('Location: ' . BASE_URL . '/dashboard/');
                exit;
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống. Vui lòng thử lại sau';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - <?= SYSTEM_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            color: white;
            padding: 2.5rem 2rem;
            text-align: center;
        }
        .login-header i { font-size: 3.5rem; margin-bottom: 1rem; }
        .login-body { padding: 2.5rem; }
        .form-control { border-radius: 10px; padding: 0.75rem 1rem; }
        .btn-login {
            width: 100%;
            padding: 0.8rem;
            border-radius: 10px;
            font-weight: 600;
            background: #1e40af;
            border: none;
            transition: all 0.3s;
        }
        .btn-login:hover { background: #1e3a8a; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-shield-lock"></i>
            <h3><?= SYSTEM_SHORT_NAME ?></h3>
            <p class="mb-0"><?= SYSTEM_NAME ?></p>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?= e($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="username" class="form-control" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Mật khẩu</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login text-white">
                    ĐĂNG NHẬP <i class="bi bi-arrow-right-short"></i>
                </button>
            </form>
        </div>
        <div class="p-3 text-center bg-light text-muted small">
            Phiên bản 5.0 Optimized &copy; <?= date('Y') ?>
        </div>
    </div>
</body>
</html>