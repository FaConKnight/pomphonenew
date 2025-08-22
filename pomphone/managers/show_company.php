<?php
// /cooladmin/manager/show_company.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 77) {
    http_response_code(403);
    exit("Unauthorized");
}

$page_title = "จัดการข้อมูลบริษัทผู้จำหน่ายสินค้า";

$search = $_GET['search'] ?? '';
if ($search) {
  $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE name_th LIKE :q OR name_en LIKE :q OR taxid LIKE :q ORDER BY created_at DESC");
  $stmt->execute(['q' => "%$search%"]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM suppliers ORDER BY created_at DESC LIMIT 50");
  $stmt->execute();
}
$suppliers = $stmt->fetchAll();
?>
<main>
<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">🏢 ข้อมูลบริษัท / ร้านค้าที่จัดส่งสินค้า</h3>

        <form class="form-inline mb-3" method="get">
          <input type="text" name="search" value="<?= safe_text($search) ?>" class="form-control mr-2" placeholder="ชื่อบริษัท / เลขประจำตัวผู้เสียภาษี">
          <button class="btn btn-primary">ค้นหา</button>
          <button type="button" class="btn btn-success ml-2" data-toggle="modal" data-target="#addCompanyModal">➕ เพิ่มบริษัท</button>
        </form>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>ชื่อ (TH)</th>
              <th>ชื่อ (EN)</th>
              <th>Tax ID</th>
              <th>ติดต่อ</th>
              <th>อีเมล</th>
              <th>ที่อยู่</th>
              <th>การจัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($suppliers as $i => $s): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= safe_text($s['name_th']) ?></td>
                <td><?= safe_text($s['name_en']) ?></td>
                <td><?= safe_text($s['taxid']) ?></td>
                <td><?= safe_text($s['contact_name'] . ' ' . $s['phone']) ?></td>
                <td><?= safe_text($s['email']) ?></td>
                <td><?= safe_text($s['address']) ?></td>
                <td><button class="btn btn-info btn-sm">✏️ แก้ไข</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal เพิ่มบริษัท -->
<div class="modal fade" id="addCompanyModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="addCompanyForm" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">➕ เพิ่มบริษัท</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>ชื่อบริษัท (TH)</label>
            <input type="text" name="name_th" class="form-control" required>
          </div>
          <div class="form-group">
            <label>ชื่อบริษัท (EN)</label>
            <input type="text" name="name_en" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Tax ID</label>
            <input type="text" name="taxid" class="form-control">
          </div>
          <div class="form-group">
            <label>ชื่อผู้ติดต่อ</label>
            <input type="text" name="contact_name" class="form-control">
          </div>
          <div class="form-group">
            <label>เบอร์โทร</label>
            <input type="text" name="phone" class="form-control">
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" class="form-control">
          </div>
          <div class="form-group">
            <label>ที่อยู่</label>
            <textarea name="address" class="form-control"></textarea>
          </div>
          <div class="form-group">
            <label>หมายเหตุ</label>
            <textarea name="note" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
          <button type="submit" class="btn btn-success">บันทึกบริษัท</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('addCompanyForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);

  fetch('../includes/save_company.php', {
    method: 'POST',
    body: formData
  })
  .then(res => res.text())
  .then(data => {
    if (data === 'success') {
      alert('เพิ่มบริษัทเรียบร้อย');
      location.reload();
    } else {
      alert(data);
    }
  })
  .catch(err => alert("เกิดข้อผิดพลาด: " + err));
});
</script>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>