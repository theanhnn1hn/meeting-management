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
$mo_ta = sanitize($_POST['mo_ta'] ?? '');

if ($noi_dung_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

// Kiểm tra quyền - chỉ người trình mới upload được
$stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
$stmt->execute([$noi_dung_id]);
$noi_dung = $stmt->fetch();

if (!$noi_dung || $noi_dung['nguoi_trinh_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền upload tài liệu']);
    exit;
}

// Kiểm tra file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Không có file upload hoặc file lỗi']);
    exit;
}

$file = $_FILES['file'];

// Validate file
$allowed_ext = ALLOWED_EXTENSIONS;
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($file_ext, $allowed_ext)) {
    echo json_encode(['success' => false, 'message' => 'Loại file không được phép. Chỉ chấp nhận: ' . implode(', ', $allowed_ext)]);
    exit;
}

if ($file['size'] > MAX_FILE_SIZE) {
    $max_mb = MAX_FILE_SIZE / 1024 / 1024;
    echo json_encode(['success' => false, 'message' => 'File quá lớn (tối đa ' . $max_mb . 'MB)']);
    exit;
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
    echo json_encode(['success' => false, 'message' => 'Lỗi upload file']);
    exit;
}

// Save to database
try {
    $stmt = $pdo->prepare("
        INSERT INTO tai_lieu (noi_dung_id, ten_file, duong_dan, kich_thuoc, loai_file, mo_ta, uploaded_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $noi_dung_id,
        $file['name'],
        $new_filename,
        $file['size'],
        $file_ext,
        $mo_ta,
        $_SESSION['user_id']
    ]);
    
    $file_id = $pdo->lastInsertId();
    
    log_activity('Upload tài liệu', 'tai_lieu', $file_id, 'Nội dung ID: ' . $noi_dung_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload thành công',
        'file_id' => $file_id,
        'file_name' => $file['name'],
        'file_size' => $file['size']
    ]);
    
} catch (PDOException $e) {
    // Delete file if DB insert fails
    if (file_exists($upload_path)) {
        unlink($upload_path);
    }
    echo json_encode(['success' => false, 'message' => 'Lỗi lưu database: ' . $e->getMessage()]);
}
?>
