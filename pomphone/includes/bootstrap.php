<?php
// includes/bootstrap.php - โหลดระบบพื้นฐานทั้งหมด
// เรียก config

$config = require_once __DIR__ . '/config.php';
$helpfunction = require_once __DIR__ . '/functions.php';

$allowed_paths = ['/backend1/line/line_webhook.php', '/backend1/line/line_webhook_test.php', '/backend1/line/register_line.php'];

// ตั้งค่า timezone
if (!empty($config['timezone'])) {
    date_default_timezone_set($config['timezone']);
}
if (!defined('SECURE_ACCESS') && !in_array($_SERVER['PHP_SELF'], $allowed_paths)) {
  http_response_code(403);
  exit('Access denied.');
}
// ตั้งค่า error reporting ตาม debug mode
if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
// เริ่ม output buffering เพื่อป้องกัน header error
if (!headers_sent()) {
    ob_start();
}

// เชื่อมต่อฐานข้อมูลด้วย PDO
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,                  // ปิด emulate เพื่อป้องกัน SQL Injection
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Flash Message Handler
if (!isset($_SESSION)) {
    session_start();
}
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = null;
}

function set_flash($message, $type = 'success') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function display_flash() {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        echo '<div class="alert alert-' . htmlspecialchars($flash['type']) . '" style="margin:10px 0;">' . htmlspecialchars($flash['message']) . '</div>';
        $_SESSION['flash'] = null;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);

if (!isset($_SESSION['employee_id']) && $current_page !== 'login.php') {
    header("Location: ../login.php");
    exit;
}