<?php
// /cooladmin/manager/add_product.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
$page_title = "เพิ่มสินค้าใหม่";

$success = null;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = $_POST['name'] ?? '';
    $sku = $_POST['sku'] ?? null;
    $category_id = $_POST['category_id'] ?? null;
    $is_trackable = isset($_POST['is_trackable']) ? 1 : 0;
    $cost_price = $_POST['cost_price'] ?? null;
    $sell_price = $_POST['sell_price'] ?? null;
    $wholesale_price = $_POST['wholesale_price'] ?? null;

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO products 
            (name, sku, category_id, is_trackable, cost_price, sell_price, wholesale_price, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        $stmt->execute([$name, $sku, $category_id, $is_trackable, $cost_price, $sell_price, $wholesale_price]);
        $success = "เพิ่มสินค้า \"$name\" สำเร็จแล้ว!";
    } else {
        $success = "\u274c กรุณากรอกชื่อสินค้า";
    }
}
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
    <?php include_once('../partials/header.php'); ?>

    <div class="main-content">
        <div class="section__content section__content--p30">
            <div class="container-fluid">
                <h3 class="mb-4">เพิ่มสินค้าใหม่</h3>

                <?php if ($success): ?>
                    <div class="alert alert-<?php echo (str_contains($success, 'สำเร็จ') ? 'success' : 'danger'); ?>">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-horizontal">
                    <div class="form-group">
                        <label>ชื่อสินค้า</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>SKU / Barcode</label>
                        <input type="text" name="sku" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>ประเภทสินค้า (Category ID)</label>
                        <input type="number" name="category_id" class="form-control">
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_trackable" id="is_trackable">
                        <label class="form-check-label" for="is_trackable">เป็นสินค้าที่ติดตามรายเครื่อง (มือถือ)</label>
                    </div>

                    <div class="form-group">
                        <label>ราคาทุน</label>
                        <input type="number" name="cost_price" step="0.01" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>ราคาขาย</label>
                        <input type="number" name="sell_price" step="0.01" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>ราคาขายส่ง</label>
                        <input type="number" name="wholesale_price" step="0.01" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-primary mt-3">บันทึกสินค้า</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once('../partials/footer.php'); ?>
