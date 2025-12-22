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

// Tính % hoàn thành checklist
$total_checklist = count($checklist);
$completed_checklist = 0;
foreach ($checklist as $item) {
    if ($item['trang_thai'] == 1) $completed_checklist++;
}
$checklist_percent = $total_checklist > 0 ? round(($completed_checklist / $total_checklist) * 100) : 0;

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

// Phân loại comments
$gop_y = [];
$doc_viec = [];
$yeu_cau_sua = [];

foreach ($comments as $cmt) {
    if ($cmt['loai'] == 'gop_y') $gop_y[] = $cmt;
    elseif ($cmt['loai'] == 'doc_viec') $doc_viec[] = $cmt;
    elseif ($cmt['loai'] == 'yeu_cau_sua') $yeu_cau_sua[] = $cmt;
}

// Check quyền
$is_owner = $noi_dung['nguoi_trinh_id'] == $_SESSION['user_id'];
$can_approve = in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP]) 
               && $noi_dung['nguoi_phe_duyet_id'] == $_SESSION['user_id']
               && $noi_dung['trang_thai'] == 'cho_duyet';
$can_comment = in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP]) 
               || ($noi_dung['phong_ban_id'] == $_SESSION['phong_ban_id'] && in_array($chuc_vu, [ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]))
               || $is_owner;

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard/">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="danh-sach.php">Nội dung</a></li>
                <li class="breadcrumb-item active">Chi tiết</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> <?= e($noi_dung['tieu_de']) ?></h2>
        <p class="text-muted mb-0">
            <i class="bi bi-calendar-event"></i> <?= e($noi_dung['ten_ky_hop']) ?> - 
            <?= format_date($noi_dung['ngay_hop']) ?>
        </p>
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

<?php
// Timeline warning
$days = $noi_dung['days_left'];
if ($days !== null && $noi_dung['tien_do'] < 100):
    $alert_class = 'info';
    $alert_icon = 'info-circle';
    $alert_text = "Còn {$days} ngày đến hạn hoàn thiện";
    
    if ($days < 0) {
        $alert_class = 'danger';
        $alert_icon = 'exclamation-octagon';
        $alert_text = "ĐÃ QUÁ HẠN " . abs($days) . " ngày!";
    } elseif ($days <= 1) {
        $alert_class = 'danger';
        $alert_icon = 'exclamation-triangle';
        $alert_text = "KHẨN CẤP: Còn {$days} ngày đến hạn!";
    } elseif ($days <= 3) {
        $alert_class = 'warning';
        $alert_icon = 'clock';
        $alert_text = "Cảnh báo: Còn {$days} ngày đến hạn";
    }
?>
    <div class="alert alert-<?= $alert_class ?> alert-dismissible fade show">
        <i class="bi bi-<?= $alert_icon ?>"></i> <strong><?= $alert_text ?></strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Thông tin chung -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-info-circle"></i> Thông tin nội dung</h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Cơ quan trình</label>
                        <div><strong><?= e($noi_dung['ten_co_quan'] ?? 'Chưa xác định') ?></strong></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Người trình</label>
                        <div><strong><?= e($noi_dung['nguoi_trinh']) ?></strong></div>
                        <small class="text-muted"><?= e($noi_dung['ten_phong'] ?? 'Chưa gắn phòng') ?></small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="text-muted small">Người phê duyệt</label>
                        <div><?= e($noi_dung['nguoi_phe_duyet'] ?? 'Chưa chỉ định') ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Trạng thái</label>
                        <div><?= status_badge($noi_dung['trang_thai']) ?></div>
                        <?php if ($noi_dung['ngay_phe_duyet']): ?>
                            <small class="text-muted">Ngày duyệt: <?= format_datetime($noi_dung['ngay_phe_duyet']) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($noi_dung['tom_tat']): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Tóm tắt</label>
                        <div><?= nl2br(e($noi_dung['tom_tat'])) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($noi_dung['ghi_chu']): ?>
                    <div class="mb-3">
                        <label class="text-muted small">Ghi chú</label>
                        <div><?= nl2br(e($noi_dung['ghi_chu'])) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($noi_dung['trang_thai'] == 'tu_choi' && $noi_dung['ly_do_tu_choi']): ?>
                    <div class="alert alert-danger mb-0">
                        <strong><i class="bi bi-x-circle"></i> Lý do từ chối:</strong><br>
                        <?= nl2br(e($noi_dung['ly_do_tu_choi'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-graph-up"></i> Tiến độ</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">Deadline hoàn thiện</label>
                    <div><strong><?= format_date($noi_dung['deadline_hoan_thien']) ?></strong></div>
                </div>
                
                <div class="mb-2">
                    <label class="text-muted small">Tiến độ hoàn thành</label>
                </div>
                <div class="progress mb-2" style="height: 30px;">
                    <?php $color = get_tien_do_color($noi_dung['tien_do']); ?>
                    <div class="progress-bar bg-<?= $color ?>" style="width: <?= $noi_dung['tien_do'] ?>%">
                        <strong><?= $noi_dung['tien_do'] ?>%</strong>
                    </div>
                </div>
                
                <?php if ($is_owner && in_array($noi_dung['trang_thai'], ['da_duyet', 'dang_xu_ly'])): ?>
                    <button onclick="showUpdateTienDo()" class="btn btn-sm btn-primary w-100 mt-2">
                        <i class="bi bi-arrow-repeat"></i> Cập nhật tiến độ
                    </button>
                <?php endif; ?>
                
                <hr>
                
                <div class="small">
                    <div class="mb-2">
                        <i class="bi bi-calendar-plus"></i> Đăng ký: 
                        <span class="text-muted"><?= format_datetime($noi_dung['created_at']) ?></span>
                    </div>
                    <?php if ($noi_dung['ngay_phe_duyet']): ?>
                        <div class="mb-2">
                            <i class="bi bi-check-circle"></i> Phê duyệt: 
                            <span class="text-muted"><?= format_datetime($noi_dung['ngay_phe_duyet']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checklist công việc -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-list-check"></i> Checklist công việc 
            <span class="badge bg-primary"><?= $completed_checklist ?>/<?= $total_checklist ?></span>
        </h6>
        <?php if ($is_owner && in_array($noi_dung['trang_thai'], ['da_duyet', 'dang_xu_ly'])): ?>
            <button onclick="showAddChecklist()" class="btn btn-sm btn-success">
                <i class="bi bi-plus-lg"></i> Thêm công việc
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($checklist)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-list-check fs-1"></i>
                <p class="mt-2">Chưa có checklist</p>
            </div>
        <?php else: ?>
            <?php if ($total_checklist > 0): ?>
                <div class="progress mb-3" style="height: 25px;">
                    <div class="progress-bar bg-<?= get_tien_do_color($checklist_percent) ?>" 
                         style="width: <?= $checklist_percent ?>%">
                        <?= $checklist_percent ?>%
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="list-group list-group-flush" id="checklistContainer">
                <?php foreach ($checklist as $item): ?>
                    <div class="list-group-item d-flex align-items-center" data-id="<?= $item['id'] ?>">
                        <?php if ($is_owner): ?>
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" 
                                       <?= $item['trang_thai'] == 1 ? 'checked' : '' ?>
                                       onchange="toggleChecklist(<?= $item['id'] ?>, this.checked)">
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex-grow-1">
                            <span class="<?= $item['trang_thai'] == 1 ? 'text-decoration-line-through text-muted' : '' ?>">
                                <?= e($item['ten_cong_viec']) ?>
                            </span>
                            <?php if ($item['trang_thai'] == 1 && $item['ngay_hoan_thanh']): ?>
                                <br><small class="text-success">
                                    <i class="bi bi-check-circle"></i> Hoàn thành: <?= format_datetime($item['ngay_hoan_thanh']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($is_owner): ?>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="deleteChecklist(<?= $item['id'] ?>)">
                                <i class="bi bi-trash"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tài liệu đính kèm -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="mb-0">
            <i class="bi bi-paperclip"></i> Tài liệu đính kèm 
            <span class="badge bg-info"><?= count($tai_lieu_list) ?></span>
        </h6>
        <?php if ($is_owner): ?>
            <button onclick="showUploadModal()" class="btn btn-sm btn-primary">
                <i class="bi bi-upload"></i> Upload tài liệu
            </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($tai_lieu_list)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-file-earmark fs-1"></i>
                <p class="mt-2">Chưa có tài liệu đính kèm</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($tai_lieu_list as $file): ?>
                    <div class="list-group-item">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="bi bi-file-earmark-<?= $file['loai_file'] ?> fs-2 text-primary"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?= e($file['ten_file']) ?></h6>
                                <small class="text-muted">
                                    <?= formatFileSize($file['kich_thuoc']) ?> - 
                                    Upload bởi <?= e($file['uploaded_by_name']) ?> - 
                                    <?= time_ago($file['created_at']) ?>
                                </small>
                                <?php if ($file['mo_ta']): ?>
                                    <div><small><?= e($file['mo_ta']) ?></small></div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <a href="<?= UPLOAD_URL . $file['duong_dan'] ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   download="<?= e($file['ten_file']) ?>">
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Comments & Góp ý -->
<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-info bg-opacity-10">
                <h6 class="mb-0">
                    <i class="bi bi-chat-dots"></i> Góp ý 
                    <span class="badge bg-info"><?= count($gop_y) ?></span>
                </h6>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($gop_y)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-chat"></i>
                        <p class="mt-2 small">Chưa có góp ý</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($gop_y as $cmt): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between mb-2">
                                <strong class="small"><?= e($cmt['ho_ten']) ?></strong>
                                <small class="text-muted"><?= time_ago($cmt['created_at']) ?></small>
                            </div>
                            <div><?= nl2br(e($cmt['noi_dung_comment'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($can_comment): ?>
                <div class="card-footer">
                    <button onclick="showCommentModal('gop_y')" class="btn btn-sm btn-info w-100">
                        <i class="bi bi-chat-dots"></i> Góp ý
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-warning bg-opacity-10">
                <h6 class="mb-0">
                    <i class="bi bi-exclamation-triangle"></i> Đốc việc 
                    <span class="badge bg-warning"><?= count($doc_viec) ?></span>
                </h6>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($doc_viec)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bell"></i>
                        <p class="mt-2 small">Chưa bị đốc việc</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($doc_viec as $cmt): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between mb-2">
                                <strong class="small text-danger"><?= e($cmt['ho_ten']) ?></strong>
                                <small class="text-muted"><?= time_ago($cmt['created_at']) ?></small>
                            </div>
                            <div><?= nl2br(e($cmt['noi_dung_comment'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG])): ?>
                <div class="card-footer">
                    <button onclick="showCommentModal('doc_viec')" class="btn btn-sm btn-warning w-100">
                        <i class="bi bi-bell"></i> Đốc việc
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header bg-danger bg-opacity-10">
                <h6 class="mb-0">
                    <i class="bi bi-pencil-square"></i> Yêu cầu sửa 
                    <span class="badge bg-danger"><?= count($yeu_cau_sua) ?></span>
                </h6>
            </div>
            <div class="card-body p-0" style="max-height: 400px; overflow-y: auto;">
                <?php if (empty($yeu_cau_sua)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle"></i>
                        <p class="mt-2 small">Không có yêu cầu sửa</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($yeu_cau_sua as $cmt): ?>
                        <div class="p-3 border-bottom">
                            <div class="d-flex justify-content-between mb-2">
                                <strong class="small"><?= e($cmt['ho_ten']) ?></strong>
                                <small class="text-muted"><?= time_ago($cmt['created_at']) ?></small>
                            </div>
                            <div><?= nl2br(e($cmt['noi_dung_comment'])) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP])): ?>
                <div class="card-footer">
                    <button onclick="showCommentModal('yeu_cau_sua')" class="btn btn-sm btn-danger w-100">
                        <i class="bi bi-pencil-square"></i> Yêu cầu sửa
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload tài liệu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="noi_dung_id" value="<?= $noi_dung_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Chọn file <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file" required>
                        <small class="text-muted">
                            Cho phép: <?= implode(', ', ALLOWED_EXTENSIONS) ?> (Tối đa <?= MAX_FILE_SIZE / 1024 / 1024 ?>MB)
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" name="mo_ta" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitUpload()">
                    <i class="bi bi-upload"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Comment -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commentModalTitle">Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="commentForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="noi_dung_id" value="<?= $noi_dung_id ?>">
                    <input type="hidden" name="loai" id="commentType">
                    
                    <div class="mb-3">
                        <label class="form-label">Nội dung <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="comment" rows="4" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitComment()">Gửi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cập nhật tiến độ -->
<div class="modal fade" id="tienDoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cập nhật tiến độ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="tienDoForm">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Tiến độ hoàn thành (%)</label>
                        <input type="range" class="form-range" name="tien_do" min="0" max="100" 
                               value="<?= $noi_dung['tien_do'] ?>" id="tienDoRange" step="5">
                        <div class="text-center">
                            <h2 id="tienDoValue"><?= $noi_dung['tien_do'] ?>%</h2>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitTienDo()">
                    <i class="bi bi-save"></i> Cập nhật
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Checklist -->
<div class="modal fade" id="addChecklistModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Thêm công việc</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addChecklistForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="noi_dung_id" value="<?= $noi_dung_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Tên công việc <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ten_cong_viec" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-primary" onclick="submitAddChecklist()">Thêm</button>
            </div>
        </div>
    </div>
</div>

<a href="danh-sach.php" class="btn btn-secondary mb-4">
    <i class="bi bi-arrow-left"></i> Quay lại danh sách
</a>

<?php
// Helper function
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

$extra_js = <<<JS
<script>
// Modals
const uploadModal = new bootstrap.Modal('#uploadModal');
const commentModal = new bootstrap.Modal('#commentModal');
const tienDoModal = new bootstrap.Modal('#tienDoModal');
const addChecklistModal = new bootstrap.Modal('#addChecklistModal');

// Approve/Reject
function approveNoiDung() {
    Swal.fire({
        title: 'Phê duyệt nội dung',
        text: 'Bạn xác nhận phê duyệt nội dung này?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Phê duyệt',
        confirmButtonColor: '#28a745'
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'phe-duyet.php';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="noi_dung_id" value="<?= $noi_dung_id ?>">
                <input type="hidden" name="action" value="approve">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

function rejectNoiDung() {
    Swal.fire({
        title: 'Từ chối nội dung',
        input: 'textarea',
        inputLabel: 'Lý do từ chối',
        inputPlaceholder: 'Nhập lý do tại đây...',
        showCancelButton: true,
        confirmButtonText: 'Từ chối',
        confirmButtonColor: '#dc3545',
        inputValidator: (value) => {
            if (!value) return 'Bạn phải nhập lý do từ chối!';
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'phe-duyet.php';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="noi_dung_id" value="<?= $noi_dung_id ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="ly_do" value="${result.value}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Tiến độ
function showUpdateTienDo() {
    tienDoModal.show();
}

document.getElementById('tienDoRange')?.addEventListener('input', function() {
    document.getElementById('tienDoValue').textContent = this.value + '%';
});

function submitTienDo() {
    const formData = new FormData(document.getElementById('tienDoForm'));
    
    fetch('cap-nhat-tien-do.php?id=$noi_dung_id', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        tienDoModal.hide();
        toastr.success('Cập nhật tiến độ thành công');
        setTimeout(() => location.reload(), 1000);
    })
    .catch(error => {
        toastr.error('Lỗi cập nhật tiến độ');
    });
}

// Upload
function showUploadModal() {
    uploadModal.show();
}

function submitUpload() {
    const formData = new FormData(document.getElementById('uploadForm'));
    
    fetch('upload-tai-lieu.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            uploadModal.hide();
            toastr.success(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Lỗi upload file');
    });
}

// Comment
function showCommentModal(type) {
    document.getElementById('commentType').value = type;
    
    const titles = {
        'gop_y': 'Góp ý',
        'doc_viec': 'Đốc việc',
        'yeu_cau_sua': 'Yêu cầu sửa'
    };
    
    document.getElementById('commentModalTitle').textContent = titles[type];
    commentModal.show();
}

function submitComment() {
    const formData = new FormData(document.getElementById('commentForm'));
    
    fetch('comment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            commentModal.hide();
            toastr.success(data.message);
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(data.message);
        }
    })
    .catch(error => {
        toastr.error('Lỗi gửi comment');
    });
}

// Checklist
function showAddChecklist() {
    addChecklistModal.show();
}

function submitAddChecklist() {
    const formData = new FormData(document.getElementById('addChecklistForm'));
    
    // Call API to add checklist
    fetch('/api/checklist.php?action=add', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            addChecklistModal.hide();
            toastr.success('Thêm công việc thành công');
            setTimeout(() => location.reload(), 1000);
        } else {
            toastr.error(data.message);
        }
    });
}

function toggleChecklist(id, checked) {
    fetch('/api/checklist.php?action=toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'id=' + id + '&checked=' + (checked ? '1' : '0') + '&csrf_token=' + document.querySelector('input[name="csrf_token"]').value
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            toastr.success('Cập nhật checklist thành công');
            setTimeout(() => location.reload(), 500);
        } else {
            toastr.error(data.message);
            location.reload();
        }
    });
}

function deleteChecklist(id) {
    confirmDelete('Bạn có chắc muốn xóa công việc này?').then((result) => {
        if (result.isConfirmed) {
            fetch('/api/checklist.php?action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + id + '&csrf_token=' + document.querySelector('input[name="csrf_token"]').value
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Xóa công việc thành công');
                    setTimeout(() => location.reload(), 500);
                } else {
                    toastr.error(data.message);
                }
            });
        }
    });
}
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>