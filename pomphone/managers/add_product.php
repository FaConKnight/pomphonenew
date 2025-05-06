<?php
// /cooladmin/manager/add_product.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
//require_once('../includes/session.php'); // เช็ค session employee login


$page_title = "เพิ่มสินค้าเข้าสต๊อก";
$success = null;

// ดึงรายการสินค้าในระบบทั้งหมด
$product_stmt = $pdo->query("SELECT id, name FROM products ORDER BY name ASC");
$product_list = $product_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $is_trackable = $_POST['is_trackable'] ?? 0;
    $cost_price = $_POST['cost_price'] ?? null;
    $sell_price = $_POST['sell_price'] ?? null;
    $wholesale_price = $_POST['wholesale_price'] ?? null;
    $quantity = $_POST['quantity'] ?? 0;
    $imei_list = $_POST['imei_list'] ?? [];
    $employee_id = $_SESSION['employee_id'] ?? 0;

    if (!$product_id || !$cost_price || !$sell_price) {
        $success = "\u274c กรุณากรอกข้อมูลให้ครบ";
    } else {
        try {
            $pdo->beginTransaction();

            if ($is_trackable == 0) {
                // ไม่ track imei: เพิ่มสินค้าทั่วไปแบบจำนวน
                for ($i = 0; $i < $quantity; $i++) {
                    $stmt = $pdo->prepare("INSERT INTO product_items (product_id, cost_price, sell_price, wholesale_price, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'in_stock', NOW(), NOW())");
                    $stmt->execute([$product_id, $cost_price, $sell_price, $wholesale_price]);
                }
            } else {
                // track imei: เพิ่มมือถือรายเครื่อง
                foreach ($imei_list as $imei) {
                    $imei = trim($imei);
                    if ($imei === '') continue;

                    $stmt = $pdo->prepare("INSERT INTO product_items (product_id, imei1, cost_price, sell_price, wholesale_price, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'in_stock', NOW(), NOW())");
                    $stmt->execute([$product_id, $imei, $cost_price, $sell_price, $wholesale_price]);
                }
            }

            // เพิ่ม log
            $log = $pdo->prepare("INSERT INTO stock_logs (product_item_id, action, quantity, employee_id, remark, created_at) VALUES (?, 'in', ?, ?, ?, NOW())");
            $log->execute([0, $quantity, $employee_id, "เพิ่มสินค้าผ่านระบบ"]);

            $pdo->commit();
            $success = "เพิ่มสินค้าสำเร็จแล้ว!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $success = "\u274c เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
}
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
    <div class="main-content">
        <div class="section__content section__content--p30">
            <div class="container-fluid">
                <h3 class="mb-4">เพิ่มสินค้าเข้าสต๊อก</h3>

                <?php if ($success): ?>
                    <div class="alert alert-<?php echo (str_contains($success, 'สำเร็จ') ? 'success' : 'danger'); ?>">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-horizontal">
                    <div class="form-group">
                        <label>เลือกสินค้า</label>
                        <select name="product_id" class="form-control" required>
                            <option value="">-- เลือกสินค้า --</option>
                            <?php foreach ($product_list as $p): ?>
                                <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_trackable" id="is_trackable">
                        <label class="form-check-label" for="is_trackable">เป็นสินค้าที่ติดตามรายเครื่อง (มือถือ)</label>
                    </div>

                    <div class="form-group">
                        <label>ราคาทุน</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control">
                            <button type="button" class="btn btn-secondary" onclick="addVAT()">+ VAT 7%</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>ราคาขาย</label>
                        <input type="number" step="0.01" name="sell_price" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>ราคาขายส่ง</label>
                        <input type="number" step="0.01" name="wholesale_price" class="form-control">
                    </div>

                    <div class="form-group" id="quantity_block">
                        <label>จำนวนสินค้า (ไม่ใช่มือถือ)</label>
                        <input type="number" name="quantity" class="form-control">
                    </div>

                    <div class="form-group" id="imei_block" style="display:none">
                        <label>รายการ IMEI (มือถือ)</label>
                        <textarea name="imei_list[]" class="form-control" rows="5" placeholder="กรอก IMEI แยกบรรทัดละ 1 ตัว"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">บันทึกสินค้าเข้าสต๊อก</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function addVAT() {
    let costField = document.getElementById("cost_price");
    let cost = parseFloat(costField.value);
    if (!isNaN(cost)) {
        costField.value = (cost * 1.07).toFixed(2);
    }
}

document.getElementById('is_trackable').addEventListener('change', function () {
    let isChecked = this.checked;
    document.getElementById('imei_block').style.display = isChecked ? 'block' : 'none';
    document.getElementById('quantity_block').style.display = isChecked ? 'none' : 'block';
});
</script>

<?php include_once('../partials/footer.php'); ?>
