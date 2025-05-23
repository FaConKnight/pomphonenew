<?php
// /manager/sale_report.php - รายงานยอดขายรายวัน รายเดือน รายพนักงาน + รวมทุน/กำไร + ยอดขายสินค้าเด่น + รายได้จากออมมือถือ + ช่องทางยอดนิยม

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 88) {
    http_response_code(403);
    exit("Unauthorized");
}

$show_cost = $_SESSION['employee_rank'] >= 95;

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT s.id, s.sale_time, s.final_amount, e.emd_name AS employee_name
    FROM sale s
    LEFT JOIN employee_details e ON s.employee_id = e.emd_id
    WHERE DATE(s.sale_time) BETWEEN ? AND ?
    ORDER BY s.sale_time DESC");
$stmt->execute([$start_date, $end_date]);
$sales = $stmt->fetchAll();

$total_sum = 0;
$total_cost = 0;
$total_profit = 0;
$daily = [];
$by_employee = [];
$by_method = ["cash" => 0, "transfer" => 0, "credit" => 0];
$by_product = [];

foreach ($sales as $s) {
    $day = substr($s['sale_time'], 0, 10);
    $daily[$day] = ($daily[$day] ?? 0) + $s['final_amount'];
    $emp = $s['employee_name'] ?? 'ไม่ระบุ';
    $by_employee[$emp] = ($by_employee[$emp] ?? 0) + $s['final_amount'];
    $total_sum += $s['final_amount'];

    $stmt2 = $pdo->prepare("SELECT method, amount FROM sale_payment_methods WHERE sale_id = ?");
    $stmt2->execute([$s['id']]);
    foreach ($stmt2->fetchAll() as $row) {
        $by_method[$row['method']] = ($by_method[$row['method']] ?? 0) + $row['amount'];
    }

    $stmt3 = $pdo->prepare("SELECT si.qty, si.price, si.cost_price, p.name FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE sale_id = ?");
    $stmt3->execute([$s['id']]);
    foreach ($stmt3->fetchAll() as $i) {
        $cost = $i['qty'] * $i['cost_price'];
        $profit = ($i['price'] - $i['cost_price']) * $i['qty'];
        $total_cost += $cost;
        $total_profit += $profit;

        $pname = $i['name'] ?? 'ไม่ทราบชื่อ';
        $by_product[$pname] = ($by_product[$pname] ?? 0) + ($i['qty'] * $i['price']);
    }
}

// รายได้จากออมมือถือ
$stmtSaving = $pdo->prepare("SELECT SUM(amount) FROM saving_payments WHERE DATE(payment_date) BETWEEN ? AND ? ");
$stmtSaving->execute([$start_date, $end_date]);
$saving_income = $stmtSaving->fetchColumn() ?: 0;
?>
<div class="main-content">
    <div class="section__content section__content--p30">
        <div class="container-fluid">
            <h3 class="mb-4">รายงานยอดขาย</h3>
            <form class="form-inline mb-4" method="GET">
                <label class="mr-2">จากวันที่:</label>
                <input type="date" name="start_date" class="form-control mr-3" value="<?= $start_date ?>">
                <label class="mr-2">ถึงวันที่:</label>
                <input type="date" name="end_date" class="form-control mr-3" value="<?= $end_date ?>">
                <button class="btn btn-primary">ดูรายงาน</button>
            </form>

            <div class="mb-4">
                <h5>ยอดขายรวม: <?= number_format($total_sum, 2) ?> บาท</h5>
                <ul>
                    <li>เงินสด: <?= number_format($by_method['cash'], 2) ?> บาท</li>
                    <li>เงินโอน: <?= number_format($by_method['transfer'], 2) ?> บาท</li>
                    <li>สินเชื่อ: <?= number_format($by_method['credit'], 2) ?> บาท</li>
                    <?php if ($show_cost): ?>
                        <li>ต้นทุนรวม: <?= number_format($total_cost, 2) ?> บาท</li>
                        <li>กำไรรวม: <?= number_format($total_profit, 2) ?> บาท</li>
                    <?php endif; ?>
                    <li>รายได้จากออมมือถือ: <?= number_format($saving_income, 2) ?> บาท</li>
                </ul>
            </div>

            <h5>สินค้าขายดี (Top 10)</h5>
            <table class="table table-bordered table-sm">
                <thead><tr><th>สินค้า</th><th>ยอดขายรวม (บาท)</th></tr></thead>
                <tbody>
                    <?php arsort($by_product); $top = array_slice($by_product, 0, 10); ?>
                    <?php foreach ($top as $name => $amt): ?>
                        <tr><td><?= htmlspecialchars($name) ?></td><td><?= number_format($amt, 2) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <h5 class="mt-4">ยอดขายรายวัน</h5>
            <table class="table table-bordered table-striped">
                <thead class="thead-light"><tr><th>วันที่</th><th>ยอดขาย (บาท)</th><th>รายละเอียด</th></tr></thead>
                <tbody>
                    <?php foreach ($daily as $d => $amt): ?>
                        <tr>
                            <td><?= $d ?></td>
                            <td><?= number_format($amt, 2) ?></td>
                            <td><a href="sale_report_daily.php?date=<?= $d ?>" class="btn btn-info btn-sm" target="_blank">ดูรายการ</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <hr>
            <h5>ยอดขายรายพนักงาน</h5>
            <table class="table table-bordered table-sm">
                <thead><tr><th>พนักงาน</th><th>ยอดขายรวม</th></tr></thead>
                <tbody>
                    <?php foreach ($by_employee as $name => $amt): ?>
                        <tr><td><?= htmlspecialchars($name) ?></td><td><?= number_format($amt, 2) ?> บาท</td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once("../partials/footer.php"); ?>
