<?php
// promotion_logs.php - ประวัติโปรโมชันที่เคยส่ง พร้อมข้อมูลผู้รับ + ระบบค้นหา

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

$where = [];
$params = [];

// ตัวกรอง
$template_id = $_GET['template_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if ($template_id !== '') {
  $where[] = 'pl.template_id = ?';
  $params[] = $template_id;
}
if ($date_from !== '') {
  $where[] = 'DATE(pl.schedule) >= ?';
  $params[] = $date_from;
}
if ($date_to !== '') {
  $where[] = 'DATE(pl.schedule) <= ?';
  $params[] = $date_to;
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$logs = $pdo->prepare("SELECT pl.*, pt.title as template_title FROM promotion_logs pl 
                        LEFT JOIN promotion_templates pt ON pl.template_id = pt.id
                        $where_sql
                        ORDER BY pl.schedule DESC, pl.id DESC LIMIT 100");
$logs->execute($params);
$logs = $logs->fetchAll();

$templates = $pdo->query("SELECT id, title FROM promotion_templates ORDER BY created_at DESC")->fetchAll();
?>

<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">ประวัติการส่งโปรโมชัน</h3>

      <form method="get" class="mb-3">
        <div class="form-row align-items-end">
          <div class="col-md-3">
            <label>เทมเพลต</label>
            <select name="template_id" class="form-control">
              <option value="">-- ทั้งหมด --</option>
              <?php foreach ($templates as $tpl): ?>
                <option value="<?= $tpl['id'] ?>" <?= ($tpl['id'] == $template_id ? 'selected' : '') ?>><?= htmlspecialchars($tpl['title']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-3">
            <label>ตั้งแต่วันที่</label>
            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
          </div>
          <div class="col-md-3">
            <label>ถึงวันที่</label>
            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
          </div>
          <div class="col-md-2">
            <button class="btn btn-primary">🔍 ค้นหา</button>
          </div>
        </div>
      </form>

      <table class="table table-bordered table-hover">
        <thead>
          <tr>
            <th>#</th>
            <th>เวลา</th>
            <th>เทมเพลต</th>
            <th>ข้อความ</th>
            <th>Flex JSON</th>
            <th>จำนวน</th>
            <th>ดูผู้รับ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $i => $log): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($log['schedule']) ?></td>
              <td><?= htmlspecialchars($log['template_title'] ?? '-') ?></td>
              <td><?= nl2br(htmlspecialchars($log['message'])) ?></td>
              <td>
                <?php if ($log['flex_json']): ?>
                  <button class="btn btn-sm btn-info" onclick='showFlex(<?= json_encode($log['flex_json']) ?>)'>ดู</button>
                <?php else: ?>-<?php endif; ?>
              </td>
              <td><?= (int)$log['recipients'] ?> ราย</td>
              <td>
                <a href="promotion_recipients.php?log_id=<?= $log['id'] ?>" class="btn btn-sm btn-primary">👥</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  function showFlex(json) {
    try {
      const obj = JSON.parse(json);
      alert(JSON.stringify(obj, null, 2));
    } catch (e) {
      alert("ไม่สามารถแสดง Flex JSON ได้");
    }
  }
</script>

<?php include_once('../partials/footer.php'); ?>
