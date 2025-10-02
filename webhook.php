<?php
$access_token = 'zJVzvgBKb7OrZ3tGN/NwjssACGGXEGZ06rJIqRQE2N5l9+atKqCcMAEWMxE3rb7Ep+90vMDcDJuRhI+87I8YrvY1KFzkWWeQDR0dplvRwthvp51vdi6MBSFJyyvofoN1z8TEHmMp9O+juPg8LofhegdB04t89/1O/w1cDnyilFU=';


$host = 'sql306.infinityfree.com';

$dbname = 'if0_40067559_orders';

$username = 'if0_40067559';

$password = '4093692';



try {

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {

    die("Database connection failed: " . $e->getMessage());

}





$content = file_get_contents('php://input');
$events = json_decode($content, true);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // ถ้าเชื่อมต่อ DB ไม่สำเร็จ ให้ส่งข้อความกลับไป LINE
    if (!empty($events['events'])) {
        foreach ($events['events'] as $event) {
            if ($event['type'] === 'postback') {
                $replyToken = $event['replyToken'];
                $messages = [[ 'type' => 'text', 'text' => "❌ เชื่อมต่อฐานข้อมูลไม่สำเร็จ" ]];
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
                curl_close($ch);
            }
        }
    }
    http_response_code(500);
    exit;
}

if (!empty($events['events'])) {
    foreach ($events['events'] as $event) {
        if ($event['type'] === 'postback') {
            $replyToken = $event['replyToken'];
            parse_str($event['postback']['data'], $params);

            if (!empty($params['summary_date'])) {
                $date = $params['summary_date'];

                // ดึงข้อมูล orders ในวันนั้น
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

                    // กำหนดถุงตามราคา
                    if ($price == 1200) $bagSize = 'small';
                    elseif ($price == 1600) $bagSize = 'medium';
                    elseif ($price == 2000) $bagSize = 'large';
                    else $bagSize = 'small';

                    // นับจำนวนออเดอร์ตามราคา (ถัง)
                    $priceCount[$price] = ($priceCount[$price] ?? 0) + 1;

                    // นับรสรวม และรสแยกถุง
                    if (!empty($flavors)) {
                        foreach ($flavors as $flavor => $qty) {
                            // รวมรสรวม
                            $flavorCount[$flavor] = ($flavorCount[$flavor] ?? 0) + $qty;

                            // รวมแยกถุง
                            if (!isset($flavorBagCount[$flavor])) {
                                $flavorBagCount[$flavor] = ['small'=>0,'medium'=>0,'large'=>0];
                            }
                            $flavorBagCount[$flavor][$bagSize] += $qty;
                        }
                    }
                }

                

// สร้างข้อความสรุป
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


                // ส่งกลับ LINE
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
                curl_close($ch);
            }

            // ✅ เพิ่มตรงนี้ต่อจาก summary_date 
if (!empty($params['orders_date'])) {
    $date = $params['orders_date'];

    // ดึงข้อมูล orders ในวันนั้น
    $sql = "SELECT id, customer_name, address, phone, price, quantity, flavors, note 
        FROM orders 
        WHERE delivery_date=?
        ORDER BY price ASC, customer_name ASC, address ASC, id ASC";


    $stmt = $pdo->prepare($sql);
    $stmt->execute([$date]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$orders) {
        $replyText = "ไม่มีออเดอร์วันที่ ".date('d/m/Y', strtotime($date));
    } else {
        $replyText = "📋 แสดงออเดอร์วันที่ ".date('d/m/Y', strtotime($date))."\n\n";
        foreach ($orders as $o) {
            $replyText .= "🆔 {$o['id']}\n";
            $replyText .= "💰 {$o['price']} บาท | 🍦 {$o['quantity']} บาท\n";
            $replyText .= "👤 {$o['customer_name']} | 🏠 {$o['address']}\n";
            $replyText .= "📞 {$o['phone']}\n";
            if (!empty($o['note'])) {
                $replyText .= "📝 {$o['note']}\n";
            }
            $replyText .= "----------------------\n";
        }
    }

    // ส่งกลับ LINE
    $messages = [[ 'type' => 'text', 'text' => $replyText ]];
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
    curl_close($ch);
}

        }
    }
}

http_response_code(200);


