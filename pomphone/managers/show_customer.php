<?php
// /cooladmin/manager/show_customer.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "จัดการข้อมูลลูกค้า";

$search = $_GET['search'] ?? '';
if ($search) {
  $query = "SELECT a.*, d.old_lineid, d.cu_register, d.cu_addess, d.cu_facebook, d.cu_note FROM customer_account a
            LEFT JOIN customer_details d ON a.cua_id = d.cua_id
            WHERE a.cua_name LIKE :q OR a.cua_tel LIKE :q OR d.old_lineid LIKE :q
            ORDER BY a.created_at DESC";
  $stmt = $pdo->prepare($query);
  $stmt->execute(['q' => "%$search%"]);
} else {
  $query = "SELECT a.*, d.old_lineid, d.cu_register, d.cu_addess, d.cu_facebook, d.cu_note FROM customer_account a
            LEFT JOIN customer_details d ON a.cua_id = d.cua_id
            ORDER BY a.created_at DESC LIMIT 20";
  $stmt = $pdo->prepare($query);
  $stmt->execute();
}
$customers = $stmt->fetchAll();

function mask_data($str, $type = 'name') {
  if ($type === 'name') {
    return mb_substr($str, 0, 1) . str_repeat('*', mb_strlen($str) - 1);
  } elseif ($type === 'tel') {
    return substr($str, 0, 3) . str_repeat('*', 4) . substr($str, -3);
  }
  return $str;
}

$rank = $_SESSION['employee_rank'] ?? 0;
$is_view_full = $rank >= 88; // manager ขึ้นไปเห็นชื่อเต็ม
$is_editable = $rank >= 77; // Headshop ขึ้นไปแก้ไขข้อมูลได้
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>
<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">👥 รายชื่อลูกค้าทั้งหมด</h3>

        <form class="form-inline mb-3" method="get">
          <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control mr-2" placeholder="ชื่อ / เบอร์ / Line ID">
          <button class="btn btn-primary">ค้นหา</button>
          <button type="button" class="btn btn-success ml-2" data-toggle="modal" data-target="#addCustomerModal">➕ เพิ่มลูกค้า</button>
        </form>

        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>ชื่อ</th>
              <th>เบอร์</th>
              <th>LINE ID</th>
              <th>Rank</th>
              <th>Username</th>
              <th>สมัครเมื่อ</th>
              <th>การจัดการ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($customers as $i => $c): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= $is_view_full ? htmlspecialchars($c['cua_name'] . ' ' . $c['cua_lastname']) : htmlspecialchars(mask_data($c['cua_name']) . ' ' . mask_data($c['cua_lastname'])) ?></td>
                <td><?= $is_view_full ? htmlspecialchars($c['cua_tel']) : htmlspecialchars(mask_data($c['cua_tel'], 'tel')) ?></td>
                <td><?= $is_view_full ? htmlspecialchars($c['old_lineid']) : ($c['old_lineid'] ? '<span class="badge badge-success"> มี ID LINE แล้ว </span>' : '-') ?></td>
                <td><?= htmlspecialchars($c['cua_rank']) ?></td>
                <td><?= htmlspecialchars($c['cua_username']) ?></td>
                <td><?= isset($c['cu_register']) ? date('d/m/Y', strtotime($c['cu_register'])) : '-' ?></td>
                <td>
                  <?php if ($is_editable): ?>
                  <button class="btn btn-info btn-sm" onclick="openContactModal(<?= $c['cua_id'] ?>, '<?= htmlspecialchars($c['cua_tel']) ?>', '<?= htmlspecialchars($c['old_lineid']) ?>', '<?= htmlspecialchars($c['cu_addess']) ?>', '<?= htmlspecialchars($c['cu_facebook']) ?>', '<?= htmlspecialchars($c['cu_note']) ?>')">✏️ แก้ไขข้อมูล</button> 
                  <?php else: ?>
                  -
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!-- Modal เพิ่มลูกค้าใหม่ -->
<div class="modal fade" id="addCustomerModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="addCustomerForm" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">➕ เพิ่มลูกค้าใหม่</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>ชื่อ</label>
            <input type="text" name="name" class="form-control" required>
          </div>
          <div class="form-group">
            <label>นามสกุล</label>
            <input type="text" name="lastname" class="form-control" required>
          </div>
          <div class="form-group">
            <label>เบอร์โทร</label>
            <input type="text" name="tel" class="form-control" required>
          </div>
          <div class="form-group">
            <label>LINE ID (ถ้ามี)</label>
            <input type="text" name="lineid" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">บันทึกลูกค้า</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal แก้ไขข้อมูลลูกค้า -->
<div class="modal fade" id="contactModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form id="contactForm" method="POST">
        <div class="modal-header">
          <h5 class="modal-title">✏️ แก้ไขข้อมูลลูกค้า</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="cua_id" id="modal_cua_id">
          <div class="form-group">
            <label>เบอร์โทร</label>
            <input type="text" name="new_tel" id="modal_tel" class="form-control">
          </div>
          <div class="form-group">
            <label>เลขบัตรประชาชน</label>
            <input type="text" name="ps_id" id="modal_psid" class="form-control" >
          </div>
          <div class="form-group">
            <label>LINE ID</label>
            <input type="text" name="new_lineid" id="modal_lineid" class="form-control">
          </div>
          <div class="form-group">
            <label>ที่อยู่</label>
            <input type="text" name="new_address" id="modal_address" class="form-control">
          </div>
          <div class="form-group">
            <label>Facebook</label>
            <input type="text" name="new_facebook" id="modal_facebook" class="form-control">
          </div>
          <div class="form-group">
            <label>หมายเหตุ</label>
            <textarea name="new_note" id="modal_note" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">บันทึก</button>
        </div>
      </form>
    </div>
  </div>
</div>

</div>
<?php include_once("../partials/footer.php"); ?>
<script>
function openContactModal(id, tel, lineid, address, facebook, note, psid) {
  document.getElementById('modal_cua_id').value = id;
  document.getElementById('modal_tel').value = tel;
  document.getElementById('modal_lineid').value = lineid;
  document.getElementById('modal_address').value = address;
  document.getElementById('modal_facebook').value = facebook;
  document.getElementById('modal_note').value = note;
  document.getElementById('modal_psid').value = psid;
  $('#contactModal').modal('show');
}

document.getElementById('addCustomerForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('../includes/save_customer.php', {
    method: 'POST',
    body: formData
  }).then(res => res.text()).then(data => {
    if (data === 'success') {
      alert('เพิ่มลูกค้าเรียบร้อย');
      location.reload();
    } else {
      alert(data);
    }
  }).catch(err => alert("เกิดข้อผิดพลาด: " + err));
});

document.getElementById('contactForm').addEventListener('submit', function(e) {
  e.preventDefault();
  const formData = new FormData(this);
  fetch('../includes/update_customer_contact.php', {
    method: 'POST',
    body: formData
  }).then(res => res.text()).then(data => {
    if (data === 'success') {
      alert('แก้ไขข้อมูลสำเร็จ');
      location.reload();
    } else {
      alert(data);
    }
  }).catch(err => alert("เกิดข้อผิดพลาด: " + err));
});
</script>

