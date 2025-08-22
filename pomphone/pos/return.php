<?php
// return.php - คืนสินค้าโดยอิงจากเลขใบเสร็จ (receipt_no)

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    echo "<script>alert('ไม่มีสิทธิ์เข้าถึงหน้าคืนสินค้า');window.location='../index.php';</script>";
    exit;
}
?>

<main>
<div class="page-container">
  <div class="main-content">
    <div class="container-fluid">
        <h3 class="mt-4">ระบบคืนสินค้า</h3>
        <div class="card p-4 shadow-sm">
            <div class="form-group">
                <label>เลขใบเสร็จ (Receipt No):</label>
                <input type="text" class="form-control" id="receipt_input" placeholder="กรอกเลขใบเสร็จ เช่น RC20250709001">
                <button class="btn btn-primary mt-2" id="load_items">โหลดรายการสินค้า</button>
            </div>
            <div id="product_section" style="display:none">
                <h5 class="mt-4">รายการสินค้าในบิล:</h5>
                <form id="return_form">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>เลือก</th>
                                <th>สินค้า</th>
                                <th>IMEI</th>
                                <th>ราคา</th>
                            </tr>
                        </thead>
                        <tbody id="item_list">
                        </tbody>
                    </table>

                    <div class="form-group">
                        <label>เหตุผลการคืน:</label>
                        <textarea class="form-control" id="return_reason" rows="2" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>วิธีการคืนเงิน:</label>
                        <select class="form-control" id="refund_method" required>
                            <option value="">-- เลือกวิธีคืนเงิน --</option>
                            <option value="cash">คืนเป็นเงินสด</option>
                            <option value="offset">หักยอดซื้อบิลใหม่</option>
                        </select>
                    </div>

                    <button class="btn btn-danger" type="submit">ยืนยันการคืนสินค้า</button>
                </form>
            </div>
        </div>
    </div>
  </div>
</div>
</main>

<script src="../js/return.js?v=<?= time() ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>