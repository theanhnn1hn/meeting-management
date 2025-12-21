-- =====================================================
-- HỆ THỐNG QUẢN LÝ KỲ HỌP UBND TỈNH
-- Phiên bản 5.0 - Final Optimized
-- BỎ trường linh_vuc - Workflow đơn giản
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. BẢNG CƠ QUAN (SỞ, NGÀNH)
-- =====================================================
CREATE TABLE `co_quan` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ma_co_quan` varchar(50) NOT NULL,
  `ten_co_quan` varchar(255) NOT NULL,
  `thu_tu` int DEFAULT 0,
  `trang_thai` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_co_quan` (`ma_co_quan`),
  KEY `idx_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. BẢNG PHÒNG BAN (VĂN PHÒNG UBND)
-- =====================================================
CREATE TABLE `phong_ban` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ma_phong` varchar(50) NOT NULL,
  `ten_phong` varchar(255) NOT NULL,
  `truong_phong_id` int DEFAULT NULL COMMENT 'ID User là Trưởng phòng',
  `pho_phong_ids` text COMMENT 'JSON array ID các Phó phòng [1,2,3]',
  `thu_tu` int DEFAULT 0,
  `trang_thai` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ma_phong` (`ma_phong`),
  KEY `idx_truong_phong` (`truong_phong_id`),
  KEY `idx_trang_thai` (`trang_thai`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. BẢNG NGƯỜI DÙNG
-- =====================================================
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ho_ten` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `dien_thoai` varchar(20) DEFAULT NULL,
  `phong_ban_id` int DEFAULT NULL,
  `chuc_vu` enum('chanh_vp','pho_cvp','truong_phong','pho_phong','chuyen_vien') NOT NULL,
  `trang_thai` tinyint(1) DEFAULT 1,
  `avatar` varchar(255) DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_phong_ban` (`phong_ban_id`),
  KEY `idx_chuc_vu` (`chuc_vu`),
  KEY `idx_trang_thai` (`trang_thai`),
  CONSTRAINT `fk_users_phong_ban` FOREIGN KEY (`phong_ban_id`) REFERENCES `phong_ban` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. BẢNG KỲ HỌP
-- =====================================================
CREATE TABLE `ky_hop` (
  `id` int NOT NULL AUTO_INCREMENT,
  `so_ky_hop` varchar(50) NOT NULL COMMENT 'VD: 01/2025',
  `ten_ky_hop` varchar(255) NOT NULL,
  `ngay_hop` date NOT NULL,
  `dia_diem` varchar(255) DEFAULT NULL,
  `chu_tri` varchar(255) DEFAULT NULL COMMENT 'Chủ tịch UBND tỉnh',
  `deadline_dang_ky` date DEFAULT NULL COMMENT 'Hạn đăng ký nội dung',
  `deadline_phe_duyet` date DEFAULT NULL COMMENT 'Hạn phê duyệt',
  `deadline_hoan_thien` date DEFAULT NULL COMMENT 'Hạn hoàn thiện hồ sơ',
  `trang_thai` enum('du_thao','dang_xu_ly','sap_dien_ra','da_dien_ra','huy') DEFAULT 'du_thao',
  `ghi_chu` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_so_ky_hop` (`so_ky_hop`),
  KEY `idx_ngay_hop` (`ngay_hop`),
  KEY `idx_trang_thai` (`trang_thai`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_deadline_dang_ky` (`deadline_dang_ky`),
  KEY `idx_deadline_phe_duyet` (`deadline_phe_duyet`),
  KEY `idx_deadline_hoan_thien` (`deadline_hoan_thien`),
  CONSTRAINT `fk_ky_hop_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. BẢNG NỘI DUNG KỲ HỌP
-- =====================================================
CREATE TABLE `noi_dung` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ky_hop_id` int NOT NULL,
  `tieu_de` varchar(500) NOT NULL,
  `tom_tat` text,
  `co_quan_trinh_id` int DEFAULT NULL COMMENT 'Cơ quan trình (Sở, ngành)',
  `nguoi_trinh_id` int NOT NULL COMMENT 'Chuyên viên đăng ký',
  `nguoi_phe_duyet_id` int DEFAULT NULL COMMENT 'Chánh VP hoặc Phó CVP được chọn',
  `phong_ban_id` int DEFAULT NULL COMMENT 'Phòng ban của người trình',
  `stt` int DEFAULT 999 COMMENT 'Số thứ tự trong chương trình',
  `trang_thai` enum('cho_duyet','da_duyet','tu_choi','dang_xu_ly','hoan_thanh') DEFAULT 'cho_duyet',
  `tien_do` int DEFAULT 0 COMMENT '% hoàn thành (0-100)',
  `ly_do_tu_choi` text,
  `ngay_phe_duyet` datetime DEFAULT NULL,
  `ghi_chu` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ky_hop` (`ky_hop_id`),
  KEY `idx_nguoi_trinh` (`nguoi_trinh_id`),
  KEY `idx_nguoi_phe_duyet` (`nguoi_phe_duyet_id`),
  KEY `idx_phong_ban` (`phong_ban_id`),
  KEY `idx_co_quan_trinh` (`co_quan_trinh_id`),
  KEY `idx_trang_thai` (`trang_thai`),
  KEY `idx_stt` (`stt`),
  CONSTRAINT `fk_noi_dung_ky_hop` FOREIGN KEY (`ky_hop_id`) REFERENCES `ky_hop` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_noi_dung_nguoi_trinh` FOREIGN KEY (`nguoi_trinh_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_noi_dung_nguoi_phe_duyet` FOREIGN KEY (`nguoi_phe_duyet_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_noi_dung_phong_ban` FOREIGN KEY (`phong_ban_id`) REFERENCES `phong_ban` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_noi_dung_co_quan` FOREIGN KEY (`co_quan_trinh_id`) REFERENCES `co_quan` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. BẢNG CHECKLIST CÔNG VIỆC
-- =====================================================
CREATE TABLE `checklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `noi_dung_id` int NOT NULL,
  `ten_cong_viec` varchar(500) NOT NULL,
  `thu_tu` int DEFAULT 0,
  `trang_thai` tinyint(1) DEFAULT 0 COMMENT '0=chưa, 1=đã hoàn thành',
  `nguoi_thuc_hien_id` int DEFAULT NULL,
  `ngay_hoan_thanh` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_noi_dung` (`noi_dung_id`),
  KEY `idx_trang_thai` (`trang_thai`),
  CONSTRAINT `fk_checklist_noi_dung` FOREIGN KEY (`noi_dung_id`) REFERENCES `noi_dung` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. BẢNG TÀI LIỆU
-- =====================================================
CREATE TABLE `tai_lieu` (
  `id` int NOT NULL AUTO_INCREMENT,
  `noi_dung_id` int NOT NULL,
  `ten_file` varchar(255) NOT NULL,
  `duong_dan` varchar(500) NOT NULL,
  `kich_thuoc` bigint DEFAULT NULL COMMENT 'Bytes',
  `loai_file` varchar(50) DEFAULT NULL COMMENT 'pdf, docx...',
  `mo_ta` text,
  `uploaded_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_noi_dung` (`noi_dung_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  CONSTRAINT `fk_tai_lieu_noi_dung` FOREIGN KEY (`noi_dung_id`) REFERENCES `noi_dung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tai_lieu_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. BẢNG COMMENTS (GÓP Ý / ĐỐC VIỆC)
-- =====================================================
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `noi_dung_id` int NOT NULL,
  `user_id` int NOT NULL,
  `noi_dung_comment` text NOT NULL,
  `loai` enum('gop_y','doc_viec','yeu_cau_sua') DEFAULT 'gop_y',
  `parent_id` int DEFAULT NULL COMMENT 'ID comment cha (reply)',
  `is_private` tinyint(1) DEFAULT 0 COMMENT '1=chỉ Lãnh đạo VP thấy',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_noi_dung` (`noi_dung_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_comments_noi_dung` FOREIGN KEY (`noi_dung_id`) REFERENCES `noi_dung` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. BẢNG THÔNG BÁO
-- =====================================================
CREATE TABLE `thong_bao` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'Người nhận',
  `tieu_de` varchar(500) NOT NULL,
  `noi_dung` text,
  `loai` enum('dang_ky','phe_duyet','tu_choi','comment','doc_viec','deadline_t7','deadline_t3','deadline_t1','qua_han','hoan_thanh','cap_nhat') NOT NULL,
  `lien_ket` varchar(500) DEFAULT NULL COMMENT 'URL liên quan',
  `da_doc` tinyint(1) DEFAULT 0,
  `noi_dung_id` int DEFAULT NULL COMMENT 'Liên kết đến nội dung',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_da_doc` (`da_doc`),
  KEY `idx_loai` (`loai`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_noi_dung` (`noi_dung_id`),
  CONSTRAINT `fk_thong_bao_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_thong_bao_noi_dung` FOREIGN KEY (`noi_dung_id`) REFERENCES `noi_dung` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 10. BẢNG TEMPLATE CHECKLIST
-- =====================================================
CREATE TABLE `template_checklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ten_template` varchar(255) NOT NULL,
  `mo_ta` text,
  `danh_sach_cong_viec` text NOT NULL COMMENT 'JSON array công việc',
  `trang_thai` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 11. BẢNG ĐÁNH GIÁ CÁN BỘ
-- =====================================================
CREATE TABLE `danh_gia_can_bo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `ky_hop_id` int NOT NULL,
  `so_luong_noi_dung` int DEFAULT 0 COMMENT 'Số nội dung đã trình',
  `diem_dung_han` decimal(5,2) DEFAULT 0.00 COMMENT 'Điểm % hoàn thành đúng hạn',
  `diem_chat_luong` decimal(5,2) DEFAULT 0.00 COMMENT 'Điểm % được duyệt ngay (không bị từ chối)',
  `so_lan_doc_viec` int DEFAULT 0 COMMENT 'Số lần bị đốc việc',
  `diem_phoi_hop` decimal(5,2) DEFAULT 0.00 COMMENT 'Điểm phối hợp (ít comment yêu cầu sửa)',
  `tong_diem` decimal(5,2) DEFAULT 0.00,
  `xep_loai` enum('xuat_sac','tot','kha','trung_binh','yeu') DEFAULT NULL,
  `ghi_chu` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_ky_hop` (`user_id`,`ky_hop_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_ky_hop` (`ky_hop_id`),
  KEY `idx_xep_loai` (`xep_loai`),
  CONSTRAINT `fk_danh_gia_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_danh_gia_ky_hop` FOREIGN KEY (`ky_hop_id`) REFERENCES `ky_hop` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 12. BẢNG ACTIVITY LOG
-- =====================================================
CREATE TABLE `activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `hanh_dong` varchar(255) NOT NULL,
  `bang` varchar(50) DEFAULT NULL,
  `ban_ghi_id` int DEFAULT NULL,
  `chi_tiet` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_bang` (`bang`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 13. BẢNG CẤU HÌNH HỆ THỐNG
-- =====================================================
CREATE TABLE `cau_hinh` (
  `id` int NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) NOT NULL,
  `value` text,
  `mo_ta` varchar(500) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- DATA MẪU - ADMIN USER
-- =====================================================
INSERT INTO `users` (`username`, `password`, `ho_ten`, `email`, `chuc_vu`, `trang_thai`) 
VALUES ('chanh.vp', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Chánh Văn phòng', 'chanh.vp@province.gov.vn', 'chanh_vp', 1);
-- Password: admin123

-- =====================================================
-- TỐI ƯU INDEXES
-- =====================================================
-- Đã tạo 20+ indexes trong các bảng trên
-- Bao gồm: Primary keys, Foreign keys, Unique keys, và Performance indexes

SET FOREIGN_KEY_CHECKS = 1;
