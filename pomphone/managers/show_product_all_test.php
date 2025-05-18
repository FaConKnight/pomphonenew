<?php
// /cooladmin/manager/show_product_name.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

// โหลดหมวดหมู่
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$selected_category = $_GET['category_id'] ?? '';
$selected_range = $_GET['range'] ?? '1y';
$products = [];
$category_name = '';
$mobile_stock_map = [];

// ถ้ายังไม่เลือกหมวดหมู่ ให้รวมรายการมือถือตามชื่อสินค้า
if ($selected_category === '') {
  $stmt = $pdo->prepare("SELECT p.name, COUNT(pi.id) AS total_in_stock
                          FROM products_items pi
                          INNER JOIN products p ON pi.product_id = p.id
                          INNER JOIN categories c ON p.category_id = c.id
                          WHERE c.name = 'มือถือ' AND pi.status = 'in_stock'
                          GROUP BY p.name
                          ORDER BY p.name ASC");
  $stmt->execute();
  $mobile_summary = $stmt->fetchAll();

  // map ชื่อสินค้า => จำนวนคงเหลือ
  foreach ($mobile_summary as $m) {
    $mobile_stock_map[$m['name']] = $m['total_in_stock'];
  }
} else {
  $mobile_summary = [];
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
                                  pi.cost_price, pi.sell_price, pi.wholesale_price,
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
                                pi.cost_price, pi.sell_price, pi.wholesale_price,
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

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">รายการสินค้าทั้งหมด</h3>

        <?php if (!empty($mobile_summary)): ?>
          <div class="alert alert-info">
            📱 <strong>จำนวนมือถือคงเหลือตามชื่อสินค้า:</strong><br>
            <ul>
              <?php foreach ($mobile_summary as $m): ?>
                <li><?= htmlspecialchars($m['name']) ?>: <strong><?= $m['total_in_stock'] ?></strong> เครื่อง</li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <script>
        document.addEventListener('DOMContentLoaded', function () {
          document.querySelectorAll('tr[data-product-name]').forEach(row => {
            const name = row.dataset.productName;
            const count = <?= json_encode($mobile_stock_map) ?>[name];
            if (count !== undefined) {
              const td = document.createElement('td');
              td.innerHTML = `<span class='badge badge-success'>เหลือ ${count} เครื่อง</span>`;
              row.appendChild(td);
            }
          });
        });
        </script>
