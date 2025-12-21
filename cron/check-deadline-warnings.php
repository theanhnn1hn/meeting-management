<?php
// =====================================================
// CRON JOB: CHECK DEADLINE WARNINGS
// Chạy mỗi giờ để tạo thông báo in-app
// =====================================================

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';

echo "[" . date('Y-m-d H:i:s') . "] Bắt đầu kiểm tra deadline warnings...\n";

try {
    // Lấy danh sách nội dung cần cảnh báo
    $stmt = $pdo->query("
        SELECT nd.id, nd.tieu_de, nd.nguoi_trinh_id, kh.deadline_hoan_thien,
               DATEDIFF(kh.deadline_hoan_thien, CURDATE()) as days_left
        FROM noi_dung nd
        JOIN ky_hop kh ON nd.ky_hop_id = kh.id
        WHERE nd.trang_thai IN ('da_duyet', 'dang_xu_ly')
        AND nd.tien_do < 100
        AND kh.deadline_hoan_thien >= CURDATE()
    ");
    
    $noi_dung_list = $stmt->fetchAll();
    $created_count = 0;
    
    foreach ($noi_dung_list as $nd) {
        $days_left = $nd['days_left'];
        $loai_thong_bao = null;
        $tieu_de = '';
        $noi_dung_tb = '';
        
        // Xác định loại thông báo
        if ($days_left == DEADLINE_WARNING_T7) {
            $loai_thong_bao = 'deadline_t7';
            $tieu_de = 'Còn 7 ngày đến hạn';
            $noi_dung_tb = "Nội dung \"{$nd['tieu_de']}\" còn 7 ngày đến hạn hoàn thiện";
        } elseif ($days_left == DEADLINE_WARNING_T3) {
            $loai_thong_bao = 'deadline_t3';
            $tieu_de = 'Còn 3 ngày đến hạn - Khẩn cấp!';
            $noi_dung_tb = "Nội dung \"{$nd['tieu_de']}\" còn 3 ngày đến hạn hoàn thiện";
        } elseif ($days_left == DEADLINE_WARNING_T1) {
            $loai_thong_bao = 'deadline_t1';
            $tieu_de = 'Còn 1 ngày đến hạn - RẤT KHẨN CẤP!';
            $noi_dung_tb = "Nội dung \"{$nd['tieu_de']}\" còn 1 ngày đến hạn hoàn thiện";
        } elseif ($days_left < 0) {
            $loai_thong_bao = 'qua_han';
            $tieu_de = 'ĐÃ QUÁ HẠN!';
            $noi_dung_tb = "Nội dung \"{$nd['tieu_de']}\" đã quá hạn hoàn thiện";
        }
        
        if ($loai_thong_bao) {
            // Kiểm tra xem đã tạo thông báo trong 24h chưa
            $check_stmt = $pdo->prepare("
                SELECT COUNT(*) FROM thong_bao 
                WHERE user_id = ? 
                AND noi_dung_id = ? 
                AND loai = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $check_stmt->execute([$nd['nguoi_trinh_id'], $nd['id'], $loai_thong_bao]);
            
            if ($check_stmt->fetchColumn() == 0) {
                // Tạo thông báo mới
                $insert_stmt = $pdo->prepare("
                    INSERT INTO thong_bao (user_id, tieu_de, noi_dung, loai, lien_ket, noi_dung_id) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert_stmt->execute([
                    $nd['nguoi_trinh_id'],
                    $tieu_de,
                    $noi_dung_tb,
                    $loai_thong_bao,
                    BASE_URL . '/noi-dung/chi-tiet.php?id=' . $nd['id'],
                    $nd['id']
                ]);
                
                $created_count++;
                echo "  [+] Tạo thông báo {$loai_thong_bao} cho nội dung ID {$nd['id']}\n";
            }
        }
    }
    
    echo "[" . date('Y-m-d H:i:s') . "] Hoàn thành. Đã tạo {$created_count} thông báo mới.\n";
    
} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
}
?>
