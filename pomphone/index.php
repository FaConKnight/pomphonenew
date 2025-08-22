<?php
// /cooladmin/index.php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit;
}

$employee_rank = $_SESSION['employee_rank'] ?? 0;

// redirect ตามสิทธิ์ role
switch ($employee_rank) {
    case 100:
    case 99:
    case 88:
        header("Location: managers/index.php");
        exit;
        break;
    case 77:
    case 11:
        header("Location: pos/sale.php");
        exit;
        break;
    default:
        echo "\u274c บัญชีของคุณถูกปิดใช้งานหรือไม่มีสิทธิ์เข้าถึงระบบ";
        session_destroy();
        break;
}
exit;