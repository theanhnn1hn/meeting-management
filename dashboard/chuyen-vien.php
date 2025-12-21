<?php
$page_title = 'Dashboard Chuyên viên';

// Lấy công việc ưu tiên
$cong_viec_uu_tien = get_cong_viec_uu_tien($_SESSION['user_id']);

// Phân loại công việc theo mức độ ưu tiên
$qua_han = [];
$khan_cap = []; // < 3 ngày
$sap_den_han = []; // 3-7 ngày

foreach ($cong_viec_uu_tien as $cv) {
    if ($cv['days_left'] < 0) {
        $qua_han[] = $cv;
    } elseif ($cv['days_left'] <= 3) {
        $khan_cap[] = $cv;
    } elseif ($cv['days_left'] <= 7) {
        $sap_den_han[] = $cv;
    }
}

// Thống kê của chuyên viên
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as tong_noi_dung,
        SUM(CASE WHEN trang_thai = 'cho_duyet' THEN 1 ELSE 0 END) as cho_duyet,
        SUM(CASE WHEN trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
        SUM(CASE WHEN trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as hoan_thanh,
        AVG(tien_do) as tien_do_tb
    FROM noi_dung 
    WHERE nguoi_trinh_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Nội dung gần đây
$stmt = $pdo->prepare("
    SELECT nd.*, kh.ten_ky_hop, kh.ngay_hop
    FROM noi_dung nd
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    WHERE nd.nguoi_trinh_id = ?
    ORDER BY nd.created_at DESC
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$noi_dung_gan_day = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard của tôi</h2>
        <p class="text-muted">Xin chào, <?= e($current_user['ho_ten']) ?></p>
    </div>
</div>

<!-- Widget Công việc ưu tiên -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-danger">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill"></i> Công việc ưu tiên</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cong_viec_uu_tien)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check-circle display-1"></i>
                        <h5 class="mt-3">Tuyệt vời! Bạn không có công việc cần xử lý khẩn cấp</h5>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <!-- Quá hạn -->
                        <?php if (!empty($qua_han)): ?>
                            <div class="col-md-4 mb-3">
                                <div class="alert alert-danger">
                                    <h6 class="alert-heading">
                                        <i class="bi bi-exclamation-octagon"></i> Quá hạn (<?= count($qua_han) ?>)
                                    </h6>
                                    <?php foreach ($qua_han as $cv): ?>
                                        <div class="mb-2 pb-2 border-bottom">
                                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $cv['id'] ?>" 
                                               class="text-decoration-none text-dark">
                                                <div class="fw-bold"><?= e($cv['tieu_de']) ?></div>
                                                <small class="text-muted">
                                                    <?= e($cv['ten_ky_hop']) ?> - Quá hạn <?= abs($cv['days_left']) ?> ngày
                                                </small>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-danger" style="width: <?= $cv['tien_do'] ?>%"></div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Khẩn cấp (còn <= 3 ngày) -->
                        <?php if (!empty($khan_cap)): ?>
                            <div class="col-md-4 mb-3">
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">
                                        <i class="bi bi-clock-fill"></i> Khẩn cấp (<?= count($khan_cap) ?>)
                                    </h6>
                                    <?php foreach ($khan_cap as $cv): ?>
                                        <div class="mb-2 pb-2 border-bottom">
                                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $cv['id'] ?>" 
                                               class="text-decoration-none text-dark">
                                                <div class="fw-bold"><?= e($cv['tieu_de']) ?></div>
                                                <small class="text-muted">
                                                    <?= e($cv['ten_ky_hop']) ?> - Còn <?= $cv['days_left'] ?> ngày
                                                </small>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-warning" style="width: <?= $cv['tien_do'] ?>%"></div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Sắp đến hạn (3-7 ngày) -->
                        <?php if (!empty($sap_den_han)): ?>
                            <div class="col-md-4 mb-3">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">
                                        <i class="bi bi-info-circle"></i> Sắp đến hạn (<?= count($sap_den_han) ?>)
                                    </h6>
                                    <?php foreach ($sap_den_han as $cv): ?>
                                        <div class="mb-2 pb-2 border-bottom">
                                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $cv['id'] ?>" 
                                               class="text-decoration-none text-dark">
                                                <div class="fw-bold"><?= e($cv['tieu_de']) ?></div>
                                                <small class="text-muted">
                                                    <?= e($cv['ten_ky_hop']) ?> - Còn <?= $cv['days_left'] ?> ngày
                                                </small>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-info" style="width: <?= $cv['tien_do'] ?>%"></div>
                                                </div>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Thống kê -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-start border-primary border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Tổng nội dung</h6>
                <h2 class="mb-0"><?= $stats['tong_noi_dung'] ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-warning border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Chờ duyệt</h6>
                <h2 class="mb-0"><?= $stats['cho_duyet'] ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-success border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Đã duyệt</h6>
                <h2 class="mb-0"><?= $stats['da_duyet'] ?></h2>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-info border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Hoàn thành</h6>
                <h2 class="mb-0"><?= $stats['hoan_thanh'] ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Nội dung gần đây -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Nội dung gần đây</h5>
                <a href="<?= BASE_URL ?>/noi-dung/dang-ky.php" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Đăng ký mới
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($noi_dung_gan_day)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-file-earmark fs-1"></i>
                        <p class="mt-2">Chưa có nội dung nào</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Kỳ họp</th>
                                    <th>Trạng thái</th>
                                    <th>Tiến độ</th>
                                    <th>Ngày tạo</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($noi_dung_gan_day as $nd): ?>
                                    <tr>
                                        <td><?= e($nd['tieu_de']) ?></td>
                                        <td><small><?= e($nd['ten_ky_hop']) ?></small></td>
                                        <td><?= status_badge($nd['trang_thai']) ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <?php $color = get_tien_do_color($nd['tien_do']); ?>
                                                <div class="progress-bar bg-<?= $color ?>" style="width: <?= $nd['tien_do'] ?>%">
                                                    <?= $nd['tien_do'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td><small><?= time_ago($nd['created_at']) ?></small></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $nd['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-eye"></i>
                                            </a>
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
