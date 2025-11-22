<?php header("Content-Type:text/html;charset=utf-8"); ?>
<?php //error_reporting(E_ALL | E_STRICT);
//==================================================================================================
// ■機能概要
// ・ＰＣ販売実績情報 更新
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

//送信確認画面の表示
$confirmDsp = 1;

//送信完了後に自動的に指定のページ
$jumpPage = 0;

//送信完了後に表示するページURL
$thanksPage0 = "./hanbai_input.php?idx=";
$thanksPage1 = "./hanbai_s_input.php?idx=";
$thanksPage2 = "./hanbai_returned.php?idx=";
$thanksPage3 = "./hanbai_entry.php?idx=";
$thanksPage4 = "./barcode_input.php?idx=";
$thanksPage5 = "./hanbai_huryou.php?idx=";
$thanksPage6 = "./barcode_input_b.php";
$thanksPage7 = "./barcode_input_p.php?idx=";
$thanksPage8 = "./huryou_code_input.php";
$thanksPage9 = "./henpin_code_input.php";
$thanksPage21 = "./hanbai_entry_trade.php?idx=";
$thanksPage23 = "./hanbai_entry_high.php?idx=";

//対象テーブル
$table = "php_t_pc_hanbai";
$table_m = "php_t_pc_reserv";
$table2 = "php_t_option_hanbai";
$table_option = "php_t_option_hanbai";
$table_high = "php_t_pc_hanbai_high";

// 以下の変更は知識のある方のみ自己責任でお願いします。

//--------------------------
// 関数実行、変数初期化　　
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
$comm->ouputlog("==== 販売実績 更新 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

//引数取得
$g_category=$_GET['category'];
$g_idx=$_GET['idx'];
$g_section=$_GET['section'];
$g_week = $_GET['week'];
$g_staff = $_GET['staff'];
$g_kbn = $_GET['kbn'];
//返品キャンセル
$g_venueid=$_GET['venueid'];
$g_venueno=$_GET['venueno'];
$comm->ouputlog("category=" . $g_category, $prgid, SYS_LOG_TYPE_DBUG);
$comm->ouputlog("idx=" . $g_idx, $prgid, SYS_LOG_TYPE_DBUG);
$comm->ouputlog("venueid=" . $g_venueid, $prgid, SYS_LOG_TYPE_DBUG);
$comm->ouputlog("venueno=" . $g_venueno, $prgid, SYS_LOG_TYPE_DBUG);

$g_post=$_POST;
foreach($g_post as $key=>$val) {
	$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
}

//カテゴリ別情報設定
//　0:会場詳細登録
//　1:販売実績登録
//　2:会場詳細修正
//　3:販売実績修正
//　4:返品登録
//　5:返品取消（ＰＣ）
//　6:返品取消（備品）
//　10:販売実績登録（新版）
//　11:バーコード連携
//　12:不良品登録
//　13:不良品登録削除
//　14:バーコード連携
//　15:バーコード連携
//　16:バーコード連携
//　17:バーコード不良登録
//　18:バーコード不良削除
//　19:バーコード返品登録
//　20:バーコード返品削除
//　21:販売実績登録（下取）
//　22:バーコード連携（ハイスペック）
//　23:販売実績登録（ハイスペック）

$require="";
$field="";

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

		//　0:会場詳細登録 2:会場詳細修正
		if ($g_category == 0 || $g_category == 2) {
			mysql_upd_performance1($db);
			if ($g_category == 0) {
				header("Location: ".$thanksPage0 . $g_idx . "&section=" . $g_section);
			}
			elseif ($g_category == 1) {
				header("Location: ".$thanksPage1 . $g_idx);
			}
		}
		//　1:販売実績登録
		elseif ($g_category == 1) {
			mysql_upd_performance2($db);
			mysql_ins_hanbai_i($db);
			mysql_ins_hanbai_d($db);
			mysql_ins_hanbai_m($db);
			mysql_ins_hanbai_b($db);
			header("Location: ".$thanksPage0 . $g_idx . "&section=" . $g_section);
		}
		//　3:販売実績修正
		elseif ($g_category == 3) {
			mysql_upd_hanbai($db);
			header("Location: ".$thanksPage1 . $g_idx);
		}
		//　4:返品登録
		elseif ($g_category == 4) {
			mysql_ins_returned($db);
			header("Location: ".$thanksPage2 . $g_idx);
		}
		//　5:返品取消（ＰＣ）
		elseif ($g_category == 5) {
			mysql_cancel_p_returned($db);
			header("Location: ".$thanksPage2 . $g_idx);
		}
		//　6:返品取消（備品）
		elseif ($g_category == 6) {
			mysql_cancel_o_returned($db);
			header("Location: ".$thanksPage2 . $g_idx);
		}
		//　10:販売実績登録（新版）
		elseif ($g_category == 10 || $g_category == 21) {
			mysql_entry_hanbai($db);
			mysql_entry_option($db);
			if ($g_category == 10) {
				header("Location: ".$thanksPage3 . $g_idx . "&section=" . $g_section);
			} elseif ($g_category == 21) {
				header("Location: ".$thanksPage3 . $g_idx . "&section=" . $g_section . "&trade=1");
			}
		}
		//　11:バーコード連携
		elseif ($g_category == 11) {
			$lostnum = mysql_barcode_option($db);
			header("Location: ".$thanksPage4 . $g_idx . "&section=" . $g_section."&lostnum=".$lostnum);
		}
		//　１２:不良品登録
		elseif ($g_category == 12) {
			mysql_ins_huryou($db);
			header("Location: ".$thanksPage5 . $g_idx);
		}
		//　１３:不良品登録削除
		elseif ($g_category == 13) {
			mysql_cancel_huryou($db);
			header("Location: ".$thanksPage5 . $g_idx);
		}
		//　14:バーコード連携
		elseif ($g_category == 14) {
			$lostnum = mysql_barcode_option($db);
			header("Location: ".$thanksPage6."?lostnum=".$lostnum);
		}
		//　16:バーコード連携
		elseif ($g_category == 16) {
			mysql_barcode_entry($db);
			mysql_barcode_entry_option($db);
			header("Location: ".$thanksPage7 . $g_idx . "&section=" . $g_section);
		}
		//　17:バーコード不良登録
		elseif ($g_category == 17) {
			mysql_huryou_code($db);
			header("Location: ".$thanksPage8 . "?week=" . $g_week . "&staff=" . $g_staff);
		}
		//　18:バーコード不良削除
		elseif ($g_category == 18) {
			mysql_huryou_code_del($db);
			header("Location: ".$thanksPage8 . "?week=" . $g_week . "&staff=" . $g_staff);
		}
		//　19:バーコード返品登録
		elseif ( $g_category == 19 ) {
			$re = mysql_henpin_code ( $db );
			if ( $re ) {
				header( "Location: ".$thanksPage9 . "?idx=" . $g_idx . "&re=true" );
			} else {
				header( "Location: ".$thanksPage9 . "?idx=" . $g_idx . "&re=false" );
			}
		}
		//　20:バーコード返品削除
		elseif ( $g_category == 20 ) {
			mysql_henpin_code_del ( $db );
			header ( "Location: ".$thanksPage9 . "?idx=" . $g_idx );
		}
		//　22:バーコード連携
		elseif ($g_category == 22) {
			$table = $table_high;
//			mysql_barcode_entry($db);
//			mysql_barcode_entry_option($db);
			mysql_barcode_entry_high($db);
			mysql_barcode_entry_option_high($db);
			header("Location: ".$thanksPage7 . $g_idx . "&section=" . $g_section);
		}
		//　10:販売実績登録（新版）
		elseif ($g_category == 23) {
			mysql_entry_hanbai_high($db);
			mysql_entry_option_high($db);
			header("Location: ".$thanksPage23 . $g_idx . "&section=" . $g_section);
		}
		//データベース切断
		if ($result) { $dba->mysql_discon($db); }
	}
}

?>

<?php
//----------------------------------------------------------------------
// 関数定義(START)
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
// mysql_upd_performance
//
// ■概要
// 会場詳細情報更新
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_performance1( $db) {

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

	// ================================================
	// ■　□　■　□　個別表示　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" UPDATE php_performance A ";
	$query .="  SET weather = " . sprintf("'%s'", $_POST{'天候'});
	$query .="  , parking = " . sprintf("'%s'", $_POST{'駐車場'});
	$query .="  , loading = " . sprintf("'%s'", $_POST{'搬入'});
	$query .="  , sheets = " . sprintf("'%s'", $_POST{'枚数'});
	$query .="  , mitui = " . sprintf("'%s'", $_POST{'三井堂'});
	$query .="  , chunichi = " . sprintf("'%s'", $_POST{'中日総合'});
	$query .="  , venue = " . sprintf("'%s'", $_POST{'会場代'});
	$query .="  , hyper = " . sprintf("'%s'", $_POST{'ハイパー'});
	$query .="  , addfee = " . sprintf("'%s'", $_POST{'追加'});
	$query .=" WHERE A.idxnum = " . sprintf("'%s'", $g_idx);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return $performance;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_upd_performance2
//
// ■概要
// 会場詳細情報更新
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_performance2( $db) {

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
	global $g_section;
	global $g_post;

	// ================================================
	// ■　□　■　□　個別表示　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" UPDATE php_performance A ";
	if ($g_section == 1) {
		$query .="  SET z1visitnum1 = " . sprintf("'%s'", $_POST{'タウン'});
		$query .="  , z1visitnum2 = " . sprintf("'%s'", $_POST{'チラシ'});
		$query .="  , z1visitnum3 = " . sprintf("'%s'", $_POST{'広報'});
		$query .="  , z1kaisyu = " . sprintf("'%s'", $_POST{'回収台数'});
	}
	elseif ($g_section == 2) {
		$query .="  SET z2visitnum1 = " . sprintf("'%s'", $_POST{'タウン'});
		$query .="  , z2visitnum2 = " . sprintf("'%s'", $_POST{'チラシ'});
		$query .="  , z2visitnum3 = " . sprintf("'%s'", $_POST{'広報'});
		$query .="  , z2kaisyu = " . sprintf("'%s'", $_POST{'回収台数'});
	}
	elseif ($g_section == 3) {
		$query .="  SET z3visitnum1 = " . sprintf("'%s'", $_POST{'タウン'});
		$query .="  , z3visitnum2 = " . sprintf("'%s'", $_POST{'チラシ'});
		$query .="  , z3visitnum3 = " . sprintf("'%s'", $_POST{'広報'});
		$query .="  , z3kaisyu = " . sprintf("'%s'", $_POST{'回収台数'});
	}
	$query .=" WHERE A.idxnum = " . sprintf("'%s'", $g_idx);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return $performance;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_sel_personal
//
// ■概要
// 会場情報取得
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_sel_personal( $db) {

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

	$comm->ouputlog("mysql_sel_personalログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	// ================================================
	// ■　□　■　□　個別表示　■　□　■　□
	// ================================================
	//----- データ抽出
	$query .=" SELECT concat(DATE_FORMAT(A.buydt , '%Y%m%d' ), LPAD(A.lane,2,'0') , '-' , A.branch ) as venueid, A.staff, A.week ";
	$query .=" FROM php_performance A ";
	$query .=" WHERE A.idxnum = " . sprintf("'%s'", $g_idx);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$performance['venueid'] = $row['venueid'];
		$performance['staff'] = $row['staff'];
		$performance['week'] = $row['week'];
	}

	return $performance;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_ins_hanbai
//
// ■概要
// 個人情報新規登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_hanbai( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;

	$comm->ouputlog("mysql_ins_hanbaiログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "end";
	$arrkey = array("メーカー名","型番","ＣＰＵ","メモリ","ＨＤＤ","ドライブ","無線ＬＡＮ","対象ＯＳ","単価","仕入単価","販売","予約","分類","処理日付","処理ＮＯ");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_pc_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	$optionCnt = 0;
	$dataCnt = 1;
	$hannum = 0;
	$grenum = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if ($end . $dataCnt == $key) {
			//チェックがある場合、登録
			if ($hannum + $grenum > 0) {
				// ================================================
				// ■　□　■　□　販売Ｔ登録　■　□　■　□
				// ================================================
				//会場ＮＯ
				$venueno++;
				$query1 .= "," . $collist["会場ＮＯ"];
				$query2 .= "," . $venueno;
				if ($hannum > 0) {
					$query1 .= "," . $collist["販売数量"];
					$query2 .= "," . $hannum;
				}
				elseif ($grenum > 0) {
					$query1 .= "," . $collist["現物予約数量"];
					$query2 .= "," . $grenum;
				}
				//ＳＱＬ文結合
				$query1 .= ")";
				$query2 .= ")";
				$query .= $query1 . $query2;
				$comm->ouputlog("===データ追加ＳＱＬ(mysql_ins_hanbai)===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

				//データ追加実行
// ----- 2019.06 ver7.0対応
//				if (! mysql_query($query, $db)) {
				if (! $db->query($query)) {
//					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					return false;
				}
				$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
				//見本予約以外は在庫の消込を行う
				if ($hannum + $grenum > 0) {
					// ================================================
					// ■　□　■　□　販売Ｔ登録　■　□　■　□
					// ================================================
					//会場ＮＯ
					$venueno++;
					// ================================================
					// ■　□　■　□　在庫Ｔ削除　■　□　■　□
					// ================================================
					$query = $delete1 . $delete2 . $delete3;
					$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);

					//データ削除実行
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($query, $db)) {
					if (! $db->query($query)) {
//						$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			$dataCnt++;
			$optionCnt = 0;
			$hannum = 0;
			$grenum = 0;
		} else {
			//販売数量チェックされている場合
			if($key == "販売". $dataCnt) {
				$hannum = $val;
			}
			//現物予約チェックされている場合
			elseif($key == "現物". $dataCnt) {
				$grenum = $val;
			}
			else {
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if($arrkey[$cnt] . $dataCnt == $key){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if ($length > 0 && strlen($val) > $length) {
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}
					else {
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if($key == "処理日付". $dataCnt) {
				$delete2 = $m_delete2 . sprintf("'%s'", $val);
			}
			elseif($key == "処理ＮＯ". $dataCnt) {
				$delete3 = $m_delete3 . sprintf("'%s'", $val);
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
// mysql_ins_hanbai_i
//
// ■概要
// PC販売登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_hanbai_i( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_ins_hanbai_iログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "end";
	$arrkey = array("メーカー名","型番","ＣＰＵ","メモリ","対象ＯＳ","単価","仕入単価","分類");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}


	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.modelnum , A.siire_d, A.profit ";
	$query .=" FROM php_pc_tanka A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.modelnum";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$profitlist = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['modelnum'];
		$siirelist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;
	//区分
	$m_query1 .= "," . $collist["区分"];
	$m_query2 .= "," . "'1'";
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_pc_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	$optionCnt = 0;
	$dataCnt = 1;
	$hannum = 0;
	$grenum = 0;
	$mrenum = 0;
	$makrname = "";
	$modelnum = "";
	$cpu = "";
	$memory = "";
	$tanka = 0;
	$profit = 0;
	$siire = 0;
	$deleteflg = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if ($end . $dataCnt == $key) {
			//販売・現物予約数合算
			$suryou = $hannum + $grenum;
			//チェックがある場合、登録
			if ($suryou > 0) {
				// ================================================
				// ■　□　■　□　在庫Ｔ検索　■　□　■　□
				// ================================================
				//会場No取得
				$select =  " SELECT A.syoridt , A.syorino ";
				$select .= " FROM php_t_pc_zaiko A ";
				$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
				$select .= " AND A.makrname = " . sprintf("'%s'", $makrname);
				$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
				$select .= " AND A.tanka = " . sprintf("'%s'", $tanka);
				$select .= " AND A.hanbaiflg = 0";
				$select .= " AND A.delflg = 0";
				$select .= " ORDER BY A.syoridt , A.syorino ";
				$select .= " LIMIT 0 , " . $suryou;
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//				if (! $rs = mysql_query($select, $db)) {
				if (!($rs = $db->query($select))) {
//					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
// ----- 2019.06 ver7.0対応
//				while ($row = @mysql_fetch_array($rs)) {
				while ($row = $rs->fetch_array()) {
					$syoridt = $row['syoridt'];
					$syorino = $row['syorino'];
					// ================================================
					// ■　□　■　□　販売Ｔ登録　■　□　■　□
					// ================================================
					//会場ＮＯ
					$venueno++;
					if (is_null($profit)) {
						$profit = 0;
					}
					//ＳＱＬ文結合
					$insert = $query;
					$insert1 = $query1;
					$insert2 = $query2;
					$insert1 .= "," . $collist["会場ＮＯ"];
					$insert2 .= "," . $venueno;
					$insert1 .= "," . $collist["処理日付"];
					$insert2 .= "," . sprintf("'%s'", $syoridt);
					$insert1 .= "," . $collist["処理ＮＯ"];
					$insert2 .= "," . sprintf("'%s'", $syorino);
					$insert1 .= "," . $collist["利益"];
					$insert2 .= "," . $profit;
					//販売
					if ($hannum > 0) {
						$insert1 .= "," . $collist["販売数量"];
						$insert2 .= "," . 1;
						$hannum--;
					}
					//現物予約
					elseif ($grenum > 0) {
						$insert1 .= "," . $collist["現物予約数量"];
						$insert2 .= "," . 1;
						$grenum--;
					}
					//ＳＱＬ文結合
					$insert1 .= ")";
					$insert2 .= ")";
					$insert .= $insert1 . $insert2;
					$comm->ouputlog("===データ追加ＳＱＬ(mysql_ins_hanbai_i)===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

					//データ追加実行
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($insert, $db)) {
					if (! $db->query($insert)) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);

					//在庫の消込処理
					// ================================================
					// ■　□　■　□　在庫Ｔ削除　■　□　■　□
					// ================================================
					$delete = $delete1;
					$delete .= $delete2 . sprintf("'%s'", $syoridt);
					$delete .= $delete3 . sprintf("'%s'", $syorino);
					$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);

					//データ削除実行
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($delete, $db)) {
					if (! $db->query($delete)) {
//						$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			//見本予約、登録
			}if($mrenum > 0){
				for($t=0; $t<$mrenum; ++$t){
					// ================================================
					// ■　□　■　□　販売Ｔ登録　■　□　■　□
					// ================================================
					//会場ＮＯ
					$venueno++;
					if (is_null($profit)) {
						$profit = 0;
					}
					//ＳＱＬ文結合
					$insert = $query;
					$insert1 = $query1;
					$insert2 = $query2;
					$insert1 .= "," . $collist["会場ＮＯ"];
					$insert2 .= "," . $venueno;
					$insert1 .= "," . $collist["処理日付"];
					$insert2 .= "," . sprintf("'%s'", $syoridt);
					$insert1 .= "," . $collist["処理ＮＯ"];
					$insert2 .= "," . sprintf("'%s'", $syorino);
					$insert1 .= "," . $collist["利益"];
					$insert2 .= "," . $profit;
					$insert1 .= "," . $collist["見本予約数量"];
					$insert2 .= "," . 1;
					//ＳＱＬ文結合
					$insert1 .= ")";
					$insert2 .= ")";
					$insert .= $insert1 . $insert2;
					$comm->ouputlog("===データ追加ＳＱＬ(mysql_ins_hanbai_i)===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

					//データ追加実行
					if (! $db->query($insert)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			$dataCnt++;
			$optionCnt = 0;
			$hannum = 0;
			$grenum = 0;
			$mrenum = 0;
			$name = "";
			$tanka = 0;
			$profit = 0;
			$siire = 0;
		} else {
			//数量チェックされている場合
			if($key == "販売". $dataCnt) {
				$hannum = $val;
			}
			elseif($key == "現物". $dataCnt) {
				$grenum = $val;
			}
			elseif($key == "見本". $dataCnt) {
				$mrenum = $val;
			}
			else {
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if($arrkey[$cnt] . $dataCnt == $key){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if ($length > 0 && strlen($val) > $length) {
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}
					else {
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if($key == "メーカー名". $dataCnt) {
				$makrname = $val;
			}
			elseif($key == "型番". $dataCnt) {
				$modelnum = $val;
			}
			elseif($key == "Ｔ単価". $dataCnt) {
				$tanka = $val;
			}
			elseif($key == "単価". $dataCnt) {
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $val - $siirelist[$modelnum];
				} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
					$_sql =" SELECT A.profit ";
					$_sql .=" FROM php_system A ";
					$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! $rs = mysql_query($_sql, $db)) {
					if (!($rs = $db->query($_sql))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)) {
					while ($row = $rs->fetch_array()) {
						$profit = $row['profit'];
					}
				}else{ //代理店以外
					$profit = $profitlist[$modelnum];
				}
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
// mysql_ins_hanbai_m
//
// ■概要
// PC見本予約登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_hanbai_m( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_ins_hanbai_mログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	$today = date('YmdHis');

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "m_end";
	$arrkey = array("ＣＰＵ","メモリ","対象ＯＳ","単価","仕入単価","分類","型番","見本備考");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query =" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.modelnum , A.siire_d, A.profit ";
	$query .=" FROM php_pc_tanka A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.modelnum";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$profitlist = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['modelnum'];
		$siirelist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;
	//部
	$m_query1 .= "," . $collist["区分"];
	$m_query2 .= "," . "'1'";
	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	$optionCnt = 0;
	$dataCnt = 1;
	$mrenum = 0;
	$modelnum = "";
	$tanka = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if ($end . $dataCnt == $key) {
			$comm->ouputlog("mrenum =" .$mrenum, $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog("desktop =" .$desktop, $prgid, SYS_LOG_TYPE_INFO);
			//数量の入力がある場合、登録
			if ($mrenum > 0) {
				for ($cnt = 0; $cnt < $mrenum; ++$cnt){
					// ================================================
					// ■　□　■　□　販売Ｔ登録　■　□　■　□
					// ================================================
					//販売Ｔ
					//ＳＱＬ文結合
					$insert = $query;
					$insert1 = $query1;
					$insert2 = $query2;
					//会場ＮＯ
					++$venueno;
					$insert1 .= "," . $collist["会場ＮＯ"];
					$insert2 .= "," . $venueno;
					$insert1 .= "," . $collist["見本予約数量"];
					$insert2 .= "," . 1;
					//利益
					$insert1 .= "," . $collist["利益"];
					$insert2 .= "," . $profit;
					//ＳＱＬ文結合
					$insert1 .= ")";
					$insert2 .= ")";
					$insert .= $insert1 . $insert2;
					$comm->ouputlog("===データ追加ＳＱＬ(mysql_ins_hanbai_m)===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

					//データ追加実行
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($insert, $db)) {
					if (! $db->query($insert)) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			++$dataCnt;
			$optionCnt = 0;
			$mrenum = 0;
			$modelnum = "";
			$tanka = 0;
		} else {
			//見本予約チェックされている場合
			if($key == "見本". $dataCnt) {
				$mrenum = $val;
			}
			else {
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); ++$cnt){
					if($arrkey[$cnt] . $dataCnt == $key){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if ($length > 0 && strlen($val) > $length) {
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}
					else {
						$query2 .= sprintf(",'%s'", $val);
					}
				}
				if($key == "見本型番". $dataCnt) {
					$modelnum = $val;
				}
				if($key == "見本単価". $dataCnt) {
					if ($_COOKIE['con_perf_mana'] == 1) { //代理店
						$profit = $val - $siirelist[$modelnum]; //利益計算
					} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
						$_sql =" SELECT A.profit ";
						$_sql .=" FROM php_system A ";
						$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//						if (! $rs = mysql_query($_sql, $db)) {
						if (!($rs = $db->query($_sql))) {
//							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
// ----- 2019.06 ver7.0対応
//						while ($row = @mysql_fetch_array($rs)) {
						while ($row = $rs->fetch_array()) {
							$profit = $row['profit'];
						}
					}else{ //代理店以外
						$profit = $profitlist[$modelnum];
					}
				}
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
// mysql_ins_hanbai_d
//
// ■概要
// デスクトップ販売登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_hanbai_d( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_ins_hanbai_dログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	$today = date('YmdHis');

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "d_end";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query =" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM php_t_pc_hanbai A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.modelnum , A.siire_d, A.profit ";
	$query .=" FROM php_pc_tanka A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.modelnum";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$profitlist = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['modelnum'];
		$siirelist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	$dataCnt = 101;
	$grenum = 0;
	$modelnum = "";
	$tanka = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if ($end . $dataCnt == $key) {
			//数量の入力がある場合、登録
			if ($grenum > 0) {
				//デスクトップPCの場合、現物予約として登録
				for ($cnt = 0; $cnt < $grenum; ++$cnt){
					//PC情報を取得
					$query = " SELECT A.siire";
					$query .= " FROM php_pc_info A";
					$query .= " WHERE A.modelnum = '$modelnum'";
					$query .= " AND A.delflg = 0";
					$query .= " GROUP BY A.modelnum";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! $rs = mysql_query($query, $db)) {
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)) {
					while ($row = $rs->fetch_array()) {
						$siire = $row['siire'];
					}
					++$venueno;
					$query = " INSERT INTO php_t_pc_hanbai (";
					$query .= " insdt, upddt, venueid, section";
					$query .= " , modelnum";
					$query .= " , tanka, siire, venueno, profit, hannum, kbn";
					$query .= " ) VALUES ( ";
					$query .= " '$today','$today','".sprintf("%s", $performance[venueid])."',$g_section";
					$query .= " ,'$modelnum'";
					$query .= " ,'$tanka','$siire','$venueno',$profit,'1','1')";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! $rs = mysql_query($query, $db)) {
					if (!($rs = $db->query($query))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					//データ追加実行
					$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//初期値セット
			++$dataCnt;
			$grenum = 0;
			$modelnum = "";
			$tanka = 0;
		} else {
			//見本予約チェックされている場合
			if($key == "デスクトップ". $dataCnt) {
				$grenum = $val;
			}
			else {
				//テーブル項目情報取得
				if($key == "デスクトップ型番". $dataCnt) {
					$modelnum = $val;
				}
				if($key == "デスクトップ単価". $dataCnt) {
					$tanka = $val;
					if ($_COOKIE['con_perf_mana'] == 1) { //代理店
						$profit = $val - $siirelist[$modelnum]; //利益計算
					} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
						$_sql =" SELECT A.profit ";
						$_sql .=" FROM php_system A ";
						$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//						if (! $rs = mysql_query($_sql, $db)) {
						if (!($rs = $db->query($_sql))) {
//							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
// ----- 2019.06 ver7.0対応
//						while ($row = @mysql_fetch_array($rs)) {
						while ($row = $rs->fetch_array()) {
							$profit = $row['profit'];
						}
					}else{ //代理店以外
						$profit = $profitlist[$modelnum];
					}
				}
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
// mysql_ins_hanbai_b
//
// ■概要
// オプション販売登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_hanbai_b( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_ins_hanbai_bログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "b_end";
	$arrkey = array("型番","数量","単価","仕入単価");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.name , A.siire_d, A.profit ";
	$query .=" FROM php_option_info A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.name";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$profitlist = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$p_name = $row['name'];
		$siirelist[$p_name] = $row['siire_d'];
		$profitlist[$p_name] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;
	//区分
	$m_query1 .= "," . $collist["区分"];
	$m_query2 .= "," . "'2'";
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_option_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	$optionCnt = 0;
	$dataCnt = 201;
	$hannum = 0;
	$grenum = 0;
	$mrenum = 0;
	$name = "";
	$tanka = 0;
	$tanka_b = 0;
	$siire = 0;
	$deleteflg = 0;
	$profit = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if ($end . $dataCnt == $key) {
			//販売・現物予約数合算
			$suryou = $hannum + $grenum;
			//チェックがある場合、登録
			if ($suryou > 0) {

				// ================================================
				// ■　□　■　□　在庫Ｔ検索　■　□　■　□
				// ================================================
				//会場No取得
				$select =  " SELECT A.syoridt , A.syorino ";
				$select .= " FROM php_t_option_zaiko A ";
				$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
				$select .= " AND A.name = " . sprintf("'%s'", $name);
				$select .= " AND A.siire = " . sprintf("'%s'", $siire);
				$select .= " AND A.tanka = " . sprintf("'%s'", $tanka);
				$select .= " AND A.hanbaiflg = 0";
				$select .= " AND A.delflg = 0";
				$select .= " LIMIT 0 , " . $suryou;
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//				if (! $rs = mysql_query($select, $db)) {
				if (!($rs = $db->query($select))) {
//					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
// ----- 2019.06 ver7.0対応
//				while ($row = @mysql_fetch_array($rs)) {
				while ($row = $rs->fetch_array()) {
					$syoridt = $row['syoridt'];
					$syorino = $row['syorino'];
					// ================================================
					// ■　□　■　□　販売Ｔ登録　■　□　■　□
					// ================================================
					//会場ＮＯ
					$venueno++;
					//ＳＱＬ文結合
					$insert = $query;
					$insert1 = $query1;
					$insert2 = $query2;
					$insert1 .= "," . $collist["会場ＮＯ"];
					$insert2 .= "," . $venueno;
					$insert1 .= "," . $collist["処理日付"];
					$insert2 .= "," . sprintf("'%s'", $syoridt);
					$insert1 .= "," . $collist["処理ＮＯ"];
					$insert2 .= "," . sprintf("'%s'", $syorino);
					//利益
					$insert1 .= "," . $collist["利益"];
					$insert2 .= "," . $profit;
					//販売
					if ($hannum > 0) {
						$insert1 .= "," . $collist["販売数量"];
						$insert2 .= "," . 1;
						$hannum--;
					}
					//現物予約
					elseif ($grenum > 0) {
						$insert1 .= "," . $collist["現物予約数量"];
						$insert2 .= "," . 1;
						$grenum--;
					}
					//ＳＱＬ文結合
					$insert1 .= ")";
					$insert2 .= ")";
					$insert .= $insert1 . $insert2;
					$comm->ouputlog("===データ追加ＳＱＬ(mysql_ins_hanbai_b)===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

					//データ追加実行
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($insert, $db)) {
					if (! $db->query($insert)) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);

					//在庫の消込処理
					// ================================================
					// ■　□　■　□　在庫Ｔ削除　■　□　■　□
					// ================================================
					$delete = $delete1;
					$delete .= $delete2 . sprintf("'%s'", $syoridt);
					$delete .= $delete3 . sprintf("'%s'", $syorino);
					$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);

					//データ削除実行
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($delete, $db)) {
					if (! $db->query($delete)) {
//						$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//見本予約
			$suryou = $mrenum;
			//チェックがある場合、登録
			if ($suryou > 0) {
				//数量分追加
				for($i = 0; $i < $suryou; $i++) {
					// ================================================
					// ■　□　■　□　販売Ｔ登録　■　□　■　□
					// ================================================
					//会場ＮＯ
					$venueno++;
					//ＳＱＬ文結合
					$insert = $query;
					$insert1 = $query1;
					$insert2 = $query2;
					$insert1 .= "," . $collist["会場ＮＯ"];
					$insert2 .= "," . $venueno;
					//利益
					$insert1 .= "," . $collist["利益"];
					$insert2 .= "," . $profit;
					//見本予約
					$insert1 .= "," . $collist["見本予約数量"];
					$insert2 .= "," . 1;
					//ＳＱＬ文結合
					$insert1 .= ")";
					$insert2 .= ")";
					$insert .= $insert1 . $insert2;
					$comm->ouputlog("===データ追加ＳＱＬ(mysql_ins_hanbai_b)===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

					//データ追加実行
//					if (! mysql_query($insert, $db)) {
					if (! $db->query($insert)) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			$dataCnt++;
			$optionCnt = 0;
			$hannum = 0;
			$grenum = 0;
			$mrenum = 0;
			$name = "";
			$tanka = 0;
			$siire = 0;
			$profit = 0;
		} else {
			//数量チェックされている場合
			if($key == "数量". $dataCnt) {
				$hannum = $val;
			}
			elseif($key == "現物予約". $dataCnt) {
				$grenum = $val;
			}
			elseif($key == "見本予約". $dataCnt) {
				$mrenum = $val;
			}
			else {
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if($arrkey[$cnt] . $dataCnt == $key){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if ($length > 0 && strlen($val) > $length) {
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}
					else {
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if($key == "型番". $dataCnt) {
				$name = $val;
			}
			elseif($key == "仕入単価". $dataCnt) {
				$siire = $val;
			}
			elseif($key == "Ｔ単価". $dataCnt) {
				$tanka = $val;
			}
			elseif($key == "単価". $dataCnt) {
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $val - $siirelist[$name];
				}else{ //代理店以外
					$profit = $profitlist[$name];
				}
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
// mysql_upd_hanbai
//
// ■概要
// 販売実績更新
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_upd_hanbai( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;

	$comm->ouputlog("mysql_upd_hanbaiログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//初期化
	$dataCnt = 1;
	//画面表示内容
	$hensyu = 0;
	$hannum = 0;
	$grenum = 0;
	$mrenum = 0;
	$thannum = 0;
	$tgrenum = 0;
	$tmrenum = 0;
	$tanka = 0;
	$ttanka = 0;
	$venueno = 0;
	$syoridt = "";
	$syorino = "";
	$section = 0;
	$desktop = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//最終項目の場合
		if (preg_match("/^end/",$key)) {
			//最終項目の場合はデータ追加(ＰＣ)
			if ("end" . $dataCnt == $key && $hensyu == 1) {
				//すべてチェックを外した場合は削除
				if ($hannum == 0 && $grenum == 0 && $mrenum == 0) {
					// ================================================
					// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
					// ================================================
					$_delete = "UPDATE php_t_pc_hanbai SET delflg = 1 ";
					$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
					$_delete .= " ,updcount = updcount + 1";
					$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
					$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
					//データ削除実行
					$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($_delete, $db)) {
					if (! $db->query($_delete)) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
					if($desktop == 0){
						// ================================================
						// ■　□　■　□　データ移送（在庫Ｔ）　■　□　■　□
						// ================================================
						$_update = "UPDATE php_t_pc_zaiko SET hanbaiflg = 0 ";
						$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
						$_update .= " ,updcount = updcount + 1";
						$_update .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
						$_update .= " AND syoridt = " . sprintf("'%s'", $syoridt);
						$_update .= " AND syorino = " . sprintf("'%s'", $syorino);
						//データ移送実行
						$comm->ouputlog("===データ移送ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($_update, $db)) {
						if (! $db->query($_update)) {
//							$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ移送完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
				else {
					// ================================================
					// ■　□　■　□　データ更新（販売Ｔ）　■　□　■　□
					// ================================================
					$_update = "UPDATE php_t_pc_hanbai ";
					$_update .= " SET hannum = " . $hannum;
					$_update .= " ,grenum = " . $grenum;
					$_update .= " ,mrenum = " . $mrenum;
					$_update .= " ,tanka = " . $tanka;
					$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
					$_update .= " ,updcount = updcount + 1";
					$_update .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
					$_update .= " AND venueno = " . sprintf("'%s'", $venueno);
					//データ更新実行
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($_update, $db)) {
					if (! $db->query($_update)) {
//						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//最終項目の場合はデータ追加(備品)
			if ("end_b" . $dataCnt == $key && $hensyu == 1) {
				//フラグ初期化
				$updflg = 0;
				//数量が変更された場合
				$suryou = $hannum + $grenum + $mrenum;
				$tsuryou = $thannum + $tgrenum + $tmrenum;
				$comm->ouputlog("--データ編集--" , $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog("suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog("tsuryou=" . $tsuryou, $prgid, SYS_LOG_TYPE_DBUG);
				//
				if ($suryou <> $tsuryou) {
					//差分算出
					$delnum = $tsuryou - $suryou;
					// ================================================
					// ■　□　■　□　削除（備品販売Ｔ）　■　□　■　□
					// ================================================
					$_update = "UPDATE php_t_pc_hanbai ";
					$_update .= " SET delflg = 1 ";
					$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
					$_update .= " ,updcount = updcount + 1";
					$_update .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
					$_update .= " AND section = " . sprintf("'%s'", $section);
					$_update .= " AND modelnum = " . sprintf("'%s'", $name);
					$_update .= " AND tanka = " . $ttanka;
					$_update .= " AND delflg = 0 ";
					$_update .= " AND henpinflg = 0 ";
					$_update .= " AND kbn='2' ";
					$_update .= " ORDER BY syoridt desc,syorino desc";
					$_update .= " LIMIT " . $delnum;
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
					//データ追加実行
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($_update, $db)) {
					if (! $db->query($_update)) {
//						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
				//在庫の消込は見本以外が対象のため、再度計算
				$suryou = $hannum + $grenum;
				$tsuryou = $thannum + $tgrenum;
				$comm->ouputlog("--在庫消込--" , $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog("suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog("tsuryou=" . $tsuryou, $prgid, SYS_LOG_TYPE_DBUG);
				if ($suryou <> $tsuryou) {
					//差分算出
					$delnum = $tsuryou - $suryou;
					if ($delnum > 0) {
						// ================================================
						// ■　□　■　□　データ移送（在庫Ｔ）　■　□　■　□
						// ================================================
						$_update = "UPDATE php_t_option_zaiko SET hanbaiflg = 0 ";
						$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
						$_update .= " ,updcount = updcount + 1";
						$_update .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
						$_update .= " AND name = " . sprintf("'%s'", $name);
						$_update .= " AND hanbaiflg = 1 ";
						$_update .= " AND delflg = 0 ";
						$_update .= " ORDER BY syoridt desc,syorino desc";
						$_update .= " LIMIT " . $delnum;
						//データ移送実行
						$comm->ouputlog("===データ移送ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($_update, $db)) {
						if (! $db->query($_update)) {
//							$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ移送完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
					//フラグ
					$updflg = 1;
				}
				//販売数量が変更された場合
				if ($hannum <> $thannum) {
					//フラグ
					$updflg = 1;
				}
				//現物予約が変更された場合
				if ($grenum <> $tgrenum) {
					//フラグ
					$updflg = 1;
				}
				//見本予約が変更された場合
				if ($mrenum <> $tmrenum) {
					//フラグ
					$updflg = 1;
				}
				//単価が変更された場合
				if ($tanka != $ttanka) {
					//フラグ
					$updflg = 1;
				}

				//フラグが立っている場合、数量を再度計算する
				if ($updflg == 1) {
					// ================================================
					// ■　□　■　□　データ更新（販売Ｔ）　■　□　■　□
					// ================================================
					$_select = " SELECT A.venueno ";
					$_select .= " FROM php_t_pc_hanbai A ";
					$_select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
					$_select .= " AND section = " . sprintf("'%s'", $section);
					$_select .= " AND modelnum = " . sprintf("'%s'", $name);
					$_select .= " AND tanka = " . $ttanka;
					$_select .= " AND delflg = 0 ";
					$_select .= " AND henpinflg = 0 ";
					$_select .= " AND kbn = '2' ";
					$_select .= " ORDER BY venueno ";
					//データ削除実行
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! $rs = mysql_query($_select, $db)) {
					if (!($rs = $db->query($_select))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					//変数初期化
					$hancnt = 0;
					$grecnt = 0;
					$mrecnt = 0;
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)) {
					while ($row = $rs->fetch_array()) {
						//販売数量更新
						if ($hancnt < $hannum) {
							$_update = "UPDATE php_t_pc_hanbai ";
							$_update .= " SET hannum = 1 ";
							$_update .= " , grenum = 0 ";
							$_update .= " , mrenum = 0 ";
							$_update .= " , tanka = " . $tanka;
							$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
							$_update .= " ,updcount = updcount + 1";
							$_update .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
							$_update .= " AND venueno = " . sprintf("'%s'", $row['venueno']);
							//データ更新実行
							$comm->ouputlog("===データ更新ＳＱＬ（販売数量更新）===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//							if (! mysql_query($_update, $db)) {
							if (! $db->query($_update)) {
//								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ更新完了（販売数量更新）===", $prgid, SYS_LOG_TYPE_DBUG);
							$hancnt++;
						}
						//現物予約更新
						elseif ($grecnt < $grenum) {
							$_update = "UPDATE php_t_pc_hanbai ";
							$_update .= " SET hannum = 0 ";
							$_update .= " , grenum = 1 ";
							$_update .= " , mrenum = 0 ";
							$_update .= " , tanka = " . $tanka;
							$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
							$_update .= " ,updcount = updcount + 1";
							$_update .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
							$_update .= " AND venueno = " . sprintf("'%s'", $row['venueno']);
							//データ更新実行
							$comm->ouputlog("===データ更新ＳＱＬ（現物予約更新）===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//							if (! mysql_query($_update, $db)) {
							if (! $db->query($_update)) {
//								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ更新完了（現物予約更新）===", $prgid, SYS_LOG_TYPE_DBUG);
							$grecnt++;
						}
						//見本予約更新
						elseif ($mrecnt < $mrenum) {
							$_update = "UPDATE php_t_pc_hanbai ";
							$_update .= " SET hannum = 0 ";
							$_update .= " , grenum = 0 ";
							$_update .= " , mrenum = 1 ";
							$_update .= " , tanka = " . $tanka;
							$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
							$_update .= " ,updcount = updcount + 1";
							$_update .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
							$_update .= " AND venueno = " . sprintf("'%s'", $row['venueno']);
							//データ更新実行
							$comm->ouputlog("===データ更新ＳＱＬ（見本予約更新）===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//							if (! mysql_query($_update, $db)) {
							if (! $db->query($_update)) {
//								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
								$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ更新完了（見本予約更新）===", $prgid, SYS_LOG_TYPE_DBUG);
							$mrecnt++;
						}
					}
				}
			}
			//最終項目の場合はデータ追加(見本)
			if ("end_m" . $dataCnt == $key && $hensyu == 1) {
				//すべてチェックを外した場合は削除
				if ($mrenum == 0) {
					// ================================================
					// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
					// ================================================
					$_delete = "UPDATE php_t_pc_hanbai SET delflg = 1 ";
					$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
					$_delete .= " ,updcount = updcount + 1";
					$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
					$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
					//データ削除実行
					$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($_delete, $db)) {
					if (! $db->query($_delete)) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
				else {
					// ================================================
					// ■　□　■　□　データ更新（販売Ｔ）　■　□　■　□
					// ================================================
					$_update = "UPDATE php_t_pc_hanbai ";
					$_update .= " SET tanka = " . $tanka;
					$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
					$_update .= " ,updcount = updcount + 1";
					$_update .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
					$_update .= " AND venueno = " . sprintf("'%s'", $venueno);
					//データ更新実行
					$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! mysql_query($_update, $db)) {
					if (! $db->query($_update)) {
//						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
				}
			}
			//初期化
			$dataCnt++;
			//画面表示内容
			$hensyu = 0;
			$hannum = 0;
			$grenum = 0;
			$mrenum = 0;
			$thannum = 0;
			$tgrenum = 0;
			$tmrenum = 0;
			$name = "";
			$tanka = 0;
			$ttanka = 0;
			$venueno = 0;
			$syoridt = "";
			$syorino = "";
			$section = 0;
			$desktop = 0;
		} else {
			//販売情報
			if($key == "販売". $dataCnt || $key == "備品販売". $dataCnt) {
				$hannum = $val;
			}
			elseif($key == "Ｔ販売". $dataCnt || $key == "Ｔ備品販売". $dataCnt) {
				$thannum = $val;
			}
			//現物予約情報
			elseif($key == "現物". $dataCnt || $key == "備品現物". $dataCnt) {
				$grenum = $val;
			}
			elseif($key == "Ｔ現物". $dataCnt || $key == "Ｔ備品現物". $dataCnt) {
				$tgrenum = $val;
			}
			//見本予約情報
			elseif($key == "見本". $dataCnt || $key == "備品見本". $dataCnt) {
				$mrenum = $val;
			}
			elseif($key == "Ｔ見本". $dataCnt || $key == "Ｔ備品見本". $dataCnt) {
				$tmrenum = $val;
			}
			//単価情報
			elseif($key == "備品品名". $dataCnt) {
				$name = $val;
			}
			//単価情報
			elseif($key == "単価". $dataCnt || $key == "備品単価". $dataCnt || $key == "見本単価". $dataCnt) {
				$tanka = $val;
			}
			elseif($key == "Ｔ単価". $dataCnt || $key == "Ｔ備品単価". $dataCnt || $key == "Ｔ見本単価". $dataCnt) {
				$ttanka = $val;
			}
			//会場ＮＯ情報
			elseif($key == "会場ＮＯ". $dataCnt || $key == "見本会場ＮＯ". $dataCnt) {
				$venueno = $val;
			}
			//処理日付情報
			elseif($key == "処理日付". $dataCnt) {
				$syoridt = $val;
			}
			//処理ＮＯ情報
			elseif($key == "処理ＮＯ". $dataCnt) {
				$syorino = $val;
			}
			//デスクトップ情報
			elseif($key == "デスクトップ". $dataCnt) {
				$desktop = $val;
			}
			//部情報
			elseif($key == "備品部". $dataCnt) {
				$section = $val;
			}
			//編集情報
			elseif($key == "編集". $dataCnt || $key == "備品編集". $dataCnt || $key == "見本編集". $dataCnt) {
				$hensyu = $val;
			}
			//合計項目の場合、初期化する
			elseif($key == "PC合計" || $key == "周辺機器合計" || $key == "見本合計") {
				$dataCnt = 1;
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
// mysql_ins_returned
//
// ■概要
// 個人情報新規登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_returned( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;

	$comm->ouputlog("mysql_ins_returnedログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$table = "";
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query = "";
	$query .=" SELECT IFNULL(max(A.venueno),0) as venueno ";
	$query .=" FROM php_t_pc_returned A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}
	//会場No取得
	$query = "";
	$query .=" SELECT IFNULL(max(A.venueno),0) as venueno ";
	$query .=" FROM php_t_option_returned A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno_b = $row['venueno'];
	}

	//初期化
	$dataCnt = 1;
	$hannum = 0;
	$tvenueid = "";
	$name = "";
	$tanka = 0;

	$insdt = date('YmdHis');

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if ("end" . $dataCnt == $key || "end_b" . $dataCnt == $key) {
			$comm->ouputlog("ENDデータ判定", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog("返品数：" . $hannum, $prgid, SYS_LOG_TYPE_INFO);
			//ＰＣ返品登録
			if (($hannum > 0 || $p_hannum > 0) && "end" . $dataCnt == $key) {
				for ($i=0;$i<$hannum;$i++) {
					//会場ＮＯカウントアップ
					$venueno++;
					// ================================================
					// ■　□　■　□　返品Ｔ登録　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" INSERT INTO php_t_pc_returned ";
					$query .=" SELECT ";
					$query .= sprintf("'%s'", $insdt);
					$query .= sprintf(",'%s'", $insdt);
					$query .= ",1";
					$query .= sprintf(",'%s'", $performance[venueid]);
					$query .= "," . $venueno;
					$query .= sprintf(",'%s'", $tvenueid);
					$query .= ",venueno";
					$query .= ",makrname";
					$query .= ",modelnum";
					$query .= ",cpu";
					$query .= ",memory";
					$query .= ",hdd";
					$query .= ",drive";
					$query .= ",os";
					$query .= ",category"; //移動(lanの下にある)
					$query .= ",lan";
					$query .= ",b_code";
					$query .= ",receiptno";
					$query .= ",hannum";
					$query .= ",siire";
					$query .= ",tanka";
					$query .= ",profit";
					$query .= ",0";
					$query .= ",0";
					$query .= " FROM php_t_pc_hanbai";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $modelnum);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
					$query .=" AND henpinflg = 0";
					$query .=" AND hannum > 0";
					$query .=" AND kbn  in ('1','5') ";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 0, 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					// ================================================
					// ■　□　■　□　販売Ｔ更新　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" UPDATE php_t_pc_hanbai ";
					$query .=" SET henpinflg = " . sprintf("'%s'", $performance[venueid]);
					$query .=" ,upddt = " . sprintf("'%s'", $insdt);
					$query .=" ,updcount = updcount + 1";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $modelnum);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
					$query .=" AND henpinflg = 0";
					$query .=" AND hannum > 0";
					$query .=" AND kbn in ('1','5') ";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
				}
				for ($i=0;$i<$p_hannum;$i++) {
					//会場ＮＯカウントアップ
					$venueno++;
					// ================================================
					// ■　□　■　□　返品Ｔ登録　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" INSERT INTO php_t_pc_returned ";
					$query .=" SELECT ";
					$query .= sprintf("'%s'", $insdt);
					$query .= sprintf(",'%s'", $insdt);
					$query .= ",1";
					$query .= sprintf(",'%s'", $performance[venueid]);
					$query .= "," . $venueno;
					$query .= sprintf(",'%s'", $tvenueid);
					$query .= ",venueno";
					$query .= ",makrname";
					$query .= ",modelnum";
					$query .= ",cpu";
					$query .= ",memory";
					$query .= ",hdd";
					$query .= ",drive";
					$query .= ",os";
					$query .= ",category"; //移動(lanの下にある)
					$query .= ",lan";
					$query .= ",b_code";
					$query .= ",receiptno";
					$query .= ",hannum";
					$query .= ",siire";
					$query .= ",tanka";
					$query .= ",profit";
					$query .= ",0";
					$query .= ",0";
					$query .= " FROM php_t_pc_hanbai";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $modelnum);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
					$query .=" AND henpinflg = 0";
					$query .=" AND hannum > 0";
					$query .=" AND kbn in ('1','5') ";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 0, 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					// ================================================
					// ■　□　■　□　販売Ｔ更新　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" UPDATE php_t_pc_hanbai ";
					$query .=" SET henpinflg = " . sprintf("'%s'", $performance[venueid]);
					$query .=" ,upddt = " . sprintf("'%s'", $insdt);
					$query .=" ,updcount = updcount + 1";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $modelnum);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
					$query .=" AND henpinflg = 0";
					$query .=" AND hannum > 0";
					$query .=" AND kbn  in ('1','5') ";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
				}
			}
			//備品返品登録
			elseif (($hannum > 0 || $p_hannum > 0) && "end_b" . $dataCnt == $key) {
				for ($i=0;$i<$hannum;$i++) {
					$venueno_b++;
					// ================================================
					// ■　□　■　□　返品Ｔ登録　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" INSERT INTO php_t_option_returned ";
					$query .=" SELECT ";
					$query .= sprintf("'%s'", $insdt);
					$query .= sprintf(",'%s'", $insdt);
					$query .= ",1";
					$query .= sprintf(",'%s'", $performance[venueid]);
					$query .= "," . $venueno_b;
					$query .= sprintf(",'%s'", $tvenueid);
					$query .= ",venueno";
					$query .= ",modelnum";
					$query .= ",hannum";
					$query .= ",siire";
					$query .= ",tanka";
					$query .= ",0";
					$query .= ",0";
					$query .= ",b_code";
					$query .= ",receiptno";
					$query .= ",''";
					$query .= " FROM php_t_pc_hanbai";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $name);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
				//	$query .=" AND kbn = '2'";
					$query .=" AND henpinflg = 0";
					$query .=" AND hannum > 0";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 0, 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					// ================================================
					// ■　□　■　□　販売Ｔ更新　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" UPDATE php_t_pc_hanbai ";
					$query .=" SET henpinflg = " . sprintf("'%s'", $performance[venueid]);
					$query .=" ,upddt = " . sprintf("'%s'", $insdt);
					$query .=" ,updcount = updcount + 1";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $name);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
					$query .=" AND henpinflg = 0";
				//	$query .=" AND kbn = '2'";
					$query .=" AND hannum > 0";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
				}
				for ($i=0;$i<$p_hannum;$i++) {
					$venueno_b++;
					// ================================================
					// ■　□　■　□　返品Ｔ登録　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" INSERT INTO php_t_option_returned ";
					$query .=" SELECT ";
					$query .= sprintf("'%s'", $insdt);
					$query .= sprintf(",'%s'", $insdt);
					$query .= ",1";
					$query .= sprintf(",'%s'", $performance[venueid]);
					$query .= "," . $venueno_b;
					$query .= sprintf(",'%s'", $tvenueid);
					$query .= ",venueno";
					$query .= ",modelnum";
					$query .= ",hannum";
					$query .= ",siire";
					$query .= ",tanka";
					$query .= ",0";
					$query .= ",0";
					$query .= ",b_code";
					$query .= ",receiptno";
					$query .= ",''";
					$query .= " FROM php_t_pc_hanbai";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $name);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
				//	$query .=" AND kbn = '2'";
					$query .=" AND henpinflg = 0";
					$query .=" AND hannum > 0";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 0, 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					// ================================================
					// ■　□　■　□　販売Ｔ更新　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" UPDATE php_t_pc_hanbai ";
					$query .=" SET henpinflg = " . sprintf("'%s'", $performance[venueid]);
					$query .=" ,upddt = " . sprintf("'%s'", $insdt);
					$query .=" ,updcount = updcount + 1";
					$query .=" WHERE venueid = " . sprintf("'%s'", $tvenueid);
					$query .=" AND modelnum = " . sprintf("'%s'", $name);
					$query .=" AND tanka = " . sprintf("'%s'", $tanka);
					$query .=" AND delflg = 0";
					$query .=" AND henpinflg = 0";
				//	$query .=" AND kbn = '2'";
					$query .=" AND hannum > 0";
					$query .=" ORDER BY venueno ";
					$query .=" LIMIT 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
				}
			}
			//初期値セット
			$dataCnt++;
			$optionCnt = 0;
			$hannum = 0;
			$tvenueid = "";
			$name = "";
			$tanka = 0;
		} else {
			//販売数量チェックされている場合
			if($key == "返品". $dataCnt || $key == "備品返品". $dataCnt) {
				$hannum = $val;
			}else if($key == "返品_p". $dataCnt || $key == "備品返品_p". $dataCnt) {
				$p_hannum = $val;
			}
			elseif($key == "会場ＩＤ". $dataCnt || $key == "備品会場ＩＤ". $dataCnt) {
				$tvenueid = $val;
			}
			elseif($key == "備品品名". $dataCnt) {
				$name = $val;
			}
			elseif($key == "型番". $dataCnt) {
				$modelnum = $val;
			}
			elseif($key == "単価". $dataCnt || $key == "備品単価". $dataCnt) {
				$tanka = $val;
			}
			//合計項目の場合、初期化する
			elseif($key == "PC合計" || $key == "周辺機器合計") {
				$dataCnt = 1;
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
// mysql_cancel_p_returned
//
// ■概要
// 個人情報新規登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_cancel_p_returned( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象プログラム
	global $prgid;
	//引数
	global $g_venueid;
	global $g_venueno;
	$comm->ouputlog("mysql_cancel_p_returnedログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	// ================================================
	// ■　□　■　□　退避情報取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.tvenueid, A.tvenueno ";
	$query .=" FROM php_t_pc_returned A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$query .=" AND A.venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ検索 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ検索エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ検索エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$tvenueid = $row['tvenueid'];
		$tvenueno = $row['tvenueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ返品削除　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" DELETE FROM php_t_pc_returned ";
	$query .=" WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$query .=" AND venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ削除 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	// ================================================
	// ■　□　■　□　ＰＣ販売　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" UPDATE php_t_pc_hanbai A ";
	$query .=" SET A.henpinflg = '0' ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $tvenueid);
	$query .=" AND A.venueno = " . sprintf("'%s'", $tvenueno);
	$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_cancel_o_returned
//
// ■概要
// 個人情報新規登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_cancel_o_returned( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象プログラム
	global $prgid;
	//引数
	global $g_venueid;
	global $g_venueno;
	$comm->ouputlog("mysql_cancel_o_returnedログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	// ================================================
	// ■　□　■　□　退避情報取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.tvenueid, A.tvenueno ";
	$query .=" FROM php_t_option_returned A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$query .=" AND A.venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ検索 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ検索エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ検索エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$tvenueid = $row['tvenueid'];
		$tvenueno = $row['tvenueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ返品削除　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" DELETE FROM php_t_option_returned ";
	$query .=" WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$query .=" AND venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ削除 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	// ================================================
	// ■　□　■　□　ＰＣ販売　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" UPDATE php_t_pc_hanbai A ";
	$query .=" SET A.henpinflg = '0' ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $tvenueid);
	$query .=" AND A.venueno = " . sprintf("'%s'", $tvenueno);
	$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! mysql_query($query, $db)) {
	if (! $db->query($query)) {
//		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return true;
}


//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_entry_hanbai
//
// ■概要
// 販売実績登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_entry_hanbai( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_entry_hanbaiログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "end";
	$arrkey = array("メーカー名","型番","ＣＰＵ","メモリ","対象ＯＳ","仕入単価","分類");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.modelnum , A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_pc_tanka A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.modelnum";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['modelnum'];
		$siirelist[$p_modelnum] = $row['siire'];
		$siiredlist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_pc_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	$dataCnt = 1;
	$hannum = 0;
	$grenum = 0;
	$mrenum = 0;
	$rentnum = 0;
	$rentmrenum = 0;
	$rentgrenum = 0;
	$makrname = "";
	$modelnum = "";
	$cpu = "";
	$memory = "";
	$tanka = 0;
	$profit = 0;
	$siire = 0;
	$deleteflg = 0;
	$desktop = 0;
	$p_flg = 0;
	$t_p_flg = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match("/^end/",$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" tanka=" . $tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_tanka=" . $t_tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" hannum=" . $hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_hannum=" . $t_hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" grenum=" . $grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_grenum=" . $t_grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mrenum=" . $mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_mrenum=" . $t_mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" rentnum=" . $rentnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_rentnum=" . $t_rentnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" rentgrenum=" . $rentgrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_rentgrenum=" . $t_rentgrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" rentmrenum=" . $rentmrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_rentmrenum=" . $t_rentmrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" remarks=" . $remarks, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_remarks=" . $t_remarks, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" p_flg=" . $p_flg, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_p_flg=" . $t_p_flg, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			//対象がPCの場合のみfunctionを実行
			if($kbn == '1' || $kbn == '5'){ //販売または下取
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $tanka - $siirelist[$modelnum];
				} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
					$_sql =" SELECT A.profit ";
					$_sql .=" FROM php_system A ";
					$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//					if (! $rs = mysql_query($_sql, $db)) {
					if (!($rs = $db->query($_sql))) {
//						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
// ----- 2019.06 ver7.0対応
//					while ($row = @mysql_fetch_array($rs)) {
					while ($row = $rs->fetch_array()) {
						$profit = $row['profit'];
					}
				}else{ //代理店以外
					$profit = $profitlist[$modelnum];
				}
				//仕入単価
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$siire = $siiredlist[$modelnum];
				}else{ //代理店以外
					$siire = $siirelist[$modelnum];
				}
				//販売・現物予約数合算
				$suryou = 0;
				//数計算
				$hannum -= $t_hannum;
				$grenum -= $t_grenum;
				$mrenum -= $t_mrenum;
				$rentnum -= $t_rentnum;
				$rentmrenum -= $t_rentmrenum;
				$rentgrenum -= $t_rentgrenum;
				if ($hannum < 0) {
					//販売削除
					mysql_del_hanbai($db, $g_section, $modelnum, $t_tanka, $hannum * -1, "hannum", $desktop, $t_remarks, $kbn, $t_p_flg, $p_flg);
					//変数初期化
					$hannum = 0;
				}
				if ($grenum < 0) {
					//販売削除
					mysql_del_hanbai($db, $g_section, $modelnum, $t_tanka, $grenum * -1, "grenum", $desktop, $t_remarks, $kbn, $t_p_flg, $p_flg);
					//変数初期化
					$grenum = 0;
				}
				if ($mrenum < 0) {
					//販売削除
					mysql_del_hanbai($db, $g_section, $modelnum, $t_tanka, $mrenum * -1, "mrenum", $desktop, $t_remarks, $kbn, $t_p_flg, $p_flg);
					//変数初期化
					$mrenum = 0;
				}
				if ($rentnum < 0) {
					//販売削除
					mysql_del_hanbai($db, $g_section, $modelnum, $t_tanka, $rentnum * -1, "hannum", $desktop, $t_remarks, $kbn+3, $t_p_flg, $p_flg);
					//変数初期化
					$rentnum = 0;
				}
				if ($rentgrenum < 0) {
					//販売削除
					mysql_del_hanbai($db, $g_section, $modelnum, $t_tanka, $rentgrenum * -1, "grenum", $desktop, $t_remarks, $kbn+3, $t_p_flg, $p_flg);
					//変数初期化
					$rentgrenum = 0;
				}
				if ($rentmrenum < 0) {
					//販売削除
					mysql_del_hanbai($db, $g_section, $modelnum, $t_tanka, $rentmrenum * -1, "mrenum", $desktop, $t_remarks, $kbn+3, $t_p_flg, $p_flg);
					//変数初期化
					$rentmrenum = 0;
				}
				$suryou = $hannum + $grenum + $mrenum + $rentnum + $rentgrenum + $rentmrenum;
				//単価が異なる場合、単価更新
				if ($tanka <> $t_tanka) {
					//単価更新
					mysql_entry_change($db, $g_section, $modelnum, $t_tanka, $tanka, $t_remarks, $remarks, $t_p_flg, $p_flg,$profit, "tanka", $kbn);
				}
				//備考が異なる場合、備考更新
				if ($remarks <> $t_remarks) {
					//単価更新
					mysql_entry_change($db, $g_section, $modelnum, $t_tanka, $tanka, $t_remarks, $remarks, $t_p_flg, $p_flg,$profit, "remarks", $kbn);
				}
				//支払い方法が異なる場合、支払い方法更新
				if ($p_flg <> $t_p_flg) {
					//単価更新
					mysql_entry_change($db, $g_section, $modelnum, $t_tanka, $tanka, $t_remarks, $remarks, $t_p_flg, $p_flg,$profit, "p_flg", $kbn);
				}
				$suryou = $hannum + $grenum + $rentnum + $rentgrenum;
				$m_suryou = $mrenum + $rentmrenum;
				//チェックがある場合、登録
				if ($suryou > 0 && $tanka > 0) {
					//通常の販売・現物
				//	if($desktop == 0){
						// ================================================
						// ■　□　■　□　在庫Ｔ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select =  " SELECT A.syoridt , A.syorino ";
						$select .= " FROM php_t_pc_zaiko A ";
						$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
						$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select .= " AND A.hanbaiflg = 0";
						$select .= " AND A.delflg = 0";
						$select .= " ORDER BY A.syoridt , A.syorino ";
						$select .= " LIMIT 0 , " . $suryou;
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//						if (! $rs = mysql_query($select, $db)) {
						if (!($rs = $db->query($select))) {
//							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
// ----- 2019.06 ver7.0対応
//						while ($row = @mysql_fetch_array($rs)) {
						while ($row = $rs->fetch_array()) {
							$syoridt = $row['syoridt'];
							$syorino = $row['syorino'];
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", $syoridt);
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", $syorino);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . $profit;
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["仕入単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["見本備考"];
							$insert2 .= "," . sprintf("'%s'", $remarks);
							//販売
							if ($hannum > 0) {
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . $kbn;
								$insert1 .= "," . $collist["paypayフラグ"];
								$insert2 .= "," . $p_flg;
							}
							//現物予約
							elseif ($grenum > 0) {
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . $kbn;
							}
							//レンタル
							elseif ($rentnum > 0) {
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . 4;
							}
							//レンタル現物予約
							elseif ($rentgrenum > 0) {
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . 4;
							}
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ(現物)===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);

							//在庫の消込処理
							// ================================================
							// ■　□　■　□　在庫Ｔ削除　■　□　■　□
							// ================================================
							$delete = $delete1;
							$delete .= $delete2 . sprintf("'%s'", $syoridt);
							$delete .= $delete3 . sprintf("'%s'", $syorino);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete)) {
								$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
							//販売
							if ($hannum > 0) {
								--$hannum;
							}
							//現物予約
							elseif ($grenum > 0) {
								--$grenum;
							}
							//レンタル販売
							elseif ($rentnum > 0) {
								--$rentnum;
							}
							//レンタル現物予約
							elseif ($rentgrenum > 0) {
								--$rentgrenum;
							}
						}
					//デスクトップの場合
				/*	}else{
						for($i=0; $i<$suryou; ++$i){
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . $profit;
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["仕入単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["見本備考"];
							$insert2 .= "," . sprintf("'%s'", $remarks);
							//販売
							if ($hannum > 0) {
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . 1;
								--$grenum;
							}
							//現物予約
							elseif ($grenum > 0) {
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . 1;
								--$hannum;
							}
							//見本予約
							elseif ($mrenum > 0) {
								$insert1 .= "," . $collist["見本予約数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . 1;
								--$mrenum;
							}
							//レンタル
							elseif ($rentnum > 0) {
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . 4;
								--$rentnum;
							}
							//レンタル予約
							elseif ($rentmrenum > 0) {
								$insert1 .= "," . $collist["見本予約数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . 4;
								--$rentmrenum;
							}
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
// ----- 2019.06 ver7.0対応
//							if (! mysql_query($insert, $db)) {
							if (! $db->query($insert)) {
//								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					} */
				}
				//見本予約の場合
				if($mrenum > 0 && $tanka > 0){
					for($i=0; $i<$mrenum; ++$i){
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $remarks);
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . $kbn;
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ(見本)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($insert, $db)) {
						if (! $db->query($insert)) {
//							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}if($rentmrenum > 0){
					for($i=0; $i<$rentmrenum; ++$i){
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $remarks);
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . 4;
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ(レンタル)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
// ----- 2019.06 ver7.0対応
//						if (! mysql_query($insert, $db)) {
						if (! $db->query($insert)) {
//							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			$dataCnt++;
			$hannum = 0;
			$grenum = 0;
			$mrenum = 0;
			$rentnum = 0;
			$rentgrenum = 0;
			$rentmrenum = 0;
			$tanka = 0;
			$t_tanka = 0;
			$remarks = 0;
			$t_remarks = 0;
			$tanka2 = 0;
			$t_tanka2 = 0;
			$profit = 0;
			$siire = 0;
			$desktop = 0;
			$p_flg = 0;
			$t_p_flg = 0;
			$kbn = "";
		}else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}elseif(preg_match("/^レンタル/",$key)){
				$rentnum = $val;
			}elseif(preg_match("/^貸出現物/",$key)){
				$rentgrenum = $val;
			}elseif(preg_match("/^貸出予約/",$key)){
				$rentmrenum = $val;
			}else{
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if(preg_match("/^$arrkey[$cnt]/",$key)){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if ($length > 0 && strlen($val) > $length) {
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}
					else {
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if(preg_match("/^型番/",$key)){
				$modelnum = $val;
			}
			//処理日付情報
			elseif(preg_match("/^処理日付/",$key)){
				$syoridt = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^処理ＮＯ/",$key)){
				$syorino = $val;
			}
			elseif(preg_match("/^Ｔ単価/",$key)){
				$t_tanka = $val;
			}
			elseif(preg_match("/^単価/",$key)){
				$tanka = $val;
			}
			elseif(preg_match("/^Ｔ販売/",$key)){
				$t_hannum = $val;
			}
			elseif(preg_match("/^Ｔ現物/",$key)){
				$t_grenum = $val;
			}
			elseif(preg_match("/^Ｔ見本/",$key)){
				$t_mrenum = $val;
			}
			elseif(preg_match("/^Ｔレンタル/",$key)){
				$t_rentnum = $val;
			}
			elseif(preg_match("/^Ｔ貸出現物/",$key)){
				$t_rentgrenum = $val;
			}
			elseif(preg_match("/^Ｔ貸出予約/",$key)){
				$t_rentmrenum = $val;
			}
			elseif(preg_match("/^Ｔ備考/",$key)){
				$t_remarks = $val;
			}
			elseif(preg_match("/^備考/",$key)){
				$remarks = $val;
			}
			elseif(preg_match("/^デスクトップ/",$key)){
				$desktop = $val;
			}
			elseif(preg_match("/^paypayフラグ/",$key)){
				$p_flg = $val;
			}
			elseif(preg_match("/^Ｔpaypayフラグ/",$key)){
				$t_p_flg = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
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
// mysql_del_hanbai
//
// ■概要
// 販売情報消込
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_hanbai( $db, $g_section, $modelnum, $tanka, $num, $numflg, $desktop, $t_remarks, $kbn, $t_p_flg, $p_flg) {

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

	$comm->ouputlog("mysql_del_hanbaiログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("kbn=".$kbn, $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
	$select .= " AND A.tanka = " . sprintf("'%s'", $tanka);
	$select .= " AND A.remarks = " . sprintf("'%s'", $t_remarks);
	$select .= " AND A.henpinflg = 0";
	$select .= " AND A.telhenflg = 0";
	$select .= " AND A.delflg = 0";
	$select .= " AND A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " AND A.p_flg = " . sprintf("'%s'", $t_p_flg);
	$select .= " AND A." . $numflg . " > 0";
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$select .= " LIMIT 0 , " . $num;
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($select, $db)) {
	if (!($rs = $db->query($select))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai SET delflg = 1 ";
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ削除実行
		$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//		if (! mysql_query($_delete, $db)) {
		if (! $db->query($_delete)) {
//			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
		//販売・現物予約の場合は在庫の消込をする
		if(($numflg =='hannum' || $numflg =='grenum')){
	//	if(($numflg =='hannum' || $numflg =='grenum') && $desktop == 0){
			// ================================================
			// ■　□　■　□　データ移送（在庫Ｔ）　■　□　■　□
			// ================================================
			$_update = "UPDATE php_t_pc_zaiko SET hanbaiflg = 0 ";
			$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
			$_update .= " ,updcount = updcount + 1";
			$_update .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
			$_update .= " AND syoridt = " . sprintf("'%s'", $syoridt);
			$_update .= " AND syorino = " . sprintf("'%s'", $syorino);
			//データ移送実行
			$comm->ouputlog("===データ移送ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//			if (! mysql_query($_update, $db)) {
			if (! $db->query($_update)) {
//				$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
				$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
		}
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_entry_change
//
// ■概要
// 単価変更/オプション変更
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_entry_change( $db, $g_section, $modelnum, $t_tanka, $tanka, $t_remarks, $remarks, $t_p_flg, $p_flg, $profit, $column, $kbn) {

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

	$comm->ouputlog("mysql_entry_changeログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("kbn=".$kbn, $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
	$select .= " AND A.tanka = " . sprintf("'%s'", $t_tanka);
	$select .= " AND A.remarks = " . sprintf("'%s'", $t_remarks);
	$select .= " AND A.p_flg = " . sprintf("'%s'", $t_p_flg);
	if($column <> "remarks"){
		$select .= " AND A.henpinflg = 0";
		$select .= " AND A.telhenflg = 0";
	}
	$select .= " AND A.delflg = 0";
	$select .= " AND (A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " OR A.kbn = " . sprintf("'%s'", $kbn + 3).")";
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($select, $db)) {
	if (!($rs = $db->query($select))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai SET ".$column." = " . sprintf("'%s'", ${$column});
		if($column == 'tanka'){
			$_delete .= " ,profit = " . sprintf("'%s'", $profit);
		}
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ削除実行
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//		if (! mysql_query($_delete, $db)) {
		if (! $db->query($_delete)) {
//			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_entry_option
//
// ■概要
// オプション販売実績登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_entry_option( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_entry_optionログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "end";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　オプション単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.name , A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_option_info A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.name";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$p_name = $row['name'];
		$siirelist[$p_name] = $row['siire'];
		$siiredlist[$p_name] = $row['siire_d'];
		$profitlist[$p_name] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_option_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	$optionCnt = 0;
	$dataCnt = 1;
	$hannum = 0;
	$grenum = 0;
	$mrenum = 0;
	$makrname = "";
	$name = "";
	$cpu = "";
	$memory = "";
	$option_tanka = 0;
	$siire = 0;
	$deleteflg = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match("/^end/",$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" option_tanka=" . $option_tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_option_tanka=" . $t_option_tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" hannum=" . $hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_hannum=" . $t_hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" grenum=" . $grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_grenum=" . $t_grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mrenum=" . $mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_mrenum=" . $t_mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" name=" . $name, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_name=" . $t_name, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" p_flg=" . $p_flg, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_p_flg=" . $t_p_flg, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			//利益
			if ($_COOKIE['con_perf_mana'] == 1) { //代理店
				$profit = $option_tanka - $siirelist[$name];
			}else{ //代理店以外
				$profit = $profitlist[$name];
			}
			//仕入単価
			if ($_COOKIE['con_perf_mana'] == 1) { //代理店
				$siire = $siiredlist[$name];
			}else{ //代理店以外
				$siire = $siirelist[$name];
			}
			//対象がオプションの場合、もしくはオプションが登録されている場合のみfunctionを実行
			if($kbn == 2 || $kbn == 3 || $kbn == 6){
				//販売・現物予約数合算
				$suryou = 0;
				//数計算
				$hannum -= $t_hannum;
				$grenum -= $t_grenum;
				$mrenum -= $t_mrenum;
				if ($hannum < 0) {
					//在庫消込
					mysql_del_option($db, $g_section, $name, $t_option_tanka, $hannum * -1, "hannum", $kbn, $t_p_flg, $p_flg);
					//変数初期化
					$hannum = 0;
				}
				if ($grenum < 0) {
					//在庫消込
					mysql_del_option($db, $g_section, $name, $t_option_tanka, $grenum * -1, "grenum", $kbn, $t_p_flg, $p_flg);
					//変数初期化
					$grenum = 0;
				}
				if ($mrenum < 0) {
					//在庫消込
					mysql_del_option($db, $g_section, $name, $t_option_tanka, $mrenum * -1, "mrenum", $kbn, $t_p_flg, $p_flg);
					//変数初期化
					$mrenum = 0;
				}
				//単価が異なる場合、単価更新
				if ($option_tanka <> $t_option_tanka) {
					//単価更新
					mysql_change_option($db, $g_section, $name, $t_option_tanka, $option_tanka, $profit, $kbn, $t_p_flg, $p_flg);
				}
				//支払い方法が異なる場合、支払い方法更新
				if ($p_flg <> $t_p_flg) {
					//単価更新
					mysql_change_option($db, $g_section, $name, $t_option_tanka, $option_tanka, $profit, $kbn, $t_p_flg, $p_flg);
				}
				$suryou = $hannum + $grenum;
				$comm->ouputlog(" suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
				//販売・現物予約の場合
				if($hannum>0 || $grenum>0){
					//備品の場合
					if($kbn <> "3"){
						// ================================================
						// ■　□　■　□　在庫Ｔ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select =  " SELECT A.syoridt , A.syorino ";
						$select .= " FROM php_t_option_zaiko A ";
						$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
						$select .= " AND A.name = " . sprintf("'%s'", $name);
						$select .= " AND A.hanbaiflg = 0";
						$select .= " AND A.delflg = 0";
						$select .= " ORDER BY A.syoridt , A.syorino ";
						$select .= " LIMIT 0 , " . $suryou;
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($select))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) {
							$syoridt = $row['syoridt'];
							$syorino = $row['syorino'];
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", $syoridt);
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", $syorino);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . sprintf("'%s'", $profit);
							$insert1 .= "," . $collist["仕入単価"];
							$insert2 .= "," . sprintf("'%s'", $siire);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $name);
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . sprintf("'%s'", $option_tanka);
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							//販売
							if ($hannum > 0) {
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["paypayフラグ"];
								$insert2 .= "," . $p_flg;
							}
							//現物予約
							elseif ($grenum > 0) {
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . 1;
							}
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ(mysql_entry_option)===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							//在庫の消込処理
							// ================================================
							// ■　□　■　□　在庫Ｔ削除　■　□　■　□
							// ================================================
							$delete = $delete1;
							$delete .= $delete2 . sprintf("'%s'", $syoridt);
							$delete .= $delete3 . sprintf("'%s'", $syorino);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete)) {
								$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
							if ($hannum > 0) {
								--$hannum;
							}
							//現物予約
							elseif ($grenum > 0) {
								--$grenum;
							}
						}
					//駐車料金の場合
					}else{
						for($i=0; $i<$suryou; ++$i){
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . sprintf("'%s'", $profit);
							$insert1 .= "," . $collist["仕入単価"];
							$insert2 .= "," . sprintf("'%s'", $siire);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $name);
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . sprintf("'%s'", $option_tanka);
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							//販売
							if ($hannum > 0) {
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["paypayフラグ"];
								$insert2 .= "," . $p_flg;
							}
							//現物予約
							elseif ($grenum > 0) {
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . 1;
							}
							//見本予約
							elseif ($mrenum > 0) {
								$insert1 .= "," . $collist["見本予約数量"];
								$insert2 .= "," . 1;
								--$mrenum;
							}
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ(mysql_entry_option)===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					}
				}
				//見本予約の場合	
				if($mrenum > 0){
					for($j=0; $j<$mrenum; ++$j){
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["処理日付"];
						$insert2 .= "," . sprintf("'%s'", $syoridt);
						$insert1 .= "," . $collist["処理ＮＯ"];
						$insert2 .= "," . sprintf("'%s'", $syorino);
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . sprintf("'%s'", $profit);
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . sprintf("'%s'", $siire);
						$insert1 .= "," . $collist["型番"];
						$insert2 .= "," . sprintf("'%s'", $name);
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . sprintf("'%s'", $option_tanka);
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . sprintf("'%s'", $kbn);
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						
						$comm->ouputlog("===データ追加ＳＱＬ(mysql_entry_option)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			$dataCnt++;
			$optionCnt = 0;
			$hannum = 0;
			$grenum = 0;
			$mrenum = 0;
			$name = "";
			$t_option_tanka = 0;
			$option_tanka = 0;
			$siire = 0;
			$p_flg = 0;
			$t_p_flg = 0;
			$kbn = "";
		} else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}
			elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}
			elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}
			if(preg_match("/^型番１/",$key)){
				$name = $val;
			}
			//処理日付情報
			elseif(preg_match("/^処理日付/",$key)){
				$syoridt = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^処理ＮＯ/",$key)){
				$syorino = $val;
			}
			elseif(preg_match("/^Ｔ単価１/",$key)){
				$t_option_tanka = $val;
			}
			elseif(preg_match("/^単価１/",$key)){
				$option_tanka = $val;
			}
			elseif(preg_match("/^Ｔ型番１/",$key)){
				$t_name = $val;
			}
			elseif(preg_match("/^Ｔ販売/",$key)){
				$t_hannum = $val;
			}
			elseif(preg_match("/^Ｔ現物/",$key)){
				$t_grenum = $val;
			}
			elseif(preg_match("/^Ｔ見本/",$key)){
				$t_mrenum = $val;
			}
			elseif(preg_match("/^Ｔpaypayフラグ/",$key)){
				$t_p_flg = $val;
			}
			elseif(preg_match("/^paypayフラグ/",$key)){
				$p_flg = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
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
// mysql_del_option
//
// ■概要
// 販売情報消込
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_option( $db, $g_section, $name, $option_tanka, $num, $numflg, $kbn, $t_p_flg, $p_flg) {

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

	$comm->ouputlog("mysql_del_optionログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $name);
	$select .= " AND A.tanka = " . sprintf("'%s'", $option_tanka);
	$select .= " AND A.henpinflg = 0";
	$select .= " AND A.telhenflg = 0";
	$select .= " AND A.delflg = 0";
	$select .= " AND A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " AND A.p_flg = " . sprintf("'%s'", $t_p_flg);
	$select .= " AND A." . $numflg . " > 0";
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$select .= " LIMIT 0 , " . $num;
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($select, $db)) {
	if (!($rs = $db->query($select))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai SET delflg = 1 ";
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ削除実行
		$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//		if (! mysql_query($_delete, $db)) {
		if (! $db->query($_delete)) {
//			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
		//販売・現物予約の場合は在庫の消込をする
		if(($numflg =='hannum' || $numflg =='grenum') && $name <> "駐車料金"){
			// ================================================
			// ■　□　■　□　データ移送（在庫Ｔ）　■　□　■　□
			// ================================================
			$_update = "UPDATE php_t_option_zaiko SET hanbaiflg = 0 ";
			$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
			$_update .= " ,updcount = updcount + 1";
			$_update .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
			$_update .= " AND syoridt = " . sprintf("'%s'", $syoridt);
			$_update .= " AND syorino = " . sprintf("'%s'", $syorino);
			//データ移送実行
			$comm->ouputlog("===データ移送ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//			if (! mysql_query($_update, $db)) {
			if (! $db->query($_update)) {
//				$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
				$comm->ouputlog("☆★☆データ移送エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			$comm->ouputlog("===データ移送完了===", $prgid, SYS_LOG_TYPE_DBUG);
		}
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_change_option
//
// ■概要
// オプション単価変更
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_change_option( $db, $g_section, $name, $t_tanka, $option_tanka, $profit, $kbn, $t_p_flg, $p_flg) {

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

	$comm->ouputlog("mysql_change_optionログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $name);
	$select .= " AND A.tanka = " . sprintf("'%s'", $t_tanka);
	$select .= " AND A.p_flg = " . sprintf("'%s'", $t_p_flg);
	$select .= " AND A.henpinflg = 0";
	$select .= " AND A.telhenflg = 0";
	$select .= " AND A.delflg = 0";
	$select .= " AND A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($select, $db)) {
	if (!($rs = $db->query($select))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ更新（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai SET tanka = " . sprintf("'%s'", $option_tanka);
		$_delete .= " ,profit = " . sprintf("'%s'", $profit);
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ更新実行
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//		if (! mysql_query($_delete, $db)) {
		if (! $db->query($_delete)) {
//			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_barcode_option
//
// ■概要
// バーコードオプション販売実績登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_barcode_option( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_post;
	$comm->ouputlog("mysql_barcode_optionログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "b_end";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　オプション単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.name , A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_option_info A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.name";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
	while ($row = $rs->fetch_array()) {
		$p_name = $row['name'];
		$siirelist[$p_name] = $row['siire'];
		$siiredlist[$p_name] = $row['siire_d'];
		$profitlist[$p_name] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//バーコードフラグ
	$m_query1 .= "," . $collist["バーコードフラグ"];
	$m_query2 .= "," . "1";
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_option_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	$dataCnt = 101;
	$federation = 0;
	$suryou = 0;
	$hannum = 0;
	$tsuryou = 0;
	$modelnum = "";
	$tanka = 0;
	$g_idxnum = 0;
	$kbn = "";
	$lostnum = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if($key == $end. $dataCnt) {
			$comm->ouputlog("===備品数量===". $suryou, $prgid, SYS_LOG_TYPE_DBUG);
			++$dataCnt;
			if($suryou > 0 && $federation == 1 && $kbn == '2') {
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $option_tanka - $siirelist[$name];
				}else{ //代理店以外
					$profit = $profitlist[$name];
				}
				//仕入単価
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$siire = $siiredlist[$name];
				}else{ //代理店以外
					$siire = $siirelist[$name];
				}
				// ================================================
				// ■　□　■　□　在庫Ｔ検索　■　□　■　□
				// ================================================
				//会場No取得
				$select =  " SELECT A.syoridt , A.syorino ";
				$select .= " FROM php_t_option_zaiko A ";
				$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
				$select .= " AND A.name = " . sprintf("'%s'", $modelnum);
				$select .= " AND A.hanbaiflg = 0";
				$select .= " AND A.delflg = 0";
				$select .= " ORDER BY A.syoridt , A.syorino ";
				$select .= " LIMIT 0 , " . $suryou;
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($select))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$syoridt = $row['syoridt'];
					$syorino = $row['syorino'];
					// ================================================
					// ■　□　■　□　販売Ｔ登録　■　□　■　□
					// ================================================
					//会場ＮＯ
					$venueno++;
					//ＳＱＬ文結合
					$insert = $query;
					$insert1 = $query1;
					$insert2 = $query2;
					$insert1 .= "," . $collist["会場ＮＯ"];
					$insert2 .= "," . $venueno;
					$insert1 .= "," . $collist["処理日付"];
					$insert2 .= "," . sprintf("'%s'", $syoridt);
					$insert1 .= "," . $collist["処理ＮＯ"];
					$insert2 .= "," . sprintf("'%s'", $syorino);
					$insert1 .= "," . $collist["型番"];
					$insert2 .= "," . sprintf("'%s'", $modelnum);
					$insert1 .= "," . $collist["単価"];
					$insert2 .= "," . sprintf("'%s'", $tanka);
					$insert1 .= "," . $collist["区分"];
					$insert2 .= "," . sprintf("'%s'", $kbn);
					$insert1 .= "," . $collist["部"];
					$insert2 .= "," . sprintf("'%s'", $section);
					$insert1 .= "," . $collist["販売数量"];
					$insert2 .= "," . 1;
					//ＳＱＬ文結合
					$insert1 .= ")";
					$insert2 .= ")";
					$insert .= $insert1 . $insert2;
					$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

					//データ追加実行
					if (! $db->query($insert)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					//在庫の消込処理
					// ================================================
					// ■　□　■　□　在庫Ｔ削除　■　□　■　□
					// ================================================
					$delete = $delete1;
					$delete .= $delete2 . sprintf("'%s'", $syoridt);
					$delete .= $delete3 . sprintf("'%s'", $syorino);
					$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
					$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);
					//データ削除実行
					if (! $db->query($delete)) {
						$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					$comm->ouputlog("===在庫消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
					++$hannum;
				}
				if($hannum < $suryou){
					$lostnum = 1;
				}
				// ================================================
				// ■　□　■　□　バーコードテーブル更新　■　□　■　□
				// ================================================
				$_update = "UPDATE php_pc_barcode_lost ";
				$_update .= " SET d_suryou = d_suryou + " . $hannum;
				$_update .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
				$_update .= " ,updcount = updcount + 1";
				if($hannum >= $tsuryou){
					$_update .= " ,doneflg = 1";
				}
				$_update .= " WHERE idxnum = " . sprintf("'%s'", $g_idxnum);
				$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
				$comm->ouputlog($_update, $prgid, SYS_LOG_TYPE_DBUG);
				//データ追加実行
				if (! $db->query($_update)) {
					$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					return false;
				}
				$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
				//初期値セット
				$query = $m_query;
				$query1 = $m_query1;
				$query2 = $m_query2;
				//在庫Ｔ
				$delete1 = $m_delete1;
				$delete2 = $m_delete2;
				$delete3 = $m_delete3;
				$federation = 0;
				$suryou = 0;
				$hannum = 0;
				$tsuryou = 0;
				$modelnum = "";
				$tanka = 0;
				$g_idxnum = 0;
				$kbn = "";
			}
		} else {
			//連携チェック
			if($key == "連携". $dataCnt) {
				$federation = $val;
			}
			elseif($key == "備品数量". $dataCnt) {
				$suryou = $val;
			}
			elseif($key == "備品品名". $dataCnt) {
				$modelnum = $val;
			}
			elseif($key == "備品単価". $dataCnt) {
				$tanka = $val;
			}
			elseif($key == "インデックス". $dataCnt) {
				$g_idxnum = $val;
			}
			//Ｔ備品数量
			elseif($key == "Ｔ備品数量". $dataCnt) {
				$tsuryou = $val;
			}
			//区分
			elseif($key == "区分". $dataCnt) {
				$kbn = $val;
			}
			//部
			elseif($key == "部". $dataCnt) {
				$section = $val;
			}
		}
		//入力値出力
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
	}

	$comm->ouputlog("===データ追加処理完了===", $prgid, SYS_LOG_TYPE_DBUG);

	return $lostnum;
}
//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_ins_huryou
//
// ■概要
// 不良品登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_ins_huryou( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	global $g_idx;

	$comm->ouputlog("mysql_ins_huryouログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$table = "";
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query = "";
	$query .=" SELECT IFNULL(max(A.venueno),0) as venueno ";
	$query .=" FROM php_t_pc_huryou A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	//初期化
	$dataCnt = 1;
	$modelnum = "";
	$name = "";
	$suryou = 0;

	$insdt = date('YmdHis');

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if ("end" . $dataCnt == $key) {
			$comm->ouputlog("ENDデータ判定", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog("不良品数：" . $suryou, $prgid, SYS_LOG_TYPE_INFO);
			//不良品登録
			if($suryou > 0){
				for($i=0;$i<$suryou;$i++){
					//会場ＮＯカウントアップ
					++$venueno;
					// ================================================
					// ■　□　■　□　返品Ｔ登録　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" INSERT INTO php_t_pc_huryou ";
					$query .=" ( ";
					$query .= " insdt ";
					$query .= " , upddt ";
					$query .= " , venueid ";
					$query .= " , venueno";
					$query .= " , suryou";
					$query .= " , modelnum";
					$query .= " , kbn ";
					$query .= " )VALUES( ";
					$query .= sprintf("'%s'", date('YmdHis'));
					$query .= "," . sprintf("'%s'", date('YmdHis'));
					$query .= "," . sprintf("'%s'", $performance[venueid]);
					$query .= "," . sprintf("'%s'", $venueno);
					$query .= ", 1" ;
					$query .= "," . sprintf("'%s'", $modelnum);
					$query .= "," . sprintf("'%s'", $kbn);
					$query .=" )";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
					// ================================================
					// ■　□　■　□　在庫Ｔ更新　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "";
					$query .=" UPDATE  ";
					if($kbn == '1'){
						$query .=" php_t_pc_zaiko ";
					}else if($kbn == '2'){
						$query .=" php_t_option_zaiko ";
					}
					$query .=" SET delflg = '1'";
					$query .=" ,movflg = '1'";
					$query .=" ,upddt = " . sprintf("'%s'", $insdt);
					$query .=" ,updcount = updcount + 1";
					$query .=" WHERE staff = " . sprintf("'%s'", $performance[staff]);
					if($kbn == '1'){
						$query .=" AND modelnum = " . sprintf("'%s'", $modelnum);
					}else if($kbn == '2'){
						$query .=" AND name = " . sprintf("'%s'", $modelnum);
					}
					$query .=" AND delflg = 0";
					$query .=" AND hanbaiflg = 0";
					$query .=" ORDER BY syorino ";
					$query .=" LIMIT 1";
					$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						return false;
					}
				}
			}
			//初期値セット
			$dataCnt++;
			$modelnum = "";
			$suryou = 0;
			$kbn = 0;
		}else{
			//数量チェックされている場合
			if($key == "不良品". $dataCnt){
				$suryou = $val;
			}
			elseif($key == "型番". $dataCnt){
				$modelnum = $val;
			}
			elseif($key == "区分". $dataCnt){
				$kbn = $val;
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
// mysql_cancel_huryou
//
// ■概要
// 不良品登録キャンセル
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_cancel_huryou( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象プログラム
	global $prgid;
	//引数
	global $g_venueid;
	global $g_venueno;
	$comm->ouputlog("mysql_cancel_p_huryouログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	// ================================================
	// ■　□　■　□　不良品削除　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT kbn, modelnum ";
	$query .=" FROM php_t_pc_huryou A ";
	$query .=" WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$query .=" AND venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$kbn = $row['kbn'];
		$modelnum = $row['modelnum'];
	}
	$query = "";
	$query .=" UPDATE php_t_pc_huryou ";
	$query .=" SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$query .=" , updcount = updcount + 1 ";
	$query .=" , delflg=1 ";
	$query .=" WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$query .=" AND venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ削除 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	// ================================================
	// ■　□　■　□　在庫追加　■　□　■　□
	// ================================================
	if($kbn == 1){
		$table = "php_t_pc_zaiko";
		$table2 = "php_pc_info";
		$p_modelnum = "modelnum";
	}else if($kbn == 2){
		$table = "php_t_option_zaiko";
		$table2 = "php_option_info";
		$p_modelnum = "name";
	}
	//会場No取得
	$query = "";
	$query .=" SELECT IFNULL(max(A.syorino),0) as syorino ";
	$query .=" FROM ".$table." A ";
	$query .=" WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
	$query .=" AND A.syoridt = ". sprintf("'%s'", date('Ymd'));
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$syorino = $row['syorino'] + 1;
	}
	
	$query =" INSERT INTO ".$table;
	$query .=" (insdt, upddt, syoridt, syorino, syoriflg, staff, ".$p_modelnum.", siire, tanka, suryou) ";
	$query .=" SELECT " . sprintf("'%s'", date('Ymd')) ."," . sprintf("'%s'", date('Ymd')) ."," . sprintf("'%s'", date('Ymd')) ."," . sprintf("'%s'", $syorino) .", '2', ";
	$query .=" " . sprintf("'%s'", $performance[staff]) ."," . sprintf("'%s'", $modelnum) .", siire, tanka, 1";
	$query .=" FROM ".$table2;
	$query .=" WHERE ".$p_modelnum." = " . sprintf("'%s'", $modelnum);
	$query .=" AND delflg = 0 ";
	$query .=" GROUP BY ".$p_modelnum." ";
	$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_barcode_entry
//
// ■概要
// バーコードデータ販売実績登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_barcode_entry( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_post;
	$comm->ouputlog("mysql_barcode_entryログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "/^end/";
	$arrkey = array("型番","ＣＰＵ","メモリ","対象ＯＳ","仕入単価","分類");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.modelnum, A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_pc_tanka A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.modelnum";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['modelnum'];
		$siirelist[$p_modelnum] = $row['siire'];
		$siiredlist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}
	// ================================================
	// ■　□　■　□　ＰＣ個別単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "
		SELECT A.modelnum, A.pop, A.tanka, A.memo
		FROM php_pc_price_team A
		WHERE A.week = '$performance[week]'
		AND A.staff = '$performance[staff]'
	";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	while ($row = $rs->fetch_array()) {
		$pc_price_team[] = $row;
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_pc_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";
	//データ削除（バーコードＴ）
	$b_delete1 = "UPDATE php_t_pc_barcode SET captureflg = 1 ";
	$b_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$b_delete1 .= " ,updcount = updcount + 1";
	$b_delete1 .= " WHERE 1";
	$b_delete2 = " AND receiptno = ";
	$b_delete3 = " AND branch = ";
	$b_delete4 = " AND section = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	//バーコードＴ
	$delete_b1 = $b_delete1;
	$delete_b2 = $b_delete2;
	$delete_b3 = $b_delete3;
	$delete_b4 = $b_delete4;
	$dataCnt = 1;
	$hannum = 0;
	$modelnum = "";
	$tanka = 0;
	$profit = 0;
	$siire = 0;
	$check = 0;
	
	$array_pop = array("1" => "①", "2" => "②", "3" => "③", "4" => "④", "5" => "⑤", "6" => "⑥", "7" => "⑦", "8" => "⑧", "9" => "⑨", "10" => "⑩"
	, "11" => "⑪", "12" => "⑫", "13" => "⑬", "14" => "⑭", "15" => "⑮", "16" => "⑯", "17" => "⑰", "18" => "⑱", "19" => "⑲", "20" => "⑳"
	, "21" => "㉑", "22" => "㉒", "23" => "㉓", "24" => "㉔", "25" => "㉕", "26" => "㉖", "27" => "㉗", "28" => "㉘", "29" => "㉙", "30" => "㉚"
	, "31" => "㉛", "32" => "㉜", "33" => "㉝", "34" => "㉞", "35" => "㉟", "36" => "㊱", "37" => "㊲", "38" => "㊳", "39" => "㊴", "40" => "㊵"
	, "41" => "㊶", "42" => "㊷", "43" => "㊸", "44" => "㊹", "45" => "㊺", "46" => "㊻", "47" => "㊼", "48" => "㊽", "49" => "㊾", "50" => "㊿"
	, "51" => "51", "52" => "52", "53" => "53", "54" => "54", "55" => "55", "56" => "56", "57" => "57", "58" => "58", "59" => "59", "60" => "60"
	, "61" => "61", "62" => "62", "63" => "63", "64" => "64", "65" => "65", "66" => "66", "67" => "67", "68" => "68", "69" => "69", "70" => "70"
	, "71" => "71", "72" => "72", "73" => "73", "74" => "74", "75" => "75", "76" => "76", "77" => "77", "78" => "78", "79" => "79", "80" => "80"
	, "81" => "81", "82" => "82", "83" => "83", "84" => "84", "85" => "85", "86" => "86", "87" => "87", "88" => "88", "89" => "89", "90" => "90"
	, "91" => "91", "92" => "92", "93" => "93", "94" => "94", "95" => "95", "99" => "99", "97" => "97", "98" => "98", "99" => "99", "100" => "100", "999" => "999", "0" => ""
	);

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match($end,$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" modelnum=" . $modelnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" tanka=" . $tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" han_suryou=" . $han_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" gre_suryou=" . $gre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mre_suryou=" . $mre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" check=" . $check, $prgid, SYS_LOG_TYPE_DBUG);
			$suryou = $han_suryou + $gre_suryou;
			$comm->ouputlog(" suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
			//対象がPCの場合のみfunctionを実行
			if(($kbn == '1' || $kbn == '4' || $kbn == '5')&& $check == 1){
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $tanka - $siirelist[$modelnum];
				} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
					$_sql =" SELECT A.profit ";
					$_sql .=" FROM php_system A ";
					$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($_sql))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$profit = $row['profit'];
					}
				}else{ //代理店以外
					$profit = $profitlist[$modelnum];
				}
				//仕入単価
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$siire = $siiredlist[$modelnum];
				}else{ //代理店以外
					$siire = $siirelist[$modelnum];
				}
				if ($suryou > 0) {
					//配列/変数初期化
					$syoridt = array_fill(1, $suryou, '');
					$syorino = array_fill(1, $suryou, '');
					$cnt = 1;
					// ================================================
					// ■　□　■　□　在庫Ｔ検索　■　□　■　□
					// ================================================
					$select =  " SELECT A.syoridt , A.syorino ";
					$select .= " FROM php_t_pc_zaiko A ";
					$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
					$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
					$select .= " AND A.hanbaiflg = 0";
					$select .= " AND A.delflg = 0";
					$select .= " ORDER BY A.syoridt , A.syorino ";
					$select .= " LIMIT 0 , " . $suryou;
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($select))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$comm->ouputlog("===在庫データ消込開始===", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog("syoridt = ".$row['syoridt'], $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog("syorino = ".$row['syorino'], $prgid, SYS_LOG_TYPE_INFO);
						$syoridt[$cnt] = $row['syoridt'];
						$syorino[$cnt] = $row['syorino'];
						// ================================================
						// ■　□　■　□　在庫Ｔ消込　■　□　■　□
						// ================================================
						$delete = $delete1;
						$delete .= $delete2 . sprintf("'%s'", $row['syoridt']);
						$delete .= $delete3 . sprintf("'%s'", $row['syorino']);
						$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);
						//データ削除実行
						if (! $db->query($delete)) {
							$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						//カウントアップ
						$cnt = $cnt + 1;
						$comm->ouputlog("===在庫データ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
					// ================================================
					// ■　□　■　□　バーコードＴ検索　■　□　■　□
					// ================================================
					//会場No取得
					$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
					$select2 .= " FROM php_t_pc_barcode A ";
					$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
					$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
					$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
					$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
					$select2 .= " AND A.captureflg = 0";
					$select2 .= " AND A.delflg = 0";
					$select2 .= " AND (A.hannum>0 OR A.c_grenum>0)";
					$select2 .= " AND B.section = " . sprintf("'%s'", $section);
					$select2 .= " ORDER BY B.syoridt , B.venueno ";
					$select2 .= " LIMIT 0 , " . $suryou;
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs2 = $db->query($select2))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					if ($rs2->num_rows == 0) {
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND (A.hannum>0 OR A.c_grenum>0)";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , " . $suryou;
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}
					//変数初期化
					$cnt = 1;
					while ($row2 = $rs2->fetch_array()) {
						// ================================================
						// ■　□　■　□　メモ情報取得　■　□　■　□
						// ================================================
						$remarks = "";
						foreach ($pc_price_team as $price) {
							$comm->ouputlog("===modelnum===".$price['modelnum'], $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog("===modelnum===".$modelnum, $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog("===pop===".$price['pop'], $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog("===pop===".$row2['g_pop'], $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog("===tanka===".$price['tanka'], $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog("===tanka===".$tanka, $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog("===memo===".$price['memo'], $prgid, SYS_LOG_TYPE_DBUG);
							if ($price['modelnum'] ==  $modelnum && $price['pop'] == $row2['g_pop'] && $price['tanka'] ==  $tanka) {
								$remarks = $price['memo'];
								break;
							}
						}
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["部"];
						$insert2 .= "," . $section;
						$insert1 .= "," . $collist["処理日付"];
						$insert2 .= "," . sprintf("'%s'", $syoridt[$cnt]);
						$insert1 .= "," . $collist["処理ＮＯ"];
						$insert2 .= "," . sprintf("'%s'", $syorino[$cnt]);
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["バーコード番号"];
						$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
						$insert1 .= "," . $collist["レシート番号"];
						$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
						$insert1 .= "," . $collist["バーコードフラグ"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["販売数量"];
						$insert2 .= "," . sprintf("'%s'", $row2['hannum']);
						$insert1 .= "," . $collist["現物予約数量"];
						$insert2 .= "," . sprintf("'%s'", $row2['c_grenum']);
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . sprintf("'%s'", $kbn);
						$insert1 .= "," . $collist["paypayフラグ"];
						$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
						$insert1 .= "," . $collist["バーコード処理日時"];
						$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $array_pop[$row2['g_pop']].$remarks);
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
						//バーコードデータの消込処理
						// ================================================
						// ■　□　■　□　バーコードＴ削除　■　□　■　□
						// ================================================
						$delete_b = $delete_b1;
						$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
						$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
						$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
						//データ削除実行
						if (! $db->query($delete_b)) {
							$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						//カウントアップ
						$cnt = $cnt + 1;
						$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
				//見本予約　チェックがある場合、登録
				for($i=0; $i<$mre_suryou; ++$i){
					// ================================================
					// ■　□　■　□　バーコードＴ検索　■　□　■　□
					// ================================================
					//会場No取得
					$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, IFNULL(C.memo, '') as remarks, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
					$select2 .= " FROM php_t_pc_barcode A ";
					$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
					$select2 .= " LEFT OUTER JOIN php_performance D ON B.venueid=CONCAT(DATE_FORMAT(D.buydt , '%Y%m%d' ), LPAD(D.lane,2,'0') , '-' , D.branch)";
					$select2 .= " LEFT OUTER JOIN php_pc_price_team C ON A.modelnum=C.modelnum AND A.g_pop=C.pop AND A.tanka=C.tanka AND D.week=C.week AND D.staff=C.staff ";
					$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
					$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
					$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
					$select2 .= " AND A.captureflg = 0";
					$select2 .= " AND A.delflg = 0";
					$select2 .= " AND A.c_mrenum > 0";
					$select2 .= " AND B.section = " . sprintf("'%s'", $section);
					$select2 .= " ORDER BY B.syoridt , B.venueno ";
					$select2 .= " LIMIT 0 , 1";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs2 = $db->query($select2))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					if ($rs2->num_rows == 0) {
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, IFNULL(C.memo, '') as remarks, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " LEFT OUTER JOIN php_performance D ON B.venueid=CONCAT(DATE_FORMAT(D.buydt , '%Y%m%d' ), LPAD(D.lane,2,'0') , '-' , D.branch)";
						$select2 .= " LEFT OUTER JOIN php_pc_price_team C ON A.modelnum=C.modelnum AND A.g_pop=C.pop AND A.tanka=C.tanka AND D.week=C.week AND D.staff=C.staff ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND A.c_mrenum > 0";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}
					while ($row2 = $rs2->fetch_array()) {
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["部"];
						$insert2 .= "," . $section;
						$insert1 .= "," . $collist["処理日付"];
						$insert2 .= "," . sprintf("'%s'", $syoridt);
						$insert1 .= "," . $collist["処理ＮＯ"];
						$insert2 .= "," . sprintf("'%s'", $syorino);
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["バーコード番号"];
						$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
						$insert1 .= "," . $collist["レシート番号"];
						$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
						$insert1 .= "," . $collist["バーコードフラグ"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . sprintf("'%s'", $row2['c_mrenum']);
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . sprintf("'%s'", $kbn);
						$insert1 .= "," . $collist["バーコード処理日時"];
						$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $array_pop[$row2['g_pop']].$row2['remarks']);
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
						//バーコードデータの消込処理
						// ================================================
						// ■　□　■　□　バーコードＴ削除　■　□　■　□
						// ================================================
						$delete_b = $delete_b1;
						$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
						$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
						$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
						//データ削除実行
						if (! $db->query($delete_b)) {
							$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			//バーコードＴ
			$delete_b1 = $b_delete1;
			$delete_b2 = $b_delete2;
			$delete_b3 = $b_delete3;
			$delete_b4 = $b_delete4;
			$dataCnt++;
			$hannum = 0;
			$tanka = 0;
			$profit = 0;
			$siire = 0;
			$kbn = "";
			$check = 0;
		}else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}else{
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if(preg_match("/^$arrkey[$cnt]/",$key)){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if($length > 0 && strlen($val) > $length){
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}else{
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if(preg_match("/^型番/",$key)){
				$modelnum = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^連携/",$key)){
				$check = $val;
			}
			elseif(preg_match("/^単価/",$key)){
				$tanka = $val;
			}
			elseif(preg_match("/^仕入単価/",$key)){
				$siire = $val;
			}
			elseif(preg_match("/^販売数量/",$key)){
				$han_suryou = $val;
			}
			elseif(preg_match("/^現物予約/",$key)){
				$gre_suryou = $val;
			}
			elseif(preg_match("/^見本予約/",$key)){
				$mre_suryou = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
			}
			elseif(preg_match("/^部/",$key)){
				$section = $val;
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
// mysql_barcode_entry_option
//
// ■概要
// 周辺機器バーコードデータ販売実績登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_barcode_entry_option( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_post;
	$comm->ouputlog("mysql_barcode_entry_optionログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "/^b_end/";
	$arrkey = array("仕入単価");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.name, A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_option_info A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.name";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['name'];
		$siirelist[$p_modelnum] = $row['siire'];
		$siiredlist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_option_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";
	//データ削除（バーコードＴ）
	$b_delete1 = "UPDATE php_t_pc_barcode SET captureflg = 1 ";
	$b_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$b_delete1 .= " ,updcount = updcount + 1";
	$b_delete1 .= " WHERE 1";
	$b_delete2 = " AND receiptno = ";
	$b_delete3 = " AND branch = ";
	$b_delete4 = " AND section = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	//バーコードＴ
	$delete_b1 = $b_delete1;
	$delete_b2 = $b_delete2;
	$delete_b3 = $b_delete3;
	$delete_b4 = $b_delete4;
	$dataCnt = 101;
	$hannum = 0;
	$modelnum = "";
	$tanka = 0;
	$profit = 0;
	$siire = 0;
	$check = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match($end,$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" modelnum=" . $modelnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" tanka=" . $tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" han_suryou=" . $han_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" gre_suryou=" . $gre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mre_suryou=" . $mre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" check=" . $check, $prgid, SYS_LOG_TYPE_DBUG);
			$suryou = $han_suryou + $gre_suryou;
			$comm->ouputlog(" suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
			//対象が周辺機器の場合のみfunctionを実行
			if($kbn > 1 && $check == 1){
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $tanka - $siirelist[$modelnum];
				} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
					$_sql =" SELECT A.profit ";
					$_sql .=" FROM php_system A ";
					$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($_sql))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$profit = $row['profit'];
					}
				}else{ //代理店以外
					$profit = $profitlist[$modelnum];
				}
				//仕入単価
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$siire = $siiredlist[$modelnum];
				}else{ //代理店以外
					$siire = $siirelist[$modelnum];
				}
				//チェックがある場合、登録
				for($i=0; $i<$suryou; ++$i){
					if($kbn == 2 || $kbn == 6){
						//販売
						// ================================================
						// ■　□　■　□　在庫Ｔ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select =  " SELECT A.syoridt , A.syorino ";
						$select .= " FROM php_t_option_zaiko A ";
						$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
						$select .= " AND A.name = " . sprintf("'%s'", $modelnum);
						$select .= " AND A.hanbaiflg = 0";
						$select .= " AND A.delflg = 0";
						$select .= " ORDER BY A.syoridt , A.syorino ";
						$select .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($select))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) {
							$syoridt = $row['syoridt'];
							$syorino = $row['syorino'];
							// ================================================
							// ■　□　■　□　バーコードＴ検索　■　□　■　□
							// ================================================
							//会場No取得
							$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
							$select2 .= " FROM php_t_pc_barcode A ";
							$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
							$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
							$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
							$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
							$select2 .= " AND A.captureflg = 0";
							$select2 .= " AND A.delflg = 0";
							$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
							$select2 .= " AND B.section = " . sprintf("'%s'", $section);
							$select2 .= " ORDER BY B.syoridt , B.venueno ";
							$select2 .= " LIMIT 0 , 1";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($select2))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							if ($rs2->num_rows == 0) {
								$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
								$select2 .= " FROM php_t_pc_barcode A ";
								$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
								$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
								$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
								$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
								$select2 .= " AND A.captureflg = 0";
								$select2 .= " AND A.delflg = 0";
								$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
								$select2 .= " AND B.section = " . sprintf("'%s'", $section);
								$select2 .= " ORDER BY B.syoridt , B.venueno ";
								$select2 .= " LIMIT 0 , 1";
								$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
								$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
								if (!($rs2 = $db->query($select2))) {
									$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								}
							}
							while ($row2 = $rs2->fetch_array()) {
								// ================================================
								// ■　□　■　□　販売Ｔ登録　■　□　■　□
								// ================================================
								//会場ＮＯ
								$venueno++;
								if (is_null($profit)) {
									$profit = 0;
								}
								//ＳＱＬ文結合
								$insert = $query;
								$insert1 = $query1;
								$insert2 = $query2;
								$insert1 .= "," . $collist["会場ＮＯ"];
								$insert2 .= "," . $venueno;
								$insert1 .= "," . $collist["部"];
								$insert2 .= "," . $section;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . sprintf("'%s'", $kbn);
								$insert1 .= "," . $collist["型番"];
								$insert2 .= "," . sprintf("'%s'", $modelnum);
								$insert1 .= "," . $collist["処理日付"];
								$insert2 .= "," . sprintf("'%s'", $syoridt);
								$insert1 .= "," . $collist["処理ＮＯ"];
								$insert2 .= "," . sprintf("'%s'", $syorino);
								$insert1 .= "," . $collist["利益"];
								$insert2 .= "," . $profit;
								$insert1 .= "," . $collist["単価"];
								$insert2 .= "," . $tanka;
								$insert1 .= "," . $collist["バーコード番号"];
								$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
								$insert1 .= "," . $collist["レシート番号"];
								$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
								$insert1 .= "," . $collist["バーコードフラグ"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["paypayフラグ"];
								$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
								$insert1 .= "," . $collist["バーコード処理日時"];
								$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . sprintf("'%s'", $row2['hannum']);
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . sprintf("'%s'", $row2['c_grenum']);
								//ＳＱＬ文結合
								$insert1 .= ")";
								$insert2 .= ")";
								$insert .= $insert1 . $insert2;
								$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
								$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

								//データ追加実行
								if (! $db->query($insert)) {
									$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									return false;
								}
								$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
								//バーコードデータの消込処理
								// ================================================
								// ■　□　■　□　バーコードＴ削除　■　□　■　□
								// ================================================
								$delete_b = $delete_b1;
								$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
								$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
								$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
								$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
								//データ削除実行
								if (! $db->query($delete_b)) {
									$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									return false;
								}
								$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
								//在庫の消込処理
								// ================================================
								// ■　□　■　□　在庫Ｔ削除　■　□　■　□
								// ================================================
								$delete = $delete1;
								$delete .= $delete2 . sprintf("'%s'", $syoridt);
								$delete .= $delete3 . sprintf("'%s'", $syorino);
								$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
								$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);
								//データ削除実行
								if (! $db->query($delete)) {
									$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									return false;
								}
								$comm->ouputlog("===在庫データ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
							}
						}
					}else if($kbn == 3){
						// ================================================
						// ■　□　■　□　バーコードＴ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						if ($rs2->num_rows == 0) {
							$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg ";
							$select2 .= " FROM php_t_pc_barcode A ";
							$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
							$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
							$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
							$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
							$select2 .= " AND A.captureflg = 0";
							$select2 .= " AND A.delflg = 0";
							$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
							$select2 .= " AND B.section = " . sprintf("'%s'", $section);
							$select2 .= " ORDER BY B.syoridt , B.venueno ";
							$select2 .= " LIMIT 0 , 1";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($select2))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
						while ($row2 = $rs2->fetch_array()) {
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["部"];
							$insert2 .= "," . $section;
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $modelnum);
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", $syoridt);
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", $syorino);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . $profit;
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["バーコード番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
							$insert1 .= "," . $collist["レシート番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
							$insert1 .= "," . $collist["paypayフラグ"];
							$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
							$insert1 .= "," . $collist["バーコードフラグ"];
							$insert2 .= "," . 1;
							$insert1 .= "," . $collist["販売数量"];
							$insert2 .= "," . 1;
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
							//バーコードデータの消込処理
							// ================================================
							// ■　□　■　□　バーコードＴ削除　■　□　■　□
							// ================================================
							$delete_b = $delete_b1;
							$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
							$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete_b)) {
								$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					}
				}
				//チェックがある場合、登録
				for($i=0; $i<$mre_suryou; ++$i){
					if($kbn == 2 || $kbn == 3 || $kbn == 6){
						// ================================================
						// ■　□　■　□　バーコードＴ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND A.c_mrenum > 0";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						if ($rs2->num_rows == 0) {
							$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
							$select2 .= " FROM php_t_pc_barcode A ";
							$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
							$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
							$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
							$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
							$select2 .= " AND A.captureflg = 0";
							$select2 .= " AND A.delflg = 0";
							$select2 .= " AND A.c_mrenum > 0";
							$select2 .= " AND B.section = " . sprintf("'%s'", $section);
							$select2 .= " ORDER BY B.syoridt , B.venueno ";
							$select2 .= " LIMIT 0 , 1";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($select2))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
						while ($row2 = $rs2->fetch_array()) {
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["部"];
							$insert2 .= "," . $section;
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $modelnum);
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", date('Y-m-d H:i:s'));
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", 0);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . $profit;
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["バーコード番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
							$insert1 .= "," . $collist["レシート番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
							$insert1 .= "," . $collist["バーコードフラグ"];
							$insert2 .= "," . 1;
							$insert1 .= "," . $collist["paypayフラグ"];
							$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
							$insert1 .= "," . $collist["バーコード処理日時"];
							$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
							$insert1 .= "," . $collist["見本予約数量"];
							$insert2 .= "," . sprintf("'%s'", $row2['c_mrenum']);
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
							//バーコードデータの消込処理
							// ================================================
							// ■　□　■　□　バーコードＴ削除　■　□　■　□
							// ================================================
							$delete_b = $delete_b1;
							$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
							$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete_b)) {
								$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			//バーコードＴ
			$delete_b1 = $b_delete1;
			$delete_b2 = $b_delete2;
			$delete_b3 = $b_delete3;
			$delete_b4 = $b_delete4;
			$dataCnt++;
			$hannum = 0;
			$tanka = 0;
			$profit = 0;
			$siire = 0;
			$kbn = "";
			$check = 0;
		}
		if(preg_match("/^end/",$key)){
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			//バーコードＴ
			$delete_b1 = $b_delete1;
			$delete_b2 = $b_delete2;
			$delete_b3 = $b_delete3;
			$delete_b4 = $b_delete4;
			$dataCnt++;
			$hannum = 0;
			$tanka = 0;
			$profit = 0;
			$siire = 0;
			$kbn = "";
			$check = 0;
		}else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}else{
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if(preg_match("/^$arrkey[$cnt]/",$key)){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if($length > 0 && strlen($val) > $length){
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}else{
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if(preg_match("/^備品品名/",$key)){
				$modelnum = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^連携/",$key)){
				$check = $val;
			}
			elseif(preg_match("/^備品単価/",$key)){
				$tanka = $val;
			}
			elseif(preg_match("/^仕入単価/",$key)){
				$siire = $val;
			}
			elseif(preg_match("/^備品数量/",$key)){
				$han_suryou = $val;
			}
			elseif(preg_match("/^備品現物/",$key)){
				$gre_suryou = $val;
			}
			elseif(preg_match("/^備品見本/",$key)){
				$mre_suryou = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
			}
			elseif(preg_match("/^部/",$key)){
				$section = $val;
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
// mysql_huryou_code
//
// ■概要
// バーコード不良品登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_huryou_code( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_week;
	global $g_post;

	$comm->ouputlog("mysql_huryou_codeログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$table = "";
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	$hit = 0;
	$query = "SELECT date";
	$query .=" FROM php_calendar A ";
	$query .=" WHERE A.week = " . sprintf("'%s'", $g_week);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$search_day = $row['date'];
		if($hit != 1){
			$query2 = "SELECT buydt,lane,branch";
			$query2 .=" FROM php_performance A ";
			$query2 .=" WHERE A.buydt = " . sprintf("'%s'", $search_day);
			$query2 .=" AND A.staff = " . sprintf("'%s'", $g_staff);
			if (!($rs2 = $db->query($query2))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row2 = $rs2->fetch_array()) {
				$kaisai_day = $row2['buydt'];
				$kaisai_lane = $row2['lane'];
				$kaisai_branch = $row2['branch'];
				$hit == 1;
			}
		}
	}
	$kaisai_day = str_replace('-', '', $kaisai_day);
	$kaisai_lane = sprintf( '%02d', $kaisai_lane );
	$venueid = $kaisai_day.$kaisai_lane.'-'.$kaisai_branch;

	//会場No取得
	$query =" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM php_t_pc_huryou A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $venueid);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
// ----- 2019.06 ver7.0対応
//	if (! $rs = mysql_query($query, $db)) {
	if (!($rs = $db->query($query))) {
//		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . mysql_errno($db) . ": " . mysql_error($db), $prgid, SYS_LOG_TYPE_ERR);
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
// ----- 2019.06 ver7.0対応
//	while ($row = @mysql_fetch_array($rs)) {
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	//初期化
	$dataCnt = 1;
	$modelnum = "";
	$name = "";
	$suryou = $_GET['sum'];
	$insdt = date('YmdHis');

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//最終項目の場合はデータ追加
		if ("end" . $dataCnt == $key) {
			$comm->ouputlog("ENDデータ判定", $prgid, SYS_LOG_TYPE_INFO);
			//会場ＮＯカウントアップ
			++$venueno;
			// ================================================
			// ■　□　■　□　返品Ｔ登録　■　□　■　□
			// ================================================
			//----- データ抽出
			$query = "";
			$query .=" INSERT INTO php_t_pc_huryou ";
			$query .=" ( ";
			$query .= " insdt ";
			$query .= " , upddt ";
			$query .= " , venueid ";
			$query .= " , venueno";
			$query .= " , suryou";
			$query .= " , modelnum";
			$query .= " , code";
			$query .= " , kbn ";
			$query .= " )VALUES( ";
			$query .= sprintf("'%s'", date('YmdHis'));
			$query .= "," . sprintf("'%s'", date('YmdHis'));
			$query .= "," . sprintf("'%s'", $venueid);
			$query .= "," . sprintf("'%s'", $venueno);
			$query .= ", 1" ;
			$query .= "," . sprintf("'%s'", $modelnum);
			$query .= "," . sprintf("'%s'", $code);
			$query .= "," . sprintf("'%s'", $kbn);
			$query .=" )";
			$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			// ================================================
			// ■　□　■　□　在庫Ｔ更新　■　□　■　□
			// ================================================
			//----- データ抽出
			$query = "";
			$query .=" UPDATE  ";
			if ($kbn == '1') {
				$query .=" php_t_pc_zaiko ";
			} else if($kbn == '2') {
 				$query .=" php_t_option_zaiko ";
			}
			$query .=" SET delflg = '1'";
			$query .=" ,movflg = '1'";
			$query .=" ,upddt = " . sprintf("'%s'", $insdt);
			$query .=" ,updcount = updcount + 1";
			$query .=" WHERE staff = " . sprintf("'%s'", $g_staff);
			if ($kbn == '1') {
				$query .=" AND modelnum = " . sprintf("'%s'", $modelnum);
			}else if($kbn == '2'){
				$query .=" AND name = " . sprintf("'%s'", $modelnum);
			}
			$query .=" AND delflg = 0";
			$query .=" AND hanbaiflg = 0";
			$query .=" ORDER BY syorino ";
			$query .=" LIMIT 1";
			$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			$dataCnt++;
		} else {
			if($key == "model". $dataCnt) {
				$modelnum = $val;
			}
			elseif($key == "code". $dataCnt) {
				$code = $val;
			}
			elseif($key == "kbn". $dataCnt) {
				$kbn = $val;
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
// mysql_huryou_code
//
// ■概要
// バーコード不良品登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_huryou_code_del( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_week;
	global $g_post;
	global $g_venueno;
	global $g_venueid;

	$comm->ouputlog("mysql_huryou_codeログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$table = "";
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	// ================================================
	// ■　□　■　□　不良品削除　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.kbn, A.modelnum, A.code ";
	$query .=" FROM php_t_pc_huryou A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $g_venueid);
	$query .=" AND A.venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$kbn = $row['kbn'];
		$modelnum = $row['modelnum'];
		$code = $row['code'];
	}
	$query = "";
	$query .=" UPDATE php_t_pc_huryou ";
	$query .=" SET upddt = " . sprintf("'%s'", date('YmdHis'));
	$query .=" , updcount = updcount + 1 ";
	$query .=" , delflg=1 ";
	$query .=" WHERE venueid = " . sprintf("'%s'", $g_venueid);
	$query .=" AND venueno = " . sprintf("'%s'", $g_venueno);
	$comm->ouputlog("データ削除 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}

	// ================================================
	// ■　□　■　□　在庫追加　■　□　■　□
	// ================================================
	if($kbn == 1){
		$table = "php_t_pc_zaiko";
		$table2 = "php_pc_info";
		$p_modelnum = "modelnum";
	}else if($kbn == 2){
		$table = "php_t_option_zaiko";
		$table2 = "php_option_info";
		$p_modelnum = "name";
	}
	
	//会場No取得
	$query = "";
	$query .=" SELECT IFNULL(max(A.syorino),0) as syorino ";
	$query .=" FROM ".$table." A ";
	$query .=" WHERE A.staff = " . sprintf("'%s'", $g_staff);
	$query .=" AND A.syoridt = ". sprintf("'%s'", date('Ymd'));
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$syorino = $row['syorino'] + 1;
	}
	
	$query =" INSERT INTO ".$table;
	$query .=" (insdt, upddt, syoridt, syorino, syoriflg, staff, ".$p_modelnum.", siire, tanka, suryou";
	if ($kbn == 1){
		$query .=",code)";
	} else{
		$query .=")";
	}
	$query .=" SELECT " . sprintf("'%s'", date('YmdHis')) ."," . sprintf("'%s'", date('YmdHis')) ."," . sprintf("'%s'", date('YmdHis')) ."," . sprintf("'%s'", $syorino) .", '2', ";
	$query .=" " . sprintf("'%s'", $g_staff) ."," . sprintf("'%s'", $modelnum) .", siire, tanka, 1, ". sprintf("'%s'", $code);
	$query .=" FROM ".$table2;
	$query .=" WHERE ".$p_modelnum." = " . sprintf("'%s'", $modelnum);
	$query .=" AND delflg = 0 ";
	$query .=" GROUP BY ".$p_modelnum." ";
	$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (! $db->query($query)) {
		$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		return false;
	}
	
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_huryou_code
//
// ■概要
// バーコード不良品登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_henpin_code( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_post;

	$comm->ouputlog("mysql_henpin_codeログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	$performance = mysql_sel_personal( $db);

	//初期化
	$dataCnt = 1;
	$op_dataCnt = 1;
	$modelnum = "";
	$name = "";
	$code = "";
	$op_code = "";
	$price = "";
	$op_price = "";
	$receipt = "";
	$op_receipt = "";
	$b_venueid = "";
	$tvenueno = "";
	$op_tvenueno = "";
	$opne_status = "";
	$suryou = $_GET['sum'];
	$insdt = date('YmdHis');

	//会場No取得
	$query = "";
	$query .=" SELECT IFNULL(max(A.venueno),0) as venueno ";
	$query .=" FROM php_t_pc_returned A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}
	$query = "";
	$query .=" SELECT IFNULL(max(A.venueno),0) as venueno ";
	$query .=" FROM php_t_option_returned A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$op_venueno = $row['venueno'];
	}

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//最終項目の場合はデータ追加
		if ("end" . $dataCnt == $key) {
			$comm->ouputlog("ENDデータ判定", $prgid, SYS_LOG_TYPE_INFO);
			//会場ＮＯカウントアップ
			++$venueno;
			$b_venueid = substr ( $receipt, 0, 12 );
			$query = "";
			$query .=" SELECT A.venueno ";
			$query .=" FROM php_t_pc_hanbai A ";
			$query .=" WHERE A.venueid = " . sprintf("'%s'", $b_venueid);
			$query .=" AND A.modelnum =  " . sprintf("'%s'", $modelnum);
			$query .=" AND A.b_code =  " . sprintf("'%s'", $code);
			$query .=" AND A.receiptno =  " . sprintf("'%s'", $receipt);
			$query .=" AND A.delflg =  " . sprintf("%s", 0);
			$query .=" AND A.telhenflg =  " . sprintf("%s", 0);
			$query .=" AND A.henpinflg =  " . sprintf("%s", 0);
			$query .=" ORDER BY A.venueno ";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				$tvenueno = $row['venueno'];
			}
			if ( $tvenueno == "" ) {
				$query = "";
				$query .=" SELECT A.venueno ";
				$query .=" FROM php_t_pc_hanbai A ";
				$query .=" WHERE A.venueid = " . sprintf("'%s'", $b_venueid);
				$query .=" AND A.modelnum =  " . sprintf("'%s'", $modelnum);
				$query .=" AND A.delflg =  " . sprintf("%s", 0);
				$query .=" AND A.telhenflg =  " . sprintf("%s", 0);
				$query .=" AND A.henpinflg =  " . sprintf("%s", 0);
				$query .=" ORDER BY A.venueno ";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$tvenueno = $row['venueno'];
				}
			}
			// ================================================
			// ■　□　■　□　返品Ｔ登録　■　□　■　□
			// ================================================
			//----- データ抽出
			$query = "";
			$query .=" INSERT INTO php_t_pc_returned ";
			$query .=" ( ";
			$query .= " insdt ";
			$query .= " , upddt ";
			$query .= " , venueid ";
			$query .= " , tvenueid ";
			$query .= " , venueno";
			$query .= " , tvenueno ";
			$query .= " , modelnum";
			$query .= " , g_code";
			$query .= " , tanka";
			$query .= " , receipt";
			$query .= " , hannum";
			$query .= " )VALUES( ";
			$query .= sprintf("'%s'", date('YmdHis'));
			$query .= "," . sprintf("'%s'", date('YmdHis'));
			$query .= "," . sprintf("'%s'", $performance[venueid]);
			$query .= "," . sprintf("'%s'", $b_venueid);
			$query .= "," . sprintf("'%s'", $venueno);
			$query .= "," . sprintf("'%s'", $tvenueno);
			$query .= "," . sprintf("'%s'", $modelnum);
			$query .= "," . sprintf("'%s'", $code);
			$query .= "," . sprintf("'%s'", $price);
			$query .= "," . sprintf("'%s'", $receipt);
			$query .= ", 1" ;
			$query .=" )";
			$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}

			// ================================================
			// ■　□　■　□　販売Ｔ更新　■　□　■　□
			// ================================================
			//----- データ抽出
			$query = "";
			$query .=" UPDATE php_t_pc_hanbai ";
			$query .=" SET henpinflg = " . sprintf("'%s'", $performance[venueid]);
			$query .=" ,upddt = " . sprintf("'%s'", $insdt);
			$query .=" ,updcount = updcount + 1";
			$query .=" WHERE venueid = " . sprintf("'%s'", $b_venueid);
			$query .=" AND venueno = " . sprintf("'%s'", $tvenueno); 
			$query .=" AND delflg = 0";
			$query .=" AND henpinflg = 0";
			$query .=" AND hannum > 0";
			$query .=" LIMIT 1";
			$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			$dataCnt++;
			// 初期化
			$modelnum = "";
			$code = "";
			$price = "";
			$receipt = "";
			$b_venueid = "";
			$tvenueno = "";
		} else if ( "op_end" . $op_dataCnt == $key ) {
			$comm->ouputlog("ENDデータ判定", $prgid, SYS_LOG_TYPE_INFO);
			//会場ＮＯカウントアップ
			++$op_venueno;
			$b_venueid = substr ( $op_receipt, 0, 12 );
			$op_venue = substr ( $op_receipt, 0, 10 );
			$query = "";
			$query .=" SELECT A.venueno ,B.staff";
			$query .=" FROM php_t_pc_hanbai A ";
			$query .=" INNER JOIN php_performance B ";
			$query .= " ON concat(DATE_FORMAT(B.buydt , '%Y%m%d' ), LPAD(B.lane,2,'0') ) = " . sprintf("'%s'", $op_venue);
			$query .=" WHERE A.venueid = " . sprintf("'%s'", $b_venueid);
			$query .=" AND A.modelnum =  " . sprintf("'%s'", $name);
			$query .=" AND A.b_code =  " . sprintf("'%s'", $op_code);
			$query .=" AND A.receiptno =  " . sprintf("'%s'", $op_receipt);
			$query .=" AND A.delflg =  " . sprintf("%s", 0);
			$query .=" AND A.telhenflg =  " . sprintf("%s", 0);
			$query .=" AND A.henpinflg =  " . sprintf("%s", 0);
			$query .=" ORDER BY A.venueno ";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				$op_tvenueno = $row['venueno'];
				$staff_name = $row['staff'];
			}
			if ( $op_tvenueno == "" ) {
				$query = "";
				$query .=" SELECT A.venueno ,B.staff";
				$query .=" FROM php_t_pc_hanbai A ";
				$query .=" INNER JOIN php_performance B ";
				$query .= " ON concat(DATE_FORMAT(B.buydt , '%Y%m%d' ), LPAD(B.lane,2,'0') ) = " . sprintf("'%s'", $op_venue);
				$query .=" WHERE A.venueid = " . sprintf("'%s'", $b_venueid);
				$query .=" AND A.modelnum =  " . sprintf("'%s'", $name);
				$query .=" AND A.delflg =  " . sprintf("%s", 0);
				$query .=" AND A.telhenflg =  " . sprintf("%s", 0);
				$query .=" AND A.henpinflg =  " . sprintf("%s", 0);
				$query .=" ORDER BY A.venueno ";
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$op_tvenueno = $row['venueno'];
					$staff_name = $row['staff'];
				}
			}
			// ================================================
			// ■　□　■　□　返品Ｔ登録　■　□　■　□
			// ================================================
			//----- データ抽出
			$query = "";
			$query .=" INSERT INTO php_t_option_returned ";
			$query .=" ( ";
			$query .= " insdt ";
			$query .= " , upddt ";
			$query .= " , venueid ";
			$query .= " , tvenueid ";
			$query .= " , venueno";
			$query .= " , tvenueno ";
			$query .= " , name";
			$query .= " , g_code";
			$query .= " , tanka";
			$query .= " , receipt";
			$query .= " , hannum";
			$query .= " , open_status";
			$query .= " )VALUES( ";
			$query .= sprintf("'%s'", date('YmdHis'));
			$query .= "," . sprintf("'%s'", date('YmdHis'));
			$query .= "," . sprintf("'%s'", $performance[venueid]);
			$query .= "," . sprintf("'%s'", $b_venueid);
			$query .= "," . sprintf("'%s'", $op_venueno);
			$query .= "," . sprintf("'%s'", $op_tvenueno);
			$query .= "," . sprintf("'%s'", $name);
			$query .= "," . sprintf("'%s'", $op_code);
			$query .= "," . sprintf("'%s'", $op_price);
			$query .= "," . sprintf("'%s'", $op_receipt);
			$query .= ", 1" ;
			$query .= "," . sprintf("'%s'", $open_status);
			$query .=" )";
			$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			// ================================================
			// ■　□　■　□　販売Ｔ更新　■　□　■　□
			// ================================================
			//----- データ抽出
			$query = "";
			$query .=" UPDATE php_t_pc_hanbai ";
			$query .=" SET henpinflg = " . sprintf("'%s'", $performance[venueid]);
			$query .=" ,upddt = " . sprintf("'%s'", $insdt);
			$query .=" ,updcount = updcount + 1";
			$query .=" WHERE venueid = " . sprintf("'%s'", $b_venueid);
			$query .=" AND venueno = " . sprintf("'%s'", $op_tvenueno); 
			$query .=" AND delflg = 0";
			$query .=" AND henpinflg = 0";
			$query .=" AND hannum > 0";
			$query .=" LIMIT 1";
			$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
			if ( $open_status == "未開封" ) {
				$query = "";
				$query .=" SELECT IFNULL(max(A.syorino),0) as syorino ";
				$query .=" FROM php_t_option_zaiko A ";
				$query .=" WHERE A.staff = " . sprintf("'%s'", $staff_name);
				$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (!($rs = $db->query($query))) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				}
				while ($row = $rs->fetch_array()) {
					$op_syorino = $row['syorino'];
				}
				$query = "";
				$query .="INSERT INTO  php_t_option_zaiko ";
				$query .=" ( ";
				$query .= " insdt ";
				$query .= " , upddt ";
				$query .= " , syoridt ";
				$query .= " , updcount ";
				$query .= " , syorino ";
				$query .= " , staff ";
				$query .= " , name ";
				$query .= " , tanka ";
				$query .= " , suryou ";
				$query .= " )VALUES( ";
				$query .= sprintf("'%s'", date('YmdHis'));
				$query .= "," . sprintf("'%s'", date('YmdHis'));
				$query .= "," . sprintf("'%s'", date('YmdHis'));
				$query .= "," . "updcount + 1";
				$query .= "," . sprintf("'%s'", $op_syorino);
				$query .= "," . sprintf("'%s'", $staff_name);
				$query .= "," . sprintf("'%s'", $name);
				$query .= "," . sprintf("'%s'", $op_price);
				$query .= "," . sprintf("%s", 1);
				$query .=" )";
				$comm->ouputlog("データ追加 実行", $prgid, SYS_LOG_TYPE_INFO);
				$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
				if (! $db->query($query)) {
					$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					return false;
				}
			}
			$op_dataCnt++;
			// 初期化
			$name = "";
			$op_code = "";
			$op_price = "";
			$op_receipt = "";
			$b_venueid = "";
			$op_tvenueno = "";
			$open_status = "";
		} else {
			if ( $key == "model". $dataCnt ) {
				$modelnum = $val;
			} else if ( $key == "code". $dataCnt ) {
				$code = $val;
			} else if ( $key == "price". $dataCnt ) {
				$price = $val;
			} else if ( $key == "receipt". $dataCnt ) {
				$receipt = $val;
			} else if ($key == "open" . $op_dataCnt ) {
				$open_status = $val;
			} else if ( $key == "op_model". $op_dataCnt ) {
				$name = $val;
			} elseif ( $key == "op_code". $op_dataCnt ) {
				$op_code = $val;
			} elseif ( $key == "op_price". $op_dataCnt ) {
				$op_price = $val;
			} elseif ( $key == "op_receipt". $op_dataCnt ) {
				$op_receipt = $val;
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
// mysql_henpin_code_del
//
// ■概要
// バーコード不良品登録削除
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_henpin_code_del( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_post;
	global $g_venueno;
	global $g_kbn;

	$comm->ouputlog("mysql_henpin_code_delログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	$performance = mysql_sel_personal( $db);
	//
	$tbl_name_ = "";

	//初期化
	$tvenueid = "";
	$tvenueno = "";

	if ( $g_kbn == "1" ) {
		// ================================================
		// ■　□　■　□　返品　■　□　■　□
		// ================================================
		//----- データ抽出
		$query = "";
		$query .=" SELECT A.tvenueid, A.tvenueno ";
		$query .=" FROM php_t_pc_returned A ";
		$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
		$query .=" AND A.venueno = " . sprintf("'%s'", $g_venueno);
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$tvenueid = $row['tvenueid'];
			$tvenueno = $row['tvenueno'];
		}

		// ================================================
		// ■　□　■　□　ＰＣ販売　■　□　■　□
		// ================================================
		//----- データ抽出
		$query = "";
		$query .=" UPDATE php_t_pc_hanbai A ";
		$query .=" SET A.henpinflg = '0' ";
		$query .=" WHERE A.venueid = " . sprintf("'%s'", $tvenueid);
		$query .=" AND A.venueno = " . sprintf("'%s'", $tvenueno);
		$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}

		// ================================================
		// ■　□　■　□　ＰＣ返品削除　■　□　■　□
		// ================================================
		$query = "";
		$query .=" DELETE FROM php_t_pc_returned ";
		$query .=" WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$query .=" AND venueno = " . sprintf("'%s'", $g_venueno);
		$comm->ouputlog("データ削除 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	} else if ( $g_kbn == "2" ) {
		// ================================================
		// ■　□　■　□　備品返品　■　□　■　□
		// ================================================
		//----- データ抽出
		$query = "";
		$query .=" SELECT A.tvenueid, A.tvenueno, A.name, A.open_status, B.staff ";
		$query .=" FROM php_t_option_returned A ";
		$query .=" INNER JOIN php_performance B ";
		$query .= " ON concat(DATE_FORMAT(B.buydt , '%Y%m%d' ), LPAD(B.lane,2,'0') ) = substring(A.receipt, 1, 10) ";
		$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
		$query .=" AND A.venueno = " . sprintf("'%s'", $g_venueno);
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$tvenueid = $row['tvenueid'];
			$tvenueno = $row['tvenueno'];
			$open_status = $row['open_status'];
			$option_name = $row['name'];
			$staff_name = $row['staff'];
		}

		//----- データ抽出
		$query = "";
		$query .=" UPDATE php_t_pc_hanbai A ";
		$query .=" SET A.henpinflg = " . sprintf("%s", 0);
		$query .=" WHERE A.venueid = " . sprintf("'%s'", $tvenueid);
		$query .=" AND A.venueno = " . sprintf("'%s'", $tvenueno);
		$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}

		// ================================================
		// ■　□　■　□　備品返品削除　■　□　■　□
		// ================================================
		$query = "";
		$query .=" DELETE FROM php_t_option_returned ";
		$query .=" WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$query .=" AND venueno = " . sprintf("'%s'", $g_venueno);
		$comm->ouputlog("データ削除 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($query)) {
			$comm->ouputlog("☆★☆データ削除エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		if ( $open_status == "未開封" ) {
			// ================================================
			// ■　□　■　□　備品在庫削除　■　□　■　□
			// ================================================
			$query = "";
			$query .=" UPDATE php_t_option_zaiko A ";
			$query .=" SET A.upddt = " . sprintf("'%s'", date('YmdHis'));
			$query .=" AND A.updcount = updcount + 1";
			$query .=" AND A.delflg = " . sprintf("%s", 1);
			$query .=" WHERE A.staff = " . sprintf("'%s'", $staff_name);
			$query .=" AND A.name = " . sprintf("'%s'", $option_name);
			$query .=" AND A.suryou = " . sprintf("%s", 1);
			$query .=" ORDER BY syorino DESC";
			$query .=" LIMIT 1";
			$comm->ouputlog("データ更新 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (! $db->query($query)) {
				$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
				return false;
			}
		}
	}
	$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_barcode_entry_high
//
// ■概要
// バーコードデータ販売実績登録_高規格
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_barcode_entry_high( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_post;
	$comm->ouputlog("mysql_barcode_entry_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "/^end/";
	$arrkey = array("型番","ＣＰＵ","メモリ","対象ＯＳ","仕入単価","分類");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.modelnum, A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_pc_tanka A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.modelnum";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['modelnum'];
		$siirelist[$p_modelnum] = $row['siire'];
		$siiredlist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_pc_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";
	//データ削除（バーコードＴ）
	$b_delete1 = "UPDATE php_t_pc_barcode SET captureflg = 1 ";
	$b_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$b_delete1 .= " ,updcount = updcount + 1";
	$b_delete1 .= " WHERE 1";
	$b_delete2 = " AND receiptno = ";
	$b_delete3 = " AND branch = ";
	$b_delete4 = " AND section = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	//バーコードＴ
	$delete_b1 = $b_delete1;
	$delete_b2 = $b_delete2;
	$delete_b3 = $b_delete3;
	$delete_b4 = $b_delete4;
	$dataCnt = 1;
	$hannum = 0;
	$modelnum = "";
	$tanka = 0;
	$profit = 0;
	$siire = 0;
	$check = 0;
	
	$array_pop = array("1" => "①", "2" => "②", "3" => "③", "4" => "④", "5" => "⑤", "6" => "⑥", "7" => "⑦", "8" => "⑧", "9" => "⑨", "10" => "⑩"
	, "11" => "⑪", "12" => "⑫", "13" => "⑬", "14" => "⑭", "15" => "⑮", "16" => "⑯", "17" => "⑰", "18" => "⑱", "19" => "⑲", "20" => "⑳"
	, "21" => "㉑", "22" => "㉒", "23" => "㉓", "24" => "㉔", "25" => "㉕", "26" => "㉖", "27" => "㉗", "28" => "㉘", "29" => "㉙", "30" => "㉚"
	, "31" => "㉛", "32" => "㉜", "33" => "㉝", "34" => "㉞", "35" => "㉟", "36" => "㊱", "37" => "㊲", "38" => "㊳", "39" => "㊴", "40" => "㊵"
	, "41" => "㊶", "42" => "㊷", "43" => "㊸", "44" => "㊹", "45" => "㊺", "46" => "㊻", "47" => "㊼", "48" => "㊽", "49" => "㊾", "50" => "㊿"
	, "51" => "51", "52" => "52", "53" => "53", "54" => "54", "55" => "55", "56" => "56", "57" => "57", "58" => "58", "59" => "59", "60" => "60"
	, "61" => "61", "62" => "62", "63" => "63", "64" => "64", "65" => "65", "66" => "66", "67" => "67", "68" => "68", "69" => "69", "70" => "70"
	, "71" => "71", "72" => "72", "73" => "73", "74" => "74", "75" => "75", "76" => "76", "77" => "77", "78" => "78", "79" => "79", "80" => "80"
	, "81" => "81", "82" => "82", "83" => "83", "84" => "84", "85" => "85", "86" => "86", "87" => "87", "88" => "88", "89" => "89", "90" => "90"
	, "91" => "91", "92" => "92", "93" => "93", "94" => "94", "95" => "95", "99" => "99", "97" => "97", "98" => "98", "99" => "99", "100" => "100", "999" => "999", "0" => ""
	);

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match($end,$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" modelnum=" . $modelnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" tanka=" . $tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" han_suryou=" . $han_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" gre_suryou=" . $gre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mre_suryou=" . $mre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" check=" . $check, $prgid, SYS_LOG_TYPE_DBUG);
			$suryou = $han_suryou + $gre_suryou;
			$comm->ouputlog(" suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
			//対象がPCの場合のみfunctionを実行
			if(($kbn == '1' || $kbn == '4' || $kbn == '5')&& $check == 1){
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $tanka - $siirelist[$modelnum];
				} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
					$_sql =" SELECT A.profit ";
					$_sql .=" FROM php_system A ";
					$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($_sql))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$profit = $row['profit'];
					}
				}else{ //代理店以外
					$profit = $profitlist[$modelnum];
				}
				//仕入単価
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$siire = $siiredlist[$modelnum];
				}else{ //代理店以外
					$siire = $siirelist[$modelnum];
				}
				//チェックがある場合、登録
				for($i=0; $i<$suryou; ++$i){
					//販売
					// ================================================
					// ■　□　■　□　在庫Ｔ検索　■　□　■　□
					// ================================================
					//会場No取得
/*					$select =  " SELECT A.syoridt , A.syorino ";
					$select .= " FROM php_t_pc_zaiko A ";
					$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
					$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
					$select .= " AND A.hanbaiflg = 0";
					$select .= " AND A.delflg = 0";
					$select .= " ORDER BY A.syoridt , A.syorino ";
					$select .= " LIMIT 0 , " . $suryou;
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($select))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
*/						$syoridt = 0;
						$syorino = 0;
						// ================================================
						// ■　□　■　□　バーコードＴ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, IFNULL(C.memo, '') as remarks, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " LEFT OUTER JOIN php_performance D ON B.venueid=CONCAT(DATE_FORMAT(D.buydt , '%Y%m%d' ), LPAD(D.lane,2,'0') , '-' , D.branch)";
						$select2 .= " LEFT OUTER JOIN php_pc_price_team C ON A.modelnum=C.modelnum AND A.g_pop=C.pop AND A.tanka=C.tanka AND D.week=C.week AND D.staff=C.staff ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND (A.hannum>0 OR A.c_grenum>0)";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						if ($rs2->num_rows == 0) {
							$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, IFNULL(C.memo, '') as remarks, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
							$select2 .= " FROM php_t_pc_barcode A ";
							$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
							$select2 .= " LEFT OUTER JOIN php_performance D ON B.venueid=CONCAT(DATE_FORMAT(D.buydt , '%Y%m%d' ), LPAD(D.lane,2,'0') , '-' , D.branch)";
							$select2 .= " LEFT OUTER JOIN php_pc_price_team C ON A.modelnum=C.modelnum AND A.g_pop=C.pop AND A.tanka=C.tanka AND D.week=C.week AND D.staff=C.staff ";
							$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
							$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
							$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
							$select2 .= " AND A.captureflg = 0";
							$select2 .= " AND A.delflg = 0";
							$select2 .= " AND (A.hannum>0 OR A.c_grenum>0)";
							$select2 .= " AND B.section = " . sprintf("'%s'", $section);
							$select2 .= " ORDER BY B.syoridt , B.venueno ";
							$select2 .= " LIMIT 0 , 1";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($select2))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
						while ($row2 = $rs2->fetch_array()) {
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["部"];
							$insert2 .= "," . $section;
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", $syoridt);
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", $syorino);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . $profit;
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["バーコード番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
							$insert1 .= "," . $collist["レシート番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
							$insert1 .= "," . $collist["バーコードフラグ"];
							$insert2 .= "," . 1;
							$insert1 .= "," . $collist["販売数量"];
							$insert2 .= "," . sprintf("'%s'", $row2['hannum']);
							$insert1 .= "," . $collist["現物予約数量"];
							$insert2 .= "," . sprintf("'%s'", $row2['c_grenum']);
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							$insert1 .= "," . $collist["paypayフラグ"];
							$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
							$insert1 .= "," . $collist["バーコード処理日時"];
							$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
							$insert1 .= "," . $collist["見本備考"];
							$insert2 .= "," . sprintf("'%s'", $array_pop[$row2['g_pop']].$row2['remarks']);
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
							//バーコードデータの消込処理
							// ================================================
							// ■　□　■　□　バーコードＴ削除　■　□　■　□
							// ================================================
							$delete_b = $delete_b1;
							$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
							$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete_b)) {
								$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
							//在庫の消込処理
							// ================================================
							// ■　□　■　□　在庫Ｔ削除　■　□　■　□
							// ================================================
/*							$delete = $delete1;
							$delete .= $delete2 . sprintf("'%s'", $syoridt);
							$delete .= $delete3 . sprintf("'%s'", $syorino);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete)) {
								$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===在庫データ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
*/					}
				}
				//見本予約　チェックがある場合、登録
				for($i=0; $i<$mre_suryou; ++$i){
					// ================================================
					// ■　□　■　□　バーコードＴ検索　■　□　■　□
					// ================================================
					//会場No取得
					$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, IFNULL(C.memo, '') as remarks, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
					$select2 .= " FROM php_t_pc_barcode A ";
					$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
					$select2 .= " LEFT OUTER JOIN php_performance D ON B.venueid=CONCAT(DATE_FORMAT(D.buydt , '%Y%m%d' ), LPAD(D.lane,2,'0') , '-' , D.branch)";
					$select2 .= " LEFT OUTER JOIN php_pc_price_team C ON A.modelnum=C.modelnum AND A.g_pop=C.pop AND A.tanka=C.tanka AND D.week=C.week AND D.staff=C.staff ";
					$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
					$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
					$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
					$select2 .= " AND A.captureflg = 0";
					$select2 .= " AND A.delflg = 0";
					$select2 .= " AND A.c_mrenum > 0";
					$select2 .= " AND B.section = " . sprintf("'%s'", $section);
					$select2 .= " ORDER BY B.syoridt , B.venueno ";
					$select2 .= " LIMIT 0 , 1";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs2 = $db->query($select2))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					if ($rs2->num_rows == 0) {
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, A.g_pop, IFNULL(C.memo, '') as remarks, B.syoridt, A.hannum, A.c_mrenum, A.c_grenum ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " LEFT OUTER JOIN php_performance D ON B.venueid=CONCAT(DATE_FORMAT(D.buydt , '%Y%m%d' ), LPAD(D.lane,2,'0') , '-' , D.branch)";
						$select2 .= " LEFT OUTER JOIN php_pc_price_team C ON A.modelnum=C.modelnum AND A.g_pop=C.pop AND A.tanka=C.tanka AND D.week=C.week AND D.staff=C.staff ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND A.c_mrenum > 0";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
					}
					while ($row2 = $rs2->fetch_array()) {
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["部"];
						$insert2 .= "," . $section;
						$insert1 .= "," . $collist["処理日付"];
						$insert2 .= "," . sprintf("'%s'", $syoridt);
						$insert1 .= "," . $collist["処理ＮＯ"];
						$insert2 .= "," . sprintf("'%s'", $syorino);
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["バーコード番号"];
						$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
						$insert1 .= "," . $collist["レシート番号"];
						$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
						$insert1 .= "," . $collist["バーコードフラグ"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . sprintf("'%s'", $row2['c_mrenum']);
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . sprintf("'%s'", $kbn);
						$insert1 .= "," . $collist["paypayフラグ"];
						$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
						$insert1 .= "," . $collist["バーコード処理日時"];
						$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $array_pop[$row2['g_pop']].$row2['remarks']);
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
						//バーコードデータの消込処理
						// ================================================
						// ■　□　■　□　バーコードＴ削除　■　□　■　□
						// ================================================
						$delete_b = $delete_b1;
						$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
						$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
						$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
						//データ削除実行
						if (! $db->query($delete_b)) {
							$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			//バーコードＴ
			$delete_b1 = $b_delete1;
			$delete_b2 = $b_delete2;
			$delete_b3 = $b_delete3;
			$delete_b4 = $b_delete4;
			$dataCnt++;
			$hannum = 0;
			$tanka = 0;
			$profit = 0;
			$siire = 0;
			$kbn = "";
			$check = 0;
		}else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}else{
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if(preg_match("/^$arrkey[$cnt]/",$key)){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if($length > 0 && strlen($val) > $length){
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}else{
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if(preg_match("/^型番/",$key)){
				$modelnum = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^連携/",$key)){
				$check = $val;
			}
			elseif(preg_match("/^単価/",$key)){
				$tanka = $val;
			}
			elseif(preg_match("/^仕入単価/",$key)){
				$siire = $val;
			}
			elseif(preg_match("/^販売数量/",$key)){
				$han_suryou = $val;
			}
			elseif(preg_match("/^現物予約/",$key)){
				$gre_suryou = $val;
			}
			elseif(preg_match("/^見本予約/",$key)){
				$mre_suryou = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
			}
			elseif(preg_match("/^部/",$key)){
				$section = $val;
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
// mysql_barcode_entry_option_high
//
// ■概要
// 周辺機器バーコードデータ販売実績登録_高規格
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_barcode_entry_option_high( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_post;
	$comm->ouputlog("mysql_barcode_entry_option_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table);
	//
	$tbl_name_ = "";

	//項目名
	$end = "/^b_end/";
	$arrkey = array("仕入単価");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.name, A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_option_info A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.name";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['name'];
		$siirelist[$p_modelnum] = $row['siire'];
		$siiredlist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//データ削除（在庫Ｔ）
	$m_delete1 = "UPDATE php_t_option_zaiko SET hanbaiflg = 1 ";
	$m_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$m_delete1 .= " ,updcount = updcount + 1";
	$m_delete1 .= " WHERE staff = " . sprintf("'%s'", $performance[staff]);
	$m_delete2 = " AND syoridt = ";
	$m_delete3 = " AND syorino = ";
	//データ削除（バーコードＴ）
	$b_delete1 = "UPDATE php_t_pc_barcode SET captureflg = 1 ";
	$b_delete1 .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
	$b_delete1 .= " ,updcount = updcount + 1";
	$b_delete1 .= " WHERE 1";
	$b_delete2 = " AND receiptno = ";
	$b_delete3 = " AND branch = ";
	$b_delete4 = " AND section = ";

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	//在庫Ｔ
	$delete1 = $m_delete1;
	$delete2 = $m_delete2;
	$delete3 = $m_delete3;
	//バーコードＴ
	$delete_b1 = $b_delete1;
	$delete_b2 = $b_delete2;
	$delete_b3 = $b_delete3;
	$delete_b4 = $b_delete4;
	$dataCnt = 101;
	$hannum = 0;
	$modelnum = "";
	$tanka = 0;
	$profit = 0;
	$siire = 0;
	$check = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match($end,$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" modelnum=" . $modelnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" tanka=" . $tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" han_suryou=" . $han_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" gre_suryou=" . $gre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mre_suryou=" . $mre_suryou, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" check=" . $check, $prgid, SYS_LOG_TYPE_DBUG);
			$suryou = $han_suryou + $gre_suryou;
			$comm->ouputlog(" suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
			//対象が周辺機器の場合のみfunctionを実行
			if($kbn > 1 && $check == 1){
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $tanka - $siirelist[$modelnum];
				} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
					$_sql =" SELECT A.profit ";
					$_sql .=" FROM php_system A ";
					$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($_sql))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$profit = $row['profit'];
					}
				}else{ //代理店以外
					$profit = $profitlist[$modelnum];
				}
				//仕入単価
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$siire = $siiredlist[$modelnum];
				}else{ //代理店以外
					$siire = $siirelist[$modelnum];
				}
				//チェックがある場合、登録
				for($i=0; $i<$suryou; ++$i){
					if($kbn == 2 || $kbn == 6){
						//販売
						// ================================================
						// ■　□　■　□　在庫Ｔ検索　■　□　■　□
						// ================================================
/*						//会場No取得
						$select =  " SELECT A.syoridt , A.syorino ";
						$select .= " FROM php_t_pc_hanbai_high A ";
						$select .= " WHERE A.staff = " . sprintf("'%s'", $performance[staff]);
						$select .= " AND A.name = " . sprintf("'%s'", $modelnum);
						$select .= " AND A.hanbaiflg = 0";
						$select .= " AND A.delflg = 0";
						$select .= " ORDER BY A.syoridt , A.syorino ";
						$select .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs = $db->query($select))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						while ($row = $rs->fetch_array()) {
*/							$syoridt = 0;
							$syorino = 0;
							// ================================================
							// ■　□　■　□　バーコードＴ検索　■　□　■　□
							// ================================================
							//会場No取得
							$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
							$select2 .= " FROM php_t_pc_barcode A ";
							$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
							$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
							$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
							$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
							$select2 .= " AND A.captureflg = 0";
							$select2 .= " AND A.delflg = 0";
							$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
							$select2 .= " AND B.section = " . sprintf("'%s'", $section);
							$select2 .= " ORDER BY B.syoridt , B.venueno ";
							$select2 .= " LIMIT 0 , 1";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($select2))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							if ($rs2->num_rows == 0) {
								$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
								$select2 .= " FROM php_t_pc_barcode A ";
								$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
								$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
								$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
								$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
								$select2 .= " AND A.captureflg = 0";
								$select2 .= " AND A.delflg = 0";
								$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
								$select2 .= " AND B.section = " . sprintf("'%s'", $section);
								$select2 .= " ORDER BY B.syoridt , B.venueno ";
								$select2 .= " LIMIT 0 , 1";
								$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
								$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
								if (!($rs2 = $db->query($select2))) {
									$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								}
							}
							while ($row2 = $rs2->fetch_array()) {
								// ================================================
								// ■　□　■　□　販売Ｔ登録　■　□　■　□
								// ================================================
								//会場ＮＯ
								$venueno++;
								if (is_null($profit)) {
									$profit = 0;
								}
								//ＳＱＬ文結合
								$insert = $query;
								$insert1 = $query1;
								$insert2 = $query2;
								$insert1 .= "," . $collist["会場ＮＯ"];
								$insert2 .= "," . $venueno;
								$insert1 .= "," . $collist["部"];
								$insert2 .= "," . $section;
								$insert1 .= "," . $collist["区分"];
								$insert2 .= "," . sprintf("'%s'", $kbn);
								$insert1 .= "," . $collist["型番"];
								$insert2 .= "," . sprintf("'%s'", $modelnum);
								$insert1 .= "," . $collist["処理日付"];
								$insert2 .= "," . sprintf("'%s'", $syoridt);
								$insert1 .= "," . $collist["処理ＮＯ"];
								$insert2 .= "," . sprintf("'%s'", $syorino);
								$insert1 .= "," . $collist["利益"];
								$insert2 .= "," . $profit;
								$insert1 .= "," . $collist["単価"];
								$insert2 .= "," . $tanka;
								$insert1 .= "," . $collist["バーコード番号"];
								$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
								$insert1 .= "," . $collist["レシート番号"];
								$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
								$insert1 .= "," . $collist["バーコードフラグ"];
								$insert2 .= "," . 1;
								$insert1 .= "," . $collist["paypayフラグ"];
								$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
								$insert1 .= "," . $collist["バーコード処理日時"];
								$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . sprintf("'%s'", $row2['hannum']);
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . sprintf("'%s'", $row2['c_grenum']);
								//ＳＱＬ文結合
								$insert1 .= ")";
								$insert2 .= ")";
								$insert .= $insert1 . $insert2;
								$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
								$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

								//データ追加実行
								if (! $db->query($insert)) {
									$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									return false;
								}
								$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
								//バーコードデータの消込処理
								// ================================================
								// ■　□　■　□　バーコードＴ削除　■　□　■　□
								// ================================================
								$delete_b = $delete_b1;
								$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
								$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
								$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
								$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
								//データ削除実行
								if (! $db->query($delete_b)) {
									$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									return false;
								}
								$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
								//在庫の消込処理
								// ================================================
								// ■　□　■　□　在庫Ｔ削除　■　□　■　□
								// ================================================
						/*		$delete = $delete1;
								$delete .= $delete2 . sprintf("'%s'", $syoridt);
								$delete .= $delete3 . sprintf("'%s'", $syorino);
								$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
								$comm->ouputlog($delete, $prgid, SYS_LOG_TYPE_DBUG);
								//データ削除実行
								if (! $db->query($delete)) {
									$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
									return false;
								}
								$comm->ouputlog("===在庫データ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
							}
*/						}
					}else if($kbn == 3){
						// ================================================
						// ■　□　■　□　バーコードＴ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						if ($rs2->num_rows == 0) {
							$select2 =  " SELECT A.g_code , A.receiptno, A.branch ";
							$select2 .= " FROM php_t_pc_barcode A ";
							$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
							$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
							$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
							$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
							$select2 .= " AND A.captureflg = 0";
							$select2 .= " AND A.delflg = 0";
							$select2 .= " AND (A.hannum > 0 OR A.c_grenum > 0)";
							$select2 .= " AND B.section = " . sprintf("'%s'", $section);
							$select2 .= " ORDER BY B.syoridt , B.venueno ";
							$select2 .= " LIMIT 0 , 1";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($select2))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
						while ($row2 = $rs2->fetch_array()) {
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["部"];
							$insert2 .= "," . $section;
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $modelnum);
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", $syoridt);
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", $syorino);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . $profit;
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["バーコード番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
							$insert1 .= "," . $collist["バーコードフラグ"];
							$insert2 .= "," . 1;
							$insert1 .= "," . $collist["販売数量"];
							$insert2 .= "," . 1;
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
							//バーコードデータの消込処理
							// ================================================
							// ■　□　■　□　バーコードＴ削除　■　□　■　□
							// ================================================
							$delete_b = $delete_b1;
							$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
							$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete_b)) {
								$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					}
				}
				//チェックがある場合、登録
				for($i=0; $i<$mre_suryou; ++$i){
					if($kbn == 2 || $kbn == 3 || $kbn == 6){
						// ================================================
						// ■　□　■　□　バーコードＴ検索　■　□　■　□
						// ================================================
						//会場No取得
						$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
						$select2 .= " FROM php_t_pc_barcode A ";
						$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
						$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
						$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
						$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
						$select2 .= " AND A.captureflg = 0";
						$select2 .= " AND A.delflg = 0";
						$select2 .= " AND A.c_mrenum > 0";
						$select2 .= " AND B.section = " . sprintf("'%s'", $section);
						$select2 .= " ORDER BY B.syoridt , B.venueno ";
						$select2 .= " LIMIT 0 , 1";
						$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
						$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
						if (!($rs2 = $db->query($select2))) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
						}
						if ($rs2->num_rows == 0) {
							$select2 =  " SELECT A.g_code , A.receiptno, A.branch, B.p_flg, B.syoridt, A.hannum, A.c_grenum, A.c_mrenum  ";
							$select2 .= " FROM php_t_pc_barcode A ";
							$select2 .= " LEFT OUTER JOIN php_t_pc_receipt B ON A.receiptno=concat(B.venueid, B.resisterno, LPAD(B.venueno,3,'0')) ";
							$select2 .= " WHERE B.venueid = " . sprintf("'%s'", $performance[venueid]);
							$select2 .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
							$select2 .= " AND A.tanka = " . sprintf("'%s'", $tanka);
							$select2 .= " AND A.captureflg = 0";
							$select2 .= " AND A.delflg = 0";
							$select2 .= " AND A.c_mrenum > 0";
							$select2 .= " AND B.section = " . sprintf("'%s'", $section);
							$select2 .= " ORDER BY B.syoridt , B.venueno ";
							$select2 .= " LIMIT 0 , 1";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($select2, $prgid, SYS_LOG_TYPE_DBUG);
							if (!($rs2 = $db->query($select2))) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
						}
						while ($row2 = $rs2->fetch_array()) {
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["部"];
							$insert2 .= "," . $section;
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $modelnum);
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", date('Y-m-d H:i:s'));
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", 0);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . $profit;
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . $tanka;
							$insert1 .= "," . $collist["バーコード番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['g_code']);
							$insert1 .= "," . $collist["レシート番号"];
							$insert2 .= "," . sprintf("'%s'", $row2['receiptno']);
							$insert1 .= "," . $collist["バーコードフラグ"];
							$insert2 .= "," . 1;
							$insert1 .= "," . $collist["paypayフラグ"];
							$insert2 .= "," . sprintf("'%s'", $row2['p_flg']);
							$insert1 .= "," . $collist["バーコード処理日時"];
							$insert2 .= "," . sprintf("'%s'", $row2['syoridt']);
							$insert1 .= "," . $collist["見本予約数量"];
							$insert2 .= "," . sprintf("'%s'", $row2['c_mrenum']);
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
							//バーコードデータの消込処理
							// ================================================
							// ■　□　■　□　バーコードＴ削除　■　□　■　□
							// ================================================
							$delete_b = $delete_b1;
							$delete_b .= $delete_b2 . sprintf("'%s'", $row2['receiptno']);
							$delete_b .= $delete_b3 . sprintf("'%s'", $row2['branch']);
							$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($delete_b, $prgid, SYS_LOG_TYPE_DBUG);
							//データ削除実行
							if (! $db->query($delete_b)) {
								$comm->ouputlog("☆★☆在庫データ消込エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===バーコードデータ消込完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			//バーコードＴ
			$delete_b1 = $b_delete1;
			$delete_b2 = $b_delete2;
			$delete_b3 = $b_delete3;
			$delete_b4 = $b_delete4;
			$dataCnt++;
			$hannum = 0;
			$tanka = 0;
			$profit = 0;
			$siire = 0;
			$kbn = "";
			$check = 0;
		}
		if(preg_match("/^end/",$key)){
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			//在庫Ｔ
			$delete1 = $m_delete1;
			$delete2 = $m_delete2;
			$delete3 = $m_delete3;
			//バーコードＴ
			$delete_b1 = $b_delete1;
			$delete_b2 = $b_delete2;
			$delete_b3 = $b_delete3;
			$delete_b4 = $b_delete4;
			$dataCnt++;
			$hannum = 0;
			$tanka = 0;
			$profit = 0;
			$siire = 0;
			$kbn = "";
			$check = 0;
		}else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}else{
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if(preg_match("/^$arrkey[$cnt]/",$key)){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if($length > 0 && strlen($val) > $length){
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}else{
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if(preg_match("/^備品品名/",$key)){
				$modelnum = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^連携/",$key)){
				$check = $val;
			}
			elseif(preg_match("/^備品単価/",$key)){
				$tanka = $val;
			}
			elseif(preg_match("/^仕入単価/",$key)){
				$siire = $val;
			}
			elseif(preg_match("/^備品数量/",$key)){
				$han_suryou = $val;
			}
			elseif(preg_match("/^備品現物/",$key)){
				$gre_suryou = $val;
			}
			elseif(preg_match("/^備品見本/",$key)){
				$mre_suryou = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
			}
			elseif(preg_match("/^部/",$key)){
				$section = $val;
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
// mysql_entry_hanbai_high
//
// ■概要
// 販売実績登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_entry_hanbai_high( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_high;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_entry_hanbai_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table_high);
	//
	$tbl_name_ = "";

	//項目名
	$end = "end";
	$arrkey = array("メーカー名","型番","ＣＰＵ","メモリ","対象ＯＳ","仕入単価","分類");

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query = "";
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table_high . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}
	//処理No取得
	$query = "";
	$query .=" SELECT max(IFNULL(A.syorino,0)) as syorino ";
	$query .=" FROM " . $table_high . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$syorino = $row['syorino'];
	}
	$syoridt = date('Ymd');

	// ================================================
	// ■　□　■　□　ＰＣ単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.modelnum , A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_pc_tanka A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.modelnum";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
	while ($row = $rs->fetch_array()) {
		$p_modelnum = $row['modelnum'];
		$siirelist[$p_modelnum] = $row['siire'];
		$siiredlist[$p_modelnum] = $row['siire_d'];
		$profitlist[$p_modelnum] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table_high, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table_high;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	$dataCnt = 1;
	$hannum = 0;
	$grenum = 0;
	$mrenum = 0;
	$rentnum = 0;
	$rentmrenum = 0;
	$rentgrenum = 0;
	$makrname = "";
	$modelnum = "";
	$cpu = "";
	$memory = "";
	$tanka = 0;
	$profit = 0;
	$siire = 0;
	$deleteflg = 0;
	$desktop = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match("/^end/",$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" tanka=" . $tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_tanka=" . $t_tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" hannum=" . $hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_hannum=" . $t_hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" grenum=" . $grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_grenum=" . $t_grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mrenum=" . $mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_mrenum=" . $t_mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" rentnum=" . $rentnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_rentnum=" . $t_rentnum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" rentgrenum=" . $rentgrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_rentgrenum=" . $t_rentgrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" rentmrenum=" . $rentmrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_rentmrenum=" . $t_rentmrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" remarks=" . $remarks, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_remarks=" . $t_remarks, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			//対象がPCの場合のみfunctionを実行
			if($kbn == '1' || $kbn == '5'){ //販売または下取
				//利益
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$profit = $tanka - $siirelist[$modelnum];
				} elseif ($_COOKIE['con_perf_mana'] == 2 || $_COOKIE['con_perf_mana'] == 3) { //代理店以外
					$_sql =" SELECT A.profit ";
					$_sql .=" FROM php_system A ";
					$_sql .=" WHERE A.Manager = '".$_COOKIE['con_perf_mana']."'";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_sql, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($_sql))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$profit = $row['profit'];
					}
				}else{ //代理店以外
					$profit = $profitlist[$modelnum];
				}
				//仕入単価
				if ($_COOKIE['con_perf_mana'] == 1) { //代理店
					$siire = $siiredlist[$modelnum];
				}else{ //代理店以外
					$siire = $siirelist[$modelnum];
				}
				//販売・現物予約数合算
				$suryou = 0;
				//数計算
				$hannum -= $t_hannum;
				$grenum -= $t_grenum;
				$mrenum -= $t_mrenum;
				$rentnum -= $t_rentnum;
				$rentmrenum -= $t_rentmrenum;
				$rentgrenum -= $t_rentgrenum;
				if ($hannum < 0) {
					//販売削除
					mysql_del_hanbai_high($db, $g_section, $modelnum, $t_tanka, $hannum * -1, "hannum", $desktop, $t_remarks, $kbn);
					//変数初期化
					$hannum = 0;
				}
				if ($grenum < 0) {
					//販売削除
					mysql_del_hanbai_high($db, $g_section, $modelnum, $t_tanka, $grenum * -1, "grenum", $desktop, $t_remarks, $kbn);
					//変数初期化
					$grenum = 0;
				}
				if ($mrenum < 0) {
					//販売削除
					mysql_del_hanbai_high($db, $g_section, $modelnum, $t_tanka, $mrenum * -1, "mrenum", $desktop, $t_remarks, $kbn);
					//変数初期化
					$mrenum = 0;
				}
				if ($rentnum < 0) {
					//販売削除
					mysql_del_hanbai_high($db, $g_section, $modelnum, $t_tanka, $rentnum * -1, "hannum", $desktop, $t_remarks, $kbn+3);
					//変数初期化
					$rentnum = 0;
				}
				if ($rentgrenum < 0) {
					//販売削除
					mysql_del_hanbai_high($db, $g_section, $modelnum, $t_tanka, $rentgrenum * -1, "grenum", $desktop, $t_remarks, $kbn+3);
					//変数初期化
					$rentgrenum = 0;
				}
				if ($rentmrenum < 0) {
					//販売削除
					mysql_del_hanbai_high($db, $g_section, $modelnum, $t_tanka, $rentmrenum * -1, "mrenum", $desktop, $t_remarks, $kbn+3);
					//変数初期化
					$rentmrenum = 0;
				}
				$suryou = $hannum + $grenum + $mrenum + $rentnum + $rentgrenum + $rentmrenum;
				//単価が異なる場合、単価更新
				if ($tanka <> $t_tanka) {
					//単価更新
					mysql_entry_change_high($db, $g_section, $modelnum, $t_tanka, $tanka, $t_remarks, $remarks, $profit, "tanka", $kbn);
				}
				//備考が異なる場合、備考更新
				if ($remarks <> $t_remarks) {
					//単価更新
					mysql_entry_change_high($db, $g_section, $modelnum, $t_tanka, $tanka, $t_remarks, $remarks, $profit, "remarks", $kbn);
				}
				$suryou = $hannum + $grenum + $rentnum + $rentgrenum;
				$m_suryou = $mrenum + $rentmrenum;
				//チェックがある場合、登録
				if($suryou > 0 && $tanka > 0) {
					//通常の販売・現物
					for($i=0; $i<$hannum; ++$i){
						++$syorino;
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["処理日付"];
						$insert2 .= "," . sprintf("'%s'", $syoridt);
						$insert1 .= "," . $collist["処理ＮＯ"];
						$insert2 .= "," . sprintf("'%s'", $syorino);
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $remarks);
						//販売
						$insert1 .= "," . $collist["販売数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . $kbn;
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ(現物)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
					//現物
					for($i=0; $i<$grenum; ++$i){
						++$syorino;
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["処理日付"];
						$insert2 .= "," . sprintf("'%s'", $syoridt);
						$insert1 .= "," . $collist["処理ＮＯ"];
						$insert2 .= "," . sprintf("'%s'", $syorino);
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $remarks);
						$insert1 .= "," . $collist["現物予約数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . $kbn;
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ(現物)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
				//見本予約の場合
				if($mrenum > 0 && $tanka > 0){
					for($i=0; $i<$mrenum; ++$i){
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						++$syorino;
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $remarks);
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . $kbn;
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ(見本)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}if($rentmrenum > 0){
					for($i=0; $i<$rentmrenum; ++$i){
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						if (is_null($profit)) {
							$profit = 0;
						}
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . $profit;
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . $tanka;
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["見本備考"];
						$insert2 .= "," . sprintf("'%s'", $remarks);
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . 4;
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						$comm->ouputlog("===データ追加ＳＱＬ(レンタル)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			$dataCnt++;
			$hannum = 0;
			$grenum = 0;
			$mrenum = 0;
			$rentnum = 0;
			$rentgrenum = 0;
			$rentmrenum = 0;
			$tanka = 0;
			$t_tanka = 0;
			$remarks = 0;
			$t_remarks = 0;
			$tanka2 = 0;
			$t_tanka2 = 0;
			$profit = 0;
			$siire = 0;
			$desktop = 0;
			$kbn = "";
		}else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}elseif(preg_match("/^レンタル/",$key)){
				$rentnum = $val;
			}elseif(preg_match("/^貸出現物/",$key)){
				$rentgrenum = $val;
			}elseif(preg_match("/^貸出予約/",$key)){
				$rentmrenum = $val;
			}else{
				//テーブル項目情報取得
				//対象データ選定
				for ($cnt = 0; $cnt < count($arrkey); $cnt++){
					if(preg_match("/^$arrkey[$cnt]/",$key)){
						$row = $collist[$arrkey[$cnt]];
						break;
					}
				}
				//対象項目が存在しない場合は登録対象外
				if($row != "") {
					//入力値出力
					$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
					//項目設定
					$query1 .= "," . $row;
					$val = str_replace("選択してください　", "", $val);
					//入力内容チェック
					if ($length > 0 && strlen($val) > $length) {
							$query2 .= sprintf(",'%s'", mb_substr($val,0,floor($length / 3), "UTF-8"));
					}
					else {
						$query2 .= sprintf(",'%s'", $val);
					}
				}
			}
			if(preg_match("/^型番/",$key)){
				$modelnum = $val;
			}
			//処理日付情報
			elseif(preg_match("/^処理日付/",$key)){
				$syoridt = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^処理ＮＯ/",$key)){
				$syorino = $val;
			}
			elseif(preg_match("/^Ｔ単価/",$key)){
				$t_tanka = $val;
			}
			elseif(preg_match("/^単価/",$key)){
				$tanka = $val;
			}
			elseif(preg_match("/^Ｔ販売/",$key)){
				$t_hannum = $val;
			}
			elseif(preg_match("/^Ｔ現物/",$key)){
				$t_grenum = $val;
			}
			elseif(preg_match("/^Ｔ見本/",$key)){
				$t_mrenum = $val;
			}
			elseif(preg_match("/^Ｔレンタル/",$key)){
				$t_rentnum = $val;
			}
			elseif(preg_match("/^Ｔ貸出現物/",$key)){
				$t_rentgrenum = $val;
			}
			elseif(preg_match("/^Ｔ貸出予約/",$key)){
				$t_rentmrenum = $val;
			}
			elseif(preg_match("/^Ｔ備考/",$key)){
				$t_remarks = $val;
			}
			elseif(preg_match("/^備考/",$key)){
				$remarks = $val;
			}
			elseif(preg_match("/^デスクトップ/",$key)){
				$desktop = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
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
// mysql_del_hanbai_high
//
// ■概要
// 販売情報消込
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_hanbai_high( $db, $g_section, $modelnum, $tanka, $num, $numflg, $desktop, $t_remarks, $kbn) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_high;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;

	$comm->ouputlog("mysql_del_hanbai_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("kbn=".$kbn, $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai_high A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
	$select .= " AND A.tanka = " . sprintf("'%s'", $tanka);
	$select .= " AND A.remarks = " . sprintf("'%s'", $t_remarks);
	$select .= " AND A.henpinflg = 0";
	$select .= " AND A.telhenflg = 0";
	$select .= " AND A.delflg = 0";
	$select .= " AND A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " AND A." . $numflg . " > 0";
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$select .= " LIMIT 0 , " . $num;
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($select))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai_high SET delflg = 1 ";
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ削除実行
		$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($_delete)) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_entry_change_high
//
// ■概要
// 単価変更/オプション変更
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_entry_change_high( $db, $g_section, $modelnum, $t_tanka, $tanka, $t_remarks, $remarks, $profit, $column, $kbn) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_high;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;

	$comm->ouputlog("mysql_entry_change_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);
	$comm->ouputlog("kbn=".$kbn, $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai_high A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $modelnum);
	$select .= " AND A.tanka = " . sprintf("'%s'", $t_tanka);
	$select .= " AND A.remarks = " . sprintf("'%s'", $t_remarks);
	if($column <> "remarks"){
		$select .= " AND A.henpinflg = 0";
		$select .= " AND A.telhenflg = 0";
	}
	$select .= " AND A.delflg = 0";
	$select .= " AND (A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " OR A.kbn = " . sprintf("'%s'", $kbn + 3).")";
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($select))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai_high SET ".$column." = " . sprintf("'%s'", ${$column});
		if($column == 'tanka'){
			$_delete .= " ,profit = " . sprintf("'%s'", $profit);
		}
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ削除実行
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($_delete)) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$comm->ouputlog("===データ更新完了===", $prgid, SYS_LOG_TYPE_DBUG);
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_entry_option_high
//
// ■概要
// オプション販売実績登録
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_entry_option_high( $db) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_high;
	//対象プログラム
	global $prgid;
	//引数
	global $g_staff;
	global $g_section;
	global $g_post;
	$comm->ouputlog("mysql_entry_option_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//テーブル項目取得
	$collist = $dba->mysql_get_collist($db, $table_high);
	//
	$tbl_name_ = "";

	//項目名
	$end = "end";

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);

	//会場No取得
	$query = "";
	$query .=" SELECT max(IFNULL(A.venueno,0)) as venueno ";
	$query .=" FROM " . $table_high . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
	}
	//処理No取得
	$query = "";
	$query .=" SELECT max(IFNULL(A.syorino,0)) as syorino ";
	$query .=" FROM " . $table_high . " A ";
	$query .=" WHERE A.venueid = " . sprintf("'%s'", $performance[venueid]);
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$syorino = $row['syorino'];
	}
	$syoridt = date('Ymd');

	// ================================================
	// ■　□　■　□　オプション単価取得　■　□　■　□
	// ================================================
	//----- データ抽出
	$query = "";
	$query .=" SELECT A.name , A.siire, A.siire_d, A.profit ";
	$query .=" FROM php_option_info A ";
	$query .=" WHERE A.delflg = 0";
	$query .=" ORDER BY A.name";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	//配列初期化
	$siirelist = "";
	$siiredlist = "";
	$profitlist = "";
	while ($row = $rs->fetch_array()) {
		$p_name = $row['name'];
		$siirelist[$p_name] = $row['siire'];
		$siiredlist[$p_name] = $row['siire_d'];
		$profitlist[$p_name] = $row['profit'];
	}

	//データ追加
	$comm->ouputlog("===データ追加=== 対象TBL=" . $table_high, $prgid, SYS_LOG_TYPE_INFO);
	$m_query = "INSERT INTO " . $table_high;
	//初期値設定
	$m_query1 = " ( ";
	$m_query2 = " VALUES ( ";
	//登録日時
	$m_query1 .= $collist["登録日時"];
	$m_query2 .= sprintf("'%s'", date('YmdHis'));
	//更新日時
	$m_query1 .= "," . $collist["更新日時"];
	$m_query2 .= sprintf(",'%s'", date('YmdHis'));
	//問合せ状況
	$m_query1 .= "," . $collist["更新回数"];
	$m_query2 .= "," . "1";
	//会場ＩＤ
	$m_query1 .= "," . $collist["会場ＩＤ"];
	$m_query2 .= sprintf(",'%s'", $performance[venueid]);
	//部
	$m_query1 .= "," . $collist["部"];
	$m_query2 .= "," . $g_section;

	//初期化
	//販売Ｔ
	$query = $m_query;
	$query1 = $m_query1;
	$query2 = $m_query2;
	$optionCnt = 0;
	$dataCnt = 1;
	$hannum = 0;
	$grenum = 0;
	$mrenum = 0;
	$makrname = "";
	$name = "";
	$cpu = "";
	$memory = "";
	$option_tanka = 0;
	$siire = 0;
	$deleteflg = 0;

	//画面入力項目設定
	foreach($g_post as $key=>$val) {
		//初期化
		$length = 0;
		$row = "";
		//最終項目の場合はデータ追加
		if(preg_match("/^end/",$key)){
			$comm->ouputlog("===END処理===", $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" option_tanka=" . $option_tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_option_tanka=" . $t_option_tanka, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" hannum=" . $hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_hannum=" . $t_hannum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" grenum=" . $grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_grenum=" . $t_grenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" mrenum=" . $mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_mrenum=" . $t_mrenum, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" name=" . $name, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" t_name=" . $t_name, $prgid, SYS_LOG_TYPE_DBUG);
			$comm->ouputlog(" kbn=" . $kbn, $prgid, SYS_LOG_TYPE_DBUG);
			//利益
			if ($_COOKIE['con_perf_mana'] == 1) { //代理店
				$profit = $option_tanka - $siirelist[$name];
			}else{ //代理店以外
				$profit = $profitlist[$name];
			}
			//仕入単価
			if ($_COOKIE['con_perf_mana'] == 1) { //代理店
				$siire = $siiredlist[$name];
			}else{ //代理店以外
				$siire = $siirelist[$name];
			}
			//対象がオプションの場合、もしくはオプションが登録されている場合のみfunctionを実行
			if($kbn == 2 || $kbn == 3 || $kbn == 6){
				//販売・現物予約数合算
				$suryou = 0;
				//数計算
				$hannum -= $t_hannum;
				$grenum -= $t_grenum;
				$mrenum -= $t_mrenum;
				if ($hannum < 0) {
					//在庫消込
					mysql_del_option_high($db, $g_section, $name, $t_option_tanka, $hannum * -1, "hannum", $kbn);
					//変数初期化
					$hannum = 0;
				}
				if ($grenum < 0) {
					//在庫消込
					mysql_del_option_high($db, $g_section, $name, $t_option_tanka, $grenum * -1, "grenum", $kbn);
					//変数初期化
					$grenum = 0;
				}
				if ($mrenum < 0) {
					//在庫消込
					mysql_del_option_high($db, $g_section, $name, $t_option_tanka, $mrenum * -1, "mrenum", $kbn);
					//変数初期化
					$mrenum = 0;
				}
				//単価が異なる場合、単価更新
				if ($option_tanka <> $t_option_tanka) {
					//単価更新
					mysql_change_option_high($db, $g_section, $name, $t_option_tanka, $option_tanka, $profit, $kbn);
				}
				$suryou = $hannum + $grenum;
				$comm->ouputlog(" suryou=" . $suryou, $prgid, SYS_LOG_TYPE_DBUG);
				//販売・現物予約の場合
				if($hannum>0 || $grenum>0){
					//備品の場合
					if($kbn <> "3"){
						for($j=0; $j<$hannum; ++$j){
							++$syorino;
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", $syoridt);
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", $syorino);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . sprintf("'%s'", $profit);
							$insert1 .= "," . $collist["仕入単価"];
							$insert2 .= "," . sprintf("'%s'", $siire);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $name);
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . sprintf("'%s'", $option_tanka);
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							//販売
							$insert1 .= "," . $collist["販売数量"];
							$insert2 .= "," . 1;
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ(mysql_entry_option)===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
						for($j=0; $j<$grenum; ++$j){
							++$syorino;
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["処理日付"];
							$insert2 .= "," . sprintf("'%s'", $syoridt);
							$insert1 .= "," . $collist["処理ＮＯ"];
							$insert2 .= "," . sprintf("'%s'", $syorino);
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . sprintf("'%s'", $profit);
							$insert1 .= "," . $collist["仕入単価"];
							$insert2 .= "," . sprintf("'%s'", $siire);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $name);
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . sprintf("'%s'", $option_tanka);
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							//現物予約
							$insert1 .= "," . $collist["現物予約数量"];
							$insert2 .= "," . 1;
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ(mysql_entry_option)===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);

							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					//駐車料金の場合
					}else{
						for($i=0; $i<$suryou; ++$i){
							// ================================================
							// ■　□　■　□　販売Ｔ登録　■　□　■　□
							// ================================================
							//会場ＮＯ
							$venueno++;
							if (is_null($profit)) {
								$profit = 0;
							}
							//ＳＱＬ文結合
							$insert = $query;
							$insert1 = $query1;
							$insert2 = $query2;
							$insert1 .= "," . $collist["会場ＮＯ"];
							$insert2 .= "," . $venueno;
							$insert1 .= "," . $collist["利益"];
							$insert2 .= "," . sprintf("'%s'", $profit);
							$insert1 .= "," . $collist["仕入単価"];
							$insert2 .= "," . sprintf("'%s'", $siire);
							$insert1 .= "," . $collist["型番"];
							$insert2 .= "," . sprintf("'%s'", $name);
							$insert1 .= "," . $collist["単価"];
							$insert2 .= "," . sprintf("'%s'", $option_tanka);
							$insert1 .= "," . $collist["区分"];
							$insert2 .= "," . sprintf("'%s'", $kbn);
							//販売
							if ($hannum > 0) {
								$insert1 .= "," . $collist["販売数量"];
								$insert2 .= "," . 1;
							}
							//現物予約
							elseif ($grenum > 0) {
								$insert1 .= "," . $collist["現物予約数量"];
								$insert2 .= "," . 1;
							}
							//見本予約
							elseif ($mrenum > 0) {
								$insert1 .= "," . $collist["見本予約数量"];
								$insert2 .= "," . 1;
								--$mrenum;
							}
							//ＳＱＬ文結合
							$insert1 .= ")";
							$insert2 .= ")";
							$insert .= $insert1 . $insert2;
							$comm->ouputlog("===データ追加ＳＱＬ(mysql_entry_option)===", $prgid, SYS_LOG_TYPE_DBUG);
							$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
							//データ追加実行
							if (! $db->query($insert)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
								return false;
							}
							$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
						}
					}
				}
				//見本予約の場合	
				if($mrenum > 0){
					for($j=0; $j<$mrenum; ++$j){
						// ================================================
						// ■　□　■　□　販売Ｔ登録　■　□　■　□
						// ================================================
						//会場ＮＯ
						$venueno++;
						//ＳＱＬ文結合
						$insert = $query;
						$insert1 = $query1;
						$insert2 = $query2;
						$insert1 .= "," . $collist["会場ＮＯ"];
						$insert2 .= "," . $venueno;
						$insert1 .= "," . $collist["処理日付"];
						$insert2 .= "," . sprintf("'%s'", $syoridt);
						$insert1 .= "," . $collist["処理ＮＯ"];
						$insert2 .= "," . sprintf("'%s'", $syorino);
						$insert1 .= "," . $collist["利益"];
						$insert2 .= "," . sprintf("'%s'", $profit);
						$insert1 .= "," . $collist["仕入単価"];
						$insert2 .= "," . sprintf("'%s'", $siire);
						$insert1 .= "," . $collist["型番"];
						$insert2 .= "," . sprintf("'%s'", $name);
						$insert1 .= "," . $collist["単価"];
						$insert2 .= "," . sprintf("'%s'", $option_tanka);
						$insert1 .= "," . $collist["見本予約数量"];
						$insert2 .= "," . 1;
						$insert1 .= "," . $collist["区分"];
						$insert2 .= "," . sprintf("'%s'", $kbn);
						//ＳＱＬ文結合
						$insert1 .= ")";
						$insert2 .= ")";
						$insert .= $insert1 . $insert2;
						
						$comm->ouputlog("===データ追加ＳＱＬ(mysql_entry_option)===", $prgid, SYS_LOG_TYPE_DBUG);
						$comm->ouputlog($insert, $prgid, SYS_LOG_TYPE_DBUG);
						//データ追加実行
						if (! $db->query($insert)) {
							$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							return false;
						}
						$comm->ouputlog("===データ追加完了===", $prgid, SYS_LOG_TYPE_DBUG);
					}
				}
			}
			//初期値セット
			//販売Ｔ
			$query = $m_query;
			$query1 = $m_query1;
			$query2 = $m_query2;
			$dataCnt++;
			$optionCnt = 0;
			$hannum = 0;
			$grenum = 0;
			$mrenum = 0;
			$name = "";
			$t_option_tanka = 0;
			$option_tanka = 0;
			$siire = 0;
			$kbn = "";
		} else {
			//数量チェックされている場合
			if(preg_match("/^販売/",$key)){
				$hannum = $val;
			}
			elseif(preg_match("/^現物/",$key)){
				$grenum = $val;
			}
			elseif(preg_match("/^見本/",$key)){
				$mrenum = $val;
			}
			if(preg_match("/^型番１/",$key)){
				$name = $val;
			}
			//処理日付情報
			elseif(preg_match("/^処理日付/",$key)){
				$syoridt = $val;
			}
			//処理ＮＯ情報
			elseif(preg_match("/^処理ＮＯ/",$key)){
				$syorino = $val;
			}
			elseif(preg_match("/^Ｔ単価１/",$key)){
				$t_option_tanka = $val;
			}
			elseif(preg_match("/^単価１/",$key)){
				$option_tanka = $val;
			}
			elseif(preg_match("/^Ｔ型番１/",$key)){
				$t_name = $val;
			}
			elseif(preg_match("/^Ｔ販売/",$key)){
				$t_hannum = $val;
			}
			elseif(preg_match("/^Ｔ現物/",$key)){
				$t_grenum = $val;
			}
			elseif(preg_match("/^Ｔ見本/",$key)){
				$t_mrenum = $val;
			}
			elseif(preg_match("/^区分/",$key)){
				$kbn = $val;
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
// mysql_del_option_high
//
// ■概要
// 販売情報消込
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_del_option_high( $db, $g_section, $name, $option_tanka, $num, $numflg, $kbn) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_high;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;

	$comm->ouputlog("mysql_del_option_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai_high A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $name);
	$select .= " AND A.tanka = " . sprintf("'%s'", $option_tanka);
	$select .= " AND A.henpinflg = 0";
	$select .= " AND A.telhenflg = 0";
	$select .= " AND A.delflg = 0";
	$select .= " AND A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " AND A." . $numflg . " > 0";
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$select .= " LIMIT 0 , " . $num;
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($select))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ削除（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai_high SET delflg = 1 ";
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ削除実行
		$comm->ouputlog("===データ削除ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($_delete)) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
		$comm->ouputlog("===データ削除完了===", $prgid, SYS_LOG_TYPE_DBUG);
	}
	return true;
}

//--------------------------------------------------------------------------------------------------
// ■メソッド名
// mysql_change_option_high
//
// ■概要
// オプション単価変更
//
// ■引数
// 第一引数：データベース
//
//--------------------------------------------------------------------------------------------------
function mysql_change_option_high( $db, $g_section, $name, $t_tanka, $option_tanka, $profit, $kbn) {

	//グローバル変数
	//オブジェクト
	global $comm;
	global $dba;
	//対象テーブル
	global $table_high;
	//対象プログラム
	global $prgid;
	//引数
	global $g_idx;
	global $g_post;

	$comm->ouputlog("mysql_change_option_highログ出力", $prgid, SYS_LOG_TYPE_DBUG);

	//会場ＩＤ取得
	$performance = mysql_sel_personal( $db);
	// ================================================
	// ■　□　■　□　販売Ｔ検索　■　□　■　□
	// ================================================
	//会場No取得
	$select =  " SELECT A.venueno , A.syoridt , A.syorino ";
	$select .= " FROM php_t_pc_hanbai_high A ";
	$select .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
	$select .= " AND A.section = " . sprintf("'%s'", $g_section);
	$select .= " AND A.modelnum = " . sprintf("'%s'", $name);
	$select .= " AND A.tanka = " . sprintf("'%s'", $t_tanka);
	$select .= " AND A.henpinflg = 0";
	$select .= " AND A.telhenflg = 0";
	$select .= " AND A.delflg = 0";
	$select .= " AND A.kbn = " . sprintf("'%s'", $kbn);
	$select .= " ORDER BY A.syoridt DESC, A.syorino DESC";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($select))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$venueno = $row['venueno'];
		$syoridt = $row['syoridt'];
		$syorino = $row['syorino'];
		// ================================================
		// ■　□　■　□　データ更新（販売Ｔ）　■　□　■　□
		// ================================================
		$_delete = "UPDATE php_t_pc_hanbai_high SET tanka = " . sprintf("'%s'", $option_tanka);
		$_delete .= " ,profit = " . sprintf("'%s'", $profit);
		$_delete .= " ,upddt = " . sprintf("'%s'", date('YmdHis'));
		$_delete .= " ,updcount = updcount + 1";
		$_delete .= " WHERE venueid = " . sprintf("'%s'", $performance[venueid]);
		$_delete .= " AND venueno = " . sprintf("'%s'", $venueno);
		//データ更新実行
		$comm->ouputlog("===データ更新ＳＱＬ===", $prgid, SYS_LOG_TYPE_DBUG);
		$comm->ouputlog($_delete, $prgid, SYS_LOG_TYPE_DBUG);
		if (! $db->query($_delete)) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			return false;
		}
	}
	return true;
}

//----------------------------------------------------------------------
// 関数定義(END)
//----------------------------------------------------------------------
?>
