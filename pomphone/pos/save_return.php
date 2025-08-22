<?php
// save_return.php - บันทึกการคืนสินค้าและหักยอดบิล

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    http_response_code(403);
    exit("Unauthorized");
}

$data = json_decode(file_get_contents("php://input"), true);
$sale_id = intval($data['sale_id'] ?? 0);
$items = $data['items'] ?? [];
$reason = trim($data['reason'] ?? '');
$refund_type = $data['refund_type'] ?? 'deduct'; // หรือ 'cash'

if ($sale_id <= 0 || empty($items)) {
    http_response_code(400);
    exit("ข้อมูลไม่ครบถ้วน");
}

try {
    $pdo->beginTransaction();
    $employee_id = $_SESSION['employee_id'];
    $total_refund = 0;

    // 1. บันทึกใน sale_returns
    $stmt = $pdo->prepare("INSERT INTO sale_returns (sale_id, employee_id, reason, refund_type, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$sale_id, $employee_id, $reason, $refund_type]);
    $return_id = $pdo->lastInsertId();

    foreach ($items as $item) {
        $sale_item_id = intval($item['sale_item_id']);
        $product_id = intval($item['product_id']);
        $imei = $item['imei'] ?? null;
        $qty = intval($item['qty']);
        $price = floatval($item['price']);
        $discount = floatval($item['discount']);
        $refund = floatval($item['refund_amount']);
        $total_refund += $refund;

        // 2. บันทึกแต่ละรายการ
        $stmt = $pdo->prepare("INSERT INTO sale_return_items 
            (return_id, sale_item_id, product_id, imei, qty, price, discount, refund_amount)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$return_id, $sale_item_id, $product_id, $imei, $qty, $price, $discount, $refund]);

        // 3. คืนสต๊อก
        if ($imei) {
            $pdo->prepare("UPDATE products_items SET status = 'in_stock' WHERE imei1 = ?")->execute([$imei]);
        } else {
            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?")->execute([$qty, $product_id]);
        }
    }

    // 4. หักยอดบิล หาก refund_type = 'deduct'
    if ($refund_type === 'deduct') {
        $stmt = $pdo->prepare("UPDATE sale SET final_amount = final_amount - ? WHERE id = ?");
        $stmt->execute([$total_refund, $sale_id]);
    }

    // 5. log
    $log_text = "คืนสินค้าบิล $sale_id มูลค่าคืน $total_refund บาท ($refund_type)";
    $stmt = $pdo->prepare("INSERT INTO system_logs (employee_id, action_type, detail, created_at) VALUES (?, 'คืนสินค้า', ?, NOW())");
    $stmt->execute([$employee_id, $log_text]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'return_id' => $return_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
