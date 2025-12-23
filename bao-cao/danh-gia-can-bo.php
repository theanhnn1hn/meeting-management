<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');
$phong_ban_id = isset($_GET['phong_ban_id']) ? (int)$_GET['phong_ban_id'] : 0;
$export = isset($_GET['export']) && $_GET['export'] == 'excel';

// Get phong ban list
$stmt = $pdo->query("SELECT id, ten_phong FROM phong_ban ORDER BY ten_phong");
$phong_ban_list = $stmt->fetchAll();

// Build where
$where = ["nd.created_at BETWEEN ? AND ?"];
$params = [$from_date . ' 00:00:00', $to_date . ' 23:59:59'];

if ($phong_ban_id) {
    $where[] = "u.phong_ban_id = ?";
    $params[] = $phong_ban_id;
}

$where_sql = implode(' AND ', $where);

// Get user performance
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.ho_ten,
        u.email,
        pb.ten_phong,
        COUNT(DISTINCT nd.id) as tong_noi_dung,
        AVG(nd.tien_do) as tien_do_tb,
        SUM(CASE WHEN nd.trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
        SUM(CASE WHEN nd.trang_thai = 'tu_choi' THEN 1 ELSE 0 END) as tu_choi,
        COUNT(DISTINCT CASE WHEN nd.deadline < NOW() AND nd.tien_do < 100 THEN nd.id END) as qua_han,
        (SELECT COUNT(*) FROM comments c
         JOIN noi_dung nd2 ON c.noi_dung_id = nd2.id
         WHERE c.loai = 'doc_viec' AND nd2.nguoi_trinh_id = u.id
         AND c.created_at BETWEEN ? AND ?) as so_lan_doc_viec,
        (SELECT AVG(TIMESTAMPDIFF(DAY, nd3.created_at, nd3.updated_at))
         FROM noi_dung nd3
         WHERE nd3.nguoi_trinh_id = u.id
         AND nd3.trang_thai = 'da_duyet'
         AND nd3.created_at BETWEEN ? AND ?) as thoi_gian_hoan_thanh_tb
    FROM users u
    JOIN phong_ban pb ON u.phong_ban_id = pb.id
    LEFT JOIN noi_dung nd ON u.id = nd.nguoi_trinh_id AND $where_sql
    GROUP BY u.id
    HAVING tong_noi_dung > 0
    ORDER BY tien_do_tb DESC, da_duyet DESC
");
$execute_params = array_merge([$from_date . ' 00:00:00', $to_date . ' 23:59:59', $from_date . ' 00:00:00', $to_date . ' 23:59:59'], $params);
$stmt->execute($execute_params);
$user_performance = $stmt->fetchAll();

// Calculate score for each user (0-100)
foreach ($user_performance as &$up) {
    $score = 0;
    
    // Tiến độ (40 điểm)
    $score += ($up['tien_do_tb'] / 100) * 40;
    
    // Tỷ lệ duyệt (30 điểm)
    if ($up['tong_noi_dung'] > 0) {
        $score += ($up['da_duyet'] / $up['tong_noi_dung']) * 30;
    }
    
    // Không quá hạn (20 điểm)
    if ($up['qua_han'] == 0) {
        $score += 20;
    } elseif ($up['tong_noi_dung'] > 0) {
        $score += max(0, 20 - ($up['qua_han'] / $up['tong_noi_dung']) * 20);
    }
    
    // Ít bị đốc việc (10 điểm)
    $score += max(0, 10 - $up['so_lan_doc_viec']);
    
    $up['diem_danh_gia'] = round($score, 1);
    
    // Xếp loại
    if ($score >= 90) {
        $up['xep_loai'] = 'Xuất sắc';
        $up['xep_loai_class'] = 'success';
    } elseif ($score >= 80) {
        $up['xep_loai'] = 'Tốt';
        $up['xep_loai_class'] = 'primary';
    } elseif ($score >= 70) {
        $up['xep_loai'] = 'Khá';
        $up['xep_loai_class'] = 'info';
    } elseif ($score >= 60) {
        $up['xep_loai'] = 'Trung bình';
        $up['xep_loai_class'] = 'warning';
    } else {
        $up['xep_loai'] = 'Yếu';
        $up['xep_loai_class'] = 'danger';
    }
}
unset($up);

// Sort by score
usort($user_performance, function($a, $b) {
    return $b['diem_danh_gia'] <=> $a['diem_danh_gia'];
});

if ($export) {
    require_once __DIR__ . '/../../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
    use PhpOffice\PhpSpreadsheet\Style\Fill;
    use PhpOffice\PhpSpreadsheet\Style\Alignment;
    
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setCellValue('A1', 'BÁO CÁO ĐÁNH GIÁ CÁN BỘ');
    $sheet->mergeCells('A1:K1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    $sheet->setCellValue('A2', 'Từ ' . date('d/m/Y', strtotime($from_date)) . ' đến ' . date('d/m/Y', strtotime($to_date)));
    $sheet->mergeCells('A2:K2');
    
    $row = 4;
    $headers = ['Hạng', 'Họ tên', 'Phòng ban', 'Tổng NĐ', 'Đã duyệt', 'Tiến độ TB', 'Quá hạn', 'Đốc việc', 'TG hoàn thành', 'Điểm', 'Xếp loại'];
    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . $row, $header);
        $sheet->getStyle($col . $row)->getFont()->setBold(true);
        $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0D0D0');
        $col++;
    }
    
    $row++;
    $rank = 1;
    foreach ($user_performance as $up) {
        $sheet->setCellValue('A' . $row, $rank++);
        $sheet->setCellValue('B' . $row, $up['ho_ten']);
        $sheet->setCellValue('C' . $row, $up['ten_phong_ban']);
        $sheet->setCellValue('D' . $row, $up['tong_noi_dung']);
        $sheet->setCellValue('E' . $row, $up['da_duyet']);
        $sheet->setCellValue('F' . $row, round($up['tien_do_tb'], 1) . '%');
        $sheet->setCellValue('G' . $row, $up['qua_han']);
        $sheet->setCellValue('H' . $row, $up['so_lan_doc_viec']);
        $sheet->setCellValue('I' . $row, round($up['thoi_gian_hoan_thanh_tb'], 1) . ' ngày');
        $sheet->setCellValue('J' . $row, $up['diem_danh_gia']);
        $sheet->setCellValue('K' . $row, $up['xep_loai']);
        $row++;
    }
    
    foreach (range('A', 'K') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    
    $filename = 'bao-cao-danh-gia-can-bo-' . date('Y-m-d') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$page_title = "Báo cáo đánh giá cán bộ";
include __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4><i class="fas fa-award"></i> Báo cáo đánh giá cán bộ</h4>
                <a href="?from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>&phong_ban_id=<?php echo $phong_ban_id; ?>&export=excel" class="btn btn-success">
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
                        <div class="col-md-4">
                            <label class="form-label">Phòng ban</label>
                            <select name="phong_ban_id" class="form-select">
                                <option value="">Tất cả</option>
                                <?php foreach ($phong_ban_list as $pb): ?>
                                    <option value="<?php echo $pb['id']; ?>" <?php echo $pb['id'] == $phong_ban_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pb['ten_phong_ban']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
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

            <div class="alert alert-info">
                <strong>Tiêu chí đánh giá:</strong>
                <ul class="mb-0">
                    <li>Tiến độ công việc: 40 điểm</li>
                    <li>Tỷ lệ nội dung được duyệt: 30 điểm</li>
                    <li>Không quá hạn: 20 điểm</li>
                    <li>Ít bị đốc việc: 10 điểm</li>
                </ul>
                <strong>Xếp loại:</strong> Xuất sắc (≥90), Tốt (80-89), Khá (70-79), Trung bình (60-69), Yếu (<60)
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h3><?php echo count(array_filter($user_performance, fn($u) => $u['diem_danh_gia'] >= 90)); ?></h3>
                            <p class="mb-0">Xuất sắc</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h3><?php echo count(array_filter($user_performance, fn($u) => $u['diem_danh_gia'] >= 80 && $u['diem_danh_gia'] < 90)); ?></h3>
                            <p class="mb-0">Tốt</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h3><?php echo count(array_filter($user_performance, fn($u) => $u['diem_danh_gia'] >= 70 && $u['diem_danh_gia'] < 80)); ?></h3>
                            <p class="mb-0">Khá</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h3><?php echo count(array_filter($user_performance, fn($u) => $u['diem_danh_gia'] < 70)); ?></h3>
                            <p class="mb-0">TB & Yếu</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Bảng xếp hạng cán bộ</h6>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover" id="performanceTable">
                        <thead class="table-light">
                            <tr>
                                <th>Hạng</th>
                                <th>Họ tên</th>
                                <th>Phòng ban</th>
                                <th>Tổng NĐ</th>
                                <th>Đã duyệt</th>
                                <th>Tiến độ TB</th>
                                <th>Quá hạn</th>
                                <th>Đốc việc</th>
                                <th>TG hoàn thành</th>
                                <th>Điểm</th>
                                <th>Xếp loại</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $rank = 1;
                            foreach ($user_performance as $up):
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($rank <= 3): ?>
                                            <span class="badge bg-warning">
                                                <?php echo $rank == 1 ? '🥇' : ($rank == 2 ? '🥈' : '🥉'); ?>
                                                #<?php echo $rank; ?>
                                            </span>
                                        <?php else: ?>
                                            #<?php echo $rank; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($up['ho_ten']); ?></td>
                                    <td><?php echo htmlspecialchars($up['ten_phong_ban']); ?></td>
                                    <td><?php echo $up['tong_noi_dung']; ?></td>
                                    <td><?php echo $up['da_duyet']; ?></td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" style="width: <?php echo round($up['tien_do_tb'], 1); ?>%">
                                                <?php echo round($up['tien_do_tb'], 1); ?>%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($up['qua_han'] > 0): ?>
                                            <span class="badge bg-danger"><?php echo $up['qua_han']; ?></span>
                                        <?php else: ?>
                                            <span class="text-success">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $up['so_lan_doc_viec']; ?></td>
                                    <td><?php echo $up['thoi_gian_hoan_thanh_tb'] ? round($up['thoi_gian_hoan_thanh_tb'], 1) . ' ngày' : 'N/A'; ?></td>
                                    <td>
                                        <strong class="text-<?php echo $up['xep_loai_class']; ?>">
                                            <?php echo $up['diem_danh_gia']; ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $up['xep_loai_class']; ?>">
                                            <?php echo $up['xep_loai']; ?>
                                        </span>
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
</div>

<script>
$(document).ready(function() {
    $('#performanceTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/vi.json'
        },
        order: [[9, 'desc']],
        pageLength: 25
    });
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
