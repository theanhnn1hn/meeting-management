<?php
$page_title = 'Dashboard Chánh Văn phòng';

// Lấy dữ liệu thống kê
$stats = [];

// Tổng số kỳ họp đang hoạt động
$stmt = $pdo->query("SELECT COUNT(*) FROM ky_hop WHERE trang_thai IN ('du_thao', 'dang_xu_ly', 'sap_dien_ra')");
$stats['ky_hop_hoat_dong'] = $stmt->fetchColumn();

// Tổng số nội dung chờ duyệt
$stmt = $pdo->query("SELECT COUNT(*) FROM noi_dung WHERE trang_thai = 'cho_duyet'");
$stats['cho_duyet'] = $stmt->fetchColumn();

// Tổng số nội dung quá hạn
$stmt = $pdo->query("
    SELECT COUNT(*) FROM noi_dung nd
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    WHERE nd.trang_thai IN ('da_duyet', 'dang_xu_ly')
    AND nd.tien_do < 100
    AND kh.deadline_hoan_thien < CURDATE()
");
$stats['qua_han'] = $stmt->fetchColumn();

// Tổng số người dùng hoạt động
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE trang_thai = 1");
$stats['users'] = $stmt->fetchColumn();

// Kỳ họp sắp diễn ra
$stmt = $pdo->query("
    SELECT * FROM ky_hop 
    WHERE trang_thai IN ('dang_xu_ly', 'sap_dien_ra')
    AND ngay_hop >= CURDATE()
    ORDER BY ngay_hop ASC
    LIMIT 1
");
$ky_hop_sap_toi = $stmt->fetch();

// Tiến độ tổng thể kỳ họp sắp tới
$tien_do_tong = null;
if ($ky_hop_sap_toi) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as tong_noi_dung,
            SUM(CASE WHEN trang_thai = 'cho_duyet' THEN 1 ELSE 0 END) as cho_duyet,
            SUM(CASE WHEN trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
            SUM(CASE WHEN trang_thai = 'tu_choi' THEN 1 ELSE 0 END) as tu_choi,
            SUM(CASE WHEN trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as hoan_thanh,
            AVG(tien_do) as tien_do_tb
        FROM noi_dung 
        WHERE ky_hop_id = ?
    ");
    $stmt->execute([$ky_hop_sap_toi['id']]);
    $tien_do_tong = $stmt->fetch();
}

// Nội dung chờ duyệt
$stmt = $pdo->query("
    SELECT nd.*, u.ho_ten as nguoi_trinh, pb.ten_phong
    FROM noi_dung nd
    JOIN users u ON nd.nguoi_trinh_id = u.id
    LEFT JOIN phong_ban pb ON nd.phong_ban_id = pb.id
    WHERE nd.trang_thai = 'cho_duyet'
    ORDER BY nd.created_at ASC
    LIMIT 5
");
$cho_duyet_list = $stmt->fetchAll();

// Phòng ban cần đốc việc (tiến độ thấp)
$stmt = $pdo->query("
    SELECT 
        pb.id, pb.ten_phong,
        COUNT(nd.id) as tong_noi_dung,
        AVG(nd.tien_do) as tien_do_tb,
        SUM(CASE WHEN kh.deadline_hoan_thien < CURDATE() AND nd.tien_do < 100 THEN 1 ELSE 0 END) as qua_han
    FROM phong_ban pb
    LEFT JOIN users u ON u.phong_ban_id = pb.id
    LEFT JOIN noi_dung nd ON nd.nguoi_trinh_id = u.id
    LEFT JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    WHERE nd.trang_thai IN ('da_duyet', 'dang_xu_ly')
    AND kh.trang_thai IN ('dang_xu_ly', 'sap_dien_ra')
    GROUP BY pb.id, pb.ten_phong
    HAVING tien_do_tb < 70
    ORDER BY tien_do_tb ASC, qua_han DESC
    LIMIT 5
");
$phong_can_doc = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard Chánh Văn phòng</h2>
        <p class="text-muted">Tổng quan hệ thống quản lý kỳ họp</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-start border-primary border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Kỳ họp đang xử lý</h6>
                        <h2 class="mb-0"><?= $stats['ky_hop_hoat_dong'] ?></h2>
                    </div>
                    <div class="text-primary" style="font-size: 3rem; opacity: 0.2;">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-warning border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Chờ phê duyệt</h6>
                        <h2 class="mb-0"><?= $stats['cho_duyet'] ?></h2>
                    </div>
                    <div class="text-warning" style="font-size: 3rem; opacity: 0.2;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-danger border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Quá hạn</h6>
                        <h2 class="mb-0"><?= $stats['qua_han'] ?></h2>
                    </div>
                    <div class="text-danger" style="font-size: 3rem; opacity: 0.2;">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-start border-success border-4">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <h6 class="text-muted mb-1">Người dùng</h6>
                        <h2 class="mb-0"><?= $stats['users'] ?></h2>
                    </div>
                    <div class="text-success" style="font-size: 3rem; opacity: 0.2;">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tiến độ kỳ họp sắp tới -->
<?php if ($ky_hop_sap_toi && $tien_do_tong): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Tiến độ kỳ họp: <?= e($ky_hop_sap_toi['ten_ky_hop']) ?></h5>
                <small class="text-muted">Ngày họp: <?= format_date($ky_hop_sap_toi['ngay_hop']) ?></small>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted small mb-1">Tổng nội dung</h6>
                            <h3 class="mb-0"><?= $tien_do_tong['tong_noi_dung'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted small mb-1">Chờ duyệt</h6>
                            <h3 class="mb-0 text-warning"><?= $tien_do_tong['cho_duyet'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted small mb-1">Đã duyệt</h6>
                            <h3 class="mb-0 text-success"><?= $tien_do_tong['da_duyet'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted small mb-1">Từ chối</h6>
                            <h3 class="mb-0 text-danger"><?= $tien_do_tong['tu_choi'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted small mb-1">Hoàn thành</h6>
                            <h3 class="mb-0 text-primary"><?= $tien_do_tong['hoan_thanh'] ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="text-center">
                            <h6 class="text-muted small mb-1">Tiến độ TB</h6>
                            <h3 class="mb-0"><?= round($tien_do_tong['tien_do_tb']) ?>%</h3>
                        </div>
                    </div>
                </div>
                
                <div class="mb-2">
                    <label class="form-label">Tiến độ hoàn thiện tổng thể</label>
                    <div class="progress" style="height: 30px;">
                        <?php 
                        $percent = round($tien_do_tong['tien_do_tb']);
                        $color = get_tien_do_color($percent);
                        ?>
                        <div class="progress-bar bg-<?= $color ?>" role="progressbar" style="width: <?= $percent ?>%">
                            <?= $percent ?>%
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="<?= BASE_URL ?>/ky-hop/chi-tiet.php?id=<?= $ky_hop_sap_toi['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-eye"></i> Xem chi tiết kỳ họp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Nội dung chờ duyệt -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Chờ phê duyệt</h5>
                <span class="badge bg-warning"><?= count($cho_duyet_list) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($cho_duyet_list)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle fs-1"></i>
                        <p class="mt-2">Không có nội dung chờ duyệt</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($cho_duyet_list as $nd): ?>
                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $nd['id'] ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= e($nd['tieu_de']) ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-person"></i> <?= e($nd['nguoi_trinh']) ?> 
                                            (<?= e($nd['ten_phong'] ?? 'Chưa gắn phòng') ?>)
                                        </small>
                                    </div>
                                    <small class="text-muted"><?= time_ago($nd['created_at']) ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($cho_duyet_list)): ?>
                <div class="card-footer text-center">
                    <a href="<?= BASE_URL ?>/noi-dung/danh-sach.php?trang_thai=cho_duyet" class="btn btn-sm btn-outline-primary">
                        Xem tất cả <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Phòng ban cần đốc việc -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Phòng ban cần đốc việc</h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($phong_can_doc)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle fs-1"></i>
                        <p class="mt-2">Tất cả phòng ban đang làm việc tốt</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($phong_can_doc as $phong): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?= e($phong['ten_phong']) ?></h6>
                                    <span class="badge bg-danger"><?= $phong['qua_han'] ?> quá hạn</span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <?php 
                                    $percent = round($phong['tien_do_tb']);
                                    $color = get_tien_do_color($percent);
                                    ?>
                                    <div class="progress-bar bg-<?= $color ?>" style="width: <?= $percent ?>%">
                                        <?= $percent ?>%
                                    </div>
                                </div>
                                <small class="text-muted"><?= $phong['tong_noi_dung'] ?> nội dung</small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
