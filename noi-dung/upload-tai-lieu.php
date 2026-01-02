<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Xác định action
$action = $_GET['action'] ?? 'upload';

if ($action === 'delete') {
    // XÓA TÀI LIỆU
    $tai_lieu_id = (int)($_POST['id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    try {
        if ($tai_lieu_id <= 0) {
            throw new Exception('ID tài liệu không hợp lệ');
        }

        // Lấy thông tin tài liệu
        $stmt = $pdo->prepare("
            SELECT tl.*, nd.nguoi_trinh_id
            FROM tai_lieu tl
            JOIN noi_dung nd ON tl.noi_dung_id = nd.id
            WHERE tl.id = ?
        ");
        $stmt->execute([$tai_lieu_id]);
        $tai_lieu = $stmt->fetch();

        if (!$tai_lieu) {
            throw new Exception('Tài liệu không tồn tại');
        }

        // Kiểm tra quyền: chỉ người trình hoặc lãnh đạo mới được xóa
        $is_owner = ($tai_lieu['nguoi_trinh_id'] == $_SESSION['user_id']);
        $is_uploader = ($tai_lieu['uploaded_by'] == $_SESSION['user_id']);
        $is_leader = in_array($_SESSION['chuc_vu'] ?? '', [ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]);

        if (!$is_owner && !$is_uploader && !$is_leader) {
            throw new Exception('Bạn không có quyền xóa tài liệu này');
        }

        // Xóa file vật lý
        $file_path = __DIR__ . '/../' . $tai_lieu['duong_dan'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }

        // Xóa khỏi database
        $stmt = $pdo->prepare("DELETE FROM tai_lieu WHERE id = ?");
        $stmt->execute([$tai_lieu_id]);

        log_activity('Xóa tài liệu', 'tai_lieu', $tai_lieu_id, $tai_lieu['ten_file']);

        echo json_encode(['success' => true, 'message' => 'Xóa tài liệu thành công']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// UPLOAD TÀI LIỆU (action mặc định)
// Verify CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
$user_id = $_SESSION['user_id'];

try {
    if ($noi_dung_id <= 0) throw new Exception('Thiếu thông tin nội dung');
    
    // Check file
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Lỗi upload file');
    }
    
    $file = $_FILES['file'];
    if ($file['size'] > 10 * 1024 * 1024) throw new Exception('File không được vượt quá 10MB');

    // MIME validation
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $mime_to_ext = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png'
    ];

    if (!array_key_exists($detected_mime, $mime_to_ext)) {
        throw new Exception('Loại file không được phép');
    }

    $upload_dir = __DIR__ . '/../uploads/tai-lieu/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    $original_name = $file['name'];
    $unique_filename = bin2hex(random_bytes(16)) . '.' . $mime_to_ext[$detected_mime];
    $file_path = $upload_dir . $unique_filename;

    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Không thể lưu file');
    }

    $stmt = $pdo->prepare("INSERT INTO tai_lieu (noi_dung_id, ten_file, duong_dan, kich_thuoc, loai_file, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$noi_dung_id, $original_name, 'uploads/tai-lieu/' . $unique_filename, $file['size'], $mime_to_ext[$detected_mime], $user_id]);
    
    $tai_lieu_id = $pdo->lastInsertId();

    // ✅ FIX: Sửa lại tham số log_activity cho đúng định nghĩa hàm
    log_activity('Upload tài liệu', 'tai_lieu', $tai_lieu_id, 'File: ' . $original_name . ' cho nội dung #' . $noi_dung_id);

    echo json_encode(['success' => true, 'message' => 'Upload thành công', 'name' => $original_name]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>