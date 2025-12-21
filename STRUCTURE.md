# CẤU TRÚC DỰ ÁN & HƯỚNG DẪN MỞ RỘNG

## ✅ CÁC FILE ĐÃ TẠO (CORE SYSTEM)

### 1. Database
- ✅ `database/schema.sql` - Schema đầy đủ 13 bảng + 20+ indexes

### 2. Config Files
- ✅ `config/database.php` - Kết nối PDO
- ✅ `config/config.php` - Cấu hình chung
- ✅ `config/constants.php` - Hằng số, roles, permissions

### 3. Includes (Template System)
- ✅ `includes/functions.php` - Functions dùng chung (300+ lines)
- ✅ `includes/header.php` - Header + navbar + notification bell
- ✅ `includes/footer.php` - Footer + all JS libraries
- ✅ `includes/sidebar.php` - Sidebar menu theo role
- ✅ `includes/notification-ajax.php` - AJAX handler cho notification

### 4. Authentication
- ✅ `auth/login.php` - Trang đăng nhập (UI đẹp)
- ✅ `auth/logout.php` - Đăng xuất + log activity
- ✅ `auth/check_auth.php` - Middleware authentication
- ✅ `auth/profile.php` - (CẦN TẠO - copy từ template bên dưới)

### 5. Dashboard (5 vai trò)
- ✅ `dashboard/index.php` - Router theo role
- ✅ `dashboard/chanh-vp.php` - Dashboard Chánh VP (tiến độ tổng thể)
- ✅ `dashboard/pho-cvp.php` - Dashboard Phó CVP (chờ duyệt)
- ✅ `dashboard/truong-phong.php` - Dashboard Trưởng phòng (đốc việc)
- ✅ `dashboard/pho-phong.php` - Dashboard Phó phòng
- ✅ `dashboard/chuyen-vien.php` - Dashboard Chuyên viên (widget ưu tiên)

### 6. Cron Jobs
- ✅ `cron/check-deadline-warnings.php` - Cảnh báo deadline (chạy mỗi giờ)

### 7. Others
- ✅ `index.php` - Trang chủ redirect
- ✅ `.htaccess` - Apache config + security
- ✅ `install.php` - Hướng dẫn cài đặt
- ✅ `README.md` - Tài liệu đầy đủ

---

## 📝 CÁC FILE CẦN TẠO THEO TEMPLATE

Các file dưới đây có cấu trúc tương tự và có thể tạo dễ dàng bằng cách sao chép template:

### Module: ky-hop/ (Quản lý Kỳ họp)

#### Template cơ bản cho CRUD:

```php
<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_role([ROLE_CHANH_VP]); // Phân quyền

$page_title = 'Tên trang';

// Logic xử lý ở đây
// - GET: Hiển thị form/data
// - POST: Xử lý submit với CSRF check

include __DIR__ . '/../includes/header.php';
?>

<!-- HTML content -->
<div class="row">
    <div class="col-12">
        <h2><?= $page_title ?></h2>
    </div>
</div>

<div class="card">
    <!-- Card content -->
</div>

<?php
// Extra JS nếu cần
$extra_js = <<<JS
<script>
// Custom JS
</script>
JS;

include __DIR__ . '/../includes/footer.php';
?>
```

#### Danh sách file cần tạo:

1. **ky-hop/danh-sach.php**
   - DataTable list kỳ họp
   - Buttons: Tạo mới, Sửa, Xóa, Xem chi tiết
   - Export Excel
   
2. **ky-hop/tao-moi.php**
   - Form: tên kỳ họp, ngày họp, deadline (3 cấp)
   - Validate: ngày hợp lệ, deadline logic
   - Thông báo tự động khi tạo
   
3. **ky-hop/chi-tiet.php**
   - Thông tin kỳ họp
   - Danh sách nội dung của kỳ họp
   - Tiến độ tổng thể (chart)
   - Buttons: Sửa, Xóa, Sắp xếp chương trình
   
4. **ky-hop/sua.php**
   - Form edit (giống tạo mới)
   - Validate permissions
   
5. **ky-hop/xoa.php**
   - Confirm delete (SweetAlert2)
   - Validate: Không xóa nếu có nội dung
   
6. **ky-hop/sap-xep-chuong-trinh.php**
   - Drag & drop với Sortable.js
   - Update STT nội dung
   - Auto-save

---

### Module: noi-dung/ (Quản lý Nội dung)

1. **noi-dung/danh-sach.php** (ĐÃ CÓ TEMPLATE BÊN DƯỚI)
   - DataTable với filter
   - Phân quyền xem
   
2. **noi-dung/dang-ky.php**
   ```php
   // Form đăng ký
   - Select kỳ họp
   - Input tiêu đề, tóm tắt
   - Select cơ quan trình
   - Select người phê duyệt (Chánh VP + 4 Phó CVP)
   - KHÔNG có dropdown lĩnh vực
   
   // Sau khi submit:
   - Tạo thông báo cho người phê duyệt
   - Tạo thông báo cho Trưởng/Phó phòng
   ```
   
3. **noi-dung/chi-tiet.php**
   ```php
   // Hiển thị:
   - Thông tin nội dung
   - Checklist công việc (progress bar)
   - Tài liệu đính kèm
   - Comments (phân loại: góp ý, đốc việc, yêu cầu sửa)
   - Timeline activities
   
   // Actions:
   - Phê duyệt (nếu có quyền)
   - Từ chối (nếu có quyền)
   - Cập nhật tiến độ (nếu là owner)
   - Upload tài liệu (nếu là owner)
   - Comment/Đốc việc (theo quyền)
   ```
   
4. **noi-dung/sua.php**
   - CHỈ cho phép sửa khi trạng thái = 'cho_duyet'
   - Validate permissions
   
5. **noi-dung/phe-duyet.php**
   ```php
   // POST handler
   - Validate: user có quyền duyệt
   - Update trạng thái = 'da_duyet'
   - Tạo thông báo cho người trình
   - GỬI EMAIL (nếu enabled)
   - Log activity
   ```
   
6. **noi-dung/cap-nhat-tien-do.php**
   ```php
   // AJAX/POST update
   - Update % tiến độ
   - Update checklist items
   - Tạo thông báo nếu hoàn thành
   ```
   
7. **noi-dung/upload-tai-lieu.php**
   ```php
   // File upload handler
   - Validate file type, size
   - Upload to /uploads/tai-lieu/
   - Insert vào bảng tai_lieu
   - Return JSON success/error
   ```
   
8. **noi-dung/comment.php**
   ```php
   // POST create comment
   - Validate user permissions
   - Insert comment
   - Tạo thông báo cho người liên quan
   ```

---

### Module: thong-bao/ (Thông báo)

1. **thong-bao/danh-sach.php**
   ```php
   // List notifications
   - DataTable filter theo loại, đã đọc/chưa đọc
   - Mark all as read button
   - Click vào thông báo -> mark as read -> redirect
   ```
   
2. **thong-bao/chi-tiet.php**
   - Hiển thị chi tiết
   - Mark as read
   - Link đến nội dung liên quan

---

### Module: bao-cao/ (Báo cáo)

1. **bao-cao/tien-do-ky-hop.php**
   ```php
   // Báo cáo theo kỳ họp
   - Select kỳ họp
   - Chart.js: Pie chart trạng thái
   - Table: Danh sách nội dung + tiến độ
   - Export Excel
   ```
   
2. **bao-cao/tien-do-phong.php**
   ```php
   // Báo cáo theo phòng
   - Chart: So sánh tiến độ các phòng
   - Table: Chi tiết từng phòng
   ```
   
3. **bao-cao/doc-viec.php**
   ```php
   // Báo cáo đốc việc
   - Danh sách comments loại 'doc_viec'
   - Filter theo phòng, kỳ họp
   - Thống kê số lần đốc việc
   ```
   
4. **bao-cao/danh-gia-can-bo.php**
   ```php
   // Module đánh giá cán bộ
   - Select kỳ họp
   - Tính điểm tự động (5 chỉ số)
   - DataTable xếp hạng
   - Chart so sánh
   - Export Excel
   ```
   
5. **bao-cao/thong-ke-chung.php**
   ```php
   // Dashboard thống kê
   - Cards: Tổng số kỳ họp, nội dung, user...
   - Charts: 
     + Line chart: Nội dung theo tháng
     + Bar chart: Top phòng hoạt động tốt
     + Pie chart: Phân bố trạng thái
   ```

---

### Module: quan-ly/nguoi-dung/ (Quản lý User)

1. **quan-ly/nguoi-dung/danh-sach.php**
   ```php
   // CRUD users
   - DataTable
   - Filter: chức vụ, phòng, trạng thái
   - Buttons: Tạo, Sửa, Xóa, Đổi MK
   ```
   
2. **quan-ly/nguoi-dung/tao-moi.php**
   ```php
   // Form:
   - Username, password, họ tên, email
   - Select phòng ban (required)
   - Select chức vụ
   - Validate: unique username, email
   - Hash password
   ```
   
3. **quan-ly/nguoi-dung/sua.php**
   ```php
   // Phân quyền 3 cấp:
   - Chánh VP: Sửa tất cả
   - Trưởng phòng: Sửa user trong phòng
   - Chuyên viên: Sửa thông tin cá nhân (không đổi role)
   ```
   
4. **quan-ly/nguoi-dung/xoa.php**
   ```php
   // Validate:
   - Kiểm tra user có công việc không
   - Nếu có -> yêu cầu chuyển sang user khác
   - Soft delete hoặc hard delete
   ```
   
5. **quan-ly/nguoi-dung/doi-mat-khau.php**
   ```php
   // Đổi password
   - Validate old password (nếu user đổi của mình)
   - Hash new password
   ```

---

### Module: quan-ly/phong-ban/ (Quản lý Phòng ban)

1. **quan-ly/phong-ban/danh-sach.php**
   - CRUD phòng ban
   - Hiển thị Trưởng/Phó phòng
   
2. **quan-ly/phong-ban/gan-truong-phong.php**
   ```php
   // Gắn Trưởng/Phó phòng
   - Validate: 1 phòng chỉ 1 Trưởng
   - Multiple Phó phòng (JSON array)
   - Update phong_ban table
   ```

---

### Module: chuong-trinh/ (Chương trình kỳ họp)

1. **chuong-trinh/xem.php**
   ```php
   // Xem chương trình
   - Select kỳ họp
   - Hiển thị nội dung theo STT
   - Button: Xuất PDF
   ```
   
2. **chuong-trinh/xuat-pdf.php**
   ```php
   // Export PDF với jsPDF
   - Format chương trình chuẩn
   - Include logo, header
   - Table nội dung
   ```

---

## 🔧 FUNCTIONS CÓ SẴN (includes/functions.php)

Bạn có thể sử dụng các functions sau:

### Security
- `generate_csrf_token()` - Tạo CSRF token
- `verify_csrf_token($token)` - Verify token
- `csrf_field()` - Output hidden input CSRF
- `e($string)` - Escape HTML (XSS protection)
- `sanitize($input)` - Sanitize input

### Notifications
- `create_notification($user_id, $title, $content, $type, $link, $noi_dung_id)`
- `notify_users($user_ids[], ...)` - Gửi nhiều user
- `count_unread_notifications($user_id)`
- `get_notifications($user_id, $limit)`

### Users & Roles
- `get_current_user()` - Lấy thông tin user hiện tại
- `get_user_by_id($id)`
- `get_users_by_chuc_vu($chuc_vu)`
- `get_users_by_phong($phong_id)`

### Permissions
- `has_permission($permission)` - Check quyền
- `require_permission($permission)` - Require + redirect nếu không đủ quyền
- `is_owner($noi_dung_id)` - Check owner

### Date/Time
- `format_date($date)` - Format dd/mm/yyyy
- `format_datetime($datetime)` - Format dd/mm/yyyy HH:ii
- `days_until($date)` - Tính số ngày còn lại
- `time_ago($datetime)` - "5 phút trước"

### Helpers
- `status_badge($status, $type)` - Badge HTML
- `get_tien_do_color($percent)` - Màu progress bar
- `log_activity($action, $table, $id, $details)` - Log
- `upload_file($file, $noi_dung_id)` - Upload file
- `redirect($url, $message, $type)` - Redirect với flash message

---

## 📊 DATABASE TABLES

Tất cả tables đã được tạo với indexes tối ưu:

1. `co_quan` - Cơ quan (Sở, ngành)
2. `phong_ban` - Phòng ban
3. `users` - Người dùng
4. `ky_hop` - Kỳ họp
5. `noi_dung` - Nội dung kỳ họp
6. `checklist` - Checklist công việc
7. `tai_lieu` - Tài liệu đính kèm
8. `comments` - Comments/Đốc việc
9. `thong_bao` - Thông báo
10. `template_checklist` - Template
11. `danh_gia_can_bo` - Đánh giá cán bộ
12. `activity_log` - Log hoạt động
13. `cau_hinh` - Cấu hình hệ thống

---

## 🎨 UI/UX COMPONENTS

### Bootstrap Classes có sẵn
- Cards: `.card`, `.card-header`, `.card-body`
- Buttons: `.btn-primary`, `.btn-success`, `.btn-danger`
- Badges: `.badge bg-{color}`
- Progress: `.progress`, `.progress-bar bg-{color}`
- Alerts: `.alert alert-{type}`

### DataTables (đã config tiếng Việt)
```javascript
$('#myTable').DataTable({
    order: [[0, 'desc']],
    pageLength: 20,
    dom: 'Bfrtip',
    buttons: ['excel']
});
```

### SweetAlert2 (confirm delete)
```javascript
confirmDelete('Bạn chắc chắn muốn xóa?').then((result) => {
    if (result.isConfirmed) {
        // Delete action
    }
});
```

### Flatpickr (date picker)
```javascript
flatpickr("#datefield", {
    dateFormat: "d/m/Y",
    locale: "vn"
});
```

---

## ✅ CHECKLIST KHI TẠO FILE MỚI

1. [ ] Include `check_auth.php` đầu tiên
2. [ ] Include `functions.php`
3. [ ] Check permissions với `require_role()`
4. [ ] Set `$page_title`
5. [ ] Verify CSRF token khi POST
6. [ ] Sanitize inputs với `e()` và `sanitize()`
7. [ ] Use prepared statements (PDO)
8. [ ] Log activity quan trọng
9. [ ] Tạo thông báo khi cần
10. [ ] Include `header.php` và `footer.php`
11. [ ] Set `$extra_js` nếu có JS riêng
12. [ ] Test responsive trên mobile

---

## 🚀 DEPLOYMENT CHECKLIST

1. [ ] Import database schema
2. [ ] Config database connection
3. [ ] Set BASE_URL
4. [ ] Set upload permissions (777)
5. [ ] Setup cron job
6. [ ] Test đăng nhập
7. [ ] Test notification system
8. [ ] Test upload file
9. [ ] Xóa/đổi tên install.php
10. [ ] Enable HTTPS trong production

---

**Lưu ý:** Tất cả các file template đều tuân theo cùng một pattern để dễ maintain và mở rộng.
