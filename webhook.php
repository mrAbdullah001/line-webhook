<?php
$access_token = 'zJVzvgBKb7OrZ3tGN/NwjssACGGXEGZ06rJIqRQE2N5l9+atKqCcMAEWMxE3rb7Ep+90vMDcDJuRhI+87I8YrvY1KFzkWWeQDR0dplvRwthvp51vdi6MBSFJyyvofoN1z8TEHmMp9O+juPg8LofhegdB04t89/1O/w1cDnyilFU=';
// รับข้อมูลจาก LINE
$content = file_get_contents('php://input');
$events = json_decode($content, true);

if (!empty($events['events'])) {
    foreach ($events['events'] as $event) {
        if (isset($event['source']['groupId'])) {
            $groupId = $event['source']['groupId'];

            // ตอบกลับ Group ID
            $replyToken = $event['replyToken'];
            $messages = [
                'type' => 'text',
                'text' => "Group ID: $groupId"
            ];

            $url = 'https://api.line.me/v2/bot/message/reply';
            $data = [
                'replyToken' => $replyToken,
                'messages' => [$messages],
            ];
            $post = json_encode($data);

            $headers = [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $access_token,
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            $result = curl_exec($ch);
            curl_close($ch);
        }
    }
}

echo json_encode(["status" => "ok"]);

