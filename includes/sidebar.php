<?php
$chuc_vu = $_SESSION['chuc_vu'] ?? '';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="py-3">
    <!-- Dashboard -->
    <ul class="nav flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, 'dashboard') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/dashboard/">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
    </ul>
    
    <!-- Kỳ họp -->
    <div class="px-3 mb-2 text-uppercase small text-muted">Quản lý kỳ họp</div>
    <ul class="nav flex-column mb-3">
        <?php if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP])): ?>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'ky-hop') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/ky-hop/danh-sach.php">
                    <i class="bi bi-calendar-event"></i> Kỳ họp
                </a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, 'noi-dung') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/noi-dung/danh-sach.php">
                <i class="bi bi-file-earmark-text"></i> Nội dung
            </a>
        </li>
        
        <?php if (in_array($chuc_vu, [ROLE_CHANH_VP])): ?>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'chuong-trinh') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/chuong-trinh/xem.php">
                    <i class="bi bi-list-ol"></i> Chương trình
                </a>
            </li>
        <?php endif; ?>
    </ul>
    
    <!-- Báo cáo -->
    <div class="px-3 mb-2 text-uppercase small text-muted">Báo cáo & Thống kê</div>
    <ul class="nav flex-column mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'tien-do-ky-hop.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/bao-cao/tien-do-ky-hop.php">
                <i class="bi bi-graph-up"></i> Tiến độ kỳ họp
            </a>
        </li>
        
        <?php if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG])): ?>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'tien-do-phong.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/bao-cao/tien-do-phong.php">
                    <i class="bi bi-building"></i> Tiến độ phòng
                </a>
            </li>
        <?php endif; ?>
        
        <?php if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP, ROLE_TRUONG_PHONG])): ?>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'doc-viec.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/bao-cao/doc-viec.php">
                    <i class="bi bi-exclamation-triangle"></i> Đốc việc
                </a>
            </li>
        <?php endif; ?>
        
        <?php if (in_array($chuc_vu, [ROLE_CHANH_VP, ROLE_PHO_CVP])): ?>
            <li class="nav-item">
                <a class="nav-link <?= $current_page == 'danh-gia-can-bo.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/bao-cao/danh-gia-can-bo.php">
                    <i class="bi bi-star"></i> Đánh giá cán bộ
                </a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item">
            <a class="nav-link <?= $current_page == 'thong-ke-chung.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/bao-cao/thong-ke-chung.php">
                <i class="bi bi-bar-chart"></i> Thống kê chung
            </a>
        </li>
    </ul>
    
    <!-- Quản lý (chỉ Chánh VP) -->
    <?php if ($chuc_vu === ROLE_CHANH_VP): ?>
        <div class="px-3 mb-2 text-uppercase small text-muted">Quản lý hệ thống</div>
        <ul class="nav flex-column mb-3">
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'nguoi-dung') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/quan-ly/nguoi-dung/danh-sach.php">
                    <i class="bi bi-people"></i> Người dùng
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'phong-ban') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/quan-ly/phong-ban/danh-sach.php">
                    <i class="bi bi-building"></i> Phòng ban
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'co-quan') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/quan-ly/co-quan/danh-sach.php">
                    <i class="bi bi-diagram-3"></i> Cơ quan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= strpos($current_page, 'template-checklist') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/quan-ly/template-checklist/danh-sach.php">
                    <i class="bi bi-list-check"></i> Template
                </a>
            </li>
        </ul>
    <?php endif; ?>
    
    <!-- Thông báo -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?= strpos($current_page, 'thong-bao') !== false ? 'active' : '' ?>" href="<?= BASE_URL ?>/thong-bao/danh-sach.php">
                <i class="bi bi-bell"></i> Thông báo
                <?php if ($unread_count > 0): ?>
                    <span class="badge bg-danger rounded-pill"><?= $unread_count ?></span>
                <?php endif; ?>
            </a>
        </li>
    </ul>
</div>
