<?php
// dashboard_manager.php - Dashboard ฝั่ง Manager

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';


if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 77) {
    http_response_code(403);
    exit("Unauthorized");
}

// สถิติเบื้องต้น
$total_customers = $pdo->query("SELECT COUNT(*) FROM customer_account")->fetchColumn();
$total_tags = $pdo->query("SELECT COUNT(*) FROM tags")->fetchColumn();
$total_templates = $pdo->query("SELECT COUNT(*) FROM message_templates")->fetchColumn();
$total_logs = $pdo->query("SELECT COUNT(*) FROM broadcast_jobs")->fetchColumn();
?>
<main>
<div class="main-content">
  <div class="section__content section__content--p30">
    <div class="container-fluid">
      <h3 class="mb-4">📊 Manager Dashboard</h3>
      <div class="row">
        <div class="col-md-3">
          <div class="card text-white bg-primary mb-3">
            <div class="card-body">
              <h5 class="card-title">ลูกค้าทั้งหมด</h5>
              <p class="card-text h4"><?= $total_customers ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-success mb-3">
            <div class="card-body">
              <h5 class="card-title">แท็กลูกค้า</h5>
              <p class="card-text h4"><?= $total_tags ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-warning mb-3">
            <div class="card-body">
              <h5 class="card-title">เทมเพลตโปรโมชัน</h5>
              <p class="card-text h4"><?= $total_templates ?></p>
            </div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card text-white bg-danger mb-3">
            <div class="card-body">
              <h5 class="card-title">รายการโปรโมชัน</h5>
              <p class="card-text h4"><?= $total_logs ?></p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>

