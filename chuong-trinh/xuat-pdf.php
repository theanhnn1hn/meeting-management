<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';

$ky_hop_id = (int)($_GET['id'] ?? 0);

if ($ky_hop_id <= 0) {
    die('Thiếu thông tin kỳ họp');
}

// Lấy thông tin kỳ họp
$stmt = $pdo->prepare("SELECT * FROM ky_hop WHERE id = ?");
$stmt->execute([$ky_hop_id]);
$ky_hop = $stmt->fetch();

if (!$ky_hop) {
    die('Không tìm thấy kỳ họp');
}

// Lấy danh sách nội dung đã được duyệt và sắp xếp theo STT
$stmt = $pdo->prepare("
    SELECT nd.*, cq.ten_co_quan, u.ho_ten as nguoi_trinh
    FROM noi_dung nd
    LEFT JOIN co_quan cq ON nd.co_quan_trinh_id = cq.id
    LEFT JOIN users u ON nd.nguoi_trinh_id = u.id
    WHERE nd.ky_hop_id = ? AND nd.trang_thai IN ('da_duyet', 'dang_xu_ly', 'hoan_thanh')
    ORDER BY nd.stt ASC, nd.id ASC
");
$stmt->execute([$ky_hop_id]);
$noi_dung_list = $stmt->fetchAll();

// Header để tải file PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chương trình kỳ họp <?= e($ky_hop['so_ky_hop']) ?></title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 2cm;
            }
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14pt;
            line-height: 1.5;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .header-left {
            float: left;
            text-align: center;
            width: 50%;
        }
        
        .header-right {
            float: right;
            text-align: center;
            width: 50%;
        }
        
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 16pt;
            margin: 30px 0 10px 0;
            text-transform: uppercase;
        }
        
        .info {
            text-align: center;
            margin-bottom: 20px;
            font-style: italic;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        table, th, td {
            border: 1px solid black;
        }
        
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            padding: 10px;
            text-align: center;
        }
        
        td {
            padding: 8px;
        }
        
        .stt {
            width: 50px;
            text-align: center;
        }
        
        .noi-dung {
            width: 60%;
        }
        
        .co-quan {
            width: 30%;
        }
        
        .ghi-chu {
            width: 10%;
        }
        
        .footer {
            margin-top: 40px;
            text-align: right;
        }
        
        .signature {
            margin-top: 10px;
            font-style: italic;
        }
        
        .signature-name {
            margin-top: 60px;
            font-weight: bold;
        }
        
        .btn-print {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        @media print {
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <button class="btn-print" onclick="window.print()">🖨️ In chương trình</button>
    
    <div class="clearfix">
        <div class="header-left">
            <strong>ỦY BAN NHÂN DÂN TỈNH</strong><br>
            <strong>VĂN PHÒNG</strong><br>
            ______
        </div>
        <div class="header-right">
            <strong>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><br>
            <strong>Độc lập - Tự do - Hạnh phúc</strong><br>
            ___________
        </div>
    </div>
    
    <div class="title">
        CHƯƠNG TRÌNH<br>
        KỲ HỌP <?= strtoupper(e($ky_hop['ten_ky_hop'])) ?>
    </div>
    
    <div class="info">
        (Số <?= e($ky_hop['so_ky_hop']) ?> - Ngày <?= format_date($ky_hop['ngay_hop']) ?>)
    </div>
    
    <table>
        <thead>
            <tr>
                <th class="stt">STT</th>
                <th class="noi-dung">NỘI DUNG</th>
                <th class="co-quan">CƠ QUAN TRÌNH</th>
                <th class="ghi-chu">GHI CHÚ</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($noi_dung_list)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; font-style: italic;">Chưa có nội dung nào</td>
                </tr>
            <?php else: ?>
                <?php foreach ($noi_dung_list as $index => $nd): ?>
                    <tr>
                        <td class="stt"><?= ($index + 1) ?></td>
                        <td class="noi-dung">
                            <strong><?= e($nd['tieu_de']) ?></strong>
                            <?php if ($nd['tom_tat']): ?>
                                <br><em><?= e($nd['tom_tat']) ?></em>
                            <?php endif; ?>
                        </td>
                        <td class="co-quan"><?= e($nd['ten_co_quan'] ?? 'Chưa xác định') ?></td>
                        <td class="ghi-chu"><?= e($nd['ghi_chu'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="footer">
        <div class="signature">
            <?php 
            $ngay_in = date('d');
            $thang_in = date('m');
            $nam_in = date('Y');
            ?>
            <em>Ngày <?= $ngay_in ?> tháng <?= $thang_in ?> năm <?= $nam_in ?></em>
        </div>
        <div style="margin-top: 5px;">
            <strong>CHÁNH VĂN PHÒNG</strong>
        </div>
        <div class="signature-name">
            <?= strtoupper(e($ky_hop['chu_tri'] ?? '')) ?>
        </div>
    </div>
    
    <script>
        // Auto print dialog khi load trang
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
