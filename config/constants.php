<?php
// =====================================================
// CONSTANTS - Roles, Trạng thái, Màu sắc
// =====================================================

// ROLES
define('ROLE_CHANH_VP', 'chanh_vp');
define('ROLE_PHO_CVP', 'pho_cvp');
define('ROLE_TRUONG_PHONG', 'truong_phong');
define('ROLE_PHO_PHONG', 'pho_phong');
define('ROLE_CHUYEN_VIEN', 'chuyen_vien');

$GLOBALS['ROLES'] = [
    ROLE_CHANH_VP => 'Chánh Văn phòng',
    ROLE_PHO_CVP => 'Phó Chánh Văn phòng',
    ROLE_TRUONG_PHONG => 'Trưởng phòng',
    ROLE_PHO_PHONG => 'Phó phòng',
    ROLE_CHUYEN_VIEN => 'Chuyên viên'
];

// TRẠNG THÁI KỲ HỌP
$GLOBALS['KY_HOP_STATUS'] = [
    'du_thao' => ['label' => 'Dự thảo', 'color' => 'secondary'],
    'dang_xu_ly' => ['label' => 'Đang xử lý', 'color' => 'info'],
    'sap_dien_ra' => ['label' => 'Sắp diễn ra', 'color' => 'warning'],
    'da_dien_ra' => ['label' => 'Đã diễn ra', 'color' => 'success'],
    'huy' => ['label' => 'Hủy', 'color' => 'danger']
];

// TRẠNG THÁI NỘI DUNG
$GLOBALS['NOI_DUNG_STATUS'] = [
    'cho_duyet' => ['label' => 'Chờ duyệt', 'color' => 'warning'],
    'da_duyet' => ['label' => 'Đã duyệt', 'color' => 'success'],
    'tu_choi' => ['label' => 'Từ chối', 'color' => 'danger'],
    'dang_xu_ly' => ['label' => 'Đang xử lý', 'color' => 'info'],
    'hoan_thanh' => ['label' => 'Hoàn thành', 'color' => 'primary']
];

// LOẠI COMMENT
$GLOBALS['COMMENT_TYPES'] = [
    'gop_y' => ['label' => 'Góp ý', 'icon' => 'bi-chat-dots', 'color' => 'info'],
    'doc_viec' => ['label' => 'Đốc việc', 'icon' => 'bi-exclamation-triangle', 'color' => 'warning'],
    'yeu_cau_sua' => ['label' => 'Yêu cầu sửa', 'icon' => 'bi-pencil-square', 'color' => 'danger']
];

// LOẠI THÔNG BÁO
$GLOBALS['NOTIFICATION_TYPES'] = [
    'dang_ky' => ['label' => 'Đăng ký nội dung', 'icon' => 'bi-file-earmark-plus', 'color' => 'primary'],
    'phe_duyet' => ['label' => 'Phê duyệt', 'icon' => 'bi-check-circle', 'color' => 'success'],
    'tu_choi' => ['label' => 'Từ chối', 'icon' => 'bi-x-circle', 'color' => 'danger'],
    'comment' => ['label' => 'Góp ý', 'icon' => 'bi-chat-dots', 'color' => 'info'],
    'doc_viec' => ['label' => 'Đốc việc', 'icon' => 'bi-bell', 'color' => 'warning'],
    'deadline_t7' => ['label' => 'Còn 7 ngày', 'icon' => 'bi-clock', 'color' => 'info'],
    'deadline_t3' => ['label' => 'Còn 3 ngày', 'icon' => 'bi-clock', 'color' => 'warning'],
    'deadline_t1' => ['label' => 'Còn 1 ngày', 'icon' => 'bi-clock', 'color' => 'danger'],
    'qua_han' => ['label' => 'Quá hạn', 'icon' => 'bi-exclamation-octagon', 'color' => 'danger'],
    'hoan_thanh' => ['label' => 'Hoàn thành', 'icon' => 'bi-check-all', 'color' => 'success'],
    'cap_nhat' => ['label' => 'Cập nhật', 'icon' => 'bi-arrow-repeat', 'color' => 'secondary']
];

// DEADLINE WARNINGS (days)
define('DEADLINE_WARNING_T7', 7);
define('DEADLINE_WARNING_T3', 3);
define('DEADLINE_WARNING_T1', 1);

// ĐÁNH GIÁ CÁN BỘ
$GLOBALS['XEP_LOAI'] = [
    'xuat_sac' => ['label' => 'Xuất sắc', 'color' => 'success', 'min' => 90],
    'tot' => ['label' => 'Tốt', 'color' => 'primary', 'min' => 80],
    'kha' => ['label' => 'Khá', 'color' => 'info', 'min' => 70],
    'trung_binh' => ['label' => 'Trung bình', 'color' => 'warning', 'min' => 60],
    'yeu' => ['label' => 'Yếu', 'color' => 'danger', 'min' => 0]
];

// MÀU CHO TIẾN ĐỘ %
function get_tien_do_color($percent) {
    if ($percent >= 80) return 'success';
    if ($percent >= 50) return 'info';
    if ($percent >= 30) return 'warning';
    return 'danger';
}

// QUYỀN TRUY CẬP
$GLOBALS['PERMISSIONS'] = [
    'manage_users' => [ROLE_CHANH_VP],
    'manage_phong_ban' => [ROLE_CHANH_VP],
    'manage_co_quan' => [ROLE_CHANH_VP],
    'create_ky_hop' => [ROLE_CHANH_VP],
    'phe_duyet' => [ROLE_CHANH_VP, ROLE_PHO_CVP],
    'doc_viec_all' => [ROLE_CHANH_VP, ROLE_PHO_CVP],
    'doc_viec_phong' => [ROLE_TRUONG_PHONG, ROLE_PHO_PHONG],
    'view_all' => [ROLE_CHANH_VP, ROLE_PHO_CVP],
    'view_phong' => [ROLE_TRUONG_PHONG, ROLE_PHO_PHONG],
    'dang_ky_noi_dung' => [ROLE_CHUYEN_VIEN, ROLE_TRUONG_PHONG, ROLE_PHO_PHONG]
];

// Helper function kiểm tra quyền
function has_permission($permission) {
    if (!isset($_SESSION['user_id'])) return false;
    
    $chuc_vu = $_SESSION['chuc_vu'] ?? '';
    $allowed_roles = $GLOBALS['PERMISSIONS'][$permission] ?? [];
    
    return in_array($chuc_vu, $allowed_roles);
}

// Helper function kiểm tra nhiều quyền
function has_any_permission($permissions) {
    foreach ($permissions as $perm) {
        if (has_permission($perm)) return true;
    }
    return false;
}
?>
