<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Phương thức không hợp lệ', 'error');
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Token không hợp lệ', 'error');
}

$noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM noi_dung WHERE id = ?");
$stmt->execute([$noi_dung_id]);
$noi_dung = $stmt->fetch();

if (!$noi_dung || $noi_dung['trang_thai'] !== 'cho_duyet') {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Nội dung không hợp lệ để phê duyệt', 'error');
}

// Kiểm tra quyền: Chỉ người được phân công mới được phê duyệt
if ($noi_dung['nguoi_phe_duyet_id'] != $_SESSION['user_id']) {
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Bạn không được phân công phê duyệt nội dung này', 'error');
}

try {
    $pdo->beginTransaction();
    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE noi_dung SET trang_thai = 'da_duyet', ngay_phe_duyet = NOW() WHERE id = ?");
        $stmt->execute([$noi_dung_id]);
        
        create_notification($noi_dung['nguoi_trinh_id'], 'Nội dung được phê duyệt', "Nội dung '{$noi_dung['tieu_de']}' đã được phê duyệt", 'phe_duyet', BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, $noi_dung_id);
        log_activity('Phê duyệt nội dung', 'noi_dung', $noi_dung_id);
        
    } elseif ($action === 'reject') {
        $reason = sanitize($_POST['ly_do'] ?? '');
        if (empty($reason)) throw new Exception("Vui lòng nhập lý do từ chối");
        
        $stmt = $pdo->prepare("UPDATE noi_dung SET trang_thai = 'tu_choi', ly_do_tu_choi = ? WHERE id = ?");
        $stmt->execute([$reason, $noi_dung_id]);
        
        create_notification($noi_dung['nguoi_trinh_id'], 'Nội dung bị từ chối', "Nội dung '{$noi_dung['tieu_de']}' bị từ chối. Lý do: $reason", 'tu_choi', BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, $noi_dung_id);
        log_activity('Từ chối nội dung', 'noi_dung', $noi_dung_id, $reason);
    }
    
    $pdo->commit();
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Xử lý thành công', 'success');
} catch (Exception $e) {
    $pdo->rollBack();
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, $e->getMessage(), 'error');
}
?>