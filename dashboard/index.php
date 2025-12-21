<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Route dashboard theo chức vụ
$chuc_vu = $_SESSION['chuc_vu'] ?? '';

switch ($chuc_vu) {
    case ROLE_CHANH_VP:
        include __DIR__ . '/chanh-vp.php';
        break;
    
    case ROLE_PHO_CVP:
        include __DIR__ . '/pho-cvp.php';
        break;
    
    case ROLE_TRUONG_PHONG:
        include __DIR__ . '/truong-phong.php';
        break;
    
    case ROLE_PHO_PHONG:
        include __DIR__ . '/pho-phong.php';
        break;
    
    case ROLE_CHUYEN_VIEN:
        include __DIR__ . '/chuyen-vien.php';
        break;
    
    default:
        die('Vai trò không hợp lệ');
}
?>
