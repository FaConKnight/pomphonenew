<?php
// /cooladmin/manager/saving_pending.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "ตรวจสอบการแจ้งชำระเงิน (ออมมือถือ)";

// อนุมัติ / ปฏิเสธ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? null;
  $action = $_POST['action'] ?? '';
  $remark = $_POST['remark'] ?? '';
  $employee_id = $_SESSION['employee_id'] ?? null;

  if ($id && in_array($action, ['approved', 'rejected'])) {
    $stmt = $pdo->prepare("SELECT * FROM saving_pending WHERE id = ?");
    $stmt->execute([$id]);
    $pending = $stmt->fetch();

    if ($pending && $action === 'approved') {
      $match_stmt = $pdo->prepare("SELECT * FROM savings s
                                    LEFT JOIN customer_account c ON s.customer_id = c.cua_id
                                    WHERE c.cua_tel = ? AND s.status IN ('active','completed')
                                    ORDER BY s.created_at DESC LIMIT 1");
      $match_stmt->execute([$pending['phone_number']]);
      $saving = $match_stmt->fetch();

      if ($saving) {
        $insert = $pdo->prepare("INSERT INTO saving_payments (saving_id, amount, employee_id, remark)
                                 VALUES (?, ?, ?, ?)");
        $insert->execute([$saving['id'], $pending['amount_guess'], $employee_id, 'อนุมัติจากสลิป']);

        $new_paid = $saving['paid_amount'] + $pending['amount_guess'];
        $new_status = ($new_paid >= $saving['total_price']) ? 'completed' : $saving['status'];
        $update = $pdo->prepare("UPDATE savings SET paid_amount = ?, status = ? WHERE id = ?");
        $update->execute([$new_paid, $new_status, $saving['id']]);

        $log = $pdo->prepare("INSERT INTO saving_logs (saving_ref, action, employee_id, remark)
                              VALUES (?, 'saving_payment', ?, 'ยืนยันสลิปโดยพนักงาน')");
        $log->execute([$saving['saving_ref'], $employee_id]);
      }
    }

    $upd = $pdo->prepare("UPDATE saving_pending SET status = ?, approved_by = ?, remark = ? WHERE id = ?");
    $upd->execute([$action, $employee_id, $remark, $id]);
  }
}

// โหลดรายการที่ยังไม่อนุมัติ
$stmt = $pdo->query("SELECT * FROM saving_pending WHERE status = 'pending' ORDER BY created_at DESC");
$pendings = $stmt->fetchAll();
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>
<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">📤 รายการแจ้งโอน รอตรวจสอบ</h3>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>เบอร์โทร</th>
              <th>ยอดเงิน</th>
              <th>แนบสลิป</th>
              <th>เวลาส่ง</th>
              <th>การกระทำ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pendings as $i => $p): ?>
              <?php
                $check = $pdo->prepare("SELECT 1 FROM savings s
                                       LEFT JOIN customer_account c ON s.customer_id = c.cua_id
                                       WHERE c.cua_tel = ? AND s.status IN ('active','completed')");
                $check->execute([$p['phone_number']]);
                $notFound = ($check->rowCount() === 0);
              ?>
              <tr<?= $notFound ? ' style="background-color: #f8d7da"' : '' ?>>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($p['phone_number']) ?></td>
                <td><?= number_format($p['amount_guess'], 2) ?></td>
                <td><a href="<?= htmlspecialchars($p['image_path']) ?>" target="_blank">ดูสลิป</a></td>
                <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                <td>
                  <form method="POST" class="form-inline">
                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                    <input type="text" name="remark" class="form-control mb-2 mr-2" placeholder="หมายเหตุ (ถ้ามี)">
                    <button name="action" value="approved" class="btn btn-success btn-sm mr-1">✔ ยืนยัน</button>
                    <button name="action" value="rejected" class="btn btn-danger btn-sm">✖ ปฏิเสธ</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!--?php include_once('../partials/footer.php'); ?-->
