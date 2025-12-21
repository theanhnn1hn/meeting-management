<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_permission('phe_duyet');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Invalid request', 'error');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Token không hợp lệ', 'error');
}

$noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
$ly_do = sanitize($_POST['ly_do'] ?? '');

if ($noi_dung_id <= 0 || empty($ly_do)) {
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Vui lòng nhập lý do từ chối', 'error');
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

// Kiểm tra quyền phê duyệt
if ($noi_dung['nguoi_phe_duyet_id'] != $_SESSION['user_id']) {
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Bạn không có quyền phê duyệt nội dung này', 'error');
}

if ($noi_dung['trang_thai'] !== 'cho_duyet') {
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Nội dung không ở trạng thái chờ duyệt', 'error');
}

try {
    $pdo->beginTransaction();
    
    // Cập nhật trạng thái
    $stmt = $pdo->prepare("
        UPDATE noi_dung 
        SET trang_thai = 'tu_choi', 
            ly_do_tu_choi = ?,
            ngay_phe_duyet = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$ly_do, $noi_dung_id]);
    
    // Tạo thông báo cho người trình
    create_notification(
        $noi_dung['nguoi_trinh_id'],
        'Nội dung bị từ chối',
        'Nội dung "' . $noi_dung['tieu_de'] . '" đã bị từ chối. Lý do: ' . $ly_do,
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
            'Nội dung "' . $noi_dung['tieu_de'] . '" đã bị từ chối bởi ' . $_SESSION['ho_ten'] . '. Lý do: ' . $ly_do
        );
    }
    
    log_activity('Từ chối nội dung', 'noi_dung', $noi_dung_id, 'Lý do: ' . $ly_do);
    
    $pdo->commit();
    
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Đã từ chối nội dung', 'success');
    
} catch (PDOException $e) {
    $pdo->rollBack();
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Lỗi từ chối nội dung: ' . $e->getMessage(), 'error');
}
?>
