<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHUYEN_VIEN]);

$page_title = 'Đăng ký nội dung kỳ họp';
$error = '';

// Lấy danh sách kỳ họp đang mở đăng ký
$stmt = $pdo->query("
    SELECT * FROM ky_hop 
    WHERE trang_thai IN ('du_thao', 'dang_xu_ly', 'sap_dien_ra')
    AND (deadline_dang_ky IS NULL OR deadline_dang_ky >= CURDATE())
    ORDER BY ngay_hop ASC
");
$ky_hop_list = $stmt->fetchAll();

// Lấy danh sách cơ quan
$co_quan_list = $pdo->query("SELECT * FROM co_quan WHERE trang_thai = 1 ORDER BY thu_tu, ten_co_quan")->fetchAll();

// Lấy danh sách người có thể phê duyệt (Chánh VP + Phó CVP)
$nguoi_phe_duyet_list = $pdo->query("
    SELECT id, ho_ten, chuc_vu 
    FROM users 
    WHERE chuc_vu IN ('chanh_vp', 'pho_cvp') 
    AND trang_thai = 1 
    ORDER BY 
        CASE chuc_vu 
            WHEN 'chanh_vp' THEN 1
            WHEN 'pho_cvp' THEN 2
        END,
        ho_ten
")->fetchAll();

// Lấy template checklist
$template_list = $pdo->query("SELECT * FROM template_checklist WHERE trang_thai = 1 ORDER BY ten_template")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $ky_hop_id = $_POST['ky_hop_id'] ?? 0;
        $tieu_de = sanitize($_POST['tieu_de'] ?? '');
        $tom_tat = sanitize($_POST['tom_tat'] ?? '');
        $co_quan_trinh_id = $_POST['co_quan_trinh_id'] ?? null;
        $nguoi_phe_duyet_id = $_POST['nguoi_phe_duyet_id'] ?? null;
        $template_id = $_POST['template_id'] ?? null;
        
        // Validation
        if (empty($ky_hop_id) || empty($tieu_de) || empty($nguoi_phe_duyet_id)) {
            $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
        } else {
            try {
                $pdo->beginTransaction();
                
                // Insert nội dung
                $stmt = $pdo->prepare("
                    INSERT INTO noi_dung (
                        ky_hop_id, tieu_de, tom_tat, co_quan_trinh_id,
                        nguoi_trinh_id, nguoi_phe_duyet_id, phong_ban_id,
                        trang_thai, tien_do
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'cho_duyet', 0)
                ");
                $stmt->execute([
                    $ky_hop_id,
                    $tieu_de,
                    $tom_tat,
                    $co_quan_trinh_id ?: null,
                    $_SESSION['user_id'],
                    $nguoi_phe_duyet_id,
                    $_SESSION['phong_ban_id']
                ]);
                
                $noi_dung_id = $pdo->lastInsertId();
                
                // Tạo checklist từ template
                if ($template_id) {
                    $stmt = $pdo->prepare("SELECT danh_sach_cong_viec FROM template_checklist WHERE id = ?");
                    $stmt->execute([$template_id]);
                    $template = $stmt->fetch();
                    
                    if ($template) {
                        $cong_viec_list = json_decode($template['danh_sach_cong_viec'], true);
                        if (!empty($cong_viec_list)) {
                            $stmt = $pdo->prepare("
                                INSERT INTO checklist (noi_dung_id, ten_cong_viec, thu_tu) 
                                VALUES (?, ?, ?)
                            ");
                            foreach ($cong_viec_list as $index => $cv) {
                                $stmt->execute([$noi_dung_id, $cv, $index + 1]);
                            }
                        }
                    }
                }
                
                // Tạo thông báo cho người phê duyệt
                create_notification(
                    $nguoi_phe_duyet_id,
                    "Nội dung mới chờ phê duyệt",
                    "{$current_user['ho_ten']} vừa đăng ký nội dung: \"{$tieu_de}\"",
                    'dang_ky',
                    BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id,
                    $noi_dung_id
                );
                
                // Thông báo cho Trưởng/Phó phòng
                if ($_SESSION['phong_ban_id']) {
                    $stmt = $pdo->prepare("
                        SELECT id FROM users 
                        WHERE phong_ban_id = ? 
                        AND chuc_vu IN ('truong_phong', 'pho_phong')
                        AND id != ?
                        AND trang_thai = 1
                    ");
                    $stmt->execute([$_SESSION['phong_ban_id'], $_SESSION['user_id']]);
                    $lanh_dao_phong = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (!empty($lanh_dao_phong)) {
                        notify_users(
                            $lanh_dao_phong,
                            "Nội dung mới từ phòng",
                            "{$current_user['ho_ten']} vừa đăng ký nội dung: \"{$tieu_de}\"",
                            'cap_nhat',
                            BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id,
                            $noi_dung_id
                        );
                    }
                }
                
                log_activity('Đăng ký nội dung kỳ họp', 'noi_dung', $noi_dung_id, $tieu_de);
                
                $pdo->commit();
                
                redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Đăng ký nội dung thành công', 'success');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Lỗi đăng ký: ' . $e->getMessage();
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
                <li class="breadcrumb-item"><a href="danh-sach.php">Nội dung</a></li>
                <li class="breadcrumb-item active">Đăng ký</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-plus-circle"></i> Đăng ký nội dung kỳ họp</h2>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-triangle"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($ky_hop_list)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> 
        Hiện tại không có kỳ họp nào đang mở đăng ký. Vui lòng liên hệ Chánh Văn phòng.
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Thông tin nội dung</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Kỳ họp <span class="text-danger">*</span></label>
                            <select name="ky_hop_id" class="form-select" required id="kyHopSelect">
                                <option value="">-- Chọn kỳ họp --</option>
                                <?php foreach ($ky_hop_list as $kh): ?>
                                    <option value="<?= $kh['id'] ?>" 
                                            data-deadline="<?= e($kh['deadline_dang_ky']) ?>"
                                            <?= ($_POST['ky_hop_id'] ?? '') == $kh['id'] ? 'selected' : '' ?>>
                                        <?= e($kh['so_ky_hop']) ?> - <?= e($kh['ten_ky_hop']) ?>
                                        (Ngày họp: <?= format_date($kh['ngay_hop']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="deadlineInfo" class="form-text"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tiêu đề nội dung <span class="text-danger">*</span></label>
                            <input type="text" name="tieu_de" class="form-control" 
                                   placeholder="VD: Báo cáo tình hình kinh tế - xã hội quý I/2025"
                                   required value="<?= e($_POST['tieu_de'] ?? '') ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tóm tắt nội dung</label>
                            <textarea name="tom_tat" class="form-control" rows="4" 
                                      placeholder="Mô tả ngắn gọn nội dung trình kỳ họp..."><?= e($_POST['tom_tat'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Cơ quan trình</label>
                            <select name="co_quan_trinh_id" class="form-select">
                                <option value="">-- Chọn cơ quan --</option>
                                <?php foreach ($co_quan_list as $cq): ?>
                                    <option value="<?= $cq['id'] ?>" <?= ($_POST['co_quan_trinh_id'] ?? '') == $cq['id'] ? 'selected' : '' ?>>
                                        <?= e($cq['ten_co_quan']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Sở, ngành chủ trì soạn thảo nội dung</small>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3"><i class="bi bi-person-check"></i> Chọn người phê duyệt</h6>
                        <div class="alert alert-info">
                            <small><i class="bi bi-info-circle"></i> 
                            Chọn Chánh Văn phòng hoặc một trong các Phó Chánh Văn phòng để phê duyệt nội dung này.
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Người phê duyệt <span class="text-danger">*</span></label>
                            <select name="nguoi_phe_duyet_id" class="form-select" required>
                                <option value="">-- Chọn người phê duyệt --</option>
                                <?php foreach ($nguoi_phe_duyet_list as $npd): ?>
                                    <option value="<?= $npd['id'] ?>" <?= ($_POST['nguoi_phe_duyet_id'] ?? '') == $npd['id'] ? 'selected' : '' ?>>
                                        <?= e($npd['ho_ten']) ?> 
                                        (<?= $GLOBALS['ROLES'][$npd['chuc_vu']] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h6 class="mb-3"><i class="bi bi-list-check"></i> Checklist công việc (Tùy chọn)</h6>
                        
                        <div class="mb-3">
                            <label class="form-label">Sử dụng template có sẵn</label>
                            <select name="template_id" class="form-select">
                                <option value="">-- Không sử dụng template --</option>
                                <?php foreach ($template_list as $tpl): ?>
                                    <option value="<?= $tpl['id'] ?>">
                                        <?= e($tpl['ten_template']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">Bạn có thể thêm/sửa checklist sau khi đăng ký</small>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Đăng ký nội dung
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
                        <li class="mb-2">Chọn kỳ họp bạn muốn đăng ký nội dung</li>
                        <li class="mb-2">Nhập tiêu đề rõ ràng, dễ hiểu</li>
                        <li class="mb-2">Mô tả tóm tắt nội dung chính</li>
                        <li class="mb-2">Chọn cơ quan chủ trì soạn thảo (nếu có)</li>
                        <li class="mb-2">Chọn người phê duyệt (Chánh VP hoặc Phó CVP)</li>
                        <li class="mb-2">Có thể sử dụng template checklist có sẵn</li>
                        <li class="mb-2">Sau khi đăng ký, trạng thái sẽ là "Chờ duyệt"</li>
                        <li>Người phê duyệt sẽ nhận được thông báo</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-info-circle"></i> Thông tin của bạn</h6>
                    <p class="mb-2"><strong>Họ tên:</strong> <?= e($current_user['ho_ten']) ?></p>
                    <p class="mb-2"><strong>Phòng ban:</strong> <?= e($current_user['ten_phong'] ?? 'Chưa gắn phòng') ?></p>
                    <p class="mb-0"><strong>Chức vụ:</strong> <?= e($GLOBALS['ROLES'][$current_user['chuc_vu']]) ?></p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$extra_js = <<<JS
<script>
$(document).ready(function() {
    $('#kyHopSelect').change(function() {
        const selectedOption = $(this).find('option:selected');
        const deadline = selectedOption.data('deadline');
        
        if (deadline) {
            $('#deadlineInfo').html('<span class="text-warning"><i class="bi bi-clock"></i> Hạn đăng ký: ' + deadline + '</span>');
        } else {
            $('#deadlineInfo').html('');
        }
    });
    
    // Trigger on load
    $('#kyHopSelect').trigger('change');
});
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
