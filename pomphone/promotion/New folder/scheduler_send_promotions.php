<?php
// scheduler_send_promotions.php - รันโดย Cron เพื่อส่งโปรโมชันตามเวลาที่กำหนด

require_once('../includes/connectdb.php');
require_once('../line/line_functions.php');

// 🔐 ป้องกันการเข้าถึงโดยตรง (ใช้ secret key ใน cron)
//curl "https://yourdomain.com/backend1/scheduler_send_promotions.php?key=my_secure_cron_key"
$secret = 'key_cron_promotion';
echo $secret;
/*
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
  http_response_code(403);
  die('Forbidden');
}
*/
// ดึงโปรโมชันที่ถึงเวลาแล้ว และยังไม่ถูกส่ง
$stmt = $pdo->prepare("SELECT * FROM promotion_logs WHERE schedule <= NOW() AND sent_at IS NULL ORDER BY schedule ASC LIMIT 5");
$stmt->execute();
$logs = $stmt->fetchAll();

foreach ($logs as $log) {
  $log_id = $log['id'];
  $message = $log['message'];
  $flex_json = $log['flex_json'];
  $content = [];

  if ($message) {
    $content[] = ["type" => "text", "text" => $message];
  }

  if ($flex_json) {
    $flex = json_decode($flex_json, true);
    if ($flex) {
      $content[] = [
        "type" => "flex",
        "altText" => "โปรโมชันสุดพิเศษสำหรับคุณเท่านั้น",
        "contents" => $flex
      ];
    }
  }

  // ดึงผู้รับทั้งหมด
  $recipients = $pdo->prepare("SELECT line_user_id FROM promotion_recipients WHERE log_id = ?");
  $recipients->execute([$log_id]);
  $line_ids = $recipients->fetchAll(PDO::FETCH_COLUMN);

  foreach ($line_ids as $line_id) {
    pushMessage($line_id, $content);
    usleep(200000); // delay เล็กน้อย (200ms) เพื่อป้องกัน rate limit
  }

  // อัปเดตว่าโปรโมชันนี้ส่งแล้ว
  $pdo->prepare("UPDATE promotion_logs SET sent_at = NOW(), sent = 1 WHERE id = ?")->execute([$log_id]);
  
  echo "\nส่ง log_id = $log_id สำเร็จถึง " . count($line_ids) . " คน\n";
}

if (count($logs) === 0) {
  echo "\nไม่มีโปรโมชันที่ถึงเวลาส่ง\n";
} else {
  echo "\n--- จบการทำงาน ---\n";
}
