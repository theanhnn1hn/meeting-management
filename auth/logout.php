<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Log activity
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, hanh_dong, ip_address) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], 'Đăng xuất', $_SERVER['REMOTE_ADDR']]);
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// Hủy session
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

session_destroy();

// Redirect về trang login
header('Location: ' . __DIR__ . '/login.php');
exit;
?>
