<?php
// update_repair.php - อัปเดตสถานะการซ่อม และต้นทุน/ราคาจริง

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    exit("ไม่พบงานซ่อม");
}

// ดึงข้อมูลเดิม
$stmt = $pdo->prepare("SELECT * FROM repairs WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$repair = $stmt->fetch();
if (!$repair) {
    exit("ไม่พบงานซ่อม");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual_cost = floatval($_POST['actual_cost'] ?? 0);
    $customer_price = floatval($_POST['customer_price'] ?? 0);
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE repairs SET actual_cost = ?, customer_price = ?, status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$actual_cost, $customer_price, $status, $id]);

    echo "<script>alert('อัปเดตข้อมูลเรียบร้อย');window.location='repairs_list.php';</script>";
    exit;
}

$statuses = ['received' => 'รับเครื่องแล้ว', 'sent' => 'ส่งร้านซ่อมแล้ว', 'done' => 'เสร็จสิ้น', 'canceled' => 'ยกเลิก'];
?>
<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">อัปเดตสถานะเครื่องซ่อม</h3>
      <form method="POST">
        <div class="form-group">
          <label>ชื่อลูกค้า:</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($repair['customer_name']) ?>" disabled>
        </div>
        <div class="form-group">
          <label>อาการเสีย:</label>
          <textarea class="form-control" rows="2" disabled><?= htmlspecialchars($repair['issue_desc']) ?></textarea>
        </div>
        <div class="form-group">
          <label>ราคาจากร้านซ่อม (ต้นทุนจริง)</label>
          <input type="number" step="0.01" name="actual_cost" class="form-control" value="<?= $repair['actual_cost'] ?>">
        </div>
        <div class="form-group">
          <label>ราคาที่คิดกับลูกค้า</label>
          <input type="number" step="0.01" name="customer_price" class="form-control" value="<?= $repair['customer_price'] ?>">
        </div>
        <div class="form-group">
          <label>สถานะ:</label>
          <select name="status" class="form-control">
            <?php foreach ($statuses as $key => $label): ?>
              <option value="<?= $key ?>" <?= $repair['status'] === $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary">อัปเดต</button>
        <a href="repairs_list.php" class="btn btn-secondary">ยกเลิก</a>
      </form>
    </div>
  </div>
</div>
<?php include_once("../partials/footer.php"); ?>
