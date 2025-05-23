<?php
// /manager/sale_report_daily.php - รายละเอียดการขายรายวัน พร้อมราคาทุนและกำไร + รวมยอดด้านล่าง + แยกตามหมวดสินค้า

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
$date = $_GET['date'] ?? date('Y-m-d');
$category_id = $_GET['category_id'] ?? '';

// ดึงหมวดสินค้า
$cat_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll();

// ดึงรายการขาย + เงื่อนไขหมวด
$sql = "SELECT s.id, s.sale_time, e.emd_name, si.qty, si.price, si.imei, si.cost_price, p.name
        FROM sale s
        LEFT JOIN sale_items si ON s.id = si.sale_id
        LEFT JOIN products p ON si.product_id = p.id
        LEFT JOIN employee_details e ON s.employee_id = e.emd_id
        WHERE DATE(s.sale_time) = :sale_date";

if ($category_id !== '') {
    $sql .= " AND p.category_id = :cat_id";
}

$sql .= " ORDER BY s.sale_time DESC";
$stmt = $pdo->prepare($sql);
$params = [':sale_date' => $date];
if ($category_id !== '') {
    $params[':cat_id'] = $category_id;
}
$stmt->execute($params);
$items = $stmt->fetchAll();

$total_qty = 0;
$total_amount = 0;
$total_cost = 0;
$total_profit = 0;
?>
<div class="main-content">
    <div class="section__content section__content--p30">
        <div class="container-fluid">
            <h3 class="mb-4">รายการสินค้าที่ขายเมื่อวันที่ <?= htmlspecialchars($date) ?></h3>

            <form method="GET" class="form-inline mb-3">
                <input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
                <label class="mr-2">เลือกหมวดสินค้า:</label>
                <select name="category_id" class="form-control mr-2">
                    <option value="">-- แสดงทุกหมวด --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $category_id ? 'selected' : '') ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary">กรอง</button>
            </form>

            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>เวลา</th>
                        <th>สินค้า</th>
                        <th>IMEI</th>
                        <th>จำนวน</th>
                        <th>ราคา/ชิ้น</th>
                        <?php if ($show_cost): ?>
                            <th>ทุน/ชิ้น</th>
                            <th>กำไร/ชิ้น</th>
                        <?php endif; ?>
                        <th>รวม</th>
                        <th>พนักงาน</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $row): 
                        $subtotal = $row['qty'] * $row['price'];
                        $cost_total = $row['qty'] * $row['cost_price'];
                        $profit_total = $subtotal - $cost_total;
                        $total_qty += $row['qty'];
                        $total_amount += $subtotal;
                        $total_cost += $cost_total;
                        $total_profit += $profit_total;
                    ?>
                        <tr>
                            <td><?= date('H:i', strtotime($row['sale_time'])) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['imei']) ?></td>
                            <td><?= $row['qty'] ?></td>
                            <td><?= number_format($row['price'], 2) ?></td>
                            <?php if ($show_cost): ?>
                                <td><?= number_format($row['cost_price'], 2) ?></td>
                                <td><?= number_format($row['price'] - $row['cost_price'], 2) ?></td>
                            <?php endif; ?>
                            <td><?= number_format($subtotal, 2) ?></td>
                            <td><?= htmlspecialchars($row['emd_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="font-weight-bold">
                    <tr>
                        <td colspan="3" class="text-right">รวมทั้งหมด</td>
                        <td><?= $total_qty ?></td>
                        <td></td>
                        <?php if ($show_cost): ?>
                            <td></td>
                            <td></td>
                        <?php endif; ?>
                        <td><?= number_format($total_amount, 2) ?></td>
                        <td></td>
                    </tr>
                    <?php if ($show_cost): ?>
                    <tr>
                        <td colspan="7" class="text-right">รวมต้นทุน:</td>
                        <td colspan="2"><?= number_format($total_cost, 2) ?> บาท</td>
                    </tr>
                    <tr>
                        <td colspan="7" class="text-right">รวมกำไร:</td>
                        <td colspan="2"><?= number_format($total_profit, 2) ?> บาท</td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    </div>
</div>
<?php include_once("../partials/footer.php"); ?>
