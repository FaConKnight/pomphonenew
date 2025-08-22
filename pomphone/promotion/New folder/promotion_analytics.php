<?php
// promotion_analytics.php - วิเคราะห์การเปิดอ่าน / คลิก / ตอบกลับ

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

// ดึง log ล่าสุด 50 รายการ
$logs = $pdo->query("SELECT pl.*, pt.title
                    FROM promotion_logs pl
                    LEFT JOIN promotion_templates pt ON pl.template_id = pt.id
                    ORDER BY pl.schedule DESC LIMIT 50")->fetchAll();
?>

<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">📊 สถิติการส่งโปรโมชัน</h3>
      <table class="table table-bordered">
        <thead class="bg-light">
          <tr>
            <th>เวลา/วันที่</th>
            <th>ชื่อเทมเพลต</th>
            <th>ส่งถึง</th>
            <th>เปิดอ่าน</th>
            <th>คลิกลิงก์</th>
            <th>ตอบกลับ</th>
            <th>ดูผู้รับ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $log): ?>
            <?php
              $opened = $pdo->prepare("SELECT COUNT(*) FROM promotion_recipients WHERE log_id = ? AND is_read = 1");
              $clicked = $pdo->prepare("SELECT COUNT(*) FROM promotion_recipients WHERE log_id = ? AND is_clicked = 1");
              $replied = $pdo->prepare("SELECT COUNT(*) FROM promotion_recipients WHERE log_id = ? AND is_replied = 1");
              $opened->execute([$log['id']]);
              $clicked->execute([$log['id']]);
              $replied->execute([$log['id']]);
            ?>
            <tr>
              <td><?= htmlspecialchars($log['schedule']) ?></td>
              <td><?= htmlspecialchars($log['title'] ?? '-') ?></td>
              <td><?= (int)$log['recipients'] ?> ราย</td>
              <td><?= $opened->fetchColumn() ?> ราย</td>
              <td><?= $clicked->fetchColumn() ?> ราย</td>
              <td><?= $replied->fetchColumn() ?> ราย</td>
              <td><a href="promotion_recipients.php?log_id=<?= $log['id'] ?>" class="btn btn-info btn-sm">ดู</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
