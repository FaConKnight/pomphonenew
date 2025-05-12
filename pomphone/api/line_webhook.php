<?php
// /cooladmin/api/line_webhook.php
// LINE BOT Webhook - ตอบกลับแบบฟอร์ม หรือให้ลงทะเบียน ถ้าไม่พบ LINE ID ในระบบ

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');

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
    $userId = $event['source']['userId'];
    $text = trim($event['message']['text']);

    if (preg_match('/ยอด|โอน|แจ้งยอด/i', $text)) {
      $replyToken = $event['replyToken'];

      try {
        $check = $pdo->prepare("SELECT cua_id FROM customer_account WHERE cua_lineid = ? LIMIT 1");
        $check->execute([$userId]);

        if ($check->rowCount() === 0) {
          $registerLink = "https://pomphone.com/backend1/api/register_line.php?id_line=" . urlencode($userId);
          $flexRegister = [
            'type' => 'flex',
            'altText' => '🔐 ลงทะเบียนลูกค้า',
            'contents' => [
              'type' => 'bubble',
              'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                  [
                    'type' => 'text',
                    'text' => '🔐 กรุณาลงทะเบียนก่อนใช้งาน',
                    'weight' => 'bold',
                    'size' => 'lg',
                    'wrap' => true
                  ],
                  [
                    'type' => 'text',
                    'text' => 'คลิกปุ่มด้านล่างเพื่อไปหน้าลงทะเบียน',
                    'size' => 'sm',
                    'color' => '#666666',
                    'wrap' => true
                  ]
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
                    'label' => '📝 ไปหน้าลงทะเบียน',
                    'uri' => $registerLink
                  ]
                ]]
              ]
            ]
          ];

          $response = [
            'replyToken' => $replyToken,
            'messages' => [$flexRegister]
          ];
        } else {
          $link = "https://pomphone.com/backend1/saving/saving_form.php?id_line=" . urlencode($userId);

          $flexMessage = [
            'type' => 'flex',
            'altText' => '📥 แจ้งการโอนเงินออมมือถือ',
            'contents' => [
              'type' => 'bubble',
              'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                  [
                    'type' => 'text',
                    'text' => '📥 แจ้งโอนเงินออมมือถือ',
                    'weight' => 'bold',
                    'size' => 'lg',
                    'wrap' => true
                  ],
                  [
                    'type' => 'text',
                    'text' => 'กรุณาคลิกปุ่มด้านล่างเพื่อกรอกแบบฟอร์มแจ้งยอดโอน',
                    'size' => 'sm',
                    'color' => '#666666',
                    'wrap' => true
                  ]
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
                    'label' => '📤 ไปยังแบบฟอร์ม',
                    'uri' => $link
                  ]
                ]]
              ]
            ]
          ];

          $response = [
            'replyToken' => $replyToken,
            'messages' => [$flexMessage]
          ];
        }

        if (isset($response)) {
          $ch = curl_init('https://api.line.me/v2/bot/message/reply');
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
          ]);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($response));
          $result = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);

          error_log("LINE Reply [$httpCode]: $result");
          error_log("REPLY JSON: " . json_encode($response));
        }
      } catch (Throwable $e) {
        error_log("LINE Error: " . $e->getMessage());
      }
    }
  }
}

echo "OK";
