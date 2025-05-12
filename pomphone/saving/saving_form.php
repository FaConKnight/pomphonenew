<?php
// /cooladmin/manager/saving_form.php
// ฟอร์มให้ลูกค้าแจ้งโอนเงินออมมือถือ (แนบสลิป)
define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');

$page_title = "แจ้งการโอนเงินออมมือถือ";
$success = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $phone = $_POST['phone_number'] ?? '';
  $amount = $_POST['amount'] ?? '';
  $file = $_FILES['slip'] ?? null;

  if (!$phone || !$amount || !$file || $file['error'] !== 0) {
    $error = "กรุณากรอกข้อมูลให้ครบและแนบสลิป";
  } else {
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024;

    if (!in_array($file['type'], $allowed_types)) {
      $error = "อนุญาตเฉพาะไฟล์ JPG หรือ PNG เท่านั้น";
    } elseif ($file['size'] > $max_size) {
      $error = "ไฟล์ต้องมีขนาดไม่เกิน 2MB";
    } else {
      $upload_dir = '../uploads/slips/';
      if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);
      $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      $filename = 'slip_form_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
      $target = $upload_dir . $filename;

      if (move_uploaded_file($file['tmp_name'], $target)) {
        $stmt = $pdo->prepare("INSERT INTO saving_pending (phone_number, amount_guess, image_path, remark)
                              VALUES (?, ?, ?, 'แจ้งผ่านแบบฟอร์มลูกค้า')");
        $stmt->execute([$phone, $amount, $target]);
        $success = "✅ ระบบได้รับข้อมูลเรียบร้อยแล้ว กรุณารอเจ้าหน้าที่ตรวจสอบภายใน 24 ชั่วโมง";
      } else {
        $error = "อัปโหลดสลิปล้มเหลว กรุณาลองใหม่";
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $page_title ?></title>
  <link href="../assets/css/theme.css" rel="stylesheet">
  <link href="../assets/vendor/bootstrap-4.1/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f8f9fa;
    }
    .form-wrapper {
      max-width: 500px;
      margin: auto;
      background: #fff;
      padding: 2rem;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    @media (max-width: 576px) {
      .form-wrapper {
        padding: 1rem;
      }
      h3 {
        font-size: 1.25rem;
      }
    }
  </style>
</head>
<body>
  <div class="page-wrapper p-3">
    <div class="form-wrapper">
      <h3 class="mb-4 text-center text-primary">📥 แจ้งการโอนเงินออมมือถือ</h3>

      <?php if ($success): ?>
        <div class="alert alert-success text-center"><?= $success ?></div>
      <?php elseif ($error): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
      <?php endif; ?>

      <?php if (!$success): ?>
      <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label>เบอร์โทรลูกค้า</label>
          <input type="tel" name="phone_number" class="form-control" required pattern="[0-9]{9,15}" placeholder="08xxxxxxxx">
        </div>
        <div class="form-group">
          <label>จำนวนเงินที่โอน</label>
          <input type="number" name="amount" step="0.01" class="form-control" required placeholder="เช่น 1500.00">
        </div>
        <div class="form-group">
          <label>แนบสลิป (JPG / PNG ไม่เกิน 2MB)</label>
          <input type="file" name="slip" accept="image/jpeg,image/png" class="form-control-file" required>
        </div>
        <label class="text-muted">เป็นการแจ้งยอดโอนเงินเท่านั้น ยอดปัจจุบันจะถูกอัพเดตภายในไม่เกิน 24 ชั่วโมงหลังจากตรวจสอบสลิปแล้ว</label>
        <br>
        <label class="text-muted">*หากเกิน 24 ชั่วโมง ยอดไม่ถูกปรับโปรดติดต่อเจ้าหน้าที่*</label>
        <button type="submit" class="btn btn-primary btn-block mt-3">📤 ส่งข้อมูล</button>
      </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
