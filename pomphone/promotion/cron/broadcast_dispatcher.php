<?php
// admin/broadcast_dispatcher.php - ตรวจสอบแคมเปญที่ต้องเริ่มส่ง แล้วเตรียมรายการส่ง
require_once __DIR__ . '/../includes/bootstrap.php';

// ดึงแคมเปญที่ถึงเวลาส่งและยังไม่ถูกประมวลผล
$stmt = $pdo->prepare("SELECT * FROM broadcast_jobs WHERE scheduled_at <= NOW() AND status = 'pending'");
$stmt->execute();
$jobs = $stmt->fetchAll();

foreach ($jobs as $job) {
    $conditions = json_decode($job['send_condition'], true);
    $selectedTags = $conditions['tags'] ?? [];
    $birthdayToday = $conditions['birthday_today'] ?? false;
    $minTotalSpent = $conditions['min_total_spent'] ?? 0;

    $params = [];
    $where = [];

    if (!empty($selectedTags)) {
        $placeholders = implode(',', array_fill(0, count($selectedTags), '?'));
        $where[] = "id IN (SELECT customer_id FROM customer_tags WHERE tag_id IN ($placeholders))";
        $params = array_merge($params, $selectedTags);
    }

    if ($birthdayToday) {
        $where[] = "DATE_FORMAT(birthday, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')";
    }

    if ($minTotalSpent > 0) {
        // ตัวอย่างจำลองลูกค้าที่ยอดเกิน 0 = id 1,2,3 (ต้องแก้เชื่อมฐานจริงภายหลัง)
        $where[] = "id IN (1,2,3)";
    }

    $sql = "SELECT id FROM customers";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

    // เตรียมรายการส่งให้ลูกค้า
    $stmtInsert = $pdo->prepare("INSERT INTO broadcast_logs (job_id, customer_id, sent_status) VALUES (?, ?, ?)");
    foreach ($customers as $cust) {
        $stmtInsert->execute([$job['id'], $cust['id'],'pending']);
        echo 'RUN';
    }

    // อัปเดตสถานะเป็น sending
    $stmt = $pdo->prepare("UPDATE broadcast_jobs SET status = 'sending' WHERE id = ?");
    $stmt->execute([$job['id']]);
} echo 'RUNTEST';
?>
