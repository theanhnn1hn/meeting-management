<?php
// Thêm require_once functions.php vào đầu file
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
                
                // Sử dụng hàm log_activity tập trung (Đã sửa ở đây)
                log_activity('Đăng nhập', 'users', $user['id']);
                
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