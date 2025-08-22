<?php
// repairs_list.php - แสดงรายการงานซ่อมทั้งหมด

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

$stmt = $pdo->query("SELECT * FROM repairs ORDER BY created_at DESC LIMIT 50");
$repairs = $stmt->fetchAll();

$status_badge = [
    'received' => 'warning',
    'returned' => 'info',
    'picked_up' => 'success',
    'cancelled' => 'secondary'
];
?>
<main>
<div class="main-content">
    <div class="section__content section__content--p30">
        <div class="container-fluid">
            <h3 class="mb-4">รายการเครื่องซ่อมทั้งหมด 50 รายการล่าสุด</h3>
            <table class="table table-bordered table-striped">
                <thead class="thead-dark">
                    <tr>
                        <th>วันที่</th>
                        <th>ลูกค้า</th>
                        <th>เบอร์</th>
                        <th>รุ่น</th>
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
                            <td><?= cheage_date($r['created_at']) ?></td>
                            <td><?= safe_text($r['customer_name']) ?></td>
                            <td><?= safe_text($r['tel']) ?></td>
                            <td><?= safe_text($r['device_model']) ?></td>
                            <td><?= nl2br(safe_text($r['issue_desc'])) ?></td>
                            <td><?= safe_text($r['partner_shop_name']) ?></td>
                            <td><?= number_format($r['expected_cost']?? 0, 2) ?> ฿</td>
                            <td><span class="badge badge-<?= $status_badge[$r['status']] ?? 'light' ?>"><?= ucfirst($r['status']) ?></span></td>
                            <td>
                                <a href="repair_receipt.php?id=<?= $r['id'] ?>" target="_blank" class="btn btn-sm btn-success reprint-btn">ปริ้นใบซ่อม</a>
                                <a href="update_repair.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-primary">แก้ไข</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</main>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>