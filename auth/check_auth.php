<?php
// =====================================================
// AUTHENTICATION MIDDLEWARE
// =====================================================

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập để tiếp tục';
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Kiểm tra session timeout (8 giờ)
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600 * 8)) {
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '/auth/login.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time();

// Helper function kiểm tra quyền truy cập
function require_role($allowed_roles = []) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    $current_role = $_SESSION['chuc_vu'] ?? '';
    
    if (!in_array($current_role, $allowed_roles)) {
        $_SESSION['error'] = 'Bạn không có quyền truy cập trang này';
        header('Location: ' . BASE_URL . '/dashboard/');
        exit;
    }
}
?>
