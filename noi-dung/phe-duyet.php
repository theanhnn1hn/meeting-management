<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

$noi_dung_id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM noi_dung WHERE id = ?");
$stmt->execute([$noi_dung_id]);
$noi_dung = $stmt->fetch();

if (!$noi_dung || $noi_dung['trang_thai'] != 'cho_duyet') {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Không thể phê duyệt', 'error');
}

if ($action == 'approve') {
    $pdo->prepare("UPDATE noi_dung SET trang_thai = 'da_duyet', ngay_phe_duyet = NOW() WHERE id = ?")->execute([$noi_dung_id]);
    create_notification($noi_dung['nguoi_trinh_id'], 'Nội dung đã được phê duyệt', 
        "Nội dung '{$noi_dung['tieu_de']}' đã được phê duyệt", 'phe_duyet',
        BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, $noi_dung_id);
    log_activity('Phê duyệt nội dung', 'noi_dung', $noi_dung_id);
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Phê duyệt thành công', 'success');
} elseif ($action == 'reject') {
    $reason = $_GET['reason'] ?? '';
    $pdo->prepare("UPDATE noi_dung SET trang_thai = 'tu_choi', ly_do_tu_choi = ? WHERE id = ?")->execute([$reason, $noi_dung_id]);
    create_notification($noi_dung['nguoi_trinh_id'], 'Nội dung bị từ chối',
        "Nội dung '{$noi_dung['tieu_de']}' bị từ chối: $reason", 'tu_choi',
        BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, $noi_dung_id);
    log_activity('Từ chối nội dung', 'noi_dung', $noi_dung_id);
    redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Đã từ chối', 'success');
}
?>
