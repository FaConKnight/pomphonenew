<?php

// ใน includes/bootstrap.php หรือ includes/functions.php
if (!function_exists('safe_text')) {
    function safe_text($str): string {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
function safe_date($datetime, $format = 'd/m/Y H:i') {
    if (empty($datetime)) return '-';
    $ts = strtotime($datetime);
    return $ts !== false ? date($format, $ts) : '-';
}

function cheage_date($datetime, $format = 'd/m/Y') {
    if (empty($datetime)) return '-';
    $ts = strtotime($datetime);
    return $ts !== false ? date($format, $ts) : '-';
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_start();
    session_destroy();
    session_unset();
    $host  = $_SERVER['HTTP_HOST'];
    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $extra = '';
    header("Location: https://$host$uri/$extra");
    header("Location: http://" . $_SERVER['HTTP_HOST']);
    exit;
}

function encodeYear($year) {
    // A = 2020 → Z = 2045
    return chr(65 + ($year - 2020));
}

function encodeMonth($month) {
    // A = Jan, B = Feb, ..., L = Dec
    return chr(65 + ($month - 1));
}

function encodeTwoDigitsToTwoAlpha($digits) {
    // Map 00–99 → AA, AB, ..., Z9
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; // 36 ตัว
    $first = intval($digits[0]);
    $second = intval($digits[1]);
    
    // ใช้ mapping แบบ 36x36 → 1296 ชุด
    $index = intval($digits);
    $a = $alphabet[intdiv($index, 36)];
    $b = $alphabet[$index % 36];
    return $a . $b;
}

function encodeReceiptReference($receiptCode) {
    // เช่น RC20250800070
    $year = intval(substr($receiptCode, 2, 4));   // 2025
    $month = intval(substr($receiptCode, 6, 2));  // 08
    $number = substr($receiptCode, 8, 5);         // 00070

    $first2 = substr($number, 0, 2);              // "00"
    $last3  = substr($number, -3);                // "070"
    $first2Encoded = encodeTwoDigitsToTwoAlpha($first2);

    $last2 = substr($last3, 0, 2);                // "07"
    $suffix = substr($last3, 2, 1);               // "0"
    $randChar = chr(rand(65, 90));                // A–Z

    $prefix = encodeYear($year) . encodeMonth($month); // FH

    return $prefix . $first2Encoded . $last2 . $randChar . $suffix;
}

function getPublicCode($fullRef) {
    return substr($fullRef, -6); // 6 ตัวท้าย
}

?>
