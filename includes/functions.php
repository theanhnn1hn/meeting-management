<?php
// =====================================================
// COMMON FUNCTIONS
// =====================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

// =====================================================
// SECURITY FUNCTIONS
// =====================================================

// CSRF Token
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

// XSS Protection
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Sanitize input
function sanitize($input) {
    return trim(strip_tags($input ?? ''));
}

// =====================================================
// NOTIFICATION FUNCTIONS
// =====================================================

function create_notification($user_id, $tieu_de, $noi_dung, $loai, $lien_ket = null, $noi_dung_id = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO thong_bao (user_id, tieu_de, noi_dung, loai, lien_ket, noi_dung_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $tieu_de, $noi_dung, $loai, $lien_ket, $noi_dung_id]);
        return true;
    } catch (PDOException $e) {
        error_log("Lỗi tạo thông báo: " . $e->getMessage());
        return false;
    }
}

// Gửi thông báo cho nhiều users
function notify_users($user_ids, $tieu_de, $noi_dung, $loai, $lien_ket = null, $noi_dung_id = null) {
    foreach ($user_ids as $user_id) {
        create_notification($user_id, $tieu_de, $noi_dung, $loai, $lien_ket, $noi_dung_id);
    }
}

// Đếm thông báo chưa đọc
function count_unread_notifications($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM thong_bao WHERE user_id = ? AND da_doc = 0");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// Lấy danh sách thông báo
function get_notifications($user_id, $limit = 10) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM thong_bao 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT ?
    ");
    $stmt->execute([$user_id, $limit]);
    return $stmt->fetchAll();
}

// =====================================================
// EMAIL FUNCTIONS (CHỈ PHÊ DUYỆT/TỪ CHỐI)
// =====================================================

function send_email_notification($to_email, $to_name, $subject, $body) {
    if (!EMAIL_ENABLED) return false;
    
    // Sử dụng PHPMailer nếu có
    // Placeholder cho việc tích hợp PHPMailer
    
    return true;
}

// =====================================================
// USER FUNCTIONS
// =====================================================

function get_current_user() {
    if (!isset($_SESSION['user_id'])) return null;
    
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT u.*, p.ten_phong 
        FROM users u
        LEFT JOIN phong_ban p ON u.phong_ban_id = p.id
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function get_user_by_id($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT u.*, p.ten_phong 
        FROM users u
        LEFT JOIN phong_ban p ON u.phong_ban_id = p.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function get_users_by_chuc_vu($chuc_vu) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE chuc_vu = ? AND trang_thai = 1 ORDER BY ho_ten");
    $stmt->execute([$chuc_vu]);
    return $stmt->fetchAll();
}

function get_users_by_phong($phong_ban_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phong_ban_id = ? AND trang_thai = 1 ORDER BY ho_ten");
    $stmt->execute([$phong_ban_id]);
    return $stmt->fetchAll();
}

// =====================================================
// PHÒNG BAN FUNCTIONS
// =====================================================

function get_all_phong_ban() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM phong_ban WHERE trang_thai = 1 ORDER BY thu_tu, ten_phong");
    return $stmt->fetchAll();
}

function get_phong_ban_by_id($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM phong_ban WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// =====================================================
// DATE/TIME FUNCTIONS
// =====================================================

function format_date($date, $format = DATE_FORMAT) {
    if (!$date) return '-';
    return date($format, strtotime($date));
}

function format_datetime($datetime, $format = DATETIME_FORMAT) {
    if (!$datetime) return '-';
    return date($format, strtotime($datetime));
}

function days_until($date) {
    if (!$date) return null;
    
    $now = new DateTime();
    $deadline = new DateTime($date);
    $diff = $now->diff($deadline);
    
    return $diff->invert ? -$diff->days : $diff->days;
}

function time_ago($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'vừa xong';
    if ($diff < 3600) return floor($diff / 60) . ' phút trước';
    if ($diff < 86400) return floor($diff / 3600) . ' giờ trước';
    if ($diff < 604800) return floor($diff / 86400) . ' ngày trước';
    
    return format_datetime($datetime);
}

// =====================================================
// ACTIVITY LOG
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

// =====================================================
// FILE UPLOAD FUNCTIONS
// =====================================================

function upload_file($file, $noi_dung_id) {
    global $pdo;
    
    // Validate file
    $allowed_ext = ALLOWED_EXTENSIONS;
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_ext)) {
        return ['success' => false, 'message' => 'Loại file không được phép'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File quá lớn (tối đa 50MB)'];
    }
    
    // Generate unique filename
    $new_filename = time() . '_' . uniqid() . '.' . $file_ext;
    $upload_path = UPLOAD_PATH . $new_filename;
    
    // Create upload directory if not exists
    if (!file_exists(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0777, true);
    }
    
    // Move file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'message' => 'Lỗi upload file'];
    }
    
    // Save to database
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tai_lieu (noi_dung_id, ten_file, duong_dan, kich_thuoc, loai_file, uploaded_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $noi_dung_id,
            $file['name'],
            $new_filename,
            $file['size'],
            $file_ext,
            $_SESSION['user_id']
        ]);
        
        return ['success' => true, 'message' => 'Upload thành công'];
    } catch (PDOException $e) {
        unlink($upload_path); // Delete file if DB insert fails
        return ['success' => false, 'message' => 'Lỗi lưu database'];
    }
}

// =====================================================
// DASHBOARD WIDGETS
// =====================================================

function get_cong_viec_uu_tien($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT nd.*, kh.ten_ky_hop, kh.deadline_hoan_thien,
               DATEDIFF(kh.deadline_hoan_thien, CURDATE()) as days_left
        FROM noi_dung nd
        JOIN ky_hop kh ON nd.ky_hop_id = kh.id
        WHERE nd.nguoi_trinh_id = ? 
        AND nd.trang_thai IN ('da_duyet', 'dang_xu_ly')
        AND nd.tien_do < 100
        AND kh.deadline_hoan_thien >= CURDATE()
        ORDER BY kh.deadline_hoan_thien ASC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

// =====================================================
// STATUS BADGE HELPER
// =====================================================

function status_badge($status, $type = 'noi_dung') {
    global $GLOBALS;
    
    $statuses = $type === 'ky_hop' ? $GLOBALS['KY_HOP_STATUS'] : $GLOBALS['NOI_DUNG_STATUS'];
    $info = $statuses[$status] ?? ['label' => $status, 'color' => 'secondary'];
    
    return "<span class='badge bg-{$info['color']}'>{$info['label']}</span>";
}

// =====================================================
// PERMISSION CHECK
// =====================================================

function require_permission($permission) {
    if (!has_permission($permission)) {
        $_SESSION['error'] = 'Bạn không có quyền truy cập chức năng này';
        header('Location: ' . BASE_URL . '/dashboard/');
        exit;
    }
}

function is_owner($noi_dung_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
    $stmt->execute([$noi_dung_id]);
    $noi_dung = $stmt->fetch();
    
    return $noi_dung && $noi_dung['nguoi_trinh_id'] == $_SESSION['user_id'];
}

// =====================================================
// REDIRECT HELPER
// =====================================================

function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION[$type] = $message;
    }
    header("Location: $url");
    exit;
}
?>
