<?php
// admin/broadcast_log.php - แสดงประวัติการส่งข้อความ
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/sidebar.php';

// ยกเลิกแคมเปญ (ถ้ากดปุ่มยกเลิก)
if (isset($_GET['cancel_job_id'])) {
    $cancelId = intval($_GET['cancel_job_id']);
    $stmt = $pdo->prepare("UPDATE broadcast_jobs SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
    $stmt->execute([$cancelId]);
    header("Location: broadcast_log.php");
    exit;
}

// ดึงรายการแคมเปญ
$jobs = $pdo->query("SELECT bj.*, 
    (SELECT COUNT(*) FROM broadcast_logs WHERE job_id = bj.id AND sent_status = 'success') AS success_count,
    (SELECT COUNT(*) FROM broadcast_logs WHERE job_id = bj.id AND sent_status = 'fail') AS fail_count,
    mt.name AS template_name
    FROM broadcast_jobs bj
    LEFT JOIN message_templates mt ON bj.template_id = mt.id
    ORDER BY scheduled_at DESC LIMIT 30")->fetchAll();
?>

<main class="main-content p-4">
    <h2>ประวัติการส่งข้อความ</h2>
    <table border="1" cellpadding="8" cellspacing="0">
        <thead>
            <tr>
                <th>ชื่อแคมเปญ</th>
                <th>เทมเพลตที่ใช้</th>
                <th>วันที่ส่ง</th>
                <th>สถานะ</th>
                <th>ส่งสำเร็จ</th>
                <th>ล้มเหลว</th>
                <th>ดูรายละเอียด</th>
                <th>ยกเลิก</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $job): ?>
                <tr>
                    <td><?= safe_text($job['title']) ?></td>
                    <td><?= safe_text($job['template_name']) ?></td>
                    <td><?= $job['scheduled_at'] ?></td>
                    <td><?= $job['status'] ?></td>
                    <td><?= $job['success_count'] ?></td>
                    <td><?= $job['fail_count'] ?></td>
                    <td><a href="broadcast_log_detail.php?job_id=<?= $job['id'] ?>">ดูรายละเอียด</a></td>
                    <td>
                        <?php if ($job['status'] === 'pending'): ?>
                            <a href="?cancel_job_id=<?= $job['id'] ?>" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกแคมเปญนี้?')">❌ ยกเลิก</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
