<?php
// /cooladmin/manager/stock_logs.php

// เวอร์ชันใหม่: แสดง log การเคลื่อนไหวเฉพาะของระบบ "สินค้า" เท่านั้น

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "ประวัติการทำรายการสินค้า (Stock Logs)";
$search = $_GET['search'] ?? '';
$actions = [
    'in' => 'รับเข้า',
    'out' => 'ขายออก',
    'adjust_stock' => 'ปรับสต๊อก',
    'adjust_price' => 'ปรับราคา',
    'status_change' => 'เปลี่ยนสถานะ',
    'return' => 'คืนสินค้า',
    'discard' => 'ตัดจำหน่าย',
    'manual_edit' => 'แก้ไขด้วยมือ'
];

$sql = "SELECT sl.*, ea.em_username,
               COALESCE(p.name, p2.name) AS product_name,
               pi.imei1,
               COALESCE(p.sku, p2.sku) AS sku
        FROM stock_logs sl
        LEFT JOIN employee_account ea ON sl.employee_id = ea.em_id
        LEFT JOIN products_items pi ON sl.product_item_id = pi.id
        LEFT JOIN products p ON pi.product_id = p.id
        LEFT JOIN products p2 ON sl.product_id = p2.id
        WHERE 1";
$params = [];

if ($search !== '') {
    $sql .= " AND (
      COALESCE(p.name, p2.name) LIKE ? OR
      pi.imei1 LIKE ? OR
      sl.remark LIKE ? OR
      ea.em_username LIKE ?
    )";
    $params = array_fill(0, 4, "%$search%");
}

$sql .= " ORDER BY sl.created_at DESC LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">ประวัติการทำรายการสินค้า</h3>

        <form method="GET" class="form-inline mb-3">
          <input type="text" name="search" placeholder="ค้นหา..." value="<?= htmlspecialchars($search) ?>" class="form-control mr-2">
          <button type="submit" class="btn btn-info">ค้นหา</button>
        </form>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>วันเวลา</th>
              <th>ชื่อสินค้า</th>
              <th>IMEI / SKU</th>
              <th>การกระทำ</th>
              <th>โดย</th>
              <th>หมายเหตุ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($logs as $i => $log): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                <td><?= htmlspecialchars($log['product_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($log['imei1'] ?? $log['sku'] ?? '-') ?></td>
                <td><?= $actions[$log['action']] ?? $log['action'] ?></td>
                <td><?= htmlspecialchars($log['em_username'] ?? '-') ?></td>
                <td><?= htmlspecialchars($log['remark']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>
  </div>
</div>

<?php include_once('../partials/footer.php'); ?>
