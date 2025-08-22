<?php
// ajax/fetch_return_items.php - ดึงรายการสินค้าที่คืนได้จากเลขใบเสร็จ

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['employee_id']) || $_SESSION['employee_rank'] < 1) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$receipt_no = trim($_POST['receipt_no'] ?? '');
if ($receipt_no === '') {
    echo json_encode(['success' => false, 'message' => 'กรุณาระบุเลขใบเสร็จ']);
    exit;
}

try {
    // 🔍 ดึง sale_id จากเลขใบเสร็จ
    $stmt = $pdo->prepare("SELECT id FROM sale WHERE receipt_no = ? LIMIT 1");
    $stmt->execute([$receipt_no]);
    $sale = $stmt->fetch();

    if (!$sale) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบเลขใบเสร็จนี้']);
        exit;
    }

    $sale_id = $sale['id'];

    // 🔄 ดึงสินค้าทั้งหมดจาก sale_items
    $stmt = $pdo->prepare("SELECT si.*, p.name 
        FROM sale_items si 
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?");
    $stmt->execute([$sale_id]);
    $items = $stmt->fetchAll();

    // 🔎 ดึง sale_item_id ทั้งหมดที่ถูกคืนแล้ว (ไม่ใช้ WHERE sale_id)
    $stmt = $pdo->prepare("SELECT sale_item_id FROM sale_return_items");
    $stmt->execute();
    $returned_ids = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'sale_item_id');

    // ✅ คัดกรองเฉพาะที่ยังไม่ถูกคืน
    $returnable = [];
    foreach ($items as $item) {
        if (in_array($item['id'], $returned_ids)) continue;

        $returnable[] = [
            'id' => $item['id'],
            'product_id' => $item['product_id'],
            'name' => $item['name'],
            'price' => floatval($item['price']),
            'imei' => $item['imei'] ?? null,
            'qty' => intval($item['qty']),
        ];
    }

    echo json_encode([
        'success' => true,
        'items' => $returnable
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
