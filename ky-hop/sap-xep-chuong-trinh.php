<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role([ROLE_CHANH_VP]);

$page_title = 'Sắp xếp chương trình';

$id = $_GET['id'] ?? 0;

// Lấy thông tin kỳ họp
$stmt = $pdo->prepare("SELECT * FROM ky_hop WHERE id = ?");
$stmt->execute([$id]);
$ky_hop = $stmt->fetch();

if (!$ky_hop) {
    redirect(BASE_URL . '/ky-hop/danh-sach.php', 'Không tìm thấy kỳ họp', 'error');
}

// Xử lý cập nhật thứ tự
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Token không hợp lệ';
    } else {
        $order = json_decode($_POST['order'] ?? '[]', true);
        
        if (!empty($order)) {
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("UPDATE noi_dung SET stt = ? WHERE id = ?");
                foreach ($order as $index => $noi_dung_id) {
                    $stmt->execute([$index + 1, $noi_dung_id]);
                }
                
                $pdo->commit();
                
                log_activity('Sắp xếp chương trình kỳ họp', 'ky_hop', $id);
                
                $_SESSION['success'] = 'Cập nhật thứ tự thành công';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Lỗi cập nhật: ' . $e->getMessage();
            }
        }
    }
}

// Lấy danh sách nội dung đã duyệt
$stmt = $pdo->prepare("
    SELECT nd.id, nd.tieu_de, nd.stt, nd.trang_thai,
           cq.ten_co_quan,
           u.ho_ten as nguoi_trinh
    FROM noi_dung nd
    LEFT JOIN co_quan cq ON nd.co_quan_trinh_id = cq.id
    LEFT JOIN users u ON nd.nguoi_trinh_id = u.id
    WHERE nd.ky_hop_id = ?
    AND nd.trang_thai IN ('da_duyet', 'dang_xu_ly', 'hoan_thanh')
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
                <li class="breadcrumb-item"><a href="chi-tiet.php?id=<?= $id ?>"><?= e($ky_hop['so_ky_hop']) ?></a></li>
                <li class="breadcrumb-item active">Sắp xếp chương trình</li>
            </ol>
        </nav>
        <h2 class="mb-0"><i class="bi bi-list-ol"></i> Sắp xếp chương trình kỳ họp</h2>
        <p class="text-muted"><?= e($ky_hop['ten_ky_hop']) ?></p>
    </div>
    <div class="col-md-4 text-end">
        <a href="chi-tiet.php?id=<?= $id ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

<?php if (empty($noi_dung_list)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Chưa có nội dung nào được phê duyệt để sắp xếp
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-grip-vertical"></i> Kéo thả để sắp xếp</h5>
                    <button onclick="saveOrder()" class="btn btn-primary btn-sm">
                        <i class="bi bi-save"></i> Lưu thứ tự
                    </button>
                </div>
                <div class="card-body p-0">
                    <div id="sortable-list" class="list-group list-group-flush">
                        <?php foreach ($noi_dung_list as $index => $nd): ?>
                            <div class="list-group-item sortable-item" data-id="<?= $nd['id'] ?>">
                                <div class="d-flex align-items-center">
                                    <div class="handle me-3" style="cursor: move;">
                                        <i class="bi bi-grip-vertical fs-4 text-muted"></i>
                                    </div>
                                    <div class="stt-badge me-3">
                                        <span class="badge bg-primary rounded-pill"><?= $index + 1 ?></span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= e($nd['tieu_de']) ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-building"></i> <?= e($nd['ten_co_quan'] ?? 'Chưa xác định') ?>
                                            | <i class="bi bi-person"></i> <?= e($nd['nguoi_trinh']) ?>
                                        </small>
                                    </div>
                                    <div>
                                        <?= status_badge($nd['trang_thai']) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <form method="POST" id="formSaveOrder">
                <?= csrf_field() ?>
                <input type="hidden" name="update_order" value="1">
                <input type="hidden" name="order" id="orderInput">
            </form>
        </div>
        
        <div class="col-lg-4">
            <div class="card bg-light">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-lightbulb"></i> Hướng dẫn</h6>
                    <ul class="small">
                        <li class="mb-2">Kéo và thả các mục để sắp xếp thứ tự</li>
                        <li class="mb-2">Số thứ tự sẽ tự động cập nhật khi bạn di chuyển</li>
                        <li class="mb-2">Nhấn "Lưu thứ tự" để cập nhật vào hệ thống</li>
                        <li class="mb-2">Chỉ các nội dung đã được phê duyệt mới được sắp xếp</li>
                        <li>Thứ tự này sẽ được sử dụng khi xuất chương trình kỳ họp</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
$extra_js = <<<JS
<script>
let sortable;

$(document).ready(function() {
    const el = document.getElementById('sortable-list');
    
    if (el) {
        sortable = Sortable.create(el, {
            handle: '.handle',
            animation: 150,
            ghostClass: 'bg-light',
            onEnd: function() {
                updateSTT();
            }
        });
    }
});

function updateSTT() {
    $('.sortable-item').each(function(index) {
        $(this).find('.stt-badge .badge').text(index + 1);
    });
}

function saveOrder() {
    const order = sortable.toArray();
    
    if (order.length === 0) {
        Swal.fire('Lỗi', 'Không có dữ liệu để lưu', 'error');
        return;
    }
    
    $('#orderInput').val(JSON.stringify(order));
    
    Swal.fire({
        title: 'Xác nhận',
        text: 'Bạn có chắc chắn muốn lưu thứ tự này?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Lưu',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#formSaveOrder').submit();
        }
    });
}
</script>

<style>
.sortable-item {
    transition: all 0.3s;
}

.sortable-item:hover {
    background-color: #f8f9fa;
}

.handle:hover i {
    color: #0d6efd !important;
}
</style>
JS;

include __DIR__ . '/../includes/footer.php';
?>
