<?php
// sale_report.php - รายงานยอดขายเฉพาะวันนี้ + แสดงรายการสินค้าที่ขาย

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 11) {
    http_response_code(403);
    exit("Unauthorized");
}

$today = date('Y-m-d');

// ดึงรายการขายวันนี้
$stmt = $pdo->prepare("SELECT s.id, s.sale_time, s.final_amount, e.emd_name AS employee_name
    FROM sale s
    LEFT JOIN employee_details e ON s.employee_id = e.emd_id
    WHERE DATE(s.sale_time) = ?
    ORDER BY s.sale_time DESC");
$stmt->execute([$today]);
$sales = $stmt->fetchAll();

$total_sum = 0;
$by_method = ["cash" => 0, "transfer" => 0, "credit" => 0];
$all_items = [];

foreach ($sales as $s) {
    $total_sum += $s['final_amount'];

    // ช่องทางการชำระเงิน
    $stmt2 = $pdo->prepare("SELECT method, amount FROM sale_payment_methods WHERE sale_id = ?");
    $stmt2->execute([$s['id']]);
    $rows = $stmt2->fetchAll();
    foreach ($rows as $row) {
        $by_method[$row['method']] = ($by_method[$row['method']] ?? 0) + $row['amount'];
    }

    // รายการสินค้า
    $stmt3 = $pdo->prepare("SELECT si.*, p.name FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
    $stmt3->execute([$s['id']]);
    $items = $stmt3->fetchAll();
    foreach ($items as $it) {
        $key = $it['name'] . ($it['imei'] ? " (IMEI: " . $it['imei'] . ")" : "");
        if (!isset($all_items[$key])) {
            $all_items[$key] = ['qty' => 0, 'sum' => 0];
        }
        $all_items[$key]['qty'] += $it['qty'];
        $all_items[$key]['sum'] += $it['qty'] * $it['price']-$it['item_discount'];
    }
}
?>
<main>
<div class="main-content container mt-4">
    <h3>รายงานยอดขายประจำวันที่ <?= date('d/m/Y') ?></h3>
    <hr>
    <h5>รายการสินค้าที่ขายวันนี้</h5>
    <table class="table table-bordered table-sm">
        <thead><tr><th>ชื่อสินค้า</th><th>จำนวน</th><th>ยอดรวม</th></tr></thead>
        <tbody>
            <?php foreach ($all_items as $name => $data): ?>
                <tr>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td><?= $data['qty'] ?></td>
                    <td><?= number_format($data['sum'], 2) ?> บาท</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
