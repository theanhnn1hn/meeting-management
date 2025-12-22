<?php
// =====================================================
// SYSTEM CONFIGURATION - FIXED VERSION
// =====================================================

// Ngăn lỗi "Headers already sent" khi redirect
ob_start();

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Session config - Khởi tạo tập trung tại đây
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 3600 * 8); // 8 hours
    session_start();
}

// Base URL
define('BASE_URL', 'http://localhost/meeting-management');

// Upload config
define('UPLOAD_PATH', __DIR__ . '/../uploads/tai-lieu/');
define('UPLOAD_URL', BASE_URL . '/uploads/tai-lieu/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar']);

// Email config
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('SMTP_FROM_EMAIL', 'noreply@province.gov.vn');
define('SMTP_FROM_NAME', 'Hệ thống Quản lý Kỳ họp');
define('EMAIL_ENABLED', false);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Notification config
define('NOTIFICATION_POLL_INTERVAL', 30); // seconds

// Date format
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');

// System name
define('SYSTEM_NAME', 'Hệ thống Quản lý Kỳ họp UBND Tỉnh');
define('SYSTEM_SHORT_NAME', 'QLKH');
?>