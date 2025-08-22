<?php
// line_functions.php - helper สำหรับระบบแจ้งเตือน LINE

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/line_config.php';


function replyMessage($replyToken, $messages) {
    global $accessToken;

    $data = [
        'replyToken' => $replyToken,
        'messages' => is_array($messages[0]) ? $messages : [$messages]
    ];

    $ch = curl_init('https://api.line.me/v2/bot/message/reply');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    $result = curl_exec($ch); 
    curl_close($ch);
    return $result;
}

function pushMessage($to, $messages) {
    global $accessToken;
    $data = ['to' => $to, 'messages' => $messages];

    $ch = curl_init("https://api.line.me/v2/bot/message/push");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$accessToken}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpcode >= 400) {
        error_log("LINE API error ($httpcode): " . $result);
    }

    curl_close($ch);
    return $httpcode === 200; //$result
}

function sendLineText($userId, $message)
{   
    global $accessToken;
    $url = "https://api.line.me/v2/bot/message/push";
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer {$accessToken}"
    ];
    $body = json_encode([
        "to" => $userId,
        "messages" => [[
            "type" => "text",
            "text" => $message
        ]]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    return $error ? ["success" => false, "error" => $error] : ["success" => true, "response" => $response];
}
function sendLineFlex($userId, $flexContent, $name)
{   
    global $accessToken;
    $url = "https://api.line.me/v2/bot/message/push";
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer {$accessToken}"
    ];
    $body = json_encode([
        "to" => $userId,
        "messages" => [[
            "type" => "flex",
            "altText" => $name,
            "contents" => json_decode($flexContent, true)
        ]]
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    return $error ? ["success" => false, "error" => $error] : ["success" => true, "response" => $response];
}

function getLineProfile($userId) {
    global $accessToken;
    $url = "https://api.line.me/v2/bot/profile/{$userId}";

    $headers = [
        "Authorization: Bearer {$accessToken}"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

function send_line_message_customer($pdo, $cua_id, $message)
{
    $stmt = $pdo->prepare("SELECT line_user_id FROM line_users WHERE cua_id = ? AND user_type = 'customer' LIMIT 1");
    $stmt->execute([$cua_id]);
    $row = $stmt->fetch();
    if ($row && $row['line_user_id']) {
        return replyMessage($row['line_user_id'], $message);
    }
    return false;
}

function send_line_message_employee($pdo, $ea_id, $message)
{
    $stmt = $pdo->prepare("SELECT line_user_id FROM line_users WHERE ea_id = ? AND user_type = 'employee' LIMIT 1");
    $stmt->execute([$ea_id]);
    $row = $stmt->fetch();
    if ($row && $row['line_user_id']) {
        return replyMessage($row['line_user_id'], $message);
    }
    return false;
}

function isRegisteredUser($userId, $pdo) {
  $stmt = $pdo->prepare("
    SELECT c.cua_id, c.cua_tel
    FROM line_users l
    JOIN customer_account c ON l.cua_id = c.cua_id
    WHERE l.line_user_id = ? AND l.user_type = 'customer'
    LIMIT 1
  ");
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

function notifyRepairStatus($pdo, $tel, $status, $repair_id, $price = null) {
    // ค้น cua_id จากเบอร์โทร
    $stmt = $pdo->prepare("SELECT cua_id FROM customer_account WHERE cua_tel = ? LIMIT 1");
    $stmt->execute([$tel]);
    $cua_id = $stmt->fetchColumn();
    if (!$cua_id) return false;

    // หา line_user_id จาก line_users
    $stmt = $pdo->prepare("SELECT line_user_id FROM line_users WHERE cua_id = ? AND user_type = 'customer' LIMIT 1");
    $stmt->execute([$cua_id]);
    $line_id = $stmt->fetchColumn();
    if (!$line_id) return false;

    // สร้างข้อความแบบ interpolated string
    switch ($status) {
        case 'received':
            $msg = "🛠️ ร้านได้รับเครื่องซ่อมของคุณแล้ว (งาน #{$repair_id})";
            break;
        case 'returned':
            $msg = "📬 เครื่องซ่อมเสร็จแล้ว รอคุณมารับ ยอดรวม: {$price} บาท";
            break;
        case 'picked_up':
            $msg = "✅ คุณได้รับเครื่องคืนเรียบร้อยแล้ว";
            break;
        case 'cancelled':
            $msg = "❌ งานซ่อมถูกยกเลิก";
            break;
        default:
            $msg = null;
    }

    return $msg ? replyMessage($line_id, [['type' => 'text', 'text' => $msg]]) : false;
}


function send_promotion_to_tag($pdo, $tag_name, $message) {
    $stmt = $pdo->prepare("SELECT lu.line_user_id FROM line_users lu
                           JOIN tag_assignments ta ON lu.cua_id = ta.cua_id
                           JOIN customer_tags ct ON ta.tag_id = ct.id
                           WHERE ct.name = ? AND lu.user_type = 'customer'");
    $stmt->execute([$tag_name]);
    $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($recipients as $line_id) {
        replyMessage($line_id, [['type' => 'text', 'text' => $message]]);
    }

    return count($recipients);
}




///////////////////////////////////////////////////////////////////////////////////
















?>
