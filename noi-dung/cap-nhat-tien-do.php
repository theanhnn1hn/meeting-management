<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$noi_dung_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM noi_dung WHERE id = ? AND nguoi_trinh_id = ?");
$stmt->execute([$noi_dung_id, $_SESSION['user_id']]);
$noi_dung = $stmt->fetch();

if (!$noi_dung) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Không có quyền', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $tien_do = min(100, max(0, (int)$_POST['tien_do']));
        $trang_thai = $tien_do == 100 ? 'hoan_thanh' : 'dang_xu_ly';
        
        $pdo->prepare("UPDATE noi_dung SET tien_do = ?, trang_thai = ? WHERE id = ?")->execute([$tien_do, $trang_thai, $noi_dung_id]);
        log_activity('Cập nhật tiến độ', 'noi_dung', $noi_dung_id, "Tiến độ: $tien_do%");
        
        redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Cập nhật tiến độ thành công', 'success');
    }
}

$page_title = 'Cập nhật tiến độ';
include __DIR__ . '/../includes/header.php';
?>
<div class="card">
    <div class="card-body">
        <h5><?= e($noi_dung['tieu_de']) ?></h5>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Tiến độ (%)</label>
                <input type="range" class="form-range" name="tien_do" min="0" max="100" step="5" 
                       value="<?= $noi_dung['tien_do'] ?>" id="rangeInput">
                <div class="text-center"><h2 id="rangeValue"><?= $noi_dung['tien_do'] ?>%</h2></div>
            </div>
            <button type="submit" class="btn btn-primary">Lưu</button>
            <a href="chi-tiet.php?id=<?= $noi_dung_id ?>" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
<script>
document.getElementById('rangeInput').oninput = function() {
    document.getElementById('rangeValue').textContent = this.value + '%';
};
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
