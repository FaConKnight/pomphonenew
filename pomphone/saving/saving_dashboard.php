<?php
// /cooladmin/manager/saving_dashboard.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

$page_title = "สรุปภาพรวมระบบออมมือถือ";

// ดึงสถิติหลัก
$total_active = $pdo->query("SELECT COUNT(*) FROM savings WHERE status = 'active'")->fetchColumn();
$total_completed = $pdo->query("SELECT COUNT(*) FROM savings WHERE status = 'completed'")->fetchColumn();
$total_cancelled = $pdo->query("SELECT COUNT(*) FROM savings WHERE status = 'cancelled'")->fetchColumn();
$total_payments = $pdo->query("SELECT SUM(amount) FROM saving_payments")->fetchColumn();
$pending_slips = $pdo->query("SELECT COUNT(*) FROM saving_pending WHERE status = 'pending'")->fetchColumn();

// ดึงรายการล่าสุด 10 รายการ
$latest = $pdo->query("SELECT s.*, c.cua_name, c.cua_lastname, p.name AS product_name
                      FROM savings s
                      LEFT JOIN customer_account c ON s.customer_id = c.cua_id
                      LEFT JOIN products p ON s.product_id = p.id
                      ORDER BY s.created_at DESC LIMIT 10")->fetchAll();
?>

<?php include_once('../partials/header.php'); ?>
<?php include_once('../partials/sidebar.php'); ?>

<div class="page-container">
  <div class="main-content">
    <div class="section__content section__content--p30">
      <div class="container-fluid">
        <h3 class="mb-4">📊 ภาพรวมระบบออมมือถือ</h3>

        <?php if ($pending_slips > 0): ?>
          <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            🚨 มีรายการสลิปรออนุมัติจำนวน <strong><?= $pending_slips ?></strong> รายการ!
            <a href="saving_pending.php" class="btn btn-sm btn-warning ml-2">ตรวจสอบตอนนี้</a>
          </div>
          <audio autoplay>
            <source src="../assets/sounds/alert.mp3" type="audio/mpeg">
          </audio>
        <?php endif; ?>

        <div class="row text-center mb-4">
          <div class="col-md-3">
            <div class="alert alert-primary"> ออมอยู่: <h4><?= $total_active ?></h4></div>
          </div>
          <div class="col-md-3">
            <div class="alert alert-success">✅ ออมครบแล้ว: <h4><?= $total_completed ?></h4></div>
          </div>
          <div class="col-md-3">
            <div class="alert alert-danger">❌ ยกเลิก: <h4><?= $total_cancelled ?></h4></div>
          </div>
          <div class="col-md-3">
            <div class="alert alert-info">💰 รวมยอดชำระ: <h4><?= number_format($total_payments, 2) ?> ฿</h4></div>
          </div>
        </div>

        <h5 class="mt-4">📋 รายการออมล่าสุด</h5>
        <table class="table table-striped table-bordered">
          <thead>
            <tr>
              <th>#</th>
              <th>รหัสออม</th>
              <th>ลูกค้า</th>
              <th>รุ่นมือถือ</th>
              <th>ยอดรวม</th>
              <th>ชำระแล้ว</th>
              <th>สถานะ</th>
              <th>เปิดเมื่อ</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latest as $i => $s): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($s['saving_ref']) ?></td>
                <td><?= htmlspecialchars($s['cua_name'] . ' ' . $s['cua_lastname']) ?></td>
                <td><?= htmlspecialchars($s['product_name']) ?></td>
                <td><?= number_format($s['total_price'], 2) ?></td>
                <td><?= number_format($s['paid_amount'], 2) ?></td>
                <td><?= htmlspecialchars($s['status']) ?></td>
                <td><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

      </div>
    </div>
  </div>
</div>

<?php include_once('../partials/footer.php'); ?>
