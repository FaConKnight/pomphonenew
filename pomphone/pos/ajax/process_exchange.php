<?php
// ajax/process_exchange.php - The single, correct backend processor.
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['employee_id'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$data = json_decode(file_get_contents("php://input"), true);
$purchase_items = $data['purchase_items'] ?? [];
$return_items = $data['return_items'] ?? [];
$payments = $data['payments'] ?? [];
$customer_id = !empty($data['customer_id']) ? $data['customer_id'] : null;
$discount = floatval($data['discount'] ?? 0);
$employee_id = $_SESSION['employee_id'];

if (empty($purchase_items) && empty($return_items)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'ไม่มีรายการสำหรับบันทึก']));
}

try {
    $pdo->beginTransaction();

    $total_refund_amount = 0;
    $new_return_id = null;

    // --- Part 1: Process Returns (if any) ---
    if (!empty($return_items)) {
        $stmt_create_return = $pdo->prepare("INSERT INTO sale_returns (employee_id, total_refund_amount, created_at) VALUES (?, 0, NOW())");
        $stmt_create_return->execute([$employee_id]);
        $new_return_id = $pdo->lastInsertId();

        foreach ($return_items as $item) {
            $original_sale_item_id = $item['original_sale_item_id'];
            $custom_return_price = floatval($item['custom_return_price']);

            $stmt_check = $pdo->prepare("SELECT price, product_id, imei FROM sale_items WHERE id = ?");
            $stmt_check->execute([$original_sale_item_id]);
            $original_item = $stmt_check->fetch();

            if (!$original_item || $custom_return_price > $original_item['price']) {
                throw new Exception("ราคาคืนสูงกว่าราคาขายเดิม หรือไม่พบรายการเดิม");
            }

            $stmt_insert_return = $pdo->prepare("INSERT INTO sale_return_items (sale_return_id, original_sale_item_id, product_id, quantity, imei, refund_amount, reason) VALUES (?, ?, ?, 1, ?, ?, ?)");
            $stmt_insert_return->execute([$new_return_id, $original_sale_item_id, $original_item['product_id'], $original_item['imei'], -$custom_return_price, $item['reason']]);

            $total_refund_amount += $custom_return_price;

            // Update stock
            if (!empty($original_item['imei'])) {
                $pdo->prepare("UPDATE products_items SET status = 'in_stock' WHERE imei1 = ?")->execute([$original_item['imei']]);
            } else {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + 1 WHERE id = ?")->execute([$original_item['product_id']]);
            }
        }
        $receipt_no_rt = 'RT' . date('Ymd') . str_pad($new_return_id, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE sale_returns SET total_refund_amount = ?, receipt_no = ? WHERE id = ?")->execute([$total_refund_amount, $receipt_no_rt, $new_return_id]);
    }

    // --- Part 2: Process New Sale (if any) ---
    $total_purchase_amount = 0;
    $new_sale_id = null;

    if (!empty($purchase_items)) {
        $stmt_create_sale = $pdo->prepare("INSERT INTO sale (employee_id, sale_time, total, discount, final_amount, customer_id) VALUES (?, NOW(), 0, ?, 0, ?)");
        $stmt_create_sale->execute([$employee_id, $discount, $customer_id]);
        $new_sale_id = $pdo->lastInsertId();

        foreach ($purchase_items as $item) {
            // (ใส่โค้ดเช็คสต็อกและดึงราคาขายจริงจาก DB ที่นี่)
            $stmt_product = $pdo->prepare("SELECT sell_price, stock_quantity, is_trackable FROM products WHERE id = ?");
            $stmt_product->execute([$item['id']]);
            $product_info = $stmt_product->fetch();
            
            $item_price = $product_info['sell_price'];
            $total_purchase_amount += $item_price * $item['qty'];

            $stmt_insert_item = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
            $stmt_insert_item->execute([$new_sale_id, $item['id'], $item['qty'], $item_price]);
            
            // Update stock
            if(!$product_info['is_trackable']) {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?")->execute([$item['qty'], $item['id']]);
            }
        }
        $final_amount = $total_purchase_amount - $discount;
        $receipt_no_rc = 'RC' . date('Ymd') . str_pad($new_sale_id, 5, '0', STR_PAD_LEFT);
        $pdo->prepare("UPDATE sale SET total = ?, final_amount = ?, receipt_no = ? WHERE id = ?")->execute([$total_purchase_amount, $final_amount, $receipt_no_rc, $new_sale_id]);
    }

    // --- Part 3: Link transactions and handle payments ---
    if ($new_sale_id && $new_return_id) {
        $pdo->prepare("UPDATE sale_returns SET used_in_sale_id = ? WHERE id = ?")->execute([$new_sale_id, $new_return_id]);
        $pdo->prepare("INSERT INTO sale_payment_methods (sale_id, method, amount) VALUES (?, 'exchange', ?)")->execute([$new_sale_id, $total_refund_amount]);
    }
    
    if($new_sale_id) {
         foreach (["cash", "transfer"] as $method) {
            $amt = floatval($payments[$method] ?? 0);
            if ($amt > 0) {
                $pdo->prepare("INSERT INTO sale_payment_methods (sale_id, method, amount) VALUES (?, ?, ?)")->execute([$new_sale_id, $method, $amt]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'sale_id' => $new_sale_id, 'return_id' => $new_return_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => "Error: " . $e->getMessage()]);
}
?>