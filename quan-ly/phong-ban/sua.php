<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_permission('manage_phong_ban');

$phong_id = (int)($_GET['id'] ?? 0);

if ($phong_id <= 0) {
    redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Thiếu thông tin', 'error');
}

// Lấy thông tin phòng
$stmt = $pdo->prepare("SELECT * FROM phong_ban WHERE id = ?");
$stmt->execute([$phong_id]);
$phong = $stmt->fetch();

if (!$phong) {
    redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Không tìm thấy phòng ban', 'error');
}

$page_title = 'Sửa phòng ban';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $ma_phong = sanitize($_POST['ma_phong'] ?? '');
        $ten_phong = sanitize($_POST['ten_phong'] ?? '');
        $thu_tu = (int)($_POST['thu_tu'] ?? 0);
        $trang_thai = (int)($_POST['trang_thai'] ?? 1);
        
        if (empty($ma_phong) || empty($ten_phong)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            try {
                // Kiểm tra mã phòng trùng (trừ phòng hiện tại)
                $stmt = $pdo->prepare("SELECT id FROM phong_ban WHERE ma_phong = ? AND id != ?");
                $stmt->execute([$ma_phong, $phong_id]);
                if ($stmt->fetch()) {
                    $error = 'Mã phòng đã tồn tại';
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE phong_ban 
                        SET ma_phong = ?, ten_phong = ?, thu_tu = ?, trang_thai = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$ma_phong, $ten_phong, $thu_tu, $trang_thai, $phong_id]);
                    
                    log_activity('Sửa phòng ban', 'phong_ban', $phong_id);
                    
                    redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Cập nhật phòng ban thành công', 'success');
                }
            } catch (PDOException $e) {
                $error = 'Lỗi cập nhật: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h2 class="mb-4"><i class="bi bi-building"></i> Sửa phòng ban</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Mã phòng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ma_phong" 
                               value="<?= e($phong['ma_phong']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên phòng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ten_phong" 
                               value="<?= e($phong['ten_phong']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" class="form-control" name="thu_tu" 
                               value="<?= $phong['thu_tu'] ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="trang_thai">
                            <option value="1" <?= $phong['trang_thai'] == 1 ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="0" <?= $phong['trang_thai'] == 0 ? 'selected' : '' ?>>Ngừng hoạt động</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/phong-ban/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Cập nhật
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
