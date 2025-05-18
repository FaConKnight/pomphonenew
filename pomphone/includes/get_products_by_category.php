<?php
// /cooladmin/manager/get_products_by_category.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');
header('Content-Type: application/json');

$category_id = $_GET['category_id'] ?? null;

if (!$category_id || !is_numeric($category_id)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, name, is_trackable, cost_price, sell_price, wholesale_price,sku FROM products WHERE category_id = ? and is_active = ? ORDER BY name ASC");
$stmt->execute([$category_id,1]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
