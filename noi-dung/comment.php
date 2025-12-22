<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];
$loai_text = ['gop_y' => 'Góp ý', 'doc_viec' => 'Đốc việc', 'yeu_cau_sua' => 'Yêu cầu sửa'];

try {
    if ($action === 'add') {
        $noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
        $loai = $_POST['loai'] ?? '';
        $comment = trim($_POST['noi_dung'] ?? '');

        if (!array_key_exists($loai, $loai_text) || empty($comment)) {
            throw new Exception('Dữ liệu không hợp lệ');
        }

        $stmt = $pdo->prepare("INSERT INTO comments (noi_dung_id, user_id, loai, noi_dung, trang_thai) VALUES (?, ?, ?, ?, 'chua_xu_ly')");
        $stmt->execute([$noi_dung_id, $user_id, $loai, $comment]);
        $comment_id = $pdo->lastInsertId();

        // Notify content owner
        $stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
        $stmt->execute([$noi_dung_id]);
        $owner_id = $stmt->fetchColumn();

        if ($owner_id && $owner_id != $user_id) {
            create_notification($owner_id, $loai_text[$loai] . ' mới', "Bạn có góp ý mới cho nội dung của mình", $loai, BASE_URL . "/noi-dung/chi-tiet.php?id=$noi_dung_id", $noi_dung_id);
        }

        // ✅ FIX: Sửa lại tham số log_activity cho đúng định nghĩa hàm
        log_activity('Thêm ' . $loai_text[$loai], 'comments', $comment_id, 'Nội dung: ' . mb_substr($comment, 0, 50));

        echo json_encode(['success' => true, 'message' => 'Đã gửi ' . $loai_text[$loai]]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>