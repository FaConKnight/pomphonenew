
<?php
// add_repair.php - หน้ารับเครื่องซ่อม

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $tel = trim($_POST['tel']);
    $device_model = trim($_POST['device_model']);
    $imei = trim($_POST['imei']);

    $selected_issues = $_POST['issue_desc'] ?? [];
    $issue_note = trim($_POST['issue_note']);
    $issue_desc = implode(', ', $selected_issues);
    if ($issue_note) {
        $issue_desc .= ' - หมายเหตุ: ' . $issue_note;
    }

    $partner_shop_name = trim($_POST['partner_shop_name']);
    if ($partner_shop_name === 'อื่นๆ') {
        $partner_shop_name = trim($_POST['partner_shop_other']);
    }

    $expected_cost = floatval($_POST['expected_cost'] ?? 0);

    $stmt = $pdo->prepare("INSERT INTO repairs (customer_name, tel, device_model, imei, issue_desc, partner_shop_name, expected_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'received')");
    $stmt->execute([$customer_name, $tel, $device_model, $imei, $issue_desc, $partner_shop_name, $expected_cost]);

    echo "<script>alert('รับเครื่องซ่อมเรียบร้อย');window.location='repairs_list.php';</script>";
    exit;
}

$common_issues = ['จอแตก', 'เปลี่ยนแบต', 'ทัชสกรีนไม่ติด', 'ลำโพงไม่ดัง', 'กล้องเสีย', 'เครื่องดับ', 'เปิดไม่ติด', 'ชาร์จไม่เข้า'];
$partner_shops = ['ร้านบุญโต', 'ร้านพี่โต้ง', 'อื่นๆ'];
?>
<div class="main-content">
    <div class="section__content section__content--p30">
        <div class="container-fluid">
            <h3 class="mb-4">รับเครื่องซ่อม (ส่งร้านภายนอก)</h3>
            <form method="POST" class="form">
                <div class="form-group">
                    <label>ชื่อ (ลูกค้า)</label>
                    <input type="text" name="customer_name" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label>เบอร์โทร</label>
                    <input type="text" name="tel" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>ยี่ห้อ/รุ่นเครื่อง</label>
                    <input type="text" name="device_model" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>IMEI (ถ้ามี)</label>
                    <input type="text" name="imei" class="form-control">
                </div>
                <div class="form-group">
                    <label>อาการเสีย (เลือกได้มากกว่า 1)</label>
                    <select name="issue_desc[]" class="form-control" multiple size="6">
                        <?php foreach ($common_issues as $issue): ?>
                            <option value="<?= htmlspecialchars($issue) ?>"><?= htmlspecialchars($issue) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">หากไม่มีในรายการ ให้พิมพ์เพิ่มเติมด้านล่าง</small>
                    <input type="text" name="issue_note" class="form-control mt-1" placeholder="เพิ่มเติมหรือระบุอาการเฉพาะ">
                </div>
                <div class="form-group">
                    <label>ร้านที่จะส่งซ่อม</label>
                    <select name="partner_shop_name" class="form-control" onchange="document.getElementById('other_shop').style.display = this.value === 'อื่นๆ' ? 'block' : 'none';">
                        <option value="">-- เลือกร้านซ่อม --</option>
                        <?php foreach ($partner_shops as $shop): ?>
                            <option value="<?= $shop ?>"><?= $shop ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="partner_shop_other" id="other_shop" class="form-control mt-2" style="display:none;" placeholder="ระบุชื่อร้านอื่น ๆ">
                </div>
                <div class="form-group">
                    <label>ประเมินราคาซ่อม (บาท)</label>
                    <input type="number" name="expected_cost" class="form-control" step="0.01">
                </div>
                <button type="submit" class="btn btn-success btn-block">บันทึก</button>
            </form>
        </div>
    </div>
</div>
<?php include_once("../partials/footer.php"); ?>
