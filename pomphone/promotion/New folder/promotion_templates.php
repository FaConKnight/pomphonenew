<?php
// promotion_templates.php - ระบบจัดการเทมเพลตโปรโมชัน

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

$msg = '';

// ลบเทมเพลต
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM promotion_templates WHERE id = ?");
    $stmt->execute([$id]);
    $msg = 'ลบเทมเพลตแล้ว';
}

// เพิ่ม/แก้ไขเทมเพลต
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name']);
    $text = trim($_POST['text']);
    $image_url = trim($_POST['image_url']);
    $flex_json = trim($_POST['flex_json']);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE promotion_templates SET title = ?, message = ?, image_url = ?, flex_json = ? WHERE id = ?");
        $stmt->execute([$name, $text, $image_url, $flex_json, $id]);
        $msg = 'อัปเดตเทมเพลตแล้ว';
    } else {
        $stmt = $pdo->prepare("INSERT INTO promotion_templates (title, message, image_url, flex_json) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $text, $image_url, $flex_json]);
        $msg = 'เพิ่มเทมเพลตใหม่แล้ว';
    }
}

// แสดงรายการเทมเพลต
$templates = $pdo->query("SELECT * FROM promotion_templates ORDER BY created_at DESC")->fetchAll();
?>

<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">จัดการเทมเพลตโปรโมชัน</h3>
      <?php if ($msg): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($msg) ?> </div>
      <?php endif; ?>

      <form method="post">
        <input type="hidden" name="id" id="id">
        <div class="form-group">
          <label>ชื่อเทมเพลต</label>
          <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="form-group">
          <label>ข้อความ</label>
          <textarea name="text" id="text" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-group">
          <label>ลิงก์รูปภาพ (optional)</label>
          <input type="url" name="image_url" id="image_url" class="form-control">
        </div>
        <div class="form-group">
          <label>Flex Message JSON</label> <a href="https://developers.line.biz/flex-simulator">สร้างJSON</a>
          <textarea name="flex_json" id="flex_json" class="form-control" rows="5"></textarea>
        </div>
        <div id="flex_preview" class="mt-3"></div>
        <button class="btn btn-primary">💾 บันทึกเทมเพลต</button>
      </form>

      <hr>
      <h5>รายการเทมเพลต</h5>
      <table class="table table-bordered">
        <thead><tr><th>ชื่อ</th><th>ข้อความ</th><th>ดู</th><th>ลบ</th></tr></thead>
        <tbody>
          <?php foreach ($templates as $tpl): ?>
            <tr>
              <td><?= htmlspecialchars($tpl['title']) ?></td>
              <td><?= htmlspecialchars($tpl['message']) ?></td>
              <td><button class="btn btn-info btn-sm" onclick='fillForm(<?= json_encode($tpl) ?>)'>เลือก</button></td>
              <td><a href="?delete=<?= $tpl['id'] ?>" onclick="return confirm('ลบเทมเพลตนี้?')" class="btn btn-danger btn-sm">ลบ</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
  function fillForm(tpl) {
    document.getElementById('id').value = tpl.id;
    document.getElementById('name').value = tpl.title;
    document.getElementById('text').value = tpl.message;
    document.getElementById('image_url').value = tpl.image_url;
    document.getElementById('flex_json').value = tpl.flex_json;
    previewFlexJSON();
  }

  function previewFlexJSON() {
    const json = document.getElementById('flex_json').value;
    const box = document.getElementById('flex_preview');
    box.innerHTML = '';
    try {
      const parsed = JSON.parse(json);
      box.innerHTML = '<div class="alert alert-info">✅ Flex JSON ถูกต้อง</div>';
    } catch (e) {
      if (json.trim()) {
        box.innerHTML = '<div class="alert alert-warning">⚠️ รูปแบบ JSON ไม่ถูกต้อง: ' + e.message + '</div>';
      }
    }
  }
</script>
<?php include_once('../partials/footer.php'); ?>
