<?php
// receipt.php - แสดงใบเสร็จ พร้อมรองรับ Reprint และข้อความท้ายใบเสร็จอัจฉริยะ

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");

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
        body { font-family: Tahoma, sans-serif; font-size: 14px; max-width: 88mm; margin: auto; }
        h2, h4 { text-align: center; margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        td, th { padding: 4px 0; }
        .text-right { text-align: right; }
        .border-top { border-top: 1px dashed #000; }
        .footer-img { max-width: 100%; display: block; margin: 10px auto; }
    </style>
</head>
<body onload="window.print()">
    <h2>ร้านป้อมมือถือ</h2>
    <h4>ใบเสร็จรับเงิน<?= $is_reprint ? ' (พิมพ์ซ้ำ)' : '' ?></h4>
    <hr>
    <p>เลขที่ใบเสร็จ: <strong><?= htmlspecialchars($sale['receipt_no']) ?></strong><br>
       วันที่: <?= date('d/m/Y H:i', strtotime($sale['sale_time'])) ?><br>
       พนักงาน: <?= htmlspecialchars($sale['employee_name']) ?><br>
       ลูกค้า: <?= $sale['cua_name'] ? htmlspecialchars($sale['cua_name'] . ' ' . $sale['cua_lastname']) : 'เงินสดทั่วไป' ?></p>

    <table>
        <thead>
            <tr><th>สินค้า</th><th class="text-right">ราคา</th></tr>
        </thead>
        <tbody>
            <?php foreach ($items as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['product_name']) ?> <?= $row['imei'] ? '(IMEI: ' . htmlspecialchars($row['imei']) . ')' : '' ?> x<?= $row['qty'] ?></td>
                    <td class="text-right"><?= formatBaht($row['price'] * $row['qty']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <hr class="border-top">
    <table>
        <tr><td>ยอดรวม</td><td class="text-right"><?= formatBaht($sale['total']) ?></td></tr>
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
        <p>สินเชื่อ: <?= htmlspecialchars($sale['credit_provider']) ?></p>
    <?php endif; ?>

    <?php if ($is_reprint): ?>
        <hr>
        <p style="text-align:center; color:red">*** ใบเสร็จนี้เป็นฉบับพิมพ์ซ้ำ ***</p>
    <?php endif; ?>

    <?php if ($footer_text): ?>
        <hr>
        <p style="text-align:center; font-size:13px; white-space:pre-line">
            <?= htmlspecialchars($footer_text) ?>
        </p>
    <?php endif; ?>

    <?php if ($footer_image): ?>
        <img src="<?= htmlspecialchars($footer_image) ?>" class="footer-img" alt="โปรโมชั่น">
    <?php endif; ?>

    <hr>
    <p style="text-align:center">ขอบคุณที่ใช้บริการ</p>
</body>
</html>
