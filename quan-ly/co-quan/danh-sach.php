<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role([ROLE_CHANH_VP]);

$page_title = 'Quản lý cơ quan';

$co_quan_list = $pdo->query("SELECT * FROM co_quan WHERE trang_thai = 1 ORDER BY thu_tu, ten_co_quan")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0"><i class="bi bi-diagram-3"></i> <?= $page_title ?></h2>
        <p class="text-muted">Quản lý các cơ quan trong hệ thống</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="tao-moi.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tạo cơ quan mới
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên cơ quan</th>
                    <th width="150">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($co_quan_list as $cq): ?>
                    <tr>
                        <td><?= e($cq['ma_co_quan']) ?></td>
                        <td><?= e($cq['ten_co_quan']) ?></td>
                        <td>
                            <a href="sua.php?id=<?= $cq['id'] ?>" class="btn btn-sm btn-outline-warning" title="Sửa">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="xoa.php?id=<?= $cq['id'] ?>" class="btn btn-sm btn-outline-danger"
                               onclick="return confirm('Xác nhận xóa cơ quan này?');" title="Xóa">
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
