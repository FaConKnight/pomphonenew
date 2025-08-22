<?php
// sale_list.php - หน้าดูใบเสร็จประจำวัน + Reprint

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    http_response_code(403);
    exit("Unauthorized");
}

$today = date('Y-m-d');
//$today = '2025-05-22';
$stmt = $pdo->prepare("SELECT s.id, s.receipt_no, s.sale_time, s.final_amount, e.emd_name AS employee_name FROM sale s
    LEFT JOIN employee_details e ON s.employee_id = e.emd_id
    WHERE DATE(s.sale_time) = ? ORDER BY s.id DESC");
$stmt->execute([$today]);
$sales = $stmt->fetchAll();
?>
<main>
<h3>รายการใบเสร็จประจำวันที่ <?= date('d/m/Y') ?></h3>
<table class="table table-bordered table-sm mt-3">
    <thead>
        <tr>
            <th>เวลา</th>
            <th>เลขที่ใบเสร็จ</th>
            <th>พนักงาน</th>
            <th>ยอดสุทธิ</th>
            <th>พิมพ์ซ้ำ</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($sales as $row): ?>
            <tr>
                <td><?= safe_date($row['sale_time']) ?></td>
                <td><?= safe_text($row['receipt_no']) ?></td>
                <td><?= safe_text($row['employee_name']) ?></td>
                <td><?= number_format($row['final_amount']?? 0, 2) ?> บาท</td>
                <td>
                    <button class="btn btn-sm btn-warning reprint-btn" data-id="<?= $row['id'] ?>" data-receipt="<?= $row['receipt_no'] ?>">Reprint</button>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal สำหรับใส่เหตุผล -->
<div class="modal" id="reprintModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">พิมพ์ใบเสร็จซ้ำ</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="reprintForm">
            <input type="hidden" name="sale_id" id="modal_sale_id">

            <div class="form-group">
                <label>เหตุผลในการพิมพ์ซ้ำ:</label>
                <select class="form-control" name="reason" id="reprint_reason" required onchange="toggleOtherReason(this)">
                    <option value="">-- เลือกเหตุผล --</option>
                    <option value="เครื่องพิมพ์มีปัญหา">เครื่องพิมพ์มีปัญหา</option>
                    <option value="ลูกค้าทำใบเสร็จหาย">ลูกค้าทำใบเสร็จหาย</option>
                    <option value="ใบเสร็จไม่สมบูรณ์">ใบเสร็จไม่สมบูรณ์</option>
                    <option value="ลูกค้าร้องขอสำเนา">ลูกค้าร้องขอสำเนา</option>
                    <option value="อื่นๆ">อื่น ๆ (ระบุ)</option>
                </select>
            </div>

            <div class="form-group d-none" id="other_reason_group">
                <label>ระบุเหตุผลอื่น ๆ:</label>
                <input type="text" class="form-control" id="other_reason_input" placeholder="พิมพ์เหตุผล..." />
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="submit" form="reprintForm" class="btn btn-primary">ยืนยัน</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
      </div>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script>
function toggleOtherReason(select) {
    if (select.value === 'อื่นๆ') {
        $('#other_reason_group').removeClass('d-none');
    } else {
        $('#other_reason_group').addClass('d-none');
    }
}

$(document).ready(function(){
    $('.reprint-btn').click(function(){
        $('#modal_sale_id').val($(this).data('id'));
        $('#reprint_reason').val('');
        $('#other_reason_group').addClass('d-none');
        $('#other_reason_input').val('');
        $('#reprintModal').modal('show');
    });

    $('#reprintForm').submit(function(e){
        e.preventDefault();
        let sale_id = $('#modal_sale_id').val();
        let reason = $('#reprint_reason').val();
        if (!reason) return alert("กรุณาเลือกเหตุผล");

        if (reason === 'อื่นๆ') {
            reason = $('#other_reason_input').val().trim();
            if (!reason) return alert("กรุณาระบุเหตุผลเพิ่มเติม");
        }

        $.post("log_reprint.php", {sale_id: sale_id, reason: reason}, function(res){
            window.open("receipt.php?sale_id=" + sale_id + "&reprint=1", "_blank");
            $('#reprintModal').modal('hide');
        });
    });
});
</script>
