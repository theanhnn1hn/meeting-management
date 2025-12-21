<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role([ROLE_CHANH_VP]);

$page_title = 'Quản lý cơ quan';

$co_quan_list = $pdo->query("SELECT * FROM co_quan WHERE trang_thai = 1 ORDER BY thu_tu, ten_co_quan")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>
<h2><i class="bi bi-diagram-3"></i> <?= $page_title ?></h2>

<div class="card">
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Mã</th>
                    <th>Tên cơ quan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($co_quan_list as $cq): ?>
                    <tr>
                        <td><?= e($cq['ma_co_quan']) ?></td>
                        <td><?= e($cq['ten_co_quan']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
