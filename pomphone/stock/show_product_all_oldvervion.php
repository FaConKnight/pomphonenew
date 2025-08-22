<?php
// /cooladmin/manager/show_product_name.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

// โหลดหมวดหมู่
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$selected_category = $_GET['category_id'] ?? '';
$selected_range = $_GET['range'] ?? '1y';
$products = [];
$category_name = '';


$mobile_stock_map = [];
$stock_stmt = $pdo->query("SELECT p.name, COUNT(pi.id) as count
                            FROM products_items pi
                            INNER JOIN products p ON pi.product_id = p.id
                            INNER JOIN categories c ON p.category_id = c.id
                            WHERE pi.status = 'in_stock' AND c.name = 'มือถือ'
                            GROUP BY p.name");
foreach ($stock_stmt->fetchAll() as $row) {
  $mobile_stock_map[$row['name']] = $row['count'];
}

// กำหนดช่วงเวลาเริ่มต้น
$range_sql = "";
if ($selected_range === '1y') {
  $range_sql = "AND pi.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
} elseif ($selected_range === '5y') {
  $range_sql = "AND pi.created_at >= DATE_SUB(NOW(), INTERVAL 5 YEAR)";
} elseif ($selected_range === '10y') {
  $range_sql = "AND pi.created_at >= DATE_SUB(NOW(), INTERVAL 10 YEAR)";
}

if ($selected_category !== '') {
    $category_stmt = $pdo->prepare("SELECT name FROM categories WHERE id = ? LIMIT 1");
    $category_stmt->execute([$selected_category]);
    $category_name = $category_stmt->fetchColumn();

    if ($category_name === 'มือถือ') {
        $stmt = $pdo->prepare("SELECT pi.id, p.name AS product_name, p.sku, c.name AS category_name,
                                      pi.imei1, pi.serial_number, pi.barcode, pi.cost_price, pi.sell_price, pi.wholesale_price,
                                      pi.status, pi.created_at
                               FROM products_items pi
                               LEFT JOIN products p ON pi.product_id = p.id
                               LEFT JOIN categories c ON p.category_id = c.id
                               WHERE p.category_id = ? $range_sql
                               ORDER BY pi.created_at DESC");
        $stmt->execute([$selected_category]);
        $products = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.sku, p.stock_quantity,
                                      c.name AS category_name,
                                      p.cost_price, p.sell_price, p.wholesale_price,
                                      p.created_at
                               FROM products p
                               LEFT JOIN categories c ON p.category_id = c.id
                               LEFT JOIN (
                                 SELECT product_id,
                                        MAX(created_at) AS latest,
                                        SUBSTRING_INDEX(GROUP_CONCAT(cost_price ORDER BY created_at DESC), ',', 1) AS cost_price,
                                        SUBSTRING_INDEX(GROUP_CONCAT(sell_price ORDER BY created_at DESC), ',', 1) AS sell_price,
                                        SUBSTRING_INDEX(GROUP_CONCAT(wholesale_price ORDER BY created_at DESC), ',', 1) AS wholesale_price
                                 FROM products_items
                                 GROUP BY product_id
                               ) pi ON p.id = pi.product_id
                               WHERE p.category_id = ?
                               ORDER BY p.name ASC");
        $stmt->execute([$selected_category]);
        $products = $stmt->fetchAll();
    }
} else {
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.sku, p.stock_quantity,
                                  c.name AS category_name,
                                  p.cost_price, p.sell_price, p.wholesale_price,
                                  p.created_at
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.id
                           LEFT JOIN (
                             SELECT product_id,
                                    MAX(created_at) AS latest,
                                    SUBSTRING_INDEX(GROUP_CONCAT(cost_price ORDER BY created_at DESC), ',', 1) AS cost_price,
                                    SUBSTRING_INDEX(GROUP_CONCAT(sell_price ORDER BY created_at DESC), ',', 1) AS sell_price,
                                    SUBSTRING_INDEX(GROUP_CONCAT(wholesale_price ORDER BY created_at DESC), ',', 1) AS wholesale_price
                             FROM products_items
                             GROUP BY product_id
                           ) pi ON p.id = pi.product_id
                           ORDER BY p.name ASC");
    $stmt->execute();
    $products = $stmt->fetchAll();
}
?>


<main>
<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">รายการสินค้าทั้งหมด</h3>

        <form method="GET" class="form-inline mb-3">
          <label class="mr-2">เลือกหมวดหมู่:</label>
          <select name="category_id" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">-- แสดงทั้งหมด --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $selected_category) ? 'selected' : '' ?>>
                <?= safe_text($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label class="ml-3 mr-2">ช่วงข้อมูล:</label>
          <select name="range" class="form-control mr-2" onchange="this.form.submit()">
            <option value="1y" <?= $selected_range === '1y' ? 'selected' : '' ?>>1 ปี</option>
            <option value="5y" <?= $selected_range === '5y' ? 'selected' : '' ?>>5 ปี</option>
            <option value="10y" <?= $selected_range === '10y' ? 'selected' : '' ?>>10 ปี</option>
            <option value="all" <?= $selected_range === 'all' ? 'selected' : '' ?>>ทั้งหมด</option>
          </select>

          <input type="text" id="searchInput" class="form-control ml-3" placeholder="ค้นหา...">
        </form>

        <?php if (count($products) > 0): ?>
        <table class="table table-bordered table-striped" id="productTable">
          <thead>
            <tr>
              <th>#</th>
              <th>ชื่อสินค้า</th>
              <th>SKU</th>
              <th>หมวดหมู่</th>
              <?php if ($category_name === 'มือถือ'): ?>
                <th>IMEI</th>
                <th>S/N</th>
                <th>Barcode</th>
                <th>สถานะ</th>
              <?php else: ?>
                <th>จำนวนคงเหลือ</th>
              <?php endif; ?>
              <th>ราคาทุน</th>
              <th>ราคาส่ง</th>
              <th>ราคาขาย</th>
              <th>เพิ่มเมื่อ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $index => $p): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= safe_text($p['product_name'] ?? $p['name']) ?></td>
                <td><?= safe_text($p['sku']) ?></td>
                <td><?= safe_text($p['category_name']) ?></td>
                <?php if ($category_name === 'มือถือ'): ?>
                  <td><?= safe_text($p['imei1']) ?></td>
                  <td><?= safe_text($p['serial_number']) ?></td>
                  <td><?= safe_text($p['barcode']) ?></td>
                  <td><?= safe_text($p['status']) ?></td>
                <?php else: ?>
                  <td>                
                    <?php 
                    if ($p['category_name'] === 'มือถือ') {
                      $name = $p['product_name'] ?? $p['name'];
                      echo isset($mobile_stock_map[$name]) 
                        ? '<span class="badge badge-info">' . $mobile_stock_map[$name] . ' เครื่อง</span>' 
                        : '-';
                    } else {
                      echo number_format($p['stock_quantity']?? 0, 2);
                    }
                  ?>
                  </td>
                <?php endif; ?>
                <td><?= $p['cost_price'] !== null ? number_format($p['cost_price']?? 0, 2) : '-' ?></td>
                <td><?= $p['wholesale_price'] !== null ? number_format($p['wholesale_price']?? 0, 2) : '-' ?></td>
                <td><?= $p['sell_price'] !== null ? number_format($p['sell_price']?? 0, 2) : '-' ?></td>
                <td><?= date('d/m/Y H:i', strtotime(safe_date($p['created_at']))) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="alert alert-info">ไม่พบสินค้า</div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function () {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll('#productTable tbody tr');
  rows.forEach(function(row) {
    row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
  });
});
</script>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>