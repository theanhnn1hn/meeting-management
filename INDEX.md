# HỆ THỐNG QUẢN LÝ KỲ HỌP - COMPLETE PACKAGE

## 📦 NỘI DUNG PACKAGE

Package này bao gồm **10 FILES MỚI** để hoàn thiện hệ thống:

### 1. THÔNG BÁO (2 files)
- `thong-bao/danh-sach.php` - Quản lý danh sách thông báo
- `thong-bao/chi-tiet.php` - Chi tiết thông báo
- `api/notification-actions.php` - API xử lý thông báo

### 2. BÁO CÁO (5 files)
- `bao-cao/tien-do-ky-hop.php` - Báo cáo tiến độ kỳ họp (Charts + Excel)
- `bao-cao/tien-do-phong.php` - Báo cáo tiến độ phòng ban
- `bao-cao/doc-viec.php` - Báo cáo đốc việc
- `bao-cao/danh-gia-can-bo.php` - Đánh giá cán bộ với điểm số
- `bao-cao/thong-ke-chung.php` - Dashboard thống kê tổng hợp

### 3. BUG FIXES (3 files + README)
- `fixes/comment.php` - Fix SQL injection
- `fixes/upload-tai-lieu.php` - Fix file upload vulnerability
- `fixes/notification-ajax.php` - Fix missing CSRF
- `fixes/BUG_FIXES_README.md` - Hướng dẫn chi tiết

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### Bước 1: Extract files
```bash
unzip complete-system.zip
```

### Bước 2: Copy files vào hệ thống

#### A. Thông báo
```bash
cp thong-bao/*.php /path/to/your/project/thong-bao/
cp api/notification-actions.php /path/to/your/project/api/
```

#### B. Báo cáo
```bash
cp bao-cao/*.php /path/to/your/project/bao-cao/
```

#### C. Bug fixes (CRITICAL - Ưu tiên cao)
```bash
# Backup files cũ trước
cp api/comment.php api/comment.php.bak
cp api/upload-tai-lieu.php api/upload-tai-lieu.php.bak
cp api/notification-ajax.php api/notification-ajax.php.bak

# Deploy fixed versions
cp fixes/comment.php api/comment.php
cp fixes/upload-tai-lieu.php api/upload-tai-lieu.php
cp fixes/notification-ajax.php api/notification-ajax.php
```

### Bước 3: Update menu (includes/header.php)

Thêm vào menu:
```php
<!-- Thông báo -->
<li class="nav-item">
    <a class="nav-link" href="/thong-bao/danh-sach.php">
        <i class="fas fa-bell"></i> Thông báo
        <span class="badge bg-danger" id="notif-badge">0</span>
    </a>
</li>

<!-- Báo cáo -->
<li class="nav-item dropdown">
    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
        <i class="fas fa-chart-bar"></i> Báo cáo
    </a>
    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="/bao-cao/tien-do-ky-hop.php">Tiến độ kỳ họp</a></li>
        <li><a class="dropdown-item" href="/bao-cao/tien-do-phong.php">Tiến độ phòng ban</a></li>
        <li><a class="dropdown-item" href="/bao-cao/doc-viec.php">Đốc việc</a></li>
        <li><a class="dropdown-item" href="/bao-cao/danh-gia-can-bo.php">Đánh giá cán bộ</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="/bao-cao/thong-ke-chung.php">Thống kê chung</a></li>
    </ul>
</li>
```

### Bước 4: Update notification polling

Trong `includes/footer.php`:
```javascript
// Update notification count
setInterval(function() {
    $.get('/api/notification-ajax.php?action=count', function(data) {
        if (data.success) {
            $('#notif-badge').text(data.count);
            if (data.count > 0) {
                $('#notif-badge').removeClass('d-none');
            } else {
                $('#notif-badge').addClass('d-none');
            }
        }
    });
}, 30000); // Every 30 seconds
```

---

## ✨ TÍNH NĂNG MỚI

### 📧 Module Thông báo
- ✅ Danh sách thông báo với filters (loại, trạng thái)
- ✅ Pagination
- ✅ Mark as read (single/all)
- ✅ Delete (single/all read)
- ✅ Chi tiết thông báo
- ✅ Thống kê (tổng, chưa đọc, đã đọc)
- ✅ Thông báo liên quan

### 📊 Module Báo cáo

#### 1. Tiến độ kỳ họp
- Charts: Phân bố trạng thái (Doughnut), Tiến độ phòng ban (Bar)
- Stats cards: Tổng NĐ, Đã duyệt, Chờ duyệt, Từ chối
- DataTable: Danh sách chi tiết
- Export Excel với formatting

#### 2. Tiến độ phòng ban
- Filter: Phòng ban, From/To date
- Charts: Tiến độ theo cán bộ
- Top 5 cán bộ
- Danh sách nội dung

#### 3. Đốc việc
- Filter: Date range, Trạng thái
- Charts: Status distribution, Phòng ban ranking
- DataTable với trạng thái màu
- Export Excel

#### 4. Đánh giá cán bộ
- **Hệ thống điểm 100:**
  - Tiến độ: 40 điểm
  - Tỷ lệ duyệt: 30 điểm
  - Không quá hạn: 20 điểm
  - Ít đốc việc: 10 điểm
- **Xếp loại:** Xuất sắc (≥90), Tốt (80-89), Khá (70-79), TB (60-69), Yếu (<60)
- Bảng xếp hạng với medals (🥇🥈🥉)
- Export Excel

#### 5. Thống kê chung
- 8 stats cards
- 3 charts: Status, Monthly trend, Top phòng ban
- Top 10 cán bộ
- Recent activities (20 items)
- Print-friendly

---

## 🔒 BUG FIXES

### 1. SQL Injection (CRITICAL)
**File:** `fixes/comment.php`
- ✅ Whitelist validation cho `$loai`
- ✅ Whitelist validation cho `$trang_thai`

### 2. File Upload Vulnerability (CRITICAL)
**File:** `fixes/upload-tai-lieu.php`
- ✅ MIME type validation với finfo
- ✅ Whitelist extensions
- ✅ Safe filename generation
- ✅ Directory traversal protection
- ✅ Server-side file size limit (10MB)

### 3. Missing CSRF (MEDIUM)
**File:** `fixes/notification-ajax.php`
- ✅ Phân biệt GET (read-only) vs POST (state-changing)
- ✅ CSRF token required cho POST

**Chi tiết:** Xem `fixes/BUG_FIXES_README.md`

---

## 🧪 TESTING

### Test Thông báo
1. Tạo thông báo mới
2. Mark as read
3. Delete
4. Filter by loại/trạng thái
5. Pagination

### Test Báo cáo
1. Select kỳ họp → Xem charts
2. Export Excel → Kiểm tra formatting
3. Filter date range
4. Check calculations (tiến độ TB, điểm đánh giá)

### Test Bug Fixes
```bash
# Test SQL injection
curl -X POST api/comment.php \
  -d 'action=add&loai=invalid_type&...'
# Expected: Error "Loại comment không hợp lệ"

# Test file upload
curl -X POST api/upload-tai-lieu.php \
  -F 'file=@malicious.php.jpg' -F '...'
# Expected: Error "File type not allowed"

# Test CSRF
curl api/notification-ajax.php?action=mark_read&id=1
# Expected: Error "Method not allowed" (cần POST)
```

---

## 📋 DEPENDENCIES

Các files báo cáo yêu cầu:
- **PhpSpreadsheet:** `composer require phpoffice/phpspreadsheet`
- **Chart.js:** Đã include trong files (CDN)

---

## 🎨 UI COMPONENTS

Tất cả files sử dụng:
- Bootstrap 5
- Font Awesome icons
- DataTables (tiếng Việt)
- SweetAlert2
- Toastr
- Chart.js 3.9.1

---

## 📄 CẤU TRÚC THƯ MỤC SAU KHI CÀI

```
your-project/
├── api/
│   ├── comment.php              [UPDATED - Fixed SQL injection]
│   ├── upload-tai-lieu.php      [UPDATED - Fixed file upload]
│   ├── notification-ajax.php    [UPDATED - Fixed CSRF]
│   └── notification-actions.php [NEW]
├── thong-bao/
│   ├── danh-sach.php           [NEW]
│   └── chi-tiet.php            [NEW]
├── bao-cao/
│   ├── tien-do-ky-hop.php      [NEW]
│   ├── tien-do-phong.php       [NEW]
│   ├── doc-viec.php            [NEW]
│   ├── danh-gia-can-bo.php     [NEW]
│   └── thong-ke-chung.php      [NEW]
└── fixes/
    ├── comment.php
    ├── upload-tai-lieu.php
    ├── notification-ajax.php
    └── BUG_FIXES_README.md
```

---

## ⚠️ QUAN TRỌNG

1. **Backup trước khi deploy:** Luôn backup files cũ
2. **Test trên staging:** Test đầy đủ trước khi production
3. **Install Composer packages:** Cần PhpSpreadsheet cho export Excel
4. **Update menu:** Thêm links vào menu chính
5. **Permissions:** Đảm bảo upload directory có quyền write (755)

---

## 🔗 TÍCH HỢP

Các files mới tích hợp hoàn toàn với hệ thống hiện tại:
- Sử dụng cùng database schema
- Sử dụng cùng authentication
- Sử dụng cùng functions.php
- Responsive design giống hệt
- Cùng color scheme và branding

---

## 📞 HỖ TRỢ

Nếu gặp vấn đề:
1. Check logs: `/var/log/apache2/error.log`
2. Check permissions: `ls -la uploads/`
3. Check composer: `composer install`
4. Check database: Tất cả tables có đủ indexes chưa

---

**Version:** 1.0  
**Ngày:** 2025-12-22  
**Tình trạng:** Production Ready ✅
