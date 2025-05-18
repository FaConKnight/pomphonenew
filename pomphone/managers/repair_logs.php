<?php
// /cooladmin/manager/repair_logs.php

// แสดง log งานซ่อม

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "ประวัติการทำรายการซ่อม (Repair Logs)";
$search = $_GET['search'] ?? '';
$actions = [
    'repair_created' => 'รับซ่อม',
    'repair_updated' => 'อัปเดตสถานะ',
    'repair_complete' => 'ซ่อมเสร็จ',
    'repair_cancelled' => 'ยกเลิกงานซ่อม',
    'repair_returned' => 'ลูกค้ามารับเครื่อง',
    'manual_edit' => 'แก้ไขด้วยมือ'
];

$sql = "SELECT rl.*, ea.em_username
        FROM repair_logs rl
        LEFT JOIN employee_account ea ON rl.employee_id = ea.em_id
        WHERE 1";
$params = [];

if ($search !== '') {
    $sql .= " AND (
      rl.repair_ref LIKE ? OR
      rl.remark LIKE ? OR
      ea.em_username LIKE ?
    )";
    $params = array_fill(0, 3, "%$search%");
}

$sql .= " ORDER BY rl.created_at DESC LIMIT 200";
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
        <h3 class="mb-4">ประวัติการทำรายการซ่อม</h3>

        <form method="GET" class="form-inline mb-3">
          <input type="text" name="search" placeholder="ค้นหา..." value="<?= htmlspecialchars($search) ?>" class="form-control mr-2">
          <button type="submit" class="btn btn-info">ค้นหา</button>
        </form>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>วันเวลา</th>
              <th>รหัสงานซ่อม</th>
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
                <td><?= htmlspecialchars($log['repair_ref']) ?></td>
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
