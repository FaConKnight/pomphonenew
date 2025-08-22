<?php
// save_sale.php - บันทึกการขายพร้อมเลขใบเสร็จ RCyyyyMMxxxxx

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    http_response_code(403);
    exit("Unauthorized");
}

$data = json_decode(file_get_contents("php://input"), true);
$items = $data['items'] ?? [];
$discount = floatval($data['discount'] ?? 0);
$payments = $data['payments'] ?? [];
$change_amount = floatval($data['change_amount'] ?? 0);
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
    $net_total = 0;
    $net_discount = 0;

    // 1. สร้างบิลการขาย (ชั่วคราวก่อนรู้เลขใบเสร็จ)
    $stmt = $pdo->prepare("INSERT INTO sale (employee_id, sale_time, total, discount, final_amount, customer_id) VALUES (?, NOW(), 0, ?, 0, ?)");
    $stmt->execute([$employee_id, $discount, $customer_id]);
    $sale_id = $pdo->lastInsertId();

    // 2. สร้างเลขใบเสร็จ RC + yyyyMM + running
    $yearMonth = date('Ym'); // yyyyMM
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sale WHERE DATE_FORMAT(sale_time, '%Y%m') = ?");
    $stmt->execute([$yearMonth]);
    $count_month = $stmt->fetchColumn() + 1;

    $receipt_no = 'RC' . $yearMonth . str_pad($count_month, 5, '0', STR_PAD_LEFT);
    $fullRef = encodeReceiptReference($receipt_no);

    $pdo->prepare("UPDATE sale SET receipt_no = ? , fullref = ? WHERE id = ?")->execute([$receipt_no, $fullRef, $sale_id]);
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
            $item_discount = floatval($item['item_discount'] ?? 0);
            $final_price = $price - $item_discount;
            $total += $price;
            $net_total += $final_price;
            $net_discount += $item_discount;

            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price, cost_price, imei, item_discount) VALUES (?, ?, 1, ?, ?, ?, ?)");
            $stmt->execute([$sale_id, $product_id, $price, $cost, $imei, $item_discount]);



            $pdo->prepare("UPDATE products_items SET status = 'sold' WHERE imei1 = ? LIMIT 1")->execute([$imei]);
            $log_detail[] = "ขาย IMEI [$imei] ราคา $price ลด $item_discount";

        } else {
            $stmt = $pdo->prepare("SELECT sell_price, cost_price, stock_quantity FROM products WHERE id = ? LIMIT 1");
            $stmt->execute([$product_id]);
            $row = $stmt->fetch();
            if (!$row || $row['stock_quantity'] < $qty) throw new Exception("สินค้า ID $product_id คงเหลือไม่พอขาย");

            $price = $row['sell_price'];
            $cost = $row['cost_price'];
            $item_discount = floatval($item['item_discount'] ?? 0);
            $final_price = ($price * $qty) - ($item_discount * $qty);
            $total += ($price * $qty);
            $net_total += $final_price;
            $net_discount += ($item_discount * $qty);

            $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price, cost_price, item_discount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$sale_id, $product_id, $qty, $price, $cost, $item_discount]);

            $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$qty, $product_id]);
            $log_detail[] = "ขายสินค้า ID $product_id x$qty ราคา $price ลด $item_discount";

        }
    }

    $final = $net_total - $discount;
    $net_discount += $discount;
    $pdo->prepare("UPDATE sale SET total = ?, final_amount = ? , discount = ? , change_amount = ? WHERE id = ?")->execute([$total, $final, $net_discount, $change_amount, $sale_id]);

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