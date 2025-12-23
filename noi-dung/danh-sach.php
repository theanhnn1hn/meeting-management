<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Danh sách Nội dung';
$current_user = get_auth_user();

// Filter
$filter_ky_hop = $_GET['ky_hop_id'] ?? '';
$filter_trang_thai = $_GET['trang_thai'] ?? '';
$filter_phong = $_GET['phong_ban_id'] ?? '';

// Build query theo quyền
$where_conditions = [];
$params = [];

// Phân quyền xem
if ($_SESSION['chuc_vu'] === ROLE_CHUYEN_VIEN) {
    // Chuyên viên chỉ xem nội dung của mình
    $where_conditions[] = "nd.nguoi_trinh_id = ?";
    $params[] = $_SESSION['user_id'];
} elseif (in_array($_SESSION['chuc_vu'], [ROLE_TRUONG_PHONG, ROLE_PHO_PHONG])) {
    // Trưởng/Phó phòng xem nội dung của phòng
    $where_conditions[] = "nd.phong_ban_id = ?";
    $params[] = $_SESSION['phong_ban_id'];
}

// Filters
if ($filter_ky_hop) {
    $where_conditions[] = "nd.ky_hop_id = ?";
    $params[] = $filter_ky_hop;
}

if ($filter_trang_thai) {
    $where_conditions[] = "nd.trang_thai = ?";
    $params[] = $filter_trang_thai;
}

if ($filter_phong && has_permission('view_all')) {
    $where_conditions[] = "nd.phong_ban_id = ?";
    $params[] = $filter_phong;
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Lấy danh sách nội dung
$stmt = $pdo->prepare("
    SELECT nd.*, 
           kh.ten_ky_hop, kh.ngay_hop, kh.deadline_hoan_thien,
           u.ho_ten as nguoi_trinh,
           pb.ten_phong,
           cq.ten_co_quan,
           DATEDIFF(kh.deadline_hoan_thien, CURDATE()) as days_left
    FROM noi_dung nd
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    LEFT JOIN users u ON nd.nguoi_trinh_id = u.id
    LEFT JOIN phong_ban pb ON nd.phong_ban_id = pb.id
    LEFT JOIN co_quan cq ON nd.co_quan_trinh_id = cq.id
    $where_clause
    ORDER BY nd.created_at DESC
");
$stmt->execute($params);
$noi_dung_list = $stmt->fetchAll();

// Lấy danh sách kỳ họp cho filter
$ky_hop_list = $pdo->query("SELECT id, ten_ky_hop, so_ky_hop FROM ky_hop ORDER BY ngay_hop DESC LIMIT 20")->fetchAll();

// Lấy danh sách phòng ban cho filter (nếu có quyền)
$phong_ban_list = [];
if (has_permission('view_all')) {
    $phong_ban_list = $pdo->query("SELECT id, ten_phong FROM phong_ban WHERE trang_thai = 1 ORDER BY ten_phong")->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> Danh sách Nội dung</h2>
        <p class="text-muted">Quản lý nội dung kỳ họp</p>
    </div>
    <div class="col-md-4 text-end">
        <?php if (has_permission('dang_ky_noi_dung')): ?>
            <a href="dang-ky.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Đăng ký nội dung
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small">Kỳ họp</label>
                <select name="ky_hop_id" class="form-select form-select-sm">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($ky_hop_list as $kh): ?>
                        <option value="<?= $kh['id'] ?>" <?= $filter_ky_hop == $kh['id'] ? 'selected' : '' ?>>
                            <?= e($kh['so_ky_hop']) ?> - <?= e($kh['ten_ky_hop']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label small">Trạng thái</label>
                <select name="trang_thai" class="form-select form-select-sm">
                    <option value="">-- Tất cả --</option>
                    <?php foreach ($GLOBALS['NOI_DUNG_STATUS'] as $key => $val): ?>
                        <option value="<?= $key ?>" <?= $filter_trang_thai == $key ? 'selected' : '' ?>>
                            <?= $val['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if (!empty($phong_ban_list)): ?>
                <div class="col-md-3">
                    <label class="form-label small">Phòng ban</label>
                    <select name="phong_ban_id" class="form-select form-select-sm">
                        <option value="">-- Tất cả --</option>
                        <?php foreach ($phong_ban_list as $pb): ?>
                            <option value="<?= $pb['id'] ?>" <?= $filter_phong == $pb['id'] ? 'selected' : '' ?>>
                                <?= e($pb['ten_phong']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-funnel"></i> Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="noiDungTable" class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Tiêu đề</th>
                        <th>Kỳ họp</th>
                        <th>Cơ quan trình</th>
                        <th>Người trình</th>
                        <th>Trạng thái</th>
                        <th>Tiến độ</th>
                        <th>Hạn hoàn thiện</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($noi_dung_list)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox display-1"></i>
                                <h5 class="mt-3">Không có nội dung nào</h5>
                                <?php if (has_permission('dang_ky_noi_dung')): ?>
                                    <p>Bạn chưa đăng ký nội dung nào. <a href="dang-ky.php">Đăng ký ngay</a></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($noi_dung_list as $nd): ?>
                        <tr>
                            <td>
                                <strong><?= e($nd['tieu_de']) ?></strong>
                                <?php if ($nd['tom_tat']): ?>
                                    <br><small class="text-muted"><?= e(substr($nd['tom_tat'], 0, 100)) ?>...</small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= e($nd['ten_ky_hop']) ?></small></td>
                            <td><small><?= e($nd['ten_co_quan'] ?? '-') ?></small></td>
                            <td>
                                <small>
                                    <?= e($nd['nguoi_trinh']) ?><br>
                                    <span class="text-muted"><?= e($nd['ten_phong'] ?? '-') ?></span>
                                </small>
                            </td>
                            <td><?= status_badge($nd['trang_thai']) ?></td>
                            <td>
                                <div class="progress" style="height: 20px; min-width: 80px;">
                                    <?php $color = get_tien_do_color($nd['tien_do']); ?>
                                    <div class="progress-bar bg-<?= $color ?>" style="width: <?= $nd['tien_do'] ?>%">
                                        <?= $nd['tien_do'] ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $days = $nd['days_left'];
                                $deadline_class = 'text-muted';
                                $deadline_icon = 'bi-calendar';
                                
                                if ($days < 0) {
                                    $deadline_class = 'text-danger fw-bold';
                                    $deadline_icon = 'bi-exclamation-octagon';
                                } elseif ($days <= 1) {
                                    $deadline_class = 'text-danger';
                                    $deadline_icon = 'bi-exclamation-triangle';
                                } elseif ($days <= 3) {
                                    $deadline_class = 'text-warning';
                                    $deadline_icon = 'bi-clock';
                                }
                                ?>
                                <small class="<?= $deadline_class ?>">
                                    <i class="bi <?= $deadline_icon ?>"></i>
                                    <?= format_date($nd['deadline_hoan_thien']) ?>
                                    <?php if ($days < 0): ?>
                                        (Quá <?= abs($days) ?> ngày)
                                    <?php elseif ($days <= 7): ?>
                                        (Còn <?= $days ?> ngày)
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td>
                                <a href="chi-tiet.php?id=<?= $nd['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$extra_js = <<<JS
<script>
$(document).ready(function() {
    $('#noiDungTable').DataTable({
        order: [[0, 'asc']],
        pageLength: 20,
        columnDefs: [
            { orderable: false, targets: [7] }
        ],
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Xuất Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6]
                }
            }
        ]
    });
});
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
