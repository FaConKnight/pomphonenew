<?php
// /backend1/includes/get_products_by_category.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
header('Content-Type: application/json');

$category_id = $_GET['category_id'] ?? null;
$brand_id = $_GET['brand_id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    echo json_encode([]);
    exit;
}

// กรองตาม category และ brand หากมี
$where = ['category_id = ?'];
$params = [$category_id];

if ($brand_id && is_numeric($brand_id)) {
    $where[] = 'brand_id = ?';
    $params[] = $brand_id;
}

$sql = "SELECT id, name, is_trackable, cost_price, sell_price, wholesale_price, sku
        FROM products
        WHERE " . implode(' AND ', $where) . " AND is_active = 1
        ORDER BY name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
