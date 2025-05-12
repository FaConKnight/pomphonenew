<?php
// /cooladmin/manager/show_saving.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "รายการออมมือถือทั้งหมด";
$search = $_GET['search'] ?? '';

$sql = "SELECT s.*, c.cua_name, c.cua_lastname, p.name AS product_name
        FROM savings s
        LEFT JOIN customer_account c ON s.customer_id = c.cua_id
        LEFT JOIN products p ON s.product_id = p.id
        WHERE 1";
$params = [];

if ($search !== '') {
  $sql .= " AND (
    s.saving_ref LIKE ? OR
    c.cua_name LIKE ? OR
    c.cua_lastname LIKE ? OR
    p.name LIKE ?
  )";
  $params = array_fill(0, 4, "%$search%");
}

$sql .= " ORDER BY s.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$savings = $stmt->fetchAll();
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">รายการออมมือถือ</h3>

        <form method="GET" class="form-inline mb-3">
          <input type="text" name="search" placeholder="ค้นหา..." value="<?= htmlspecialchars($search) ?>" class="form-control mr-2">
          <button type="submit" class="btn btn-info">ค้นหา</button>
        </form>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>รหัสออม</th>
              <th>ลูกค้า</th>
              <th>รุ่นมือถือ</th>
              <th>ยอดรวม</th>
              <th>ยอดที่ชำระ</th>
              <th>สถานะ</th>
              <th>เปิดเมื่อ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($savings as $i => $s): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($s['saving_ref']) ?></td>
                <td><?= htmlspecialchars($s['cua_name'] . ' ' . $s['cua_lastname']) ?></td>
                <td><?= htmlspecialchars($s['product_name']) ?></td>
                <td><?= number_format($s['total_price'], 2) ?></td>
                <td><?= number_format($s['paid_amount'], 2) ?></td>
                <td><?= htmlspecialchars($s['status']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>
  </div>
</div>

<?php include_once('../partials/footer.php'); ?>
