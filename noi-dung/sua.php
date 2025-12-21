<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$noi_dung_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM noi_dung WHERE id = ?");
$stmt->execute([$noi_dung_id]);
$noi_dung = $stmt->fetch();

if (!$noi_dung || $noi_dung['nguoi_trinh_id'] != $_SESSION['user_id'] || $noi_dung['trang_thai'] != 'cho_duyet') {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Không thể sửa', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $tieu_de = sanitize($_POST['tieu_de']);
        $tom_tat = sanitize($_POST['tom_tat']);
        
        $pdo->prepare("UPDATE noi_dung SET tieu_de = ?, tom_tat = ? WHERE id = ?")->execute([$tieu_de, $tom_tat, $noi_dung_id]);
        redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Cập nhật thành công', 'success');
    }
}

$page_title = 'Sửa nội dung';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Tiêu đề</label>
                <input type="text" class="form-control" name="tieu_de" value="<?= e($noi_dung['tieu_de']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Tóm tắt</label>
                <textarea class="form-control" name="tom_tat" rows="4"><?= e($noi_dung['tom_tat'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="chi-tiet.php?id=<?= $noi_dung_id ?>" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
