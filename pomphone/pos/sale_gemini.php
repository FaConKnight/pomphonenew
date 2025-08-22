<?php
// sale.php เวอร์ชัน UI มืออาชีพ + AJAX ค้นหาลูกค้า พร้อมระบบคืนสินค้าแบบ Modal

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 11) {
    echo "<script>alert('ไม่มีสิทธิ์เข้าถึงหน้าขายสินค้า');window.location='../index.php';</script>";
    exit;
}
?>

<main>
<div class="page-container">
  <div class="main-content" style="padding-top: 10px;">
    <div class="container-fluid">
        <h3 class="mt-4">ระบบขายสินค้า (POS)</h3>
        <div class="row mt-3">
            <div class="col-md-7">
                <div class="form-group">
                    <label>ค้นหาสินค้า (ชื่อ / บาร์โค้ด / IMEI):</label>
                    <input type="text" class="form-control" id="search_input" placeholder="พิมพ์เพื่อค้นหา..." autofocus>
                    <div id="product_list" class="list-group mt-2 position-absolute" style="z-index: 1000;"></div>
                </div>

                <div id="return_summary_area" class="mt-3"></div>

                <h5 class="mt-4">ตะกร้าสินค้า (รายการขาย)</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered" id="cart_table">
                        <thead>
                            <tr>
                                <th>ชื่อสินค้า</th>
                                <th>ราคา/ชิ้น</th>
                                <th>จำนวน</th>
                                <th>รวม</th>
                                <th>ลบ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card p-3 shadow-sm">
                    <div class="form-group">
                        <label>ลูกค้า:</label>
                        <div class="input-group">
                          <input type="text" class="form-control" id="customer_input" placeholder="ค้นหาลูกค้า (ไม่ระบุ = เงินสด)">
                          <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#returnModal" title="รับคืนสินค้า"><i class="fa fa-undo"></i> รับคืน</button>
                          </div>
                        </div>
                        <input type="hidden" id="customer_id">
                        <div id="customer_list" class="list-group mt-1 position-absolute" style="z-index: 999;"></div>
                    </div>
                    <hr>
                    <table class="table table-borderless table-sm">
                        <tr><td>ยอดรวมสินค้าใหม่</td><td class="text-right" id="purchase_total">0.00</td></tr>
                        <tr><td>ยอดรวมรับคืน</td><td class="text-right text-danger" id="refund_total">0.00</td></tr>
                        <tr><td>ส่วนลด (บาท)</td><td class="text-right"><input type="number" class="form-control form-control-sm text-right" id="discount_input" value="0" min="0"></td></tr>
                        <tr class="font-weight-bold h5">
                            <td id="final_amount_label">ยอดสุทธิ</td>
                            <td class="text-right" id="final_amount">0.00</td>
                        </tr>
                    </table>
                    <hr>
                    <h5 class="mt-2">ช่องทางชำระเงิน</h5>
                     <div class="form-row">
                        <div class="form-group col-md-6"><label>เงินสด</label><input type="number" class="form-control payment-input" id="pay_cash" placeholder="0"></div>
                        <div class="form-group col-md-6"><label>เงินโอน</label><input type="number" class="form-control payment-input" id="pay_transfer" placeholder="0"></div>
                    </div>
                    <div class="font-weight-bold text-danger h5 text-right">
                        คงเหลือ/ทอน: <span id="balance_due">0.00</span>
                    </div>

                    <button class="btn btn-success btn-block mt-3" id="confirm_exchange_btn">
                        <i class="fa fa-check-circle"></i> ยืนยันและบันทึกรายการ
                    </button>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
</main>

<div class="modal fade" id="returnModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">รับคืนสินค้า</h5></div>
      <div class="modal-body">
        <div class="input-group mb-3">
          <input type="text" id="search_receipt_no" class="form-control" placeholder="ค้นหาด้วยเลขที่ใบเสร็จ RC...">
          <div class="input-group-append">
            <button class="btn btn-primary" type="button" id="search_receipt_btn">ค้นหา</button>
          </div>
        </div>
        <div id="return_items_area" class="table-responsive"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
        <button type="button" class="btn btn-primary" id="add_return_items_btn">เพิ่มรายการคืนที่เลือก</button>
      </div>
    </div>
  </div>
</div>
<script src="../js/sale_pos.js?v=<?= time() ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
