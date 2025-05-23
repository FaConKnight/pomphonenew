<?php
// /cooladmin/manager/show_product_name.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

// โหลดหมวดหมู่
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC")->fetchAll();

$selected_category = $_GET['category_id'] ?? '';
$products = [];

if ($selected_category !== '') {
    $stmt = $pdo->prepare("SELECT p.id, p.name, p.sku, p.is_trackable, p.created_at, p.is_active, c.name AS category_name
                            FROM products p
                            LEFT JOIN categories c ON p.category_id = c.id
                            WHERE p.category_id = ?
                            ORDER BY p.name ASC");
    $stmt->execute([$selected_category]);
    $products = $stmt->fetchAll();
}

// toggle สถานะสินค้า
if (isset($_GET['toggle']) && isset($_GET['pid'])) {
    $pid = (int)$_GET['pid'];
    $pdo->query("UPDATE products SET is_active = NOT is_active WHERE id = $pid");
    header("Location: show_product_name.php?category_id=$selected_category");
    exit;
}
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">รายการสินค้าตามหมวดหมู่</h3>

        <div class="mb-3">
          <a href="add_category.php" class="btn btn-success btn-sm">➕ เพิ่มหมวดหมู่หลัก</a>
          <a href="add_subcategory.php" class="btn btn-warning btn-sm" hidden>➕ เพิ่มหมวดหมู่ย่อย(ยังใช้ไม่ได้)</a>
          <a href="add_product_name.php" class="btn btn-primary btn-sm">➕ เพิ่มรายการสินค้า</a>
        </div>

        <form method="GET" class="form-inline mb-4">
          <label class="mr-2">เลือกหมวดหมู่:</label>
          <select name="category_id" class="form-control mr-2" onchange="this.form.submit()">
            <option value="">-- เลือก --</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $selected_category) ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>

        <?php if ($selected_category && count($products) > 0): ?>
        <table class="table table-striped">
          <thead>
            <tr>
              <th>#</th>
              <th>ชื่อสินค้า</th>
              <th>SKU</th>
              <th>หมวดหมู่</th>
              <th>IMEI</th>
              <th>สถานะ</th>
              <th>เปิด/ปิด</th>
              <th>เพิ่มเมื่อ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($products as $index => $p): ?>
              <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($p['sku']) ?></td>
                <td><?= htmlspecialchars($p['category_name']) ?></td>
                <td><?= $p['is_trackable'] ? '✔️' : '✖️' ?></td>
                <td><?= $p['is_active'] ? '<span class="text-success">เปิด</span>' : '<span class="text-danger">ปิด</span>' ?></td>
                <td><a href="?toggle=1&pid=<?= $p['id'] ?>&category_id=<?= $selected_category ?>" class="btn btn-sm btn-warning">สลับ</a></td>
                <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php elseif ($selected_category): ?>
          <div class="alert alert-info">ไม่พบสินค้าในหมวดนี้</div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<?php include_once('../partials/footer.php'); ?>
