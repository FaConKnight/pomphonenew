<?php
// /cooladmin/includes/save_company.php

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('../includes/session.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

$name_th = trim($_POST['name_th'] ?? '');
$name_en = trim($_POST['name_en'] ?? '');
$taxid = trim($_POST['taxid'] ?? '');
$contact = trim($_POST['contact_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$note = trim($_POST['note'] ?? '');

$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id || ($_SESSION['employee_rank'] ?? 0) < 77) {
  http_response_code(403);
  exit('Permission Denied');
}

if (!$name_th || !$taxid) {
  exit('กรุณากรอกข้อมูลบริษัทให้ครบถ้วน');
}

try {
  $stmt = $pdo->prepare("INSERT INTO suppliers (name_th, name_en, taxid, contact_name, phone, email, address, note, created_at, updated_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
  $stmt->execute([$name_th, $name_en, $taxid, $contact, $phone, $email, $address, $note]);

  // Log
  $log = $pdo->prepare("INSERT INTO system_logs (module, action_type, employee_id, detail, created_at)
                        VALUES ('supplier', 'add_supplier', ?, ?, NOW())");
  $log->execute([$employee_id, "เพิ่มบริษัทใหม่: $name_th ($taxid)"]);

  echo 'success';
} catch (Exception $e) {
  http_response_code(500);
  echo 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}
