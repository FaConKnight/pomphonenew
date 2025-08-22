<?php
// /cooladmin/manager/add_saving.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

$page_title = "เปิดออมมือถือ";
$success = null;
$error = null;

// ดึงข้อมูลลูกค้าและสินค้า
$customers = $pdo->query("SELECT cua_id, cua_name, cua_lastname FROM customer_account ORDER BY cua_name")->fetchAll();
$products = $pdo->query("SELECT id, name FROM products WHERE category_id = 1 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $customer_id = $_POST['customer_id'] ?? null;
  $product_id = $_POST['product_id'] ?? null;
  $total_price = $_POST['total_price'] ?? null;
  $employee_id = $_SESSION['employee_id'] ?? null;

  if (!$customer_id || !$product_id || !$total_price) {
    $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
  } else {
    try {
      $saving_ref = 'SAV' . date('YmdHis') . rand(10, 99);
      $stmt = $pdo->prepare("INSERT INTO savings (saving_ref, customer_id, product_id, total_price, employee_id) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$saving_ref, $customer_id, $product_id, $total_price, $employee_id]);

      $log = $pdo->prepare("INSERT INTO saving_logs (saving_ref, action, employee_id, remark) VALUES (?, 'saving_created', ?, ?)");
      $log->execute([$saving_ref, $employee_id, 'เปิดออมมือถือ']);

      $success = "✅ บันทึกข้อมูลสำเร็จ รหัสออม: $saving_ref";
    } catch (Exception $e) {
      $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
  }
}
?>

<main>
<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">เปิดออมมือถือ</h3>

        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST">
          <div class="form-group">
            <label>เลือกลูกค้า</label>
            <select name="customer_id" class="form-control" required>
              <option value="">-- เลือกลูกค้า --</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['cua_id'] ?>"><?= safe_text($c['cua_name'] . ' ' . $c['cua_lastname']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>เลือกรุ่นมือถือ</label>
            <select name="product_id" class="form-control" required>
              <option value="">-- เลือกสินค้า --</option>
              <?php foreach ($products as $p): ?>
                <option value="<?= $p['id'] ?>"><?= safe_text($p['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>ราคาทั้งหมด</label>
            <input type="number" step="0.01" name="total_price" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary">บันทึก</button>
        </form>

      </div>
    </div>
  </div>
</div>

</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>