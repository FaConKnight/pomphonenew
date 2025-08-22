<?php
// brands.php - จัดการแบรนด์สินค้า

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

$msg = '';

// ลบแบรนด์
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->execute([$id]);
    $msg = 'ลบแบรนด์แล้ว';
}

// เพิ่ม/แก้ไขแบรนด์
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name']);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE brands SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $msg = 'อัปเดตชื่อแบรนด์แล้ว';
    } else {
        $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
        $stmt->execute([$name]);
        $msg = 'เพิ่มแบรนด์ใหม่แล้ว';
    }
}

$brands = $pdo->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();
?>

<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">จัดการแบรนด์สินค้า</h3>
      <?php if ($msg): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($msg) ?> </div>
      <?php endif; ?>

      <form method="post" class="mb-3">
        <input type="hidden" name="id" id="id">
        <div class="form-group">
          <label>ชื่อแบรนด์</label>
          <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <button class="btn btn-primary">💾 บันทึกแบรนด์</button>
      </form>

      <hr>
      <table class="table table-bordered">
        <thead><tr><th>#</th><th>ชื่อแบรนด์</th><th>เลือก</th><th>ลบ</th></tr></thead>
        <tbody>
          <?php foreach ($brands as $i => $b): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($b['name']) ?></td>
              <td><button class="btn btn-sm btn-info" onclick='fillForm(<?= json_encode($b) ?>)'>เลือก</button></td>
              <td><a href="?delete=<?= $b['id'] ?>" onclick="return confirm('ลบแบรนด์นี้?')" class="btn btn-sm btn-danger">ลบ</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
  function fillForm(brand) {
    document.getElementById('id').value = brand.id;
    document.getElementById('name').value = brand.name;
  }
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
