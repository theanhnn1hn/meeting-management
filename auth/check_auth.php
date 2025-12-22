<?php
// =====================================================
// AUTHENTICATION MIDDLEWARE - FIXED VERSION
// =====================================================

// Load config (đã có session_start inside)
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
?>