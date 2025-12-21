<?php
$page_title = 'Dashboard Phó Chánh Văn phòng';

// Nội dung chờ duyệt (của Phó CVP này)
$stmt = $pdo->prepare("
    SELECT nd.*, u.ho_ten as nguoi_trinh, pb.ten_phong, kh.ten_ky_hop
    FROM noi_dung nd
    JOIN users u ON nd.nguoi_trinh_id = u.id
    JOIN ky_hop kh ON nd.ky_hop_id = kh.id
    LEFT JOIN phong_ban pb ON nd.phong_ban_id = pb.id
    WHERE nd.nguoi_phe_duyet_id = ? AND nd.trang_thai = 'cho_duyet'
    ORDER BY nd.created_at ASC
");
$stmt->execute([$_SESSION['user_id']]);
$cho_duyet = $stmt->fetchAll();

// Thống kê
$stmt = $pdo->query("SELECT COUNT(*) FROM noi_dung WHERE trang_thai = 'cho_duyet'");
$stats['cho_duyet'] = $stmt->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard Phó Chánh Văn phòng</h2>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-start border-warning border-4">
            <div class="card-body">
                <h6 class="text-muted mb-1">Chờ tôi duyệt</h6>
                <h2 class="mb-0"><?= count($cho_duyet) ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Nội dung chờ phê duyệt</h5>
            </div>
            <div class="card-body">
                <?php if (empty($cho_duyet)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-check-circle fs-1"></i>
                        <p class="mt-2">Không có nội dung chờ duyệt</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Tiêu đề</th>
                                    <th>Kỳ họp</th>
                                    <th>Người trình</th>
                                    <th>Phòng</th>
                                    <th>Ngày đăng ký</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cho_duyet as $nd): ?>
                                    <tr>
                                        <td><?= e($nd['tieu_de']) ?></td>
                                        <td><small><?= e($nd['ten_ky_hop']) ?></small></td>
                                        <td><?= e($nd['nguoi_trinh']) ?></td>
                                        <td><?= e($nd['ten_phong'] ?? '-') ?></td>
                                        <td><?= time_ago($nd['created_at']) ?></td>
                                        <td>
                                            <a href="<?= BASE_URL ?>/noi-dung/chi-tiet.php?id=<?= $nd['id'] ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-eye"></i> Xem & Duyệt
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
