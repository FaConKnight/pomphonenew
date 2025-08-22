<?php
// ajax/fetch_sale_for_return.php
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['employee_id'])) {
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$receipt_no = trim($_POST['receipt_no'] ?? '');
if (empty($receipt_no)) {
    exit(json_encode(['success' => false, 'message' => 'กรุณากรอกเลขที่ใบเสร็จ']));
}

$stmt = $pdo->prepare("SELECT id FROM sale WHERE receipt_no = ?");
$stmt->execute([$receipt_no]);
$sale = $stmt->fetch();

if (!$sale) {
    exit(json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลใบเสร็จ']));
}

// ดึงรายการสินค้าที่ "ยังสามารถคืนได้"
$sql = "
    SELECT
        si.id as original_sale_item_id,
        p.name as product_name,
        si.price as original_price,
        si.imei,
        si.qty as original_qty,
        (SELECT COALESCE(SUM(sri.quantity), 0) FROM sale_return_items sri WHERE sri.original_sale_item_id = si.id) as returned_qty
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$sale['id']]);
$items = $stmt->fetchAll();

$returnable_items = [];
foreach ($items as $item) {
    if ($item['original_qty'] > $item['returned_qty']) {
        $item['qty_available_for_return'] = $item['original_qty'] - $item['returned_qty'];
        $returnable_items[] = $item;
    }
}

if (empty($returnable_items)) {
    exit(json_encode(['success' => false, 'message' => 'สินค้าในใบเสร็จนี้ถูกคืนทั้งหมดแล้ว']));
}

echo json_encode(['success' => true, 'items' => $returnable_items]);