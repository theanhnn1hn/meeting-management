<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHUYEN_VIEN, ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]);

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

// Kiểm tra empty state
$has_ky_hop = !empty($ky_hop_list);
if (!$has_ky_hop) {
    $error = 'Hiện tại không có kỳ họp nào đang mở đăng ký nội dung.';
}

$co_quan_list = $pdo->query("SELECT * FROM co_quan WHERE trang_thai = 1 ORDER BY thu_tu, ten_co_quan")->fetchAll();

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
        
        if (empty($ky_hop_id) || empty($tieu_de) || empty($nguoi_phe_duyet_id)) {
            $error = 'Vui lòng nhập đầy đủ thông tin bắt buộc';
        } else {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("
                    INSERT INTO noi_dung (
                        ky_hop_id, tieu_de, tom_tat, co_quan_trinh_id,
                        nguoi_trinh_id, nguoi_phe_duyet_id, phong_ban_id,
                        trang_thai, tien_do
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 'cho_duyet', 0)
                ");
                $stmt->execute([
                    $ky_hop_id, $tieu_de, $tom_tat,
                    $co_quan_trinh_id ?: null,
                    $_SESSION['user_id'], $nguoi_phe_duyet_id,
                    $_SESSION['phong_ban_id']
                ]);
                
                $noi_dung_id = $pdo->lastInsertId();
                
                if ($template_id) {
                    $stmt = $pdo->prepare("SELECT danh_sach_cong_viec FROM template_checklist WHERE id = ?");
                    $stmt->execute([$template_id]);
                    $template = $stmt->fetch();
                    if ($template) {
                        $cong_viec_list = json_decode($template['danh_sach_cong_viec'], true);
                        if (!empty($cong_viec_list)) {
                            $stmt = $pdo->prepare("INSERT INTO checklist (noi_dung_id, ten_cong_viec, thu_tu) VALUES (?, ?, ?)");
                            foreach ($cong_viec_list as $index => $cv) {
                                $stmt->execute([$noi_dung_id, $cv, $index + 1]);
                            }
                        }
                    }
                }
                
                // Sử dụng $_SESSION['ho_ten'] để tránh lỗi undefined variable
                create_notification(
                    $nguoi_phe_duyet_id,
                    "Nội dung mới chờ phê duyệt",
                    "{$_SESSION['ho_ten']} vừa đăng ký nội dung: \"{$tieu_de}\"",
                    'dang_ky',
                    BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id,
                    $noi_dung_id
                );
                
                log_activity('Đăng ký nội dung kỳ họp', 'noi_dung', $noi_dung_id, $tieu_de);
                $pdo->commit();
                redirect(BASE_URL . '/noi-dung/chi-tiet.php?id=' . $noi_dung_id, 'Đăng ký thành công', 'success');
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Lỗi: ' . $e->getMessage();
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="bi bi-plus-circle"></i> Đăng ký nội dung kỳ họp</h2>
    </div>
</div>

<?php if ($error && !$has_ky_hop): ?>
    <div class="alert alert-warning">
        <i class="bi bi-info-circle"></i> <?= e($error) ?>
        <hr>
        <p class="mb-0">Vui lòng chờ khi có kỳ họp mới được tạo hoặc liên hệ Chánh Văn phòng để biết thêm chi tiết.</p>
    </div>
<?php elseif ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><?= e($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<?php if ($has_ky_hop): ?>
<div class="card">
    <div class="card-body">
        <form method="POST">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Kỳ họp <span class="text-danger">*</span></label>
                <select name="ky_hop_id" class="form-select" required>
                    <option value="">-- Chọn kỳ họp --</option>
                    <?php foreach ($ky_hop_list as $kh): ?>
                        <option value="<?= $kh['id'] ?>">
                            <?= e($kh['so_ky_hop']) ?> - <?= e($kh['ten_ky_hop']) ?>
                            <?php if ($kh['deadline_dang_ky']): ?>
                                (Hạn đăng ký: <?= format_date($kh['deadline_dang_ky']) ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Tiêu đề <span class="text-danger">*</span></label>
                <input type="text" name="tieu_de" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Cơ quan trình <span class="text-danger">*</span></label>
                <select name="co_quan_trinh_id" class="form-select" required>
                    <option value="">-- Chọn cơ quan --</option>
                    <?php foreach ($co_quan_list as $cq): ?>
                        <option value="<?= $cq['id'] ?>"><?= e($cq['ten_co_quan']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Người phê duyệt <span class="text-danger">*</span></label>
                <select name="nguoi_phe_duyet_id" class="form-select" required>
                    <?php foreach ($nguoi_phe_duyet_list as $npd): ?>
                        <option value="<?= $npd['id'] ?>"><?= e($npd['ho_ten']) ?> (<?= $GLOBALS['ROLES'][$npd['chuc_vu']] ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Đăng ký
            </button>
            <a href="danh-sach.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </form>
    </div>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>