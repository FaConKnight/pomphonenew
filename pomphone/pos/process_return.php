<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['employee_id'])) {
    http_response_code(403);
    exit('Unauthorized');
}

$data = json_decode(file_get_contents("php://input"), true);
$receipt_no = trim($data['receipt_no'] ?? '');
$items = $data['items'] ?? [];
$reason = trim($data['reason'] ?? '');
$method = trim($data['method'] ?? '');

if (!$receipt_no || !$reason || !$method || empty($items)) {
    http_response_code(400);
    exit('ข้อมูลไม่ครบถ้วน');
}

$stmt = $pdo->prepare("SELECT id FROM sale WHERE receipt_no = ? LIMIT 1");
$stmt->execute([$receipt_no]);
$sale = $stmt->fetch();
if (!$sale) {
    http_response_code(404);
    exit('ไม่พบใบเสร็จนี้');
}
$sale_id = $sale['id'];

try {
    $pdo->beginTransaction();
    $total_refund = 0;
    $log_lines = [];

    foreach ($items as $imei) {
        $stmt = $pdo->prepare("SELECT id, product_id, price FROM sale_items WHERE sale_id = ? AND imei = ? LIMIT 1");
        $stmt->execute([$sale_id, $imei]);
        $item = $stmt->fetch();
        if (!$item) throw new Exception("IMEI $imei ไม่อยู่ในบิลนี้");

        // คืนสินค้า
        $pdo->prepare("UPDATE products_items SET status = 'in_stock' WHERE imei1 = ?")->execute([$imei]);
        $log_lines[] = "คืนสินค้า IMEI [$imei] มูลค่า {$item['price']}";
        $total_refund += $item['price'];
    }

    $employee_id = $_SESSION['employee_id'];
    $log_detail = "คืนจากบิล $receipt_no; วิธีคืน: $method; เหตุผล: $reason; ยอดรวม: $total_refund; " . implode("; ", $log_lines);
    $stmt = $pdo->prepare("INSERT INTO system_logs (employee_id, action_type, detail, created_at) VALUES (?, 'คืนสินค้า', ?, NOW())");
    $stmt->execute([$employee_id, $log_detail]);

    $pdo->commit();
    echo "คืนสินค้าสำเร็จ ยอดคืน: $total_refund บาท (" . ($method == 'cash' ? 'คืนเงินสด' : 'หักกับบิลใหม่') . ")";
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
?>