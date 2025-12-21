<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role([ROLE_CHANH_VP]);

$page_title = 'Thêm người dùng';

$phong_ban_list = $pdo->query("SELECT * FROM phong_ban WHERE trang_thai = 1 ORDER BY ten_phong")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];
        $ho_ten = sanitize($_POST['ho_ten']);
        $email = sanitize($_POST['email']);
        $chuc_vu = $_POST['chuc_vu'];
        $phong_ban_id = $_POST['phong_ban_id'] ?: null;
        
        // Validate
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = 'Username đã tồn tại';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, ho_ten, email, chuc_vu, phong_ban_id, trang_thai)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([$username, $password_hash, $ho_ten, $email, $chuc_vu, $phong_ban_id]);
            
            log_activity('Tạo user mới', 'users', $pdo->lastInsertId(), $ho_ten);
            redirect(BASE_URL . '/quan-ly/nguoi-dung/danh-sach.php', 'Tạo người dùng thành công', 'success');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>
<h2><?= $page_title ?></h2>
<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Họ và tên <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="ho_ten" required>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" name="email">
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Chức vụ <span class="text-danger">*</span></label>
                        <select class="form-select" name="chuc_vu" required>
                            <?php foreach ($GLOBALS['ROLES'] as $key => $val): ?>
                                <option value="<?= $key ?>"><?= $val ?></option>
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
                                <option value="<?= $pb['id'] ?>"><?= e($pb['ten_phong']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Tạo</button>
            <a href="danh-sach.php" class="btn btn-secondary">Hủy</a>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
