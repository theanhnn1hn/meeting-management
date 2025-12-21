<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
$comment_text = sanitize($_POST['comment'] ?? '');
$loai = $_POST['loai'] ?? 'gop_y';

if ($noi_dung_id <= 0 || empty($comment_text)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Kiểm tra quyền
$stmt = $pdo->prepare("SELECT nd.*, pb.id as phong_ban_id FROM noi_dung nd LEFT JOIN users u ON nd.nguoi_trinh_id = u.id LEFT JOIN phong_ban pb ON u.phong_ban_id = pb.id WHERE nd.id = ?");
$stmt->execute([$noi_dung_id]);
$noi_dung = $stmt->fetch();

if (!$noi_dung) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy nội dung']);
    exit;
}

$chuc_vu = $_SESSION['chuc_vu'];
$can_comment = false;

// Kiểm tra quyền comment
if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP])) {
    $can_comment = true;
} elseif (in_array($chuc_vu, [ROLE_TRUONG_PHONG, ROLE_PHO_PHONG])) {
    // Trưởng/Phó phòng chỉ comment cho nội dung của phòng mình
    if ($noi_dung['phong_ban_id'] == $_SESSION['phong_ban_id']) {
        $can_comment = true;
    }
} elseif ($noi_dung['nguoi_trinh_id'] == $_SESSION['user_id']) {
    // Người trình có thể comment
    $can_comment = true;
}

if (!$can_comment) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền comment']);
    exit;
}

try {
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (noi_dung_id, user_id, noi_dung_comment, loai) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$noi_dung_id, $_SESSION['user_id'], $comment_text, $loai]);
    
    // Tạo thông báo
    $user_ids_to_notify = [$noi_dung['nguoi_trinh_id']];
    
    // Nếu là đốc việc, thông báo cho Trưởng phòng
    if ($loai === 'doc_viec' && $noi_dung['phong_ban_id']) {
        $stmt = $pdo->prepare("SELECT truong_phong_id FROM phong_ban WHERE id = ?");
        $stmt->execute([$noi_dung['phong_ban_id']]);
        $phong = $stmt->fetch();
        if ($phong && $phong['truong_phong_id']) {
            $user_ids_to_notify[] = $phong['truong_phong_id'];
        }
    }
    
    $user_ids_to_notify = array_unique($user_ids_to_notify);
    $user_ids_to_notify = array_filter($user_ids_to_notify, function($id) {
        return $id != $_SESSION['user_id'];
    });
    
    $loai_labels = $GLOBALS['COMMENT_TYPES'];
    $tieu_de = $loai_labels[$loai]['label'] . ' mới';
    $noi_dung_tb = $_SESSION['ho_ten'] . ' đã ' . strtolower($loai_labels[$loai]['label']) . ' cho nội dung "' . $noi_dung['tieu_de'] . '"';
    
    notify_users(
        $user_ids_to_notify,
        $tieu_de,
        $noi_dung_tb,
        $loai === 'doc_viec' ? 'doc_viec' : 'comment',
        BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id,
        $noi_dung_id
    );
    
    log_activity('Thêm comment ' . $loai, 'comments', $pdo->lastInsertId(), 'Nội dung ID: ' . $noi_dung_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Đã thêm ' . strtolower($loai_labels[$loai]['label'])
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
}
?>
