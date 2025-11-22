<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
//   ・ネットストアデータ更新
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
$thanksPage1 = "./nsorder_list_all2.php";
$thanksPage2 = "./manual/henpin/henpin_tel_top.php";
$thanksPage3 = "./yoyaku_list.php?kbn=2";

$table = "php_telorder__";

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
$comm->ouputlog("==== ネット注文データ 更新 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//グローバルIPアドレス取得
$g_ip = $_SERVER['REMOTE_ADDR'];

//担当者
$p_staff = $_COOKIE['con_perf_staff'];
$companycd = $_COOKIE['con_perf_companycd'];

//引数取得
$do = $_GET['do'];
$g_idxnum = $_GET['idxnum'];
$g_post = $_POST;

if($empty_flag != 1){
	//--------------------------
	// ＤＢ更新　　
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
		if($do == "ins"){
			$flg = mysql_ins($db);
			header("Location: ".$thanksPage1."?adminflg=".$flg);
		}else if($do == "update"){
			$flg = mysql_upd($db);
			header("Location: ".$thanksPage1);
		}else if($do == "delete"){
			$flg = mysql_del($db);
			header("Location: ".$thanksPage1);
		}else if($do == "delete_stores"){
			$flg = mysql_del_stores($db);
			header("Location: ".$thanksPage1);
		}else if($do == "delete_stores_y"){
			$flg = mysql_del_stores($db);
			header("Location: ".$thanksPage3);
		}else if($do == "delete_bank"){
			$flg = mysql_del_bank($db);
			header("Location: ".$thanksPage2."?t_idx=".$flg);
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
//   mysql_ins
//
// ■概要
//   登録
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $p_staff;
	global $g_post;
	$today = date('Y-m-d H:i:s');
	
	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	
	//時間をリストに格納
	$time_list = array("0" => "0-6"
			, "6" => "6-7"
			, "7" => "7-8"
			, "8" => "8-9"
			, "9" => "9-10"
			, "10" => "10-11"
			, "11" => "11-12"
			, "12" => "12-13"
			, "13" => "13-14"
			, "14" => "14-15"
			, "15" => "15-16"
			, "16" => "16-17"
			, "17" => "17-18"
			, "18" => "18-19"
			, "19" => "19-20"
			, "20" => "20-21"
			, "21" => "21-22"
			, "22" => "22-23"
			, "23" => "23-24"
			);
			
	$comm->ouputlog("日付：".$g_post['日付'], $prgid, SYS_LOG_TYPE_INFO);
	if(isset($g_post['日付'])){
		//DBに格納
		for($i=0; $i<$g_post["最大行"]; ++$i){
			foreach($time_list as $key => $val){
				$editflg = 0;
				$comm->ouputlog("i = ".$i.", key = ".$key, $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog("use_staff = ".$g_post["use_staff_".$i."_".$key].", t_use_staff = ".$g_post["t_use_staff_".$i."_".$key]." ,インデックス = ".$g_post["インデックス_".$i."_".$key], $prgid, SYS_LOG_TYPE_INFO);
				if($g_post["use_staff_".$i."_".$key] <> $g_post["t_use_staff_".$i."_".$key] && $g_post["インデックス_".$i."_".$key] == ""){
					$editflg = 1;
				}else if($g_post["use_staff_".$i."_".$key] <> $g_post["t_use_staff_".$i."_".$key]){
					$editflg = 2;
				}
				$comm->ouputlog("editflg = ".$editflg.", key = ".$key, $prgid, SYS_LOG_TYPE_INFO);
				//新規登録
				if($editflg == 1){
					$query  = "INSERT INTO ".$table."(";
					$query .= " ".$collist["登録日時"];
					$query .= ", ".$collist["更新日時"];
					$query .= ", ".$collist["更新担当者"];
					$query .= ", ".$collist["ジム"];
					$query .= ", ".$collist["日付"];
					$query .= ", ".$collist["使用時間"];
					$query .= ", ".$collist["使用者"];
					$query .= " )VALUES( ";
					$query .= " ".sprintf("'%s'", $today);
					$query .= ", ".sprintf("'%s'", $today);
					$query .= ", ".sprintf("'%s'", $p_staff);
					$query .= ", ".sprintf("'%s'", $g_post["ジム".$i]);
					$query .= ", ".sprintf("'%s'", $g_post['日付']);
					$query .= ", ".$key;
					$query .= ", ".sprintf("'%s'", $g_post["use_staff_".$i."_".$key]);
					$query .= " );";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
				//更新
				}else if($editflg == 2){
					//DBを更新
					$query  = "UPDATE ".$table;
					$query  .= " SET upddt = ".sprintf("'%s'", $today);
					$query  .= ",".$collist["更新回数"]."  = ".$collist["更新回数"]." + 1";
					$query .= ", ".$collist["更新担当者"]." = ".sprintf("'%s'", $p_staff);
					$query .= ", ".$collist["使用者"]." = ".sprintf("'%s'", $g_post["use_staff_".$i."_".$key]);
					$query  .= " WHERE idxnum = ".sprintf("'%s'", $g_post["インデックス_".$i."_".$key]);
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
				}
			}
		}
	}
	
	return $g_post["管理者フラグ"];
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_upd
//
// ■概要
//   更新
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $p_staff;
	global $g_post;
	$today = date('Y-m-d H:i:s');
	
	
	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	
	//保存する項目をリストに格納
	$arrkey = array("会社名", "ご担当者様","電話番号","電話番号２","FAX番号","メールアドレス","郵便番号１","郵便番号２","住所１","住所２","住所３","申請書送付先","配布可能エリア","配布可能枚数","料金","依頼期日","データ形式","URL","備考");
	
	//DBを更新
	
	return $g_post["インデックス"];
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_del
//
// ■概要
//   削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $p_staff;
	global $g_idxnum;
	$today = date('Y-m-d H:i:s');
	
	
	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	
	if($g_idxnum<>''){
		$comm->ouputlog("データ削除 実行 ".$g_idxnum, $prgid, SYS_LOG_TYPE_INFO);
		//DBを更新
		$query  = "UPDATE ".$table;
		$query  .= " SET upddt = ".sprintf("'%s'", $today);
		$query  .= ",".$collist["更新回数"]."  = ".$collist["更新回数"]." + 1";
		$query .= ", ".$collist["削除フラグ"]." = ".sprintf("'%s'", '1');
		$query .= ", ".$collist["備考"]." = CONCAT(".$collist["備考"].",".sprintf("'%s'", 'データ削除（'.$today.' '.$p_staff.")").")";
		$query  .= " WHERE t_idx = ".sprintf("'%s'", $g_idxnum);
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
	}
	
	return $g_idxnum;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_del_stores
//
// ■概要
//   削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_stores($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $p_staff;
	global $g_idxnum;
	$today = date('Y-m-d H:i:s');
	
	
	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	
	if($g_idxnum<>''){
		$comm->ouputlog(" mysql_del_stores データ削除 実行 ".$g_idxnum, $prgid, SYS_LOG_TYPE_INFO);
		//DBを更新
		$query  = "UPDATE ".$table;
		$query  .= " SET upddt = ".sprintf("'%s'", $today);
		$query  .= ",".$collist["更新回数"]."  = ".$collist["更新回数"]." + 1";
		$query .= ", ".$collist["削除フラグ"]." = ".sprintf("'%s'", '1');
		$query .= ", ".$collist["備考"]." = CONCAT(".$collist["備考"].",".sprintf("'%s'", 'STORESよりｸﾚｼﾞｯﾄ返金済　データ削除（'.$today.' '.$p_staff.")").")";
		$query  .= " WHERE t_idx = ".sprintf("'%s'", $g_idxnum);
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
	}
	
	return $g_idxnum;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
//   mysql_del_bank
//
// ■概要
//   削除
//
// ■引数
//   第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_bank($db) {
	//グローバル変数
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $today;
	global $p_staff;
	global $g_idxnum;
	$today = date('Y-m-d H:i:s');
	
	
	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	
	if($g_idxnum<>''){
		$comm->ouputlog(" mysql_del_bank データ削除 実行 ".$g_idxnum, $prgid, SYS_LOG_TYPE_INFO);
		//DBを更新
		$query  = "UPDATE ".$table;
		$query  .= " SET upddt = ".sprintf("'%s'", $today);
		$query  .= ",".$collist["更新回数"]."  = ".$collist["更新回数"]." + 1";
		$query .= ", ".$collist["伝票番号"]." = ".sprintf("'%s'", '000000000000');
		$query .= ", ".$collist["ステータス"]." = ".sprintf("'%s'", '9');
		$query .= ", ".$collist["アウトプットフラグ"]." = ".sprintf("'%s'", '3');
		$query .= ", ".$collist["備考"]." = CONCAT(".$collist["備考"].",".sprintf("'%s'", 'ｸﾚｼﾞｯﾄ返金不可　返品登録のためステータスを変更（'.$today.' '.$p_staff.")").")";
		$query  .= " WHERE t_idx = ".sprintf("'%s'", $g_idxnum);
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
	}
	
	return $g_idxnum;
}

?>
