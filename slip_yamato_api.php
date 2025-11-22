<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・ヤマト伝票発行API連携
//==================================================================================================
if (version_compare(PHP_VERSION, '5.1.0', '>=')) {
	date_default_timezone_set('Asia/Tokyo');
}

//スパム防止のためのリファラチェック
$Referer_check = 0;
//リファラチェックを「する」場合のドメイン
$Referer_check_domain = "forincs.com";

// 送信確認画面の表示
$confirmDsp = 1;

// 送信完了後に自動的に指定のページ
$jumpPage = 0;


// 送信完了後に表示するページURL
$thanksPage = "./slip_output_yamato.php";


// 以下の変更は知識のある方のみ自己責任でお願いします。

//--------------------------
//  関数実行、変数初期化
//--------------------------
//このファイルの文字コード定義
$encode = "UTF-8";

if(isset($_GET)) $_GET = sanitize($_GET);
if(isset($_POST)) $_POST = sanitize($_POST);
if(isset($_COOKIE)) $_COOKIE = sanitize($_COOKIE);
if($encode == 'SJIS') $_POST = sjisReplace($_POST,$encode);

//変数初期化
$sendmail = 0;
$empty_flag = 0;
$post_mail = '';
$errm ='';
$header ='';

//外部ファイル取り込み
require_once("./lib/define.php");
require_once("./lib/comm.php");

//オブジェクト生成
$comm = new comm();

//実行プログラム名取得
$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
$prgname = "ヤマト伝票発行API連携";
$comm->ouputlog("==== ヤマト伝票発行API連携 ====", $prgid, SYS_LOG_TYPE_INFO);
$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//引数取得
$today = date('YmdHis');
$p_staff = $_COOKIE['con_perf_staff'];

$do = "";
$g_post=$_POST;
$action=$_GET['action'];
$factory=$_GET['fac'];

//本番環境
$access_token = "@0529368887-,Ftxfs1xPeBq4parPdByApz85JJDxnOoUxCfk5JsYKb0=";
$api_url = "https://newb2web.kuronekoyamato.co.jp/";

//テスト用情報
$access_token = "@900000000042-999,1ikPe67TMt4YSKwBPSewumOCMULkrf+PXx9KcRJAPvA=";
$api_url = "https://testb2api.kuronekoyamato.co.jp";

// =========================
// 共通設定
// =========================
$cookieFile = __DIR__ . "/yamato_cookie.txt"; // cookieを保存するファイル
if (file_exists($cookieFile)) {
unlink($cookieFile); // 前回のcookieをクリア（不要なら削除）
}

if($empty_flag != 1){
	//--------------------------
	//  ＤＢ更新　　
	//--------------------------
	//外部ファイル取り込み
	require_once("./lib/dbaccess.php");

	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$dba = new dbaccess();

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//処理実施
	if ($result) {
		// 文字コード指定
		$comm->ouputlog("===文字コード指定===", $prgid, SYS_LOG_TYPE_DBUG);
		$query = ' set character_set_client = utf8';
		$result = $db->query($query);
		
		if (!$result) {
			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			$empty_flag = 1;
		}
		$comm->ouputlog("===文字コード指定完了===", $prgid, SYS_LOG_TYPE_DBUG);

		//データベース更新
		if($action == 'output'){
			$flg = output_yamato_slip($db);
			//出力後元のページに戻る
			header("Location: ".$thanksPage."?flg=".$flg);
		}


		//データベース切断
		if ($result) { $dba->mysql_discon($db); }
	}
}

?>

<?php
//----------------------------------------------------------------------
//  関数定義(START)
//----------------------------------------------------------------------
function h($string) {
	global $encode;
	return htmlspecialchars($string, ENT_QUOTES,$encode);
}
function sanitize($arr){
	if(is_array($arr)){
		return array_map('sanitize',$arr);
	}
	return str_replace("\0","",$arr);
}

//全角→半角変換
function zenkaku2hankaku($key,$out,$hankaku_array){
	global $encode;
	if(is_array($hankaku_array) && function_exists('mb_convert_kana')){
		foreach($hankaku_array as $hankaku_array_val){
			if($key == $hankaku_array_val){
				$out = mb_convert_kana($out,'a',$encode);
			}
		}
	}
	return $out;
}
//配列連結の処理
function connect2val($arr){
	$out = '';
	foreach($arr as $key => $val){
		if($key === 0 || $val == ''){//配列が未記入（0）、または内容が空のの場合には連結文字を付加しない（型まで調べる必要あり）
			$key = '';
		}elseif(strpos($key,"円") !== false && $val != '' && preg_match("/^[0-9]+$/",$val)){
			$val = number_format($val);//金額の場合には3桁ごとにカンマを追加
		}
		$out .= $val . $key;
	}
	return $out;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   output_yamato_slip
//
// ■概要
//   仮データ登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function output_yamato_slip($db){

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $cookieFile;
	global $p_staff;
	global $today;
	global $factory;
	global $g_post;
	global $api_url;
	global $access_token;

	//データ削除
	$comm->ouputlog("===ヤマト伝票　出力===", $prgid, SYS_LOG_TYPE_INFO);
	
	$arr_outputno = $_POST["outputno"] ?? [];
	if ($arr_outputno) {
		//一時キーを生成
		$tmp_key = substr(base_convert(md5(uniqid()), 16, 36), 0, 20);
		$id_list = implode(',',$arr_outputno);
		//伝票出力フラグを3に
		$_update = "
			 UPDATE php_pi_ship_history 
			 SET upddt = '$today'
			 , updcount = updcount + 1
			 , output_staff = '$p_staff'
			 , output_flg=3
			 , tmp_key = '$tmp_key'
			 WHERE idxnum IN ($id_list)
			 AND output_flg = 0
			 AND delflg = 0
		";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_update))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		//ヤマトデータを取得
		$_select = "
			SELECT 
				A.kbn, A.item_name, A.item_value 
			FROM 
				php_yamato_account A
			WHERE
				A.kbn IN ('invoice_code', 'search_key')
		";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$yamato_account[$row['kbn']][$row['item_name']] = $row['item_value'];
		}
		//購入方法取得
		$_select = "
			SELECT 
				A.item_name, A.item_value 
			FROM 
				php_item A
			WHERE
				A.item='transaction_cd'
		";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$transaction_cd[$row['item_value']] = $row['item_name'];
		}
		//出力データを取得
		$_select = "
			SELECT 
				A.name1, A.name2, A.phonenum1, A.phonenum2, CONCAT(A.postcd1,'-',A.postcd2) as postcd, A.address1, A.address2, A.address3, 
				B.send_way, MIN(B.idxnum) as idxnum ,GROUP_CONCAT(B.modelnum) as modelnum, SUM(B.cash) as cash, SUM(B.buynum) as buynum, B.reception_dt, B.transaction_cd, B.order_num, B.p_way
				,C.desktop, D.item_name as sales_way
			FROM
				php_personal_information A
					INNER JOIN php_pi_ship_history B
						 ON concat(LPAD(A.idxnum,10,'0'),p_year) = B.picd
						AND B.delflg=0
						LEFT OUTER JOIN (
							SELECT desktop, modelnum FROM php_pc_info WHERE delflg=0 GROUP BY modelnum
							UNION ALL
							SELECT desktopflg as desktop, modelnum FROM php_ecommerce_pc_info WHERE delflg=0 GROUP BY modelnum
						) C ON B.modelnum=C.modelnum
						LEFT OUTER JOIN php_item D ON B.transaction_cd=D.item_value AND D.item='transaction_cd'
			WHERE
				A.delflg=0
			AND 
				B.output_flg=3
			AND 
				B.tmp_key='$tmp_key'
			GROUP BY 
				B.picd, C.desktop, B.send_way
		";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($_select))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$arr_shipment = [];
		while ($row = $rs->fetch_array()) {
			//配列にデータを格納
			$arr_shipment[] = array("shipment" => array(
			                "shipment_number" => "",
			                "service_type" => $row['send_way'],
			                "is_cool" => "0",
			                "shipment_date" => date('Ymd'),
			                "delivery_date" => "",
				"amount" => $row['cash'],
			                "tax_amount" => "0",
//			                "invoice_code" => $yamato_account['invoice_code'][$factory],
			                "invoice_code" => "900000000042",
			                "invoice_code_ext" => "999",
			                "invoice_freight_no" => "01",
			                "is_using_delivery_email" => "0",
			                "shipper_telephone_display" => "052-936-8887",
			                "shipper_name" => "052-936-8887",
			                "shipper_zip_code" => "461-0011",
			                "shipper_address1" => "愛知県",
			                "shipper_address2" => "名古屋市東区",
			                "shipper_address3" => "白壁3-12-13",
			                "shipper_address4" => "中部産業連盟ビル新館4階",
			                "consignee_telephone_display" => "052-936-8887",
			                "consignee_name" => "JEMTC　日本電子機器補修協会",
			                "consignee_zip_code" => $row['postcd'],
			                "consignee_address" => $row['address1'].$row['address2'],
			                "consignee_address4" => $row['address3'],
			                "item_name1" => "PC　".$row['buynum']."点",
			                "item_name2" => "ご注文日：".date('Y/n/j', strtotime($row['reception_dt']))." No.".$row['idxnum'],
			                "handling_information1" => "精密機器",
			                "handling_information2" => "ワレ物注意",
			                "note" => date('ymd', strtotime($row['reception_dt'])).$row['sales_way']." (".$row['address1'].") No.".$row['idxnum'],
			                "search_key_title1" => "オーダー番号",
			                "search_key1" => $row['order_num'],
			                "search_key_title2" => "金額",
			                "search_key2" => $row['cash'],
			                "search_key_title3" => "注文番号",
			                "search_key3" => $row['idxnum'],
			                "search_key_title4" => "購入方法",
			                "search_key4" => $yamato_account['search_key'][$row['sales_way']],
		                )
		                );
		}
	}
	//データがあればAPI実行
	if (!empty($arr_shipment)){
		// ===================================
		//①仮データ登録　処理開始
		// ===================================
		$comm->ouputlog("===ヤマト伝票発行API　仮データ登録　実行===", $prgid, SYS_LOG_TYPE_INFO);

		//本番環境
		$api_user_id = "0529368887";
		//テスト用情報
		$api_user_id = "sBTUN8kJPuizQnAfLpFX";
		
		//パラメータ
		$paramater = "/b2/p/editA?api_user_id=";
		$set_url = $api_url.$paramater.$api_user_id;
		
		// --- 送り状データ ---
		$request_data = [
		    "feed" => [
		        "entry" => 
		                $arr_shipment
		        
		    ]
		];

		$comm->ouputlog("=======ヤマト伝票発行API　仮データ登録　送信内容=======", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog("送信先：".$set_url, $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog(json_encode($request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), $prgid, SYS_LOG_TYPE_INFO);
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
		    CURLOPT_POSTFIELDS => json_encode($request_data, JSON_UNESCAPED_UNICODE),
		    CURLOPT_SSL_VERIFYPEER => false,
		    CURLOPT_TIMEOUT => 15,
		    CURLOPT_HEADER => false,  // ← レスポンスヘッダも含めず取得
		    CURLOPT_VERBOSE => true  // ← 詳細ログを出力
		]);

		// リクエスト実行
		$response = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$body = substr($response, $headerSize); // HTML部分だけ抽出
		curl_close($ch);
		
		// JSONデコードを試みる
		$decoded_data = json_decode($response, true);
		
		$err_flg = check_http($db, $httpCode);
		if($err_flg > 0){
			slip_data_rollback($db, $id_list, $tmp_key);
			return false;
		}

		$comm->ouputlog("=======ヤマト伝票発行API　仮データ登録　送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($response, $prgid, SYS_LOG_TYPE_INFO);
		
		//データにエラーがあるかチェック
		if (!empty($decoded_data["feed"]["entry"][0]["error"])){
			$error_log = $decoded_data["feed"]["entry"][0]["error"];
			$encoded_error_log = json_encode($error_log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
			$comm->ouputlog("=======エラーログ=======", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($encoded_error_log, $prgid, SYS_LOG_TYPE_INFO);
			if($encoded_error_log == "null"){
				$encoded_error_log = $response;
			}
			// セッションにエラーログを格納
			session_start();
			$_SESSION['yamato_api_response'] = $response;
			//ロールバック
			slip_data_rollback($db, $id_list, $tmp_key);
			return false;
		}
		
		//仮データ取得
		yamato_slip_get($db);
		
		// ===================================
		// ②送り状発行　処理開始
		// ===================================
		//送り状発行
		$comm->ouputlog("===ヤマト伝票発行API　送り状発行　実行===", $prgid, SYS_LOG_TYPE_INFO);

		$paramater3 = "/b2/p/new?issue_editA&display=0&print_type=m&sort1=tracking_number";
		$set_url3 = $api_url.$paramater3;
		
		$created_ms = $decoded_data["feed"]["entry"][0]["shipment"]["created_ms"];
		$tracking_number = $decoded_data["feed"]["entry"][0]["shipment"]["tracking_number"];
		
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
		    "updated" => $decoded_data["feed"]["updated"]
		    ]
		];
		
		$encoded_data = json_encode($slip_data);

		$ch = curl_init($set_url3);
		curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => false,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_POSTFIELDS => $encoded_data,
		CURLOPT_HTTPHEADER => [
		"Content-Type: application/json;charset=UTF-8",
		"X-Requested-With: XMLHttpRequest", // ← 必須！
		],
		CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
		]);
		$response_slip = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$comm->ouputlog("=======ヤマト伝票発行API　送り状発行　送信内容=======", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog("送信先：".$set_url3, $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($encoded_data, $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog("=======ヤマト伝票発行API　送り状発行　送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($response_slip, $prgid, SYS_LOG_TYPE_INFO);
		
		// JSONデコードを試みる
		$decoded_slip = json_decode($response_slip, true);
		$issue_no = $decoded_slip["feed"]["title"];
		$comm->ouputlog("issue_no：".$issue_no, $prgid, SYS_LOG_TYPE_INFO);
		
		//印刷状態確認
		$ch = yamato_check_slip($db, $issue_no);
		// JSONデコードを試みる
		$response_check = curl_exec($ch);
		// JSONデコードを試みる
		$decoded_check = json_decode($response_check, true);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$comm->ouputlog("=======ヤマト伝票発行API　印刷状態確認　送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($response_check, $prgid, SYS_LOG_TYPE_INFO);
		
		$err_flg = check_http($db, $httpCode);
		if($err_flg > 0){
			slip_data_rollback($db, $id_list, $tmp_key);
			return false;
		}
		$g_status = $decoded_check["feed"]["entry"][0]["subtitle"];
		$rxid = $decoded_check["feed"]["title"];
		//レスポンスが202，204の場合は再度トライする
		if($g_status <> "200"){
			if($g_status == "400" || $g_status == "204"){
				slip_data_rollback($db, $id_list, $tmp_key);
				return false;
			}else{
				//印刷状態確認再トライ
				$ch = yamato_check_slip($db, $issue_no);
				// JSONデコードを試みる
				$response_check = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				$comm->ouputlog("=======ヤマト伝票発行API　印刷状態確認　再送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($response_check, $prgid, SYS_LOG_TYPE_INFO);
				
				$err_flg = check_http($db, $httpCode);
				if($err_flg > 0){
					slip_data_rollback($db, $id_list, $tmp_key);
					return false;
				}
			}
		}
		
		//印刷データチェック
		$httpCode = yamato_check_slip2($db, $issue_no);
		$err_flg = check_http($db, $httpCode);
		if($err_flg > 0){
			slip_data_rollback($db, $id_list, $tmp_key);
			return false;
		}
		
		//PDFダウンロード
		$httpCode = yamato_pdf_download($db, $issue_no);
		$err_flg = check_http($db, $httpCode);
		if($err_flg > 0){
			slip_data_rollback($db, $id_list, $tmp_key);
			return false;
		}
		
		//伝票番号取得
		$httpCode = yamato_get_slipnumber($db, $id_list, $tmp_key, $rxid);
		$err_flg = check_http($db, $httpCode);
		if($err_flg > 0){
			slip_data_rollback($db, $id_list, $tmp_key);
			return false;
		}
		
		//ロールバック（テスト用）
		$comm->ouputlog("=======ロールバック=======", $prgid, SYS_LOG_TYPE_INFO);
		$_update = "
			 UPDATE php_pi_ship_history 
			 SET upddt = '$today'
			 , updcount = updcount + 1
			 , output_staff = '$p_staff'
			 , output_flg=0
			 , tmp_key = ''
			 WHERE idxnum IN ($id_list)
			 AND tmp_key = '$tmp_key'
			 AND delflg = 0
		";
		$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
		if (!($rs = $db->query($_update))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
	}
	return 2;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   check_http
//
// ■概要
//   HTTPステータスチェック
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function check_http($db, $httpCode){
	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $p_staff;
	global $today;
	
	$comm->ouputlog("===HTTPステータスチェック　check_http　実行===", $prgid, SYS_LOG_TYPE_INFO);
	
	// 判定
	if ($httpCode === 200) {
		$comm->ouputlog("✅登録成功（HTML返却）", $prgid, SYS_LOG_TYPE_INFO);
		return 0;
	} else {
		$comm->ouputlog("❌ HTTPエラー: ".$httpCode, $prgid, SYS_LOG_TYPE_INFO);
		return 1;
	}
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   slip_data_rollback
//
// ■概要
//  ロールバック
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function slip_data_rollback($db, $id_list, $tmp_key){
	
	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $p_staff;
	global $today;
	
	$comm->ouputlog("===ロールバック　slip_data_rollback　実行===", $prgid, SYS_LOG_TYPE_INFO);
	
	//データをもとに戻す
	$comm->ouputlog("=======発送データロールバック=======", $prgid, SYS_LOG_TYPE_INFO);
	$_update = "
		 UPDATE php_pi_ship_history 
		 SET upddt = '$today'
		 , updcount = updcount + 1
		 , output_staff = '$p_staff'
		 , output_flg=0
		 , tmp_key = ''
		 WHERE idxnum IN ($id_list)
		 AND output_flg = 3
		 AND tmp_key = '$tmp_key'
		 AND delflg = 0
	";
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
	if (!($rs = $db->query($_update))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   yamato_check_slip
//
// ■概要
//   印刷状態確認
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function yamato_check_slip($db, $issue_no){
	
	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $cookieFile;
	global $p_staff;
	global $today;
	global $api_url;

	$comm->ouputlog("===ヤマト伝票発行API　印刷状態確認　yamato_check_slip　実行===", $prgid, SYS_LOG_TYPE_INFO);

	$paramater4 = "/b2/p/polling?display=0&issue_no=";
	$set_url4 = $api_url.$paramater4.$issue_no;
	
	$comm->ouputlog("=======ヤマト伝票発行API　印刷状態確認　送信内容=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("送信先：".$set_url4, $prgid, SYS_LOG_TYPE_INFO);
	
	// =========================
	// 印刷状態確認
	// =========================
	
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

	return $ch;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   yamato_check_slip
//
// ■概要
//   印刷データチェック
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function yamato_check_slip2($db, $issue_no){
	
	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $cookieFile;
	global $p_staff;
	global $today;
	global $api_url;

	$comm->ouputlog("===ヤマト伝票発行API　印刷データチェック　yamato_check_slip2　実行===", $prgid, SYS_LOG_TYPE_INFO);

	$paramater5 = "/b2/p/getfile?display=0&checkonly=1&issue_no=";
	$set_url5 = $api_url.$paramater5;
	
	// =========================
	// ⑤印刷データチェック
	// =========================

	$set_url5 = $set_url5.$issue_no;
	
	$comm->ouputlog("=======ヤマト伝票発行API　印刷データチェック　送信内容=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("送信先：".$set_url5, $prgid, SYS_LOG_TYPE_INFO);
	
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
	
	$comm->ouputlog("=======ヤマト伝票発行API　印刷データチェック　送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($response_check2, $prgid, SYS_LOG_TYPE_INFO);
	
	return $httpCode;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   yamato_pdf_download
//
// ■概要
//   PDFダウンロード
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function yamato_pdf_download($db, $issue_no){
	
	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $cookieFile;
	global $p_staff;
	global $today;
	global $api_url;

	$comm->ouputlog("===ヤマト伝票発行API　PDFダウンロード　yamato_pdf_download　実行===", $prgid, SYS_LOG_TYPE_INFO);
	
	$paramater6 = "/b2/p/getfile?display=0&fileonly=1&issue_no=";
	$set_url6 = $api_url.$paramater6;
	
	// =========================
	// ⑥PDFダウンロード
	// =========================

	$set_url6 = $set_url6.$issue_no;
	
	$comm->ouputlog("=======ヤマト伝票発行API　PDFダウンロード　送信内容=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("送信先：".$set_url6, $prgid, SYS_LOG_TYPE_INFO);	
	
	$ch = curl_init($set_url6);
	curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HEADER => false,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
	"X-Requested-With: XMLHttpRequest", // ← 必須！
	],
	CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
	]);
	$response_file = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	// HTTP 200 ならブラウザにPDFとして出力
	if ($httpCode === 200 && !empty($response_file)) {
		$comm->ouputlog("✅PDFデータダウンロード成功（HTML返却）", $prgid, SYS_LOG_TYPE_INFO);
		// ファイル名は動的に付けられます
		$filename = "ヤマト伝票.pdf"; 

		header('Content-Type: application/pdf');
		header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
		header('Content-Length: ' . strlen($response_file));
		echo $response_file;
//				exit; // これ以降の出力は止める
	} else {
		$comm->ouputlog("❌ PDF取得失敗。HTTPエラー: ".$httpCode, $prgid, SYS_LOG_TYPE_INFO);
	}
	
	$comm->ouputlog("=======ヤマト伝票発行API　PDFダウンロード　送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("HTTP Status：".$httpCode, $prgid, SYS_LOG_TYPE_INFO);

	return $httpCode;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   yamato_get_slipnumber
//
// ■概要
//   伝票番号取得
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function yamato_get_slipnumber($db, $id_list, $tmp_key, $rxid){
	
	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $cookieFile;
	global $p_staff;
	global $today;
	global $api_url;

	$comm->ouputlog("===ヤマト伝票発行API　伝票番号取得　yamato_get_slipnumber　実行===", $prgid, SYS_LOG_TYPE_INFO);
	
	$paramater7 = "/b2/p/editA?spool&RXID=";
	$set_url7 = $api_url.$paramater7;
	// =========================
	// ⑦伝票番号取得
	// =========================

	$set_url7 = $set_url7.$rxid;
	
	$comm->ouputlog("=======ヤマト伝票発行API　伝票番号取得　送信内容=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("送信先：".$set_url7, $prgid, SYS_LOG_TYPE_INFO);
	
	$ch = curl_init($set_url7);
	curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HEADER => false,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
	"X-Requested-With: XMLHttpRequest", // ← 必須！
	],
	CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
	]);
	$response_slipnumber = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	//判定
	check_http($db, $httpCode, $id_list, $tmp_key);
	$comm->ouputlog("=======ヤマト伝票発行API　伝票番号取得　送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("HTTP Status：".$httpCode, $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($response_slipnumber, $prgid, SYS_LOG_TYPE_INFO);
	// JSONデコードを試みる
	$decoded_slipnumber = json_decode($response_slipnumber, true);
	//伝票番号を配列に格納
	$entries = $decoded_slipnumber["feed"]["entry"];
	$arr_slipnumber = [];
	$tracking_number = "";
	$search_key3 = "";

	$_update = "";
	//伝票番号更新
	$comm->ouputlog("=======伝票番号更新=======", $prgid, SYS_LOG_TYPE_INFO);
	foreach ($entries as $entry) {
		$tracking_number = $entry['shipment']['tracking_number'] ?? '';
		$search_key3     = $entry['shipment']['search_key3'] ?? '';
		 $_update .= " 
		 ";
		 $_update .= " UPDATE php_pi_ship_history ";
		 $_update .= " SET upddt = '$today' ";
		 $_update .= " , updcount = updcount + 1 ";
		 $_update .= " , output_staff = '$p_staff' ";
		 $_update .= " , output_flg=9 ";
		 $_update .= " , carrier = 'ヤマト運輸' ";
		 $_update .= " , slipnumber = '$tracking_number' ";
		 $_update .= " WHERE idxnum = '$search_key3' ";
		 $_update .= " AND output_flg = 3 ";
		 $_update .= " AND tmp_key = '$tmp_key' ";
		 $_update .= " AND delflg = 0; ";
		$comm->ouputlog("インデックス：".$search_key3."　伝票番号：".$tracking_number, $prgid, SYS_LOG_TYPE_INFO);
	}
	if($_update <> ""){
		$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
		if (!($rs = $db->query($_update))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
	}
	// セッションにログを格納
	session_start();
	$_SESSION['yamato_api_response'] = $response_slipnumber;
	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   yamato_slip_get
//
// ■概要
//   仮データ取得
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function yamato_slip_get($db){
	
	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $cookieFile;
	global $p_staff;
	global $today;
	global $api_url;

	$comm->ouputlog("===ヤマト伝票発行API　仮データ取得　yamato_slip_get　実行===", $prgid, SYS_LOG_TYPE_INFO);

	$paramater2 = "/b2/p/editA";
	$set_url2 = $api_url.$paramater2;
	
	// =========================
	// 仮データ取得
	// =========================

	
	$comm->ouputlog("=======ヤマト伝票発行API　仮データ取得　送信内容=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("送信先：".$set_url2, $prgid, SYS_LOG_TYPE_INFO);
	
	$ch = curl_init($set_url2);
	curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_HEADER => false,
	CURLOPT_CUSTOMREQUEST => "GET",
	CURLOPT_HTTPHEADER => [
	"X-Requested-With: XMLHttpRequest", // ← 必須！
	],
	CURLOPT_COOKIEFILE => $cookieFile, // 同じセッションCookieを使用
	]);
	
	$response_get = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	$comm->ouputlog("=======ヤマト伝票発行API　仮データ取得　送信結果=======", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($response_get, $prgid, SYS_LOG_TYPE_INFO);
	
	return true;
}