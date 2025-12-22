<?php
// =====================================================
// COMMON FUNCTIONS - FINAL STABLE & FIXED VERSION
// =====================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

// =====================================================
// SECURITY & DATA HANDLING
// =====================================================

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function sanitize($input) {
    return trim(strip_tags($input ?? ''));
}

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

function require_role($allowed_roles) {
    if (!is_array($allowed_roles)) $allowed_roles = [$allowed_roles];
    if (!in_array($_SESSION['chuc_vu'] ?? '', $allowed_roles)) {
        redirect(BASE_URL . '/dashboard/', 'Bạn không có quyền truy cập trang này', 'error');
    }
}

// Bổ sung hàm kiểm tra quyền chi tiết
function require_permission($permission) {
    if (!has_permission($permission)) {
        redirect(BASE_URL . '/dashboard/', 'Bạn không có quyền thực hiện thao tác này', 'error');
    }
}

function is_owner($noi_dung_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
    $stmt->execute([$noi_dung_id]);
    $nguoi_trinh_id = $stmt->fetchColumn();
    // FIXED: So sánh nghiêm ngặt để tránh lỗi false == 0
    return $nguoi_trinh_id !== false && (int)$nguoi_trinh_id === (int)($_SESSION['user_id'] ?? 0);
}

// =====================================================
// LOGGING & NOTIFICATIONS
// =====================================================

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
// UTILITIES & DATA FETCHING
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

function days_until($date) {
    $now = new DateTime();
    $target = new DateTime($date);
    $interval = $now->diff($target);
    return (int)$interval->format('%R%a');
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
    // FIXED: Không lấy cột password để bảo mật
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.ho_ten, u.email, u.chuc_vu, u.phong_ban_id, p.ten_phong 
        FROM users u 
        LEFT JOIN phong_ban p ON u.phong_ban_id = p.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function get_users_by_chuc_vu($chuc_vu) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, ho_ten FROM users WHERE chuc_vu = ? AND trang_thai = 1");
    $stmt->execute([$chuc_vu]);
    return $stmt->fetchAll();
}

function get_users_by_phong($phong_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, ho_ten FROM users WHERE phong_ban_id = ? AND trang_thai = 1");
    $stmt->execute([$phong_id]);
    return $stmt->fetchAll();
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

// Bổ sung hàm xử lý upload file tập trung
function upload_file($file, $noi_dung_id) {
    global $pdo;
    
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (in_array($fileExt, ALLOWED_EXTENSIONS)) {
        if ($fileError === 0) {
            if ($fileSize <= MAX_FILE_SIZE) {
                $fileNameNew = uniqid('', true) . "." . $fileExt;
                $fileDestination = UPLOAD_PATH . $fileNameNew;
                
                if (move_uploaded_file($fileTmpName, $fileDestination)) {
                    $stmt = $pdo->prepare("INSERT INTO tai_lieu (noi_dung_id, ten_file, duong_dan, kich_thuoc, loai_file, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$noi_dung_id, $fileName, $fileNameNew, $fileSize, $fileExt, $_SESSION['user_id']]);
                    return true;
                }
            }
        }
    }
    return false;
}
?>