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

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0"><i class="bi bi-building"></i> <?= $page_title ?></h2>
        <p class="text-muted">Quản lý các phòng ban trong hệ thống</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="tao-moi.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tạo phòng ban mới
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã phòng</th>
                    <th>Tên phòng</th>
                    <th>Trưởng phòng</th>
                    <th>Số nhân sự</th>
                    <th width="250">Thao tác</th>
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
                            <a href="gan-truong-phong.php?id=<?= $pb['id'] ?>" class="btn btn-sm btn-outline-primary" title="Gắn Trưởng phòng">
                                <i class="bi bi-person-check"></i>
                            </a>
                            <a href="sua.php?id=<?= $pb['id'] ?>" class="btn btn-sm btn-outline-warning" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="xoa.php?id=<?= $pb['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Xác nhận xóa phòng ban này?');" title="Xóa">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
