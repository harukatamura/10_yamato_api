<?
//==================================================================================================
// ■機能概要
// ・会場参加予定登録画面
//
// ■履歴
//   
//==================================================================================================

	//----------------------------------------------------------------------------------------------
	// 初期処理
	//----------------------------------------------------------------------------------------------
	//ログイン確認(COOKIEを利用)
	if((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])) {
			//Urlへ送信
			header("Location: ./idx.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
			exit();
	}

	//----------------------------------------------------------------------------------------------
	// 共通処理
	//----------------------------------------------------------------------------------------------
	//ファイル読込
	require_once("./lib/comm.php");
	require_once("./lib/define.php");
	require_once("./lib/dbaccess.php");
	require_once("./lib/html.php");
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "ネット注文状況一覧画面";
	$prgmemo = "　ネット注文の状況を確認できます。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//チェック項目数
	$chkcnt = 6;
	
	foreach($_POST as $key=>$val) {
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_DBUG);
	}
	//担当者
	$g_staff = $_COOKIE['con_perf_staff'];
	//会社
	$p_compcd = $_COOKIE['con_perf_compcd'];
//	$p_compcd = "H";
	//精査
	$p_scrutiny = $_COOKIE['con_perf_scrutiny'];
//	$p_scrutiny = 0;
	$comm->ouputlog("p_compcd=".$p_compcd, $prgid, SYS_LOG_TYPE_INFO);

	//対象年月取得
	if (isset($_POST['対象年'])) {
		$p_year = $_POST['対象年'];
		$p_month = $_POST['対象月'];
	}
	else {
		$p_year = date('Y');
		$p_month = date('m');
	}
?>

<!--------------------------------------------------------------------------------------------------
	コンテンツ表示
---------------------------------------------------------------------------------------------------->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<meta charset="UTF-8">
<head>
	<style type="text/css">
	body {
		color: #333333;
		background-color: #FFFFFF;
		margin: 0px;
		padding: 0px;
		text-align: center;
		font: 90%/2 "メイリオ", Meiryo, "ＭＳ Ｐゴシック", Osaka, "ヒラギノ角ゴ Pro W3", "Hiragino Kaku Gothic Pro";
		background-image: url(./images/bg.jpg);	/*背景壁紙*/
		background-repeat: no-repeat;			/*背景をリピートしない*/
		background-position: center top;		/*背景を中央、上部に配置*/
	}
	#formWrap {
		width:900px;
		margin:0 auto;
		color:#555;
		line-height:120%;
		font-size:100%;
	}
	table.formTable{
		width:100%;
		margin:0 auto;
		border-collapse:collapse;
	}
	table.formTable td,table.formTable th{
		background:#ffffff;
		padding:10px;
	}
	table.formTable th{
		width:30%;
		font-weight:bolder;
		background:#FFDEAD;
		text-align:left;
	}

	h2{
		margin: 0px;
		padding: 0px;
	}

	/*コンテナー（HPを囲むブロック）
	---------------------------------------------------------------------------*/
	#container {
		text-align: left;
		width: 1010px;	/*コンテナー幅*/
		margin-right: auto;
		margin-left: auto;
		background-color: #FFFFFF;						/*背景色*/
		padding-right: 4px;
		padding-left: 4px;
	}

	/*メインコンテンツ
	---------------------------------------------------------------------------*/
	#main {
		width: 1000px;	/*メインコンテンツ幅*/
		padding: 10px 2px 50px 0px;	/*左から、上、右、下、左への余白*/
	}
	/*h2タグ設定*/
	#main h2 {
		font-size: 120%;		/*文字サイズ*/
		color: #FFFFFF;			/*文字色*/
		background-image: url(./images/bg2.gif);	/*背景画像の読み込み*/
		background-repeat: no-repeat;			/*背景画像をリピートしない*/
		clear: both;
		line-height: 40px;
		padding-left: 40px;
		overflow: hidden;
	}
	/*段落タグの余白設定*/
	#main p {
		padding: 0.5em 10px 1em;	/*左から、上、左右、下への余白*/
	}

	/*コンテンツ（左右ブロックとフッターを囲むブロック）
	---------------------------------------------------------------------------*/
	#contents {
		clear: left;
		width: 100%;
		padding-top: 4px;
	}
	/* --- ヘッダーセル（th） --- */
	th.tbd_th_p1 {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	top: 0;
	position: sticky;
	}
	th.tbd_th_p2 {
	width: 200px;
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p2_h {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #0C58A6; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p2_s {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #ff6699; /* 見出しセルの背景色 */
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3_h {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #007AC1; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3_s {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #FFB2CB; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	}
	th.tbd_th_p3_c {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #FFB2CB; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	line-height: 130%;
	width: 100px;
	}

	/* --- データセル（td） --- */

	td.tbd_td_p1 {
	width: 50px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p1_l {
	width: 150px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p1_r {
	width: 50px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p1_c {
	width: 150px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p2 {
	width: 400px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p2_l {
	width: 400px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p2_r {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p2_c {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p3 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_c {
	width: 150px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p3_err {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4 {
	padding: 10px 1px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbd_td_p4_l {
	padding: 10px 1px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p4_r {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	/* --- 仕切り線セル --- */
	td.tbd_line_p1 {
	width: 10px;
	background-color: #e0f1fc; /* 任意項目セルの背景色 */
	border: 1px solid #b6b6b6;
	}
	td.tbd_line_p2 {
	width: 2px;
	background-color: #e0f1fc; /* 任意項目セルの背景色 */
	border-bottom: 1px #c0c0c0 dotted; /* データセルの下境界線 */
	}
	select.sizechange{
	font-size:120%;
	}

	/* === ボタンを表示するエリア ============================== */
	.Area1 {
	  margin         : auto;                /* 中央寄せ           */
	  width          : 60px;                /* ボタンの横幅       */
	}
	 
	 /* === チェックボックス ==================================== */
	.Area1 input[type="checkbox"] {
	  display        : none;            /* チェックボックス非表示 */
	}
	 
	 /* === チェックボックスのラベル（標準） ==================== */
	.Area1 label {
	  display        : block;               /* ボックス要素に変更 */
	  box-sizing     : border-box;          /* 枠線を含んだサイズ */
	  text-align     : center;              /* 文字位置は中央     */
	  border         : 2px solid #ccc;      /* 枠線(一旦四方向)   */
	  border-radius  : 3px;                 /* 角丸               */
	  height         : 60px;                /* ボタンの高さ       */
	  font-size      : 18px;                /* 文字サイズ         */
	  line-height    : 60px;                /* 太字               */
	  font-weight    : bold;                /* 太字               */
	  background     : #eee;                /* 背景色             */
	  box-shadow     : 3px 3px 6px #888;    /* 影付け             */
	  transition     : .3s;                 /* ゆっくり変化       */
	}
	 
	 /* === ON側のチェックボックスのラベル（ONのとき） ========== */
	.Area1 label span:after{
	  content        : "";               /* 表示する文字       */
	  color          : #aaa;
	}
	.Area1 .check1:checked + label {
	  background     : #78bd78;             /* 背景色             */
	  box-shadow     : none;                /* 影を消す           */
	}
	.Area1 .check1:checked + label span:after {
	  content        : "完";                /* 表示する文字       */
	  color          : #fff;                /* 文字色             */
	}

	/* footer */
	#footerFloatingMenu {
	    display: block;
	    width: 100%;
	    position: fixed;
	    left: 0px;
	    bottom: 2px;
	    z-index: 9999;
	    text-align: center;
	    padding: 0 auto;
	}
	 
	#footerFloatingMenu img {
	    max-width: 99%;
	}
	a {
	  color: white;
	  text-decoration: none;
	}
	a.cheapest {
	    color: #EF3D84 ;
	}
		 
		.submit{
		    text-align: center;
		    margin-bottom: 15px;
		}
		.submit input {
		  background: #f78181;
		  background-image: -webkit-linear-gradient(top, #f78181, #f78181);
		  background-image: -moz-linear-gradient(top, #f78181, #f78181);
		  background-image: -ms-linear-gradient(top, #f78181, #f78181);
		  background-image: -o-linear-gradient(top, #f78181, #f78181);
		  background-image: linear-gradient(to bottom, #f78181, #f78181);
		  -webkit-border-radius: 8;
		  -moz-border-radius: 8;
		  border-radius: 8px;
		  -webkit-box-shadow: 1px 1px 3px #666666;
		  -moz-box-shadow: 1px 1px 3px #666666;
		  box-shadow: 1px 1px 3px #666666;
		  font-family: Courier New;
		  color: #ffffff;
		  font-size: 50px;
		  padding: 10px 20px 10px 20px;
		  text-decoration: none;
		  width: 1300px;
		}
		 
		.submit input:hover {
		  background: #f5c7c7;
		  background-image: -webkit-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: -moz-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: -ms-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: -o-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: linear-gradient(to bottom, #f5c7c7, #f5c7c7);
		  text-decoration: none;
		}

		/* 対象週 */
		fieldset {
		  border: none;
		  padding: 0;
		  margin: 0;
		}

		.radio-inline__input {
		    clip: rect(1px, 1px, 1px, 1px);
		    position: absolute !important;
		}

		.radio-inline__label {
		    display: inline-block;
		    padding: 0.5rem 1rem;
		    margin-right: 18px;
		    border-radius: 3px;
		    transition: all .2s;
		}

		.radio-inline__input:checked + .radio-inline__label {
		    background: #B54A4A;
		    color: #fff;
		    text-shadow: 0 0 1px rgba(0,0,0,.7);
		}

		.radio-inline__input:focus + .radio-inline__label {
		    outline-color: #4D90FE;
		    outline-offset: -2px;
		    outline-style: auto;
		    outline-width: 5px;
		}
	</style>
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		//-->
	</script>
	<? $html->output_htmlheadinfo2($prgname); ?>
	<script type="text/javascript">
		//対象月変更
		function Mclk_onChange(kbn){
			document.forms['frm'].action = './<? echo $prgid;?>.php';
			document.forms['frm'].submit();
		}
	</script>		 
	<style type="text/css">
		.submit input {
		  background: #f78181;
		  background-image: -webkit-linear-gradient(top, #f78181, #f78181);
		  background-image: -moz-linear-gradient(top, #f78181, #f78181);
		  background-image: -ms-linear-gradient(top, #f78181, #f78181);
		  background-image: -o-linear-gradient(top, #f78181, #f78181);
		  background-image: linear-gradient(to bottom, #f78181, #f78181);
		  -webkit-border-radius: 8;
		  -moz-border-radius: 8;
		  border-radius: 8px;
		  -webkit-box-shadow: 1px 1px 3px #666666;
		  -moz-box-shadow: 1px 1px 3px #666666;
		  box-shadow: 1px 1px 3px #666666;
		  font-family: Courier New;
		  color: #ffffff;
		  font-size: 50px;
		  padding: 10px 20px 10px 20px;
		  text-decoration: none;
		  width: 800px;
		  height: 100px;
		}
		 
		.submit input:hover {
		  background: #f5c7c7;
		  background-image: -webkit-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: -moz-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: -ms-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: -o-linear-gradient(top, #f5c7c7, #f5c7c7);
		  background-image: linear-gradient(to bottom, #f5c7c7, #f5c7c7);
		  text-decoration: none;
		}
	</style>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p><img src="images/logo_h_<? echo $prgid; ?>.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<h1><u><b><font size="20">ネット注文状況一覧</font></b></u></h1>
			<? echo $prgmemo; ?>
			<p>
			＜検索方法＞<br>
			①Ctrl + F<br>
			②お名前を入力（※ご注文された際の漢字）<? if ($p_compcd == "S") { ?>、または電話番号を入力<? } ?>
			</p>
			<div id="formWrap">
				<form name="frm" method = "post">
				<h2>検索条件</h2><br>
					<!--非表示ここから-->
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr>
							<th class="tbd_th"><strong>対象週</strong></th>
							<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
							<td class="tbd_td">
								<fieldset>
									<input id="y-item-1" class="radio-inline__input" type="radio" name="対象年" value="<? echo date('Y')-1 ?>"
										onChange="javascript:Mclk_onChange('y')" <? if($p_year == date('Y')-1) { echo "checked=\"checked\"";} ?>/>
									<label class="radio-inline__label" for="y-item-1"><center><? echo date('Y')-1 ?>年</center></label>
									<input id="y-item-2" class="radio-inline__input" type="radio" name="対象年" value="<? echo date('Y') ?>"
										onChange="javascript:Mclk_onChange('y')" <? if($p_year == date('Y')) { echo "checked=\"checked\"";} ?>/>
									<label class="radio-inline__label" for="y-item-2"><center><? echo date('Y') ?>年</center></label>
									<input id="y-item-3" class="radio-inline__input" type="radio" name="対象年" value="<? echo date('Y')+1 ?>"
										onChange="javascript:Mclk_onChange('y')" <? if($p_year == date('Y')+1) { echo "checked=\"checked\"";} ?>/>
									<label class="radio-inline__label" for="y-item-3"><center><? echo date('Y')+1 ?>年</center></label>
								</fieldset>
								<hr style="border:none;border-top:dashed 1px ;height:1px;">
								<fieldset>
									<?
									$cnt=1;
									for($i=1; $i <= 12; $i++) {
									?>
										<input id="m-item-<? echo $cnt ?>" class="radio-inline__input" type="radio" name="対象月" value="<? echo sprintf('%02d', $cnt); ?>" onChange="javascript:Mclk_onChange('m')" <? if(ltrim($p_month, '0') == $cnt) { echo "checked=\"checked\"";} ?>/>
										<label class="radio-inline__label" for="m-item-<? echo $cnt ?>"><center><? echo $cnt ?>月</center></label>
									<?
										$cnt = $cnt + 1;
									}
									?>
								</fieldset>
							</td>
							<td class="tbd_td" style="display:none;">
								<input type="text" id="p_week" name="週" value=<? echo $p_week; ?>>
							</td>
						</tr>
					</table>
					<!--ここまで-->
					<br>
					<h2>対象データ</h2><br>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL">
						<tr>
							<th class="tbd_th_p1"><strong>注文No.<br>（オーダー番号）</strong></th>
							<th class="tbd_th_p1"><strong>受付日<br>担当者</strong></th>
							<th class="tbd_th_p1" colspan="3"><strong>購入情報</strong></th>
							<th class="tbd_th_p1"><strong>返品</strong></th>
						</tr>
					<?
					// ================================================
					// ■　□　■　□　個別表示　■　□　■　□
					// ================================================
					//会場データ抽出
					$ym = $p_year ."-". $p_month;
					$query  = "
						SELECT
							 A.idxnum  ,DATE(A.receptionday) as receptionday  ,A.receptionist 
							,A.name    ,A.modelnum      ,A.category      ,A.option_han    ,A.cash    ,A.buynum, A.order_num
							,A.slipnumber	,A.phonenum1, A.p_method
							,CASE 
								WHEN A.delflg = '1' THEN 'キャンセル済'
								WHEN C.slipnumber IS NOT NULL OR A.receptionday<'2022-09-01' THEN '発送済'
								WHEN A.status = '1' THEN '伝票未発行'
								WHEN A.status = '2' THEN '保留中'
								WHEN A.status = '9' THEN '伝票発行済'
							 END as status
							,CASE WHEN B.idxnum > 0 THEN '○'
							 END as henpin
						  FROM  php_telorder__ A
							LEFT OUTER JOIN php_pc_failure B
								ON B.tel_idx = A.idxnum
							   AND B.delflg=0
							   AND B.kbn='返品'
							   AND B.locale in ('100001','100004')
							   AND B.buydt = DATE(A.receptionday)
							LEFT OUTER JOIN (
								SELECT slipnumber
								FROM php_yamato_status 
								GROUP BY slipnumber
								) C 
								ON A.slipnumber = C.slipnumber
						 WHERE A.sales_name in ('100001','100004')
						   AND A.receptionday like '$ym%'
						   AND A.modelnum <> 'JSP'
						 ORDER BY A.t_idx DESC
					";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if(!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆  " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$allcnt = 0;
					$rowcnt = 1;
					$supplier = "";
					$arr_color = array("キャンセル済" => "red", "発送済" =>"black", "伝票未発行" =>"black", "保留中" =>"black", "伝票発行済" =>"black",  );
					while ($row = $rs->fetch_array()) {
					?>
						<!-- １段目 -->
						<? if(($rowcnt % 2) == 0){ ?>
						<tr style="background-color:white;">
						<? }else{ ?>
						<tr style="background-color:#EDEDED;">
						<? } ?>
							<? if ($p_compcd == "S") { ?>
								<td class="tbd_td_p1_c" rowspan="3"><? echo $row['idxnum'] ?><? if($row['order_num'] <> ""){echo "<br>（".$row['order_num']."）";} ?></td>
								<td class="tbd_td_p1_l" rowspan="3">
							<? } else { ?>
								<td class="tbd_td_p1_c" rowspan="2"><? echo $row['idxnum'] ?><? if($row['order_num'] <> ""){echo "<br>（".$row['order_num']."）";} ?></td>
								<td class="tbd_td_p1_l" rowspan="2">
							<? } ?>
								<? echo date('m/d', strtotime($row['receptionday'])) ?><br>
								<? echo $row['receptionist'] ?><br>
								<font color="<? echo $arr_color[$row['status']] ?>"><? echo $row['status']; ?></font><br>
								<?
								if ($row['p_method'] == "") {
									echo "【代金引換】";
								} else if($row['p_method'] != "") {
									echo "【".$row['p_method']."】";
								} 
								?><br>
								<? if ($row['slipnumber'] > 0) { ?>
									<?
									$str_ary = str_split($row['slipnumber'], 4);
									$result = implode('-', $str_ary);
									?>
									<a href="https://member.kms.kuronekoyamato.co.jp/parcel/detail?pno=<? echo $row['slipnumber'] ?>" target="_blank"><?php echo $result; ?></a>
								<? } ?>
							</td>
							<td class="tbd_td_p2_l" colspan="3"><? echo $row['name'] ?></td>
							<? if ($p_compcd == "S") { ?>
								<td class="tbd_td_p2_c" rowspan="3"><? echo $row['henpin'] ?></td>
							<? } else { ?>
								<td class="tbd_td_p2_c" rowspan="2"><? echo $row['henpin'] ?></td>
							<? } ?>
						</tr>
						<!-- ２段目 -->
						<?
						if ($p_compcd == "S") {
							if(($rowcnt % 2) == 0) { ?>
							<tr style="background-color:white;">
							<? }else{ ?>
							<tr style="background-color:#EDEDED;">
							<? } ?>
								<td class="tbd_td_p2_l" colspan="3"><? echo $row['phonenum1'] ?></td>
							</tr>
						<? } ?>
						<!-- ３段目 -->
						<? if(($rowcnt % 2) == 0){ ?>
						<tr style="background-color:white;">
						<? }else{ ?>
						<tr style="background-color:#EDEDED;">
						<? } ?>
							<td class="tbd_td_p2_l">
								<?
								$trade_m = "";
								if (strpos($row['modelnum'], 'user') !== false || strpos($row['modelnum'], 'trade') !== false) {
									$trade_m = "下取り購入";
								}
								?>
								<? echo $row['category'] ?><br>
								<? echo $row['option_han'] ?>
								<?
								if ($row['option_han'] != "") {
									echo "<br>";
								} 
								echo $trade_m;
								?>
							</td>
							<td class="tbd_td_p1_r"><? echo $row['buynum'] ?>台</td>
							<td class="tbd_td_p2_r"><? echo number_format($row['cash']) ?>円</td>
						</tr>
					<?
						//日付を退避
						$buydt = $row['buydt'];
						//カウントアップ
						$rowcnt++;
					}
					?>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<? if($result) { $dba->mysql_discon($db); } ?>

</html>
