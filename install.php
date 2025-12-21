<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt - Hệ thống Quản lý Kỳ họp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        .card-header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 2rem;
        }
        .step {
            padding: 1.5rem;
            border-left: 3px solid #e2e8f0;
            margin-bottom: 1rem;
        }
        .step.active {
            border-left-color: #3b82f6;
            background: #eff6ff;
        }
        .check-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: #f8fafc;
        }
        .check-item i {
            margin-right: 1rem;
            font-size: 1.5rem;
        }
        .check-success { color: #059669; }
        .check-error { color: #dc2626; }
    </style>
</head>
<body>
    <div class="install-container">
        <div class="card">
            <div class="card-header text-center">
                <h2><i class="bi bi-calendar-check"></i> Hệ thống Quản lý Kỳ họp UBND Tỉnh</h2>
                <p class="mb-0">Phiên bản 5.0 - Final Optimized</p>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> Vui lòng làm theo các bước sau để cài đặt hệ thống
                </div>
                
                <div class="step active">
                    <h5><i class="bi bi-1-circle"></i> Kiểm tra yêu cầu hệ thống</h5>
                    <div class="check-item">
                        <?php
                        $php_ok = version_compare(PHP_VERSION, '8.2.0', '>=');
                        ?>
                        <i class="bi <?= $php_ok ? 'bi-check-circle check-success' : 'bi-x-circle check-error' ?>"></i>
                        <div>
                            <strong>PHP Version:</strong> 
                            <?= PHP_VERSION ?> 
                            <?= $php_ok ? '(OK)' : '(Yêu cầu >= 8.2)' ?>
                        </div>
                    </div>
                    
                    <div class="check-item">
                        <?php
                        $pdo_ok = extension_loaded('pdo') && extension_loaded('pdo_mysql');
                        ?>
                        <i class="bi <?= $pdo_ok ? 'bi-check-circle check-success' : 'bi-x-circle check-error' ?>"></i>
                        <div><strong>PDO MySQL:</strong> <?= $pdo_ok ? 'Có sẵn' : 'Chưa cài đặt' ?></div>
                    </div>
                    
                    <div class="check-item">
                        <?php
                        $upload_ok = is_writable(__DIR__ . '/uploads');
                        ?>
                        <i class="bi <?= $upload_ok ? 'bi-check-circle check-success' : 'bi-x-circle check-error' ?>"></i>
                        <div><strong>Upload Directory:</strong> <?= $upload_ok ? 'Có quyền ghi' : 'Không có quyền ghi' ?></div>
                    </div>
                </div>
                
                <div class="step">
                    <h5><i class="bi bi-2-circle"></i> Tạo Database</h5>
                    <pre class="bg-light p-3 rounded"><code>mysql -u root -p
CREATE DATABASE meeting_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

mysql -u root -p meeting_management < database/schema.sql</code></pre>
                </div>
                
                <div class="step">
                    <h5><i class="bi bi-3-circle"></i> Cấu hình Database</h5>
                    <p>Sửa file <code>config/database.php</code>:</p>
                    <pre class="bg-light p-3 rounded"><code>$host = 'localhost';
$dbname = 'meeting_management';
$username = 'root';
$password = 'your_password';</code></pre>
                </div>
                
                <div class="step">
                    <h5><i class="bi bi-4-circle"></i> Cấu hình hệ thống</h5>
                    <p>Sửa file <code>config/config.php</code>:</p>
                    <ul>
                        <li>Cập nhật <code>BASE_URL</code></li>
                        <li>Cấu hình email (nếu cần)</li>
                        <li>Cấu hình upload path</li>
                    </ul>
                </div>
                
                <div class="step">
                    <h5><i class="bi bi-5-circle"></i> Set Permissions</h5>
                    <pre class="bg-light p-3 rounded"><code>chmod 755 -R .
chmod 777 uploads/
chmod 777 uploads/tai-lieu/</code></pre>
                </div>
                
                <div class="step">
                    <h5><i class="bi bi-6-circle"></i> Thiết lập Cron Job</h5>
                    <pre class="bg-light p-3 rounded"><code>crontab -e

# Thêm dòng sau:
0 * * * * php /path/to/meeting-management/cron/check-deadline-warnings.php</code></pre>
                </div>
                
                <div class="step">
                    <h5><i class="bi bi-7-circle"></i> Đăng nhập</h5>
                    <ul>
                        <li><strong>Username:</strong> chanh.vp</li>
                        <li><strong>Password:</strong> admin123</li>
                    </ul>
                </div>
                
                <div class="text-center mt-4">
                    <a href="auth/login.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-box-arrow-in-right"></i> Đăng nhập ngay
                    </a>
                </div>
                
                <div class="alert alert-warning mt-4">
                    <i class="bi bi-exclamation-triangle"></i> <strong>Lưu ý:</strong> 
                    Sau khi cài đặt xong, hãy xóa hoặc đổi tên file <code>install.php</code> này để bảo mật.
                </div>
            </div>
            <div class="card-footer text-center text-muted">
                <small>Hệ thống Quản lý Kỳ họp UBND Tỉnh - Phiên bản 5.0</small>
            </div>
        </div>
    </div>
</body>
</html>
