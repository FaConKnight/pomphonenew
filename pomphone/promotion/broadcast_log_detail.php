<?php
// admin/broadcast_log_detail.php - รายละเอียดการส่งข้อความของแต่ละแคมเปญ
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

$jobId = isset($_GET['job_id']) ? intval($_GET['job_id']) : 0;
if ($jobId <= 0) {
    die("ไม่พบแคมเปญที่ต้องการแสดง");
}

// ดึงรายละเอียดแคมเปญ
$stmt = $pdo->prepare("SELECT * FROM broadcast_jobs WHERE id = ?");
$stmt->execute([$jobId]);
$job = $stmt->fetch();
if (!$job) {
    die("ไม่พบแคมเปญนี้ในระบบ");
}

// ดึง log การส่งข้อความ
$stmt = $pdo->prepare("SELECT bl.*, c.display_name, c.cua_tel 
    FROM broadcast_logs bl
    JOIN customer_account c ON bl.customer_id = c.cua_id
    WHERE bl.job_id = ? ORDER BY bl.sent_at DESC");
$stmt->execute([$jobId]);
$logs = $stmt->fetchAll();
?>

<main class="main-content p-4">
    <h2>รายละเอียดการส่งข้อความ: <?= safe_text($job['title']) ?></h2>
    <p><strong>เวลาที่กำหนดส่ง:</strong> <?= $job['scheduled_at'] ?></p>
    <p><strong>สถานะ:</strong> <?= $job['status'] ?></p>

    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
            <tr>
                <th>ชื่อลูกค้า</th>
                <th>เบอร์โทร</th>
                <th>สถานะส่ง</th>
                <th>เวลาที่ส่ง</th>
                <th>ข้อผิดพลาด</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= safe_text($log['display_name']) ?></td>
                    <td><?= safe_text($log['cua_tel']) ?></td>
                    <td><?= $log['sent_status'] === 'success' ? '✅ สำเร็จ' : '❌ ล้มเหลว' ?></td>
                    <td><?= $log['sent_at'] ?></td>
                    <td><?= safe_text($log['error_message'] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <p><a href="broadcast_log.php">← กลับไปหน้าหลัก</a></p>
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
