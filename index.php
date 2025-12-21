<?php
session_start();
require_once __DIR__ . '/config/config.php';

// Nếu chưa đăng nhập, redirect đến login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

// Redirect đến dashboard
header('Location: ' . BASE_URL . '/dashboard/');
exit;
?>
