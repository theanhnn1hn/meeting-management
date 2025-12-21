<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_permission('manage_co_quan');

$page_title = 'Tạo cơ quan mới';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $ma_co_quan = sanitize($_POST['ma_co_quan'] ?? '');
        $ten_co_quan = sanitize($_POST['ten_co_quan'] ?? '');
        $thu_tu = (int)($_POST['thu_tu'] ?? 0);
        
        if (empty($ma_co_quan) || empty($ten_co_quan)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM co_quan WHERE ma_co_quan = ?");
                $stmt->execute([$ma_co_quan]);
                if ($stmt->fetch()) {
                    $error = 'Mã cơ quan đã tồn tại';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO co_quan (ma_co_quan, ten_co_quan, thu_tu) VALUES (?, ?, ?)");
                    $stmt->execute([$ma_co_quan, $ten_co_quan, $thu_tu]);
                    log_activity('Tạo cơ quan', 'co_quan', $pdo->lastInsertId());
                    redirect(BASE_URL . '/quan-ly/co-quan/danh-sach.php', 'Tạo cơ quan thành công', 'success');
                }
            } catch (PDOException $e) {
                $error = 'Lỗi tạo cơ quan: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h2 class="mb-4"><i class="bi bi-diagram-3"></i> Tạo cơ quan mới</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Mã cơ quan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ma_co_quan" required placeholder="VD: SNV">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên cơ quan <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ten_co_quan" required 
                               placeholder="VD: Sở Nội vụ">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Thứ tự</label>
                        <input type="number" class="form-control" name="thu_tu" value="0" min="0">
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/co-quan/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Tạo cơ quan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
