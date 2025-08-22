<?php
// line_helper.php - helper สำหรับระบบแจ้งเตือน LINE
define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

function notifyRepairStatus($pdo, $tel, $status, $repair_id, $price = null) {
    // ค้น cua_id จากเบอร์โทร
    $stmt = $pdo->prepare("SELECT cua_id FROM customer_account WHERE cua_tel = ? LIMIT 1");
    $stmt->execute([$tel]);
    $cua_id = $stmt->fetchColumn();
    if (!$cua_id) return false;

    // หา line_user_id จาก line_users
    $stmt = $pdo->prepare("SELECT line_user_id FROM line_users WHERE cua_id = ? AND user_type = 'customer' LIMIT 1");
    $stmt->execute([$cua_id]);
    $line_id = $stmt->fetchColumn();
    if (!$line_id) return false;

    // สร้างข้อความ
    $msg = match($status) {
        'received' => "🛠️ ร้านได้รับเครื่องซ่อมของคุณแล้ว (งาน #$repair_id)",
        'sent'     => "📦 เครื่องถูกส่งไปยังร้านซ่อมแล้ว",
        'done'     => "✅ เครื่องซ่อมเสร็จแล้ว ยอดที่ต้องชำระ: {$price} บาท (งาน #$repair_id)",
        default    => null
    };

    return $msg ? pushMessage($line_id, [['type' => 'text', 'text' => $msg]]) : false;
}

