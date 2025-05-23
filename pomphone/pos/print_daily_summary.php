<?php
// print_daily_summary.php - พิมพ์สรุปยอดขายแบบย่อ (เฉพาะยอดเงินวันนี้)

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 88) {
    http_response_code(403);
    exit("Unauthorized");
}

$today = date('Y-m-d');

$stmt = $pdo->prepare("SELECT s.id FROM sale s WHERE DATE(s.sale_time) = ?");
$stmt->execute([$today]);
$sale_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$by_method = ["cash" => 0, "transfer" => 0, "credit" => 0];

foreach ($sale_ids as $id) {
    $stmt2 = $pdo->prepare("SELECT method, amount FROM sale_payment_methods WHERE sale_id = ?");
    $stmt2->execute([$id]);
    foreach ($stmt2->fetchAll() as $row) {
        $by_method[$row['method']] = ($by_method[$row['method']] ?? 0) + $row['amount'];
    }
}
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
    </style>
</head>
<body onload="handlePrintClose()">
<script>
function handlePrintClose() {
    window.print();
    // รอให้ dialog print เสร็จ → แล้วค่อยปิด
    setTimeout(function() {
        window.close();
    }, 1000); // รอ 1 วินาที (พอดีกับบาง browser)
}
</script>
    <h3>สรุปยอดขาย</h3>
    <p>ประจำวันที่ <?= date('d/m/Y') ?></p>
    <hr>
    <ul>
        <li>เงินสด: <?= number_format($by_method['cash'], 2) ?> บาท</li>
        <li>เงินโอน: <?= number_format($by_method['transfer'], 2) ?> บาท</li>
        <li>สินเชื่อ: <?= number_format($by_method['credit'], 2) ?> บาท</li>
    </ul>
    <hr>
    <p>ขอบคุณครับ</p>
</body>
</html>
