<?php
// =====================================================
// API CHECKLIST - CRUD Operations
// =====================================================

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            // Thêm công việc mới
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
                exit;
            }

            $noi_dung_id = (int)($_POST['noi_dung_id'] ?? 0);
            $ten_cong_viec = sanitize($_POST['ten_cong_viec'] ?? '');
            $template_id = (int)($_POST['template_id'] ?? 0);

            if (empty($noi_dung_id) || empty($ten_cong_viec)) {
                echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
                exit;
            }

            // Kiểm tra quyền: chỉ người trình hoặc lãnh đạo mới được thêm checklist
            $stmt = $pdo->prepare("SELECT nguoi_trinh_id FROM noi_dung WHERE id = ?");
            $stmt->execute([$noi_dung_id]);
            $noi_dung = $stmt->fetch();

            if (!$noi_dung) {
                echo json_encode(['success' => false, 'message' => 'Nội dung không tồn tại']);
                exit;
            }

            $is_owner = ($noi_dung['nguoi_trinh_id'] == $_SESSION['user_id']);
            $is_leader = in_array($_SESSION['chuc_vu'] ?? '', [ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]);

            if (!$is_owner && !$is_leader) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thêm công việc']);
                exit;
            }

            // Lấy thứ tự lớn nhất
            $stmt = $pdo->prepare("SELECT COALESCE(MAX(thu_tu), 0) + 1 as next_thu_tu FROM checklist WHERE noi_dung_id = ?");
            $stmt->execute([$noi_dung_id]);
            $thu_tu = $stmt->fetchColumn();

            // Thêm vào database
            $stmt = $pdo->prepare("
                INSERT INTO checklist (noi_dung_id, ten_cong_viec, thu_tu, trang_thai)
                VALUES (?, ?, ?, 0)
            ");
            $stmt->execute([$noi_dung_id, $ten_cong_viec, $thu_tu]);

            log_activity('Thêm công việc checklist', 'checklist', $pdo->lastInsertId(), $ten_cong_viec);

            echo json_encode(['success' => true, 'message' => 'Thêm công việc thành công']);
            break;

        case 'toggle':
            // Toggle trạng thái công việc
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);
            $checked = (int)($_POST['checked'] ?? 0);

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }

            // Lấy thông tin checklist
            $stmt = $pdo->prepare("
                SELECT c.*, nd.nguoi_trinh_id
                FROM checklist c
                JOIN noi_dung nd ON c.noi_dung_id = nd.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $checklist = $stmt->fetch();

            if (!$checklist) {
                echo json_encode(['success' => false, 'message' => 'Công việc không tồn tại']);
                exit;
            }

            // Kiểm tra quyền
            $is_owner = ($checklist['nguoi_trinh_id'] == $_SESSION['user_id']);
            $is_leader = in_array($_SESSION['chuc_vu'] ?? '', [ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]);

            if (!$is_owner && !$is_leader) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền cập nhật công việc']);
                exit;
            }

            // Cập nhật trạng thái
            if ($checked) {
                $stmt = $pdo->prepare("
                    UPDATE checklist
                    SET trang_thai = 1,
                        nguoi_thuc_hien_id = ?,
                        ngay_hoan_thanh = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE checklist
                    SET trang_thai = 0,
                        nguoi_thuc_hien_id = NULL,
                        ngay_hoan_thanh = NULL
                    WHERE id = ?
                ");
                $stmt->execute([$id]);
            }

            // Tự động cập nhật tiến độ dựa trên checklist
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN trang_thai = 1 THEN 1 ELSE 0 END) as completed
                FROM checklist
                WHERE noi_dung_id = ?
            ");
            $stmt->execute([$checklist['noi_dung_id']]);
            $progress = $stmt->fetch();

            if ($progress['total'] > 0) {
                $tien_do = round(($progress['completed'] / $progress['total']) * 100);
                $stmt = $pdo->prepare("UPDATE noi_dung SET tien_do = ? WHERE id = ?");
                $stmt->execute([$tien_do, $checklist['noi_dung_id']]);

                // Nếu 100% thì chuyển sang hoàn thành
                if ($tien_do >= 100) {
                    $stmt = $pdo->prepare("UPDATE noi_dung SET trang_thai = 'hoan_thanh' WHERE id = ?");
                    $stmt->execute([$checklist['noi_dung_id']]);
                }
            }

            log_activity($checked ? 'Hoàn thành công việc' : 'Bỏ tick công việc', 'checklist', $id);

            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
            break;

        case 'delete':
            // Xóa công việc
            if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
                echo json_encode(['success' => false, 'message' => 'Token không hợp lệ']);
                exit;
            }

            $id = (int)($_POST['id'] ?? 0);

            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }

            // Lấy thông tin checklist
            $stmt = $pdo->prepare("
                SELECT c.*, nd.nguoi_trinh_id
                FROM checklist c
                JOIN noi_dung nd ON c.noi_dung_id = nd.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $checklist = $stmt->fetch();

            if (!$checklist) {
                echo json_encode(['success' => false, 'message' => 'Công việc không tồn tại']);
                exit;
            }

            // Kiểm tra quyền
            $is_owner = ($checklist['nguoi_trinh_id'] == $_SESSION['user_id']);
            $is_leader = in_array($_SESSION['chuc_vu'] ?? '', [ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]);

            if (!$is_owner && !$is_leader) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa công việc']);
                exit;
            }

            // Xóa
            $stmt = $pdo->prepare("DELETE FROM checklist WHERE id = ?");
            $stmt->execute([$id]);

            // Cập nhật lại tiến độ
            $stmt = $pdo->prepare("
                SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN trang_thai = 1 THEN 1 ELSE 0 END) as completed
                FROM checklist
                WHERE noi_dung_id = ?
            ");
            $stmt->execute([$checklist['noi_dung_id']]);
            $progress = $stmt->fetch();

            if ($progress['total'] > 0) {
                $tien_do = round(($progress['completed'] / $progress['total']) * 100);
            } else {
                $tien_do = 0;
            }

            $stmt = $pdo->prepare("UPDATE noi_dung SET tien_do = ? WHERE id = ?");
            $stmt->execute([$tien_do, $checklist['noi_dung_id']]);

            log_activity('Xóa công việc checklist', 'checklist', $id, $checklist['ten_cong_viec']);

            echo json_encode(['success' => true, 'message' => 'Xóa công việc thành công']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
            break;
    }

} catch (PDOException $e) {
    error_log("Checklist API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>
