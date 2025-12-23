<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// General stats
$stmt = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM ky_hop WHERE ngay_hop BETWEEN ? AND ?) as tong_ky_hop,
        (SELECT COUNT(*) FROM noi_dung WHERE created_at BETWEEN ? AND ?) as tong_noi_dung,
        (SELECT COUNT(*) FROM users WHERE trang_thai = 1) as tong_can_bo,
        (SELECT COUNT(*) FROM phong_ban) as tong_phong_ban,
        (SELECT AVG(tien_do) FROM noi_dung WHERE created_at BETWEEN ? AND ?) as tien_do_chung,
        (SELECT COUNT(*) FROM noi_dung WHERE trang_thai = 'da_duyet' AND created_at BETWEEN ? AND ?) as da_duyet,
        (SELECT COUNT(*) FROM noi_dung WHERE trang_thai = 'cho_duyet' AND created_at BETWEEN ? AND ?) as cho_duyet,
        (SELECT COUNT(*) FROM comments WHERE loai = 'doc_viec' AND created_at BETWEEN ? AND ?) as tong_doc_viec
");
$params = [
    $from_date, $to_date,
    $from_date . ' 00:00:00', $to_date . ' 23:59:59',
    $from_date . ' 00:00:00', $to_date . ' 23:59:59',
    $from_date . ' 00:00:00', $to_date . ' 23:59:59',
    $from_date . ' 00:00:00', $to_date . ' 23:59:59',
    $from_date . ' 00:00:00', $to_date . ' 23:59:59'
];
$stmt->execute($params);
$general_stats = $stmt->fetch();

// Content by status
$stmt = $pdo->prepare("
    SELECT 
        trang_thai,
        COUNT(*) as count
    FROM noi_dung
    WHERE created_at BETWEEN ? AND ?
    GROUP BY trang_thai
");
$stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$status_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Content by month
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as thang,
        COUNT(*) as count
    FROM noi_dung
    WHERE created_at BETWEEN ? AND ?
    GROUP BY thang
    ORDER BY thang
");
$stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$monthly_stats = $stmt->fetchAll();

// Top phong ban
$stmt = $pdo->prepare("
    SELECT 
        pb.ten_phong,
        COUNT(nd.id) as tong_noi_dung,
        AVG(nd.tien_do) as tien_do_tb,
        SUM(CASE WHEN nd.trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet
    FROM phong_ban pb
    LEFT JOIN users u ON pb.id = u.phong_ban_id
    LEFT JOIN noi_dung nd ON u.id = nd.nguoi_trinh_id 
        AND nd.created_at BETWEEN ? AND ?
    GROUP BY pb.id
    HAVING tong_noi_dung > 0
    ORDER BY tien_do_tb DESC
    LIMIT 10
");
$stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$top_phong_ban = $stmt->fetchAll();

// Top users
$stmt = $pdo->prepare("
    SELECT 
        u.ho_ten,
        pb.ten_phong,
        COUNT(nd.id) as tong_noi_dung,
        AVG(nd.tien_do) as tien_do_tb,
        SUM(CASE WHEN nd.trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet
    FROM users u
    JOIN phong_ban pb ON u.phong_ban_id = pb.id
    LEFT JOIN noi_dung nd ON u.id = nd.nguoi_trinh_id 
        AND nd.created_at BETWEEN ? AND ?
    GROUP BY u.id
    HAVING tong_noi_dung > 0
    ORDER BY tien_do_tb DESC
    LIMIT 10
");
$stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$top_users = $stmt->fetchAll();

// Recent activities
$stmt = $pdo->prepare("
    SELECT *
    FROM activity_log
    WHERE created_at BETWEEN ? AND ?
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$recent_activities = $stmt->fetchAll();

$page_title = "Thống kê chung";
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-chart-bar"></i> Thống kê chung</h4>
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print"></i> In báo cáo
                </button>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Xem
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Main Stats Cards -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo $general_stats['tong_ky_hop']; ?></h3>
                                    <p class="mb-0">Kỳ họp</p>
                                </div>
                                <i class="fas fa-calendar-alt fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo $general_stats['tong_noi_dung']; ?></h3>
                                    <p class="mb-0">Nội dung</p>
                                </div>
                                <i class="fas fa-file-alt fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo round($general_stats['tien_do_chung'], 1); ?>%</h3>
                                    <p class="mb-0">Tiến độ chung</p>
                                </div>
                                <i class="fas fa-chart-line fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-0"><?php echo $general_stats['tong_doc_viec']; ?></h3>
                                    <p class="mb-0">Đốc việc</p>
                                </div>
                                <i class="fas fa-tasks fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">Đã duyệt</h6>
                            <h3 class="text-success"><?php echo $general_stats['da_duyet']; ?></h3>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-success" style="width: <?php echo $general_stats['tong_noi_dung'] > 0 ? ($general_stats['da_duyet'] / $general_stats['tong_noi_dung'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">Chờ duyệt</h6>
                            <h3 class="text-warning"><?php echo $general_stats['cho_duyet']; ?></h3>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-warning" style="width: <?php echo $general_stats['tong_noi_dung'] > 0 ? ($general_stats['cho_duyet'] / $general_stats['tong_noi_dung'] * 100) : 0; ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="text-muted">Tổng cán bộ / Phòng ban</h6>
                            <h3><?php echo $general_stats['tong_can_bo']; ?> / <?php echo $general_stats['tong_phong_ban']; ?></h3>
                            <small class="text-muted">Trung bình: <?php echo round($general_stats['tong_can_bo'] / max(1, $general_stats['tong_phong_ban']), 1); ?> cán bộ/phòng</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row 1 -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Phân bố trạng thái</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Nội dung theo tháng</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Rankings -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Top 10 phòng ban</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="phongBanChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Top 10 cán bộ</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Hạng</th>
                                        <th>Họ tên</th>
                                        <th>Phòng ban</th>
                                        <th>Tiến độ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $rank = 1;
                                    foreach ($top_users as $user):
                                    ?>
                                        <tr>
                                            <td>
                                                <?php if ($rank <= 3): ?>
                                                    <span class="badge bg-warning">
                                                        <?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    #<?php echo $rank; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['ho_ten']); ?></td>
                                            <td><small><?php echo htmlspecialchars($user['ten_phong']); ?></small></td>
                                            <td>
                                                <div class="progress" style="height: 18px; min-width: 60px;">
                                                    <div class="progress-bar bg-success" style="width: <?php echo round($user['tien_do_tb'], 1); ?>%">
                                                        <?php echo round($user['tien_do_tb'], 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                    $rank++;
                                    endforeach;
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Hoạt động gần đây (20 hoạt động)</h6>
                        </div>
                        <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_activities as $activity): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <i class="fas fa-circle text-primary" style="font-size: 8px;"></i>
                                                <?php echo htmlspecialchars($activity['hanh_dong']); ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo time_ago($activity['created_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Chờ duyệt', 'Đã duyệt', 'Từ chối', 'Đang xử lý', 'Hoàn thành'],
            datasets: [{
                data: [
                    <?php echo $status_stats['cho_duyet'] ?? 0; ?>,
                    <?php echo $status_stats['da_duyet'] ?? 0; ?>,
                    <?php echo $status_stats['tu_choi'] ?? 0; ?>,
                    <?php echo $status_stats['dang_xu_ly'] ?? 0; ?>,
                    <?php echo $status_stats['hoan_thanh'] ?? 0; ?>
                ],
                backgroundColor: ['#ffc107', '#28a745', '#dc3545', '#17a2b8', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Monthly Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(',', array_map(function($m) { return "'" . $m['thang'] . "'"; }, $monthly_stats)); ?>],
            datasets: [{
                label: 'Số nội dung',
                data: [<?php echo implode(',', array_map(function($m) { return $m['count']; }, $monthly_stats)); ?>],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // Phong Ban Chart
    const pbCtx = document.getElementById('phongBanChart').getContext('2d');
    new Chart(pbCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($pb) { return "'" . addslashes($pb['ten_phong']) . "'"; }, $top_phong_ban)); ?>],
            datasets: [{
                label: 'Tiến độ TB (%)',
                data: [<?php echo implode(',', array_map(function($pb) { return round($pb['tien_do_tb'], 1); }, $top_phong_ban)); ?>],
                backgroundColor: '#28a745'
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    max: 100
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});
</script>

<style>
@media print {
    .btn, .card-header, nav { display: none !important; }
    .card { page-break-inside: avoid; }
}
</style>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
