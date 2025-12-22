<?php
/**
 * FIXED VERSION - File Upload with Security Improvements
 * 
 * BUGS FIXED:
 * 1. Extension vulnerability: .php.jpg files could be uploaded
 * 2. Missing MIME type validation
 * 3. No directory traversal protection
 * 4. File size not validated server-side
 * 
 * SECURITY IMPROVEMENTS:
 * - Strict MIME type validation with finfo
 * - Whitelist file extensions only
 * - Randomized filename (no original extension preservation)
 * - Real path validation to prevent directory traversal
 * - Server-side file size limit
 * - Proper error handling
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Verify CSRF
$csrf_token = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
$user_id = $_SESSION['user_id'];

try {
    if ($noi_dung_id <= 0) {
        throw new Exception('Thiếu thông tin nội dung');
    }
    
    // Check permission
    $stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
    $stmt->execute([$noi_dung_id]);
    $noi_dung = $stmt->fetch();
    
    if (!$noi_dung) {
        throw new Exception('Nội dung không tồn tại');
    }
    
    if ($noi_dung['nguoi_trinh_id'] != $user_id) {
        throw new Exception('Không có quyền upload tài liệu cho nội dung này');
    }
    
    // Check file upload
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Lỗi upload file');
    }
    
    $file = $_FILES['file'];
    
    // ✅ FIX 1: Server-side file size validation (max 10MB)
    $max_file_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_file_size) {
        throw new Exception('File không được vượt quá 10MB');
    }
    
    if ($file['size'] <= 0) {
        throw new Exception('File rỗng');
    }
    
    // ✅ FIX 2: Validate MIME type with finfo (not just extension)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detected_mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    // Whitelist allowed MIME types
    $allowed_mimes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/zip',
        'application/x-rar-compressed'
    ];
    
    if (!in_array($detected_mime, $allowed_mimes, true)) {
        throw new Exception('Loại file không được phép. Chỉ cho phép: PDF, Word, Excel, PowerPoint, hình ảnh, text, ZIP');
    }
    
    // ✅ FIX 3: Validate file extension from original name (additional layer)
    $original_name = $file['name'];
    $path_parts = pathinfo($original_name);
    $original_ext = strtolower($path_parts['extension'] ?? '');
    
    // Whitelist extensions
    $allowed_extensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar'];
    
    if (!in_array($original_ext, $allowed_extensions, true)) {
        throw new Exception('Extension không được phép: .' . htmlspecialchars($original_ext));
    }
    
    // Map MIME to safe extension (don't trust original extension)
    $mime_to_ext = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'text/plain' => 'txt',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/zip' => 'zip',
        'application/x-rar-compressed' => 'rar'
    ];
    
    $safe_extension = $mime_to_ext[$detected_mime] ?? 'bin';
    
    // ✅ FIX 4: Generate completely random filename (no preservation of original extension pattern)
    $upload_dir = __DIR__ . '/../uploads/tai-lieu/';
    
    // ✅ FIX 5: Ensure upload directory exists and validate real path
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('Không thể tạo thư mục upload');
        }
    }
    
    // Validate realpath to prevent directory traversal
    $real_upload_dir = realpath($upload_dir);
    if ($real_upload_dir === false) {
        throw new Exception('Thư mục upload không hợp lệ');
    }
    
    // Generate unique filename with safe extension
    $unique_filename = bin2hex(random_bytes(16)) . '.' . $safe_extension;
    $file_path = $real_upload_dir . '/' . $unique_filename;
    
    // Double-check the resolved path is within upload directory
    $real_file_path = realpath(dirname($file_path));
    if ($real_file_path === false || strpos($real_file_path, $real_upload_dir) !== 0) {
        throw new Exception('Path không hợp lệ');
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Không thể lưu file');
    }
    
    // Store in database
    $stmt = $pdo->prepare("
        INSERT INTO tai_lieu (noi_dung_id, ten_file, duong_dan, kich_thuoc, loai_file, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $noi_dung_id,
        $original_name, // Keep original name for display
        'uploads/tai-lieu/' . $unique_filename, // Store relative path
        $file['size'],
        $detected_mime,
        $user_id
    ]);
    
    $tai_lieu_id = $pdo->lastInsertId();
    
    // Log activity
    log_activity(
        $user_id,
        'upload_tai_lieu',
        'Upload tài liệu: ' . $original_name . ' cho nội dung #' . $noi_dung_id,
        json_encode([
            'tai_lieu_id' => $tai_lieu_id,
            'file_size' => $file['size'],
            'mime_type' => $detected_mime
        ])
    );
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload thành công',
        'tai_lieu_id' => $tai_lieu_id,
        'file_info' => [
            'name' => $original_name,
            'size' => $file['size'],
            'type' => $detected_mime
        ]
    ]);
    
} catch (Exception $e) {
    // Clean up uploaded file if exists
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
