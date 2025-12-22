<?php
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
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'mark_all_read':
            $stmt = $pdo->prepare("
                UPDATE thong_bao 
                SET da_doc = 1, ngay_doc = NOW() 
                WHERE nguoi_nhan_id = ? AND da_doc = 0
            ");
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đã đánh dấu tất cả đã đọc',
                'updated' => $stmt->rowCount()
            ]);
            break;
            
        case 'delete':
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Thiếu thông tin');
            }
            
            // Check ownership
            $stmt = $pdo->prepare("SELECT id FROM thong_bao WHERE id = ? AND nguoi_nhan_id = ?");
            $stmt->execute([$id, $user_id]);
            
            if (!$stmt->fetch()) {
                throw new Exception('Không có quyền');
            }
            
            $stmt = $pdo->prepare("DELETE FROM thong_bao WHERE id = ? AND nguoi_nhan_id = ?");
            $stmt->execute([$id, $user_id]);
            
            echo json_encode(['success' => true, 'message' => 'Đã xóa']);
            break;
            
        case 'delete_all_read':
            $stmt = $pdo->prepare("
                DELETE FROM thong_bao 
                WHERE nguoi_nhan_id = ? AND da_doc = 1
            ");
            $stmt->execute([$user_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Đã xóa tất cả thông báo đã đọc',
                'deleted' => $stmt->rowCount()
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
