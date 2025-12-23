<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$trang_thai = $_GET['trang_thai'] ?? '';
$export = isset($_GET['export']) && $_GET['export'] == 'excel';

// Build where clause
$where = ["c.loai = 'doc_viec'"];
$params = [];

if ($from_date) {
    $where[] = "c.created_at >= ?";
    $params[] = $from_date . ' 00:00:00';
}

if ($to_date) {
    $where[] = "c.created_at <= ?";
    $params[] = $to_date . ' 23:59:59';
}

if ($trang_thai) {
    $where[] = "c.trang_thai = ?";
    $params[] = $trang_thai;
}

$where_sql = implode(' AND ', $where);

// Get statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as tong_doc_viec,
        SUM(CASE WHEN trang_thai = 'chua_xu_ly' THEN 1 ELSE 0 END) as chua_xu_ly,
        SUM(CASE WHEN trang_thai = 'dang_xu_ly' THEN 1 ELSE 0 END) as dang_xu_ly,
        SUM(CASE WHEN trang_thai = 'da_hoan_thanh' THEN 1 ELSE 0 END) as da_hoan_thanh
    FROM comments c
    WHERE $where_sql
");
$stmt->execute($params);
$stats = $stmt->fetch();

// Get doc viec list
$stmt = $pdo->prepare("
    SELECT
        c.*,
        nd.tieu_de,
        u_from.ho_ten as nguoi_doc_viec,
        u_to.ho_ten as nguoi_thuc_hien,
        pb.ten_phong,
        kh.ten_ky_hop
    FROM comments c
    JOIN noi_dung nd ON c.noi_dung_id = nd.id
    JOIN users u_from ON c.user_id = u_from.id
    JOIN users u_to ON nd.nguoi_trinh_id = u_to.id
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    JOIN phong_ban pb ON u_to.phong_ban_id = pb.id
    WHERE $where_sql
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$doc_viec_list = $stmt->fetchAll();

// By phong ban
$stmt = $pdo->prepare("
    SELECT
        pb.ten_phong,
        COUNT(c.id) as tong_doc_viec,
        SUM(CASE WHEN c.trang_thai = 'chua_xu_ly' THEN 1 ELSE 0 END) as chua_xu_ly,
        SUM(CASE WHEN c.trang_thai = 'da_hoan_thanh' THEN 1 ELSE 0 END) as da_hoan_thanh
    FROM comments c
    JOIN noi_dung nd ON c.noi_dung_id = nd.id
    JOIN users u ON nd.nguoi_trinh_id = u.id
    JOIN phong_ban pb ON u.phong_ban_id = pb.id
    WHERE $where_sql
    GROUP BY pb.id
    ORDER BY tong_doc_viec DESC
");
$stmt->execute($params);
$phong_ban_stats = $stmt->fetchAll();

if ($export) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'BÁO CÁO ĐỐC VIỆC');
    $sheet->mergeCells('A1:I1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A2', 'Từ ' . date('d/m/Y', strtotime($from_date)) . ' đến ' . date('d/m/Y', strtotime($to_date)));
    $sheet->mergeCells('A2:I2');
    
    $row = 4;
    $headers = ['STT', 'Kỳ họp', 'Nội dung', 'Phòng ban', 'Người đốc', 'Người thực hiện', 'Nội dung đốc', 'Trạng thái', 'Ngày đốc'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0D0D0');
        $col++;
    }
    
    $row++;
    $stt = 1;
    foreach ($doc_viec_list as $dv) {
        $sheet->setCellValue('A' . $row, $stt++);
        $sheet->setCellValue('B' . $row, $dv['ten_ky_hop']);
        $sheet->setCellValue('C' . $row, $dv['ten_noi_dung']);
        $sheet->setCellValue('D' . $row, $dv['ten_phong_ban']);
        $sheet->setCellValue('E' . $row, $dv['nguoi_doc_viec']);
        $sheet->setCellValue('F' . $row, $dv['nguoi_thuc_hien']);
        $sheet->setCellValue('G' . $row, $dv['noi_dung']);
        $sheet->setCellValue('H' . $row, $dv['trang_thai']);
        $sheet->setCellValue('I' . $row, date('d/m/Y H:i', strtotime($dv['created_at'])));
        $row++;
    }
    
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $filename = 'bao-cao-doc-viec-' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$page_title = "Báo cáo đốc việc";
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-tasks"></i> Báo cáo đốc việc</h4>
                <a href="?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&trang_thai=<?php echo $trang_thai; ?>&export=excel" class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Xuất Excel
                </a>
            </div>

            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Từ ngày</label>
                            <input type="date" name="from_date" class="form-control" value="<?php echo $from_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Đến ngày</label>
                            <input type="date" name="to_date" class="form-control" value="<?php echo $to_date; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Trạng thái</label>
                            <select name="trang_thai" class="form-select">
                                <option value="">Tất cả</option>
                                <option value="chua_xu_ly" <?php echo $trang_thai == 'chua_xu_ly' ? 'selected' : ''; ?>>Chưa xử lý</option>
                                <option value="dang_xu_ly" <?php echo $trang_thai == 'dang_xu_ly' ? 'selected' : ''; ?>>Đang xử lý</option>
                                <option value="da_hoan_thanh" <?php echo $trang_thai == 'da_hoan_thanh' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Lọc
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h3><?php echo $stats['tong_doc_viec']; ?></h3>
                            <p class="mb-0">Tổng đốc việc</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h3><?php echo $stats['chua_xu_ly']; ?></h3>
                            <p class="mb-0">Chưa xử lý</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h3><?php echo $stats['dang_xu_ly']; ?></h3>
                            <p class="mb-0">Đang xử lý</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3><?php echo $stats['da_hoan_thanh']; ?></h3>
                            <p class="mb-0">Đã hoàn thành</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Trạng thái đốc việc</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Đốc việc theo phòng ban</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="phongBanChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Danh sách đốc việc</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="docViecTable">
                        <thead class="table-light">
                            <tr>
                                <th>STT</th>
                                <th>Kỳ họp</th>
                                <th>Nội dung</th>
                                <th>Phòng ban</th>
                                <th>Người đốc</th>
                                <th>Nội dung đốc</th>
                                <th>Trạng thái</th>
                                <th>Ngày đốc</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stt = 1;
                            foreach ($doc_viec_list as $dv):
                                $trang_thai_class = [
                                    'chua_xu_ly' => 'danger',
                                    'dang_xu_ly' => 'warning',
                                    'da_hoan_thanh' => 'success'
                                ];
                                $trang_thai_text = [
                                    'chua_xu_ly' => 'Chưa xử lý',
                                    'dang_xu_ly' => 'Đang xử lý',
                                    'da_hoan_thanh' => 'Đã hoàn thành'
                                ];
                            ?>
                                <tr>
                                    <td><?php echo $stt++; ?></td>
                                    <td><?php echo htmlspecialchars($dv['ten_ky_hop']); ?></td>
                                    <td><?php echo htmlspecialchars($dv['ten_noi_dung']); ?></td>
                                    <td><?php echo htmlspecialchars($dv['ten_phong_ban']); ?></td>
                                    <td><?php echo htmlspecialchars($dv['nguoi_doc_viec']); ?></td>
                                    <td><?php echo htmlspecialchars(mb_substr($dv['noi_dung'], 0, 100)); ?>...</td>
                                    <td>
                                        <span class="badge bg-<?php echo $trang_thai_class[$dv['trang_thai']] ?? 'secondary'; ?>">
                                            <?php echo $trang_thai_text[$dv['trang_thai']] ?? $dv['trang_thai']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($dv['created_at'])); ?></td>
                                    <td>
                                        <a href="../noi-dung/chi-tiet.php?id=<?php echo $dv['noi_dung_id']; ?>" 
                                           class="btn btn-sm btn-info" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
$(document).ready(function() {
    $('#docViecTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
        },
        order: [[7, 'desc']]
    });
    
    // Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Chưa xử lý', 'Đang xử lý', 'Đã hoàn thành'],
            datasets: [{
                data: [
                    <?php echo $stats['chua_xu_ly']; ?>,
                    <?php echo $stats['dang_xu_ly']; ?>,
                    <?php echo $stats['da_hoan_thanh']; ?>
                ],
                backgroundColor: ['#dc3545', '#ffc107', '#28a745']
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
    
    // Phong Ban Chart
    const pbCtx = document.getElementById('phongBanChart').getContext('2d');
    new Chart(pbCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($pb) { return "'" . addslashes($pb['ten_phong_ban']) . "'"; }, $phong_ban_stats)); ?>],
            datasets: [{
                label: 'Tổng đốc việc',
                data: [<?php echo implode(',', array_map(function($pb) { return $pb['tong_doc_viec']; }, $phong_ban_stats)); ?>],
                backgroundColor: '#007bff'
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
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
