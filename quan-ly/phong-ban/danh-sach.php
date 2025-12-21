<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role([ROLE_CHANH_VP]);

$page_title = 'Quản lý phòng ban';

$stmt = $pdo->query("
    SELECT pb.*, 
           u1.ho_ten as truong_phong_name
    FROM phong_ban pb
    LEFT JOIN users u1 ON pb.truong_phong_id = u1.id
    WHERE pb.trang_thai = 1
    ORDER BY pb.thu_tu, pb.ten_phong
");
$phong_ban_list = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<h2><i class="bi bi-building"></i> <?= $page_title ?></h2>

<div class="card">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã phòng</th>
                    <th>Tên phòng</th>
                    <th>Trưởng phòng</th>
                    <th>Số nhân sự</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($phong_ban_list as $pb): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phong_ban_id = ? AND trang_thai = 1");
                    $stmt->execute([$pb['id']]);
                    $so_nhan_su = $stmt->fetchColumn();
                    ?>
                    <tr>
                        <td><?= e($pb['ma_phong']) ?></td>
                        <td><?= e($pb['ten_phong']) ?></td>
                        <td><?= e($pb['truong_phong_name'] ?? 'Chưa có') ?></td>
                        <td><?= $so_nhan_su ?></td>
                        <td>
                            <a href="gan-truong-phong.php?id=<?= $pb['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-person-check"></i> Gắn Trưởng phòng
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
