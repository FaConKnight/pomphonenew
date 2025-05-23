<?php
// manage_footer_rules.php - Backend จัดการข้อความท้ายใบเสร็จอัจฉริยะ พร้อมเปิด/ปิด และระยะเวลาแสดงผล

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 99) {
    http_response_code(403);
    exit("Unauthorized");
}

// เพิ่มหรือแก้ไขข้อความ
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $min_amount = floatval($_POST['min_amount'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $image_url = trim($_POST['image_url'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE receipt_footer_rules SET min_amount=?, message=?, image_url=?, start_date=?, end_date=?, is_active=? WHERE id=?");
        $stmt->execute([$min_amount, $message, $image_url, $start_date, $end_date, $is_active, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO receipt_footer_rules (min_amount, message, image_url, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$min_amount, $message, $image_url, $start_date, $end_date, $is_active]);
    }
    header("Location: manage_footer_rules.php");
    exit;
}

// ลบข้อความ
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $pdo->prepare("DELETE FROM receipt_footer_rules WHERE id=?")->execute([$id]);
    header("Location: manage_footer_rules.php");
    exit;
}

// toggle active
if (isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $pdo->prepare("UPDATE receipt_footer_rules SET is_active = NOT is_active WHERE id=?")->execute([$id]);
    header("Location: manage_footer_rules.php");
    exit;
}

// ดึงข้อมูลทั้งหมด
$stmt = $pdo->query("SELECT * FROM receipt_footer_rules ORDER BY min_amount DESC");
$rules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าข้อความท้ายใบเสร็จ</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
</head>
<body class="container mt-4">
<h3>ข้อความท้ายใบเสร็จอัจฉริยะ</h3>
<p>ระบบจะเลือกข้อความที่ min_amount ตรงเงื่อนไขมากที่สุด และอยู่ในช่วงวันที่แสดงผล</p>
<hr>
<table class="table table-bordered table-sm">
    <thead>
        <tr><th>สถานะ</th><th>ยอดขั้นต่ำ</th><th>วันที่เริ่ม</th><th>วันที่สิ้นสุด</th><th>ข้อความ</th><th>ภาพ</th><th>จัดการ</th></tr>
    </thead>
    <tbody>
        <?php foreach ($rules as $r): ?>
        <tr>
            <td><a href="?toggle=<?= $r['id'] ?>" class="btn btn-sm btn-<?= $r['is_active'] ? 'success' : 'secondary' ?>"><?= $r['is_active'] ? 'เปิด' : 'ปิด' ?></a></td>
            <td><?= number_format($r['min_amount'], 2) ?></td>
            <td><?= htmlspecialchars($r['start_date']) ?></td>
            <td><?= htmlspecialchars($r['end_date']) ?></td>
            <td style="white-space: pre-line; max-width:200px;"><?= htmlspecialchars($r['message']) ?></td>
            <td><?= $r['image_url'] ? '<img src="'.htmlspecialchars($r['image_url']).'" style="max-width:80px;">' : '-' ?></td>
            <td>
                <button class="btn btn-sm btn-info" onclick="editRule(<?= $r['id'] ?>, <?= $r['min_amount'] ?>, <?= json_encode($r['message']) ?>, <?= json_encode($r['image_url']) ?>, <?= json_encode($r['start_date']) ?>, <?= json_encode($r['end_date']) ?>, <?= $r['is_active'] ?>)">แก้ไข</button>
                <a href="?delete=<?= $r['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('ลบรายการนี้?')">ลบ</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<hr>
<h5 id="form-title">เพิ่มข้อความใหม่</h5>
<form method="POST">
    <input type="hidden" name="id" id="id">
    <div class="form-row">
        <div class="form-group col-md-3">
            <label>ยอดขั้นต่ำ (บาท)</label>
            <input type="number" step="0.01" name="min_amount" id="min_amount" class="form-control" required>
        </div>
        <div class="form-group col-md-3">
            <label>เริ่มแสดง</label>
            <input type="date" name="start_date" id="start_date" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label>สิ้นสุด</label>
            <input type="date" name="end_date" id="end_date" class="form-control">
        </div>
        <div class="form-group col-md-3">
            <label>เปิดใช้งาน</label><br>
            <input type="checkbox" name="is_active" id="is_active" value="1" checked>
        </div>
    </div>
    <div class="form-group">
        <label>ข้อความท้ายใบเสร็จ</label>
        <textarea name="message" id="message" class="form-control" rows="3" required></textarea>
    </div>
    <div class="form-group">
        <label>URL รูปภาพ (เช่น QR Code)</label>
        <input type="text" name="image_url" id="image_url" class="form-control">
    </div>
    <button type="submit" class="btn btn-success">บันทึก</button>
    <button type="reset" class="btn btn-secondary" onclick="resetForm()">ยกเลิก</button>
</form>
<?php include_once("../partials/footer.php"); ?>
<script>
function editRule(id, min, msg, img, start, end, active) {
    document.getElementById('id').value = id;
    document.getElementById('min_amount').value = min;
    document.getElementById('message').value = msg;
    document.getElementById('image_url').value = img;
    document.getElementById('start_date').value = start;
    document.getElementById('end_date').value = end;
    document.getElementById('is_active').checked = active == 1;
    document.getElementById('form-title').innerText = 'แก้ไขข้อความ';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}
function resetForm() {
    document.getElementById('form-title').innerText = 'เพิ่มข้อความใหม่';
}
</script>
</body>
</html>
