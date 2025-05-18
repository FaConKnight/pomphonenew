<?php
// /cooladmin/manager/save_product.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../managers/add_product.php');
    exit;
}

$product_id = $_POST['product_id'] ?? null;
$cost_price = $_POST['cost_price'] ?? null;
$sell_price = $_POST['sell_price'] ?? null;
$wholesale_price = $_POST['wholesale_price'] ?? null;
$quantity = $_POST['quantity'] ?? 0;
$imei_raw = $_POST['imei_list'] ?? []; // แล้วใช้ array_map('trim', $imei_raw)
$employee_id = $_SESSION['employee_id'] ?? 0;

// ตรวจสอบข้อมูลเบื้องต้น
if (!$product_id || !$cost_price || !$sell_price) {
    $_SESSION['error'] = "\u274c กรุณากรอกข้อมูลให้ครบถ้วน";
    header('Location: ../managers/add_product.php');
    exit;
}

// ตรวจสอบว่าเป็นสินค้าต้อง track IMEI หรือไม่
$stmt = $pdo->prepare("SELECT is_trackable FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
$is_trackable = $product['is_trackable'] ?? 0;

try {
    $pdo->beginTransaction();

    if ($is_trackable) {
        // มือถือ
        $imei_list = array_filter(array_map('trim', explode("\n", $imei_raw)));
        foreach ($imei_list as $imei) {
            // ตรวจสอบ IMEI ซ้ำ
            $check = $pdo->prepare("SELECT COUNT(*) FROM products_items WHERE imei1 = ?");
            $check->execute([$imei]);
            if ($check->fetchColumn() > 0) continue; // ข้าม IMEI ซ้ำ

            // เพิ่มรายการ
            $stmt = $pdo->prepare("INSERT INTO products_items (product_id, imei1, cost_price, sell_price, wholesale_price, status, created_at, updated_at)
                                   VALUES (?, ?, ?, ?, ?, 'in_stock', NOW(), NOW())");
            $stmt->execute([$product_id, $imei, $cost_price, $sell_price, $wholesale_price]);

            $item_id = $pdo->lastInsertId();

            // log
            $log = $pdo->prepare("INSERT INTO stock_logs (product_item_id, action, quantity, employee_id, remark, created_at)
                                  VALUES (?, 'in', 1, ?, ?, NOW())");
            $log->execute([$item_id, $employee_id, "เพิ่มมือถือ IMEI $imei"]);
        }
    } else {
        // สินค้าทั่วไป
        $stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
        $stmt->execute([$quantity, $product_id]);
        // log แบบรวมจำนวน
        $log = $pdo->prepare("INSERT INTO stock_logs (product_item_id, product_id, action, quantity, employee_id, remark, created_at)
                              VALUES (?, 'in', ?, ?, ?, NOW())");
        $log->execute([0, $product_id, $quantity, $employee_id, "เพิ่มสินค้าเข้าสต๊อก จำนวน $quantity"]);
        
    }

    $pdo->commit();
    $_SESSION['success'] = "\u2705 เพิ่มสินค้าเข้าสต๊อกเรียบร้อยแล้ว";
}catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "\u274c เกิดข้อผิดพลาด: " . $e->getMessage();
}

header('Location: ../managers/add_product.php');
exit;
