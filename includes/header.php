<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$current_user = get_current_user();
$unread_count = count_unread_notifications($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? SYSTEM_NAME ?></title>
    
    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
    
    <style>
        :root {
            --primary-color: #1e40af;
            --secondary-color: #64748b;
            --success-color: #059669;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --info-color: #0891b2;
            --sidebar-bg: #1e293b;
            --sidebar-text: #cbd5e1;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
        }
        
        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            color: white !important;
        }
        
        .navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: all 0.3s;
        }
        
        .navbar .nav-link:hover {
            color: white !important;
            transform: translateY(-2px);
        }
        
        /* Notification Bell */
        .notification-bell {
            position: relative;
            font-size: 1.3rem;
            cursor: pointer;
            margin-right: 1rem;
        }
        
        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc2626;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.65rem;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        /* Notification Dropdown */
        .notification-dropdown {
            width: 380px;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notification-item:hover {
            background: #f1f5f9;
        }
        
        .notification-item.unread {
            background: #eff6ff;
        }
        
        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .notification-time {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        /* Sidebar */
        .sidebar {
            background: var(--sidebar-bg);
            min-height: calc(100vh - 56px);
            color: var(--sidebar-text);
        }
        
        .sidebar .nav-link {
            color: var(--sidebar-text);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin: 4px 8px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .sidebar .nav-link.active {
            background: var(--primary-color);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 24px;
            margin-right: 8px;
        }
        
        /* Main Content */
        .main-content {
            padding: 2rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid #f1f5f9;
            padding: 1.25rem;
            font-weight: 600;
        }
        
        /* Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Alerts */
        .alert {
            border-radius: 8px;
            border: none;
        }
        
        /* Progress bars */
        .progress {
            height: 24px;
            border-radius: 12px;
        }
        
        .progress-bar {
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        /* Badges */
        .badge {
            padding: 0.4rem 0.8rem;
            font-weight: 500;
            border-radius: 6px;
        }
        
        /* User dropdown */
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>">
            <i class="bi bi-calendar-check"></i> <?= SYSTEM_SHORT_NAME ?>
        </a>
        
        <div class="d-flex align-items-center ms-auto">
            <!-- Notification Bell -->
            <div class="notification-bell" id="notificationBell">
                <i class="bi bi-bell text-white"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge" id="notificationBadge"><?= $unread_count ?></span>
                <?php endif; ?>
            </div>
            
            <!-- User Info -->
            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-white user-info" href="#" role="button" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <?= strtoupper(substr($current_user['ho_ten'], 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline"><?= e($current_user['ho_ten']) ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header"><?= $GLOBALS['ROLES'][$current_user['chuc_vu']] ?></h6></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/profile.php"><i class="bi bi-person"></i> Hồ sơ</a></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/thong-bao/danh-sach.php"><i class="bi bi-bell"></i> Thông báo</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i> Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Notification Dropdown (Hidden) -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999; margin-top: 60px; display: none;" id="notificationDropdown">
    <div class="card notification-dropdown shadow-lg">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Thông báo</h6>
            <a href="<?= BASE_URL ?>/thong-bao/danh-sach.php" class="btn btn-sm btn-link">Xem tất cả</a>
        </div>
        <div class="card-body p-0" id="notificationList">
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-2 d-md-block sidebar">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </nav>
        
        <!-- Main Content -->
        <main class="col-md-10 ms-sm-auto main-content">
            <!-- Flash Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> <?= e($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?= e($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['warning'])): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle"></i> <?= e($_SESSION['warning']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['warning']); ?>
            <?php endif; ?>
