<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_permission('manage_co_quan');

$co_quan_id = (int)($_GET['id'] ?? 0);

if ($co_quan_id <= 0) {
    redirect(BASE_URL . '/quan-ly/co-quan/danh-sach.php', 'Thiếu thông tin', 'error');
}

$stmt = $pdo->prepare("SELECT * FROM co_quan WHERE id = ?");
$stmt->execute([$co_quan_id]);
$co_quan = $stmt->fetch();

if (!$co_quan) {
    redirect(BASE_URL . '/quan-ly/co-quan/danh-sach.php', 'Không tìm thấy cơ quan', 'error');
}

$page_title = 'Sửa cơ quan';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $ma_co_quan = sanitize($_POST['ma_co_quan'] ?? '');
        $ten_co_quan = sanitize($_POST['ten_co_quan'] ?? '');
        $thu_tu = (int)($_POST['thu_tu'] ?? 0);
        $trang_thai = (int)($_POST['trang_thai'] ?? 1);
        
        if (empty($ma_co_quan) || empty($ten_co_quan)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM co_quan WHERE ma_co_quan = ? AND id != ?");
                $stmt->execute([$ma_co_quan, $co_quan_id]);
                if ($stmt->fetch()) {
                    $error = 'Mã cơ quan đã tồn tại';
                } else {
                    $stmt = $pdo->prepare("UPDATE co_quan SET ma_co_quan = ?, ten_co_quan = ?, thu_tu = ?, trang_thai = ? WHERE id = ?");
                    $stmt->execute([$ma_co_quan, $ten_co_quan, $thu_tu, $trang_thai, $co_quan_id]);
                    log_activity('Sửa cơ quan', 'co_quan', $co_quan_id);
                    redirect(BASE_URL . '/quan-ly/co-quan/danh-sach.php', 'Cập nhật cơ quan thành công', 'success');
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
        <h2 class="mb-4"><i class="bi bi-diagram-3"></i> Sửa cơ quan</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Mã cơ quan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ma_co_quan" value="<?= e($co_quan['ma_co_quan']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên cơ quan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ten_co_quan" value="<?= e($co_quan['ten_co_quan']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" class="form-control" name="thu_tu" value="<?= $co_quan['thu_tu'] ?>" min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Trạng thái</label>
                        <select class="form-select" name="trang_thai">
                            <option value="1" <?= $co_quan['trang_thai'] == 1 ? 'selected' : '' ?>>Hoạt động</option>
                            <option value="0" <?= $co_quan['trang_thai'] == 0 ? 'selected' : '' ?>>Ngừng hoạt động</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/co-quan/danh-sach.php" class="btn btn-secondary">
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
