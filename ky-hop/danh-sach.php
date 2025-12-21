<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHANH_VP, ROLE_PHO_CVP]);

$page_title = 'Danh sách Kỳ họp';

// Lấy danh sách kỳ họp
$stmt = $pdo->query("
    SELECT kh.*, u.ho_ten as nguoi_tao,
           (SELECT COUNT(*) FROM noi_dung WHERE ky_hop_id = kh.id) as so_noi_dung
    FROM ky_hop kh
    LEFT JOIN users u ON kh.created_by = u.id
    ORDER BY kh.ngay_hop DESC
");
$ky_hop_list = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-0"><i class="bi bi-calendar-event"></i> Danh sách Kỳ họp</h2>
        <p class="text-muted">Quản lý các kỳ họp UBND Tỉnh</p>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($_SESSION['chuc_vu'] === ROLE_CHANH_VP): ?>
            <a href="tao-moi.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Tạo kỳ họp mới
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="kyHopTable" class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Số kỳ họp</th>
                        <th>Tên kỳ họp</th>
                        <th>Ngày họp</th>
                        <th>Số nội dung</th>
                        <th>Trạng thái</th>
                        <th>Deadline</th>
                        <th>Người tạo</th>
                        <th width="120"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ky_hop_list as $kh): ?>
                        <tr>
                            <td><strong><?= e($kh['so_ky_hop']) ?></strong></td>
                            <td><?= e($kh['ten_ky_hop']) ?></td>
                            <td><?= format_date($kh['ngay_hop']) ?></td>
                            <td>
                                <span class="badge bg-info"><?= $kh['so_noi_dung'] ?> nội dung</span>
                            </td>
                            <td><?= status_badge($kh['trang_thai'], 'ky_hop') ?></td>
                            <td>
                                <small class="text-muted">
                                    Đăng ký: <?= format_date($kh['deadline_dang_ky']) ?><br>
                                    Phê duyệt: <?= format_date($kh['deadline_phe_duyet']) ?><br>
                                    Hoàn thiện: <?= format_date($kh['deadline_hoan_thien']) ?>
                                </small>
                            </td>
                            <td><small><?= e($kh['nguoi_tao'] ?? '-') ?></small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="chi-tiet.php?id=<?= $kh['id'] ?>" 
                                       class="btn btn-outline-primary" title="Chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($_SESSION['chuc_vu'] === ROLE_CHANH_VP): ?>
                                        <a href="sua.php?id=<?= $kh['id'] ?>" 
                                           class="btn btn-outline-warning" title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button onclick="deleteKyHop(<?= $kh['id'] ?>, '<?= e($kh['ten_ky_hop']) ?>')" 
                                                class="btn btn-outline-danger" title="Xóa">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
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
    $('#kyHopTable').DataTable({
        order: [[2, 'desc']], // Sort by ngày họp
        pageLength: 20,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="bi bi-file-earmark-excel"></i> Xuất Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4]
                }
            }
        ]
    });
});

function deleteKyHop(id, ten) {
    confirmDelete('Bạn có chắc chắn muốn xóa kỳ họp "' + ten + '"?').then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'xoa.php?id=' + id;
        }
    });
}
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
