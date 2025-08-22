<?php
// admin/send_messages_worker.php - Worker สำหรับส่งข้อความจากแคมเปญที่รอส่ง
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

$maxLoops = 20; // ส่งได้ประมาณ 10 นาที ถ้ารอ 30 วินาที/รอบ
$loopCount = 0;
sleep(30); // พักก่อนเริ่มระบบ 

while ($loopCount < $maxLoops) {
    // ดึงรายการ broadcast_logs ที่ยังไม่ได้ส่ง
    $stmt = $pdo->prepare("SELECT bl.*, bj.template_id, bl.job_id, c.line_user_id, mt.content_type, mt.content_json , mt.name
        FROM broadcast_logs bl
        INNER JOIN broadcast_jobs bj ON bl.job_id = bj.id
        INNER JOIN customers c ON bl.customer_id = c.id
        INNER JOIN message_templates mt ON bj.template_id = mt.id
        WHERE bl.sent_status = 'pending' LIMIT 100");
    $stmt->execute();
    $logs = $stmt->fetchAll();

    if (empty($logs)) {
        echo 'break';
        break; // ไม่มีงานจะส่งแล้ว
    }

    foreach ($logs as $log) {
        $success = false;
        $error = '';
        try {
            if ($log['content_type'] === 'text') {
                $success = sendLineText($log['line_user_id'], $log['content_json']);
            } elseif ($log['content_type'] === 'flex') {
                $success = sendLineFlex($log['line_user_id'], $log['content_json'], $log['name']);
            } else {
                $error = 'Unknown content type';
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }

        // อัปเดตผลการส่ง
        $stmt = $pdo->prepare("UPDATE broadcast_logs SET sent_status = ?, error_message = ?, sent_at = NOW() WHERE id = ?");
        $stmt->execute([$success ? 'success' : 'fail', $error, $log['id']]);
        usleep(50000); // ใช้เวลา 5 วิส่ง 100 ครั้ง = 20/1s 
    }

    // ตรวจสอบแคมเปญที่ส่งครบแล้ว และอัปเดตสถานะเป็น 'done'
    $stmt = $pdo->query("SELECT job_id FROM broadcast_logs WHERE sent_status = 'pending' GROUP BY job_id");
    $jobsWithPending = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($jobsWithPending)) {
        $placeholders = implode(',', array_fill(0, count($jobsWithPending), '?'));
        $stmt = $pdo->prepare("UPDATE broadcast_jobs SET status = 'done' WHERE status = 'sending' AND id NOT IN ($placeholders)");
        $stmt->execute($jobsWithPending);
    } else {
        $pdo->query("UPDATE broadcast_jobs SET status = 'done' WHERE status = 'sending'");
        echo 'done';
    }

    $loopCount++;
    if(fmod($loopCount, 10)==0){
        sleep(30); // พักก่อนส่งรอบถัดไป   
    }
    sleep(10); // พักก่อนส่งรอบถัดไป   
}
?>
