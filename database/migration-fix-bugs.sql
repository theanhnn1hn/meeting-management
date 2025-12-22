-- =====================================================
-- MIGRATION: Fix Bugs Website - Thêm cột ngay_doc
-- Version: 5.0
-- Date: 2025-12-22
-- =====================================================

-- Kiểm tra và thêm cột ngay_doc vào bảng thong_bao
ALTER TABLE thong_bao 
ADD COLUMN IF NOT EXISTS ngay_doc datetime DEFAULT NULL COMMENT 'Ngày đọc thông báo'
AFTER da_doc;

-- Verify kết quả
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

-- Nếu thành công, sẽ hiển thị:
-- ngay_doc | datetime | YES | NULL | Ngày đọc thông báo

-- =====================================================
-- OPTIONAL: Cập nhật ngay_doc cho các thông báo đã đọc
-- (Chỉ chạy nếu muốn có dữ liệu cho cột mới)
-- =====================================================

-- Cập nhật ngay_doc = created_at cho các thông báo đã đọc trước đây
UPDATE thong_bao 
SET ngay_doc = created_at 
WHERE da_doc = 1 AND ngay_doc IS NULL;

-- =====================================================
-- VERIFY FINAL RESULT
-- =====================================================

-- Kiểm tra số lượng thông báo
SELECT 
    COUNT(*) as tong_thong_bao,
    SUM(CASE WHEN da_doc = 1 THEN 1 ELSE 0 END) as da_doc,
    SUM(CASE WHEN da_doc = 0 THEN 1 ELSE 0 END) as chua_doc,
    SUM(CASE WHEN ngay_doc IS NOT NULL THEN 1 ELSE 0 END) as co_ngay_doc
FROM thong_bao;

-- =====================================================
-- ROLLBACK (Nếu cần)
-- =====================================================

-- Chỉ chạy nếu cần rollback
-- ALTER TABLE thong_bao DROP COLUMN ngay_doc;
