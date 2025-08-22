<?php
// receipt.php - แสดงใบเสร็จ พร้อมรองรับ Reprint และข้อความท้ายใบเสร็จอัจฉริยะ

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

$sale_id = intval($_GET['sale_id'] ?? 0);
$is_reprint = isset($_GET['reprint']) && $_GET['reprint'] == 1;

if ($sale_id <= 0) {
    exit("ไม่พบข้อมูลใบเสร็จ");
}

// ดึงข้อมูลบิล
$stmt = $pdo->prepare("SELECT s.*, e.emd_name AS employee_name, c.cua_name, c.cua_lastname, c.cua_tel FROM sale s
    LEFT JOIN employee_details e ON s.employee_id = e.emd_ea
    LEFT JOIN customer_account c ON s.customer_id = c.cua_id
    WHERE s.id = ? LIMIT 1");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    exit("ไม่พบข้อมูลใบเสร็จ");
}

// รายการสินค้า
$stmt = $pdo->prepare("SELECT si.*, p.name AS product_name FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();

// ช่องทางชำระเงิน
$stmt = $pdo->prepare("SELECT method, amount FROM sale_payment_methods WHERE sale_id = ?");
$stmt->execute([$sale_id]);
$rows = $stmt->fetchAll();
$payments = [];
foreach ($rows as $row) {
    $payments[$row['method']][] = $row['amount'];
}
// คำนวณยอดรวมที่รับมา
$total_paid = 0;
foreach ($payments as $method => $list) {
    foreach ($list as $amt) {
        $total_paid += floatval($amt);
    }
}
// คำนวณเงินทอน
$change = $total_paid - $sale['final_amount'];

function formatBaht($amount) {
    return number_format($amount, 2) . ' บาท';
}

// ค้นหาข้อความท้ายใบเสร็จแบบฉลาด + รูปภาพ (ถ้ามี)
$footer_text = "";
$footer_image = null;
$stmt = $pdo->prepare("SELECT message, image_url FROM receipt_footer_rules WHERE min_amount <= ? ORDER BY min_amount DESC LIMIT 1");
$stmt->execute([$sale['final_amount']]);
if ($row = $stmt->fetch()) {
    $footer_text = $row['message'];
    $footer_image = $row['image_url'] ?? null;
}

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จ</title>
    <style>
        body { font-family: Tahoma, sans-serif; font-size: 14px; max-width: 80mm; margin: 0; }
        h2, h4 { text-align: center; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 1px; }
        td, th { padding: 4px 0; }
        .text-right { text-align: right; }
        .border-top { border-top: 1px dashed #000; }
        .footer-img { max-width: 100%; display: block; margin: 10px auto; }
        @media print {
  @page {
    margin-top: 0mm;       /* ไม่มีขอบด้านบน */
    margin-left: 3mm;      /* ซ้าย */
    margin-right: 3mm;     /* ขวา */
    margin-bottom: 5mm;    /* ล่าง (เผื่อไว้สำหรับ footer) */
  }
    </style>
</head>
<body onload="window.print()">
    <h2>ร้านป้อมมือถือ</h2>
    <h4>ใบเสร็จรับเงิน<?= $is_reprint ? ' (พิมพ์ซ้ำ)' : '' ?></h4>
    <hr>
    <p>เลขที่ใบเสร็จ: <strong><?= safe_text(getPublicCode($sale['fullref'])) ?></strong><br>
       วันที่: <?= safe_date($sale['sale_time']) ?><br>
       พนักงาน: <?= safe_text($sale['employee_name']) ?><br>
       ลูกค้า: <?= $sale['cua_name'] ? safe_text($sale['cua_name'] . ' ' . $sale['cua_lastname']) : 'เงินสดทั่วไป' ?></p>

    <table>
        <thead>
            <tr><th>สินค้า</th><th class="text-right">ราคา</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $row): 
                    $discount = floatval($row['item_discount'] ?? 0);
                ?>
                <tr>
                    <td><?= safe_text($row['product_name']) ?> <?= $row['imei'] ? '(IMEI: ' . safe_text($row['imei']) . ')' : '' ?> x<?= $row['qty'] ?></td>
                    <td class="text-right"><?= '฿' . ($row['price']-$discount)* $row['qty']   ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr class="border-top">
    <table>
        <tr><td>ราคาก่อนส่วนลด</td><td class="text-right"><?= formatBaht($sale['total']) ?></td></tr>
        <tr><td>ส่วนลด</td><td class="text-right"><?= formatBaht($sale['discount']) ?></td></tr>
        <tr><td><strong>ยอดสุทธิ</strong></td><td class="text-right"><strong><?= formatBaht($sale['final_amount']) ?></strong></td></tr>
    </table>
    <hr>
    <p><strong>ช่องทางชำระเงิน:</strong></p>
    <ul>
        <?php foreach ($payments as $method => $list): ?>
            <?php foreach ($list as $amt): ?>
                <li><?= ucfirst($method) ?>: <?= formatBaht($amt) ?></li>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>

    <?php if (!empty($sale['credit_provider'])): ?>
        <p>สินเชื่อ: <?= safe_text($sale['credit_provider']) ?></p>
    <?php endif; ?>
    <?php if ($change > 0): ?>
        <table><tr><td>เงินทอน</td><td class="text-right"><?= formatBaht($change) ?></td></tr></table>
    <?php endif; ?>
    <?php if ($is_reprint): ?>
        <hr>
        <p style="text-align:center; color:red">*** ใบเสร็จนี้เป็นฉบับพิมพ์ซ้ำ ***</p>
    <?php endif; ?>

    <?php if ($footer_text): ?>
        <hr>
        <p style="text-align:center; font-size:13px; white-space:pre-line">
            <?= safe_text($footer_text) ?>
        </p>
    <?php endif; ?>

    <?php if ($footer_image): ?>
        <img src="<?= safe_text($footer_image) ?>" class="footer-img" alt="โปรโมชั่น">
    <?php endif; ?>

    <hr>
    <p style="text-align:center">ขอบคุณที่ใช้บริการ</p>
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
