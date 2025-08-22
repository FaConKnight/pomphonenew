<?php
// add_repair.php - หน้ารับเครื่องซ่อม

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $tel = trim($_POST['tel']);
    $device_model = trim($_POST['device_model']);
    $imei = trim($_POST['imei']);
    $statusprint = false;

    $selected_issues = $_POST['issue_desc'] ?? [];
    $issue_note = trim($_POST['issue_note']);
    $issue_desc = implode(', ', $selected_issues);
    if ($issue_note) {
        $issue_desc .= ' - หมายเหตุ: ' . $issue_note;
    }

    $send_type = $_POST['send_type'] ?? 'ส่งร้านนอก';
    $deposit_amount = floatval($_POST['deposit_amount'] ?? 0);
    $device_password = trim($_POST['device_password'] ?? '');
    $pickup_date = $_POST['pickup_date'] ?? null;

    // รับค่าร้านตามประเภท
    if ($send_type === 'ส่งศูนย์') {
        $partner_shop_name = trim($_POST['partner_shop_name_center']);
    } else {
        $partner_shop_name = trim($_POST['partner_shop_name']);
    }
    if ($partner_shop_name === 'อื่นๆ') {
        $partner_shop_name = trim($_POST['partner_shop_other']);
    }

    $expected_cost = floatval($_POST['expected_cost'] ?? 0);

    $stmt = $pdo->prepare("INSERT INTO repairs 
        (customer_name, tel, device_model, imei, issue_desc, partner_shop_name, expected_cost, send_type, deposit_amount, device_password, pickup_date, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'received')");
    $stmt->execute([
        $customer_name, $tel, $device_model, $imei,
        $issue_desc, $partner_shop_name, $expected_cost,
        $send_type, $deposit_amount, $device_password, $pickup_date
    ]);

    $repair_id = $pdo->lastInsertId();
    echo "<script>alert('รับเครื่องซ่อมเรียบร้อย');window.location='add_repair.php';</script>";
    exit;
}

$common_issues = ['จอแตก', 'เปลี่ยนแบต', 'ทัชสกรีนไม่ติด', 'ลำโพงไม่ดัง', 'กล้องเสีย', 'ติดpin', 'เปิดไม่ติด', 'ชาร์จไม่เข้า', 'ติดgmail', 'กู้เมล', 'สมัครเมล', 'ลบไวรัส'];
$partner_shops_outside = ['ร้านป้อมมือถือ','ร้านบุญโต', 'ร้านพี่โต้ง', 'อื่นๆ'];
$partner_shops_center = ['ศูนย์ซัมซุง', 'ศูนย์ออปโป้', 'ศูนย์วีโว่', 'ศูนย์อินฟินิก', 'ศูนย์เรียวมี', 'อื่นๆ'];
?>
<main>
<div class="main-content">
    <div class="section__content section__content--p30">
        <div class="container-fluid">
            <h3 class="mb-4">รับเครื่องซ่อม</h3>
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
                    <label>ส่งซ่อมแบบ</label>
                    <select name="send_type" id="send_type" class="form-control" required onchange="togglePartnerOptions()">
                        <option value="ส่งร้านนอก">ส่งร้านนอก</option>
                        <option value="ส่งศูนย์">ส่งศูนย์</option>
                    </select>
                </div>

                <div class="form-group" id="partner_outside_group">
                    <label>เลือกร้านซ่อม (ร้านนอก)</label>
                    <select name="partner_shop_name" class="form-control" onchange="document.getElementById('other_shop').style.display = this.value === 'อื่นๆ' ? 'block' : 'none';">
                        <option value="">-- เลือกร้านซ่อม --</option>
                        <?php foreach ($partner_shops_outside as $shop): ?>
                            <option value="<?= $shop ?>"><?= $shop ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" id="partner_center_group" style="display: none;">
                    <label>เลือกศูนย์ซ่อม (ส่งศูนย์)</label>
                    <select name="partner_shop_name_center" class="form-control" onchange="document.getElementById('other_shop').style.display = this.value === 'อื่นๆ' ? 'block' : 'none';">
                        <option value="">-- เลือกศูนย์ --</option>
                        <?php foreach ($partner_shops_center as $shop): ?>
                            <option value="<?= $shop ?>"><?= $shop ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <input type="text" name="partner_shop_other" id="other_shop" class="form-control mt-2" style="display:none;" placeholder="ระบุชื่อร้านอื่น ๆ">

                <div class="form-group">
                    <label>มัดจำ (บาท)</label>
                    <input type="number" name="deposit_amount" class="form-control" step="0.01">
                </div>
                <div class="form-group">
                    <label>รหัสผ่านเครื่อง (ถ้ามี)</label>
                    <input type="text" name="device_password" class="form-control">
                </div>
                <div class="form-group">
                    <label>วันที่นัดรับ</label>
                    <input type="date" name="pickup_date" class="form-control">
                </div>

                <div class="form-group">
                    <label>อาการเสีย (เลือกได้มากกว่า 1)</label>
                    <select name="issue_desc[]" class="form-control" multiple size="6">
                        <?php foreach ($common_issues as $issue): ?>
                            <option value="<?= safe_text($issue) ?>"><?= safe_text($issue) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">หากไม่มีในรายการ ให้พิมพ์เพิ่มเติมด้านล่าง</small>
                    <input type="text" name="issue_note" class="form-control mt-1" placeholder="เพิ่มเติมหรือระบุอาการเฉพาะ">
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
</main>

<script>
function togglePartnerOptions() {
    var sendType = document.getElementById('send_type').value;
    var groupOutside = document.getElementById('partner_outside_group');
    var groupCenter = document.getElementById('partner_center_group');

    if (sendType === 'ส่งร้านนอก') {
        groupOutside.style.display = 'block';
        groupCenter.style.display = 'none';
    } else {
        groupOutside.style.display = 'none';
        groupCenter.style.display = 'block';
    }
}
window.addEventListener('DOMContentLoaded', togglePartnerOptions);
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
