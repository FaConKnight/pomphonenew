// sale_pos.js - รองรับระบบขาย + คืนสินค้าแบบ Modal + ระบบ POS เดิม (ค้นหาลูกค้า, ใส่สินค้า, คำนวณยอด)
function debounce(func, delay) {
  let timeoutId;
  return function (...args) {
    clearTimeout(timeoutId);
    timeoutId = setTimeout(() => func.apply(this, args), delay);
  };
}

document.addEventListener("DOMContentLoaded", function () {
  //console.log("🔁 calculateTotal() called");
  const $ = jQuery;
  let selectedRowForDiscount = null;

  // โฟกัส input สินค้า
  $('#search_input').focus();

  /////////////////////////  Input search /////////////////////////
  $('#search_input').on('keyup', debounce(function (e) {
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
      return;
    }
    // กด Enter → บังคับค้นหา
    if (e.which === 13 ) {
      $.post("ajax/fetch_products.php", { query: query }, function (data) {
        let temp = $('<div>').html(data);
        let found = temp.find('.add-to-cart');

        if (found.length === 1) {
          // เจอสินค้ารายการเดียว → เพิ่มเข้าตะกร้าเลย (ไม่ใช้ .click())
          let btn = found.first();
          let id = btn.data('id');
          let name = btn.data('name');
          let price = parseFloat(btn.data('price')) || 0;
          let imei = btn.data('imei') || null;
          let wholesale_price = parseFloat($(this).data('cost')) || 0;

          let isDuplicate = false;
          $('#cart_table tbody tr').each(function () {
            let existingImei = $(this).data('imei');
            if (imei && existingImei === imei) {
              isDuplicate = true;
              return false;
            }
          });

          if (isDuplicate) {
            alert("❗ สินค้านี้มีอยู่ในตะกร้าแล้ว ไม่สามารถเพิ่ม IMEI ซ้ำได้");
          } else {
            let tr = `<tr data-id="${id}" data-imei="${imei}" data-cost="${wholesale_price}" data-price="${price}">
                        <td>${name}</td>
                        <td><input type="number" class="form-control qty" value="1" min="1" ${imei ? 'readonly' : ''} ></td>
                        <td>${price.toFixed(2)}</td>
                        <td class="subtotal">${price.toFixed(2)}</td>
                        <td><button class="btn btn-danger btn-sm remove">ลบ</button></td>
                      </tr>`;
            $('#cart_table tbody').append(tr);
            calculateTotal();
          }

          $('#search_input').val('').focus();
          $('#product_list').html('');
        } else {
          // เจอหลายรายการ หรือไม่เจอ → แสดงให้เลือก
          $('#product_list').html(data);
        }
      });
    } else if (query.length > 1) {
      $.post("ajax/fetch_products.php", { query: query }, function (data) {
        $('#product_list').html(data);
      });
    } else {
      $('#product_list').html('');
    }
  }, 300));
  /////////////////////////  END Input search /////////////////////////
  ////////////////////////  search Customer ค้นหาลูกค้า //////////////////
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

  $(document).on('click', '.select-customer', function () {
    let name = $(this).data('name');
    let id = $(this).data('id');
    $('#customer_input').val(name);
    $('#customer_id').val(id);
    $('#customer_list').html('');
  });
  ////////////////////////  END search Customer   //////////////////
  ///////////////////// เปิดหน้าต่างใส่ราคาขายเอง Modal  //////////////////////
  $(document).on('click', '#cart_table tbody td:nth-child(3)', function (e) {
    if ($(e.target).hasClass('remove') || $(e.target).is('button')) return;

    selectedRowForDiscount = $(this).closest('tr');
    let productName = selectedRowForDiscount.find('td:first').text();
    let originalPrice = parseFloat(selectedRowForDiscount.data('price')) || 0;
    let costPrice = parseFloat(selectedRowForDiscount.data('cost')) || 0;

    $('#modal_product_name').text(productName);
    $('#price_input').val(originalPrice);
    $('#price_warning').text('').hide();
    $('#priceModal').modal('show');

    $('#price_input').off('input').on('input', debounce(function () {
      let newPrice = parseFloat($(this).val()) || 0;

      if (newPrice < costPrice) {
        $('#price_warning').text(`⚠️ ราคานี้ต่ำกว่าราคาทุน (${costPrice.toFixed(2)}฿)`).show();
      } else {
        $('#price_warning').hide();
      }
    }, 300));
  });

  $('#confirm_price_btn').on('click', function () {
    let newPrice = parseFloat($('#price_input').val()) || 0;
    let originalPrice = parseFloat(selectedRowForDiscount.data('price')) || 0;

    let itemDiscount = Math.max(originalPrice - newPrice, 0);

    selectedRowForDiscount.data('price-custom', newPrice);       // ✅ ราคาขายใหม่
    selectedRowForDiscount.data('item-discount', itemDiscount);  // ✅ ส่วนลดที่คำนวณได้

    selectedRowForDiscount.find('td:eq(2)').html(`
      ${newPrice.toFixed(2)}
      <br><small class="text-muted">ลด ${itemDiscount.toFixed(2)}</small>
    `);

    $('#priceModal').modal('hide');
    calculateTotal();
  });
  ///////////////////// END เปิดหน้าต่างใส่ราคาขายเอง Modal  //////////////////////




  //////////// เพิ่มสินค้าลงตะกร้า โดยการคลิกเลือก //////////////////
  $(document).on('click', '.add-to-cart', function () {
    let id = $(this).data('id');
    let name = $(this).data('name');
    let price = parseFloat($(this).data('price')) || 0;
    let imei = $(this).data('imei') || null;
    let wholesale_price = parseFloat($(this).data('cost')) || 0;

    let isDuplicate = false;
    $('#cart_table tbody tr').each(function () {
      let existingId = $(this).data('id');
      let existingImei = $(this).data('imei');
      if (imei && existingImei === imei) {
        isDuplicate = true;
        return false;
      }
    });

    if (isDuplicate) {
      alert("❗ สินค้านี้มีอยู่ในตะกร้าแล้ว ไม่สามารถเพิ่ม IMEI ซ้ำได้");
      $('#search_input').val('').focus();
      $('#product_list').html('');
      return;
    }

    let tr = `<tr data-id="${id}" data-imei="${imei}" data-cost="${wholesale_price}" data-price="${price}">
              <td>${name}</td>
              <td><input type="number" class="form-control qty" value="1" min="1" ${imei ? 'readonly' : ''} ></td>
              <td>${price.toFixed(2)}</td>
              <td class="subtotal">${price.toFixed(2)}</td>
              <td><button class="btn btn-danger btn-sm remove">ลบ</button></td>
            </tr>`;

    $('#cart_table tbody').append(tr);
    calculateTotal();
    $('#search_input').val('').focus();
    $('#product_list').html('');
  });
  /////////////////////////////////////////////////////////////////////////////////////////////////////
  ///////////////////////////////// ส่วนของการคำนวน /////////////////////////////////////////////////////
  $(document).on('input', '.qty, #discount_input, #pay_cash, #pay_transfer, #pay_credit', function () {
    calculateTotal();
    calculateBalance();
  });

  $(document).on('click', '.remove', function () {
    $(this).closest('tr').remove();
    calculateTotal();
  });

  $('#pay_credit').on('input', function () {
    let val = parseFloat($(this).val()) || 0;
    if (val > 0) {
      $('#credit_provider_group').slideDown();
    } else {
      $('#credit_provider_group').slideUp();
      $('#credit_provider').val('');
    }
  });

  function calculateTotal() {
    let total = 0;
    let discount = 0;

    $('#cart_table tbody tr').each(function () {
      let qty = parseFloat($(this).find('.qty').val()) || 1;
      let price = parseFloat($(this).data('price-custom')) || parseFloat($(this).data('price')) || 0;
      let itemDiscount = parseFloat($(this).data('item-discount')) || 0;

      total += price * qty;
      discount += itemDiscount * qty;

      $(this).find('.subtotal').text((price * qty).toFixed(2));
    });

    let final = total - discount;
    $('#total_price').text(total.toFixed(2));
    $('#total_discount').text(discount.toFixed(2));
    $('#final_amount').text(final.toFixed(2));

    calculateBalance();
  }

  function calculateBalance() {
    const total = parseFloat($('#total_price').text()) || 0;
    const discount = parseFloat($('#discount_input').val()) || 0;
    const cash = parseFloat($('#pay_cash').val()) || 0;
    const transfer = parseFloat($('#pay_transfer').val()) || 0;
    const credit = parseFloat($('#pay_credit').val()) || 0;

    const finalAmount = total - discount;
    const paid = cash + transfer + credit;
    const remaining = Math.max(0, finalAmount - paid);

    $('#final_amount').val(finalAmount.toFixed(2));
    $('#remaining_balance').val(remaining.toFixed(2));
  }
  /////////////////////////////////////////////////////////////////////////////////////////////////////
  //////////////////////////// ระบบขายสินค้า ////////////////////////////////////////////////////////////
  $('#confirm_sale').click(function () {
    let items = [];
    $('#cart_table tbody tr').each(function () {
      let id = $(this).data('id');
      let qty = $(this).find('.qty').val();
      let imei = $(this).data('imei') || null;
      let isReturn = $(this).hasClass('return-item-row');
      let custom_price = parseFloat($(this).data('price-custom')) || null;
      let item_discount = parseFloat($(this).data('item-discount')) || 0;

      items.push({
        id: id,
        qty: qty,
        imei: imei,
        is_return: isReturn,
        custom_price: custom_price,
        item_discount: item_discount
      });
    });

    if (items.length === 0) {
      alert("กรุณาเลือกสินค้าก่อนทำการขาย");
      return;
    }

    let customer_id = $('#customer_id').val() || null;
    let payments = {
      cash: parseFloat($('#pay_cash').val()) || 0,
      transfer: parseFloat($('#pay_transfer').val()) || 0,
      credit: parseFloat($('#pay_credit').val()) || 0,
      credit_provider: $('#credit_provider').val() || null
    };

    let totalAmount = parseFloat($('#total_price').text()) || 0;
    let discount = parseFloat($('#discount_input').val()) || 0;
    let finalAmount = totalAmount - discount;
    let paid = payments.cash + payments.transfer + payments.credit;
    let change = Math.max(0, paid - finalAmount);
    if ( (Math.abs(paid - finalAmount) > finalAmount * 0.2) && (Math.abs(paid - finalAmount) > 1000 ) ) {
      alert("ยอดชำระผิดปกติ กรุณาตรวจสอบอีกครั้ง");
      return;
    }

    if ( paid <  totalAmount ) {
      alert("โปรดใส่จำนวนเงินให้ถูกต้อง");
      return;
    }

    if (payments.credit > 0 && !payments.credit_provider) {
      alert("กรุณาเลือกประเภทสินเชื่อให้ครบถ้วน");
      return;
    }

    $('#confirm_sale').prop('disabled', true);

    $.ajax({
      url: "save_sale.php",
      method: "POST",
      contentType: "application/json",
      data: JSON.stringify({
        items: items,
        discount: discount,
        payments: payments,
        customer_id: customer_id,
        change_amount: change
      }),
      success: function (response) {
        alert("ขายสินค้าสำเร็จ");
        window.open("receipt.php?sale_id=" + response, "_blank");
        location.reload();
      },
      error: function (xhr) {
        alert("เกิดข้อผิดพลาดในการบันทึกการขาย\n" + xhr.responseText);
        $('#confirm_sale').prop('disabled', false);
      }
    });
  });
  // END ระบบขายสินค้า //

  // ระบบคืนสินค้า: โหลดใบเสร็จ //
    // 🔄 ค้นหาสินค้าในใบเสร็จเพื่อคืน
  $('#fetch_return_items').on('click', function () {
    const receiptNo = $('#return_receipt_no').val().trim();
    $('#return_error').hide().text('');
    $('#return_items_container').html('');

    if (receiptNo === '') {
      $('#return_error').text('กรุณากรอกเลขใบเสร็จ').show();
      return;
    }

    $.post('ajax/fetch_return_items.php', { receipt_no: receiptNo }, function (res) {
      if (!res.success) {
        $('#return_error').text(res.message).show();
        return;
      }

      if (!res.items || res.items.length === 0) {
        $('#return_error').text('ไม่พบสินค้าที่สามารถคืนได้ในใบเสร็จนี้').show();
        return;
      }

      const itemsHtml = res.items.map((item, index) => `
        <div class="form-check mb-1">
          <input class="form-check-input return-item-checkbox" type="checkbox" value="${index}" data-raw='${JSON.stringify(item)}' id="return_item_${index}">
          <label class="form-check-label" for="return_item_${index}">
            ${item.name} ${item.imei ? `(IMEI: ${item.imei})` : ''} - ฿${parseFloat(item.price).toFixed(2)}
          </label>
        </div>
      `).join('');

      $('#return_items_container').html(itemsHtml);
    }, 'json').fail(function () {
      $('#return_error').text('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์').show();
    });
  });



  

  $('#search_receipt_no').on('keypress', function (e) {
    if (e.which === 13) $('#load_receipt_items').click();
  });

  $('#load_receipt_items, #confirm_return_items').on('click', function () {
    let receiptNo = $('#search_receipt_no').val().trim();
    if (receiptNo === '') return alert('กรุณากรอกเลขใบเสร็จ');

    $.post('ajax/fetch_sale_for_return.php', { receipt_no: receiptNo }, function (res) {
      if (res.success) {
        let html = '<table class="table table-sm table-bordered"><thead><tr><th></th><th>สินค้า</th><th>IMEI</th><th>จำนวน</th><th>ราคาซื้อ</th><th>ราคาคืน</th><th>เหตุผล</th></tr></thead><tbody>';
        res.items.forEach((item, i) => {
          html += `<tr>
              <td><input type="checkbox" class="return-check" data-index="${i}"></td>
              <td>${item.product_name}</td>
              <td>${item.imei ?? '-'}</td>
              <td>${item.qty}</td>
              <td>${item.price.toFixed(2)}</td>
              <td><input type="number" class="form-control form-control-sm return-price" data-index="${i}" value="${item.price.toFixed(2)}" max="${item.price.toFixed(2)}"></td>
              <td><input type="text" class="form-control form-control-sm return-reason" data-index="${i}" placeholder="สาเหตุการคืน"></td>
          </tr>`;
        });
        html += '</tbody></table>';
        $('#return_items_area').html(html);
      } else {
        alert(res.message || 'ไม่พบใบเสร็จ');
      }
    }, 'json');
  });

  // ยืนยันการคืนสินค้า → เพิ่มในตะกร้า
  $(document).on('click', '#confirm_return_items', function () {
    $('.return-check:checked').each(function () {
      let i = $(this).data('index');
      let product = $(this).closest('tr').find('td').eq(1).text();
      let imei = $(this).closest('tr').find('td').eq(2).text();
      let price = parseFloat($(`.return-price[data-index='${i}']`).val());
      let reason = $(`.return-reason[data-index='${i}']`).val();

      if (isNaN(price) || price <= 0) return;

      let tr = `<tr class="return-item-row" data-id="${item.product_id}" data-imei="${item.imei}" data-price="${price}" data-price-custom="${-price}" data-qty="1">
            <td>${product}</td>
            <td><input type="number" class="form-control qty" value="1" min="1" readonly></td>
            <td>-${price.toFixed(2)}</td>
            <td class="subtotal">-${price.toFixed(2)}</td>
            <td><button class="btn btn-sm btn-danger remove">ลบ</button></td>
          </tr>`;
      $('#cart_table tbody').append(tr);
    });
    $('#returnModal').modal('hide');
    calculateTotal();
  });







});

