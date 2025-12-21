<?php
$page_title = 'Dashboard Trưởng phòng';

// Lấy nội dung của phòng
$stmt = $pdo->prepare("
    SELECT nd.*, u.ho_ten as nguoi_trinh, kh.ten_ky_hop, kh.deadline_hoan_thien
    FROM noi_dung nd
    JOIN users u ON nd.nguoi_trinh_id = u.id
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    WHERE nd.phong_ban_id = ?
    AND nd.trang_thai IN ('da_duyet', 'dang_xu_ly')
    ORDER BY nd.tien_do ASC, kh.deadline_hoan_thien ASC
");
$stmt->execute([$_SESSION['phong_ban_id']]);
$noi_dung_phong = $stmt->fetchAll();

// Thống kê phòng
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as tong,
        AVG(tien_do) as tien_do_tb,
        SUM(CASE WHEN tien_do < 100 AND kh.deadline_hoan_thien < CURDATE() THEN 1 ELSE 0 END) as qua_han
    FROM noi_dung nd
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    WHERE nd.phong_ban_id = ?
    AND nd.trang_thai IN ('da_duyet', 'dang_xu_ly')
");
$stmt->execute([$_SESSION['phong_ban_id']]);
$stats = $stmt->fetch();

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard Trưởng phòng</h2>
        <p class="text-muted">Quản lý công việc phòng: <?= e($current_user['ten_phong']) ?></p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-start border-primary border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Nội dung đang xử lý</h6>
                <h2 class="mb-0"><?= $stats['tong'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-danger border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Quá hạn</h6>
                <h2 class="mb-0"><?= $stats['qua_han'] ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-start border-success border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Tiến độ trung bình</h6>
                <h2 class="mb-0"><?= round($stats['tien_do_tb']) ?>%</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-task"></i> Công việc phòng</h5>
            </div>
            <div class="card-body">
                <?php if (empty($noi_dung_phong)): ?>
                    <div class="text-center py-4 text-muted">Phòng chưa có công việc nào</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Kỳ họp</th>
                                    <th>Người thực hiện</th>
                                    <th>Tiến độ</th>
                                    <th>Hạn hoàn thiện</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($noi_dung_phong as $nd): ?>
                                    <tr>
                                        <td><?= e($nd['tieu_de']) ?></td>
                                        <td><small><?= e($nd['ten_ky_hop']) ?></small></td>
                                        <td><?= e($nd['nguoi_trinh']) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <?php $color = get_tien_do_color($nd['tien_do']); ?>
                                                <div class="progress-bar bg-<?= $color ?>" style="width: <?= $nd['tien_do'] ?>%">
                                                    <?= $nd['tien_do'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= format_date($nd['deadline_hoan_thien']) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $nd['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">Xem</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
