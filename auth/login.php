<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

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
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND trang_thai = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['ho_ten'] = $user['ho_ten'];
                $_SESSION['chuc_vu'] = $user['chuc_vu'];
                $_SESSION['phong_ban_id'] = $user['phong_ban_id'];
                
                // Cập nhật last login
                $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
                
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, hanh_dong, ip_address) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], 'Đăng nhập', $_SERVER['REMOTE_ADDR']]);
                
                header('Location: ' . BASE_URL . '/dashboard/');
                exit;
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
            }
        } catch (PDOException $e) {
            $error = 'Lỗi hệ thống. Vui lòng thử lại sau';
            error_log($e->getMessage());
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            padding: 3rem 2rem 2rem;
            text-align: center;
        }
        
        .login-header i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .login-header h2 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 2.5rem;
        }
        
        .form-floating {
            margin-bottom: 1.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            padding: 1rem 0.75rem;
            font-size: 0.95rem;
        }
        
        .form-control:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            font-weight: 600;
            border-radius: 10px;
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            border: none;
            color: white;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(30,64,175,0.3);
        }
        
        .alert {
            border-radius: 10px;
            border: none;
        }
        
        .login-footer {
            text-align: center;
            padding: 1.5rem;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
            <i class="bi bi-calendar-check"></i>
            <h2><?= SYSTEM_SHORT_NAME ?></h2>
            <p><?= SYSTEM_NAME ?></p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Tên đăng nhập" required autofocus>
                    <label for="username"><i class="bi bi-person"></i> Tên đăng nhập</label>
                </div>
                
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Mật khẩu" required>
                    <label for="password"><i class="bi bi-lock"></i> Mật khẩu</label>
                </div>
                
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="remember">
                    <label class="form-check-label" for="remember">Ghi nhớ đăng nhập</label>
                </div>
                
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> Đăng nhập
                </button>
            </form>
        </div>
        
        <div class="login-footer">
            © 2025 UBND Tỉnh. Phiên bản 5.0
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
