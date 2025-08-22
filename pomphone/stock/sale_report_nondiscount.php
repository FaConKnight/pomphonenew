<?php
// /manager/sale_report.php - รายงานยอดขายรายวัน รายเดือน รายพนักงาน + รวมทุน/กำไร + ยอดขายสินค้าเด่น + รายได้จากออมมือถือ + ช่องทางยอดนิยม

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

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

    $stmt3 = $pdo->prepare("SELECT si.qty, si.price, si.cost_price, p.name, p.category_id FROM sale_items si LEFT JOIN products p ON si.product_id = p.id WHERE sale_id = ?");
    $stmt3->execute([$s['id']]);
    foreach ($stmt3->fetchAll() as $i) {
        $cost = $i['qty'] * $i['cost_price'];
        $profit = ($i['price'] - $i['cost_price']) * $i['qty'];
        $total_cost += $cost;
        $total_profit += $profit;

        $pname = $i['name'] ?? 'ไม่ทราบชื่อ';
        $cat_id = $i['category_id'] ?? null;
        if (!isset($by_product[$pname])) {
            $by_product[$pname] = ['amount' => 0, 'qty' => 0, 'category_id' => $cat_id];
        }
        $by_product[$pname]['amount'] += $i['qty'] * $i['price'];
        $by_product[$pname]['qty'] += $i['qty'];

    }
}

// รายได้จากออมมือถือ
$stmtSaving = $pdo->prepare("SELECT SUM(amount) FROM saving_payments WHERE DATE(payment_date) BETWEEN ? AND ? ");
$stmtSaving->execute([$start_date, $end_date]);
$saving_income = $stmtSaving->fetchColumn() ?: 0;
?>
<main>
<div class="main-content">
    <div class="section__content section__content--p30">
        <div class="container-fluid">
            <h3 class="mb-4">รายงานยอดขาย(ไม่หักส่วนลด) <a href="sale_report.php" style="font-size: 10px" >แบบหักส่วนลด</a></h3> 
            <form class="form-inline mb-4" method="GET">
                <label class="mr-2">จากวันที่:</label>
                <input type="date" name="start_date" class="form-control mr-3" value="<?= $start_date ?>">
                <label class="mr-2">ถึงวันที่:</label>
                <input type="date" name="end_date" class="form-control mr-3" value="<?= $end_date ?>">
                <button class="btn btn-primary">ดูรายงาน</button>
            </form>

            <div class="mb-4">
                <h5>ยอดขายรวม: <?= number_format($total_sum?? 0, 2) ?> บาท</h5>
                <ul>
                    <li>เงินสด: <?= number_format($by_method['cash']?? 0, 2) ?> บาท</li>
                    <li>เงินโอน: <?= number_format($by_method['transfer']?? 0, 2) ?> บาท</li>
                    <li>สินเชื่อ: <?= number_format($by_method['credit']?? 0, 2) ?> บาท</li>
                    <?php if ($show_cost): ?>
                        <li>ต้นทุนรวม: <?= number_format($total_cost?? 0, 2) ?> บาท</li>
                        <li>กำไรรวม: <?= number_format($total_profit?? 0, 2) ?> บาท</li>
                    <?php endif; ?>
                    <li>รายได้จากออมมือถือ: <?= number_format($saving_income?? 0, 2) ?> บาท</li>
                </ul>
            </div>
            <?php
                // ดึงหมวดหมู่ทั้งหมด
                $categories = $pdo->query("SELECT id, name FROM categories")->fetchAll();
                $selected_category = $_GET['category'] ?? '';
                ?>
                <form method="get" class="form-inline mb-3">
                    <input type="hidden" name="start_date" value="<?= $start_date ?>">
                    <input type="hidden" name="end_date" value="<?= $end_date ?>">

                    <label class="mr-2">แสดง Top 10 ตาม:</label>
                    <select name="product_sort" class="form-control mr-2" onchange="this.form.submit()">
                        <option value="amount" <?= ($_GET['product_sort'] ?? 'amount') === 'amount' ? 'selected' : '' ?>>ยอดขายรวม (บาท)</option>
                        <option value="qty" <?= ($_GET['product_sort'] ?? '') === 'qty' ? 'selected' : '' ?>>จำนวนชิ้น</option>
                    </select>

                    <label class="mr-2">หมวดหมู่:</label>
                    <select name="category" class="form-control mr-2" onchange="this.form.submit()">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $selected_category == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>


            <h5>สินค้าขายดี (Top 10)</h5>
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>สินค้า</th>
                        <th>ยอดขายรวม (บาท)</th>
                        <th>จำนวนชิ้น</th>
                    </tr>
                </thead>
                <?php
                    $sort_type = $_GET['product_sort'] ?? 'amount';

                    // สร้างอาเรย์ใหม่ก่อน
                    $by_product_items = [];
                    foreach ($by_product as $name => $data) {
                        if ($selected_category && $data['category_id'] != $selected_category) continue;
                        $by_product_items[] = [
                            'name' => $name,
                            'amount' => $data['amount'],
                            'qty' => $data['qty']
                        ];
                    }
                    // แล้วค่อยเรียก usort ปกติ
                    usort($by_product_items, function($a, $b) use ($sort_type) {
                        return $b[$sort_type] <=> $a[$sort_type];
                    });

                    $top = array_slice($by_product_items, 0, 10);
                ?>
                <tbody>
                    <?php foreach ($top as $p): ?>
                        <tr>
                            <td><?= safe_text($p['name']) ?></td>
                            <td><?= number_format($p['amount'], 2) ?> บาท</td>
                            <td><?= number_format($p['qty']) ?> ชิ้น</td>
                        </tr>
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
                            <td><?= number_format($amt?? 0, 2) ?></td>
                            <td><a href="sale_report_daily_nondiscount.php?date=<?= $d ?>" class="btn btn-info btn-sm" target="_blank">ดูรายการ</a></td>
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
                        <tr><td><?= safe_text($name) ?></td><td><?= number_format($amt?? 0, 2) ?> บาท</td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
