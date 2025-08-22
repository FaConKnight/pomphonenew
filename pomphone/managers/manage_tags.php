<?php
// manage_tags.php - จัดการแท็กลูกค้า
//echo getcwd();
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 77) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tag_name'])) {
    $name = trim($_POST['tag_name']);
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT IGNORE INTO tags(tag_name,created_by) VALUES(?,'user')");
        $stmt->execute([$name]);
    }
}

// แสดงแท็กทั้งหมด
$tags = $pdo->query("SELECT * FROM tags ORDER BY tag_name ASC")->fetchAll();
?>
<main>
<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">จัดการแท็กลูกค้า</h3>
      <form method="post" class="form-inline mb-3">
        <input type="text" name="tag_name" class="form-control mr-2" placeholder="เพิ่มแท็กใหม่">
        <button class="btn btn-primary">เพิ่ม</button>
      </form>

      <table class="table table-bordered table-sm">
        <thead><tr><th>#</th><th>ชื่อแท็ก</th></tr></thead>
        <tbody>
          <?php foreach ($tags as $tag): ?>
            <tr>
              <td><?= $tag['id'] ?></td>
              <td><?= safe_text($tag['tag_name']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
