<?php
// /cooladmin/includes/save_customer.php

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

$name = trim($_POST['name'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$tel = trim($_POST['tel'] ?? '');
$lineid = trim($_POST['lineid'] ?? '');

$employee_id = $_SESSION['employee_id'] ?? null;
if (!$employee_id || ($_SESSION['employee_rank'] ?? 0) < 77) {
  http_response_code(403);
  exit('Permission Denied');
}

if (!$name || !$lastname || !$tel) {
  exit('กรุณากรอกข้อมูลให้ครบ');
}

try {
  $pdo->beginTransaction();

  // สร้าง username ใหม่
  $latest = $pdo->query("SELECT cua_id FROM customer_account ORDER BY cua_id DESC LIMIT 1")->fetchColumn();
  $running_number = str_pad((int)$latest + 1, 6, '0', STR_PAD_LEFT);
  $year_code = date('y'); // ปี ค.ศ. 2 หลัก เช่น 24
  $new_username = 'CU' . $year_code . $running_number;


  // บันทึก customer_account
  $stmt1 = $pdo->prepare("INSERT INTO customer_account (cua_name, cua_lastname, cua_tel, cua_username) VALUES (?, ?, ?, ?)");
  $stmt1->execute([$name, $lastname, $tel, $new_username]);
  $cua_id = $pdo->lastInsertId();

  // บันทึก customer_details
  $stmt2 = $pdo->prepare("INSERT INTO customer_details (cu_register, cu_addess, old_tel, old_lineid, cu_facebook, cu_note,  cua_id) VALUES (NOW(), '', ?, ?, '', '', ?)");
  $stmt2->execute([$tel, $lineid, $cua_id]);

  // log
  $log = $pdo->prepare("INSERT INTO system_logs (module, action_type, employee_id, detail, created_at) VALUES ('customer', 'add_customer', ?, ?, NOW())");
  $log->execute([$employee_id, "เพิ่มลูกค้าใหม่: $name $lastname ($tel)"]);

  $pdo->commit();
  echo 'success';
} catch (Exception $e) {
  $pdo->rollBack();
  http_response_code(500);
  echo 'เกิดข้อผิดพลาด: ' . $e->getMessage();
}
