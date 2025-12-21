<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'get_notifications':
        $limit = $_GET['limit'] ?? 10;
        
        $stmt = $pdo->prepare("
            SELECT *, 
                   CASE 
                       WHEN TIMESTAMPDIFF(SECOND, created_at, NOW()) < 60 THEN 'vừa xong'
                       WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' phút trước')
                       WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' giờ trước')
                       WHEN TIMESTAMPDIFF(DAY, created_at, NOW()) < 7 THEN CONCAT(TIMESTAMPDIFF(DAY, created_at, NOW()), ' ngày trước')
                       ELSE DATE_FORMAT(created_at, '%d/%m/%Y %H:%i')
                   END as time_ago
            FROM thong_bao 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, (int)$limit]);
        $notifications = $stmt->fetchAll();
        
        echo json_encode(['notifications' => $notifications]);
        break;
    
    case 'count_unread':
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM thong_bao WHERE user_id = ? AND da_doc = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        echo json_encode(['count' => $result['count']]);
        break;
    
    case 'mark_as_read':
        $notif_id = $_POST['id'] ?? 0;
        
        $stmt = $pdo->prepare("UPDATE thong_bao SET da_doc = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notif_id, $user_id]);
        
        echo json_encode(['success' => true]);
        break;
    
    case 'mark_all_read':
        $stmt = $pdo->prepare("UPDATE thong_bao SET da_doc = 1 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        echo json_encode(['success' => true]);
        break;
    
    case 'get_urgent':
        // Lấy thông báo khẩn cấp (quá hạn, còn 1 ngày)
        $stmt = $pdo->prepare("
            SELECT * FROM thong_bao 
            WHERE user_id = ? 
            AND loai IN ('qua_han', 'deadline_t1') 
            AND da_doc = 0
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY created_at DESC
            LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();
        
        echo json_encode(['notifications' => $notifications]);
        break;
    
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>
