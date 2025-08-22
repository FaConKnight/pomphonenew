<?php 
// pos/ajax/fetch_products.php 
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    http_response_code(403);
    exit('Access denied.');
}

$query = trim($_POST['query'] ?? '');
if ($query === '') {
    exit;
}
// 1️⃣ ดึงสินค้าทั่วไป (ไม่ใช้ IMEI)
$sql1 = "
    SELECT id, name, sell_price, wholesale_price, is_trackable
    FROM products
    WHERE is_trackable = 0 AND (name LIKE :q1 OR sku LIKE :q2)
    ORDER BY name ASC
    LIMIT 10
";
$stmt1 = $pdo->prepare($sql1);
$stmt1->execute([    
    'q1' => "%$query%",
    'q2' => "%$query%",
]);
$products = $stmt1->fetchAll();

// 2️⃣ ดึงมือถือ (trackable + มี IMEI)
$sql2 = "
    SELECT p.id, p.name, i.sell_price, i.imei1, i.wholesale_price
    FROM products_items i
    JOIN products p ON i.product_id = p.id
    WHERE p.is_trackable = 1 AND (
        p.name LIKE :q3 OR i.imei1 LIKE :q4
    ) AND i.status = 'in_stock'
    ORDER BY p.name ASC
    LIMIT 20
";
$stmt2 = $pdo->prepare($sql2);
$stmt2->execute([    
    'q3' => "%$query%",
    'q4' => "%$query%",
]);
$imeis = $stmt2->fetchAll();

// 🔄 รวมผลลัพธ์
foreach ($products as $row):
    $id = $row['id'];
    $name = htmlspecialchars($row['name']);
    $price = number_format($row['sell_price'] ?? 0, 2);
    echo '<a href="#" class="list-group-item list-group-item-action add-to-cart" ';
    echo 'data-id="' . $id . '" ';
    echo 'data-name="' . $name . '" ';
    echo 'data-cost="' . $row['wholesale_price'] . '" ';
    echo 'data-price="' . $row['sell_price'] . '" ';
    echo '>';
    echo $name . ' - ฿' . $price;
    echo '</a>';
endforeach;

foreach ($imeis as $row):
    $id = $row['id'];
    $name = htmlspecialchars($row['name']);
    $imei = htmlspecialchars($row['imei1']);
    $price = number_format($row['sell_price'] ?? 0, 2);
    echo '<a href="#" class="list-group-item list-group-item-action add-to-cart" ';
    echo 'data-id="' . $id . '" ';
    echo 'data-name="' . $name . ' (IMEI: ' . $imei . ')" ';
    echo 'data-cost="' . $row['wholesale_price'] . '" ';
    echo 'data-price="' . $row['sell_price'] . '" ';
    echo 'data-imei="' . $imei . '" ';
    echo '>';
    echo $name . ' - IMEI: ' . $imei . ' - ฿' . $price;
    echo ' <span class="badge badge-info">มือถือ</span>';
    echo '</a>';
endforeach;
