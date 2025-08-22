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
        <h3 class="mt-4">ระบบขายสินค้า POS </h3>
        <div class="row mt-3">
            <div class="col-md-6">
                <label>ค้นหาสินค้า (ชื่อ / บาร์โค้ด):</label>
                <input type="text" class="form-control" id="search_input" placeholder="พิมพ์ชื่อหรือบาร์โค้ด..." autofocus>
                <div id="product_list" class="list-group mt-2"></div>
            </div> 
            <div class="col-md-6">
                <div class="card p-3 shadow-sm">
                    <div class="form-group">
                        <label>ลูกค้า:</label>
                        <div class="input-group">
                          <input type="text" class="form-control" id="customer_input" placeholder="ค้นหาลูกค้า (ไม่ระบุ = เงินสด)">
                          <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#returnModal" title="รับคืนสินค้า"><i class="fa fa-undo" ></i> รับคืน</button>
                          </div>
                        </div>
                        <input type="hidden" id="customer_id">
                        <div id="customer_list" class="list-group mt-1 position-absolute" style="z-index: 999;"></div>
                    </div>

                    <h5 class="mb-3">ตะกร้าสินค้า:</h5>
                    <table class="table table-sm table-bordered" id="cart_table">
                        <thead>
                            <tr>
                                <th>สินค้า</th>
                                <th>จำนวน</th>
                                <th>ราคาต่อหน่วย</th>
                                <th>รวม</th>
                                <th>ลบ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right">รวมทั้งหมด</td>
                                <td id="total_price">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="form-row">
                        <div class="form-group col-md-6" hidden>
                            <label>ส่วนลด (บาท):</label>
                            <input type="number" class="form-control" id="discount_input" placeholder="0" min="0">
                        </div>
                        <div class="form-group col-md-6">
                            <label>ยอดสุทธิ (บาท):</label>
                            <input type="text" class="form-control" id="final_amount" disabled value="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                      <label>ยอดคงเหลือ (ยังไม่ได้ชำระ):</label>
                      <input type="text" class="form-control text-danger font-weight-bold" id="remaining_balance" disabled value="0.00">
                    </div>
                    <h5 class="mt-3">ช่องทางชำระเงิน</h5>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>เงินสด</label>
                            <input type="number" class="form-control" id="pay_cash" placeholder="0" min="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label>เงินโอน</label>
                            <input type="number" class="form-control" id="pay_transfer" placeholder="0" min="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label>สินเชื่อ</label>
                            <input type="number" class="form-control" id="pay_credit" placeholder="0" min="0">
                        </div>
                    </div>
                    <div class="form-group" id="credit_provider_group" style="display:none">
                        <label>ประเภทสินเชื่อ</label>
                        <select class="form-control" id="credit_provider">
                            <option value="">-- เลือกประเภทสินเชื่อ --</option>
                            <option value="creditcard">บัตรเครดิต</option>
                            <option value="samsung finance">Samsung Finance+</option>
                            <option value="sg finance">SGFinance+</option>
                            <option value="อื่นๆ">อื่นๆ</option>
                        </select>
                    </div>

                    <button class="btn btn-success btn-block mt-3" id="confirm_sale">บันทึกการขาย</button>
                    
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
</main>

    <!-- Modal รับคืนสินค้า -->
    <div class="modal fade" id="returnModal" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

          <div class="modal-header py-2">
            <h5 class="modal-title" id="returnModalLabel">คืนสินค้า</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="ปิด">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <div class="modal-body">
            <!-- 🔍 ค้นหาใบเสร็จ -->
            <div class="form-inline mb-3">
              <label class="mr-2">เลขใบเสร็จ:</label>
              <input type="text" id="return_receipt_no" class="form-control mr-2" placeholder="เช่น RC20250700123">
              <button class="btn btn-primary btn-sm" id="fetch_return_items">ค้นหา</button>
            </div>

            <!-- 🧾 รายการสินค้าในใบเสร็จ -->
            <div id="return_items_container">
              <!-- จะถูกเติมผ่าน JS หลังจากกดค้นหา -->
            </div>
          </div>

          <div class="modal-footer py-2">
            <span class="text-danger mr-auto small" id="return_error" style="display:none;"></span>
            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">ยกเลิก</button>
            <button type="button" class="btn btn-success btn-sm" id="confirm_return_items">เพิ่มสินค้าที่คืนเข้าใบเสร็จ</button>
          </div>

        </div>
      </div>
    </div>



<!-- Modal แก้ไขราคาขาย -->
<div class="modal fade" id="priceModal" tabindex="-1" role="dialog" aria-labelledby="priceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="priceModalLabel">แก้ไขราคาขาย</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="ปิด">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div><strong id="modal_product_name">ชื่อสินค้า</strong></div>
        <div class="form-group mb-2">
          <label for="price_input" class="mb-1">ราคาขายใหม่ (บาท):</label>
          <input type="number" step="0.01" class="form-control" id="price_input" autofocus>
        </div>
        <div id="price_warning" class="text-danger small" style="display:none;"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">ยกเลิก</button>
        <button type="button" class="btn btn-primary btn-sm" id="confirm_price_btn">ตกลง</button>
      </div>
    </div>
  </div>
</div>

<script src="../js/sale_pos.js?v=<?= time() ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
