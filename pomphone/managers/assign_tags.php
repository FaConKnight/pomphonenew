<?php
// assign_tags.php - ผูกแท็กลูกค้ากับรายชื่อลูกค้า

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 77) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cua_id = $_POST['cua_id'];
    $tag_ids = $_POST['tag_ids'] ?? [];

    $pdo->prepare("DELETE FROM customer_tags WHERE cua_id = ?")->execute([$cua_id]);
    $stmt = $pdo->prepare("INSERT INTO customer_tags (cua_id, tag_id) VALUES (?, ?)");
    foreach ($tag_ids as $tag_id) {
        $stmt->execute([$cua_id, $tag_id]);
    }
}

$customers = $pdo->query("SELECT cua_id, cua_name, cua_tel FROM customer_account ORDER BY cua_name ASC")->fetchAll();
$tags = $pdo->query("SELECT * FROM tags ORDER BY tag_name ASC")->fetchAll();

$selected_id = $_GET['id'] ?? $customers[0]['cua_id'] ?? null;
$assigned = [];
if ($selected_id) {
    $stmt = $pdo->prepare("SELECT tag_id FROM customer_tags WHERE cua_id = ?");
    $stmt->execute([$selected_id]);
    $assigned = array_column($stmt->fetchAll(), 'tag_id');
}
?>
<main>
<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">ผูกแท็กลูกค้า</h3>
      <form method="get" class="form-inline mb-3">
        <label>เลือกลูกค้า:</label>
        <select name="id" onchange="this.form.submit()" class="form-control ml-2">
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['cua_id'] ?>" <?= $c['cua_id'] == $selected_id ? 'selected' : '' ?>>
              <?= safe_text($c['cua_name']) ?> (<?= $c['cua_tel'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </form>

      <form method="post">
        <input type="hidden" name="cua_id" value="<?= $selected_id ?>">
        <div class="form-group">
          <?php foreach ($tags as $tag): ?>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="tag_ids[]" value="<?= $tag['id'] ?>"
                <?= in_array($tag['id'], $assigned) ? 'checked' : '' ?>>
              <label class="form-check-label"> <?= safe_text($tag['tag_name']) ?> </label>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="btn btn-success">บันทึก</button>
      </form>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>

