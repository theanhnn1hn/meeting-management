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

$stmt = $pdo->prepare("SELECT COUNT(*) FROM noi_dung WHERE co_quan_trinh_id = ?");
$stmt->execute([$co_quan_id]);
$noi_dung_count = $stmt->fetchColumn();

if ($noi_dung_count > 0) {
    redirect(BASE_URL . '/quan-ly/co-quan/danh-sach.php', 
        'Không thể xóa cơ quan đã có ' . $noi_dung_count . ' nội dung liên quan', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
    try {
        $stmt = $pdo->prepare("DELETE FROM co_quan WHERE id = ?");
        $stmt->execute([$co_quan_id]);
        log_activity('Xóa cơ quan', 'co_quan', $co_quan_id, 'Mã: ' . $co_quan['ma_co_quan']);
        redirect(BASE_URL . '/quan-ly/co-quan/danh-sach.php', 'Xóa cơ quan thành công', 'success');
    } catch (PDOException $e) {
        redirect(BASE_URL . '/quan-ly/co-quan/danh-sach.php', 'Lỗi xóa cơ quan: ' . $e->getMessage(), 'error');
    }
}

$page_title = 'Xóa cơ quan';
include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Xác nhận xóa cơ quan</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-info-circle"></i> Bạn có chắc chắn muốn xóa cơ quan này?
                </div>
                
                <table class="table">
                    <tr>
                        <th>Mã cơ quan:</th>
                        <td><?= e($co_quan['ma_co_quan']) ?></td>
                    </tr>
                    <tr>
                        <th>Tên cơ quan:</th>
                        <td><?= e($co_quan['ten_co_quan']) ?></td>
                    </tr>
                </table>
                
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/co-quan/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Xóa cơ quan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
