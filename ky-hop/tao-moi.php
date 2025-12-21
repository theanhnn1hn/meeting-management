<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHANH_VP]);

$page_title = 'Tạo kỳ họp mới';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $so_ky_hop = sanitize($_POST['so_ky_hop'] ?? '');
        $ten_ky_hop = sanitize($_POST['ten_ky_hop'] ?? '');
        $ngay_hop = $_POST['ngay_hop'] ?? '';
        $dia_diem = sanitize($_POST['dia_diem'] ?? '');
        $chu_tri = sanitize($_POST['chu_tri'] ?? '');
        $deadline_dang_ky = $_POST['deadline_dang_ky'] ?? '';
        $deadline_phe_duyet = $_POST['deadline_phe_duyet'] ?? '';
        $deadline_hoan_thien = $_POST['deadline_hoan_thien'] ?? '';
        $ghi_chu = sanitize($_POST['ghi_chu'] ?? '');
        
        // Validation
        if (empty($so_ky_hop) || empty($ten_ky_hop) || empty($ngay_hop)) {
            $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
        } else {
            // Kiểm tra số kỳ họp trùng
            $stmt = $pdo->prepare("SELECT id FROM ky_hop WHERE so_ky_hop = ?");
            $stmt->execute([$so_ky_hop]);
            if ($stmt->fetch()) {
                $error = 'Số kỳ họp đã tồn tại';
            } else {
                // Validate deadline logic
                if (!empty($deadline_dang_ky) && !empty($deadline_phe_duyet) && !empty($deadline_hoan_thien)) {
                    if ($deadline_dang_ky >= $deadline_phe_duyet || $deadline_phe_duyet >= $deadline_hoan_thien || $deadline_hoan_thien > $ngay_hop) {
                        $error = 'Deadline không hợp lệ. Phải tuân thủ: Đăng ký < Phê duyệt < Hoàn thiện <= Ngày họp';
                    }
                }
                
                if (empty($error)) {
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO ky_hop (
                                so_ky_hop, ten_ky_hop, ngay_hop, dia_diem, chu_tri,
                                deadline_dang_ky, deadline_phe_duyet, deadline_hoan_thien,
                                ghi_chu, created_by, trang_thai
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'du_thao')
                        ");
                        $stmt->execute([
                            $so_ky_hop, $ten_ky_hop, $ngay_hop, $dia_diem, $chu_tri,
                            $deadline_dang_ky ?: null,
                            $deadline_phe_duyet ?: null,
                            $deadline_hoan_thien ?: null,
                            $ghi_chu,
                            $_SESSION['user_id']
                        ]);
                        
                        $ky_hop_id = $pdo->lastInsertId();
                        
                        log_activity('Tạo kỳ họp mới', 'ky_hop', $ky_hop_id, $ten_ky_hop);
                        
                        // Thông báo cho tất cả chuyên viên
                        $stmt = $pdo->query("SELECT id FROM users WHERE chuc_vu = 'chuyen_vien' AND trang_thai = 1");
                        $chuyen_viens = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (!empty($chuyen_viens)) {
                            notify_users(
                                $chuyen_viens,
                                "Kỳ họp mới: $ten_ky_hop",
                                "Đã tạo kỳ họp mới. Hạn đăng ký nội dung: " . format_date($deadline_dang_ky),
                                'cap_nhat',
                                BASE_URL . '/ky-hop/chi-tiet.php?id=' . $ky_hop_id
                            );
                        }
                        
                        redirect(BASE_URL . '/ky-hop/chi-tiet.php?id=' . $ky_hop_id, 'Tạo kỳ họp thành công', 'success');
                    } catch (PDOException $e) {
                        $error = 'Lỗi tạo kỳ họp: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard/">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="danh-sach.php">Kỳ họp</a></li>
                <li class="breadcrumb-item active">Tạo mới</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-plus-circle"></i> Tạo kỳ họp mới</h2>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Thông tin kỳ họp</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="formTaoKyHop">
                    <?= csrf_field() ?>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Số kỳ họp <span class="text-danger">*</span></label>
                            <input type="text" name="so_ky_hop" class="form-control" 
                                   placeholder="VD: 01/2025" required
                                   value="<?= e($_POST['so_ky_hop'] ?? '') ?>">
                            <small class="text-muted">Định dạng: XX/YYYY</small>
                        </div>
                        
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tên kỳ họp <span class="text-danger">*</span></label>
                            <input type="text" name="ten_ky_hop" class="form-control" 
                                   placeholder="VD: Kỳ họp thường kỳ tháng 01/2025" required
                                   value="<?= e($_POST['ten_ky_hop'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ngày họp <span class="text-danger">*</span></label>
                            <input type="date" name="ngay_hop" class="form-control" required
                                   value="<?= e($_POST['ngay_hop'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Địa điểm</label>
                            <input type="text" name="dia_diem" class="form-control" 
                                   placeholder="VD: Hội trường UBND Tỉnh"
                                   value="<?= e($_POST['dia_diem'] ?? 'Hội trường UBND Tỉnh') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Chủ trì</label>
                        <input type="text" name="chu_tri" class="form-control" 
                               placeholder="VD: Chủ tịch UBND Tỉnh"
                               value="<?= e($_POST['chu_tri'] ?? 'Chủ tịch UBND Tỉnh') ?>">
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3"><i class="bi bi-calendar-check"></i> Deadline</h6>
                    <div class="alert alert-info">
                        <small><i class="bi bi-info-circle"></i> Deadline phải tuân thủ: Đăng ký &lt; Phê duyệt &lt; Hoàn thiện &le; Ngày họp</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hạn đăng ký nội dung</label>
                            <input type="date" name="deadline_dang_ky" class="form-control"
                                   value="<?= e($_POST['deadline_dang_ky'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hạn phê duyệt</label>
                            <input type="date" name="deadline_phe_duyet" class="form-control"
                                   value="<?= e($_POST['deadline_phe_duyet'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Hạn hoàn thiện hồ sơ</label>
                            <input type="date" name="deadline_hoan_thien" class="form-control"
                                   value="<?= e($_POST['deadline_hoan_thien'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Ghi chú</label>
                        <textarea name="ghi_chu" class="form-control" rows="3" 
                                  placeholder="Ghi chú thêm về kỳ họp..."><?= e($_POST['ghi_chu'] ?? '') ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Tạo kỳ họp
                        </button>
                        <a href="danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card bg-light">
            <div class="card-body">
                <h6 class="mb-3"><i class="bi bi-lightbulb"></i> Hướng dẫn</h6>
                <ul class="small">
                    <li class="mb-2">Số kỳ họp phải là duy nhất, định dạng: XX/YYYY</li>
                    <li class="mb-2">Tên kỳ họp nên rõ ràng, dễ hiểu</li>
                    <li class="mb-2">Deadline đăng ký: Thời hạn chuyên viên đăng ký nội dung</li>
                    <li class="mb-2">Deadline phê duyệt: Thời hạn lãnh đạo phê duyệt</li>
                    <li class="mb-2">Deadline hoàn thiện: Thời hạn hoàn thiện hồ sơ trước khi họp</li>
                    <li class="mb-2">Sau khi tạo, trạng thái mặc định là "Dự thảo"</li>
                    <li>Hệ thống sẽ tự động thông báo cho tất cả chuyên viên</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = <<<JS
<script>
flatpickr('input[type="date"]', {
    dateFormat: 'Y-m-d',
    locale: 'vn',
    minDate: 'today'
});
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
