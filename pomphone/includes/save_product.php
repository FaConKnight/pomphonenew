<?php
// /cooladmin/includes/save_product.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id || ($_SESSION['employee_rank'] ?? 0) < 77) {
  http_response_code(403);
  exit('Permission Denied');
}

$product_id = $_POST['product_id'] ?? null;
$category_id = $_POST['category_id'] ?? null;
$supplier_id = $_POST['supplier_id'] ?? null;
$cost_price = max(0, floatval($_POST['cost_price'] ?? 0));
$sell_price = max(0, floatval($_POST['sell_price'] ?? 0));
$wholesale_price_raw = $cost_price * 1.05;
$wholesale_price = ceil($wholesale_price_raw / 10) * 10;
$quantity   = max(0, intval($_POST['quantity'] ?? 0));
$imei_list = trim($_POST['imei_list'] ?? '');

if (!$product_id || !$category_id || !$supplier_id) {
  exit('กรุณากรอกข้อมูลให้ครบ');
}


try {
  $pdo->beginTransaction();

  // ตรวจสอบว่าสินค้านี้ track IMEI หรือไม่
  $product_stmt = $pdo->prepare("SELECT is_trackable FROM products WHERE id = ? LIMIT 1");
  $product_stmt->execute([$product_id]);
  $product = $product_stmt->fetch();
  if (!$product) throw new Exception("ไม่พบสินค้า");

  $is_trackable = $product['is_trackable'];

  if ($is_trackable) {
    // มือถือ (track IMEI)
    $imeis = array_filter(array_map('trim', explode("\n", $imei_list)));
    foreach ($imeis as $imei) {
      // ตรวจสอบ IMEI ซ้ำ
      $check = $pdo->prepare("SELECT COUNT(*) FROM products_items WHERE imei1 = ?");
      $check->execute([$imei]);
      if ($check->fetchColumn() > 0) continue;

      // เพิ่มสินค้า
      $insert = $pdo->prepare("INSERT INTO products_items 
        (product_id, imei1, status, cost_price, sell_price, wholesale_price, source_supplier_id, created_at, updated_at)
        VALUES (?, ?, 'in_stock', ?, ?, ?, ?, NOW(), NOW())");
      $insert->execute([$product_id, $imei, $cost_price, $sell_price, $wholesale_price, $supplier_id]);

      // เพิ่ม log
      $item_id = $pdo->lastInsertId();
      $log = $pdo->prepare("INSERT INTO stock_logs 
        (product_item_id, action, quantity, employee_id, remark, supplier_id, created_at)
        VALUES (?, 'in', 1, ?, ?, ?, NOW())");
      $log->execute([$item_id, $employee_id, "เพิ่มมือถือ IMEI $imei", $supplier_id]);
    }
  } else {
    // สินค้าทั่วไป
    if (!$is_trackable && $quantity <= 0) {
      throw new Exception("กรุณาระบุจำนวนสินค้าให้ถูกต้อง (> 0)");
    }
    $update = $pdo->prepare("UPDATE products 
      SET stock_quantity = stock_quantity + ?, cost_price = ?, sell_price = ?, wholesale_price = ?
      WHERE id = ?");
    $update->execute([$quantity, $cost_price, $sell_price, $wholesale_price, $product_id]);

    $log = $pdo->prepare("INSERT INTO stock_logs 
      (product_id, employee_id, action, quantity, remark, supplier_id, created_at)
      VALUES (?, ?, 'in', ?, ?, ?, NOW())");
    $log->execute([$product_id, $employee_id, $quantity, "เพิ่มจำนวนสินค้าที่ $product_id.จำนวน.$quantity", $supplier_id]);
  }

  $pdo->commit();
  header('Location: ../stock/add_product.php?success=เพิ่มสินค้าเข้าสต๊อกสำเร็จ');
  exit;
} catch (Exception $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo "❌ เกิดข้อผิดพลาด: " . $e->getMessage();
}
