
<?php
//==================================================================================================
// ■機能概要
// ・通販伝票出力画面
//==================================================================================================

	//----------------------------------------------------------------------------------------------
	// 初期処理
	//----------------------------------------------------------------------------------------------
	//ログイン確認(COOKIEを利用)
	if ((!$_COOKIE['j_office_Uid']) or (!$_COOKIE['j_office_Pwd'])) {
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
	require_once("./sql/sql_aggregate.php");
	require_once('./Classes/PHPExcel.php');
	require_once('./Classes/PHPExcel/IOFactory.php');
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	$sql = new SQL_aggregate();
	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	foreach($_POST as $key=>$val) {
		$comm->ouputlog("key =" .$key. " val =" . $val, $prgid, SYS_LOG_TYPE_INFO);
	}
	//セッション取得
	session_start();
	$response = $_SESSION['yamato_api_response'] ?? '';
	// 必要なら読み終わったら削除
	unset($_SESSION['yamato_api_response']);
	
	//COOKIE取得
	$j_office_Uid = $_COOKIE['j_office_Uid'];
	$s_staff = $_COOKIE['con_perf_staff']; 
	$p_slip_flg = $_COOKIE['con_slip_flg']; 
	
	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "伝票発行画面";
	$prgmemo = "　発送伝票の発行ができます。（通販・会場予約）";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//購入方法取得
	$_select = " SELECT A.item_value, A.item_name ";
	$_select .= " FROM php_item A ";
	$_select .= " WHERE A.item='transaction_cd' ";
	$_select .= " AND A.item_value LIKE '02%' OR A.item_value LIKE '04%' OR A.item_value LIKE '05%' ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$staff_list[$row['item_value']] = $row['item_name'];
	}
	
	//ヤマトデータ取得
	$_select = " 
		SELECT A.item_name, A.item_value
		FROM php_yamato_account A
		WHERE  1
		 ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$yamato_data[$row['service_type']][$row['item_name']] = $row['item_value'];
		$yamato_data[$row['service_type']][$row['item_value']] = $row['item_name'];
	}
	
	//金曜日の場合はデフォルト10日後着までのデータを抽出、他は7日後着までのデータを抽出
	if(date('w') == 5){
		$p_maxday = 10;
	}else{
		$p_maxday = 7;
	}
	$today = date('YmdHis');
	
	$g_flg = $_GET['flg'];
	$arr_alert = array("1" => "伝票の発行に失敗しました", "2" => "伝票の発行が完了しました");
	//POSTデータ取得
	if(isset($_POST['search'])){
		$p_staff = $_POST['販売方法'];
		$designated_day = $_POST['designated_day'];
		$s_modelnum_list[0] = $_POST['型番ノート'];
		$s_modelnum_list[1] = $_POST['型番デスク'];
		$s_modelnum_list[2] = $_POST['型番周辺機器'];
	}else{
		$p_staff = "0201";
		$designated_day = $p_maxday;
		$s_modelnum_list[0] = [];
		$s_modelnum_list[1] = [];
		$s_modelnum_list[2] = [];
	}
	//PCリスト取得
	$_select = "
		 SELECT A.modelnum, 
		 CASE
		 WHEN B.desktop=1 THEN 1
		 WHEN C.desktopflg=1 THEN 1
		 WHEN C.kbn=1 THEN 0
		 WHEN C.kbn=2 THEN 2
		 ELSE 0
		 END as kbn
		FROM php_pi_ship_history A 
		LEFT OUTER JOIN php_pc_info B ON A.modelnum=B.modelnum AND B.delflg=0 
		LEFT OUTER JOIN php_ecommerce_pc_info C ON A.modelnum=C.modelnum AND C.delflg=0 
		WHERE A.output_flg = 0 
		AND A.delflg = 0 
		AND (A.transaction_cd LIKE '02%' OR A.transaction_cd LIKE '04%' OR A.transaction_cd LIKE '05%') 
		GROUP BY A.modelnum 
		ORDER BY A.modelnum 
	";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($_select))) {
		$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$modelnum_list[$row['kbn']][] = $row['modelnum'];
		if(!isset($_POST['search'])){
			$s_modelnum_list[$row['kbn']][] = $row['modelnum'];
		}
	}
	//JSON形式に変換
	$json_array_s_modelnum = json_encode($s_modelnum_list);
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
	input[type=submit]{
	 background-image:url("./img/satei.jpg");
	 background-repeat:no-repeat;
	 background-color:#000000;
	 border:none;
	 width:430px;
	 height:59px;
	 cursor: pointer;
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
		width: 950px;	/*メインコンテンツ幅*/
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
	width: 200px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p2 {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p3 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_err {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p4 {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
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

	.tbd thead th {
	  /* 縦スクロール時に固定する */
	  position: -webkit-sticky;
	  position: sticky;
	  top: 0;
	  background-color:#00885a;
	  height: 3em;
	   /* tbody内のセルより手前に表示する */
	  z-index: 1;
	  color:white;			
	}
	input[type=checkbox] {
	  transform: scale(1.5);
	}
		.btn-circle-border-simple {
		position: relative;
		display: inline-block;
		text-decoration: none;
		background: #b3e1ff;
		color: #668ad8;
		width: 300px;
		border-radius: 10%;
		border: solid 2px #668ad8;
		text-align: center;
		overflow: hidden;
		font-weight: bold;
		transition: .4s;
		box-shadow: 1px 1px 3px #666666;
		font-size: 30px;
		padding: 50px;
		margin: 20px 30px 20px 30px;
		}
		.btn-circle-border-simple:hover {
		background: #668ad8;
		color: white;
		text-decoration: none;
		}
		.btn-circle-border-simple2 {
		display: inline-block;
		text-decoration: none;
		background: #ffdab9;
		color: #ff8c00;
		width: 300px;
		border-radius: 10%;
		border: solid 2px #ff8c00;
		text-align: center;
		overflow: hidden;
		font-weight: bold;
		transition: .4s;
		box-shadow: 1px 1px 3px #666666;
		font-family: Courier New;
		font-size: 30px;
		padding: 50px;
		margin: 20px 30px 20px 30px;
		}
		.btn-circle-border-simple2:hover {
		background: #ff8c00;
		color: white;
		text-decoration: none;
		}
	</style>
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		//-->
	</script>
	<?php $html->output_htmlheadinfo3($prgname); ?>
	<script type="text/javascript" src="//code.jquery.com/jquery-2.1.0.min.js"></script>
	<script type="text/javascript">
		//二重登録防止後伝票出力
		function MClickBtn(action,g_fac,horyuu_num) {
			if(window.confirm(g_fac+'出荷用の伝票を出力します。')){
				if(horyuu_num > 0){
					if(window.confirm('保留データが残っていますが、このまま出力していいですか？')){
						document.forms['frm'].action = './slip_yamato_api.php?action='+action+'&fac='+g_fac;
						return;
					}
				}else{
					document.forms['frm'].action = './slip_yamato_api.php?action='+action+'&fac='+g_fac;
					document.forms['frm'].submit();
				}
			}else{
				return false;
			}
		}
		function CheckRow(i,status){
			const checkbox = document.getElementById("box" + i);
			if (!checkbox) return;
			// チェックを反転
			checkbox.checked = !checkbox.checked;
			CntRow();
		}
		function CntRow(){
			const max_row = document.forms['frm'].elements['行数'].value;
			for(let i = 1; i <= max_row; i++) {
				const checkbox = document.getElementById("box" + i);
				const row = checkbox.closest("tr");
				if (checkbox.checked) {
					row.style.backgroundColor = "pink";
				} else {
					if(i % 2 == 0){
						row.style.backgroundColor = 'ffffff';
					}else{
						row.style.backgroundColor = 'EDEDED';
					}
				}
			}
		}
		function ChkAll(){
			const all_checkbox = document.getElementById("checkAll");
			if (!all_checkbox) return;
			// チェックを反転
			all_checkbox.checked = !all_checkbox.checked;
			const status = all_checkbox.checked;
			const max_row = document.forms['frm'].elements['行数'].value;
			for(let i = 1; i <= max_row; i++) {
				const checkbox = document.getElementById("box" + i);
				if (checkbox) {
					checkbox.checked = status;
				}
			}
			CntRow();
		}
		
	</script>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p><img src="images/logo_ecommerce_output.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<div id="formWrap">
				<h2>検索条件</h2><br>
				<?= $arr_alert[$g_flg]; ?>
				<?= $p_memo; ?>
				<?= $response; ?>
				<form name="frm" method = "post" action="./slip_output_yamato.php" >
					<input type="hidden" name="登録担当者_old" value="<? echo $p_staff ?>">
					<table class="tbd" align="center" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr>
							<th class="tbd_th"><strong>担当者</strong></th>
							<td class="tbd_req"><img src="./images/hisu.gif" alt="必須"></td>
							<td class="tbd_td">
								<?php
									$j = 5;
									foreach($staff_list as $key => $val) { ?>
										<label for="radio<? echo $j ?>" class="radio"><input type="radio" name="販売方法" value="<? echo $key ?>" <? if($p_staff==$key){ ?>checked='checked' <? } ?> id="radio<? echo $j ?>"><strong><?= $val; ?></strong></label>
										<?
										$j++;
									}
								?>
							</td>
						</tr>
					<tr>
							<th class="tbd_th"><strong>型番</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<? if(count($modelnum_list[0]) > 0){ ?>
									<label><input type="checkbox" id="checkAllNote" name="checkAllNote"><B>ノートPC</B></label>
									<? foreach($modelnum_list[0] as $key => $val){ ?>
										<br>　<label><input type="checkbox" name="型番ノート[]" value="<? echo $val ?>" <? if(in_array($val, (array)$s_modelnum_list[0])){ ?>checked='checked'<? } ?>> <? echo $val ?></label>
									<? } ?>
								<? }if(count($modelnum_list[1]) > 0){ ?>
									<br>
									<label><input type="checkbox" id="checkAllDsk" name="checkAllDsk"><B>デスクトップ</B></label><br>
									<? foreach($modelnum_list[1] as $key => $val){ ?>
										<label><input type="checkbox" name="型番デスク[]" value="<? echo $val ?>" <? if(in_array($val, (array)$s_modelnum_list[1])){ ?>checked='checked'<? } ?>> <? echo $val ?></label>
									<? } ?>
								<? }if(count($modelnum_list[2]) > 0){ ?>
									<br>
									<label><input type="checkbox" id="checkAllOp" name="checkAllOp"><B>周辺機器</B></label><br>
									<? foreach($modelnum_list[2] as $key => $val){ ?>
									<label><input type="checkbox" name="型番周辺機器[]" value="<? echo $val ?>" <? if(in_array($val, (array)$s_modelnum_list[2])){ ?>checked='checked'<? } ?>> <? echo $val ?></label>
									<? } ?>
								<? } ?>
							</td>
						</tr>
						<tr>
							<th class="tbd_th"><strong>着日</strong></th>
							<td class="tbd_req"></td>
							<td class="tbd_td">
								<label for="radio02" class="radio"><input type="radio" name="designated_day" value="<? echo $p_maxday ?>" id="radio02" <? if($designated_day == $p_maxday) { ?>checked='checked'<? } ?>><strong><? echo $p_maxday ?>日以内</strong></label>
								<label for="radio01" class="radio"><input type="radio" name="designated_day" value="全て表示" id="radio01" <? if($designated_day == "全て表示") { ?>checked='checked'<? } ?>><strong>全て表示</strong></label>
							</td>
						</tr>
					</table>
					<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<tr>
							<td class="tbf3_td_p1_c"><input type="submit" name="search" style="width:100px; height:30px; font-size:12px;" value="検索"></td>
						</tr>
					</table>
					<?
					//保留数量取得
					$_select = " SELECT A.modelnum, SUM(A.buynum) as sumnum ";
					$_select .= " FROM php_pi_ship_history A ";
					$_select .= " WHERE A.delflg = 0 ";
					$_select .= " AND A.transaction_cd = '$p_staff' ";
					$_select .= " AND A.status = 2 ";
					$_select .= " GROUP BY A.modelnum ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($_select, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($_select))) {
						$comm->ouputlog("☆★☆データ更新エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					if ($rs->num_rows > 0) { ?>
						<h2>保留中台数</h2><br>
						<a href="./nsorder_list_all2.php?horyuflg=1" target="_blank">リスト表示</a><br>
						<table class="tbh" width="600" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr style="background:#ccccff">
								<th class="tbd_th_p1"><strong>型番</strong></th>
								<th class="tbd_th_p2"><strong>台数</strong></th>
							</tr>
							<?
							$t = 0;
							$g_horyuu_num = 0;
							while ($row = $rs->fetch_array()) {
								$g_horyuu_num += $row['buynum']; ?>
								<tr <?if($t % 2 == 0){echo ' style="background-color:#EDEDED;"';} ?>>
									<td class="tbd_td_p1"><? echo $row['modelnum']; ?></td>
									<td class="tbd_td_p4_r"><? echo $row['sumnum']; ?>台</td>
								</tr>
							<? } ?>
						</table><br>
					<? } ?>
					</table>
					<h2>受注詳細</h2><br>
					<a href="javascript:MClickBtn('output','本部','<? echo $g_horyuu_num ?>')" class="btn-circle-border-simple">本部伝票出力</a>
					<a href="javascript:MClickBtn('output','補修センター','<? echo $g_horyuu_num ?>')" class="btn-circle-border-simple2">補修センター伝票出力</a>
					<p style="text-align:right">※背景が赤くなっているデータは、存在しない型番のため伝票の出力ができません。php_ecommerce_pc_infoテーブルにデータを追加した上で、出力してください。<br>（もしくは、システム担当者までお問い合わせください）</p>
					<table class="tbh" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr><td class="category"><strong>■◇■販売データ■◇■</strong></td></tr>
					</table>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL">
						<thead>
							<tr onclick="Javascript:ChkAll(this.checked);">
								<th><label><input type="checkbox" class="list" id="checkAll" name="checkAll" onchange="Javascript:ChkAll(this.checked);"></label></th>
								<th>受付NO.</th>
								<th>受付日</th>
								<th>名前</th>
								<th>型番</th>
								<th>金額</th>
								<th>購入台数</th>
								<th>購入方法</th>
								<th>支払方法</th>
<!--								<th>着日指定</th>-->
								<th title="全文">備考※</th>
							</tr>
						</thead>
						<!-- 個別表示 -->
						<?php
						//変数初期化
						$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
						// ================================================
						// ■　□　■　□　個別表示　■　□　■　□
						// ================================================
						$flg = 0;
						for($i=0; $i<3; ++$i){
							if(count($s_modelnum_list[$i])>0){
								$flg = 1;
							}
						}
						if($flg > 0){
							$query = "
								 SELECT A.modelnum, A.cash, A.buynum, A.idxnum, A.status, A.reception_dt
								, B.name1, B.name2, B.phonenum1
								, GROUP_CONCAT(DISTINCT A.modelnum) as modelnum, SUM(A.cash) as cash, SUM(A.buynum) as buynum
								, GROUP_CONCAT(DISTINCT A.remarks) as remarks, A.designated_day, A.output_flg,  A.p_way
								 FROM php_pi_ship_history A 
								 LEFT OUTER JOIN php_personal_information B ON concat(LPAD(B.idxnum,10,'0'),p_year) = A.picd
								 LEFT JOIN (SELECT modelnum, desktopflg as desktop FROM php_ecommerce_pc_info WHERE delflg=0 GROUP BY modelnum) C ON A.modelnum=C.modelnum
								 LEFT JOIN (SELECT modelnum, desktop FROM php_pc_info WHERE delflg=0 GROUP BY modelnum) D ON A.modelnum=D.modelnum
								 WHERE A.delflg = 0 
								 AND A.output_flg = 0 
								 AND A.transaction_cd = '$p_staff'
								 AND A.status IN ( 1
								 ";
	/*						$chkcnt = 0;
							foreach($g_status as $key => $val){
								if($chkcnt == 1){
									$query .= " , ";
								}
								$query .= " '$val' ";
								$chkcnt = 1;
							}
	*/						$query .= " ) ";
							$query .= " AND A.modelnum IN (";
							$chkcnt = 0;
							for($i=0; $i<3; ++$i){
								if(count($s_modelnum_list[$i])>0){
									foreach($s_modelnum_list[$i] as $key => $val){
										if($i > 0){
											$query .= ", ";
										}
										$query .= "'$val'";
										++$chkcnt;
									}
								}
							}
							$query .= " )";
							$query .= " GROUP BY A.picd, A.p_way, C.desktop, D.desktop ";
							$query .= " ORDER BY COUNT(*) DESC, A.insdt";
							$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
							$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
							if (! $rs = $db->query($query)) {
								$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
							}
							$i = 0;
							while ($row = $rs->fetch_array()) {
								$i++;
								//明細設定
								if ($row['flg'] > 0) { ?>
									<tr style="background-color:#ff0000;">
								<? }else if (($i % 2) == 0) { ?>
									<tr style="background-color:#ffff00" onclick="Javascript:CheckRow(<? echo $i ?>,<? echo $row['status'] ?>)">
								<? } else { ?>
									<tr style="background-color:#EDEDED" onclick="Javascript:CheckRow(<? echo $i ?>,<? echo $row['status'] ?>)">
								<? }?>
									<td style="display:none;"><?php echo $t_idx?></td>
									<!-- 印刷用チェックボックス -->
									<? if($row['flg'] == 0){ ?>
										<td style="text-align:center; vertical-align:middle;padding:0">
											<input id="box<? echo $i ?>" type="checkbox" name="outputno[]" value="<? echo $row['idxnum'] ?>" readonly="readonly" onchange="Javascript:CheckRow(<? echo $i ?>,<? echo $row['status'] ?>)">
											<input type="text" id="台数<? echo $i ?>" value="<? echo $row['buynum'] ?>"  style="display:none">
											<input type="text" id="modelnum<? echo $i ?>" value="<? echo $row['category'] ?>"  style="display:none">
											<input type="text" id="status<? echo $i ?>" value="<? echo $row['status'] ?>"  style="display:none">
											<input type="text" id="flg<? echo $i ?>" value="<? echo $row['flg'] ?>"  style="display:none">
											<? if($row['buynum'] > 1){ ?>
												<? $t = 1;
												foreach($g_category as $val){ ?>
													<input type="text" id="modelnum<? echo $i ?>_<? echo $t; ?>" value="<? echo $val ?>"  style="display:none">
													<? ++$t;
												} ?>
											<? } ?>
										</td>
									<? } else{ ?>
										<td style="text-align:center; vertical-align:middle;padding:0">
											<input id="box<? echo $i ?>" type="checkbox" name="outputno[]" value="<? echo $row['t_idx'] ?>" style="display:none;">
											<input type="text" id="台数<? echo $i ?>" value="<? echo $row['buynum'] ?>"  style="display:none">
											<input type="text" id="modelnum<? echo $i ?>" value="<? echo $row['category'] ?>"  style="display:none">
											<input type="text" id="status<? echo $i ?>" value="<? echo $row['status'] ?>"  style="display:none">
											<input type="text" id="flg<? echo $i ?>" value="<? echo $row['flg'] ?>"  style="display:none">
											<? if($row['buynum'] > 1){ ?>
												<? $t = 1;
												foreach($g_category as $val){ ?>
													<input type="text" id="modelnum<? echo $i ?>_<? echo $t; ?>" value="<? echo $val ?>"  style="display:none">
													<? ++$t;
												} ?>
											<? } ?>
										</td>
									<? } ?>
									<!-- インデックス -->
									<td width="70" style="text-align:center; vertical-align:middle;"><?php echo str_pad($row['idxnum'], 6, "0", STR_PAD_LEFT) ?></td>
									<!-- 受付日時 -->
									<td style="text-align:center; vertical-align:middle;"><?php echo date('Y/n/j', strtotime($row['reception_dt'])); ?><br>
									<? echo date('H:i:s',strtotime($row['reception_dt'])) ?>
									</td>
									<!-- お名前 -->
									<td style="vertical-align:middle;">
										<?= $row['name1']."　".$row['name2']; ?>
									</td>
									<!-- 型番 -->
									<td style="text-align:center; vertical-align:middle;"><?= $row['modelnum']; ?></td>
									<!-- 金額 -->
									<td style="text-align:center; vertical-align:middle;"><?= number_format($row['cash'])."円"; ?></td>
									<!-- 台数 -->
									<td style="text-align:center; vertical-align:middle;"><?= $row['buynum']."台"; ?></td>
									<!-- 販売方法 -->
									<td style="text-align:center; vertical-align:middle;"><?= $sales_name[$row['sales_name']]; ?></td>
									<!-- 支払方法/配送方法 -->
									<td style="text-align:center; vertical-align:middle;">
										<?php
										echo $p_method;
										if ($p_method == "") {
											echo $yamato_data["service_type"][$row['p_way']];
										}
										?>
									</td>
									<!-- 着日指定 -->
	<!--								<td style="text-align:center; vertical-align:middle;"><?= $row['designated_day_h']; ?></td>-->
									<!-- 備考 -->
									<td style="text-align:left; vertical-align:middle;" title="<?= $remark ?>"><?= mb_substr($row['remarks'],0,10); ?><? if(mb_strlen($row['remarks']) > 9){echo "・・・";} ?></td>
								</tr>
							<? } ?>
						<? } ?>
					</table>
					<input type="text" name="行数"  id="行数" value="<? echo $i ?>" style="display:none">
					<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<td class="tbf3_td_p2_c"><a href="#" onClick="window.close(); return false;"><input type="button" value="閉じる"></a></td>
					</table>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
