<?php
// /line/line_webhook.php
// LINE BOT Webhook อัจฉริยะ

define('SECURE_ACCESS', true);
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/line_config.php';
require_once __DIR__ . '/line_functions.php';
  
$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';

if (hash_hmac('sha256', $body, $channelSecret, true) !== base64_decode($signature)) {
  http_response_code(400);
  echo "Invalid signature";
  exit;
}

$data = json_decode($body, true);
if (!isset($data['events'])) {
  http_response_code(200);
  echo "No event";
  exit;
}

foreach ($data['events'] as $event) {
  
  if ($event['type'] === 'message' && $event['message']['type'] === 'text' ) {
    $replyToken = $event['replyToken'];
    $userId = $event['source']['userId'];
    $text = trim($event['message']['text']);
    $reply = '';

    // คำสั่งที่ต้องเช็คว่าลูกค้าผูกบัญชีแล้วหรือยัง
    $requires_registration = preg_match('/ยอด|ดูยอด|ยอดปัจจุบัน|ยอดล่าสุด|ประวัติ|แจ้งยอดก่อนหน้า|โอน/i', $text);

    if ($requires_registration) {
      $user = isRegisteredUser($userId, $pdo);
      
      if (!$user) {
        $link = 'https://pomphone.com/backend1/line/register_line.php?id_line=' . urlencode($userId);
        $reply = [
          'type' => 'flex',
          'altText' => '🔐 กรุณาลงทะเบียนก่อนใช้งาน',
          'contents' => [
            'type' => 'bubble',
            'body' => [
              'type' => 'box',
              'layout' => 'vertical',
              'contents' => [
                ['type' => 'text', 'text' => '🔐 ยังไม่ได้ลงทะเบียน', 'weight' => 'bold', 'size' => 'lg'],
                ['type' => 'text', 'text' => 'กรุณาลงทะเบียนก่อนใช้งานระบบออมมือถือ', 'wrap' => true, 'size' => 'sm', 'color' => '#666666']
              ]
            ],
            'footer' => [
              'type' => 'box',
              'layout' => 'vertical',
              'spacing' => 'sm',
              'contents' => [[
                'type' => 'button',
                'style' => 'primary',
                'action' => [
                  'type' => 'uri',
                  'label' => '📋 ลงทะเบียน',
                  'uri' => $link
                ]
              ]]
            ]
          ]
        ];
      } else { 
        ///////// Start ต้องลงทะเบียนถึงใช้งานได้  ///////////////
        $tel = $user['cua_tel'];
        $cua_id = $user['cua_id'];

        if (preg_match('/ดูยอด|ยอดปัจจุบัน|ยอดล่าสุด/i', $text)) {
          $summary = getSavingSummary($cua_id, $pdo);
          if ($summary) {
            $reply = [
              'type' => 'flex',
              'altText' => '📊 ยอดออมล่าสุด',
              'contents' => buildFlexSavingSummary($summary)
            ];
          } else {
            $reply = ['type' => 'text', 'text' => '❌ ไม่พบบัญชีออมมือถือของคุณในระบบ'];
          }

        } elseif (preg_match('/ประวัติ|แจ้งยอดก่อนหน้า|รายการก่อนหน้า/i', $text)) {
          $pending = getPendingTransfers($tel, $pdo);
          if ($pending) {
            $reply = [
              'type' => 'flex',
              'altText' => '📄 ประวัติการแจ้งยอด',
              'contents' => buildFlexPendingList($pending)
            ];
          } else {
            $reply = ['type' => 'text', 'text' => '📭 ไม่มีประวัติแจ้งโอนล่าสุดในระบบ'];
          }
        } elseif (preg_match('/แจ้งยอด|โอนเงิน|แจ้งโอน/i', $text)) {
          $reply = [
            'type' => 'flex',
            'altText' => '📥 แจ้งโอนเงิน',
            'contents' => buildFlexPaymentForm($userId)
          ];
        } else {
          $reply = ['type' => 'text', 'text' => "❌ ไม่เข้าใจคำสั่ง โปรดพิมพ์:\n ดูยอด | ประวัติ | แจ้งยอด"];
        }
      }


      ///////// END ต้องลงทะเบียนถึงใช้งานได้  ///////////////
    } elseif(preg_match('/เมนู|ทำอะไรได้บ้าง|ขอเมนู/i', $text)) {
      $reply = ['type' => 'text', 'text' => '❓ พิมพ์ "ออมมือถือ", "ซ่อม", หรือ "ติดต่อเจ้าหน้าที่" เพื่อใช้งานระบบ'];
    } elseif(preg_match('/ออม|เก็บเงิน|ออมมือถือ/i', $text)) {
      $reply = ['type' => 'text', 'text' => '❓ พิมพ์ "ดูยอด", "ประวัติ", หรือ "แจ้งโอน" เพื่อใช้งานระบบ'];
    } elseif(preg_match('/ซ่อม|เครื่องซ่อม|สถานะซ่อม/i', $text)) {
      $reply = ['type' => 'text', 'text' => '❓ พิมพ์ "สถานะซ่อม" เพื่อใช้งานระบบ'];
    } elseif($text == 'ทดสอบระบบ') {
      $reply = ['type' => 'text', 'text' => "$replyToken+$userId"];
    } else {

    }
    // ส่งคำตอบกลับ
    replyMessage($replyToken, [$reply]);
  }
}

echo "OK";

