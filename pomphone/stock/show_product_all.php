<?php
// /stock/show_all_products.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

// ดึงหมวดหมู่และแบรนด์
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();
?>

<main>
<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">รายการสินค้าทั้งหมด</h3>

        <!-- ฟิลเตอร์ -->
        <form id="filter-form" class="form-inline mb-4">
          <input type="text" name="keyword" class="form-control mr-2" placeholder="ค้นหาชื่อ / SKU">

          <select name="category_id" class="form-control mr-2">
            <option value="">ทุกหมวดหมู่</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= safe_text($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <select name="brand_id" class="form-control mr-2">
            <option value="">ทุกแบรนด์</option>
            <?php foreach ($brands as $brand): ?>
              <option value="<?= $brand['id'] ?>"><?= safe_text($brand['name']) ?></option>
            <?php endforeach; ?>
          </select>

          <select name="stock_status" class="form-control mr-2">
            <option value="">ทุกสถานะสต๊อก</option>
            <option value="in">คงเหลือ > 0</option>
            <option value="out">หมดสต๊อก</option>
          </select>

          <button type="submit" class="btn btn-primary">ค้นหา</button>
        </form>

        <!-- ตาราง -->
        <div class="table-responsive">
          <table class="table table-bordered table-striped" id="products-table">
            <thead>
              <tr>
                <th>#</th>
                <th>ชื่อสินค้า</th>
                <th>SKU</th>
                <th>หมวดหมู่</th>
                <th>แบรนด์</th>
                <th>คงเหลือ</th>
                <th>ราคาทุน</th>
                <th>ราคาส่ง</th>
                <th>ราคาขาย</th>
                <th>เพิ่มเมื่อ</th>
              </tr>
            </thead>
            <tbody id="products-body">
              <tr><td colspan="10" class="text-center">กรุณารอสักครู่...</td></tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function () {
  function loadProducts() {
    const formData = $('#filter-form').serialize();

    $('#products-body').html('<tr><td colspan="10" class="text-center">กำลังโหลด...</td></tr>');

    $.get('../includes/get_all_products.php', formData, function (data) {
      if (data.length === 0) {
        $('#products-body').html('<tr><td colspan="10" class="text-center text-muted">ไม่พบข้อมูล</td></tr>');
        return;
      }

      let rows = '';
      data.forEach((p, i) => {
        rows += `
          <tr>
            <td>${i + 1}</td>
            <td>${p.name}</td>
            <td>${p.sku ?? ''}</td>
            <td>${p.category_name ?? '-'}</td>
            <td>${p.brand_name ?? '-'}</td>
            <td>${p.stock_quantity ?? 0}</td>
            <td>${p.cost_price ?? '-'}</td>
            <td>${p.wholesale_price ?? '-'}</td>
            <td>${p.sell_price ?? '-'}</td>
            <td>${p.created_at}</td>
          </tr>`;
      });

      $('#products-body').html(rows);
    }, 'json');
  }

  $('#filter-form').submit(function (e) {
    e.preventDefault();
    loadProducts();
  });

  // โหลดครั้งแรก
  loadProducts();
});
</script>

