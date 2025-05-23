
<?php
// sale.php เวอร์ชัน UI มืออาชีพ + AJAX ค้นหาลูกค้า

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 11) {
    echo "<script>alert('ไม่มีสิทธิ์เข้าถึงหน้าขายสินค้า');window.location='../index.php';</script>";
    exit;
}
?>
<div class="page-container">
  <div class="main-content">
    <div class="container-fluid">
        <h3 class="mt-4">ระบบขายสินค้า POS</h3>
        <div class="row mt-3">
            <div class="col-md-6">
                <label>ค้นหาสินค้า (ชื่อ / บาร์โค้ด):</label>
                <input type="text" class="form-control" id="search_input" placeholder="พิมพ์ชื่อหรือบาร์โค้ด..." autofocus>
                <div id="product_list" class="list-group mt-2"></div>
            </div>
            <div class="col-md-6">
                <div class="card p-3 shadow-sm">
                    <div class="form-group">
                        <label>ค้นหาลูกค้า (ชื่อ / เบอร์ / Username):</label>
                        <input type="text" class="form-control" id="customer_input" placeholder="ไม่ระบุ = เงินสดทั่วไป">
                        <div id="customer_list" class="list-group mt-1"></div>
                        <input type="hidden" id="customer_id">
                    </div>

                    <h5 class="mb-3">ตะกร้าสินค้า:</h5>
                    <table class="table table-sm table-bordered" id="cart_table">
                        <thead>
                            <tr>
                                <th>ชื่อสินค้า</th>
                                <th>จำนวน</th>
                                <th>ราคา/ชิ้น</th>
                                <th>รวม</th>
                                <th>ลบ</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right">รวมทั้งหมด</td>
                                <td id="total_price">0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>ส่วนลด (บาท):</label>
                            <input type="number" class="form-control" id="discount_input" placeholder="0" min="0">
                        </div>
                        <div class="form-group col-md-6">
                            <label>ยอดสุทธิ (บาท):</label>
                            <input type="text" class="form-control" id="final_amount" disabled value="0">
                        </div>
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

<?php include_once("../partials/footer.php"); ?>
<script>
$(document).ready(function(){
    $('#search_input').focus();

    $('#search_input').keyup(function(){
        let query = $(this).val().trim();

        // ✅ ตรวจหา customer โดยอัตโนมัติหากขึ้นต้นด้วย CU
        if (query.startsWith("CU")) {
            $.post("ajax/fetch_customers.php", {query: query}, function(data){
                if (data.includes('data-id')) {
                    let temp = $('<div>').html(data);
                    let first = temp.find('a.select-customer').first();
                    if (first.length > 0) {
                        let name = first.data('name');
                        let id = first.data('id');
                        $('#customer_input').val(name);
                        $('#customer_id').val(id);
                        $('#search_input').val('').focus();
                        $('#product_list').html('');
                    }
                }
            });
        }

        // ค้นหาสินค้าตามปกติ
        if(query.length > 1){
            $.post("ajax/fetch_products.php", {query:query}, function(data){
                $('#product_list').html(data);
            });
        } else {
            $('#product_list').html('');
        }
    });

    $('#customer_input').keyup(function(){
        let q = $(this).val().trim();
        if(q.length > 1){
            $.post("ajax/fetch_customers.php", {query:q}, function(data){
                $('#customer_list').html(data);
            });
        } else {
            $('#customer_list').html('');
            $('#customer_id').val('');
        }
    });

    $(document).on('click', '.select-customer', function(){
        let name = $(this).data('name');
        let id = $(this).data('id');
        $('#customer_input').val(name);
        $('#customer_id').val(id);
        $('#customer_list').html('');
    });

    $(document).on('click', '.add-to-cart', function(){
        let id = $(this).data('id');
        let name = $(this).data('name');
        let price = $(this).data('price');
        let imei = $(this).data('imei') || null;

        let tr = `<tr data-id="${id}" data-imei="${imei}">
                    <td>${name}</td>
                    <td><input type="number" class="form-control qty" value="1" min="1" ${imei ? 'readonly' : ''}></td>
                    <td>${price}</td>
                    <td class="subtotal">${price}</td>
                    <td><button class="btn btn-danger btn-sm remove">ลบ</button></td>
                </tr>`;
        $('#cart_table tbody').append(tr);
        calculateTotal();

        $('#search_input').val('').focus();
        $('#product_list').html('');
    });

    $(document).on('input', '.qty, #discount_input', function(){
        calculateTotal();
    });

    $(document).on('click', '.remove', function(){
        $(this).closest('tr').remove();
        calculateTotal();
    });

    $('#pay_credit').on('input', function(){
        let val = parseFloat($(this).val()) || 0;
        if (val > 0) {
            $('#credit_provider_group').slideDown();
        } else {
            $('#credit_provider_group').slideUp();
            $('#credit_provider').val('');
        }
    });

    function calculateTotal(){
        let total = 0;
        $('#cart_table tbody tr').each(function(){
            total += parseFloat($(this).find('.subtotal').text());
        });
        let discount = parseFloat($('#discount_input').val()) || 0;
        let final = total - discount;
        $('#total_price').text(total.toFixed(2));
        $('#final_amount').val(final.toFixed(2));
    }

    $('#confirm_sale').click(function(){
        let items = [];
        $('#cart_table tbody tr').each(function(){
            let id = $(this).data('id');
            let qty = $(this).find('.qty').val();
            let imei = $(this).data('imei') || null;
            items.push({id:id, qty:qty, imei:imei});
        });

        let discount = parseFloat($('#discount_input').val()) || 0;
        let customer_id = $('#customer_id').val() || null;
        let payments = {
            cash: parseFloat($('#pay_cash').val()) || 0,
            transfer: parseFloat($('#pay_transfer').val()) || 0,
            credit: parseFloat($('#pay_credit').val()) || 0,
            credit_provider: $('#credit_provider').val() || null
        };

        let totalAmount = parseFloat($('#total_price').text());
        let finalAmount = totalAmount - discount;
        let paid = payments.cash + payments.transfer + payments.credit;

        if (Math.abs(paid - finalAmount) > 0.01) {
            alert("ยอดชำระไม่ตรงกับยอดสุทธิ กรุณาตรวจสอบอีกครั้ง");
            return;
        }

        if (payments.credit > 0 && !payments.credit_provider) {
            alert("กรุณาเลือกประเภทสินเชื่อให้ครบถ้วน");
            return;
        }

        $.ajax({
            url: "save_sale.php",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({items:items, discount:discount, payments:payments, customer_id:customer_id}),
            success: function(response){
                alert("ขายสินค้าสำเร็จ");
                window.open("receipt.php?sale_id=" + response, "_blank");
                location.reload();
            },
            error: function(xhr){
                alert("เกิดข้อผิดพลาดในการบันทึกการขาย\n" + xhr.responseText);
            }
        });
    });
});
</script>
