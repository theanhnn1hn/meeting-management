<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Chương trình kỳ họp';

$ky_hop_id = $_GET['ky_hop_id'] ?? 0;
$ky_hop_list = $pdo->query("SELECT * FROM ky_hop ORDER BY ngay_hop DESC LIMIT 20")->fetchAll();

$chuong_trinh = [];
if ($ky_hop_id) {
    $stmt = $pdo->prepare("SELECT * FROM ky_hop WHERE id = ?");
    $stmt->execute([$ky_hop_id]);
    $ky_hop = $stmt->fetch();
    
    $stmt = $pdo->prepare("
        SELECT nd.*, cq.ten_co_quan
        FROM noi_dung nd
        LEFT JOIN co_quan cq ON nd.co_quan_trinh_id = cq.id
        WHERE nd.ky_hop_id = ? AND nd.trang_thai IN ('da_duyet', 'dang_xu_ly', 'hoan_thanh')
        ORDER BY nd.stt ASC
    ");
    $stmt->execute([$ky_hop_id]);
    $chuong_trinh = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<h2><i class="bi bi-list-ol"></i> <?= $page_title ?></h2>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET">
            <div class="row">
                <div class="col-md-10">
                    <select class="form-select" name="ky_hop_id">
                        <option value="">-- Chọn kỳ họp --</option>
                        <?php foreach ($ky_hop_list as $kh): ?>
                            <option value="<?= $kh['id'] ?>" <?= $ky_hop_id == $kh['id'] ? 'selected' : '' ?>>
                                <?= e($kh['ten_ky_hop']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Xem</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($chuong_trinh)): ?>
    <div class="card">
        <div class="card-header text-center">
            <h4>CHƯƠNG TRÌNH KỲ HỌP</h4>
            <h5><?= e($ky_hop['ten_ky_hop']) ?></h5>
            <p>Ngày: <?= format_date($ky_hop['ngay_hop']) ?></p>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th width="50">STT</th>
                        <th>Nội dung</th>
                        <th>Cơ quan trình</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chuong_trinh as $nd): ?>
                        <tr>
                            <td class="text-center"><?= $nd['stt'] ?></td>
                            <td><?= e($nd['tieu_de']) ?></td>
                            <td><?= e($nd['ten_co_quan'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
