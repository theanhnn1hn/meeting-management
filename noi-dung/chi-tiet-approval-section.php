<?php
/**
 * PHẦN PHÊ DUYỆT TRONG CHI-TIET.PHP - FIXED VERSION
 * 
 * Đây là đoạn code thay thế phần Approve/Reject trong chi-tiet.php
 * Sử dụng FORM POST thay vì GET link
 */
?>

<!-- Trong phần header actions của chi-tiet.php, thay thế đoạn button approve/reject -->

<?php if ($can_approve): ?>
    <!-- Form Phê duyệt -->
    <form method="POST" action="phe-duyet.php" style="display: inline;" id="formApprove">
        <?= csrf_field() ?>
        <input type="hidden" name="noi_dung_id" value="<?= $noi_dung_id ?>">
        <input type="hidden" name="action" value="approve">
        <button type="button" onclick="confirmApprove()" class="btn btn-success">
            <i class="bi bi-check-circle"></i> Phê duyệt
        </button>
    </form>
    
    <!-- Form Từ chối -->
    <button type="button" onclick="showRejectModal()" class="btn btn-danger">
        <i class="bi bi-x-circle"></i> Từ chối
    </button>
<?php endif; ?>

<!-- Modal Từ chối - thêm vào cuối file, trước các modal khác -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="phe-duyet.php" id="formReject">
                <?= csrf_field() ?>
                <input type="hidden" name="noi_dung_id" value="<?= $noi_dung_id ?>">
                <input type="hidden" name="action" value="reject">
                
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-x-circle"></i> Từ chối nội dung
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Bạn đang từ chối nội dung:</strong><br>
                        "<?= e($noi_dung['tieu_de']) ?>"
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Lý do từ chối <span class="text-danger">*</span>
                        </label>
                        <textarea 
                            name="ly_do" 
                            class="form-control" 
                            rows="4" 
                            required
                            placeholder="Nhập lý do từ chối cụ thể để người trình biết cách sửa đổi..."
                        ></textarea>
                        <small class="text-muted">
                            Lý do từ chối sẽ được gửi thông báo cho người trình và lưu vào hệ thống
                        </small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg"></i> Hủy
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-circle"></i> Xác nhận từ chối
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript - thêm vào phần $extra_js -->
<script>
// Modal instances
const rejectModal = new bootstrap.Modal('#rejectModal');

// Confirm approve
function confirmApprove() {
    Swal.fire({
        title: 'Xác nhận phê duyệt',
        html: '<p>Bạn xác nhận phê duyệt nội dung:</p><p class="fw-bold">"<?= e($noi_dung['tieu_de']) ?>"</p>',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="bi bi-check-circle"></i> Phê duyệt',
        cancelButtonText: '<i class="bi bi-x-lg"></i> Hủy',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit form
            document.getElementById('formApprove').submit();
        }
    });
}

// Show reject modal
function showRejectModal() {
    rejectModal.show();
}

// Validate reject form
document.getElementById('formReject')?.addEventListener('submit', function(e) {
    const lyDo = this.querySelector('textarea[name="ly_do"]').value.trim();
    
    if (lyDo.length < 10) {
        e.preventDefault();
        Swal.fire({
            title: 'Lý do quá ngắn',
            text: 'Vui lòng nhập lý do từ chối cụ thể (ít nhất 10 ký tự)',
            icon: 'warning',
            confirmButtonText: 'OK'
        });
        return false;
    }
});
</script>
