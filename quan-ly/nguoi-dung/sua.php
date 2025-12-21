<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';

$user_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'User không tồn tại', 'error');

// Check quyền
$can_edit = ($_SESSION['chuc_vu'] == ROLE_CHANH_VP) ||
           ($_SESSION['chuc_vu'] == ROLE_TRUONG_PHONG && $user['phong_ban_id'] == $_SESSION['phong_ban_id']);

if (!$can_edit) redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Không có quyền', 'error');

$page_title = 'Sửa người dùng';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $ho_ten = sanitize($_POST['ho_ten']);
        $email = sanitize($_POST['email']);
        $chuc_vu = $_POST['chuc_vu'];
        $phong_ban_id = $_POST['phong_ban_id'] ?: null;
        $trang_thai = $_POST['trang_thai'];
        
        $pdo->prepare("
            UPDATE users SET ho_ten = ?, email = ?, chuc_vu = ?, phong_ban_id = ?, trang_thai = ?
            WHERE id = ?
        ")->execute([$ho_ten, $email, $chuc_vu, $phong_ban_id, $trang_thai, $user_id]);
        
        redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Cập nhật thành công', 'success');
    }
}

$phong_ban_list = $pdo->query("SELECT * FROM phong_ban WHERE trang_thai = 1 ORDER BY ten_phong")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<h2><?= $page_title ?></h2>
<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Họ tên</label>
                <input type="text" class="form-control" name="ho_ten" value="<?= e($user['ho_ten']) ?>" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email" value="<?= e($user['email'] ?? '') ?>">
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Chức vụ</label>
                        <select class="form-select" name="chuc_vu">
                            <?php foreach ($GLOBALS['ROLES'] as $key => $val): ?>
                                <option value="<?= $key ?>" <?= $user['chuc_vu'] == $key ? 'selected' : '' ?>>
                                    <?= $val ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Phòng ban</label>
                        <select class="form-select" name="phong_ban_id">
                            <option value="">-- Chưa gắn phòng --</option>
                            <?php foreach ($phong_ban_list as $pb): ?>
                                <option value="<?= $pb['id'] ?>" <?= $user['phong_ban_id'] == $pb['id'] ? 'selected' : '' ?>>
                                    <?= e($pb['ten_phong']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Trạng thái</label>
                <select class="form-select" name="trang_thai">
                    <option value="1" <?= $user['trang_thai'] == 1 ? 'selected' : '' ?>>Hoạt động</option>
                    <option value="0" <?= $user['trang_thai'] == 0 ? 'selected' : '' ?>>Khóa</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Cập nhật</button>
            <a href="danh-sach.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
