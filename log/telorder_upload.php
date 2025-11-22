<?php
//==================================================================================================
// ■機能概要
//   ・通販販売実績取り込み画面
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
	require_once './Classes/PHPExcel.php';
	require_once './Classes/PHPExcel/IOFactory.php';
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "通販販売実績取込";
	$prgmemo = "　通販販売実績データの取込を行います。";
	$comm->ouputlog("==== " . $prgname . " 処理開始 ====", $prgid, SYS_LOG_TYPE_INFO);

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);
	
	//担当者・会場情報を取得
	$query = " SELECT idxnum, sales_name, staff FROM php_headoffice_list ";
	$query .= " WHERE delflg=0 ";
	$query .= " AND aggregation_flg=1 ";
	$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
	$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
	if (!($rs = $db->query($query))) {
		$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
	}
	while ($row = $rs->fetch_array()) {
		$sales_name[$row['idxnum']] = $row['sales_name'];
		$staff[$row['idxnum']] = $row['staff'];
	}


	//本日日付
	$today = date('Y-m-d H:i:s');
	$comm->ouputlog("today=" . $today, $prgid, SYS_LOG_TYPE_DBUG);

	//一括登録後の表示
	if($_GET['flg'] == 1){
		$alert = '<p>登録が完了しました。</p>';
	}
	if($_GET['flg'] == 2){
		$alert = '<div style="font-size: 40px; color: red; font-weight: bold; margin-top: 30px; margin-bottom: 30px;">登録に失敗しました。</div>';
	}
	if(isset($_FILES['upload_file'])){
		$filename = $_FILES["upload_file"]["name"];
		$pro_filepath = $_FILES["upload_file"]["tmp_name"];
		$expand = mime_content_type($pro_filepath);
		$expand = mb_strtolower($expand);
		if(strpos($expand,'csv') !== false || strpos($expand,'text/plain') !== false){
			$filepath = $pro_filepath;
			$g_style = 1;
//		}else if(strpos($expand,'xlsx') !== false){
		}else{
			$filepath = $pro_filepath;
			$g_style = 2;
//		}else{
//			$alert = '<p><font color="red">csvもしくはxlsx形式のファイルで登録してください。</font></p>';
		}
	}
	if($filepath<>""){
		//CSV読み込み
		if($g_style == 1){
			$locale = 100001;
			$data = file_get_contents($filepath);// ファイルの読み込み
			$data = mb_convert_encoding($data, 'UTF-8', 'SJIS-win');// 文字コードの変換（UTF-8 → SJIS-win）
			$temp = tmpfile(); //テンポラリファイルの作成
			$meta = stream_get_meta_data($temp); //メタデータの取得
			fwrite($temp, $data); //ファイル書き込み
			rewind($temp); //ファイルポインタの位置を戻す
			//オブジェクトを生成する
			$file = new SplFileObject($meta['uri'], 'rb');
			//CSVファイルの読み込み
			$file->setFlags(
				SplFileObject::READ_CSV
			);
			$file->setCsvControl(',');
			$chk_dt = date('Y-m-d', strtotime('-7 days'));
			$g_row = 0;
			//1行ずつ値を取得する
			foreach ($file as $line) {
				//内容が空白の場合と先頭行の場合、取込済データは読み飛ばす
				if($line[0] <> "" && $line[0] <> "オーダー番号" && $line[8] <> "JSP"){
					$g_remark = "";
					$g_address = "";
					$address2 = "";
					$address3 = "";
					$address4 = "";
					$limit = 16;
					$duplicate = 0;
					//1行の要素数を調べる
					$receptionday[] = $line[3];
					$p_modelnum[] = $line[8];
					$p_order_num[] = $line[0];
					$p_buynum[] = $line[12];
					$p_cash[] = $line[13];
					$p_name[] = $line[34]."　".$line[35];
					if(mb_substr($line[39],0,1) == "0"){
						$phonenum1[] = $line[39];
					}else{
						$phonenum1[] = "0".$line[39];
					}
					if($line[39] <> $line[47]){
						if(mb_substr($line[47],0,1) == "0"){
							$phonenum2[] = $line[47];
						}else{
							$phonenum1[] = "0".$line[47];
						}
					}else{
						$phonenum2[] = "";
					}
					if (strlen($line[36]) == 5 || strlen($line[36]) == 6) {
						$g_postcd = sprintf('%07d', $line[36]);
					} else if (strlen($line[36]) != 5 && strlen($line[36]) != 6) {
						$g_postcd = $line[36];
					}
					$postcd1[] = mb_substr($g_postcd, 0, 3);
					$postcd2[] = mb_substr($g_postcd, 3);
					//住所
					$p_address1[] = $line[37];
					$address2 = "";
					$address3 = "";
					$address4 = "";
					$g_address = mb_convert_kana($line[38],"as");
					//住所を空白で分ける
					list($address2, $address3) = array_pad(explode(' ', $g_address, 2), 2, '');
					//16文字以上の場合は分割する
					if (mb_strlen($address2) > $limit) {
						$address4 = $address3;
						// 16文字目付近で数字で切らないよう調整
						$pos = $limit;
						while ($pos > 0 && preg_match('/[0-9]/u', mb_substr($address2, $pos, 1))) {
							$pos--; // 数字の途中を避ける
						}
						if ($pos == 0) $pos = $limit; // 数字ばかりの場合は妥協
						$address3 = mb_substr($address2, $pos);
						$address2 = mb_substr($address2, 0, $pos);
					}
					if (mb_strlen($address3) > $limit) {
						$pos = $limit;
						while ($pos > 0 && preg_match('/[0-9]/u', mb_substr($address3, $pos, 1))) {
							$pos--;
						}
						if ($pos == 0) $pos = $limit;
						$address4 = mb_substr($address3, $pos);
						$address3 = mb_substr($address3, 0, $pos);
					}
					/*
					if(mb_strlen($address2) > 16){
						$address4 = $address3;
						$address3 = mb_substr($address2, 16);
						$address2 = mb_substr($address2, 0, 16); 
					}if(mb_strlen($address3) > 16){
						$address4 = mb_substr($address3, 16).$address4;
						$address3 = mb_substr($address3, 0, 16); 
					}
					*/
					$p_address2[] = $address2;
					$p_address3[] = $address3;
					$p_company[] = $address4;
					$p_specified_times[] = "指定なし";
					$p_designated_day[] = "";
					$p_option[] = "なし";
					$p_mail[] = $line[48];
					if($line[2] == "代金引換"){
						$p_way[] = "2";
					}else{
						$p_way[] = "0";
					}
					$g_over = 0;
					//重複で取り込んでいないか確認
					$query = " SELECT idxnum";
					$query .= " FROM php_telorder__ ";
					$query .= " WHERE order_num = '".$line[0]."' ";
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$g_over = 1;
					}
					$over_flg[] = $g_over;
					//重複注文チェック
					$query = "SELECT GROUP_CONCAT(idxnum) as idxnum ";
					$query .= " FROM php_telorder__ ";
					$query .= " WHERE delflg = 0 ";
					$query .= " AND (name LIKE ".sprintf("'%s'", $line[34]."%".$line[35]);
					$query .= " OR phonenum1 = ".sprintf("'%s'", $line[39]);
					$query .= " OR CONCAT(address1,address2,address3) = ".sprintf("'%s'", $line[37].$line[38]);
					$query .= " )";
					$query .= " AND DATE(receptionday) > ".sprintf("'%s'", $chk_dt);
					$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
					if (!($rs = $db->query($query))) {
						$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
					}
					while ($row = $rs->fetch_array()) {
						$duplicate = $row['idxnum'];
					}
					if($duplicate > 0){
						$g_remark .= "重複注文の可能性があります。注文No.".$duplicate."のデータを確認してください。";
					}
					//備考
					$p_remark[] = $line[50].$g_remark;
					//決済方法
					$p_method[] = $line[2];
					$p_duplicate[] = $duplicate;
					++$g_row;
				}
			}
			//エンコーディングする
			mb_convert_variables('UTF-8', 'SJIS-win', $arr_code);
		//エクセル読み込み
		}else if($g_style == 2){
			//対象ファイル読込
			$objReader = PHPExcel_IOFactory::createReader('Excel2007');
			$book = $objReader->load($filepath);
			//シート設定
			$sheet = $book->getSheetByName("個人情報");
			//フォーマット確認
			$g_style = $sheet->getCellByColumnAndRow(0, 1)->getCalculatedValue();
			//はがき注文
			if($g_style=="会場名"){
				$locale =100100;
			//テレビショッピング
			}else if($g_style=="受付日"){
				$locale = 100003;
			}
			//受付日付
			$g_receptionday  = "20".mb_substr($filename,0,2)."-".mb_substr($filename,2,2)."-".mb_substr($filename,4,2);
			$nameNo = 1;
			$phonenumNo = 2;
			$postcdNo = 3;
			$address1No = 4;
			$address2_1No = 5;
			$address2_2No = 6;
			$address2_3No = 7;
			$address3No = 8;
			$modelnumNo = 9;
			$buynumNo = 10;
			$kinNo = 11;
			$designated_dayNo = 12;
			$specified_timesNo = 13;
			$option1No = 15;
			$option2No = 16;
			$option3No = 17;
			$option4No = 18;
			$startGyo = 5;
			$maxGyo = 200;
			$g_row = 0;
			$comm->ouputlog("file_style=".$file_style, $prgid, SYS_LOG_TYPE_INFO);
			for($i = $startGyo; $i <= $maxGyo; ++$i){
				//名前
				$g_name = $sheet->getCellByColumnAndRow($nameNo, $i)->getCalculatedValue();
				if($g_name <> ""){
					//名前
					$p_name[] = $g_name;
					//受付日付
					$receptionday[] = $g_receptionday;
					//郵便番号
					$g_postcd = $sheet->getCellByColumnAndRow($postcdNo, $i)->getCalculatedValue();
					if (strlen($g_postcd) == 6) {
						$g_postcd = sprintf('%07d', $g_postcd);
					}
					$postcd1[] = mb_substr($g_postcd, 0, 3);
					$postcd2[] = mb_substr($g_postcd, 3);
					//電話番号
					$phonenum1[] = $sheet->getCellByColumnAndRow($phonenumNo, $i)->getCalculatedValue();
					//住所１
					$p_address1[] = $sheet->getCellByColumnAndRow($address1No, $i)->getCalculatedValue();
					//住所２
					$address2_1 = $sheet->getCellByColumnAndRow($address2_1No, $i)->getCalculatedValue();
					$address2_2 = $sheet->getCellByColumnAndRow($address2_2No, $i)->getCalculatedValue();
					$address2_3  = $sheet->getCellByColumnAndRow($address2_3No, $i)->getCalculatedValue();
					$p_address2[] = $address2_1.$address2_2.$address2_3;
					//住所３
					$p_address3[] = $sheet->getCellByColumnAndRow($address3No, $i)->getCalculatedValue();
					//型番
					$p_modelnum[] = $sheet->getCellByColumnAndRow($modelnumNo, $i)->getCalculatedValue();
					//購入台数
					$p_buynum[] = $sheet->getCellByColumnAndRow($buynumNo, $i)->getCalculatedValue();
					//到着指定日付
					$p_designated_day[] = $sheet->getCellByColumnAndRow($designated_dayNo, $i)->getCalculatedValue();
					//到着指定時間
					$p_specified_times[] = $sheet->getCellByColumnAndRow($specified_timesNo, $i)->getCalculatedValue();
					//支払い方法
					$p_way[] = "2";
					//金額
					$g_cash = $sheet->getCellByColumnAndRow($kinNo, $i)->getCalculatedValue();
					$p_cash[] = $g_cash * 100;
					//備品
					$option1  = $sheet->getCellByColumnAndRow($option1No, $i)->getCalculatedValue();
					$option2  = $sheet->getCellByColumnAndRow($option2No, $i)->getCalculatedValue();
					$option3  = $sheet->getCellByColumnAndRow($option3No, $i)->getCalculatedValue();
					$option4  = $sheet->getCellByColumnAndRow($option4No, $i)->getCalculatedValue();
					$s_p_option = $option1;
					if($option2 <> ""){
						$s_p_option .= "・".$option2;
					}if($option3 <> ""){
						$s_p_option .= "・".$option3;
					}if($option4 <> ""){
						$s_p_option .= "・".$option4;
					}
					if($s_p_option <> ""){
						$p_option[] = $s_p_option;
					}else{
						$p_option[] = "なし";
					}
					++$g_row;
				}
			}
		}
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
		#postcode1, #postcode2 { width: 3em; ime-mode: inactive; }
		#address3 { width: 15em; }
	</style>
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
	table.tbf3{
	width: 100%;
	text-align: center;
	padding: auto;
	margin: auto auto 30px;
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

	/* --- 固定テーブル --- */
	table.tbs{
	width: 1720px;
	text-align: center;
	margin: 10px;
	}
	thead.tbs_thead{
	display:block;
	width:1700px;
	}
	tbody.tbs_tbody{
	display:block;
	overflow-y:scroll;
	width:1720px;
	height:400px;
	}
	th.tbs_th_p1_c {
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	width: 250px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	th.tbs_th_p2_c {
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	width: 110px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	th.tbs_th_p3_c {
	color: white;
	background-color: #2B8225; /* 見出しセルの背景色 */
	width: 400px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid white;
	text-align: center;
	}
	td.tbs_td_p1 {
	width: 250px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: right;
	}
	td.tbs_td_p2 {
	width: 110px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: right;
	}
	td.tbs_td_p1_l {
	width: 250px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: left;
	}
	td.tbs_td_p2_l {
	width: 110px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: left;
	}
	td.tbs_td_p3_l {
	width: 400px;
	padding: 10px 5px 7px; /* データセルのパディング（上、左右、下） */
	border: 1px solid gray;
	text-align: left;
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
	<script type="text/javascript">
		//編集ボタン
		function Mclk_Stat(cnt_row){
			//画面項目設定
			document.forms['frm'].action = './ecommerce_sql.php?do=upload_t';
			document.forms['frm'].submit();
		}
		function Mclk_Upload(){
			document.forms['frm'].action = "./telorder_upload.php";
			document.forms['frm'].submit();
		}
	</script>
</head>

<body>
<br>
<div id="container">
	<table class="base" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
		<!-- ヘッダー情報生成 -->
		<div id="header">
			<p><img src="images/logo_ecommerce.png" alt="" /></p>
		</div>
	</table>
	<div id="contents">
		<div id="main">
			<?php echo $prgmemo; ?>
			<div id="formWrap">
				<?php echo $alert ?>
				<form name="frm" method = "post" enctype="multipart/form-data" action="./barcode_upload_manual.php">
					<h2>ファイル情報</h2><br>
					<div>
						<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<tr>
								<th class="tbd_th"><strong>ファイル</strong></th>
								<td class="tbd_req"></td>
								<td class="tbd_td">
									<input type="file" name="upload_file" size="30" /><br />
									<input type="hidden" name="mode" value="upload" /><br />
								</td>
							</tr>
						</table>
						<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
							<td class="tbf3_td_p1_c"><input type="button" name="アップロード" value="アップロード" onclick="javascript:Mclk_Upload()"></td>
							<td class="tbf3_td_p2_c"><a href="#" onClick="window.close(); return false;"><input type="button" value="閉じる"></a></td>
						</table>
					</div>
					<br>
					<div>
					<?php if($filepath <> ""){ ?>
						<h2>取得データ一覧</h2><br>
						<p style="text-align:right"><span style="background-color:#ffc0cb;">　　　　</span>　重複データ　取込対象外</p>
						<? echo "販売方法：".$sales_name[$locale] ?>
						<table class="tbs" summary="ベーステーブル" >
							<thead class="tbs_thead">
								<tr>
									<th class="tbs_th_p2_c">オーダー番号</td>
									<th class="tbs_th_p2_c">注文日</td>
									<th class="tbs_th_p2_c">型番<br>備品</td>
									<th class="tbs_th_p2_c">台数<br>金額</td>
									<th class="tbs_th_p2_c">名前</td>
									<th class="tbs_th_p2_c">電話番号<br>メールアドレス</td>
									<th class="tbs_th_p1_c">郵便番号<br>住所</td>
									<th class="tbs_th_p1_c">到着指定日時</td>
									<th class="tbs_th_p1_c">備考</td>
								</tr>
							</thead>
							<tbody class="tbs_tbody">
								<?php for($i=0; $i<$g_row; ++$i){
									if($over_flg[$i] == 1){ ?>
										<tr style="background-color:#ffc0cb">
									<? }else{ ?>
										<tr>
									<? } ?>
										<td class="tbs_td_p2">
											<?php echo $p_order_num[$i]; ?>
											<input type="text" name="注文番号<?php echo $i ?>" style="display:none" value="<?php echo $p_order_num[$i]; ?>">
										</td>
										<td class="tbs_td_p2">
											<?php echo date('Y/n/j', strtotime($receptionday[$i])); ?>
											<input type="text" name="注文日<?php echo $i ?>" style="display:none" value="<?php echo date('Y-m-d H:i:s', strtotime($receptionday[$i])); ?>">
										</td>
										<td class="tbs_td_p2_l">
											<?php echo $p_modelnum[$i]; ?>
											<input type="text" name="型番<?php echo $i ?>" style="display:none" value="<?php echo $p_modelnum[$i]; ?>"><br>
											<?php echo $p_option[$i]; ?>
											<input type="text" name="備品<?php echo $i ?>" style="display:none" value="<?php echo $p_option[$i]; ?>">
										</td>
										<td class="tbs_td_p2">
											<?php echo $p_buynum[$i]; ?>台<br>
											<input type="text" name="台数<?php echo $i ?>" style="display:none" value="<?php echo $p_buynum[$i]; ?>">
											<?php echo $p_cash[$i]; ?>円
											<input type="text" name="金額<?php echo $i ?>" style="display:none" value="<?php echo $p_cash[$i]; ?>">
										</td>
										<td class="tbs_td_p2_l">
											<?php echo $p_name[$i]; ?>
											<input type="text" name="名前<?php echo $i ?>" style="display:none" value="<?php echo $p_name[$i]; ?>">
										</td>
										<td class="tbs_td_p2_l">
											<?php echo $phonenum1[$i]; ?><br>
											<?php echo $phonenum2[$i]; ?><br>
											<?php echo $p_mail[$i]; ?>
											<input type="text" name="電話番号１<?php echo $i ?>" style="display:none" value="<?php echo $phonenum1[$i]; ?>">
											<input type="text" name="電話番号２<?php echo $i ?>" style="display:none" value="<?php echo $phonenum2[$i]; ?>">
											<input type="text" name="メールアドレス<?php echo $i ?>" style="display:none" value="<?php echo $p_mail[$i]; ?>">
										</td>
										<td class="tbs_td_p1_l">
											<?php echo $postcd1[$i]."-".$postcd2[$i]; ?><br>
											<input type="text" name="郵便番号１<?php echo $i ?>" style="display:none" value="<?php echo $postcd1[$i]; ?>">
											<input type="text" name="郵便番号２<?php echo $i ?>" style="display:none" value="<?php echo $postcd2[$i]; ?>">
											<?php echo $p_address1[$i].$p_address2[$i].$p_address3[$i].$p_company[$i]; ?>
											<input type="text" name="住所１<?php echo $i ?>" value="<?php echo $p_address1[$i]; ?>">
											<input type="text" name="住所２<?php echo $i ?>" value="<?php echo $p_address2[$i]; ?>">
											<input type="text" name="住所３<?php echo $i ?>" value="<?php echo $p_address3[$i]; ?>">
											<input type="text" name="会社名<?php echo $i ?>" value="<?php echo $p_company[$i]; ?>">
										</td>
										<td class="tbs_td_p1_l">
											<input type="date" name="到着指定日付<?php echo $i ?>" value="<?php echo $p_designated_day[$i]; ?>"><br>
											<select name="到着指定時間<?php echo $i ?>">
												<option value="指定なし" <? if($p_specified_times[$i]=="指定なし"){echo "selected='selected'";} ?>"">指定なし</option>
												<option value="0812" <? if($p_specified_times[$i]=="0812"){echo "selected='selected'";} ?>"">午前中</option>
												<option value="1416" <? if($p_specified_times[$i]=="1416"){echo "selected='selected'";} ?>"">1416</option>
												<option value="1618" <? if($p_specified_times[$i]=="1618"){echo "selected='selected'";} ?>"">1618</option>
												<option value="1820" <? if($p_specified_times[$i]=="1820"){echo "selected='selected'";} ?>"">1820</option>
												<option value="1921" <? if($p_specified_times[$i]=="1921"){echo "selected='selected'";} ?>"">1921</option>
											</select>
										</td>
										<td class="tbs_td_p1_l">
											<textarea name="備考<?php echo $i ?>"cols="25" rows="5"><?php echo $p_remark[$i]; ?></textarea>
										</td>
										<td class="tbs_td_p2" style="display:none">
											<input type="text" name="重複インデックス<?php echo $i ?>" style="display:none" value="<?php echo $p_duplicate[$i]; ?>">
											<input type="text" name="支払方法<?php echo $i ?>" style="display:none" value="<?php echo $p_way[$i]; ?>">
											<input type="text" name="決済方法<?php echo $i ?>" style="display:none" value="<?php echo $p_method[$i]; ?>">
											<input type="text" name="重複フラグ<?php echo $i ?>" style="display:none" value="<?php echo $over_flg[$i]; ?>">
											<input type="text" name="end<?php echo $i ?>">
										</td>
									</tr>
								<?php } ?>
								<tr style="display:none">
									<td>
										<input type="text" name="locale"  value="<?php echo $sales_name[$locale]; ?>">
										<input type="text" name="販売方法" value="<?php echo $locale; ?>">
										<input type="text" name="担当者" value="<?php echo $staff[$locale]; ?>">
									</td>
								</tr>
							</tbody>
						</table>
						<p><?php echo  $g_row ?>行のデータを取得しました。</p>
					</div>
					<table class="tbf3" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル">
						<td class="tbf3_td_p1_c"><input type="button" name="一括登録" value="一括登録" onclick="javascript:Mclk_Stat(<?php echo $g_row ?>)"></td>
						<td class="tbf3_td_p2_c"><a href="#" onClick="window.close(); return false;"><input type="button" value="閉じる"></a></td>
					</table>
					<?php } ?>
				</form>
			</div>
		</div>
	</div>
</div>
</body>

<!-- データベース切断 -->
<?php if ($result) { $dba->mysql_discon($db); } ?>

</html>
