<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHANH_VP]);

$id = $_GET['id'] ?? 0;

// Lấy thông tin kỳ họp
$stmt = $pdo->prepare("SELECT * FROM ky_hop WHERE id = ?");
$stmt->execute([$id]);
$ky_hop = $stmt->fetch();

if (!$ky_hop) {
    redirect(BASE_URL . '/ky-hop/danh-sach.php', 'Không tìm thấy kỳ họp', 'error');
}

// Kiểm tra có nội dung không
$stmt = $pdo->prepare("SELECT COUNT(*) FROM noi_dung WHERE ky_hop_id = ?");
$stmt->execute([$id]);
$count_noi_dung = $stmt->fetchColumn();

if ($count_noi_dung > 0) {
    redirect(
        BASE_URL . '/ky-hop/chi-tiet.php?id=' . $id,
        "Không thể xóa kỳ họp vì có {$count_noi_dung} nội dung liên quan. Vui lòng xóa nội dung trước.",
        'error'
    );
}

try {
    // Xóa kỳ họp
    $stmt = $pdo->prepare("DELETE FROM ky_hop WHERE id = ?");
    $stmt->execute([$id]);
    
    log_activity('Xóa kỳ họp', 'ky_hop', $id, $ky_hop['ten_ky_hop']);
    
    redirect(BASE_URL . '/ky-hop/danh-sach.php', 'Xóa kỳ họp thành công', 'success');
} catch (PDOException $e) {
    redirect(BASE_URL . '/ky-hop/chi-tiet.php?id=' . $id, 'Lỗi xóa kỳ họp: ' . $e->getMessage(), 'error');
}
?>
