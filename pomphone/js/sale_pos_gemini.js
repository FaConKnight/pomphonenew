// sale_pos.js - Final & Complete Professional Version
document.addEventListener("DOMContentLoaded", function () {
    const $ = jQuery;
    let itemsToReturn = []; // Array สำหรับเก็บรายการคืนชั่วคราว

    // ==========================================================
    // ส่วนที่ 1: ฟังก์ชันหลัก (Core Functions)
    // ==========================================================

    function updateTotals() {
        let purchaseTotal = 0;
        $('#cart_table tbody tr').each(function () {
            const price = parseFloat($(this).find('.price-cell').text()) || 0;
            const qty = parseInt($(this).find('.qty').val()) || 1;
            const subtotal = price * qty;
            $(this).find('.subtotal-cell').text(subtotal.toFixed(2));
            purchaseTotal += subtotal;
        });

        let refundTotal = itemsToReturn.reduce((sum, item) => sum + parseFloat(item.custom_return_price), 0);

        $('#purchase_total').text(purchaseTotal.toFixed(2));
        $('#refund_total').text(`-${refundTotal.toFixed(2)}`);

        const discount = parseFloat($('#discount_input').val()) || 0;
        const finalAmount = purchaseTotal - refundTotal - discount;

        $('#final_amount').text(finalAmount.toFixed(2));

        if (finalAmount < 0) {
            $('#final_amount_label').text('ยอดเงินทอนลูกค้า');
            $('#final_amount').text(Math.abs(finalAmount).toFixed(2));
        } else {
            $('#final_amount_label').text('ยอดสุทธิ');
        }
        updateBalance(finalAmount);
    }

    function updateBalance(finalAmount) {
        const cash = parseFloat($('#pay_cash').val()) || 0;
        const transfer = parseFloat($('#pay_transfer').val()) || 0;
        const totalPaid = cash + transfer;
        
        const balance = finalAmount - totalPaid;
        $('#balance_due').text(balance.toFixed(2));
    }
    
    function showReturnSummary() {
         let refundTotal = itemsToReturn.reduce((sum, item) => sum + parseFloat(item.custom_return_price), 0);
        $('#return_summary_area').html(
            itemsToReturn.length > 0
                ? `<div class="alert alert-warning p-2">มีรายการรอคืน ${itemsToReturn.length} รายการ | ยอดคืนรวม: <strong>${refundTotal.toFixed(2)}</strong> <button id="clear_returns_btn" class="btn btn-sm btn-danger float-right py-0 px-1" title="ล้างรายการคืนทั้งหมด">ล้าง</button></div>`
                : ''
        );
    }

    // ==========================================================
    // ส่วนที่ 2: Event Listeners ทั่วไป
    // ==========================================================

    // โฟกัสที่ช่องค้นหาเมื่อหน้าโหลด
    $('#search_input').focus();


  // ค้นหาสินค้า
  $('#search_input').on('keyup', function () {
    let query = $(this).val().trim();

    if (query.startsWith("CU")) {
      $.post("ajax/fetch_customers.php", { query: query }, function (data) {
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

    if (query.length > 1) {
      $.post("ajax/fetch_products.php", { query: query }, function (data) {
        $('#product_list').html(data);
      });
    } else {
      $('#product_list').html('');
    }
  });

    // ค้นหาลูกค้า
  $('#customer_input').on('keyup', function () {
    let q = $(this).val().trim();
    if (q.length > 1) {
      $.post("ajax/fetch_customers.php", { query: q }, function (data) {
        $('#customer_list').html(data);
      });
    } else {
      $('#customer_list').html('');
      $('#customer_id').val('');
    }
  });
  
    // เพิ่มสินค้าลงตะกร้า
    $(document).on('click', '.add-to-cart', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');
        const price = parseFloat($(this).data('price'));
        const imei = $(this).data('imei') || null;

        // ตรวจสอบ IMEI ซ้ำในตะกร้า
        if (imei && $(`#cart_table tbody tr[data-imei="${imei}"]`).length > 0) {
            alert('มีสินค้านี้ (IMEI) ในตะกร้าแล้ว');
            return;
        }

        const newRow = `
            <tr data-id="${id}" data-imei="${imei || ''}">
                <td>${name}</td>
                <td class="price-cell">${price.toFixed(2)}</td>
                <td><input type="number" class="form-control form-control-sm qty" value="1" min="1" ${imei ? 'readonly' : ''}></td>
                <td class="subtotal-cell">${price.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger remove-cart-item">&times;</button></td>
            </tr>`;
        $('#cart_table tbody').append(newRow);
        
        $('#search_input').val('').focus();
        $('#product_list').html('').hide();
        updateTotals();
    });

    // ลบสินค้าออกจากตะกร้า
    $(document).on('click', '.remove-cart-item', function () {
        $(this).closest('tr').remove();
        updateTotals();
    });
    
    // ล้างรายการคืนทั้งหมด
    $(document).on('click', '#clear_returns_btn', function() {
        if (confirm('คุณต้องการล้างรายการคืนทั้งหมดใช่หรือไม่?')) {
            itemsToReturn = [];
            showReturnSummary();
            updateTotals();
        }
    });

    // คำนวณยอดใหม่เมื่อมีการเปลี่ยนแปลง
    $(document).on('input', '.qty, #discount_input, .payment-input', updateTotals);

    // ==========================================================
    // ส่วนที่ 3: ระบบคืนสินค้า (Return System Logic)
    // ==========================================================

    // ค้นหาใบเสร็จใน Modal
    $('#search_receipt_btn').on('click', function () {
        const receiptNo = $('#search_receipt_no').val().trim();
        if (!receiptNo) {
            alert('กรุณากรอกเลขที่ใบเสร็จ');
            return;
        }
        $(this).prop('disabled', true).text('กำลังค้นหา...');

        $.post('ajax/fetch_sale_for_return.php', { receipt_no: receiptNo }, function (res) {
            if (res.success && res.items.length > 0) {
                let html = `
                    <table class="table table-sm table-bordered">
                        <thead><tr>
                            <th>เลือก</th>
                            <th>สินค้า</th>
                            <th>จำนวนที่คืนได้</th>
                            <th>ราคาซื้อ</th>
                            <th>ราคาคืน (แก้ไขได้)</th>
                            <th>เหตุผล</th>
                        </tr></thead>
                        <tbody>`;
                res.items.forEach(item => {
                    const itemData = JSON.stringify(item);
                    html += `
                        <tr data-original-item='${itemData}'>
                            <td><input type="checkbox" class="return-check"></td>
                            <td>${item.product_name} ${item.imei ? `(${item.imei})` : ''}</td>
                            <td>${item.qty_available_for_return}</td>
                            <td>${parseFloat(item.original_price).toFixed(2)}</td>
                            <td><input type="number" class="form-control form-control-sm return-price" value="${parseFloat(item.original_price).toFixed(2)}" max="${parseFloat(item.original_price).toFixed(2)}" step="0.01"></td>
                            <td><input type="text" class="form-control form-control-sm return-reason" placeholder="ลูกค้าเปลี่ยนใจ, ชำรุด, อื่นๆ"></td>
                        </tr>`;
                });
                html += '</tbody></table>';
                $('#return_items_area').html(html);
            } else {
                $('#return_items_area').html(`<div class="alert alert-danger">${res.message || 'ไม่พบใบเสร็จ หรือไม่มีสินค้าที่สามารถคืนได้'}</div>`);
            }
        }, 'json').always(function() {
            $('#search_receipt_btn').prop('disabled', false).text('ค้นหา');
        });
    });

    // ยืนยันการเพิ่มรายการคืนจาก Modal
    $('#add_return_items_btn').on('click', function () {
        let hasError = false;
        $('.return-check:checked').each(function () {
            const row = $(this).closest('tr');
            const originalItem = JSON.parse(row.attr('data-original-item'));
            const customPrice = parseFloat(row.find('.return-price').val());
            const reason = row.find('.return-reason').val();

            if (isNaN(customPrice) || customPrice < 0 || customPrice > parseFloat(originalItem.original_price)) {
                alert(`ราคาคืนของสินค้า "${originalItem.product_name}" ไม่ถูกต้อง (ต้องไม่สูงกว่าราคาซื้อและไม่ติดลบ)`);
                row.find('.return-price').addClass('is-invalid');
                hasError = true;
                return false; // Stop .each loop
            }
            
            if (itemsToReturn.some(item => item.original_sale_item_id === originalItem.original_sale_item_id)) {
                alert(`สินค้า "${originalItem.product_name}" ถูกเพิ่มในรายการรอคืนแล้ว`);
                hasError = true;
                return false;
            }

            itemsToReturn.push({
                original_sale_item_id: originalItem.original_sale_item_id,
                custom_return_price: customPrice,
                reason: reason
            });
        });

        if (!hasError) {
            $('#returnModal').modal('hide');
            $('#return_items_area').html(''); // Clear modal content
            $('#search_receipt_no').val('');
            showReturnSummary();
            updateTotals();
        }
    });

    // ==========================================================
    // ส่วนที่ 4: การบันทึกรายการสุดท้าย (Final Confirmation)
    // ==========================================================
    $('#confirm_exchange_btn').on('click', function() {
        const purchaseItems = [];
        $('#cart_table tbody tr').each(function() {
            purchaseItems.push({
                id: $(this).data('id'),
                qty: $(this).find('.qty').val(),
                imei: $(this).data('imei') || null
            });
        });

        if (purchaseItems.length === 0 && itemsToReturn.length === 0) {
            alert('ไม่มีรายการสำหรับบันทึก');
            return;
        }

        const payload = {
            purchase_items: purchaseItems,
            return_items: itemsToReturn,
            discount: $('#discount_input').val() || 0,
            customer_id: $('#customer_id').val() || null,
            payments: {
                cash: $('#pay_cash').val() || 0,
                transfer: $('#pay_transfer').val() || 0
            }
        };

        const finalAmount = parseFloat($('#final_amount').text());
        const totalPaid = (parseFloat(payload.payments.cash) + parseFloat(payload.payments.transfer));
        
        if (finalAmount > 0 && Math.abs(totalPaid - finalAmount) > 0.01) {
            if (!confirm(`ยอดชำระไม่ตรงกับยอดสุทธิ (${totalPaid.toFixed(2)} vs ${finalAmount.toFixed(2)})\nคุณต้องการทำรายการต่อหรือไม่?`)) {
                return;
            }
        }
        
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> กำลังบันทึก...');

        $.ajax({
            url: 'ajax/process_exchange.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            success: function(res) {
                if (res.success) {
                    alert('บันทึกรายการสำเร็จ!');
                    if (res.sale_id) {
                         window.open("receipt.php?sale_id=" + res.sale_id, "_blank");
                    }
                    location.reload();
                } else {
                    alert('เกิดข้อผิดพลาดจาก Server: ' + res.message);
                    btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> ยืนยันและบันทึกรายการ');
                }
            },
            error: function(xhr) {
                let errorMsg = 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ';
                try {
                    const err = JSON.parse(xhr.responseText);
                    if (err.message) {
                        errorMsg = err.message;
                    }
                } catch (e) {
                    errorMsg = xhr.responseText;
                }
                alert('เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + errorMsg);
                btn.prop('disabled', false).html('<i class="fa fa-check-circle"></i> ยืนยันและบันทึกรายการ');
            },
            dataType: 'json'
        });
    });
});