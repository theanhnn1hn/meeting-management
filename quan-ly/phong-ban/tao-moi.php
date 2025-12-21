<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_permission('manage_phong_ban');

$page_title = 'Tạo phòng ban mới';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $ma_phong = sanitize($_POST['ma_phong'] ?? '');
        $ten_phong = sanitize($_POST['ten_phong'] ?? '');
        $thu_tu = (int)($_POST['thu_tu'] ?? 0);
        
        if (empty($ma_phong) || empty($ten_phong)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            try {
                // Kiểm tra mã phòng trùng
                $stmt = $pdo->prepare("SELECT id FROM phong_ban WHERE ma_phong = ?");
                $stmt->execute([$ma_phong]);
                if ($stmt->fetch()) {
                    $error = 'Mã phòng đã tồn tại';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO phong_ban (ma_phong, ten_phong, thu_tu) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$ma_phong, $ten_phong, $thu_tu]);
                    
                    log_activity('Tạo phòng ban', 'phong_ban', $pdo->lastInsertId());
                    
                    redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Tạo phòng ban thành công', 'success');
                }
            } catch (PDOException $e) {
                $error = 'Lỗi tạo phòng ban: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h2 class="mb-4"><i class="bi bi-building"></i> Tạo phòng ban mới</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Mã phòng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ma_phong" required
                               placeholder="VD: VHXH">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên phòng <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ten_phong" required
                               placeholder="VD: Phòng Văn hóa - Xã hội">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" class="form-control" name="thu_tu" value="0" min="0">
                        <small class="text-muted">Để sắp xếp hiển thị</small>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/phong-ban/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Tạo phòng ban
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
