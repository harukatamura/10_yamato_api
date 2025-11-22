<?php
header("Content-Type: text/plain; charset=utf-8"); // ← JSONではなくプレーンテキストで見る

//本番環境
$api_user_id = "0529368887";
$access_token = "@0529368887-,Ftxfs1xPeBq4parPdByApz85JJDxnOoUxCfk5JsYKb0=";
$api_url = "https://newb2web.kuronekoyamato.co.jp/";

//テスト用情報
$api_user_id = "sBTUN8kJPuizQnAfLpFX";
$access_token = "@900000000042-999,1ikPe67TMt4YSKwBPSewumOCMULkrf+PXx9KcRJAPvA=";
$api_url = "https://testb2api.kuronekoyamato.co.jp";

//パラメータ
$set_url = $api_url;
$paramater = "/b2/p/editA?api_user_id=";
$paramater2 = "/b2/p/editA";
$paramater3 = "/b2/p/new?issue_editA&display=0&print_type=m";
$paramater4 = "/b2/p/polling?display=0&issue_no=";
$paramater5 = "/b2/p/getfile?display=0&checkonly=1&issue_no=";
$paramater6 = "/b2/p/getfile?display=0&fileonly=1&issue_no=";
$set_url = $api_url.$paramater.$api_user_id;
$set_url2 = $api_url.$paramater2;
$set_url3 = $api_url.$paramater3;
$set_url4 = $api_url.$paramater4;
$set_url5 = $api_url.$paramater5;
$set_url6 = $api_url.$paramater6;


// =========================
// 共通設定
// =========================
$cookieFile = __DIR__ . "/yamato_cookie.txt"; // cookieを保存するファイル
if (file_exists($cookieFile)) {
unlink($cookieFile); // 前回のcookieをクリア（不要なら削除）
}

// =========================
// ① 仮データ登録（POST）
// =========================

// --- 送り状データ ---
$request_data = [
    "feed" => [
        "entry" => [
            [
                "shipment" => [
                //お客様管理番号
                "shipment_number" => "",
                "service_type" => "5",
                "is_cool" => "0",
                "shipment_date" => date('Ymd'),
                "delivery_date" => date('Ymd', strtotime('+2 day')),
                "amount" => "28600",
                "tax_amount" => "0",
                "invoice_code" => "",
                "invoice_code_ext" => "",
                "invoice_freight_no" => "01",
                "is_using_delivery_email" => "0",
                "shipper_telephone_display" => "052-936-8887",
                "shipper_name" => "052-936-8887",
                "shipper_zip_code" => "461-0011",
                "shipper_address1" => "愛知県",
                "shipper_address2" => "名古屋市東区",
                "shipper_address3" => "白壁3-12-13",
                "shipper_address4" => "中部産業連盟ビル新館8階",
                "consignee_telephone_display" => "052-936-8887",
                "consignee_name" => "JEMTC　再生担当部署",
                "consignee_zip_code" => "461-0011",
                "consignee_address1" => "愛知県",
                "consignee_address2" => "名古屋市東区",
                "consignee_address3" => "白壁3-12-13",
                "consignee_address4" => "中部産業連盟ビル新館4階",
                "item_name1" => "PC(Ci3-8GB-SSD-W11-user　計1点)",
                "item_name2" => "ご注文日：2025/10/23No.251665",
                "handling_information1" => "精密機器",
                "handling_information2" => "ワレ物注意",
                "note" => "251023 電話通販 (石川県) No.251665"
                ],
                "shipment" => [
                //お客様管理番号
                "shipment_number" => "",
                "service_type" => "5",
                "is_cool" => "0",
                "shipment_date" => date('Ymd'),
                "delivery_date" => date('Ymd', strtotime('+2 day')),
                "amount" => "28600",
                "tax_amount" => "0",
                "invoice_code" => "",
                "invoice_code_ext" => "",
                "invoice_freight_no" => "01",
                "is_using_delivery_email" => "0",
                "shipper_telephone_display" => "052-936-8887",
                "shipper_name" => "052-936-8887",
                "shipper_zip_code" => "461-0011",
                "shipper_address1" => "愛知県",
                "shipper_address2" => "名古屋市東区",
                "shipper_address3" => "白壁3-12-13",
                "shipper_address4" => "中部産業連盟ビル新館8階",
                "consignee_telephone_display" => "052-936-8887",
                "consignee_name" => "JEMTC　再生担当部署",
                "consignee_zip_code" => "461-0011",
                "consignee_address1" => "愛知県",
                "consignee_address2" => "名古屋市東区",
                "consignee_address3" => "白壁3-12-13",
                "consignee_address4" => "中部産業連盟ビル新館4階",
                "item_name1" => "PC(Ci3-8GB-SSD-W11-user　計1点)",
                "item_name2" => "ご注文日：2025/10/23No.251665",
                "handling_information1" => "精密機器",
                "handling_information2" => "ワレ物注意",
                "note" => "251023 電話通販 (石川県) No.251665"
                ]
            ]
        ]
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $set_url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Token {$access_token}", 
        "Content-Type:application/json"
    ],
    CURLOPT_COOKIEJAR => $cookieFile, // Cookieを保存
    CURLOPT_POSTFIELDS => json_encode($request_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HEADER => false,  // ← レスポンスヘッダも含めて取得
    CURLOPT_VERBOSE => true  // ← 詳細ログを出力
]);

// リクエスト実行
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$body = substr($response, $headerSize); // HTML部分だけ抽出
curl_close($ch);

// 判定
if ($httpCode === 200) {
    if (stripos($body, 'エラー') !== false) {
        echo "⚠️ エラー検出: HTML内にエラー情報があります\n";

        // エラー詳細抽出（例: <p>エラー内容</p> の中身を取得）
        if (preg_match_all('/<p.*?>(.*?)<\/p>/si', $body, $matches)) {
            foreach ($matches[1] as $err) {
                echo "・".$err."\n";
            }
        }

    } else {
        echo "✅ 登録成功（HTML返却）\n";

        // もしHTML内に送り状番号などがある場合は正規表現で取得可能
        // 例: <span id="slip_no">123456789</span>
        if (preg_match('/<span id="slip_no">(\d+)<\/span>/', $body, $match)) {
            $slipNo = $match[1];
            echo "送り状番号: ".$slipNo."\n";
        }
    }
} else {
    echo "❌ HTTPエラー: ".$httpCode."\n";
}

echo "HTTP Status: {$httpCode}\n\n";

echo "仮データ登録レスポンス:\n$response\n";

		echo "\n=======================\n";
if ($response) {
	// UTF-8 BOM 対策：先頭3バイトがBOMなら除去
	$response = preg_replace('/^\xEF\xBB\xBF/', '', $response);

	// JSONデコードを試みる
	$decoded = json_decode($response, true);

	if (json_last_error() === JSON_ERROR_NONE) {
		echo "\nデコード成功\n";
		
		
		$title = $decoded['feed']['title']; // ← 仮登録ID取得
		echo "仮登録ID（title）: $title\n";

		// =========================
		// ② 仮データ更新（PUT）
		// =========================
		$put_data = $decoded;

		$ch = curl_init($set_url2);
		curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_CUSTOMREQUEST => "PUT",
		CURLOPT_POSTFIELDS => json_encode($put_data),
		CURLOPT_HTTPHEADER => [
		"Content-Type: application/json;charset=UTF-8",
		"X-Requested-With: XMLHttpRequest", // ← 必須！
		],
		CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
		]);
		$response_put = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		

		// 判定
		if ($httpCode === 200) {
		    if (stripos($body, 'エラー') !== false) {
		        echo "⚠️ エラー検出: HTML内にエラー情報があります\n";

		        // エラー詳細抽出（例: <p>エラー内容</p> の中身を取得）
		        if (preg_match_all('/<p.*?>(.*?)<\/p>/si', $body, $matches)) {
		            foreach ($matches[1] as $err) {
		                echo "・".$err."\n";
		            }
		        }

		    } else {
		        echo "✅ 仮データ更新成功（HTML返却）\n";

		        // もしHTML内に送り状番号などがある場合は正規表現で取得可能
		        // 例: <span id="slip_no">123456789</span>
		        if (preg_match('/<span id="slip_no">(\d+)<\/span>/', $body, $match)) {
		            $slipNo = $match[1];
		            echo "送り状番号: ".$slipNo."\n";
		        }
		    }
		} else {
		    echo "❌ HTTPエラー: ".$httpCode."\n";
		}

		echo "HTTP Status: {$httpCode}\n\n";

		echo "仮データ更新レスポンス:\n$response_put\n";

		echo "\n=======================\n";
		
		// =========================
		// ③送り状発行
		// =========================
		// JSONデコードを試みる
		$decoded_put = json_decode($response_put, true);
		
		$created_ms = $decoded_put["feed"]["entry"][0]["shipment"]["created_ms"];
		$tracking_number = $decoded_put["feed"]["entry"][0]["shipment"]["tracking_number"];
		
		// --- 発行データ ---
		$slip_data = [
		    "feed" => [
		        "entry" => [
		             [
		                "id" => "1",
		                "shipment" => [
		                "tracking_number" => $tracking_number,
		                "created_ms" => $created_ms,
		                "service_type" => "5",
		                "printer_type" => "1",
		                "shipment_flg" => "1",
		                ]
		            ]
		        ],
		    "updated" => $decoded_put["feed"]["updated"]
		    ]
		];
		
		$encoded_data = json_encode($slip_data);
		
		$ch = curl_init($set_url3);
		curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => json_encode($slip_data),
		CURLOPT_HTTPHEADER => [
		"Content-Type: application/json;charset=UTF-8",
		"X-Requested-With: XMLHttpRequest", // ← 必須！
		],
		CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
		]);
		$response_slip = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		

		// 判定
		if ($httpCode === 200) {
		    if (stripos($body, 'エラー') !== false) {
		        echo "⚠️ エラー検出: HTML内にエラー情報があります\n";

		        // エラー詳細抽出（例: <p>エラー内容</p> の中身を取得）
		        if (preg_match_all('/<p.*?>(.*?)<\/p>/si', $body, $matches)) {
		            foreach ($matches[1] as $err) {
		                echo "・".$err."\n";
		            }
		        }

		    } else {
		        echo "✅送り状発行成功（HTML返却）\n";
		    }
		} else {
		    echo "❌ HTTPエラー: ".$httpCode."\n";
		}


		echo "\n送信先：: {$set_url3}\n\n";
		
		echo "\nHTTP Status: {$httpCode}\n\n";
		
		echo "\n送り状印刷リクエスト:\n$encoded_data\n";

		echo "\nレスポンス:\n$response_slip\n";
		
		echo "\n=======================\n";
		
		// =========================
		// ④印刷状態確認
		// =========================
		// JSONデコードを試みる
		$decoded_slip = json_decode($response_slip, true);
		
		$get_title = $decoded_slip["feed"]["title"];
		
		$set_url4 = $set_url4.$get_title;
		
		$ch = curl_init($set_url4);
		curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => [
		"X-Requested-With: XMLHttpRequest", // ← 必須！
		],
		CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
		]);
		$response_check = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		// 判定
		if ($httpCode === 200) {
		    if (stripos($body, 'エラー') !== false) {
		        echo "⚠️ エラー検出: HTML内にエラー情報があります\n";

		        // エラー詳細抽出（例: <p>エラー内容</p> の中身を取得）
		        if (preg_match_all('/<p.*?>(.*?)<\/p>/si', $body, $matches)) {
		            foreach ($matches[1] as $err) {
		                echo "・".$err."\n";
		            }
		        }

		    } else {
		        echo "✅印刷状態確認成功（HTML返却）\n";
		    }
		} else {
		    echo "❌ HTTPエラー: ".$httpCode."\n";
		}


		echo "\n送信先：: {$set_url4}\n\n";
		
		echo "\nHTTP Status: {$httpCode}\n\n";
		
		echo "\nレスポンス:\n$response_check\n";
		
		echo "\n=======================\n";
		
		// =========================
		// ⑤印刷データチェック
		// =========================

		$set_url5 = $set_url5.$get_title;
		
		$ch = curl_init($set_url5);
		curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => [
		"X-Requested-With: XMLHttpRequest", // ← 必須！
		],
		CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
		]);
		$response_check2 = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		// 判定
		if ($httpCode === 200) {
			echo "✅印刷データチェック成功（HTML返却）\n";
		} else {
			echo "❌ HTTPエラー: ".$httpCode."\n";
		}


		echo "\n送信先: {$set_url5}\n\n";
		
		echo "\nHTTP Status: {$httpCode}\n\n";
		
		echo "\nレスポンス:\n$response_check2\n";
		
		// =========================
		// ⑥PDFダウンロード
		// =========================

		$set_url6 = $set_url6.$get_title;
		
		$ch = curl_init($set_url6);
		curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_CUSTOMREQUEST => "GET",
		CURLOPT_HTTPHEADER => [
		"X-Requested-With: XMLHttpRequest", // ← 必須！
		"Accept: application/pdf, */*",
		],
		CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
		]);
		$response_file = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		
		// HTTP 200 ならブラウザにPDFとして出力
		if ($httpCode === 200 && !empty($response_file)) {
			echo "✅PDFデータダウンロード成功（HTML返却）\n";
			// ファイル名は動的に付けられます
			$filename = "ヤマト伝票.pdf"; 

			header('Content-Type: application/pdf');
			header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
			header('Content-Length: ' . strlen($response_file));

			echo $response_file;
			exit; // これ以降の出力は止める
		} else {
			echo "❌ PDF取得失敗。HTTPコード: {$httpCode}\n";
		}

		echo "\n送信先: {$set_url6}\n\n";
		
		echo "\nHTTP Status: {$httpCode}\n\n";
		
	} else {
		echo "\n失敗\n";
	}
} else {
	echo "レスポンスがありません。\n";
}

