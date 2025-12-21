<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role([ROLE_CHANH_VP]);

$phong_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM phong_ban WHERE id = ?");
$stmt->execute([$phong_id]);
$phong = $stmt->fetch();

if (!$phong) redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Phòng không tồn tại', 'error');

// Lấy users trong phòng
$stmt = $pdo->prepare("SELECT * FROM users WHERE phong_ban_id = ? AND trang_thai = 1 ORDER BY ho_ten");
$stmt->execute([$phong_id]);
$users = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $truong_phong_id = $_POST['truong_phong_id'] ?: null;
        $pdo->prepare("UPDATE phong_ban SET truong_phong_id = ? WHERE id = ?")->execute([$truong_phong_id, $phong_id]);
        redirect(BASE_URL . '/quan-ly/phong-ban/danh-sach.php', 'Cập nhật thành công', 'success');
    }
}

$page_title = 'Gắn Trưởng phòng';
include __DIR__ . '/../../includes/header.php';
?>
<h2><?= $page_title ?>: <?= e($phong['ten_phong']) ?></h2>
<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Trưởng phòng</label>
                <select class="form-select" name="truong_phong_id">
                    <option value="">-- Chưa có --</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $phong['truong_phong_id'] == $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['ho_ten']) ?> (<?= $GLOBALS['ROLES'][$u['chuc_vu']] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Lưu</button>
            <a href="danh-sach.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
