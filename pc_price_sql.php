<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・infoメール情報 更新
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

// 送信完了後に表示するページURL
$thanksPage = "./pc_price_input.php";
$thanksPage2 = "./pc_price_input_team.php";

//対象テーブル
$table = "php_pc_price";
$table_info = "php_pc_info";
$table_tanka = "php_pc_tanka";
$table_zaiko = "php_t_pc_zaiko";
$table_team = "php_pc_price_team";

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
$prgname = "価格表登録";
$comm->ouputlog("==== 価格表登録 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);
$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//引数取得
$do = "";
$do = $_GET['do'];
$g_idx=$_GET['idx'];
$week=$_GET['week'];
$g_idxnum=$_GET['idxnum'];
$l_date=$_GET['l_date'];
$g_post=$_POST;

//　1:infoメール更新
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

	if($do == ""){
		$query = "SELECT MIN(date) as mindate, week ";
		$query .= " FROM php_calendar ";
		$query .= " WHERE week = ";
		$query .= "(SELECT week ";
		$query .= " FROM php_calendar ";
		$query .= " WHERE date = '".date('Ymd')."')";
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			if(date('Ymd') == date('Ymd',strtotime($row['mindate']))){
				$do = 'cron';
				$week = $row['week'];
			}
		}
	}
	//処理実施
	if ($result) {
		// 文字コード指定
		$comm->ouputlog("===文字コード指定===", $prgid, SYS_LOG_TYPE_DBUG);
		$query = ' set character_set_client = utf8';
// ----- 2019.06 ver7.0対応
//		$result = mysql_query($query, $db);
		$result = $db->query($query);
		
		if (!$result) {
// ----- 2019.06 ver7.0対応
//			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆文字コード指定エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			$empty_flag = 1;
		}
		$comm->ouputlog("===文字コード指定完了===", $prgid, SYS_LOG_TYPE_DBUG);

		//データベース更新
		if($do == 'ins'){
			mysql_ins_pc_price($db);
			setcookie ('week', '', time()-3600);
			setcookie ('week', $week, time()+1);
			//更新後元のページに戻る
			header("Location: ".$thanksPage);
		}else if($do == 'del'){
			mysql_del_pc_price($db);
			setcookie ('week', '', time()-3600);
			setcookie ('week', $week, time()+1);
			//更新後元のページに戻る
			header("Location: ".$thanksPage);
		}else if($do == 'delweek'){
			mysql_delweek_pc_price($db);
			setcookie ('week', '', time()-3600);
			setcookie ('week', $week, time()+1);
			//更新後元のページに戻る
			header("Location: ".$thanksPage);
		}else if($do == 'next'){
			mysql_next_pc_price($db);
			setcookie ('s_date1', '', time()-3600);
			setcookie ('s_date1', $l_date, time()+1);
			//更新後元のページに戻る
			header("Location: ".$thanksPage);
		}else if($do == 'cron'){
			mysql_ins_pc_price_cron($db);
			mysql_ins_pc_price_cron_team($db);
		}else if($do == 'ins_team'){
			mysql_ins_pc_price_team($db);
			//更新後元のページに戻る
			header("Location: ".$thanksPage2."?idx=".$g_idxnum);
		}else if($do == 'del_team'){
			mysql_del_pc_price_team($db);
			setcookie ('week', '', time()-3600);
			setcookie ('week', $week, time()+1);
			//更新後元のページに戻る
			header("Location: ".$thanksPage2."?idx=".$g_idxnum);
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
//   mysql_ins_pc_price
//
// ■概要
//   価格表登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_pc_price( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_info;
	global $table_tanka;
	global $table_zaiko;
	//対象プログラム
	global $prgid;
	//引数
	global $week;
	global $g_post;
	
	//作成日付
	$today = date('YmdHis');
	
	//初期値化
	$dataCnt = 1;
	$pop="";
	$modelnum="";
	$cpu="";
	$memory="";
	$tanka_notax=0;
	$tanka=0;
	$tanka_trade=0;
	$reserv="";
	$memo="";
	$idxnum="";
	$hensyu=0;
	
	foreach($g_post as $key=>$val){
		if ("end" . $dataCnt == $key) {
			$query = "";

			if($hensyu == 1 && $idxnum == ""){
				//インデックスが空の場合はインサート
				$query = "INSERT INTO " . $table;
				$query .= " (insdt, upddt, week, pop, modelnum, cpu";
				$query .= " , memory, tanka_notax, tanka, tanka_rent, tanka_trade, reserv, memo)";
				$query .= " VALUE ('$today', '$today', '$week', '$pop', '$modelnum', '$cpu', ";
				$query .= " '$memory', '$tanka_notax', '$tanka', '$tanka_rent', '$tanka_trade', '$reserv', '$memo' )";
				
			}else if ($hensyu == 1 && $idxnum != ""){
				//インデックスにデータがある場合はアップデート
				$query = "UPDATE " . $table;
				$query .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
				$query .= " ,updcount = updcount + 1";
				$query .= " ,week   = ". sprintf("'%s'", $week);
				$query .= " ,pop   = ". sprintf("'%s'", $pop);
				$query .= " ,modelnum   = ". sprintf("'%s'", $modelnum);
				$query .= " ,cpu = ". sprintf("'%s'", $cpu);
				$query .= " ,memory = ". sprintf("'%s'", $memory);
				$query .= " ,tanka_notax = ". sprintf("'%s'", $tanka_notax);
				$query .= " ,tanka = ". sprintf("'%s'", $tanka);
				$query .= " ,tanka_rent = ". sprintf("'%s'", $tanka_rent);
				$query .= " ,tanka_trade = ". sprintf("'%s'", $tanka_trade);
				$query .= " ,reserv = ". sprintf("'%s'", $reserv);
				$query .= " ,memo = ". sprintf("'%s'", $memo);
				$query .= " WHERE idxnum = " . sprintf("'%s'", $idxnum);
			}
			//編集フラグが立っていればデータベース更新
			if($query != ""){
				$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
// ----- 2019.06 ver7.0対応
//				if (! mysql_query($query, $db)) {
				if (! $db->query($query)) {
//					$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				
				//infoテーブルに該当機種がない場合はinfoテーブルにも登録
				$query = "SELECT idxnum FROM " . $table_info . " WHERE modelnum = '".$modelnum."' AND delflg = 0";
// ----- 2019.06 ver7.0対応
//				if (!($rs = mysql_query($query))) {$comm->ouputlog("☆★☆データ確認エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);}
				if (!($rs = $db->query($query))) {$comm->ouputlog("☆★☆データ確認エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);}
				else {
// ----- 2019.06 ver7.0対応
//					if (mysql_num_rows($rs) < 1) {
					if ($rs->num_rows < 1) {
						$query = "INSERT INTO " . $table_info;
						$query .= " (insdt, upddt, modelnum, cpu, memory, tanka)";
						$query .= " VALUE ('$today', '$today', '$modelnum', '$cpu', '$memory', '$tanka' )";
						$comm->ouputlog("infoテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($query, $db)) {
						if (! $db->query($query)) {
//							$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}else{
					//あれば単価を更新
						$query = "UPDATE " . $table_info;
						$query .= " SET tanka = '$tanka'";
						$query .= " WHERE modelnum = '$modelnum'";
						$query .= " AND delflg = 0 ";
						$comm->ouputlog("tankaテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($query, $db)) {
						if (! $db->query($query)) {
//							$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}
				}
				//tankaテーブルに該当機種がない場合はtankaテーブルにも登録
				$query = "SELECT idxnum FROM " . $table_tanka . " WHERE modelnum = '".$modelnum."' AND delflg = 0";
// ----- 2019.06 ver7.0対応
//				if (!($rs = mysql_query($query))) {$comm->ouputlog("☆★☆データ確認エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);}
				if (!($rs = $db->query($query))) {$comm->ouputlog("☆★☆データ確認エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);}
				else {
// ----- 2019.06 ver7.0対応
//					if (mysql_num_rows($rs) < 1) {
					if ($rs->num_rows < 1) {
						$query = "INSERT INTO " . $table_tanka;
						$query .= " (insdt, upddt, modelnum, tanka)";
						$query .= " VALUE ('$today', '$today', '$modelnum', '$tanka' )";
						$comm->ouputlog("tankaテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($query, $db)) {
						if (! $db->query($query)) {
//							$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}else{
					//あれば単価を更新
						$query = "UPDATE " . $table_tanka;
						$query .= " SET tanka = '$tanka'";
						$query .= " WHERE modelnum = '$modelnum'";
						$query .= " AND delflg = 0 ";
						$comm->ouputlog("tankaテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($query, $db)) {
						if (! $db->query($query)) {
//							$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}
				}
				//工場が在庫を持っていれば単価を更新
				$query = "UPDATE " . $table_zaiko;
				$query .= " SET tanka = '$tanka'";
				$query .= " WHERE modelnum = '$modelnum'";
				$query .= " AND (staff = 'RNG' OR staff='YKO' OR staff='補修センター') ";
				$query .= " AND hanbaiflg = 0 ";
				$query .= " AND delflg = 0 ";
				$comm->ouputlog("zaikoテーブル更新", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
// ----- 2019.06 ver7.0対応
//				if (! mysql_query($query, $db)) {
				if (! $db->query($query)) {
//					$comm->ouputlog("☆★☆データ更新エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				//未受取の単価を更新
				$query = "UPDATE php_s_pc_zaiko" ;
				$query .= " SET tanka = '$tanka'";
				$query .= " WHERE modelnum = '$modelnum'";
				$query .= " AND receiveflg = 0 ";
				$query .= " AND delflg = 0 ";
				$comm->ouputlog("zaikoテーブル更新", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
				if (! $db->query($query)) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
			}
			
			//初期値セット
			++$dataCnt;
			$pop="";
			$modelnum="";
			$cpu="";
			$memory="";
			$tanka_notax=0;
			$tanka=0;
			$tanka_trade=0;
			$reserv="";
			$memo="";
			$idxnum="";
			$hensyu=0;

		} else {
			if($key == "POP".$dataCnt){
				$pop = $val;
			}else if($key == "型番".$dataCnt){
				$modelnum = $val;
				$modelnum = trim($modelnum);
				$modelnum = preg_replace("/　|\s+/", "", $modelnum);
			}else if($key == "CPU".$dataCnt){
				$cpu = $val;
				$cpu = trim($cpu);
				$cpu = preg_replace("/　|\s+/", "", $cpu);
			}else if($key == "メモリ".$dataCnt){
				$memory = $val;
				$memory = trim($memory);
				$memory = preg_replace("/　|\s+/", "", $memory);
			}else if($key == "税抜".$dataCnt){
				$tanka_notax = $val;
			}else if($key == "税込".$dataCnt){
				$tanka = $val;
			}else if($key == "レンタル単価".$dataCnt){
				$tanka_rent = $val;
			}else if($key == "下取単価".$dataCnt){
				$tanka_trade = $val;
			}else if($key == "予約可不可".$dataCnt){
				$reserv = $val;
			}else if($key == "備考".$dataCnt){
				$memo = $val;
			}else if($key == "インデックス".$dataCnt){
				$idxnum = $val;
			}elseif($key == "編集". $dataCnt) {
				$hensyu = $val;
			}
		}
		//入力値出力
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
	}
	$comm->ouputlog("===データ追加処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_del_pc_price
//
// ■概要
//   価格表行削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_pc_price( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;

	//データ削除
	$comm->ouputlog("===価格表データ削除　1行===", $prgid, SYS_LOG_TYPE_INFO);
	$query = "";
	$query .= "DELETE FROM " . $table;
	$query .= " WHERE idxnum = " . $g_idx;
	$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ削除エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===削除完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_delweek_pc_price
//
// ■概要
//   価格表行削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_delweek_pc_price( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $week;
	global $g_post;

	//データ削除
	$comm->ouputlog("===価格表データ削除　週ごと===", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog("対象週：".$week, $prgid, SYS_LOG_TYPE_INFO);
	$query = "";
	$query .= "DELETE FROM " . $table;
	$query .= " WHERE week = " . $week;
	$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	//データ追加実行
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ削除エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_next_pc_price
//
// ■概要
//   価格表登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_next_pc_price( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_info;
	global $table_tanka;
	global $table_zaiko;
	//対象プログラム
	global $prgid;
	//引数
	global $l_date;
	global $g_post;
	
	//作成日付
	$today = date('YmdHis');
	
	//初期値化
	$dataCnt = 1;
	$pop="";
	$modelnum="";
	$tanka_trade = 0;
	$cpu="";
	$memory="";
	$tanka_notax=0;
	$tanka=0;
	$reserv="";
	$memo="";
	$idxnum="";
	$hensyu=0;
	$week="";
	
	//翌週の取得
	$query = "SELECT A.week";
	$query = $query." FROM php_calendar A ";
	$query = $query." WHERE A.date = ".date('Ymd', strtotime($l_date));
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$week = $row['week'];
	}
	
	foreach($g_post as $key=>$val){
		if ("end" . $dataCnt == $key) {
			//型番にデータが入っていれば、未登録分を翌週にコピー
			if ($modelnum != "") {
				if ($tanka_trade != 0) {
					$where_tanka = " AND A.tanka_trade = ".sprintf("'%s'", $tanka_trade);
				} else {
					$where_tanka = " AND A.tanka = ".sprintf("'%s'", $tanka);
				}
				$query = "
				SELECT 
					A.week 
				FROM 
					php_pc_price A 
				WHERE 
					A.week = ".sprintf("'%s'", $week)." 
					AND A.modelnum = ".sprintf("'%s'", $modelnum).
					$where_tanka;					
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				$g_flg = 0;
				while ($row = $rs->fetch_array()) {
					$g_flg = 1;
				}
				if($g_flg == 0){
					$query = "";
					$query = "INSERT INTO " . $table;
					$query .= " (insdt, upddt, week, pop, modelnum, cpu";
					$query .= " , memory, tanka_notax, tanka, tanka_rent, tanka_trade, reserv, memo)";
					$query .= " VALUE ('$today', '$today', '$week', '$pop', '$modelnum', '$cpu', ";
					$query .= " '$memory', '$tanka_notax', '$tanka', '$tanka_rent', $tanka_trade ,'$reserv', '$memo' )";
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					//infoテーブルに該当機種がない場合はinfoテーブルにも登録
					$query = "SELECT idxnum FROM " . $table_info . " WHERE modelnum = '".$modelnum."' AND delflg = 0";
					if (!($rs = $db->query($query))) {$comm->ouputlog("☆★☆データ確認エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);}
					else{
						if($rs->num_rows < 1){
							$query = "INSERT INTO " . $table_info;
							$query .= " (insdt, upddt, modelnum, cpu, memory, tanka, tanka_rent)";
							$query .= " VALUE ('$today', '$today', '$modelnum', '$cpu', '$memory', '$tanka', '$tanka_rent' )";
							$comm->ouputlog("infoテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							//データ追加実行
							if (! $db->query($query)) {
								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}else{
						//あれば単価を更新
							$query = "UPDATE " . $table_info;
							$query .= " SET tanka = '$tanka'";
							$query .= " WHERE modelnum = '$modelnum'";
							$query .= " AND delflg = 0 ";
							$comm->ouputlog("tankaテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							//データ追加実行
							if (! $db->query($query)) {
								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
					}
					//tankaテーブルに該当機種がない場合はtankaテーブルにも登録
					$query = "SELECT idxnum FROM " . $table_tanka . " WHERE modelnum = '".$modelnum."' AND delflg = 0";
					if (!($rs = $db->query($query))) {$comm->ouputlog("☆★☆データ確認エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);}
					else{
						if ($rs->num_rows < 1) {
							$query = "INSERT INTO " . $table_tanka;
							$query .= " (insdt, upddt, modelnum, tanka)";
							$query .= " VALUE ('$today', '$today', '$modelnum', '$tanka' )";
							$comm->ouputlog("tankaテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							//データ追加実行
							if (! $db->query($query)) {
								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}else{
						//あれば単価を更新
							$query = "UPDATE " . $table_tanka;
							$query .= " SET tanka = '$tanka'";
							$query .= " WHERE modelnum = '$modelnum'";
							$query .= " AND delflg = 0 ";
							$comm->ouputlog("tankaテーブルに追加", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							//データ追加実行
							if (! $db->query($query)) {
								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
					}
					//リングロー、融興が在庫を持っていれば単価を更新
					$query = "UPDATE " . $table_zaiko;
					$query .= " SET tanka = '$tanka'";
					$query .= " WHERE modelnum = '$modelnum'";
					$query .= " AND (staff = 'RNG' OR staff='YKO' OR staff='補修センター') ";
					$query .= " AND hanbaiflg = 0 ";
					$query .= " AND delflg = 0 ";
					$comm->ouputlog("zaikoテーブル更新", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
				}
			}
			
			//初期値セット
			++$dataCnt;
			$pop="";
			$modelnum="";
			$cpu="";
			$memory="";
			$tanka_notax=0;
			$tanka=0;
			$tanka_trade=0;
			$reserv="";
			$memo="";
			$idxnum="";
			$hensyu=0;
		}else{
			if($key == "POP".$dataCnt){
				$pop = $val;
			}else if($key == "型番".$dataCnt){
				$modelnum = $val;
			}else if($key == "CPU".$dataCnt){
				$cpu = $val;
			}else if($key == "メモリ".$dataCnt){
				$memory = $val;
			}else if($key == "税抜".$dataCnt){
				$tanka_notax = $val;
			}else if($key == "税込".$dataCnt){
				$tanka = $val;
			}else if($key == "レンタル単価".$dataCnt){
				$tanka_rent = $val;
			}else if($key == "下取単価".$dataCnt){
				$tanka_trade = $val;
			}else if($key == "予約可不可".$dataCnt){
				$reserv = $val;
			}else if($key == "備考".$dataCnt){
				$memo = $val;
			}
		}
		//入力値出力
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
	}
	$comm->ouputlog("===データ追加処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_ins_pc_price_cron
//
// ■概要
//   価格表自動登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_pc_price_cron( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_info;
	global $table_tanka;
	global $table_zaiko;
	//対象プログラム
	global $prgid;
	//引数
	global $week;
	global $l_date;
	
	//作成日付
	$today = date('YmdHis');
	
	$comm->ouputlog("mysql_ins_pc_price_cron 実行", $prgid, SYS_LOG_TYPE_INFO);
	
	//価格表が登録されていないか確認
	$query = "SELECT A.week";
	$query .= " FROM php_pc_price A ";
	$query .= " WHERE A.week = '".$week."'";
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$g_flg = 0;
	while ($row = $rs->fetch_array()) {
		$g_flg = 1;
	}
	if($g_flg == 0){
		//前週の取得
		$query = "SELECT A.week";
		$query .= " FROM php_calendar A ";
		$query .= " WHERE A.date = '".date('Y-m-d' , strtotime(date($p_date1).' - 1 day'))."'";
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$p_week = $row['week'];
		}
		//テーブルから前週とおなじ恒常商品を取得
		$query = "INSERT INTO " . $table;
		$query .= " (insdt, upddt, week, pop, modelnum, cpu";
		$query .= " , memory, tanka_notax, tanka, tanka_rent, tanka_trade ,reserv, memo";
		$query .= " ) ";
		$query .= " SELECT '$today', '$today', '$week' ";
		$query .= " , A.pop, A.modelnum, A.cpu, A.memory, A.tanka_notax, A.tanka, A.tanka_rent, A.tanka_trade ,A.reserv, A.memo";
		$query .= " FROM php_pc_price A ";
		$query .= " WHERE A.week = '".$p_week."' ";
		$query .= " AND A.modelnum <> '' ";
		$query .= " AND ( ";
		$query .= " A.modelnum IN ";
		$query .= " ( SELECT modelnum FROM php_t_pc_zaiko ";
		$query .= " WHERE staff IN  ";
		$query .= " (SELECT staff FROM php_performance ";
		$query .= " WHERE week = '".$week."' ";
		$query .= " GROUP BY staff) ";
		$query .= " AND delflg=0 ";
		$query .= " AND hanbaiflg=0";
		$query .= " GROUP BY modelnum) ";
		$query .= " OR A.modelnum IN ";
		$query .= " ( SELECT modelnum FROM php_s_pc_zaiko ";
		$query .= " WHERE staff IN  ";
		$query .= " ( SELECT staff FROM php_performance ";
		$query .= " WHERE week = '".$week."' ";
		$query .= " GROUP BY staff) ";
		$query .= " AND delflg=0 ";
		$query .= " AND receiveflg=0 ";
		$query .= " GROUP BY modelnum) ";
		$query .= " OR A.modelnum LIKE '%DSK%'";
		$query .= " ) ";
		$query .= " ORDER BY A.modelnum LIKE '%DSK%' ASC, A.tanka, A.modelnum, A.pop";
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		//データ追加実行
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
	}
	$comm->ouputlog("===データ追加処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_ins_pc_price_cron_team
//
// ■概要
//   価格表自動登録（チーム用）
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_pc_price_cron_team( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	global $table_info;
	global $table_tanka;
	global $table_zaiko;
	//対象プログラム
	global $prgid;
	//引数
	global $week;
	global $l_date;
	
	//作成日付
	$today = date('YmdHis');
	
	$comm->ouputlog("mysql_ins_pc_price_cron_team 実行", $prgid, SYS_LOG_TYPE_INFO);

	//前週の取得
	$query = "SELECT A.week";
	$query .= " FROM php_calendar A ";
	$query .= " WHERE A.date = '".date('Y-m-d' , strtotime(date($p_date1).' - 1 day'))."'";
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$p_week = $row['week'];
	}
	//会場のある担当者を取得
	$query = "SELECT A.staff, A.lane";
	$query .= " FROM php_performance A ";
	$query .= " WHERE A.week = '".$week."'";
	$query .= " GROUP BY A.staff";
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		//価格表が登録されていないか確認
		$query2 = "SELECT A.week";
		$query2 .= " FROM php_pc_price_team A ";
		$query2 .= " WHERE A.week = '".$week."'";
		$query2 .= " AND A.staff = '".$row['staff']."'";
		$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs2 = $db->query($query2))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		$g_flg = 0;
		while ($row2 = $rs2->fetch_array()) {
			$g_flg = 1;
		}
		$query2 = "SELECT A.companycd";
		$query2 .= " FROM php_l_user A ";
		$query2 .= " WHERE A.staff = '".$row['staff']."'";
		$comm->ouputlog($query2, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs2 = $db->query($query2))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row2 = $rs2->fetch_array()) {
			$g_compcd = $row2['companycd'];
		}
		if($g_flg == 0){
			//テーブルから在庫のある前週のデータを取得
			$query3 = "INSERT INTO php_pc_price_team";
			$query3 .= " (insdt, upddt, week, pop, barcode, modelnum, cpu";
			$query3 .= " , memory, tanka_notax, tanka, tanka_rent, reserv, tenkey, drive, kbn, memo, staff";
			$query3 .= " ) ";
			$query3 .= " SELECT '$today', '$today', '$week' ";
			$query3 .= " , A.pop, A.barcode, A.modelnum, A.cpu, A.memory, A.tanka_notax, A.tanka, A.tanka_rent, A.reserv, A.tenkey, A.drive, A.kbn, A.memo, A.staff";
			$query3 .= " FROM php_pc_price_team A ";
			$query3 .= " WHERE A.staff = '".$row['staff']."' ";
			$query3 .= " AND A.week = '".$p_week."' ";
			$query3 .= " AND A.modelnum <> '' ";
			if($g_compcd == "J"){
				$query3 .= " AND ( ";
				$query3 .= " A.modelnum IN ";
				$query3 .= " ( SELECT modelnum FROM php_t_pc_zaiko ";
				$query3 .= " WHERE staff = '".$row['staff']."' ";
				$query3 .= " AND delflg=0 ";
				$query3 .= " AND hanbaiflg=0) ";
				$query3 .= " OR A.modelnum IN ";
				$query3 .= " ( SELECT modelnum FROM php_s_pc_zaiko ";
				$query3 .= " WHERE staff = '".$row['staff']."' ";
				$query3 .= " AND delflg=0 ";
				$query3 .= " AND receiveflg=0) ";
				$query3 .= " OR A.modelnum IN ";
				$query3 .= " ( SELECT AA.modelnum FROM php_shipment AA ";
				$query3 .= " LEFT OUTER JOIN php_calendar AB ON AA.buydt=AB.date ";
				$query3 .= " WHERE AA.lane = '".$row['lane']."' ";
				$query3 .= " AND AB.week= '".$p_week."') ";
				$query3 .= " OR A.modelnum ='駐車料金' ";
				$query3 .= " ) ";
			}
			$query3 .= " ORDER BY A.modelnum LIKE '%駐車料金' ASC, A.modelnum LIKE '%DSK' ASC, A.tanka, A.modelnum, A.pop";
			$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($query3, $prgid, SYS_LOG_TYPE_DBUG);
			//データ追加実行
			if (! $db->query($query3)) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
		}
	}
	$comm->ouputlog("===データ追加処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_ins_pc_price_team
//
// ■概要
//   チーム用価格表登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_pc_price_team( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_team;
	global $table_info;
	global $table_tanka;
	global $table_zaiko;
	//対象プログラム
	global $prgid;
	//引数
	global $week;
	global $g_idxnum;
	global $g_idx;
	global $g_post;
	
	//作成日付
	$today = date('YmdHis');
	
	//初期値化
	++$dataCnt;
	$pop="";
	$barcode="";
	$modelnum="";
	$tenkey=0;
	$drive=0;
	$tanka_notax=0;
	$tanka=0;
	$tanka_rent=0;
	$tanka_trade=0;
	$memo="";
	$idxnum="";
	$hensyu=0;
	$kbn=0;
	
	$query = "SELECT A.staff, A.week";
	$query .= " FROM php_performance A ";
	$query .= " WHERE A.idxnum = '$g_idxnum'";
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$week = $row['week'];
		$staff = $row['staff'];
	}
	$reserv="2";
	foreach($g_post as $key=>$val){
		if ("end" . $dataCnt == $key) {
			$query = "";
			if($hensyu == 1 && $idxnum == ""){
				// 下取りの場合は下取り価格へ登録
				if ($kbn == 5) {
					$tanka_trade = $tanka;
				}
				//インデックスが空の場合はインサート
				$query = "INSERT INTO " . $table_team;
				$query .= " (insdt, upddt, week, staff, barcode, pop, modelnum";
				$query .= " ,tenkey, drive, tanka_notax, tanka, tanka_rent, tanka_trade, reserv, memo, kbn)";
				$query .= " VALUE ('$today', '$today', '$week', '$staff', '$barcode', '$pop', '$modelnum'";
				$query .= ", '$tenkey', '$drive', '$tanka_notax', '$tanka', '$tanka_rent', '$tanka_trade', '$reserv', '$memo', '$kbn' )";
			}else if ($hensyu == 1 && $idxnum != ""){
				//インデックスにデータがある場合はアップデート
				$query = "UPDATE " . $table_team;
				$query .= " SET upddt = " . sprintf("'%s'", date('YmdHis'));
				$query .= " ,updcount = updcount + 1";
				$query .= " ,week   = ". sprintf("'%s'", $week);
				$query .= " ,staff   = ". sprintf("'%s'", $staff);
				$query .= " ,pop   = ". sprintf("'%s'", $pop);
				$query .= " ,modelnum   = ". sprintf("'%s'", $modelnum);
				$query .= " ,barcode   = ". sprintf("'%s'", $barcode);
				$query .= " ,tenkey = ". sprintf("'%s'", $tenkey);
				$query .= " ,drive = ". sprintf("'%s'", $drive);
				$query .= " ,tanka_notax = ". sprintf("'%s'", $tanka_notax);
				$query .= " ,tanka = ". sprintf("'%s'", $tanka);
				$query .= " ,reserv = ". sprintf("'%s'", $reserv);
				$query .= " ,memo = ". sprintf("'%s'", $memo);
				$query .= " ,kbn = ". sprintf("'%s'", $kbn);
				$query .= " ,tanka_rent = ". sprintf("'%s'", $tanka_rent);
				$query .= " WHERE idxnum = " . sprintf("'%s'", $idxnum);
			}
			//編集フラグが立っていればデータベース更新
			if($query != ""){
				$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
				if (! $db->query($query)) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
			}
			
			//初期値セット
			++$dataCnt;
			$pop="";
			$barcode="";
			$modelnum="";
			$tenkey=0;
			$drive=0;
			$tanka_notax=0;
			$tanka=0;
			$tanka_trade=0;
			$kbn=0;
			$memo="";
			$idxnum="";
			$hensyu=0;

		} else {
			if($key == "POP".$dataCnt){
				$pop = $val;
			}else if($key == "バーコード番号".$dataCnt){
				$barcode = $val;
				$barcode = trim($barcode);
				$barcode = preg_replace("/　|\s+/", "", $barcode);
			}else if($key == "型番".$dataCnt){
				$modelnum = $val;
				$modelnum = trim($modelnum);
				$modelnum = preg_replace("/　|\s+/", "", $modelnum);
			}else if($key == "テンキー".$dataCnt){
				$tenkey = $val;
			}else if($key == "ドライブ".$dataCnt){
				$drive = $val;
			}else if($key == "税抜".$dataCnt){
				$tanka_notax = $val;
			}else if($key == "税込".$dataCnt){
				$tanka = $val;
			}else if($key == "レンタル単価".$dataCnt){
				$tanka_rent = $val;
			}else if($key == "区分".$dataCnt){
				$kbn = $val;
			}else if($key == "備考".$dataCnt){
				$memo = $val;
			}else if($key == "インデックス".$dataCnt){
				$idxnum = $val;
			}elseif($key == "編集". $dataCnt) {
				$hensyu = $val;
			}
		}
		//入力値出力
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
	}
	$comm->ouputlog("===データ追加処理完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_del_pc_price_team
//
// ■概要
//   価格表行削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_pc_price_team( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_team;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;

	//データ削除
	$comm->ouputlog("===価格表データ削除　個別価格表　1行===", $prgid, SYS_LOG_TYPE_INFO);
	$query = "";
	$query .= "DELETE FROM " . $table_team;
	$query .= " WHERE idxnum = " . $g_idx;
	$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

	//データ追加実行
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}

?>
