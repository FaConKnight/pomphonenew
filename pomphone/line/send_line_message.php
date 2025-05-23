<?php
// send_line_message.php - ใช้ส่งข้อความ LINE OA ไปยังลูกค้าหรือพนักงาน

define('SECURE_ACCESS', true);
require_once("../includes/connectdb.php");

function send_line_message($line_user_id, $message)
{
    $access_token = "YOUR_LINE_CHANNEL_ACCESS_TOKEN";

    $data = [
        'to' => $line_user_id,
        'messages' => [[
            'type' => 'text',
            'text' => $message
        ]]
    ];

    $ch = curl_init("https://api.line.me/v2/bot/message/push");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer {$access_token}"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

function send_line_message_customer($pdo, $cua_id, $message)
{
    $stmt = $pdo->prepare("SELECT line_user_id FROM line_users WHERE cua_id = ? AND user_type = 'customer' LIMIT 1");
    $stmt->execute([$cua_id]);
    $row = $stmt->fetch();
    if ($row && $row['line_user_id']) {
        return send_line_message($row['line_user_id'], $message);
    }
    return false;
}

function send_line_message_employee($pdo, $ea_id, $message)
{
    $stmt = $pdo->prepare("SELECT line_user_id FROM line_users WHERE ea_id = ? AND user_type = 'employee' LIMIT 1");
    $stmt->execute([$ea_id]);
    $row = $stmt->fetch();
    if ($row && $row['line_user_id']) {
        return send_line_message($row['line_user_id'], $message);
    }
    return false;
}
