<?php
// /cooladmin/register_line.php
// แบบฟอร์มลงทะเบียน LINE → เบอร์โทรลูกค้า

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');

$line_id = $_GET['id_line'] ?? '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tel = trim($_POST['tel'] ?? '');
  if ($line_id && $tel) {
    // ค้นลูกค้าด้วยเบอร์โทร
    $stmt = $pdo->prepare("SELECT cua_id FROM customer_account WHERE cua_tel = ? LIMIT 1");
    $stmt->execute([$tel]);
    if ($stmt->rowCount() > 0) {
      $cu_id = $stmt->fetchColumn();
      // อัปเดต lineid
      $update = $pdo->prepare("UPDATE customer_account SET cua_lineid = ? WHERE cua_id = ?");
      $update->execute([$line_id, $cu_id]);
      $message = "✅ ผูก LINE กับเบอร์ $tel เรียบร้อยแล้ว! กรุณากลับไปที่ LINE เพื่อใช้งานต่อ";
    } else {
      $message = "ไม่พบบัญชีที่มีเบอร์โทรนี้ในระบบ กรุณาติดต่อร้านค้าหรือสมัครใหม่";
    }
  } else {
    $message = "กรุณากรอกข้อมูลให้ครบ";
  }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ลงทะเบียน LINE กับเบอร์โทร</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card shadow-lg">
          <div class="card-body">
            <h4 class="card-title mb-3">🔐 ลงทะเบียนใช้งานระบบออมมือถือ</h4>
            <?php if ($message): ?>
              <div class="alert alert-info"> <?= htmlspecialchars($message) ?> </div>
            <?php endif; ?>
            <?php if (!$message): ?>
            <form method="post">
              <input type="hidden" name="line_id" value="<?= htmlspecialchars($line_id) ?>">
              <div class="mb-3">
                <label for="tel" class="form-label">เบอร์โทรศัพท์</label>
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
