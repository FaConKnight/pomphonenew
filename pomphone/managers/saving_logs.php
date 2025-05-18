<?php
// /cooladmin/manager/saving_logs.php

// แสดง log งานออมมือถือ

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "ประวัติการทำรายการออมมือถือ (Saving Logs)";
$search = $_GET['search'] ?? '';
$actions = [
    'saving_created' => 'เปิดออม',
    'saving_payment' => 'ผ่อนงวด',
    'saving_completed' => 'ผ่อนครบ',
    'saving_cancelled' => 'ยกเลิกออม',
    'manual_edit' => 'แก้ไขด้วยมือ'
];

$sql = "SELECT sl.*, ea.em_username
        FROM saving_logs sl
        LEFT JOIN employee_account ea ON sl.employee_id = ea.em_id
        WHERE 1";
$params = [];

if ($search !== '') {
    $sql .= " AND (
      sl.saving_ref LIKE ? OR
      sl.remark LIKE ? OR
      ea.em_username LIKE ?
    )";
    $params = array_fill(0, 3, "%$search%");
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
        <h3 class="mb-4">ประวัติการทำรายการออมมือถือ</h3>

        <form method="GET" class="form-inline mb-3">
          <input type="text" name="search" placeholder="ค้นหา..." value="<?= htmlspecialchars($search) ?>" class="form-control mr-2">
          <button type="submit" class="btn btn-info">ค้นหา</button>
        </form>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>วันเวลา</th>
              <th>รหัสออม</th>
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
                <td><?= htmlspecialchars($log['saving_ref']) ?></td>
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
