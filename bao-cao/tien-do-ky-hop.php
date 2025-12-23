<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

// Get filters
$ky_hop_id = isset($_GET['ky_hop_id']) ? (int)$_GET['ky_hop_id'] : 0;
$export = isset($_GET['export']) && $_GET['export'] == 'excel';

// Get all ky hop for select
$stmt = $pdo->query("
    SELECT id, ten_ky_hop, ngay_hop 
    FROM ky_hop 
    ORDER BY ngay_hop DESC
");
$ky_hop_list = $stmt->fetchAll();

// Default to latest if not specified
if (!$ky_hop_id && !empty($ky_hop_list)) {
    $ky_hop_id = $ky_hop_list[0]['id'];
}

if ($ky_hop_id) {
    // Get ky hop info
    $stmt = $pdo->prepare("SELECT * FROM ky_hop WHERE id = ?");
    $stmt->execute([$ky_hop_id]);
    $ky_hop = $stmt->fetch();
    
    // Get statistics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as tong_noi_dung,
            SUM(CASE WHEN trang_thai = 'cho_duyet' THEN 1 ELSE 0 END) as cho_duyet,
            SUM(CASE WHEN trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
            SUM(CASE WHEN trang_thai = 'tu_choi' THEN 1 ELSE 0 END) as tu_choi,
            SUM(CASE WHEN trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as hoan_thanh,
            AVG(tien_do) as tien_do_trung_binh
        FROM noi_dung
        WHERE ky_hop_id = ?
    ");
    $stmt->execute([$ky_hop_id]);
    $stats = $stmt->fetch();

    // Get progress by phong ban
    $stmt = $pdo->prepare("
        SELECT
            pb.ten_phong,
            COUNT(nd.id) as tong_noi_dung,
            AVG(nd.tien_do) as tien_do_tb,
            SUM(CASE WHEN nd.trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
            SUM(CASE WHEN nd.trang_thai = 'tu_choi' THEN 1 ELSE 0 END) as tu_choi
        FROM phong_ban pb
        LEFT JOIN users u ON pb.id = u.phong_ban_id
        LEFT JOIN noi_dung nd ON u.id = nd.nguoi_trinh_id AND nd.ky_hop_id = ?
        GROUP BY pb.id, pb.ten_phong
        HAVING tong_noi_dung > 0
        ORDER BY tien_do_tb DESC
    ");
    $stmt->execute([$ky_hop_id]);
    $phong_ban_stats = $stmt->fetchAll();

    // Get content details
    $stmt = $pdo->prepare("
        SELECT
            nd.*,
            u.ho_ten as nguoi_trinh,
            pb.ten_phong,
            cq.ten_co_quan
        FROM noi_dung nd
        JOIN users u ON nd.nguoi_trinh_id = u.id
        JOIN phong_ban pb ON u.phong_ban_id = pb.id
        LEFT JOIN co_quan cq ON nd.co_quan_trinh_id = cq.id
        WHERE nd.ky_hop_id = ?
        ORDER BY nd.trang_thai, nd.tien_do DESC
    ");
    $stmt->execute([$ky_hop_id]);
    $noi_dung_list = $stmt->fetchAll();
}

// Export to Excel
if ($export && $ky_hop_id) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Border;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // Title
    $sheet->setCellValue('A1', 'BÁO CÁO TIẾN ĐỘ KỲ HỌP');
    $sheet->mergeCells('A1:H1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A2', 'Kỳ họp: ' . $ky_hop['ten_ky_hop']);
    $sheet->mergeCells('A2:H2');
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
    
    $sheet->setCellValue('A3', 'Ngày họp: ' . date('d/m/Y', strtotime($ky_hop['ngay_hop'])));
    $sheet->mergeCells('A3:H3');
    
    // Statistics
    $row = 5;
    $sheet->setCellValue('A' . $row, 'TỔNG QUAN');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
    
    $row++;
    $sheet->setCellValue('A' . $row, 'Tổng nội dung: ' . $stats['tong_noi_dung']);
    $row++;
    $sheet->setCellValue('A' . $row, 'Chờ duyệt: ' . $stats['cho_duyet']);
    $row++;
    $sheet->setCellValue('A' . $row, 'Đã duyệt: ' . $stats['da_duyet']);
    $row++;
    $sheet->setCellValue('A' . $row, 'Từ chối: ' . $stats['tu_choi']);
    $row++;
    $sheet->setCellValue('A' . $row, 'Tiến độ trung bình: ' . round($stats['tien_do_trung_binh'], 1) . '%');
    
    // Details table
    $row += 2;
    $sheet->setCellValue('A' . $row, 'DANH SÁCH NỘI DUNG');
    $sheet->mergeCells('A' . $row . ':H' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
    
    $row++;
    $headers = ['STT', 'Tên nội dung', 'Phòng ban', 'Người trình', 'Trạng thái', 'Tiến độ', 'Deadline', 'Ghi chú'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0D0D0');
        $col++;
    }
    
    $row++;
    $stt = 1;
    foreach ($noi_dung_list as $nd) {
        $trang_thai_text = [
            'cho_duyet' => 'Chờ duyệt',
            'da_duyet' => 'Đã duyệt',
            'tu_choi' => 'Từ chối',
            'dang_xu_ly' => 'Đang xử lý',
            'hoan_thanh' => 'Hoàn thành'
        ];

        $sheet->setCellValue('A' . $row, $stt++);
        $sheet->setCellValue('B' . $row, $nd['tieu_de']);
        $sheet->setCellValue('C' . $row, $nd['ten_phong']);
        $sheet->setCellValue('D' . $row, $nd['nguoi_trinh']);
        $sheet->setCellValue('E' . $row, $trang_thai_text[$nd['trang_thai']] ?? $nd['trang_thai']);
        $sheet->setCellValue('F' . $row, $nd['tien_do'] . '%');
        $sheet->setCellValue('G' . $row, $nd['deadline'] ? date('d/m/Y', strtotime($nd['deadline'])) : '');
        $sheet->setCellValue('H' . $row, $nd['ghi_chu']);

        $row++;
    }
    
    // Auto size columns
    foreach (range('A', 'H') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    // Borders
    $sheet->getStyle('A5:H' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Output
    $filename = 'bao-cao-tien-do-ky-hop-' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$page_title = "Báo cáo tiến độ kỳ họp";
include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="bi bi-chart-line"></i> Báo cáo tiến độ kỳ họp</h4>
                <?php if ($ky_hop_id): ?>
                    <a href="?ky_hop_id=<?php echo $ky_hop_id; ?>&export=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>
                <?php endif; ?>
            </div>

            <!-- Filter -->
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Chọn kỳ họp</label>
                            <select name="ky_hop_id" class="form-select" onchange="this.form.submit()">
                                <option value="">-- Chọn kỳ họp --</option>
                                <?php foreach ($ky_hop_list as $kh): ?>
                                    <option value="<?php echo $kh['id']; ?>" <?php echo $kh['id'] == $ky_hop_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kh['ten_ky_hop']); ?> - <?php echo date('d/m/Y', strtotime($kh['ngay_hop'])); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($ky_hop_id && $ky_hop): ?>
                <!-- Statistics Cards -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h3><?php echo $stats['tong_noi_dung']; ?></h3>
                                <p class="mb-0">Tổng nội dung</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h3><?= $stats['da_duyet'] ?></h3>
                                <p class="mb-0">Đã duyệt</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h3><?php echo $stats['cho_duyet']; ?></h3>
                                <p class="mb-0">Chờ duyệt</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body">
                                <h3><?php echo $stats['tu_choi']; ?></h3>
                                <p class="mb-0">Từ chối</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
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
                                <h6 class="mb-0">Tiến độ theo phòng ban</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="progressChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Table -->
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Chi tiết nội dung</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover" id="detailsTable">
                            <thead class="table-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Tên nội dung</th>
                                    <th>Phòng ban</th>
                                    <th>Người trình</th>
                                    <th>Trạng thái</th>
                                    <th>Tiến độ</th>
                                    <th>Deadline</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stt = 1;
                                foreach ($noi_dung_list as $nd):
                                    $trang_thai_class = [
                                        'cho_duyet' => 'warning',
                                        'da_duyet' => 'success',
                                        'tu_choi' => 'danger',
                                        'dang_xu_ly' => 'info',
                                        'hoan_thanh' => 'primary'
                                    ];
                                    $trang_thai_text = [
                                        'cho_duyet' => 'Chờ duyệt',
                                        'da_duyet' => 'Đã duyệt',
                                        'tu_choi' => 'Từ chối',
                                        'dang_xu_ly' => 'Đang xử lý',
                                        'hoan_thanh' => 'Hoàn thành'
                                    ];

                                    $progress_class = $nd['tien_do'] >= 80 ? 'success' : ($nd['tien_do'] >= 50 ? 'warning' : 'danger');
                                ?>
                                    <tr>
                                        <td><?= $stt++ ?></td>
                                        <td><?= e($nd['tieu_de']) ?></td>
                                        <td><?= e($nd['ten_phong']) ?></td>
                                        <td><?= e($nd['nguoi_trinh']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $trang_thai_class[$nd['trang_thai']] ?? 'secondary' ?>">
                                                <?= $trang_thai_text[$nd['trang_thai']] ?? $nd['trang_thai'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar bg-<?= $progress_class ?>"
                                                     style="width: <?= $nd['tien_do'] ?>%">
                                                    <?= $nd['tien_do'] ?>%
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= $nd['deadline'] ? date('d/m/Y', strtotime($nd['deadline'])) : '' ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $nd['id'] ?>"
                                               class="btn btn-sm btn-info" target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Vui lòng chọn kỳ họp để xem báo cáo
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    <?php if (!empty($noi_dung_list)): ?>
    $('#detailsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
        },
        order: [[5, 'asc']]
    });
    <?php endif; ?>

    <?php if ($ky_hop_id && $stats): ?>
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Đã duyệt', 'Chờ duyệt', 'Từ chối', 'Đang xử lý', 'Hoàn thành'],
            datasets: [{
                data: [
                    <?= $stats['da_duyet'] ?>,
                    <?= $stats['cho_duyet'] ?>,
                    <?= $stats['tu_choi'] ?>,
                    <?= $stats['tong_noi_dung'] - $stats['da_duyet'] - $stats['cho_duyet'] - $stats['tu_choi'] - $stats['hoan_thanh'] ?>,
                    <?= $stats['hoan_thanh'] ?>
                ],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545', '#17a2b8', '#007bff']
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

    // Progress Chart
    const progressCtx = document.getElementById('progressChart').getContext('2d');
    new Chart(progressCtx, {
        type: 'bar',
        data: {
            labels: [<?= implode(',', array_map(function($pb) { return "'" . addslashes($pb['ten_phong']) . "'"; }, $phong_ban_stats)) ?>],
            datasets: [{
                label: 'Tiến độ TB (%)',
                data: [<?= implode(',', array_map(function($pb) { return round($pb['tien_do_tb'], 1); }, $phong_ban_stats)) ?>],
                backgroundColor: '#007bff'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
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
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
