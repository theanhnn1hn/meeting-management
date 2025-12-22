<?php
// =====================================================
// COMMON FUNCTIONS - FINAL STABLE VERSION
// =====================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

// =====================================================
// SECURITY & DATA HANDLING
// =====================================================

// XSS Protection
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Sanitize input
function sanitize($input) {
    return trim(strip_tags($input ?? ''));
}

// CSRF Helpers
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrf_field() {
    $token = generate_csrf_token();
    return "<input type='hidden' name='csrf_token' value='$token'>";
}

// =====================================================
// AUTHENTICATION & PERMISSIONS
// =====================================================

function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/auth/login.php');
        exit;
    }
}

// Hàm require_role duy nhất (Đã xóa bản trùng lặp)
function require_role($allowed_roles) {
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    if (!in_array($_SESSION['chuc_vu'] ?? '', $allowed_roles)) {
        redirect(BASE_URL . '/dashboard/', 'Bạn không có quyền truy cập trang này', 'error');
    }
}

function is_owner($noi_dung_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
    $stmt->execute([$noi_dung_id]);
    $nguoi_trinh_id = $stmt->fetchColumn();
    return $nguoi_trinh_id == ($_SESSION['user_id'] ?? 0);
}

// =====================================================
// LOGGING & NOTIFICATIONS
// =====================================================

// Sửa lỗi tham số: Hàm tự lấy user_id từ Session
function log_activity($hanh_dong, $bang = null, $ban_ghi_id = null, $chi_tiet = null) {
    global $pdo;
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, hanh_dong, bang, ban_ghi_id, chi_tiet, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $hanh_dong, $bang, $ban_ghi_id, $chi_tiet, $ip, $user_agent]);
    } catch (PDOException $e) {
        error_log("Lỗi log activity: " . $e->getMessage());
    }
}

function create_notification($user_id, $tieu_de, $noi_dung, $loai, $lien_ket = null, $noi_dung_id = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO thong_bao (user_id, tieu_de, noi_dung, loai, lien_ket, noi_dung_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$user_id, $tieu_de, $noi_dung, $loai, $lien_ket, $noi_dung_id]);
    } catch (PDOException $e) {
        error_log("Lỗi tạo thông báo: " . $e->getMessage());
        return false;
    }
}

function notify_users($user_ids, $tieu_de, $noi_dung, $loai, $lien_ket = null, $noi_dung_id = null) {
    foreach ($user_ids as $id) {
        create_notification($id, $tieu_de, $noi_dung, $loai, $lien_ket, $noi_dung_id);
    }
}

// =====================================================
// UTILITIES
// =====================================================

function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: $url");
    exit;
}

function format_date($date) {
    return $date ? date(DATE_FORMAT, strtotime($date)) : '-';
}

function format_datetime($datetime) {
    return $datetime ? date(DATETIME_FORMAT, strtotime($datetime)) : '-';
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    if ($diff < 60) return 'vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    return format_date($datetime);
}

function get_current_user() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) return null;
    $stmt = $pdo->prepare("SELECT u.*, p.ten_phong FROM users u LEFT JOIN phong_ban p ON u.phong_ban_id = p.id WHERE u.id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function count_unread_notifications($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM thong_bao WHERE user_id = ? AND da_doc = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

function status_badge($status, $type = 'noi_dung') {
    global $GLOBALS;
    $statuses = ($type === 'ky_hop') ? $GLOBALS['KY_HOP_STATUS'] : $GLOBALS['NOI_DUNG_STATUS'];
    $info = $statuses[$status] ?? ['label' => $status, 'color' => 'secondary'];
    return "<span class='badge bg-{$info['color']}'>{$info['label']}</span>";
}
?>