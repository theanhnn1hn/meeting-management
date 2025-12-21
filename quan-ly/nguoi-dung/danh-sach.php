<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG]);

$page_title = 'Quản lý người dùng';

// Lấy danh sách users
$where = "1=1";
$params = [];

// Phân quyền xem
if ($_SESSION['chuc_vu'] == ROLE_TRUONG_PHONG) {
    $where .= " AND u.phong_ban_id = ?";
    $params[] = $_SESSION['phong_ban_id'];
}

$stmt = $pdo->prepare("
    SELECT u.*, p.ten_phong
    FROM users u
    LEFT JOIN phong_ban p ON u.phong_ban_id = p.id
    WHERE $where
    ORDER BY 
        CASE u.chuc_vu
            WHEN 'chanh_vp' THEN 1
            WHEN 'pho_cvp' THEN 2
            WHEN 'truong_phong' THEN 3
            WHEN 'pho_phong' THEN 4
            ELSE 5
        END,
        u.ho_ten
");
$stmt->execute($params);
$users = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="bi bi-people"></i> Quản lý người dùng</h2>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($_SESSION['chuc_vu'] == ROLE_CHANH_VP): ?>
            <a href="tao-moi.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Thêm người dùng
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="tableUsers" class="table table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Chức vụ</th>
                        <th>Phòng ban</th>
                        <th>Trạng thái</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e($u['username']) ?></td>
                            <td><?= e($u['ho_ten']) ?></td>
                            <td><?= e($u['email'] ?? '') ?></td>
                            <td><?= $GLOBALS['ROLES'][$u['chuc_vu']] ?></td>
                            <td><?= e($u['ten_phong'] ?? '-') ?></td>
                            <td>
                                <?php if ($u['trang_thai'] == 1): ?>
                                    <span class="badge bg-success">Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Khóa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $can_edit = ($_SESSION['chuc_vu'] == ROLE_CHANH_VP) ||
                                           ($_SESSION['chuc_vu'] == ROLE_TRUONG_PHONG && $u['phong_ban_id'] == $_SESSION['phong_ban_id']);
                                ?>
                                <?php if ($can_edit): ?>
                                    <a href="sua.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extra_js = <<<JS
<script>
$('#tableUsers').DataTable({
    order: [[3, 'asc']],
    pageLength: 25
});
</script>
JS;
include __DIR__ . '/../../includes/footer.php';
?>
