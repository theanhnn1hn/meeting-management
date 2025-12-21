<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Chi tiết nội dung';
$noi_dung_id = $_GET['id'] ?? 0;

// Lấy thông tin nội dung
$stmt = $pdo->prepare("
    SELECT nd.*, 
           kh.ten_ky_hop, kh.ngay_hop, kh.deadline_hoan_thien,
           u1.ho_ten as nguoi_trinh,
           u2.ho_ten as nguoi_phe_duyet,
           pb.ten_phong,
           cq.ten_co_quan,
           DATEDIFF(kh.deadline_hoan_thien, CURDATE()) as days_left
    FROM noi_dung nd
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    JOIN users u1 ON nd.nguoi_trinh_id = u1.id
    LEFT JOIN users u2 ON nd.nguoi_phe_duyet_id = u2.id
    LEFT JOIN phong_ban pb ON nd.phong_ban_id = pb.id
    LEFT JOIN co_quan cq ON nd.co_quan_trinh_id = cq.id
    WHERE nd.id = ?
");
$stmt->execute([$noi_dung_id]);
$noi_dung = $stmt->fetch();

if (!$noi_dung) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Nội dung không tồn tại', 'error');
}

// Kiểm tra quyền xem
$chuc_vu = $_SESSION['chuc_vu'];
$can_view = false;

if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP])) {
    $can_view = true;
} elseif ($noi_dung['nguoi_trinh_id'] == $_SESSION['user_id']) {
    $can_view = true;
} elseif (in_array($chuc_vu, [ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]) 
         && $noi_dung['phong_ban_id'] == $_SESSION['phong_ban_id']) {
    $can_view = true;
}

if (!$can_view) {
    redirect(BASE_URL . '/noi-dung/danh-sach.php', 'Bạn không có quyền xem nội dung này', 'error');
}

// Lấy checklist
$stmt = $pdo->prepare("SELECT * FROM checklist WHERE noi_dung_id = ? ORDER BY thu_tu, id");
$stmt->execute([$noi_dung_id]);
$checklist = $stmt->fetchAll();

// Lấy tài liệu
$stmt = $pdo->prepare("
    SELECT tl.*, u.ho_ten as uploaded_by_name
    FROM tai_lieu tl
    LEFT JOIN users u ON tl.uploaded_by = u.id
    WHERE tl.noi_dung_id = ?
    ORDER BY tl.created_at DESC
");
$stmt->execute([$noi_dung_id]);
$tai_lieu_list = $stmt->fetchAll();

// Lấy comments
$stmt = $pdo->prepare("
    SELECT c.*, u.ho_ten, u.chuc_vu
    FROM comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.noi_dung_id = ?
    ORDER BY c.created_at DESC
");
$stmt->execute([$noi_dung_id]);
$comments = $stmt->fetchAll();

// Check quyền
$is_owner = $noi_dung['nguoi_trinh_id'] == $_SESSION['user_id'];
$can_approve = in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP]) 
               && $noi_dung['nguoi_phe_duyet_id'] == $_SESSION['user_id']
               && $noi_dung['trang_thai'] == 'cho_duyet';

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2><i class="bi bi-file-earmark-text"></i> Chi tiết nội dung</h2>
        <h5 class="text-muted"><?= e($noi_dung['tieu_de']) ?></h5>
    </div>
    <div class="col-md-4 text-end">
        <?php if ($is_owner && $noi_dung['trang_thai'] == 'cho_duyet'): ?>
            <a href="sua.php?id=<?= $noi_dung_id ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Sửa
            </a>
        <?php endif; ?>
        
        <?php if ($can_approve): ?>
            <button onclick="approveNoiDung()" class="btn btn-success">
                <i class="bi bi-check-circle"></i> Phê duyệt
            </button>
            <button onclick="rejectNoiDung()" class="btn btn-danger">
                <i class="bi bi-x-circle"></i> Từ chối
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Thông tin chung -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Thông tin nội dung</h6></div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Kỳ họp:</strong><br>
                        <?= e($noi_dung['ten_ky_hop']) ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Ngày họp:</strong><br>
                        <?= format_date($noi_dung['ngay_hop']) ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Cơ quan trình:</strong><br>
                        <?= e($noi_dung['ten_co_quan'] ?? 'Chưa rõ') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Người trình:</strong><br>
                        <?= e($noi_dung['nguoi_trinh']) ?> (<?= e($noi_dung['ten_phong'] ?? 'Chưa gắn phòng') ?>)
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Người phê duyệt:</strong><br>
                        <?= e($noi_dung['nguoi_phe_duyet'] ?? 'Chưa chỉ định') ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Trạng thái:</strong><br>
                        <?= status_badge($noi_dung['trang_thai']) ?>
                    </div>
                </div>
                
                <?php if ($noi_dung['tom_tat']): ?>
                    <div class="mb-3">
                        <strong>Tóm tắt:</strong><br>
                        <?= nl2br(e($noi_dung['tom_tat'])) ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($noi_dung['trang_thai'] == 'tu_choi' && $noi_dung['ly_do_tu_choi']): ?>
                    <div class="alert alert-danger">
                        <strong>Lý do từ chối:</strong><br>
                        <?= nl2br(e($noi_dung['ly_do_tu_choi'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Tiến độ</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Deadline:</strong><br>
                    <?= format_date($noi_dung['deadline_hoan_thien']) ?>
                    <?php if ($noi_dung['days_left'] !== null): ?>
                        <br>
                        <?php if ($noi_dung['days_left'] < 0): ?>
                            <span class="badge bg-danger">Quá <?= abs($noi_dung['days_left']) ?> ngày</span>
                        <?php elseif ($noi_dung['days_left'] <= 3): ?>
                            <span class="badge bg-warning">Còn <?= $noi_dung['days_left'] ?> ngày</span>
                        <?php else: ?>
                            <span class="badge bg-info">Còn <?= $noi_dung['days_left'] ?> ngày</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="mb-2">
                    <strong>Tiến độ hoàn thành:</strong>
                </div>
                <div class="progress mb-2" style="height: 30px;">
                    <div class="progress-bar bg-<?= get_tien_do_color($noi_dung['tien_do']) ?>" 
                         style="width: <?= $noi_dung['tien_do'] ?>%">
                        <?= $noi_dung['tien_do'] ?>%
                    </div>
                </div>
                
                <?php if ($is_owner && in_array($noi_dung['trang_thai'], ['da_duyet', 'dang_xu_ly'])): ?>
                    <button onclick="updateTienDo()" class="btn btn-sm btn-primary w-100">
                        <i class="bi bi-arrow-repeat"></i> Cập nhật tiến độ
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Checklist, Tài liệu, Comments ... -->
<!-- (Code tiếp tục cho các phần này) -->

<a href="danh-sach.php" class="btn btn-secondary">
    <i class="bi bi-arrow-left"></i> Quay lại danh sách
</a>

<?php
$extra_js = <<<JS
<script>
function approveNoiDung() {
    Swal.fire({
        title: 'Phê duyệt nội dung',
        text: 'Bạn xác nhận phê duyệt nội dung này?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Phê duyệt',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'phe-duyet.php?id=$noi_dung_id&action=approve';
        }
    });
}

function rejectNoiDung() {
    Swal.fire({
        title: 'Từ chối nội dung',
        input: 'textarea',
        inputLabel: 'Lý do từ chối',
        inputPlaceholder: 'Nhập lý do từ chối...',
        showCancelButton: true,
        confirmButtonText: 'Từ chối',
        cancelButtonText: 'Hủy',
        inputValidator: (value) => {
            if (!value) {
                return 'Vui lòng nhập lý do từ chối';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'phe-duyet.php?id=$noi_dung_id&action=reject&reason=' + encodeURIComponent(result.value);
        }
    });
}

function updateTienDo() {
    window.location.href = 'cap-nhat-tien-do.php?id=$noi_dung_id';
}
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
