<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_permission('manage_users');

$page_title = 'Tạo template checklist';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $ten_template = sanitize($_POST['ten_template'] ?? '');
        $mo_ta = sanitize($_POST['mo_ta'] ?? '');
        $cong_viec = $_POST['cong_viec'] ?? [];
        
        if (empty($ten_template) || empty($cong_viec)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            $danh_sach_cong_viec = array_filter(array_map('trim', $cong_viec));
            
            if (empty($danh_sach_cong_viec)) {
                $error = 'Vui lòng thêm ít nhất 1 công việc';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO template_checklist (ten_template, mo_ta, danh_sach_cong_viec) VALUES (?, ?, ?)");
                    $stmt->execute([$ten_template, $mo_ta, json_encode($danh_sach_cong_viec)]);
                    log_activity('Tạo template checklist', 'template_checklist', $pdo->lastInsertId());
                    redirect(BASE_URL . '/quan-ly/template-checklist/danh-sach.php', 'Tạo template thành công', 'success');
                } catch (PDOException $e) {
                    $error = 'Lỗi tạo template: ' . $e->getMessage();
                }
            }
        }
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <h2 class="mb-4"><i class="bi bi-list-check"></i> Tạo template checklist</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-body">
                <form method="POST" id="templateForm">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Tên template <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ten_template" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mô tả</label>
                        <textarea class="form-control" name="mo_ta" rows="2"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Danh sách công việc <span class="text-danger">*</span></label>
                        <div id="congViecList">
                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="cong_viec[]" placeholder="Nhập tên công việc" required>
                                <button type="button" class="btn btn-danger" onclick="removeCongViec(this)"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-success" onclick="addCongViec()">
                            <i class="bi bi-plus-lg"></i> Thêm công việc
                        </button>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/quan-ly/template-checklist/danh-sach.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Hủy
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Tạo template
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$extra_js = <<<JS
<script>
function addCongViec() {
    const html = `
        <div class="input-group mb-2">
            <input type="text" class="form-control" name="cong_viec[]" placeholder="Nhập tên công việc" required>
            <button type="button" class="btn btn-danger" onclick="removeCongViec(this)"><i class="bi bi-trash"></i></button>
        </div>
    `;
    $('#congViecList').append(html);
}

function removeCongViec(btn) {
    if ($('#congViecList .input-group').length > 1) {
        $(btn).closest('.input-group').remove();
    } else {
        toastr.warning('Phải có ít nhất 1 công việc');
    }
}
</script>
JS;

include __DIR__ . '/../../includes/footer.php';
?>
