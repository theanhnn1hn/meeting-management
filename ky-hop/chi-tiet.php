<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

$page_title = 'Chi tiết Kỳ họp';

$id = $_GET['id'] ?? 0;

// Lấy thông tin kỳ họp
$stmt = $pdo->prepare("
    SELECT kh.*, u.ho_ten as nguoi_tao
    FROM ky_hop kh
    LEFT JOIN users u ON kh.created_by = u.id
    WHERE kh.id = ?
");
$stmt->execute([$id]);
$ky_hop = $stmt->fetch();

if (!$ky_hop) {
    redirect(BASE_URL . '/ky-hop/danh-sach.php', 'Không tìm thấy kỳ họp', 'error');
}

// Thống kê nội dung
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as tong_noi_dung,
        SUM(CASE WHEN trang_thai = 'cho_duyet' THEN 1 ELSE 0 END) as cho_duyet,
        SUM(CASE WHEN trang_thai = 'da_duyet' THEN 1 ELSE 0 END) as da_duyet,
        SUM(CASE WHEN trang_thai = 'tu_choi' THEN 1 ELSE 0 END) as tu_choi,
        SUM(CASE WHEN trang_thai = 'dang_xu_ly' THEN 1 ELSE 0 END) as dang_xu_ly,
        SUM(CASE WHEN trang_thai = 'hoan_thanh' THEN 1 ELSE 0 END) as hoan_thanh,
        AVG(tien_do) as tien_do_tb
    FROM noi_dung WHERE ky_hop_id = ?
");
$stmt->execute([$id]);
$stats = $stmt->fetch();

// Danh sách nội dung
$stmt = $pdo->prepare("
    SELECT nd.*, 
           u.ho_ten as nguoi_trinh,
           pb.ten_phong,
           cq.ten_co_quan,
           phe_duyet.ho_ten as nguoi_phe_duyet
    FROM noi_dung nd
    LEFT JOIN users u ON nd.nguoi_trinh_id = u.id
    LEFT JOIN users phe_duyet ON nd.nguoi_phe_duyet_id = phe_duyet.id
    LEFT JOIN phong_ban pb ON nd.phong_ban_id = pb.id
    LEFT JOIN co_quan cq ON nd.co_quan_trinh_id = cq.id
    WHERE nd.ky_hop_id = ?
    ORDER BY nd.stt ASC, nd.created_at ASC
");
$stmt->execute([$id]);
$noi_dung_list = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard/">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="danh-sach.php">Kỳ họp</a></li>
                <li class="breadcrumb-item active"><?= e($ky_hop['so_ky_hop']) ?></li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-calendar-event"></i> <?= e($ky_hop['ten_ky_hop']) ?></h2>
        <p class="text-muted mb-0">Số kỳ họp: <?= e($ky_hop['so_ky_hop']) ?></p>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($_SESSION['chuc_vu'] === ROLE_CHANH_VP): ?>
            <div class="btn-group">
                <a href="sua.php?id=<?= $id ?>" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Sửa
                </a>
                <a href="sap-xep-chuong-trinh.php?id=<?= $id ?>" class="btn btn-info">
                    <i class="bi bi-list-ol"></i> Sắp xếp
                </a>
                <button onclick="deleteKyHop()" class="btn btn-danger">
                    <i class="bi bi-trash"></i> Xóa
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Thông tin kỳ họp -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Thông tin kỳ họp</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Ngày họp</label>
                        <div><strong><?= format_date($ky_hop['ngay_hop']) ?></strong></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Địa điểm</label>
                        <div><?= e($ky_hop['dia_diem'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Chủ trì</label>
                        <div><?= e($ky_hop['chu_tri'] ?? '-') ?></div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="text-muted small">Trạng thái</label>
                        <div><?= status_badge($ky_hop['trang_thai'], 'ky_hop') ?></div>
                    </div>
                </div>
                
                <hr>
                
                <h6 class="mb-3">Deadline</h6>
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <small class="text-muted">Hạn đăng ký:</small><br>
                        <strong><?= format_date($ky_hop['deadline_dang_ky']) ?></strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted">Hạn phê duyệt:</small><br>
                        <strong><?= format_date($ky_hop['deadline_phe_duyet']) ?></strong>
                    </div>
                    <div class="col-md-4 mb-2">
                        <small class="text-muted">Hạn hoàn thiện:</small><br>
                        <strong><?= format_date($ky_hop['deadline_hoan_thien']) ?></strong>
                    </div>
                </div>
                
                <?php if ($ky_hop['ghi_chu']): ?>
                    <hr>
                    <label class="text-muted small">Ghi chú</label>
                    <div><?= nl2br(e($ky_hop['ghi_chu'])) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Thống kê</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h1 class="display-4 mb-0"><?= $stats['tong_noi_dung'] ?></h1>
                    <small class="text-muted">Tổng nội dung</small>
                </div>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Chờ duyệt</small>
                        <strong class="text-warning"><?= $stats['cho_duyet'] ?></strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: <?= $stats['tong_noi_dung'] > 0 ? ($stats['cho_duyet'] / $stats['tong_noi_dung'] * 100) : 0 ?>%"></div>
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Đã duyệt</small>
                        <strong class="text-success"><?= $stats['da_duyet'] ?></strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= $stats['tong_noi_dung'] > 0 ? ($stats['da_duyet'] / $stats['tong_noi_dung'] * 100) : 0 ?>%"></div>
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Hoàn thành</small>
                        <strong class="text-primary"><?= $stats['hoan_thanh'] ?></strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" style="width: <?= $stats['tong_noi_dung'] > 0 ? ($stats['hoan_thanh'] / $stats['tong_noi_dung'] * 100) : 0 ?>%"></div>
                    </div>
                </div>
                
                <hr>
                
                <div class="text-center">
                    <small class="text-muted">Tiến độ trung bình</small>
                    <h3 class="mb-0 text-primary"><?= round($stats['tien_do_tb']) ?>%</h3>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Danh sách nội dung -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-list-task"></i> Danh sách nội dung (<?= count($noi_dung_list) ?>)</h5>
    </div>
    <div class="card-body">
        <?php if (empty($noi_dung_list)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox display-1"></i>
                <p class="mt-3">Chưa có nội dung nào được đăng ký</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th width="50">STT</th>
                            <th>Tiêu đề</th>
                            <th>Cơ quan trình</th>
                            <th>Người trình</th>
                            <th>Người duyệt</th>
                            <th>Trạng thái</th>
                            <th>Tiến độ</th>
                            <th width="100"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($noi_dung_list as $nd): ?>
                            <tr>
                                <td><strong><?= $nd['stt'] ?></strong></td>
                                <td><?= e($nd['tieu_de']) ?></td>
                                <td><small><?= e($nd['ten_co_quan'] ?? '-') ?></small></td>
                                <td>
                                    <small>
                                        <?= e($nd['nguoi_trinh']) ?><br>
                                        <span class="text-muted"><?= e($nd['ten_phong'] ?? '-') ?></span>
                                    </small>
                                </td>
                                <td><small><?= e($nd['nguoi_phe_duyet'] ?? '-') ?></small></td>
                                <td><?= status_badge($nd['trang_thai']) ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <?php $color = get_tien_do_color($nd['tien_do']); ?>
                                        <div class="progress-bar bg-<?= $color ?>" style="width: <?= $nd['tien_do'] ?>%">
                                            <?= $nd['tien_do'] ?>%
                                        </div>
                                    </div>
                                </td>
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

<?php
$extra_js = <<<JS
<script>
function deleteKyHop() {
    confirmDelete('Bạn có chắc chắn muốn xóa kỳ họp này?<br><small>Lưu ý: Tất cả nội dung liên quan sẽ bị xóa</small>').then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'xoa.php?id=$id';
        }
    });
}
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
