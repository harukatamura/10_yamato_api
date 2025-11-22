<?php
//==================================================================================================
// ■機能概要
//   ・ヤマトXML通信　配送状況取得
//
// ■履歴
//==================================================================================================

	//----------------------------------------------------------------------------------------------
	// 共通処理
	//----------------------------------------------------------------------------------------------
	//ファイル読込
	require_once("./lib/comm.php");
	require_once("./lib/define.php");
	require_once("./lib/dbaccess.php");
	require_once("./lib/html.php");
	require_once("./sql/sql_aggregate.php");
	require_once("./send_ns_mail_auto.php");
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	$sql = new SQL_aggregate();

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "ヤマトXML通信　配送状況取得";
	$prgmemo = "　ヤマトのXML通信を利用して配送状況を更新するシステムです。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	foreach($_POST as $key=>$val) {
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
	}

	// 送信先
	$endpoint = "https://inter-consistent2.kuronekoyamato.co.jp/consistent2/cts";

	// SOAPアクションに相当するメソッド
	$function = "provideXMLTraceService";
	
	//顧客情報
	$customer_code     = "0529368887";
	$password          = "JJem2020";
	$ip_address = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '127.0.0.1';
	
	$today = date('YmdHis');
	
	//ステータスコード取得
	$query = " SELECT A.code,  A.status ";
	$query .= " FROM php_yamato_status_code A ";
	$query .= " ORDER BY A.code ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$arr_code = [];
	while ($row = $rs->fetch_array()) {
		$arr_code[$row['code']] = $row['status'];
	}
	//ステータスコード取得
	$query = " SELECT code, status FROM php_yamato_status_code
		WHERE status LIKE '発送中止'
		OR status LIKE '%返品%'
		OR status LIKE '%完了%'
		GROUP BY status;";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$arr_no_mail = [];
	while ($row = $rs->fetch_array()) {
		$arr_no_mail[] = $row['code'];
	}

	//対象伝票番号取得
	$query = " 
		SELECT A.slipnumber, A.sales_name
		FROM php_telorder__ A  
		LEFT OUTER JOIN php_ecommerce_pc_info B ON A.category=B.category 
		AND A.sales_name=B.sales_name  
		LEFT OUTER JOIN php_pc_failure C ON A.t_idx = C.tel_idx 
		AND C.delflg=0 
		AND C.kbn='返品'  
		LEFT OUTER JOIN (  SELECT slipnumber, ship_date, status
		FROM php_yamato_status   
		WHERE insdt>'2025-09-01'  GROUP BY slipnumber  )D ON A.slipnumber=D.slipnumber  
		WHERE 1  
		AND ((  A.sales_name = '100001' 
		AND (A.reception_telnum LIKE '%新規%' OR A.reception_telnum ='') ) OR ( A.sales_name = '100001' 
		AND A.reception_telnum LIKE '%代理店%') OR ( A.sales_name = '100001' 
		AND A.reception_telnum LIKE '%既存%') OR ( A.sales_name = '100002' 
		AND A.reception_telnum LIKE '%%') OR ( A.sales_name = '100004' 
		AND (A.reception_telnum LIKE '%新規%' OR A.reception_telnum ='')  )) 
		AND  A.status IN ( '1', '2', '9' ) 
		AND A.delflg = 0  
		AND A.slipnumber IS NOT NULL  
		AND A.slipnumber <> ''
		AND D.status IS NULL  
		AND A.receptionday > '2025-09-01'  
		AND (A.modelnum NOT LIKE '%JSP%' OR(A.modelnum LIKE '%JSP%' 
		AND A.p_way>0))  
		AND C.kbn IS NULL  GROUP BY A.t_idx 
		UNION ALL  
		SELECT A.slipnumber, 'PREF' as sales_name
		FROM php_personal_info A  
		LEFT OUTER JOIN (  SELECT slipnumber, ship_date, status
		FROM php_yamato_status   
		WHERE insdt>'2025-09-01'  GROUP BY slipnumber  )D ON A.slipnumber=D.slipnumber  
		WHERE 1  
		AND A.delflg = 0  
		AND A.cancelflg = 0  
		AND A.slipnumber IS NOT NULL  
		AND A.slipnumber <> ''
		AND D.status IS NULL  
		AND A.g_buydt > '2025-09-01'  
		AND  A.status IN ( '1', '2', '9' )   
		GROUP BY A.slipnumber";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$tracking_numbers = [];
	while ($row = $rs->fetch_array()) {
		$sales_name[$row['slipnumber']] = $row['sales_name'];
		$tracking_numbers[] = $row['slipnumber'];
	}
	
	
	//SQL初期設定
	$_insert1 = " INSERT INTO php_yamato_status ";
	$_insert1 .= " (insdt, upddt, way, slipnumber, status, center, centercode, ship_date) ";
	$_insert1 .= " VALUES ";
	
	// 20件ずつに分割
	$chunks = array_chunk($tracking_numbers, 20);
	
	foreach ($chunks as $tracking_chunk) {
		usleep(200000); // 0.2秒スリープ
		// 伝票番号部分を組み立て
		$numbersXml = "";
		foreach ($tracking_chunk as $num) {
			$numbersXml .= "<伝票番号>{$num}</伝票番号>\n";
		}

// 要求XMLデータ（仕様に沿って作成）
$requestXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<問合せ要求>
  <基本情報>
    <IPアドレス>{$ip_address}</IPアドレス>
    <顧客コード>{$customer_code}</顧客コード>
    <パスワード>{$password}</パスワード>
  </基本情報>
  <検索オプション>
    <検索区分>01</検索区分>
    <届け先情報表示フラグ>1</届け先情報表示フラグ>
  </検索オプション>
  <検索条件>
    {$numbersXml}
  </検索条件>
</問合せ要求>
XML;
		
		try {
			// SoapClientの作成（WSDLなしモード）
			$client = new SoapClient(null, [
			"location" => $endpoint,
			"uri"      => "urn:yamatows", // 仮の名前空間（必須）
			"soap_version" => SOAP_1_2,
			"trace" => true,
			"exceptions" => true,
			]);

			// 呼び出し
			$response = $client->__soapCall($function, [$requestXml]);

			// パース
			$xml = simplexml_load_string($response);
			
			foreach ($xml->宅急便データ as $data) {
				$_insert = "";
				$_insert2 = "";
				$invoice  = (string)$data->問い合わせ情報->商品情報->伝票番号;
				$method   = (string)$data->問い合わせ情報->商品情報->サイズ品目;
				$status   = (string)$data->問い合わせ情報->商品情報->最新ステータス;
				$shipDate = (string)$data->届け先情報->貨物取扱い情報->発送日 ?? null;
				$searchkey = (string)$data->届け先情報->管理キー項目情報->検索キー4;

				// 最新ステータスに対応するセンター
				$center = $centerCode = null;

				if (isset($data->問い合わせ情報->到着店情報->宅急便情報)) {
					$arrival = $data->問い合わせ情報->到着店情報->宅急便情報;
					if ((string)$arrival->ステータス === $status) {
						$center     = trim((string)$arrival->店名);
						$centerCode = trim((string)$arrival->店コード);
					}
				}

				// 確認用出力
				echo "<br>";
				echo "<br>";
				echo "---------------------------\n";
				echo "<br>";
				echo "<br>";
				echo "伝票番号: <a href='https://member.kms.kuronekoyamato.co.jp/parcel/detail?pno={$invoice}' target='_blank'>{$invoice}</a>\n";
				echo "<br>";
				echo "配送方法: {$method}\n";
				echo "<br>";
				echo "状況: {$arr_code[$status]}\n";
				echo "<br>";
				echo "センター: {$center}\n";
				echo "<br>";
				echo "センターコード: {$centerCode}\n";
				echo "<br>";
				echo "出荷日: {$shipDate}\n";
				echo "<br>";
				echo "検索キー: {$searchkey}\n";
				echo "<br>";
				echo "sales_name: {$sales_name[$invoice]}\n";
				echo "\n";
				echo "<br>";
				if($shipDate == ""){
					$shipDate = $today;
				}
				
				$_insert2 = " (". sprintf("'%s'", date('YmdHis')).", ". sprintf("'%s'", date('YmdHis')).", '$method', '$invoice', '$arr_code[$status]', '$center', '$centerCode', '$shipDate')";
				$_insert = $_insert1.$_insert2;
				echo $_insert;
				echo "<br>";
				$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($_insert, $prgid, SYS_LOG_TYPE_DBUG);
				//データ更新実行
				if (! $db->query($_insert)) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					return 2;
				}
				$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
				//完了・返品のデータ以外は発送メール送信
				if(!in_array($status, $arr_no_mail) && $sales_name[$invoice] == "100001"){
					//フラグを3にする
					$_update = "UPDATE php_telorder__ SET ";
					$_update .= " upddt = " . sprintf("'%s'", $today);
					$_update .= " ,updcount = updcount + 1";
					$_update .= " ,mail_flg = 3";
					$_update .= " ,shipmailsenddt = " . sprintf("'%s'", $today);
					$_update .= " WHERE slipnumber = '$invoice'";
					$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
					//データ更新実行
					if (! $db->query($_update)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return;
					}
					//メール送信
					send_mail($db, $invoice);
					$comm->ouputlog("==== メール送信完了 ====", $prgid, SYS_LOG_TYPE_INFO);
					echo $_update;
				}
			}
			print_r($xml);
		} catch (SoapFault $e) {
			echo "SOAP Fault: " . $e->getMessage();
		}
	}