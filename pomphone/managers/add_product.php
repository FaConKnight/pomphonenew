<?php
// /cooladmin/manager/add_product.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php'); // เช็ค session employee login

$page_title = "เพิ่มสินค้าเข้าสต๊อก";
$success = $_GET['success'] ?? null;

// ดึงหมวดหมู่สินค้า
$category_stmt = $pdo->query("SELECT id, name FROM categories ORDER BY id ASC");
$category_list = $category_stmt->fetchAll();

// ดึงรายชื่อบริษัท (supplier)
$supplier_stmt = $pdo->query("SELECT id, name_th FROM suppliers ORDER BY name_th ASC");
$supplier_list = $supplier_stmt->fetchAll();
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

                <form method="POST" action="../includes/save_product.php" class="form-horizontal" id="addProductForm">
                    <div class="form-group">
                        <label>หมวดหมู่สินค้า</label>
                        <select name="category_id" id="category_id" class="form-control" required>
                            <option value="">-- เลือกหมวดหมู่ --</option>
                            <?php foreach ($category_list as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>เลือกสินค้า</label>
                        <select name="product_id" id="product_id" class="form-control" required disabled>
                            <option value="">-- เลือกสินค้า --</option>
                        </select>
                    </div>

                    <div id="sku_section" style="display: none;">
                        <div class="form-group">
                            <label>SKU/Barcode</label>
                            <input type="text" id="sku_display" class="form-control" readonly>
                        </div>
                    </div>

                    <div class="form-group">
                      <label for="supplier_id">บริษัทที่มาของสินค้า</label>
                      <select name="supplier_id" id="supplier_id" class="form-control" required>
                        <option value="">-- เลือกบริษัท --</option>
                        <?php foreach ($supplier_list as $s): ?>
                          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name_th']) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>

                    <div id="price_section" style="display: none;">
                        <div class="form-group">
                            <label>ราคาทุน</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="cost_price" id="cost_price" class="form-control">
                                <button type="button" class="btn btn-secondary" onclick="addVAT()">+ VAT 7%</button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ราคาขาย</label>
                            <input type="number" step="0.01" name="sell_price" id="sell_price" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>ราคาขายส่ง</label>
                            <input type="number" step="0.01" name="wholesale_price" id="wholesale_price" class="form-control">
                        </div>
                    </div>

                    <div class="form-group" id="quantity_block" style="display: none;">
                        <label>จำนวนสินค้า (ไม่ใช่มือถือ)</label>
                        <input type="number" name="quantity" class="form-control">
                    </div>

                    <div class="form-group" id="imei_block" style="display:none">
                        <label>รายการ IMEI (มือถือ)</label>
                        <textarea name="imei_list" class="form-control" rows="5" placeholder="กรอก IMEI แยกบรรทัดละ 1 ตัว"></textarea>
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

document.getElementById('category_id').addEventListener('change', function () {
    const categoryId = this.value;
    const productSelect = document.getElementById('product_id');

    productSelect.innerHTML = '<option value="">-- กำลังโหลดสินค้า... --</option>';
    productSelect.disabled = true;

    fetch('../includes/get_products_by_category.php?category_id=' + categoryId)
        .then(response => response.json())
        .then(data => {
            productSelect.innerHTML = '<option value="">-- เลือกสินค้า --</option>';
            data.forEach(product => {
                const opt = document.createElement('option');
                opt.value = product.id;
                opt.textContent = product.name;
                opt.dataset.trackable = product.is_trackable;
                opt.dataset.sku = product.sku;
                productSelect.appendChild(opt);
            });
            productSelect.disabled = false;
        });
});

document.getElementById('product_id').addEventListener('change', function () {
    const selected = this.options[this.selectedIndex];
    const productId = selected.value;
    const isTrackable = selected.dataset.trackable === '1';

    document.getElementById('imei_block').style.display = isTrackable ? 'block' : 'none';
    document.getElementById('quantity_block').style.display = isTrackable ? 'none' : 'block';

    if (!isTrackable) {
        document.getElementById('sku_section').style.display = 'block';
        document.getElementById('sku_display').value = selected.dataset.sku;
    } else {
        document.getElementById('sku_section').style.display = 'none';
        document.getElementById('sku_display').value = '';
    }

    fetch('../includes/get_latest_price.php?product_id=' + productId + '&trackable=' + (isTrackable ? '1' : '0'))
        .then(response => response.json())
        .then(data => {
            document.getElementById('price_section').style.display = 'block';
            document.getElementById('cost_price').value = data.cost_price;
            document.getElementById('sell_price').value = data.sell_price;
            document.getElementById('wholesale_price').value = data.wholesale_price;
        });
});
</script>

<?php include_once('../partials/footer.php'); ?>
