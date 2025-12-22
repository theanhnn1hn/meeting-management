<?php
/**
 * PHÊ DUYỆT NỘI DUNG - FIXED VERSION
 * 
 * BUG FIXED:
 * - Lỗi 2: CSRF vulnerability - chuyển từ GET sang POST
 * - Lỗi 3: Thiếu kiểm tra quyền định danh (nguoi_phe_duyet_id)
 * 
 * SECURITY IMPROVEMENTS:
 * - Sử dụng POST method cho tất cả thao tác thay đổi dữ liệu
 * - Verify CSRF token
 * - Kiểm tra chỉ người được phân công mới được phê duyệt/từ chối
 * - Validate trạng thái nội dung
 */

require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Yêu cầu quyền Chánh VP hoặc Phó CVP
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

// ✅ FIX: Chỉ chấp nhận POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Phương thức không hợp lệ', 'error');
}

// ✅ FIX: Verify CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Token không hợp lệ', 'error');
}

$noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
$action = trim($_POST['action'] ?? '');

// Validate input
if ($noi_dung_id <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Thông tin không hợp lệ', 'error');
}

// Lấy thông tin nội dung
$stmt = $pdo->prepare("
    SELECT nd.*, u.ho_ten as nguoi_trinh, u.email
    FROM noi_dung nd
    JOIN users u ON nd.nguoi_trinh_id = u.id
    WHERE nd.id = ?
");
$stmt->execute([$noi_dung_id]);
$noi_dung = $stmt->fetch();

if (!$noi_dung) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Không tìm thấy nội dung', 'error');
}

// ✅ FIX LỖI 3: Kiểm tra quyền định danh - chỉ người được phân công mới được phê duyệt
if ($noi_dung['nguoi_phe_duyet_id'] != $_SESSION['user_id']) {
    redirect(
        BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 
        'Bạn không được phân công phê duyệt nội dung này', 
        'error'
    );
}

// Validate trạng thái
if ($noi_dung['trang_thai'] !== 'cho_duyet') {
    redirect(
        BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 
        'Nội dung không ở trạng thái chờ duyệt', 
        'error'
    );
}

try {
    $pdo->beginTransaction();
    
    if ($action === 'approve') {
        // PHÊ DUYỆT
        $stmt = $pdo->prepare("
            UPDATE noi_dung 
            SET trang_thai = 'da_duyet', 
                ngay_phe_duyet = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$noi_dung_id]);
        
        // Tạo thông báo cho người trình
        create_notification(
            $noi_dung['nguoi_trinh_id'],
            'Nội dung đã được phê duyệt',
            'Nội dung "' . $noi_dung['tieu_de'] . '" đã được phê duyệt bởi ' . $_SESSION['ho_ten'],
            'phe_duyet',
            BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id,
            $noi_dung_id
        );
        
        // Gửi email nếu được bật
        if (EMAIL_ENABLED && !empty($noi_dung['email'])) {
            send_email_notification(
                $noi_dung['email'],
                $noi_dung['nguoi_trinh'],
                'Nội dung đã được phê duyệt - ' . $noi_dung['tieu_de'],
                'Nội dung "' . $noi_dung['tieu_de'] . '" đã được phê duyệt bởi ' . $_SESSION['ho_ten'] . '. Vui lòng tiến hành hoàn thiện hồ sơ.'
            );
        }
        
        log_activity('Phê duyệt nội dung', 'noi_dung', $noi_dung_id, 'Nội dung: ' . $noi_dung['tieu_de']);
        
        $pdo->commit();
        
        redirect(
            BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 
            'Phê duyệt nội dung thành công', 
            'success'
        );
        
    } elseif ($action === 'reject') {
        // TỪ CHỐI
        $ly_do = trim($_POST['ly_do'] ?? '');
        
        if (empty($ly_do)) {
            $pdo->rollBack();
            redirect(
                BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 
                'Vui lòng nhập lý do từ chối', 
                'error'
            );
        }
        
        $stmt = $pdo->prepare("
            UPDATE noi_dung 
            SET trang_thai = 'tu_choi', 
                ly_do_tu_choi = ?,
                ngay_phe_duyet = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$ly_do, $noi_dung_id]);
        
        // Tạo thông báo cho người trình
        create_notification(
            $noi_dung['nguoi_trinh_id'],
            'Nội dung bị từ chối',
            'Nội dung "' . $noi_dung['tieu_de'] . '" bị từ chối. Lý do: ' . $ly_do,
            'tu_choi',
            BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id,
            $noi_dung_id
        );
        
        // Gửi email nếu được bật
        if (EMAIL_ENABLED && !empty($noi_dung['email'])) {
            send_email_notification(
                $noi_dung['email'],
                $noi_dung['nguoi_trinh'],
                'Nội dung bị từ chối - ' . $noi_dung['tieu_de'],
                'Nội dung "' . $noi_dung['tieu_de'] . '" bị từ chối bởi ' . $_SESSION['ho_ten'] . '. Lý do: ' . $ly_do
            );
        }
        
        log_activity('Từ chối nội dung', 'noi_dung', $noi_dung_id, 'Lý do: ' . $ly_do);
        
        $pdo->commit();
        
        redirect(
            BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 
            'Đã từ chối nội dung', 
            'success'
        );
    }
    
} catch (PDOException $e) {
    $pdo->rollBack();
    
    error_log('Lỗi phê duyệt nội dung: ' . $e->getMessage());
    
    redirect(
        BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 
        'Lỗi xử lý: ' . $e->getMessage(), 
        'error'
    );
}
?>
