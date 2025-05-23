<?php
// sale_list.php - หน้าดูใบเสร็จประจำวัน + Reprint

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

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
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายการใบเสร็จวันนี้</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <script src="../assets/js/jquery.min.js"></script>
</head>

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
                <td><?= date('H:i', strtotime($row['sale_time'])) ?></td>
                <td><?= htmlspecialchars($row['receipt_no']) ?></td>
                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                <td><?= number_format($row['final_amount'], 2) ?> บาท</td>
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
                <textarea class="form-control" name="reason" id="reprint_reason" required></textarea>
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
<?php include_once("../partials/footer.php"); ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){
    $('.reprint-btn').click(function(){
        let id = $(this).data('id');
        let receipt = $(this).data('receipt');
        $('#modal_sale_id').val(id);
        $('#reprint_reason').val('');
        $('#reprintModal').modal('show');
    });

    $('#reprintForm').submit(function(e){
        e.preventDefault();
        let sale_id = $('#modal_sale_id').val();
        let reason = $('#reprint_reason').val().trim();
        if (!reason) return alert("กรุณาระบุเหตุผล");

        $.post("log_reprint.php", {sale_id: sale_id, reason: reason}, function(res){
            window.open("receipt.php?sale_id=" + sale_id + "&reprint=1", "_blank");
            $('#reprintModal').modal('hide');
        });
    });
});
</script>
