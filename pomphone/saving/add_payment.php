<?php
// /cooladmin/manager/add_payment.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

$page_title = "บันทึกการผ่อนงวด";
$success = null;
$error = null;

// โหลดรายการออมที่ active หรือ completed (ยังไม่ delivered)
$savings = $pdo->query("SELECT s.id, s.saving_ref, c.cua_name, c.cua_lastname
                         FROM savings s
                         LEFT JOIN customer_account c ON s.customer_id = c.cua_id
                         WHERE s.status IN ('active', 'completed')
                         ORDER BY s.created_at DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $saving_id = $_POST['saving_id'] ?? null;
  $amount = $_POST['amount'] ?? null;
  $employee_id = $_SESSION['employee_id'] ?? null;

  if (!$saving_id || !$amount || $amount <= 0) {
    $error = "กรุณากรอกข้อมูลให้ครบและถูกต้อง";
  } else {
    try {
      $pdo->beginTransaction();

      // ดึงข้อมูลเดิม
      $stmt = $pdo->prepare("SELECT * FROM savings WHERE id = ? LIMIT 1");
      $stmt->execute([$saving_id]);
      $saving = $stmt->fetch();

      if (!$saving) throw new Exception("ไม่พบข้อมูลการออม");

      // บันทึกการชำระ
      $insert = $pdo->prepare("INSERT INTO saving_payments (saving_id, amount, employee_id) VALUES (?, ?, ?)");
      $insert->execute([$saving_id, $amount, $employee_id]);

      // อัปเดตยอดสะสม
      $new_paid = $saving['paid_amount'] + $amount;
      $new_status = $saving['status'];

      if ($new_paid >= $saving['total_price'] && $saving['status'] !== 'delivered') {
        $new_status = 'completed';
      }

      $update = $pdo->prepare("UPDATE savings SET paid_amount = ?, status = ?, updated_at = NOW() WHERE id = ?");
      $update->execute([$new_paid, $new_status, $saving_id]);

      // log
      $log = $pdo->prepare("INSERT INTO saving_logs (saving_ref, action, employee_id, remark) VALUES (?, 'saving_payment', ?, ?)");
      $log->execute([$saving['saving_ref'], $employee_id, 'ผ่อนงวด ' . number_format($amount, 2) . ' บาท']);

      $pdo->commit();
      $success = "✅ บันทึกการชำระสำเร็จ";

    } catch (Exception $e) {
      $pdo->rollBack();
      $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
  }
}
?>

<main>
<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">บันทึกการผ่อนงวด</h3>

        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST">
          <div class="form-group">
            <label>เลือกรายการออม</label>
            <select name="saving_id" class="form-control" required>
              <option value="">-- เลือกรายการ --</option>
              <?php foreach ($savings as $s): ?>
                <option value="<?= $s['id'] ?>">
                  <?= safe_text($s['saving_ref'] . ' - ' . $s['cua_name'] . ' ' . $s['cua_lastname']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>จำนวนเงินที่ชำระ</label>
            <input type="number" name="amount" step="0.01" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary">บันทึกการชำระ</button>
        </form>

      </div>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>