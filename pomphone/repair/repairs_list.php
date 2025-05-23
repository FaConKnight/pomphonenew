<?php
// repairs_list.php - แสดงรายการงานซ่อมทั้งหมด

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");
include_once('../partials/header.php');
include_once('../partials/sidebar.php');

$stmt = $pdo->query("SELECT * FROM repairs ORDER BY created_at DESC");
$repairs = $stmt->fetchAll();

$status_badge = [
    'received' => 'warning',
    'sent' => 'info',
    'done' => 'success',
    'canceled' => 'secondary'
];
?>
<div class="main-content">
    <div class="section__content section__content--p30">
        <div class="container-fluid">
            <h3 class="mb-4">รายการเครื่องซ่อมทั้งหมด</h3>
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>วันที่</th>
                        <th>ลูกค้า</th>
                        <th>เบอร์</th>
                        <th>รุ่น</th>
                        <th>IMEI</th>
                        <th>อาการ</th>
                        <th>ร้านซ่อม</th>
                        <th>ราคาประเมิน</th>
                        <th>สถานะ</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($repairs as $r): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($r['created_at'])) ?></td>
                            <td><?= htmlspecialchars($r['customer_name']) ?></td>
                            <td><?= htmlspecialchars($r['tel']) ?></td>
                            <td><?= htmlspecialchars($r['device_model']) ?></td>
                            <td><?= htmlspecialchars($r['imei']) ?></td>
                            <td><?= nl2br(htmlspecialchars($r['issue_desc'])) ?></td>
                            <td><?= htmlspecialchars($r['partner_shop_name']) ?></td>
                            <td><?= number_format($r['expected_cost'], 2) ?> ฿</td>
                            <td><span class="badge badge-<?= $status_badge[$r['status']] ?? 'light' ?>"><?= ucfirst($r['status']) ?></span></td>
                            <td>
                                <a href="update_repair.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">แก้ไข</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once("../partials/footer.php"); ?>
