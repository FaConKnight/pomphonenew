<?php
// /cooladmin/api/line_webhook.php
// LINE BOT Webhook - รับรูปสลิปและบันทึกลง saving_pending
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('SECURE_ACCESS', true);
require_once('../includes/connectdb.php');

$channelSecret = 'f7b887f0ac567200de61a3a0b8f9f46f';
$accessToken = 'b2Ef1Gsg7mPp3+i9r0Lr8F2Lx9sUBuCu1wTstwJtJgkfL2DfJsMi15BRKPCdWEndn+2E+WolSG62hHqU5fR/oaAPpwArvm9e0GiJXs8x6yIjBGSm3tyqnsFEOHKqkObeLDkPruvsqSvLuCAGYw7/hwdB04t89/1O/w1cDnyilFU=';

$body = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_LINE_SIGNATURE'] ?? '';

// ยืนยันลายเซ็น LINE
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
    $replyToken = $event['replyToken'];
    $receivedText = $event['message']['text'];

    $replyMessage = [
      'replyToken' => $replyToken,
      'messages' => [[
        'type' => 'text',
        'text' => "คุณพิมพ์ว่า: $receivedText "
      ]]
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/reply');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($replyMessage));
    $result = curl_exec($ch);
    curl_close($ch);
  }
}

http_response_code(200);
echo "OK";
