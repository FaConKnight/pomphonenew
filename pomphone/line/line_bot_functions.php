<?php
// /cooladmin/line/line_bot_functions.php

require_once(__DIR__ . '/../includes/connectdb.php');

function isRegisteredUser($userId, $pdo) {
  $stmt = $pdo->prepare("SELECT cua_id, cua_tel FROM customer_account WHERE cua_lineid = ? LIMIT 1");
  $stmt->execute([$userId]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getSavingSummary($cua_id, $pdo) {
  $stmt = $pdo->prepare("SELECT s.*, p.name AS product_name FROM savings s
                         LEFT JOIN products p ON s.product_id = p.id
                         WHERE s.customer_id = ? ORDER BY s.created_at DESC LIMIT 1");
  $stmt->execute([$cua_id]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPendingTransfers($phone, $pdo, $limit = 5) {
  $stmt = $pdo->prepare("SELECT * FROM saving_pending WHERE phone_number = ? ORDER BY created_at DESC LIMIT ?");
  $stmt->bindValue(1, $phone);
  $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buildFlexSavingSummary($summary) {
  return [
    'type' => 'bubble',
    'body' => [
      'type' => 'box',
      'layout' => 'vertical',
      'contents' => [
        ['type' => 'text', 'text' => '📊 ยอดออมมือถือล่าสุด', 'weight' => 'bold', 'size' => 'lg'],
        ['type' => 'separator', 'margin' => 'md'],
        ['type' => 'text', 'text' => 'รุ่น: ' . $summary['product_name'], 'margin' => 'md'],
        ['type' => 'text', 'text' => 'สถานะ: ' . strtoupper($summary['status']), 'margin' => 'sm'],
        ['type' => 'text', 'text' => 'ยอดรวม: ' . number_format($summary['total_price'], 2) . ' ฿', 'margin' => 'sm'],
        ['type' => 'text', 'text' => 'ชำระแล้ว: ' . number_format($summary['paid_amount'], 2) . ' ฿', 'margin' => 'sm'],
        ['type' => 'text', 'text' => 'คงเหลือ: ' . number_format($summary['total_price'] - $summary['paid_amount'], 2) . ' ฿', 'margin' => 'sm']
      ]
    ]
  ];
}

function buildFlexPendingList($list) {
  $items = [];
  foreach ($list as $row) {
    $items[] = [
      'type' => 'box',
      'layout' => 'vertical',
      'spacing' => 'xs',
      'contents' => [
        ['type' => 'text', 'text' => '📅 ' . date('d/m/Y H:i', strtotime($row['created_at'])), 'size' => 'sm'],
        ['type' => 'text', 'text' => '💵 ' . number_format($row['amount_guess'], 2) . ' ฿', 'size' => 'sm'],
        ['type' => 'text', 'text' => '🔗 ดูสลิป', 'size' => 'sm', 'color' => '#007BFF', 'action' => [
          'type' => 'uri', 'label' => 'ดูสลิป', 'uri' => 'https://pomphone.com/backend1/' . ltrim($row['image_path'], './')
        ]]
      ]
    ];
  }

  return [
    'type' => 'bubble',
    'body' => [
      'type' => 'box',
      'layout' => 'vertical',
      'contents' => array_merge([
        ['type' => 'text', 'text' => '📄 ประวัติการแจ้งโอน', 'weight' => 'bold', 'size' => 'lg'],
        ['type' => 'separator', 'margin' => 'md']
      ], $items)
    ]
  ];
}

function buildFlexPaymentForm($userId) {
  $url = 'https://pomphone.com/backend1/saving/saving_form.php?id_line=' . urlencode($userId);
  return [
    'type' => 'bubble',
    'body' => [
      'type' => 'box',
      'layout' => 'vertical',
      'contents' => [
        ['type' => 'text', 'text' => '📥 แจ้งโอนเงินออมมือถือ', 'weight' => 'bold', 'size' => 'lg'],
        ['type' => 'text', 'text' => 'กรุณาคลิกลิงก์ด้านล่างเพื่อกรอกแบบฟอร์ม', 'size' => 'sm', 'color' => '#888888', 'wrap' => true]
      ]
    ],
    'footer' => [
      'type' => 'box',
      'layout' => 'vertical',
      'spacing' => 'sm',
      'contents' => [[
        'type' => 'button',
        'style' => 'primary',
        'height' => 'sm',
        'action' => [
          'type' => 'uri',
          'label' => '📤 กรอกฟอร์มแจ้งโอน',
          'uri' => $url
        ]
      ]]
    ]
  ];
}
