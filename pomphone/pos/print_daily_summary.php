<?php
// print_daily_summary.php - พิมพ์สรุปยอดขายแบบย่อ (เฉพาะยอดเงินวันนี้)

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['employee_id']) || $_GET['key'] != true) {
    http_response_code(403);
    exit("Unauthorized");
}

$today = date('Y-m-d');

// 1. ดึงรายการ sale_id ทั้งหมดของวันนี้
$stmt = $pdo->prepare("SELECT s.id FROM sale s WHERE DATE(s.sale_time) = ?");
$stmt->execute([$today]);
$sale_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

// 2. คำนวณยอดตามช่องทางการชำระ
$by_method = ["cash" => 0, "transfer" => 0, "credit" => 0];

foreach ($sale_ids as $id) {
    $stmt2 = $pdo->prepare("SELECT method, amount FROM sale_payment_methods WHERE sale_id = ?");
    $stmt2->execute([$id]);
    foreach ($stmt2->fetchAll() as $row) {
        $by_method[$row['method']] = ($by_method[$row['method']] ?? 0) + $row['amount'];
    }
}

// 3. รวมยอดเงินทอนของวันนี้
$stmt = $pdo->prepare("SELECT SUM(change_amount) FROM sale WHERE DATE(sale_time) = ?");
$stmt->execute([$today]);
$total_change = floatval($stmt->fetchColumn());

// 4. คำนวณเงินสดหลังหักเงินทอน
$cash_after_change = $by_method['cash'] - $total_change;
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สรุปยอดขายวันนี้</title>
    <style>
        body { font-family: Tahoma, sans-serif; font-size: 16px; max-width: 88mm; margin: auto; }
        h3, p { text-align: center; margin: 4px 0; }
        ul { list-style: none; padding: 0; }
        li { margin: 4px 0; }
        .sub { font-size: 14px; color: #666; margin-left: 10px; }
    </style>
</head>
<body onload="handlePrintClose()">
<script>
function handlePrintClose() {
    window.print();
    setTimeout(function () {
        window.close();
    }, 1000);
}
</script>

    <h3>สรุปยอดขาย</h3>
    <p>ประจำวันที่ <?= date('d/m/Y') ?></p>
    <hr>
    <ul>
        <li>เงินสดรวม: <?= number_format($by_method['cash'], 2) ?> บาท</li>
        <li class="sub">- เงินทอน: <?= number_format($total_change, 2) ?> บาท</li>
        <li><strong>เงินสดสุทธิในลิ้นชัก: <?= number_format($cash_after_change, 2) ?> บาท</strong></li>
        <hr>
        <li>เงินโอน: <?= number_format($by_method['transfer'], 2) ?> บาท</li>
        <li>สินเชื่อ: <?= number_format($by_method['credit'], 2) ?> บาท</li>
    </ul>
    <hr>
    <p>ขอบคุณครับ</p>
</body>
</html>
