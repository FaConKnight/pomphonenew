<?php
// ป้องกันการเข้าตรงไฟล์
if (!defined('SECURE_ACCESS')) {
    die("Access denied");
}

// ตั้งค่าระบบ
ob_start();
date_default_timezone_set('Asia/Bangkok');
if (!isset($_SESSION)) {
    session_start();
}

// DB Config
define('DB_HOST', 'localhost');
define('DB_NAME', 'pomphone_shop1');
define('DB_USER', 'pomphone_shop');
define('DB_PASS', 'Chanatip1');

// Connect แบบ PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

?>
