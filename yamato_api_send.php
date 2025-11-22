<?php
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
$orders = $input['orders'] ?? [];

if (empty($orders)) {
    echo json_encode(['results' => [['order_no' => '', 'status' => 'error', 'message' => 'データがありません']]]); 
    exit;
}

// ▼ ここにヤマトB2クラウドAPI接続情報を設定
$api_user_id = "0529368887";
$access_token = "@0529368887-,Ftxfs1xPeBq4parPdByApz85JJDxnOoUxCfk5JsYKb0=";
$api_url = "https://newb2web.kuronekoyamato.co.jp/";

$results = [];

foreach ($orders as $order_no) {

    $request_data = [
        "feed" => [
            "entry" => [[
                "shipment" => [
                    "orderNo" => $order_no,
                    "shippingDate" => date("Y-m-d"),
                    "serviceCode" => "0",
                    "toName" => "山田太郎",
                    "toTel" => "09012345678",
                    "toZip" => "4600008",
                    "toAddress1" => "愛知県名古屋市中区栄1-1-1",
                    "fromName" => "日本電子機器補修協会",
                    "fromTel" => "0521234567",
                    "fromZip" => "4600001",
                    "fromAddress1" => "愛知県名古屋市中区錦2-2-2",
                    "itemName" => "テスト商品",
                    "itemCount" => 1,
                    "totalAmount" => 0
                ]
            ]]
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$access_token}",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($request_data, JSON_UNESCAPED_UNICODE),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    $resp_data = json_decode($response, true);
    $message = $resp_data['feed']['title'] ?? $curl_error ?? '';

    $results[] = [
        'order_no' => $order_no,
        'status' => $http_code === 200 ? 'OK' : 'NG',
        'message' => $message
    ];
}

echo json_encode(['results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);