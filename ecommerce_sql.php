<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・勤怠情報更新
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
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

//本日日付
$today = date('YmdHis');
	
// 送信完了後に表示するページURL
$thanksPage1 = "./ecommerce_input.php";
$thanksPage2 = "./telorder_upload.php";

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
$comm->ouputlog("==== 通販データ取込 更新 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//グローバルIPアドレス取得
$g_ip = $_SERVER['REMOTE_ADDR'];

//引数取得
$do = $_GET['do'];
$g_post = $_POST;
$require="";
$field="";

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
			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			$empty_flag = 1;
		}
		$comm->ouputlog("===文字コード指定完了===", $prgid, SYS_LOG_TYPE_DBUG);

		//データベース更新
		if($do == "upload"){
			$flg = mysql_ecommerce_input($db);
			header("Location: ".$thanksPage1."?flg=".$flg);
		}else if($do == "upload_t"){
			$flg = mysql_telorder_upload($db);
			header("Location: ".$thanksPage2."?flg=".$flg);
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
//   mysql_ecommerce_input
//
// ■概要
//   通販データ取込
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ecommerce_input($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $g_post;
	$dataCnt = 0;
	//変数の初期化
	$buynum = "";
	$category = "";
	$modelnum = "";
	$receptionday = "";
	$cash = "";
	$name = "";
	$postcd1 = "";
	$postcd2 = "";
	$address1 = "";
	$address2 = "";
	$address3 = "";
	$mail_address = "";
	$sales_name = "";
	$insert = "INSERT IGNORE INTO php_ecommerce ";
	$insert .= " ( insdt, upddt, buynum, category, modelnum, receptionday, cash, name, postcd1, postcd2, phonenum1, phonenum2, address1, address2, address3, mailaddress, locale)";
	$insert .= " VALUES ";
	foreach($g_post as $key=>$val) {
		if($key == "end".$dataCnt) {
			//データをインサート
			if($dataCnt == 0){
				$insert .= "  ('$today', '$today', '$buynum', '$category', '$modelnum', '$receptionday', '$cash', '$name', '$postcd1', '$postcd2', '$phonenum1', '$phonenum2', '$address1', '$address2', '$address3', '$mail_address', '$sales_name')";
			}else{
				$insert .= "  ,('$today', '$today', '$buynum', '$category', '$modelnum', '$receptionday', '$cash', '$name', '$postcd1', '$postcd2', '$phonenum1', '$phonenum2', '$address1', '$address2', '$address3', '$mail_address', '$sales_name')";
			}
			++$dataCnt;
			//変数の初期化
			$buynum = "";
			$category = "";
			$modelnum = "";
			$receptionday = "";
			$cash = "";
			$name = "";
			$postcd1 = "";
			$postcd2 = "";
			$address1 = "";
			$address2 = "";
			$address3 = "";
			$mail_address = "";
			$sales_name = "";
		}else{
			if($key == "注文日".$dataCnt) {
				$receptionday = $val;
			}else if($key == "カテゴリ".$dataCnt) {
				$category = addslashes($val);
			}else if($key == "型番".$dataCnt) {
				$modelnum = addslashes($val);
			}else if($key == "台数".$dataCnt) {
				$buynum = $val;
			}else if($key == "金額".$dataCnt) {
				$cash = $val;
			}else if($key == "名前".$dataCnt) {
				$name = addslashes($val);
			}else if($key == "電話番号１".$dataCnt) {
				$phonenum1 = $val;
			}else if($key == "電話番号２".$dataCnt) {
				$phonenum2 = $val;
			}else if($key == "郵便番号１".$dataCnt) {
				$postcd1 = $val;
			}else if($key == "郵便番号２".$dataCnt) {
				$postcd2 = $val;
			}else if($key == "住所１".$dataCnt) {
				$address1 = $val;
			}else if($key == "住所２".$dataCnt) {
				$address2 = addslashes($val);
			}else if($key == "住所３".$dataCnt) {
				$address3 = addslashes($val);
			}else if($key == "メールアドレス".$dataCnt) {
				$mail_address = addslashes($val);
			}else if($key == "販売方法".$dataCnt) {
				$sales_name = $val;
			}
		}
	}
	$insert .= ";";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return 2;
	}
	return 1;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_telorder_upload
//
// ■概要
//   NSデータアップロード
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_telorder_upload($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $g_post;
	$dataCnt = 0;
	//変数の初期化
	$status = 1;
	$output_flg = 0;
	$order_num = "";
	$buynum = "";
	$category = "";
	$receptionday = "";
	$cash = "";
	$name = "";
	$postcd1 = "";
	$postcd2 = "";
	$address1 = "";
	$address2 = "";
	$address3 = "";
	$company = "";
	$mail_address = "";
	$designated_day = "";
	$specified_times = "";
	$option_han = "";
	$remark = "";
	$p_way = "";
	$status = 1;
	$g_over = 0;
	$locale = $_POST['locale'];
	$sales_name = $_POST['販売方法'];
	$staff = $_POST['担当者'];
	$duplicate = 0;

	//型番をリストに格納
	$query .= " SELECT A.category, A.modelnum";
	$query .= " FROM php_ecommerce_pc_info A";
	$query .= " WHERE A.delflg=0 ";
	$query .= " AND A.sales_name=100001 ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$modelnum_list[$row['category']] = $row['modelnum'];
	}
	$insert = "INSERT IGNORE INTO php_telorder__ ";
	$insert .= " ( insdt, upddt, status, output_flg, order_num, buynum, category, receptionday, cash, name, postcd1, postcd2, phonenum1, phonenum2, address1, address2, address3, company, mailaddress, sales_name, locale, option_han, staff, reception_telnum, remark, p_way, modelnum, p_method)";
	$insert .= " VALUES ";
	foreach($g_post as $key=>$val) {
		if($key == "end".$dataCnt) {
			//代理店注文の場合、型番を修正＆流入経路を修正
			if(mb_substr($category, -2) == " A"){
				$category = rtrim($category, " A");
				$reception_telnum = "代理店";
			}else{
				$reception_telnum = "新規";
			}
			if($g_over == 0){
				if($sales_name == 100003){
					$status = 5;
					$output_flg = 3;
				}
				//データをインサート	
				$query = $insert;
				$query .= "  ('$today', '$today', '$status', '$output_flg', '$order_num', '$buynum', '$category', '$receptionday', '$cash', '$name', '$postcd1', '$postcd2', '$phonenum1', '$phonenum2', '$address1', '$address2', '$address3', '$company', '$mail_address', '$sales_name', '$locale', '$option_han', '$staff', '$reception_telnum', '$remark', '$p_way','".$modelnum_list[$category]."','$p_method')";
				//
				$query .= ";";
				$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $db->query($query)) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					return 2;
				}
				//備考がある場合、問合せテーブルへ格納
				if($remark <> ""){
					$t_idx = mysqli_insert_id($db);
					$question2 = "ネット注文No.".$t_idx."\n";
					$question3 = "STORESオーダー番号：".$order_num."\n";
					$question0 = "【注文時備考】\n";
					$p_question = $question0.$question2.$question3.$remark;
					
					$_insert01 = "INSERT INTO php_info_mail ( ";
					$_insert02 = ")VALUES(";
					$_insert01 .= " insdt ";
					$_insert02 .= sprintf("'%s'", $today);
					$_insert01 .= ", upddt ";
					$_insert02 .= sprintf(",'%s'", $today);
					$_insert01 .= ", status ";
					$_insert02 .= sprintf(",'%s'", 1);
					$_insert01 .= ", name ";
					$_insert02 .= sprintf(",'%s'", $name);
					$_insert01 .= ", email ";
					$_insert02 .= sprintf(",'%s'", $mail_address);
					$_insert01 .= ", phonenum ";
					$_insert02 .= sprintf(",'%s'", $phonenum1);
					$_insert01 .= ", postcd1 ";
					$_insert02 .= sprintf(",'%s'", $postcd1);
					$_insert01 .= ", postcd2 ";
					$_insert02 .= sprintf(",'%s'", $postcd2);
					$_insert01 .= ", address1 ";
					$_insert02 .= sprintf(",'%s'", $address1);
					$_insert01 .= ", address2 ";
					$_insert02 .= sprintf(",'%s'", $address2);
					$_insert01 .= ", contact ";
					$_insert02 .= sprintf(",'%s'", "メール");
					$_insert01 .= ", question ";
					$_insert02 .= sprintf(",'%s'", $p_question);
					$_insert01 .= ", delflg ";
					$_insert02 .= sprintf(",'%s'", 0);
					$_insert03 = ")";
					$query = $_insert01.$_insert02.$_insert03;
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/codmail_error_mail.php');
						return false;
					}
					$i_idx = mysqli_insert_id($db);
					
					$_update = "UPDATE php_telorder__ ";
					$_update .= " SET upddt = ".sprintf("'%s'", $today);
					$_update .= " , updcount = updcount + 1 ";
					if($duplicate <> 0){
						$_update .= " , status = 2 ";
					}
					$_update .= " , remark  = CONCAT(remark, ' 問合No.".$i_idx."')";
					$_update .= " WHERE idxnum  = " . sprintf("'%s'", $t_idx);
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (!($rs = $db->query($_update))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						require_once(dirname(_FILE_).'/codmail_error_mail.php');
						return false;
					}
					if($duplicate <> 0){
						$_update = "UPDATE php_telorder__ ";
						$_update .= " SET upddt = ".sprintf("'%s'", $today);
						$_update .= " , updcount = updcount + 1 ";
						$_update .= " , status = 2 ";
						$_update .= " , remark  = CONCAT(remark, '重複の可能性があります。注文No.".$t_idx."のデータを確認してください。', ' 問合No.".$i_idx."')";
						$_update .= " WHERE idxnum  IN ( " .$duplicate.")";
						$_update .= " AND output_flg = 0 ";
						$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
						if (!($rs = $db->query($_update))) {
							$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							require_once(dirname(_FILE_).'/codmail_error_mail.php');
							return false;
						}
					}
				
				}
			}
			++$dataCnt;
			//変数の初期化
			$status = 1;
			$output_flg = 0;
			$order_num = "";
			$buynum = "";
			$category = "";
			$receptionday = "";
			$cash = "";
			$name = "";
			$phonenum1 = "";
			$phonenum2 = "";
			$postcd1 = "";
			$postcd2 = "";
			$address1 = "";
			$address2 = "";
			$address3 = "";
			$company = "";
			$mail_address = "";
			$designated_day = "";
			$specified_times = "";
			$option_han = "";
			$remark = "";
			$p_way = "";
			$p_method = "";
			$g_over = 0;
			$duplicate = 0;
		}else{
			if($key == "注文日".$dataCnt) {
				$receptionday = $val;
			}else if($key == "注文番号".$dataCnt) {
				$order_num = $val;
			}else if($key == "型番".$dataCnt) {
				$category = addslashes($val);
			}else if($key == "台数".$dataCnt) {
				$buynum = $val;
			}else if($key == "金額".$dataCnt) {
				$cash = $val;
			}else if($key == "名前".$dataCnt) {
				$name = addslashes($val);
			}else if($key == "電話番号１".$dataCnt) {
				$phonenum1 = $val;
			}else if($key == "電話番号２".$dataCnt) {
				$phonenum2 = $val;
			}else if($key == "郵便番号１".$dataCnt) {
				$postcd1 = $val;
			}else if($key == "郵便番号２".$dataCnt) {
				$postcd2 = $val;
			}else if($key == "住所１".$dataCnt) {
				$address1 = addslashes($val);
			}else if($key == "住所２".$dataCnt) {
				$address2 = addslashes($val);
			}else if($key == "住所３".$dataCnt) {
				$address3 = addslashes($val);
			}else if($key == "会社名".$dataCnt) {
				$company = $val;
			}else if($key == "メールアドレス".$dataCnt) {
				$mail_address = addslashes($val);
			}else if($key == "到着指定日付".$dataCnt) {
				$designated_day = $val;
			}else if($key == "到着指定時間".$dataCnt) {
				$specified_times = $val;
			}else if($key == "備品".$dataCnt) {
				$option_han = addslashes($val);
			}else if($key == "備考".$dataCnt) {
				$remark = addslashes($val);
			}else if($key == "重複インデックス".$dataCnt) {
				$duplicate = $val;
			}else if($key == "支払方法".$dataCnt) {
				$p_way = $val;
			}else if($key == "決済方法".$dataCnt) {
				$p_method = $val;
			}else if($key == "重複フラグ".$dataCnt) {
				$g_over = $val;
			}
		}
	}
	/*
	$insert .= ";";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($insert)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return 2;
	}
	*/
	//グループID登録
	$_update = " UPDATE php_telorder__ ";
	$_update .= " SET t_idx=idxnum ";
	$_update .= " , upddt='$today' ";
	$_update .= " , updcount = updcount+1 ";
	$_update .= " WHERE t_idx=0 ";
	$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($_update)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return 2;
	}
	if($sales_name == 100003){
		//テレビショッピングの場合、ecommerceテーブルに移行
		$insert = " INSERT INTO php_ecommerce ";
		$insert .= " (insdt, upddt, name, phonenum1, buynum, cash, receptionday, category, locale, reception_telnum, postcd1, postcd2, address1, address2, address3, option_han, modelnum) ";
		$insert .= " SELECT '".date('Y-m-d H:i:s')."','".date('Y-m-d H:i:s')."',A.name, A.phonenum1, A.buynum, A.cash, A.receptionday, A.category, A.sales_name, A.reception_telnum, A.postcd1, A.postcd2, A.address1, A.address2, A.address3, A.option_han, B.modelnum ";
		$insert .= " FROM php_telorder__ A ";
		$insert .= " LEFT OUTER JOIN php_ecommerce_pc_info B  ON A.category=B.category AND A.sales_name=B.sales_name";
		$insert .= " WHERE A.delflg=0 ";
		$insert .= " AND A.status = 5 ";
		$insert .= " AND A.output_flg = 3 ";
		$insert .= " AND A.sales_name = 100003 ";
		$comm->ouputlog("データ登録 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($insert))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		//状況更新
		$query = "UPDATE php_telorder__ A ";
		$query .= " SET A.status = 9";
		$query .= " WHERE A.delflg=0 ";
		$query .= " AND A.status = 5 ";
		$query .= " AND A.output_flg = 3 ";
		$query .= " AND A.sales_name = 100003 ";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
	}
	return 1;
}

?>
