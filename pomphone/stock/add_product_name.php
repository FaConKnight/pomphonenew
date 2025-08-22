<?php
// /backend1/stock/add_product_name.php 

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

// โหลดหมวดหมู่
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
$brands = $pdo->query("SELECT id, name FROM brands ORDER BY name ASC")->fetchAll();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $category_id = $_POST['category_id'] ?? null;
    $brand_id = $_POST['brand_id'] ?? null;
    $is_trackable = $_POST['is_trackable'] ?? 0;

    if ($name === '' || !$category_id ) {
        $error = "\u274c กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        $stmt = $pdo->prepare("INSERT INTO products (name, sku, category_id, brand_id, is_trackable, created_at, updated_at)
                               VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
        if ($stmt->execute([$name, $sku, $category_id, $brand_id, $is_trackable])) {
            $success = "\u2705 เพิ่มสินค้าเรียบร้อยแล้ว";
        } else {
            $error = "\u274c ไม่สามารถเพิ่มสินค้าได้";
        }
    }
}
?>
<main>
<div class="page-container">
    <div class="main-content">
        <div class="section__content section__content--p30">
            <div class="container-fluid">
                <h3 class="mb-4">เพิ่มรายการสินค้า</h3>

                <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

                <form method="POST" class="form-horizontal" onsubmit="return confirm('ยืนยันการบันทึก?');">
                    <div class="form-group">
                        <label>ชื่อสินค้า</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>SKU (ถ้ามี)</label>
                        <input type="text" name="sku" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>หมวดหมู่</label>
                        <select name="category_id" class="form-control" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= safe_text($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>แบรนด์สินค้า</label>
                        <select name="brand_id" class="form-control" >
                            <option value="">-- เลือกแบรนด์ --</option>
                            <?php foreach ($brands as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= safe_text($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_trackable" value="1" id="is_trackable">
                        <label class="form-check-label" for="is_trackable">เป็นสินค้าที่ต้องติดตามรายเครื่อง (เช่น มือถือ)</label>
                    </div>
                    <button type="submit" class="btn btn-primary" >บันทึกสินค้า</button>
                </form>
            </div>
        </div>
    </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
