<?php
// log_reprint.php - บันทึกเหตุผลการพิมพ์ใบเสร็จซ้ำ

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    http_response_code(403);
    exit("Unauthorized");
}

$sale_id = intval($_POST['sale_id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');

if ($sale_id <= 0 || $reason === '') {
    http_response_code(400);
    exit("ข้อมูลไม่ครบถ้วน");
}

// ดึงเลขใบเสร็จ
$stmt = $pdo->prepare("SELECT receipt_no FROM sale WHERE id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    http_response_code(404);
    exit("ไม่พบใบเสร็จ");
}

$receipt_no = $sale['receipt_no'];
$employee_id = $_SESSION['employee_id'];
$log = "พิมพ์ซ้ำใบเสร็จ $receipt_no ด้วยเหตุผล: $reason";

$stmt = $pdo->prepare("INSERT INTO system_logs (employee_id, module, action_type, detail, created_at) VALUES (?,'warning', 'พิมพ์ซ้ำใบเสร็จ', ?, NOW())");
$stmt->execute([$employee_id, $log]);
$pdo->prepare("UPDATE sale SET reprint_count = reprint_count + 1 WHERE id = ?")->execute([$sale_id]);
echo "OK";
