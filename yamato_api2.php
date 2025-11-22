<?php
header("Content-Type: text/plain; charset=utf-8"); // ← JSONではなくプレーンテキストで見る

//本番環境
$api_user_id = "0529368887";
$access_token = "@0529368887-,Ftxfs1xPeBq4parPdByApz85JJDxnOoUxCfk5JsYKb0=";
$api_url = "https://newb2web.kuronekoyamato.co.jp/";

//テスト環境
$api_user_id = "sBTUN8kJPuizQnAfLpFX";
$access_token = "@900000000042-999,1ikPe67TMt4YSKwBPSewumOCMULkrf+PXx9KcRJAPvA=";
$api_url = "https://testb2api.kuronekoyamato.co.jp/";

// --- 送り状データ ---
$request_data = [
    "request" => [
        "head" => [
            "userId" => "YOUR_USER_ID",
            "password" => "YOUR_PASSWORD",
            "apiKey" => "YOUR_API_KEY"
        ],
        "body" => [
            "order" => [
                "orderNo" => "12345678",
                "deliveryDate" => date('Y-m-d'),
                "deliveryTime" => "12-14",
                "sender" => [
                    "name" => "発送元名",
                    "tel" => "0521234567",
                    "zipcode" => "4500002",
                    "address1" => "愛知県名古屋市中村区名駅",
                    "address2" => "1-1-1"
                ],
                "receiver" => [
                    "name" => "山田太郎",
                    "tel" => "09012345678",
                    "zipcode" => "1000001",
                    "address1" => "東京都千代田区千代田",
                    "address2" => "1-1-1"
                ],
                "items" => [
                    [
                        "itemName" => "商品名",
                        "quantity" => 1,
                        "size" => "60"
                    ]
                ]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $api_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer {$access_token}", // ← token → Bearer に修正
        "Content-Type: application/json"
    ],
    CURLOPT_POSTFIELDS => json_encode($request_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HEADER => true,  // ← レスポンスヘッダも含めて取得
    CURLOPT_VERBOSE => true  // ← 詳細ログを出力
]);

ob_start(); // ログキャプチャ
$response = curl_exec($ch);
$debug_info = ob_get_clean();

$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// 出力整理
echo "===== USER DATA =====\n";
echo $api_user_id . "\n\n";
echo $api_url . "\n\n";

echo "===== HTTP CODE =====\n";
echo $http_code . "\n\n";

echo "===== CURL ERROR =====\n";
echo ($curl_error ?: "(none)") . "\n\n";

echo "===== RESPONSE (raw) =====\n";
echo $response . "\n\n";

echo "===== DEBUG INFO =====\n";
echo $debug_info . "\n";


