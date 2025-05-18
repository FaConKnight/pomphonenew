<?php
// /cooladmin/api/line_webhook.php
// LINE BOT Webhook อัจฉริยะ

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');
require_once('line_bot_functions.php');

$channelSecret = 'f7b887f0ac567200de61a3a0b8f9f46f';
$accessToken = 'b2Ef1Gsg7mPp3+i9r0Lr8F2Lx9sUBuCu1wTstwJtJgkfL2DfJsMi15BRKPCdWEndn+2E+WolSG62hHqU5fR/oaAPpwArvm9e0GiJXs8x6yIjBGSm3tyqnsFEOHKqkObeLDkPruvsqSvLuCAGYw7/hwdB04t89/1O/w1cDnyilFU=';

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
  if ($event['type'] === 'message' && $event['message']['type'] === 'text') {
    $replyToken = $event['replyToken'];
    $userId = $event['source']['userId'];
    $text = trim($event['message']['text']);
    $reply = '';

    // คำสั่งที่ต้องเช็คว่าลูกค้าผูกบัญชีแล้วหรือยัง
    $requires_registration = preg_match('/ยอด|ดูยอด|ยอดปัจจุบัน|ยอดล่าสุด|ประวัติ|แจ้งยอดก่อนหน้า|โอน/i', $text);

    // คำสั่ง
    //$is_summary = preg_match('/\b(ยอด|ดูยอด|ยอดปัจจุบัน|ยอดล่าสุด)\b/i', $text);
    //$is_history = !$is_summary && preg_match('/\b(ประวัติ|แจ้งยอดก่อนหน้า|รายการก่อนหน้า)\b/i', $text);
    //$is_payment = preg_match('/\b(แจ้งยอด|โอนเงิน|แจ้งโอน)\b/i', $text); // ปรับให้ไม่ขึ้นกับ summary หรือ history

    //error_log('is_summary: ' . $is_summary . 'is_history:'. $is_history . 'is_payment' . $is_payment);
    if ($requires_registration) {
      $user = isRegisteredUser($userId, $pdo);

      if (!$user) {
        $link = 'https://pomphone.com/backend1/register_line.php?id_line=' . urlencode($userId);
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
        }
      }
    }  elseif(preg_match('/เมนู|ทำอะไรได้บ้าง|ขอเมนู/i', $text)) {
      $reply = ['type' => 'text', 'text' => '❓ พิมพ์ "ออมมือถือ", "ซ่อม", หรือ "ติดต่อเจ้าหน้าที่" เพื่อใช้งานระบบ'];
    } elseif(preg_match('/ออม|เก็บเงิน|ออมมือถือ/i', $text)) {
      $reply = ['type' => 'text', 'text' => '❓ พิมพ์ "ดูยอด", "ประวัติ", หรือ "แจ้งโอน" เพื่อใช้งานระบบ'];
    } elseif($text == 'ซ่อม') {
      $reply = ['type' => 'text', 'text' => '❓ พิมพ์ "สถานะซ่อม" เพื่อใช้งานระบบ'];
    } else {

    }

    // ส่งคำตอบกลับ
    $response = [
      'replyToken' => $replyToken,
      'messages' => [$reply]
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/reply');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
    curl_exec($ch);
    curl_close($ch);
  }
}

echo "OK";

