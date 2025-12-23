<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

check_login();
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

$phong_ban_id = isset($_GET['phong_ban_id']) ? (int)$_GET['phong_ban_id'] : 0;
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$export = isset($_GET['export']) && $_GET['export'] == 'excel';

// Get phong ban list
$stmt = $pdo->query("SELECT id, ten_phong FROM phong_ban ORDER BY ten_phong");
$phong_ban_list = $stmt->fetchAll();

if ($phong_ban_id) {
    $stmt = $pdo->prepare("SELECT * FROM phong_ban WHERE id = ?");
    $stmt->execute([$phong_ban_id]);
    $phong_ban = $stmt->fetch();
    
    // Stats
    $stmt = $pdo->prepare("
        SELECT
            COUNT(DISTINCT nd.id) as tong_noi_dung,
            AVG(nd.tien_do) as tien_do_tb,
            SUM(CASE WHEN nd.trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
            SUM(CASE WHEN nd.trang_thai = 'tu_choi' THEN 1 ELSE 0 END) as tu_choi,
            COUNT(DISTINCT u.id) as so_can_bo
        FROM users u
        LEFT JOIN noi_dung nd ON u.id = nd.nguoi_trinh_id
            AND nd.created_at BETWEEN ? AND ?
        WHERE u.phong_ban_id = ?
    ");
    $stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59', $phong_ban_id]);
    $stats = $stmt->fetch();

    // By user
    $stmt = $pdo->prepare("
        SELECT
            u.id, u.ho_ten, u.email,
            COUNT(nd.id) as tong_noi_dung,
            AVG(nd.tien_do) as tien_do_tb,
            SUM(CASE WHEN nd.trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet
        FROM users u
        LEFT JOIN noi_dung nd ON u.id = nd.nguoi_trinh_id
            AND nd.created_at BETWEEN ? AND ?
        WHERE u.phong_ban_id = ?
        GROUP BY u.id
        ORDER BY tien_do_tb DESC
    ");
    $stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59', $phong_ban_id]);
    $user_stats = $stmt->fetchAll();
    
    // Content list
    $stmt = $pdo->prepare("
        SELECT 
            nd.*,
            u.ho_ten as nguoi_trinh,
            kh.ten_ky_hop
        FROM noi_dung nd
        JOIN users u ON nd.nguoi_trinh_id = u.id
        JOIN ky_hop kh ON nd.ky_hop_id = kh.id
        WHERE u.phong_ban_id = ?
        AND nd.created_at BETWEEN ? AND ?
        ORDER BY nd.created_at DESC
    ");
    $stmt->execute([$phong_ban_id, $from_date . ' 00:00:00', $to_date . ' 23:59:59']);
    $noi_dung_list = $stmt->fetchAll();
}

if ($export && $phong_ban_id) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'BÁO CÁO TIẾN ĐỘ PHÒNG BAN');
    $sheet->mergeCells('A1:G1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A2', 'Phòng ban: ' . $phong_ban['ten_phong']);
    $sheet->mergeCells('A2:G2');
    
    $sheet->setCellValue('A3', 'Từ ngày ' . date('d/m/Y', strtotime($from_date)) . ' đến ' . date('d/m/Y', strtotime($to_date)));
    $sheet->mergeCells('A3:G3');
    
    $row = 5;
    $headers = ['STT', 'Cán bộ', 'Email', 'Tổng NĐ', 'Đã duyệt', 'Tiến độ TB', 'Ghi chú'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0D0D0');
        $col++;
    }
    
    $row++;
    $stt = 1;
    foreach ($user_stats as $us) {
        $sheet->setCellValue('A' . $row, $stt++);
        $sheet->setCellValue('B' . $row, $us['ho_ten']);
        $sheet->setCellValue('C' . $row, $us['email']);
        $sheet->setCellValue('D' . $row, $us['tong_noi_dung']);
        $sheet->setCellValue('E' . $row, $us['da_duyet']);
        $sheet->setCellValue('F' . $row, round($us['tien_do_tb'], 1) . '%');
        $sheet->setCellValue('G' . $row, '');
        $row++;
    }
    
    foreach (range('A', 'G') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $filename = 'bao-cao-phong-ban-' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$page_title = "Báo cáo tiến độ phòng ban";
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-building"></i> Báo cáo tiến độ phòng ban</h4>
                <?php if ($phong_ban_id): ?>
                    <a href="?phong_ban_id=<?php echo $phong_ban_id; ?>&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&export=excel" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>
                <?php endif; ?>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Phòng ban</label>
                            <select name="phong_ban_id" class="form-select" required>
                                <option value="">-- Chọn phòng ban --</option>
                                <?php foreach ($phong_ban_list as $pb): ?>
                                    <option value="<?php echo $pb['id']; ?>" <?php echo $pb['id'] == $phong_ban_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pb['ten_phong']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Xem
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <?php if ($phong_ban_id && $phong_ban): ?>
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
                                <h3><?php echo round($stats['tien_do_tb'], 1); ?>%</h3>
                                <p class="mb-0">Tiến độ TB</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h3><?php echo $stats['da_duyet']; ?></h3>
                                <p class="mb-0">Đã duyệt</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h3><?php echo $stats['so_can_bo']; ?></h3>
                                <p class="mb-0">Số cán bộ</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Tiến độ theo cán bộ</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="userChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Top 5 cán bộ</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Cán bộ</th>
                                            <th>Nội dung</th>
                                            <th>Tiến độ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $top5 = array_slice($user_stats, 0, 5);
                                        foreach ($top5 as $us):
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($us['ho_ten']); ?></td>
                                                <td><?php echo $us['tong_noi_dung']; ?></td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" style="width: <?php echo round($us['tien_do_tb'], 1); ?>%">
                                                            <?php echo round($us['tien_do_tb'], 1); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Danh sách nội dung</h6>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered" id="contentTable">
                            <thead class="table-light">
                                <tr>
                                    <th>STT</th>
                                    <th>Kỳ họp</th>
                                    <th>Nội dung</th>
                                    <th>Người trình</th>
                                    <th>Trạng thái</th>
                                    <th>Tiến độ</th>
                                    <th>Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stt = 1;
                                foreach ($noi_dung_list as $nd):
                                    $trang_thai_class = [
                                        'cho_duyet' => 'warning',
                                        'lanh_dao_duyet' => 'success',
                                        'tu_choi' => 'danger'
                                    ];
                                ?>
                                    <tr>
                                        <td><?php echo $stt++; ?></td>
                                        <td><?php echo htmlspecialchars($nd['ten_ky_hop']); ?></td>
                                        <td><?php echo htmlspecialchars($nd['tieu_de']); ?></td>
                                        <td><?php echo htmlspecialchars($nd['nguoi_trinh']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $trang_thai_class[$nd['trang_thai']] ?? 'secondary'; ?>">
                                                <?php echo $nd['trang_thai']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $nd['tien_do']; ?>%</td>
                                        <td><?php echo date('d/m/Y', strtotime($nd['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    $('#contentTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
        }
    });
    
    <?php if ($phong_ban_id && !empty($user_stats)): ?>
    const ctx = document.getElementById('userChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($u) { return "'" . addslashes($u['ho_ten']) . "'"; }, $user_stats)); ?>],
            datasets: [{
                label: 'Tiến độ TB (%)',
                data: [<?php echo implode(',', array_map(function($u) { return round($u['tien_do_tb'], 1); }, $user_stats)); ?>],
                backgroundColor: '#007bff'
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
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
