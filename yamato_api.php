 <?php
//==================================================================================================
// ■機能概要
// ・ヤマトAPI
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
	require_once('./Classes/PHPExcel.php');
	require_once('./Classes/PHPExcel/IOFactory.php');
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();
	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "ヤマトAPI";
	$prgmemo = "　ヤマトAPIです。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//ヤマト連携API　トークンを設定
	$url = "https://testb2api.kuronekoyamato.co.jp/api/endpoint"; // ←エンドポイント
	$token = "sBTUN8kJPuizQnAfLpFX"; // ← API連携会社コード

	$data = [
		"sample" => "value"
	];

	$options = [
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_HTTPHEADER => [
			"Authorization: Token " . $token,
			"Content-Type: application/json"
		],
		CURLOPT_POSTFIELDS => json_encode($data),
	];

	$ch = curl_init();
	curl_setopt_array($ch, $options);
	$response = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	echo "HTTPコード: {$httpcode}\n";
	echo "レスポンス: {$response}\n";

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
	th.tbd_th_c {
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: white;
	background-color: #0C58A6; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	}
	th.tbd_th_p1 {
	width: 80px;
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: #ffffff;
	background-color: #5f9ea0; /* 見出しセルの背景色 */
	border: 1px solid white;
	text-align: center;
	}
	th.tbd_th_p2 {
	width: 200px;
	padding: 10px 8px; /* 見出しセルのパディング（上下、左右） */
	color: #ffffff;
	background-color: #5f9ea0; /* 見出しセルの背景色 */
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

	td.tbd_td_p1_l {
	width: 200px;
	padding: 1px 10px 1px; /* データセルのパディング（上、左右、下） */
	border: none;
	text-align: left;
	vertical-align:middle;
	}
	td.tbd_td_p1_c {
	padding: 3px 10px; /* データセルのパディング（上、左右、下） */
	border: none;
	text-align: center;
	vertical-align:middle;
	}
	td.tbd_td_p1_r {
	padding: 3px 10px; /* データセルのパディング（上、左右、下） */
	border: none;
	text-align: right;
	vertical-align:middle;
	}
	td.tbd_td_p2 {
	width: 100px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: left;
	}
	td.tbd_td_p3_r {
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: right;
	}
	td.tbd_td_p3_c {
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
	</style>
	<script type="text/javascript">
		<!--
		function hpbmapinit() {
			hpbmaponload();
		}
		
		//ヤマトAPI　トークンの指定
		const accessToken = "123456789abcdef";

		fetch("/api/send_to_b2cloud.php", {
			method: "POST",
			headers: { "Content-Type": "application/json" },
			body: JSON.stringify({ order_id: "12345" })
		})
		.then(res => res.json())
		.then(data => console.log(data))
		.catch(err => console.error("通信エラー:", err));
		
	</script>
	<?php $html->output_htmlheadinfo3($prgname); ?>
	<script type="text/javascript" src="//code.jquery.com/jquery-2.1.0.min.js"></script>
	<style type="text/css">
		.btn-circle-border-simple {
		position: relative;
		display: inline-block;
		text-decoration: none;
		background: #b3e1ff;
		color: #668ad8;
		width: 250px;
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
		width: 250px;
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
		.btn-circle-border-simple-sagawa {
		position: relative;
		display: inline-block;
		text-decoration: none;
		background: #1e50a2;
		color: #dbffff;
		width: 250px;
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
		.btn-circle-border-simple-sagawa:hover {
		background: #0f2350;
		color: white;
		text-decoration: none;
		}
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
	.header_logo {
		vertical-align: middle;
		font-size:64px;
	}

	/*--ボタンデザイン--*/
	.btn-border-b {
	display: inline-block;
	text-align: left;
	border: 2px solid #00608d;
	font-size: 24px;
	text-decoration: none;
	font-weight: bold;
	padding: 20px 20px;
	border-radius: 4px;
	transition: .4s;
	background-color: #bbe2f1;
	color: #0073a8;
	}
	.btn-border-b:hover,
	.btn-border-b::before,
	.btn-border-b::after,
	.btn-border-b:hover:before,
	.btn-border-b:hover:after {
	border-color: #00608d;
	background-color: #008db7;
	color: #fff;
	}
	.btn-flat-border {
	display: inline-block;
	padding: 0.3em 1em;
	text-decoration: none;
	background-color: #afeeee;
	color: #191970;
	border: solid 2px #191970;
	border-radius: 3px;
	transition: .4s;
	}
	.btn-flat-border:hover {
	background: #191970;
	color: white;
	}
	/*--ボタンデザイン--*/
	.btn-border {
	display: inline-block;
	text-align: left;
	border: 2px solid #ff8c00;
	font-size: 20px;
	color: #ff8c00;
	text-decoration: none;
	font-weight: bold;
	padding: 20px 20px;
	border-radius: 4px;
	transition: .4s;
	background-color: #fffacd;
	color: #0073a8;
	}
	.btn-border:hover,
	.btn-border::before,
	.btn-border::after,
	.btn-border:hover:before,
	.btn-border:hover:after {
	background-color: #ffa500;
	border-color: #ff8c00;
	color: #FFF;
	}
	</style>
	<script type="text/javascript">
		document.addEventListener("DOMContentLoaded", () => {
		  const btn = document.getElementById("copyBtn");
		  btn.addEventListener("click", () => {
		    const text = document.getElementById("textToCopy").textContent;
		    text.trim();
			    navigator.clipboard.writeText(text).then(() => {
			        btn.textContent = "Done";
			        setTimeout(() => btn.textContent = "Copy", 1000);
			    });
		    });
		});
		//発送完了メール一括送信ボタン
		function Push_Send_all(month){
			if(window.confirm(month + "月の発送完了メールを一括で送信します")){
				//値をPHPに受け渡す
				$.ajax({
				type: "POST", //　GETでも可
				url: "./rice_mail_slip_reply_all.php", //　送り先
				data: { 
				month: month
				 }, //　渡したいデータをオブジェクトで渡す
				dataType : "json", //　データ形式を指定
				scriptCharset: 'utf-8' //　文字コードを指定
				})
				.then(
				function(mail_result){　 //　paramに処理後のデータが入って戻ってくる
				alert("　結果：" + mail_result[1]);
				console.log('resister', "対象月：" + mail_result[0] + "　結果：" + mail_result[1]);
				},
				function(XMLHttpRequest, textStatus, errorThrown){
				console.log(errorThrown); //　エラー表示
				});
			}
		}
		//受取依頼メール送信ボタン
		function Push_Send_Re(idxnum){
			if(window.confirm("No." +idxnum + "の受取依頼メールを送信します")){
				//値をPHPに受け渡す
				$.ajax({
				type: "POST", //　GETでも可
				url: "./rice_re_mail_slip_reply.php", //　送り先
				data: { 
				ship_idxnum: idxnum
				 }, //　渡したいデータをオブジェクトで渡す
				dataType : "json", //　データ形式を指定
				scriptCharset: 'utf-8' //　文字コードを指定
				})
				.then(
				function(mail_result){　 //　paramに処理後のデータが入って戻ってくる
				alert("　結果：" + mail_result[1]);
				console.log('resister', "伝票番号：" + mail_result[0] + "　結果：" + mail_result[1]);
				},
				function(XMLHttpRequest, textStatus, errorThrown){
				console.log(errorThrown); //　エラー表示
				});
			}
		}
	</script>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p class="header_logo"><img src="images/logo_jemtc.png" alt="" />精米倶楽部伝票発行</p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<?php echo $prgmemo; ?>
			<div id="formWrap">
				<form name="frm" method = "post" action="./rice_slip.php">
					<!-- 伝票出力済データ -->
					<?php
					$comm->ouputlog("☆★☆処理開始☆★☆ ", $prgid, SYS_LOG_TYPE_INFO);
					// ================================================
					// ■　□　■　□　個別表示　■　□　■　□
					// ================================================
					//----- データ抽出
					$query = "SELECT A.ship_idxnum, A.tanka, A.category, A.weight, A.delivery_date, A.specified_times, A.output_flg, A.slipnumber, A.mail_date, A.mail_flg, A.re_mail_date, A.re_mail_flg, C.email, C.idxnum";
					$query .= " ,C.name, C.company, C.phonenum1, C.postcd1, C.postcd2, C.address1, C.address2, C.address3, C.address4, B.memo3, B.subsc_idxnum, A.ship_date, A.receive_date ";
					$query .= " , CASE ";
					$query .= "  WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN '初回' ";
					$query .= "  WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN '最終回' ";
					$query .= "  ELSE '' END as remarks2 ";
					$query .= " ,CASE ";
					$query .= " WHEN A.output_flg = 3 THEN 'エラー' ";
					$query .= " WHEN A.delflg = 1 AND A.ship_date <> '0000-00-00' THEN '発送後キャンセル' ";
					$query .= " WHEN A.delflg = 1 AND A.ship_date = '0000-00-00' THEN '発送前キャンセル' ";
					$query .= " WHEN A.output_flg = 9 AND A.ship_date = '0000-00-00' THEN '発送準備中' ";
					$query .= " WHEN A.output_flg = 9 AND A.ship_date <> '0000-00-00'AND A.receive_date = '0000-00-00' THEN '配送中' ";
					$query .= " WHEN A.output_flg = 9 AND A.receive_date <> '0000-00-00' THEN '受取完了' ";
					$query .= "  ELSE '?' END as status ";
					$query .= " FROM php_rice_shipment A";
					$query .= " LEFT OUTER JOIN php_rice_subscription B ON A.subsc_idxnum=B.subsc_idxnum ";
					$query .= " LEFT OUTER JOIN php_rice_personal_info C ON B.personal_idxnum=C.idxnum ";
					$query .= " WHERE A.stopflg = 0";
					$query .= " AND A.output_flg > 0";
					$query .= " AND (A.delflg = 0 OR A.slipnumber <> '')";
					$query .= " AND A.delivery_date BETWEEN '".$p_year.$p_month."01' AND LAST_DAY('".$p_year.$p_month."01')";
					$query .= " ORDER BY  ";
					$query .= " CASE ";
					$query .= " WHEN A.delflg = 1 AND A.ship_date <> '0000-00-00' THEN 7 ";
					$query .= " WHEN A.delflg = 1 AND A.ship_date = '0000-00-00' THEN 8 ";
					$query .= " WHEN A.output_flg = 3 THEN 1 ";
					$query .= " WHEN A.output_flg = 9 AND A.ship_date = '0000-00-00' THEN 2 ";
					$query .= " WHEN A.output_flg = 9 AND A.ship_date <> '0000-00-00'AND A.receive_date = '0000-00-00' THEN 3 ";
					$query .= " WHEN A.output_flg = 9 AND A.receive_date <> '0000-00-00' THEN 4 ";
					$query .= " WHEN YEAR(B.date_s)=YEAR(A.delivery_date) AND MONTH(B.date_s)=MONTH(A.delivery_date) THEN 5 ";
					$query .= " WHEN YEAR(B.date_e)=YEAR(A.delivery_date) AND MONTH(B.date_e)=MONTH(A.delivery_date) THEN 6 ";
					$query .= " ELSE 2 END ";
					$query .= " , A.category, A.weight, B.memo3 DESC, C.idxnum";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (! $rs = $db->query($query)) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					$rowcnt = 0;
					if($rs && $rs->num_rows > 0){ ?>
						<h2>伝票出力済データ</h2>
						<p id="p2">
							<p style="text-align:right;"><a href="Javascript:Push_Send_all(<?= $p_year.$p_month; ?>)" class="btn-border-b">ﾒｰﾙ一括送信</a></p>
							<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
								<tr>
									<th class="tbd_th_c" >状況</th>
									<th class="tbd_th_c" >出荷日</th>
									<th class="tbd_th_c" >発送NO.</th>
									<th class="tbd_th_c" ></th>
									<th class="tbd_th_c" >名前</th>
									<th class="tbd_th_c" >コース</th>
									<th class="tbd_th_c" >量</th>
									<th class="tbd_th_c" >金額</th>
									<th class="tbd_th_c" title="全文">備考※</th>
									<th class="tbd_th_c" >受取依頼</th>
								</tr>
							<?
							while ($row = $rs->fetch_array()) {
								//明細設定
								if($row['status'] == "エラー") { ?>
									<tr style="background-color:#ff6347;">
								<? }else if($row['status'] == "発送準備中" || $row['status'] == "配送中") { ?>
									<tr style="background-color:yellow;">
								<? }else if (($rowcnt % 2) == 0) { ?>
									<tr>
								<? } else { ?>
									<tr style="background-color:#EDEDED;">
								<? }
								$rowcnt = $rowcnt +1; ?>
									<!-- ステータス -->
									<td class="tbd_td_p1_c">
										<?php echo $row['status']; ?>
										<? if($row['status'] == "配送中"){ ?>
											<br>(<a href="https://k2k.sagawa-exp.co.jp/p/web/okurijosearch.do?okurijoNo=<?= $row['slipnumber'] ?>" target="_blank"><?= $row['slipnumber'] ?></a>)
										<? }else if($row['status'] == "受取完了"){
											echo "<br>(".date('Y/n/j', strtotime($row['receive_date'])).")";
										} ?>
									</td>
									<!-- 出荷日 -->
									<td class="tbd_td_p1_c"><?php if($row['ship_date'] == "0000-00-00 00:00:00"){echo "-";}else{echo date('Y/n/j', strtotime($row['ship_date']));} ?></td>
									<!-- インデックス -->
									<td width="70" class="tbd_td_p1_c"><?php echo str_pad($row['ship_idxnum'], 6, "0", STR_PAD_LEFT) ?></td>
									<!-- 初回/最終回チェック -->
									<td class="tbd_td_p1_c"><?php echo $row['remarks2']; ?></td>
									<!-- お名前 -->
									<td class="tbd_td_p1_l" style="vertical-align:middle;"><a href="./rice_kokyaku.php?idx=<?= $row['idxnum']; ?>" target="_blank"><?php echo $row['name'] ?></a></td>
									<!-- コース -->
									<td class="tbd_td_p1_c"><?php echo $row['category']; ?></td>
									<!-- 量 -->
									<td class="tbd_td_p1_c"><?php echo $row['weight']; ?></td>
									<!-- 金額 -->
									<td class="tbd_td_p1_r"><?php echo number_format($row['tanka'])."円"; ?></td>
									<!-- 備考 -->
									<td class="tbd_td_p1_l" title="<? echo $row['memo3'] ?>"><?php echo mb_substr($row['memo3'],0,6); ?><? if(mb_strlen($row['memo3']) > 5){echo "...";} ?></td>
									<!-- 受取依頼メール -->
									<td class="tbd_td_p1_c">
										<? if($row['re_mail_flg'] == 1){
											echo "送信済<br>(".date('y/n/j H:i:s', strtotime($row['re_mail_date'])).")";
										}else if($row['status'] == "配送中" && $row['slipnumber'] <> "" && $row['email'] <> "" && date('Ymd', strtotime($row['delivery_date'])) < date('Ymd', strtotime('-4 days'))){ ?>
											<a href="Javascript:Push_Send_Re(<? echo $row['ship_idxnum'] ?>)" class="btn-flat-border">伝票発行</a>
										<? } ?>
									</td>
								</tr>
							<? } ?>
							</table>
						</p>
						<? } ?>
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
