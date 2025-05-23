<?php
// repair_receipt.php - ใบรับเครื่องซ่อม (88mm)

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    exit("ไม่พบข้อมูลใบรับซ่อม");
}

$stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$repair = $stmt->fetch();
if (!$repair) {
    exit("ไม่พบข้อมูลใบรับซ่อม");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>ใบรับเครื่องซ่อม</title>
  <style>
    body {
      font-family: Tahoma, sans-serif;
      font-size: 14px;
      max-width: 88mm;
      margin: auto;
    }
    h3, p {
      text-align: center;
      margin: 4px 0;
    }
    table {
      width: 100%;
      margin-top: 10px;
    }
    td {
      vertical-align: top;
      padding: 3px 0;
    }
    .bold { font-weight: bold; }
    .border-top { border-top: 1px dashed #000; margin: 8px 0; }
  </style>
</head>
<body onload="window.print()">
  <h3>ร้านป้อมมือถือ</h3>
  <p>ใบรับเครื่องซ่อม</p>
  <div class="border-top"></div>

  <table>
    <tr>
      <td class="bold">เลขที่:</td>
      <td>#<?= $repair['id'] ?></td>
    </tr>
    <tr>
      <td class="bold">วันที่:</td>
      <td><?= date('d/m/Y H:i', strtotime($repair['created_at'])) ?></td>
    </tr>
    <tr>
      <td class="bold">ลูกค้า:</td>
      <td><?= htmlspecialchars($repair['customer_name']) ?></td>
    </tr>
    <tr>
      <td class="bold">เบอร์:</td>
      <td><?= htmlspecialchars($repair['tel']) ?></td>
    </tr>
    <tr>
      <td class="bold">รุ่นเครื่อง:</td>
      <td><?= htmlspecialchars($repair['device_model']) ?></td>
    </tr>
    <?php if (!empty($repair['imei'])): ?>
    <tr>
      <td class="bold">IMEI:</td>
      <td><?= htmlspecialchars($repair['imei']) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
      <td class="bold">อาการ:</td>
      <td><?= nl2br(htmlspecialchars($repair['issue_desc'])) ?></td>
    </tr>
    <tr>
      <td class="bold">ราคาประเมิน:</td>
      <td><?= number_format($repair['expected_cost'], 2) ?> บาท</td>
    </tr>
  </table>

  <div class="border-top"></div>
  <p style="text-align:center">ขอบคุณที่ใช้บริการ</p>
  <p style="text-align:center">สอบถามเพิ่มเติม: 044-315-949</p>
  <p style="text-align:center">สอบถามเพิ่มเติม: 086-247-1599</p>
</body>
</html>
