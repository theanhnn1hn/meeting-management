<?php
/**
 * FIXED VERSION - Notification AJAX with CSRF Protection
 * 
 * BUG FIXED:
 * - Missing CSRF protection for GET requests
 * - Solution: Use POST for all state-changing operations, add CSRF token validation
 */

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ FIX: Read-only operations (GET) vs State-changing operations (POST)
$request_method = $_SERVER['REQUEST_METHOD'];

try {
    // GET - Read-only operations (no CSRF needed)
    if ($request_method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'count':
                // Get unread count
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) 
                    FROM thong_bao 
                    WHERE nguoi_nhan_id = ? AND da_doc = 0
                ");
                $stmt->execute([$user_id]);
                $count = $stmt->fetchColumn();
                
                echo json_encode([
                    'success' => true,
                    'count' => (int)$count
                ]);
                break;
                
            case 'list':
                // Get recent notifications
                $limit = min(20, (int)($_GET['limit'] ?? 10));
                
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        loai,
                        tieu_de,
                        noi_dung,
                        lien_ket,
                        da_doc,
                        created_at
                    FROM thong_bao
                    WHERE nguoi_nhan_id = ?
                    ORDER BY created_at DESC
                    LIMIT ?
                ");
                $stmt->execute([$user_id, $limit]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Format timestamps
                foreach ($notifications as &$notif) {
                    $notif['created_at'] = time_elapsed_string($notif['created_at']);
                    $notif['da_doc'] = (bool)$notif['da_doc'];
                }
                
                echo json_encode([
                    'success' => true,
                    'notifications' => $notifications
                ]);
                break;
                
            default:
                throw new Exception('Invalid action for GET request');
        }
    }
    // POST - State-changing operations (CSRF required)
    elseif ($request_method === 'POST') {
        // ✅ FIX: Verify CSRF token for all POST requests
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid CSRF token'
            ]);
            exit;
        }
        
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'mark_read':
                $notification_id = (int)($_POST['id'] ?? 0);
                
                if ($notification_id <= 0) {
                    throw new Exception('Invalid notification ID');
                }
                
                // Verify ownership
                $stmt = $pdo->prepare("
                    SELECT id FROM thong_bao 
                    WHERE id = ? AND nguoi_nhan_id = ?
                ");
                $stmt->execute([$notification_id, $user_id]);
                
                if (!$stmt->fetch()) {
                    throw new Exception('Notification not found or access denied');
                }
                
                // Mark as read
                $stmt = $pdo->prepare("
                    UPDATE thong_bao 
                    SET da_doc = 1, ngay_doc = NOW() 
                    WHERE id = ? AND nguoi_nhan_id = ?
                ");
                $stmt->execute([$notification_id, $user_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Marked as read'
                ]);
                break;
                
            case 'mark_all_read':
                // Mark all notifications as read
                $stmt = $pdo->prepare("
                    UPDATE thong_bao 
                    SET da_doc = 1, ngay_doc = NOW() 
                    WHERE nguoi_nhan_id = ? AND da_doc = 0
                ");
                $stmt->execute([$user_id]);
                
                $affected = $stmt->rowCount();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Marked all as read',
                    'count' => $affected
                ]);
                break;
                
            case 'delete':
                $notification_id = (int)($_POST['id'] ?? 0);
                
                if ($notification_id <= 0) {
                    throw new Exception('Invalid notification ID');
                }
                
                // Verify ownership before delete
                $stmt = $pdo->prepare("
                    DELETE FROM thong_bao 
                    WHERE id = ? AND nguoi_nhan_id = ?
                ");
                $stmt->execute([$notification_id, $user_id]);
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Notification not found or already deleted');
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Notification deleted'
                ]);
                break;
                
            case 'delete_all_read':
                // Delete all read notifications
                $stmt = $pdo->prepare("
                    DELETE FROM thong_bao 
                    WHERE nguoi_nhan_id = ? AND da_doc = 1
                ");
                $stmt->execute([$user_id]);
                
                $affected = $stmt->rowCount();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Deleted all read notifications',
                    'count' => $affected
                ]);
                break;
                
            default:
                throw new Exception('Invalid action for POST request');
        }
    }
    else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * USAGE EXAMPLES:
 * 
 * // GET - No CSRF needed (read-only)
 * $.get('/api/notification-ajax.php?action=count', function(data) {
 *     console.log('Unread:', data.count);
 * });
 * 
 * // POST - CSRF required (state-changing)
 * $.post('/api/notification-ajax.php', {
 *     action: 'mark_read',
 *     id: 123,
 *     csrf_token: '<?php echo $_SESSION['csrf_token']; ?>'
 * }, function(data) {
 *     console.log('Marked as read');
 * });
 */
?>
