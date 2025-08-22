<?php
// get_all_products.php - สำหรับโหลดข้อมูลสินค้าแบบ Ajax

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

$category_id = $_GET['category_id'] ?? '';
$status_filter = $_GET['status'] ?? '';

$results = [];

if ($category_id !== '') {
  // ตรวจสอบว่าเป็นสินค้าติดตามรายชิ้นหรือไม่
  $stmt = $pdo->prepare("SELECT is_trackable FROM categories WHERE id = ?");
  $stmt->execute([$category_id]);
  $is_trackable = (int)$stmt->fetchColumn();

  if ($is_trackable === 1) {
    // ดึงจาก products_items
    $sql = "SELECT pi.imei1, pi.status, pi.cost_price, pi.wholesale_price, pi.sell_price,
                   pi.created_at, p.name, p.sku, c.name AS category, b.name AS brand
            FROM products_items pi
            JOIN products p ON pi.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE c.id = ?";
    $params = [$category_id];

    if (!empty($status_filter)) {
      $sql .= " AND pi.status = ?";
      $params[] = $status_filter;
    }

    $sql .= " ORDER BY pi.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();

    foreach ($items as $row) {
      $results[] = [
        'name' => $row['name'],
        'sku' => $row['sku'],
        'category' => $row['category'],
        'brand' => $row['brand'],
        'imei1' => $row['imei1'],
        'status' => $row['status'],
        'stock_quantity' => 1,
        'cost_price' => $row['cost_price'],
        'wholesale_price' => $row['wholesale_price'],
        'sell_price' => $row['sell_price'],
        'created_at' => date('d/m/Y H:i', strtotime($row['created_at']))
      ];
    }
  } else {
    // ดึงจาก products
    $stmt = $pdo->prepare("SELECT p.name, p.sku, p.stock_quantity, c.name AS category,
                                  b.name AS brand, p.cost_price, p.wholesale_price, p.sell_price, p.created_at
                           FROM products p
                           JOIN categories c ON p.category_id = c.id
                           LEFT JOIN brands b ON p.brand_id = b.id
                           WHERE p.category_id = ?
                           ORDER BY p.name ASC");
    $stmt->execute([$category_id]);
    $items = $stmt->fetchAll();

    foreach ($items as $row) {
      $results[] = [
        'name' => $row['name'],
        'sku' => $row['sku'],
        'category' => $row['category'],
        'brand' => $row['brand'],
        'imei1' => null,
        'status' => null,
        'stock_quantity' => $row['stock_quantity'],
        'cost_price' => $row['cost_price'],
        'wholesale_price' => $row['wholesale_price'],
        'sell_price' => $row['sell_price'],
        'created_at' => date('d/m/Y H:i', strtotime($row['created_at']))
      ];
    }
  }

} else {
  // ทุกหมวด - รวมทั้ง trackable และไม่ trackable
  // Trackable (products_items)
  $stmt = $pdo->query("SELECT pi.imei1, pi.status, pi.cost_price, pi.wholesale_price, pi.sell_price,
                              pi.created_at, p.name, p.sku, c.name AS category, b.name AS brand
                       FROM products_items pi
                       JOIN products p ON pi.product_id = p.id
                       JOIN categories c ON p.category_id = c.id
                       LEFT JOIN brands b ON p.brand_id = b.id
                       WHERE pi.status = 'in_stock'
                       ORDER BY pi.created_at DESC");
  foreach ($stmt->fetchAll() as $row) {
    $results[] = [
      'name' => $row['name'],
      'sku' => $row['sku'],
      'category' => $row['category'],
      'brand' => $row['brand'],
      'imei1' => $row['imei1'],
      'status' => $row['status'],
      'stock_quantity' => 1,
      'cost_price' => $row['cost_price'],
      'wholesale_price' => $row['wholesale_price'],
      'sell_price' => $row['sell_price'],
      'created_at' => date('d/m/Y H:i', strtotime($row['created_at']))
    ];
  }

  // Non-trackable (products)
  $stmt = $pdo->query("SELECT p.name, p.sku, p.stock_quantity, c.name AS category,
                              b.name AS brand, p.cost_price, p.wholesale_price, p.sell_price, p.created_at
                       FROM products p
                       JOIN categories c ON p.category_id = c.id
                       LEFT JOIN brands b ON p.brand_id = b.id
                       ORDER BY p.name ASC");
  foreach ($stmt->fetchAll() as $row) {
    $results[] = [
      'name' => $row['name'],
      'sku' => $row['sku'],
      'category' => $row['category'],
      'brand' => $row['brand'],
      'imei1' => null,
      'status' => null,
      'stock_quantity' => $row['stock_quantity'],
      'cost_price' => $row['cost_price'],
      'wholesale_price' => $row['wholesale_price'],
      'sell_price' => $row['sell_price'],
      'created_at' => safe_date($row['created_at'])
    ];
  }
}

header('Content-Type: application/json');
echo json_encode($results);
