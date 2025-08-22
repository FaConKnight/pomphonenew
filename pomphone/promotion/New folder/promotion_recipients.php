<?php
// promotion_recipients.php - รายชื่อผู้รับโปรโมชันตาม log_id

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

$log_id = $_GET['log_id'] ?? '';
if (!$log_id || !is_numeric($log_id)) {
  echo '<div class="alert alert-danger">ไม่พบรหัส log ที่ถูกต้อง</div>';
  exit;
}

$stmt = $pdo->prepare("SELECT pl.*, pt.title as template_title FROM promotion_logs pl 
                        LEFT JOIN promotion_templates pt ON pl.template_id = pt.id
                        WHERE pl.id = ? LIMIT 1");
$stmt->execute([$log_id]);
$log = $stmt->fetch();

if (!$log) {
  echo '<div class="alert alert-warning">ไม่พบข้อมูล log นี้</div>';
  exit;
}

$recipients = $pdo->prepare("SELECT pr.*, ca.cua_name, ca.cua_tel, ca.cua_rank
                               FROM promotion_recipients pr
                               LEFT JOIN customer_account ca ON pr.cua_id = ca.cua_id
                               WHERE pr.log_id = ?");
$recipients->execute([$log_id]);
$recipients = $recipients->fetchAll();
?>

<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">ผู้รับโปรโมชัน: <?= htmlspecialchars($log['template_title'] ?? '-') ?></h3>
      <p><strong>เวลาส่ง:</strong> <?= htmlspecialchars($log['schedule']) ?> | <strong>จำนวน:</strong> <?= count($recipients) ?> ราย</p>
      <table class="table table-striped table-bordered">
        <thead>
          <tr>
            <th>#</th>
            <th>ชื่อ</th>
            <th>เบอร์โทร</th>
            <th>ระดับ</th>
            <th>userId</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recipients as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($r['cua_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['cua_tel'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['cua_rank'] ?? '-') ?></td>
              <td><?= htmlspecialchars($r['line_user_id']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <a href="promotion_logs.php" class="btn btn-secondary mt-3">ย้อนกลับ</a>
    </div>
  </div>
</div>

<?php include_once('../partials/footer.php'); ?>
