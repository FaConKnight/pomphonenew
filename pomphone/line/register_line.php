<?php
// line/register_line.php - ผูก LINE กับเบอร์โทรลูกค้า + บันทึกลง line_users

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

$line_id = $_GET['id_line'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tel = trim($_POST['tel'] ?? '');
  $line_id = trim($_POST['line_id'] ?? '');

  if ($line_id && $tel) {
    // ค้นหาลูกค้าจากเบอร์โทร
    $stmt = $pdo->prepare("SELECT cua_id FROM customer_account WHERE cua_tel = ? LIMIT 1");
    $stmt->execute([$tel]);
    $cua_id = $stmt->fetchColumn();

    if ($cua_id) {

      // ลบ record เดิม (ป้องกันซ้ำ)
      $pdo->prepare("DELETE FROM line_users WHERE line_user_id = ? AND user_type = 'customer'")
          ->execute([$line_id]);

      // เพิ่มเข้า line_users
      $pdo->prepare("INSERT INTO line_users (cua_id, line_user_id, user_type, updated_at)
                     VALUES (?, ?, 'customer', NOW())")
          ->execute([$cua_id, $line_id]);

      $message = "✅ ผูก LINE กับเบอร์ $tel เรียบร้อยแล้ว! กรุณากลับไปที่ LINE เพื่อใช้งานต่อ";
    } else {
      $message = "❌ ไม่พบบัญชีที่มีเบอร์โทรนี้ในระบบ กรุณาติดต่อร้านค้าหรือสมัครใหม่";
    }
  } else {
    $message = "❌ กรุณากรอกข้อมูลให้ครบ";
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ลงทะเบียน LINE กับเบอร์โทร</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-lg">
          <div class="card-body">
            <h4 class="card-title mb-3">🔐 ลงทะเบียนสมาชิกร้านป้อมมือถือ</h4>
            <?php if ($message): ?>
              <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!$message): ?>
            <form method="post">
              <input type="hidden" name="line_id" value="<?= htmlspecialchars($line_id) ?>">
              <div class="mb-3">
                <label class="form-label">เบอร์โทรศัพท์</label>
                <input type="tel" name="tel" class="form-control" required placeholder="เช่น 0812345678">
              </div>
              <button type="submit" class="btn btn-primary w-100">📝 ลงทะเบียน</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
