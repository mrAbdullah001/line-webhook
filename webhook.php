<?php
$access_token = 'zJVzvgBKb7OrZ3tGN/NwjssACGGXEGZ06rJIqRQE2N5l9+atKqCcMAEWMxE3rb7Ep+90vMDcDJuRhI+87I8YrvY1KFzkWWeQDR0dplvRwthvp51vdi6MBSFJyyvofoN1z8TEHmMp9O+juPg8LofhegdB04t89/1O/w1cDnyilFU=';

$host = 'sql12.freesqldatabase.com';

$dbname = 'sql12801123';

$username = 'sql12801123';

$password = 'x2jaUxJ2dN';



try {

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage());

}


$content = file_get_contents('php://input');
file_put_contents("debug_request.log", date("Y-m-d H:i:s") . " | " . $content . PHP_EOL, FILE_APPEND);

$events = json_decode($content, true);

if (!empty($events['events'])) {
    foreach ($events['events'] as $event) {
        if ($event['type'] === 'postback') {
            $replyToken = $event['replyToken'];
            parse_str($event['postback']['data'], $params);

            if (!empty($params['summary_date'])) {
                $date = $params['summary_date'];

                // ดึงข้อมูล orders
                $sql = "SELECT price, quantity, flavors FROM orders WHERE delivery_date=?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$date]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $priceCount = [];
                $flavorCount = [];
                $flavorBagCount = [];

                foreach ($orders as $order) {
                    $price = $order['price'];
                    $flavors = json_decode($order['flavors'], true);

                    if ($price == 1200) $bagSize = 'small';
                    elseif ($price == 1600) $bagSize = 'medium';
                    elseif ($price == 2000) $bagSize = 'large';
                    else $bagSize = 'small';

                    $priceCount[$price] = ($priceCount[$price] ?? 0) + 1;

                    if (!empty($flavors)) {
                        foreach ($flavors as $flavor => $qty) {
                            $flavorCount[$flavor] = ($flavorCount[$flavor] ?? 0) + $qty;

                            if (!isset($flavorBagCount[$flavor])) {
                                $flavorBagCount[$flavor] = ['small'=>0,'medium'=>0,'large'=>0];
                            }
                            $flavorBagCount[$flavor][$bagSize] += $qty;
                        }
                    }
                }

                $summary = "📦 สรุปยอดวันที่ ".date('d/m/Y', strtotime($date))."\n\n";
                $summary .= "📊 จำนวนออเดอร์:\n";
                foreach ($priceCount as $price=>$cnt) {
                    $summary .= "ราคา $price บาท: $cnt ถัง\n";
                }

                $summary .= "\n🍨 รสชาติ:\n";
                foreach ($flavorCount as $flavor=>$cnt) {
                    $summary .= "รส $flavor: $cnt ถุง\n";
                }

                $summary .= "\n📦 ขนาดถุง:\n";
                foreach ($flavorBagCount as $flavor=>$bags) {
                    $summary .= "รส $flavor\n";
                    $summary .= "  . ถุงเล็ก: {$bags['small']}\n";
                    $summary .= "  . ถุงกลาง: {$bags['medium']}\n";
                    $summary .= "  . ถุงใหญ่: {$bags['large']}\n";
                }

                $messages = [[ 'type' => 'text', 'text' => $summary ]];
                $url = 'https://api.line.me/v2/bot/message/reply';
                $headers = [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token
                ];
                $post_data = json_encode([
                    'replyToken' => $replyToken,
                    'messages' => $messages
                ], JSON_UNESCAPED_UNICODE);

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                $result = curl_exec($ch);

                // ✅ Log response ของ LINE API
                file_put_contents("debug_response.log", date("Y-m-d H:i:s") . " | " . $result . PHP_EOL, FILE_APPEND);

                if (curl_errno($ch)) {
                    file_put_contents("debug_response.log", "CURL Error: " . curl_error($ch) . PHP_EOL, FILE_APPEND);
                }

                curl_close($ch);
            }

            // ... (โค้ด orders_date เหมือนเดิม แนะนำให้ใส่ log ด้วยแบบเดียวกัน)
        }
    }
}

http_response_code(200);




