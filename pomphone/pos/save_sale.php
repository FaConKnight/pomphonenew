<?php
// save_sale.php - บันทึกการขายพร้อมเลขใบเสร็จ RCyyyyMMddxxxxx

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");
require_once("../includes/session.php");

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    http_response_code(403);
    exit("Unauthorized");
}

$data = json_decode(file_get_contents("php://input"), true);
$items = $data['items'] ?? [];
$discount = floatval($data['discount'] ?? 0);
$payments = $data['payments'] ?? [];
$customer_id = $data['customer_id'] ?? null;

if (empty($items)) {
    http_response_code(400);
    exit("ไม่มีรายการสินค้า");
}

try {
    $pdo->beginTransaction();

    $employee_id = $_SESSION['employee_id'];
    $total = 0;
    $final = 0;

    // 1. สร้างบิลการขาย (ชั่วคราวก่อนรู้เลขใบเสร็จ)
    $stmt = $pdo->prepare("INSERT INTO sale (employee_id, sale_time, total, discount, final_amount, customer_id) VALUES (?, NOW(), 0, ?, 0, ?)");
    $stmt->execute([$employee_id, $discount, $customer_id]);
    $sale_id = $pdo->lastInsertId();

    // 2. สร้างเลขใบเสร็จ RC + yyyyMMdd + running
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale WHERE DATE(sale_time) = ? AND id <= ?");
    $stmt->execute([$today, $sale_id]);
    $count_today = $stmt->fetchColumn();
    $receipt_no = 'RC' . date('Ymd') . str_pad($count_today, 5, '0', STR_PAD_LEFT);

    $pdo->prepare("UPDATE sale SET receipt_no = ? WHERE id = ?")->execute([$receipt_no, $sale_id]);

    // เตรียม log
    $log_detail = [];

    foreach ($items as $item) {
        $product_id = $item['id'];
        $qty = intval($item['qty'] ?? 1);
        $imei = $item['imei'] ?? null;

        if ($imei) {
            $stmt = $pdo->prepare("SELECT sell_price, cost_price FROM products_items WHERE imei1 = ? AND product_id = ? AND status = 'in_stock' LIMIT 1");
            $stmt->execute([$imei, $product_id]);
            $row = $stmt->fetch();
            if (!$row) throw new Exception("IMEI ไม่พร้อมขาย: $imei");

            $price = $row['sell_price'];
            $cost = $row['cost_price'];
            $total += $price;

            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price, cost_price, imei) VALUES (?, ?, 1, ?, ?, ?)");
            $stmt->execute([$sale_id, $product_id, $price, $cost, $imei]);

            $pdo->prepare("UPDATE products_items SET status = 'sold' WHERE imei1 = ? LIMIT 1")->execute([$imei]);
            $log_detail[] = "ขาย IMEI [$imei] ราคา $price";
        } else {
            $stmt = $pdo->prepare("SELECT sell_price, cost_price, stock_quantity FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $row = $stmt->fetch();
            if (!$row || $row['stock_quantity'] < $qty) throw new Exception("สินค้า ID $product_id คงเหลือไม่พอขาย");

            $price = $row['sell_price'];
            $cost = $row['cost_price'];
            $total += $price * $qty;

            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price, cost_price) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$sale_id, $product_id, $qty, $price, $cost]);

            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$qty, $product_id]);
            $log_detail[] = "ขายสินค้า ID $product_id x$qty ราคา $price";
        }
    }

    $final = $total - $discount;
    $pdo->prepare("UPDATE sale SET total = ?, final_amount = ? WHERE id = ?")->execute([$total, $final, $sale_id]);

    // 3. บันทึกช่องทางการชำระเงิน
    foreach (["cash", "transfer", "credit"] as $method) {
        $amt = floatval($payments[$method] ?? 0);
        if ($amt > 0) {
            $stmt = $pdo->prepare("INSERT INTO sale_payment_methods (sale_id, method, amount) VALUES (?, ?, ?)");
            $stmt->execute([$sale_id, $method, $amt]);
        }
    }

    // 4. ถ้ามีประเภทสินเชื่อ
    if (!empty($payments['credit_provider']) && $payments['credit']) {
        $stmt = $pdo->prepare("UPDATE sale SET credit_provider = ? WHERE id = ?");
        $stmt->execute([$payments['credit_provider'], $sale_id]);
    }

    // 5. log
    $stmt = $pdo->prepare("INSERT INTO system_logs (employee_id, action_type, detail, created_at) VALUES (?, 'ขายสินค้า', ?, NOW())");
    $stmt->execute([$employee_id, implode('; ', $log_detail)]);
    $pdo->commit();
    echo $sale_id;

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
