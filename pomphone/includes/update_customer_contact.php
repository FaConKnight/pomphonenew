<?php
// /cooladmin/manager/update_customer_contact.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

header('Content-Type: text/plain');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo "Method not allowed";
  exit;
}

$employee_id = $_SESSION['employee_id'] ?? null;
$rank = $_SESSION['employee_rank'] ?? 0;
if (!$employee_id || $rank < 77) {
  echo "Access denied";
  exit;
}

$cua_id = $_POST['cua_id'] ?? null;
$new_tel = trim($_POST['new_tel'] ?? '');
$new_lineid = trim($_POST['new_lineid'] ?? '');
$new_address = trim($_POST['new_address'] ?? '');
$new_facebook = trim($_POST['new_facebook'] ?? '');
$ps_id = trim($_POST['ps_id'] ?? '');
$new_note = trim($_POST['new_note'] ?? '');

if (!$cua_id) {
  echo "Missing customer ID";
  exit;
}

try {
  $pdo->beginTransaction();

  // บันทึกค่าเดิมไว้สำหรับ log
  $stmt_old = $pdo->prepare("SELECT * FROM customer_account a
                              LEFT JOIN customer_details d ON a.cua_id = d.cua_id
                              WHERE a.cua_id = ? LIMIT 1");
  $stmt_old->execute([$cua_id]);
  $old = $stmt_old->fetch();

  if (!$old) {
    throw new Exception("ไม่พบลูกค้า");
  }
  if($ps_id==null || $ps_id =='undefined'){
    $ps_id = $old['cu_psid'];
  }
  // อัปเดต customer_account (เฉพาะเบอร์โทร)
  if ($new_tel && $new_tel !== $old['cua_tel']) {
    $pdo->prepare("UPDATE customer_account SET cua_tel = ? WHERE cua_id = ?")
        ->execute([$new_tel, $cua_id]);

    $pdo->prepare("INSERT INTO system_logs (action_type, module, ref_id, employee_id, detail, created_at)
                   VALUES ('update', 'customer_account_tel', ?, ?, ?, NOW())")
        ->execute([$cua_id, $employee_id, "เปลี่ยนเบอร์โทรจาก {$old['cua_tel']} เป็น {$new_tel}"]);
  }

  // อัปเดต customer_details
  $stmt = $pdo->prepare("UPDATE customer_details
                         SET old_lineid = ?, cu_addess = ?, cu_facebook = ?, cu_note = ?, cu_psid = ?
                         WHERE cua_id = ?");
  $stmt->execute([$new_lineid, $new_address, $new_facebook, $new_note, $ps_id, $cua_id]);

  $log_desc = [];
  if ($new_lineid !== $old['old_lineid']) {
    $log_desc[] = "เปลี่ยน LINE ID จาก {$old['old_lineid']} เป็น {$new_lineid}";
  }
  if ($new_address !== $old['cu_addess']) {
    $log_desc[] = "เปลี่ยนที่อยู่จาก {$old['cu_addess']} เป็น {$new_address}";
  }
  if ($new_facebook !== $old['cu_facebook']) {
    $log_desc[] = "เปลี่ยน Facebook จาก {$old['cu_facebook']} เป็น {$new_facebook}";
  }
  if ($ps_id !== $old['cu_psid']) {
    $log_desc[] = "เปลี่ยน PSID จาก {$old['cu_psid']} เป็น {$ps_id}";
  }
  if ($new_note !== $old['cu_note']) {
    $log_desc[] = "เปลี่ยนหมายเหตุจาก {$old['cu_note']} เป็น {$new_note}";
  }

  foreach ($log_desc as $desc) {
    $pdo->prepare("INSERT INTO system_logs (action_type, module, ref_id, employee_id, detail, created_at)
                   VALUES ('update', 'customer_details', ?, ?, ?, NOW())")
        ->execute([$cua_id, $employee_id, $desc]);
  }

  $pdo->commit();
  echo "success";
} catch (Exception $e) {
  $pdo->rollBack();
  echo "เกิดข้อผิดพลาด: " . $e->getMessage();
}
