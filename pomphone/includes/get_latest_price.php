<?php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json');

$product_id = $_GET['product_id'] ?? null;
$is_trackable = $_GET['trackable'] ?? 0;

if (!$product_id || !is_numeric($product_id)) {
    echo json_encode([]);
    exit;
}

if ($is_trackable) {
    $stmt = $pdo->prepare("SELECT cost_price, sell_price, wholesale_price FROM products_items WHERE product_id = ? ORDER BY id DESC LIMIT 1");
} else {
    $stmt = $pdo->prepare("SELECT cost_price, sell_price, wholesale_price FROM products WHERE id = ?");
}

$stmt->execute([$product_id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($result ?: [
    'cost_price' => '',
    'sell_price' => '',
    'wholesale_price' => '',
    'sku' => ''
]);
