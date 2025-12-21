<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHANH_VP]);

$page_title = 'Sửa kỳ họp';

$id = $_GET['id'] ?? 0;
$error = '';

// Lấy thông tin kỳ họp
$stmt = $pdo->prepare("SELECT * FROM ky_hop WHERE id = ?");
$stmt->execute([$id]);
$ky_hop = $stmt->fetch();

if (!$ky_hop) {
    redirect(BASE_URL . '/ky-hop/danh-sach.php', 'Không tìm thấy kỳ họp', 'error');
}

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
        $trang_thai = $_POST['trang_thai'] ?? 'du_thao';
        $ghi_chu = sanitize($_POST['ghi_chu'] ?? '');
        
        if (empty($so_ky_hop) || empty($ten_ky_hop) || empty($ngay_hop)) {
            $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
        } else {
            // Kiểm tra số kỳ họp trùng (trừ kỳ họp hiện tại)
            $stmt = $pdo->prepare("SELECT id FROM ky_hop WHERE so_ky_hop = ? AND id != ?");
            $stmt->execute([$so_ky_hop, $id]);
            if ($stmt->fetch()) {
                $error = 'Số kỳ họp đã tồn tại';
            } else {
                try {
                    $stmt = $pdo->prepare("
                        UPDATE ky_hop SET
                            so_ky_hop = ?, ten_ky_hop = ?, ngay_hop = ?, dia_diem = ?, chu_tri = ?,
                            deadline_dang_ky = ?, deadline_phe_duyet = ?, deadline_hoan_thien = ?,
                            trang_thai = ?, ghi_chu = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $so_ky_hop, $ten_ky_hop, $ngay_hop, $dia_diem, $chu_tri,
                        $deadline_dang_ky ?: null,
                        $deadline_phe_duyet ?: null,
                        $deadline_hoan_thien ?: null,
                        $trang_thai,
                        $ghi_chu,
                        $id
                    ]);
                    
                    log_activity('Cập nhật kỳ họp', 'ky_hop', $id, $ten_ky_hop);
                    
                    redirect(BASE_URL . '/ky-hop/chi-tiet.php?id=' . $id, 'Cập nhật kỳ họp thành công', 'success');
                } catch (PDOException $e) {
                    $error = 'Lỗi cập nhật: ' . $e->getMessage();
                }
            }
        }
    }
} else {
    // Load dữ liệu hiện tại vào $_POST để hiển thị
    $_POST = $ky_hop;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/dashboard/">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="danh-sach.php">Kỳ họp</a></li>
                <li class="breadcrumb-item"><a href="chi-tiet.php?id=<?= $id ?>"><?= e($ky_hop['so_ky_hop']) ?></a></li>
                <li class="breadcrumb-item active">Sửa</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-pencil"></i> Sửa kỳ họp</h2>
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
                <form method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Số kỳ họp <span class="text-danger">*</span></label>
                            <input type="text" name="so_ky_hop" class="form-control" required
                                   value="<?= e($_POST['so_ky_hop'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tên kỳ họp <span class="text-danger">*</span></label>
                            <input type="text" name="ten_ky_hop" class="form-control" required
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
                                   value="<?= e($_POST['dia_diem'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Chủ trì</label>
                            <input type="text" name="chu_tri" class="form-control"
                                   value="<?= e($_POST['chu_tri'] ?? '') ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Trạng thái <span class="text-danger">*</span></label>
                            <select name="trang_thai" class="form-select" required>
                                <?php foreach ($GLOBALS['KY_HOP_STATUS'] as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= ($_POST['trang_thai'] ?? '') == $key ? 'selected' : '' ?>>
                                        <?= $val['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="mb-3"><i class="bi bi-calendar-check"></i> Deadline</h6>
                    
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
                        <textarea name="ghi_chu" class="form-control" rows="3"><?= e($_POST['ghi_chu'] ?? '') ?></textarea>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Cập nhật
                        </button>
                        <a href="chi-tiet.php?id=<?= $id ?>" class="btn btn-secondary">
                            <i class="bi bi-x-lg"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
