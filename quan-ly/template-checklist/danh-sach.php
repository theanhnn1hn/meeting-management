<?php
require_once __DIR__ . '/../../auth/check_auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_role([ROLE_CHANH_VP]);

$page_title = 'Quản lý template checklist';

include __DIR__ . '/../../includes/header.php';
?>
<h2><i class="bi bi-list-check"></i> <?= $page_title ?></h2>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i> Module template checklist cho phép tạo các mẫu công việc để áp dụng nhanh khi đăng ký nội dung.
</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
