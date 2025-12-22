<?php
/**
 * FIXED VERSION - Comment API with SQL Injection Protection
 * 
 * BUGS FIXED:
 * - SQL Injection risk: $loai không được validate trước INSERT
 * - Wrong parameter order in create_notification()
 * - Solution: Whitelist validation cho $loai, fix parameter order
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

$action = $_POST['action'] ?? '';
$csrf_token = $_POST['csrf_token'] ?? '';

// Verify CSRF
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'add':
            $noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
            $loai = trim($_POST['loai'] ?? '');
            $noi_dung_comment = trim($_POST['noi_dung'] ?? '');
            
            // ✅ FIX: Validate $loai with whitelist
            $allowed_loai = ['gop_y', 'doc_viec', 'yeu_cau_sua'];
            if (!in_array($loai, $allowed_loai, true)) {
                throw new Exception('Loại comment không hợp lệ');
            }
            
            if ($noi_dung_id <= 0 || empty($noi_dung_comment)) {
                throw new Exception('Thiếu thông tin');
            }
            
            // Check permission
            $stmt = $pdo->prepare("SELECT id FROM noi_dung WHERE id = ?");
            $stmt->execute([$noi_dung_id]);
            if (!$stmt->fetch()) {
                throw new Exception('Nội dung không tồn tại');
            }
            
            // Insert comment - Now safe with validated $loai
            $stmt = $pdo->prepare("
                INSERT INTO comments (noi_dung_id, user_id, loai, noi_dung, trang_thai)
                VALUES (?, ?, ?, ?, 'chua_xu_ly')
            ");
            $stmt->execute([$noi_dung_id, $user_id, $loai, $noi_dung_comment]);
            
            $comment_id = $pdo->lastInsertId();
            
            // Create notification for content owner
            $stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
            $stmt->execute([$noi_dung_id]);
            $nguoi_trinh_id = $stmt->fetchColumn();
            
            if ($nguoi_trinh_id && $nguoi_trinh_id != $user_id) {
                $loai_text = [
                    'gop_y' => 'Góp ý',
                    'doc_viec' => 'Đốc việc',
                    'yeu_cau_sua' => 'Yêu cầu sửa'
                ];
                
                // ✅ FIX: Correct parameter order
                // create_notification($user_id, $tieu_de, $noi_dung, $loai, $lien_ket, $noi_dung_id)
                create_notification(
                    $nguoi_trinh_id,                                          // $user_id
                    $loai_text[$loai] . ' mới',                              // $tieu_de
                    'Bạn có ' . strtolower($loai_text[$loai]) . ' mới: ' . mb_substr($noi_dung_comment, 0, 100), // $noi_dung
                    $loai,                                                    // $loai (enum)
                    BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, // $lien_ket
                    $noi_dung_id                                              // $noi_dung_id
                );
            }
            
            // Log activity
            log_activity(
                'Thêm ' . $loai . ' cho nội dung #' . $noi_dung_id,
                'comments',
                $comment_id,
                json_encode(['comment_id' => $comment_id, 'loai' => $loai])
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã thêm ' . $loai_text[$loai],
                'comment_id' => $comment_id
            ]);
            break;
            
        case 'update_status':
            $comment_id = (int)($_POST['comment_id'] ?? 0);
            $trang_thai = trim($_POST['trang_thai'] ?? '');
            
            // ✅ FIX: Validate $trang_thai with whitelist
            $allowed_trang_thai = ['chua_xu_ly', 'dang_xu_ly', 'da_hoan_thanh'];
            if (!in_array($trang_thai, $allowed_trang_thai, true)) {
                throw new Exception('Trạng thái không hợp lệ');
            }
            
            if ($comment_id <= 0) {
                throw new Exception('Thiếu thông tin');
            }
            
            // Check permission - only content owner can update
            $stmt = $pdo->prepare("
                SELECT c.id, nd.nguoi_trinh_id
                FROM comments c
                JOIN noi_dung nd ON c.noi_dung_id = nd.id
                WHERE c.id = ?
            ");
            $stmt->execute([$comment_id]);
            $comment = $stmt->fetch();
            
            if (!$comment) {
                throw new Exception('Comment không tồn tại');
            }
            
            if ($comment['nguoi_trinh_id'] != $user_id) {
                throw new Exception('Không có quyền');
            }
            
            // Update status - Now safe
            $stmt = $pdo->prepare("
                UPDATE comments 
                SET trang_thai = ?, 
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$trang_thai, $comment_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã cập nhật trạng thái'
            ]);
            break;
            
        case 'delete':
            $comment_id = (int)($_POST['comment_id'] ?? 0);
            
            if ($comment_id <= 0) {
                throw new Exception('Thiếu thông tin');
            }
            
            // Check permission - only comment creator can delete
            $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            $comment_user_id = $stmt->fetchColumn();
            
            if (!$comment_user_id) {
                throw new Exception('Comment không tồn tại');
            }
            
            if ($comment_user_id != $user_id && !in_array($_SESSION['chuc_vu'] ?? '', ['chanh_vp', 'pho_cvp'])) {
                throw new Exception('Không có quyền xóa');
            }
            
            $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
            $stmt->execute([$comment_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Đã xóa comment'
            ]);
            break;
            
        default:
            throw new Exception('Action không hợp lệ');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
