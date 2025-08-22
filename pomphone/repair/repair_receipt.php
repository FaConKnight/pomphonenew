<?php
// repair_receipt.php - ใบรับเครื่องซ่อม (80mm)

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

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
    body { font-family: Tahoma, sans-serif; font-size: 14px; max-width: 80mm; margin: 0; }
    h2, h4 { text-align: center; margin: 0; }
    table { width: 100%; border-collapse: collapse; margin-top: 1px; }
    td, th { padding: 4px 0; }
    .text-right { text-align: right; }
    .border-top { border-top: 1px dashed #000; }
    .footer-img { max-width: 100%; display: block; margin: 5px auto; }
    @media print {
      @page {
        margin-top: 0mm;
        margin-left: 3mm;
        margin-right: 3mm;
        margin-bottom: 5mm;
      }
    }
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
      <td><?= safe_date($repair['created_at']) ?></td>
    </tr>
    <tr>
      <td class="bold">ลูกค้า:</td>
      <td><?= safe_text($repair['customer_name']) ?></td>
    </tr>
    <tr>
      <td class="bold">เบอร์:</td>
      <td><?= safe_text($repair['tel']) ?></td>
    </tr>
    <tr>
      <td class="bold">รุ่นเครื่อง:</td>
      <td><?= safe_text($repair['device_model']) ?></td>
    </tr>
    <?php if (!empty($repair['imei'])): ?>
    <tr>
      <td class="bold">IMEI:</td>
      <td><?= safe_text($repair['imei']) ?></td>
    </tr>
    <?php endif; ?>
    <tr>
      <td class="bold">อาการ:</td>
      <td><?= nl2br(safe_text($repair['issue_desc'])) ?></td>
    </tr>
    <tr>
      <td class="bold">ราคาประเมิน:</td>
      <td><?= number_format($repair['expected_cost'] ?? 0, 2) ?> บาท</td>
    </tr>
    <?php if ($repair['send_type'] === 'ส่งศูนย์'): ?>
    <tr>
      <td class="bold">สถานะ:</td>
      <td>
          ส่งศูนย์บริการ (<?= safe_text($repair['partner_shop_name']) ?>)
      </td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($repair['pickup_date'])): ?>
    <tr>
      <td class="bold">นัดรับ:</td>
      <td><?= cheage_date($repair['pickup_date']) ?></td>
    </tr>
    <?php endif; ?>
    <?php if (!empty($repair['deposit_amount']) && $repair['deposit_amount'] > 0): ?>
    <tr>
      <td class="bold">มัดจำ:</td>
      <td><?= number_format($repair['deposit_amount'], 2) ?> บาท</td>
    </tr>
    <?php endif; ?>
  </table>

  <div class="border-top"></div>
  <div style="font-size:10px; line-height:1.6; padding:10px;">
    <h4 style="text-align:center; margin-bottom:10px;">🛠️ เงื่อนไขการรับบริการซ่อมสินค้า</h4>
    <p style="text-align:left; margin:5px 0;">
      การส่งเครื่องเข้ารับบริการ ถือว่าท่านได้อ่านและยอมรับเงื่อนไขต่อไปนี้แล้ว:
    </p>
    <ol style="padding-left: 20px;">
      <li style="margin-bottom: 10px;">
        <strong>กรุณาตรวจสอบความเรียบร้อยของสินค้า</strong> รวมถึงรอยขีดข่วน การแตกร้าว หรืออุปกรณ์ที่มาพร้อมเครื่อง 
        ทั้งก่อนส่งซ่อมและขณะรับคืน ทางร้านจะไม่รับผิดชอบความเสียหายใด ๆ ที่ไม่ได้แจ้งไว้ก่อนหน้า 
        และตรวจพบภายหลังจากที่ลูกค้าได้รับสินค้ากลับไปแล้ว
      </li>
      <li style="margin-bottom: 10px;">
        <strong>ข้อมูลภายในเครื่องของลูกค้า</strong> (เช่น รูปภาพ รายชื่อ ข้อมูลส่วนตัว) 
        อาจสูญหายได้จากกระบวนการซ่อมหรือเหตุสุดวิสัย ทางร้านแนะนำให้สำรองข้อมูลก่อนส่งเครื่อง 
        และขอสงวนสิทธิ์ไม่รับผิดชอบต่อความเสียหายหรือความสูญหายของข้อมูล เว้นแต่พิสูจน์ได้ว่าเกิดจากความประมาทของทางร้าน
      </li>
      <li style="margin-bottom: 10px;">
        <strong>หากไม่มารับเครื่องภายใน 15 วันนับจากวันที่นัดรับ</strong> และไม่สามารถติดต่อได้ 
        ทางร้านขอสงวนสิทธิ์ในการจัดเก็บสินค้าโดยไม่รับผิดชอบต่อความเสียหายที่อาจเกิดขึ้น 
        ทั้งนี้ ทางร้านจะพยายามติดต่อท่านล่วงหน้าก่อนเสมอ
      </li>
      <li style="margin-bottom: 10px;">
        กรณีที่ต้องมีการส่งสินค้าไปยังศูนย์บริการ หรือร้านคู่ค้า ทางร้านจะทำหน้าที่เป็นตัวกลางในการประสานงาน 
        โดยไม่รับผิดในความล่าช้าหรือข้อกำหนดจากศูนย์บริการนั้น ซึ่งลูกค้าจะได้รับแจ้งก่อนทุกครั้ง
      </li>
    </ol>
    <p style="text-align:left; margin-top:10px;">
      <strong>โปรดอ่านเงื่อนไขข้างต้นก่อนยืนยันการส่งซ่อม หากมีข้อสงสัย กรุณาสอบถามเจ้าหน้าที่ก่อนทุกครั้ง</strong><br>
      การส่งเครื่องเข้ารับบริการต่อเจ้าหน้าที่ ถือเป็นการยินยอมตามเงื่อนไขนี้แล้ว
    </p>
  </div>

  <div class="border-top"></div>
  <p style="text-align:center">ขอบคุณที่ใช้บริการ</p>
  <p style="text-align:center">สอบถามเพิ่มเติม: 044-315-949</p>
</body>
<script>
  // ปิดหน้าต่างทันทีหลังจากพิมพ์ หรือกดยกเลิกการพิมพ์
  window.onafterprint = () => {
    window.close();
  };

  // กันกรณีบาง Browser ไม่รองรับ onafterprint
  setTimeout(() => {
    window.close();
  }, 5000); // ปิดอัตโนมัติใน 5 วินาที (กันหลุด)
</script>
</html>