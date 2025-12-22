-- =====================================================
-- MIGRATION: Bug Fixes - Version 5.1
-- Date: 2025-12-22
-- Author: System Administrator
-- =====================================================

-- Bug fixes:
-- 1. Fixed $current_user variable in dang-ky.php
-- 2. Fixed CSRF vulnerability in phe-duyet.php  
-- 3. Added permission check for phe-duyet.php
-- 4. Added ngay_doc column to thong_bao table
-- 5. Made co_quan_trinh_id required in business logic

-- =====================================================
-- 1. THÊM CỘT ngay_doc VÀO BẢNG thong_bao
-- =====================================================

ALTER TABLE thong_bao 
ADD COLUMN IF NOT EXISTS ngay_doc datetime DEFAULT NULL COMMENT 'Ngày đọc thông báo'
AFTER da_doc;

-- Verify
SELECT 
    COLUMN_NAME, 
    COLUMN_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT, 
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'thong_bao'
  AND COLUMN_NAME = 'ngay_doc';

-- =====================================================
-- 2. CẬP NHẬT DỮ LIỆU CŨ (Optional)
-- =====================================================

-- Cập nhật ngay_doc = created_at cho các thông báo đã đọc
UPDATE thong_bao 
SET ngay_doc = created_at 
WHERE da_doc = 1 AND ngay_doc IS NULL;

-- =====================================================
-- 3. KIỂM TRA TÍNH TOÀN VẸN DỮ LIỆU
-- =====================================================

-- Kiểm tra các nội dung không có cơ quan trình
SELECT 
    nd.id,
    nd.tieu_de,
    kh.ten_ky_hop,
    u.ho_ten as nguoi_trinh,
    nd.created_at
FROM noi_dung nd
JOIN ky_hop kh ON nd.ky_hop_id = kh.id
JOIN users u ON nd.nguoi_trinh_id = u.id
WHERE nd.co_quan_trinh_id IS NULL
ORDER BY nd.created_at DESC;

-- Nếu có dữ liệu NULL, nên cập nhật hoặc xóa:
-- UPDATE noi_dung SET co_quan_trinh_id = [ID_CO_QUAN_MAC_DINH] WHERE co_quan_trinh_id IS NULL;

-- =====================================================
-- 4. THÊM INDEXES ĐỂ TỐI ƯU PERFORMANCE
-- =====================================================

-- Index cho ngay_doc (dùng trong thống kê)
ALTER TABLE thong_bao 
ADD INDEX IF NOT EXISTS idx_ngay_doc (ngay_doc);

-- Index composite cho query notification list
ALTER TABLE thong_bao
ADD INDEX IF NOT EXISTS idx_user_created (user_id, created_at DESC);

-- =====================================================
-- 5. KIỂM TRA QUYỀN PHÊ DUYỆT
-- =====================================================

-- Liệt kê các nội dung có người phê duyệt không hợp lệ
SELECT 
    nd.id,
    nd.tieu_de,
    nd.trang_thai,
    u1.ho_ten as nguoi_trinh,
    u2.ho_ten as nguoi_phe_duyet,
    u2.chuc_vu as chuc_vu_phe_duyet
FROM noi_dung nd
JOIN users u1 ON nd.nguoi_trinh_id = u1.id
LEFT JOIN users u2 ON nd.nguoi_phe_duyet_id = u2.id
WHERE nd.nguoi_phe_duyet_id IS NOT NULL
  AND u2.chuc_vu NOT IN ('chanh_vp', 'pho_cvp')
ORDER BY nd.created_at DESC;

-- Nếu có dữ liệu không hợp lệ, cần sửa:
-- UPDATE noi_dung SET nguoi_phe_duyet_id = [ID_CHANH_VP] WHERE id IN ([danh_sach_id]);

-- =====================================================
-- 6. VERIFY FINAL RESULT
-- =====================================================

-- Kiểm tra số lượng thông báo
SELECT 
    COUNT(*) as tong_thong_bao,
    SUM(CASE WHEN da_doc = 1 THEN 1 ELSE 0 END) as da_doc,
    SUM(CASE WHEN da_doc = 0 THEN 1 ELSE 0 END) as chua_doc,
    SUM(CASE WHEN ngay_doc IS NOT NULL THEN 1 ELSE 0 END) as co_ngay_doc
FROM thong_bao;

-- Kiểm tra các nội dung theo trạng thái
SELECT 
    trang_thai,
    COUNT(*) as so_luong,
    COUNT(CASE WHEN nguoi_phe_duyet_id IS NOT NULL THEN 1 END) as co_nguoi_duyet,
    COUNT(CASE WHEN co_quan_trinh_id IS NOT NULL THEN 1 END) as co_co_quan
FROM noi_dung
GROUP BY trang_thai
ORDER BY 
    CASE trang_thai
        WHEN 'cho_duyet' THEN 1
        WHEN 'da_duyet' THEN 2
        WHEN 'tu_choi' THEN 3
        WHEN 'dang_xu_ly' THEN 4
        WHEN 'hoan_thanh' THEN 5
    END;

-- =====================================================
-- 7. ACTIVITY LOG
-- =====================================================

-- Log migration
INSERT INTO activity_log (
    user_id,
    hanh_dong,
    bang,
    chi_tiet,
    ip_address
) VALUES (
    1, -- admin user
    'Chạy migration Bug Fixes v5.1',
    'system',
    'Fixed: CSRF, permission check, variable scope, database schema',
    '127.0.0.1'
);

-- =====================================================
-- 8. ROLLBACK SCRIPT (Nếu cần)
-- =====================================================

/*
-- Chỉ chạy nếu cần rollback

-- Remove ngay_doc column
ALTER TABLE thong_bao DROP COLUMN IF EXISTS ngay_doc;

-- Remove new indexes
ALTER TABLE thong_bao DROP INDEX IF EXISTS idx_ngay_doc;
ALTER TABLE thong_bao DROP INDEX IF EXISTS idx_user_created;

-- Delete migration log
DELETE FROM activity_log 
WHERE hanh_dong = 'Chạy migration Bug Fixes v5.1' 
  AND bang = 'system';
*/

-- =====================================================
-- NOTES
-- =====================================================

/*
IMPORTANT CHANGES:

1. SECURITY FIXES:
   - phe-duyet.php: Chuyển từ GET sang POST + CSRF protection
   - phe-duyet.php: Thêm kiểm tra nguoi_phe_duyet_id
   - dang-ky.php: Fixed variable scope issue

2. DATABASE CHANGES:
   - Added thong_bao.ngay_doc column
   - Added performance indexes

3. BUSINESS LOGIC CHANGES:
   - co_quan_trinh_id now REQUIRED in form (not in DB constraint)
   - Stricter permission checks for approval

4. FILES TO UPDATE:
   - noi-dung/dang-ky.php (fixed)
   - noi-dung/phe-duyet.php (fixed)
   - noi-dung/chi-tiet.php (needs update for POST form)
   - includes/notification-ajax.php (already fixed)
   - includes/functions.php (no changes needed)

5. TESTING CHECKLIST:
   [ ] Test đăng ký nội dung mới
   [ ] Test phê duyệt với đúng người được phân công
   [ ] Test phê duyệt với sai người (should fail)
   [ ] Test CSRF với token không hợp lệ (should fail)
   [ ] Test thông báo được tạo đúng
   [ ] Test hiển thị ngay_doc
   [ ] Check performance của notification queries
*/

-- =====================================================
-- END OF MIGRATION
-- =====================================================

SELECT 'Migration Bug Fixes v5.1 completed successfully!' as status;
