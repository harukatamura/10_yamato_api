<?php
//==================================================================================================
// ■機能概要
//   ・社内関連メニュー
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani　【対応不要】
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
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$html = new html();
	$comm = new comm();
	$dba = new dbaccess();

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "個人情報管理";

	//データベース接続
	$db = "";
	$result = $dba->mysql_con($db);

	//日付設定
	$datetime = new DateTime();
	$week = array("日", "月", "火", "水", "木", "金", "土");
	$w = (int)$datetime->format('w');
	$today = date('Y/m/d') . "(" .  $week[$w] . ")";

	//----------------------------------------------------------------------------------------------
	// 引数取得処理
	//----------------------------------------------------------------------------------------------
	//表示内容
	//1:時間別　2:カテゴリ別　3:地域別
	$p_kbn = 0;
	if (isset($_GET['kbn'])) {
		$p_kbn = $_GET['kbn'];
	}
	$p_kbn = 1;

	// ================================================
	// ■　□　■　□　本日利用者取得　■　□　■　□
	// ================================================
	//
	$sum = 0;
	// StoresAPIよりオーダ一覧情報を取得
	$a_members = m_callAPI_getMembers(date('Y-m-d'));
	//$a_members = m_callAPI_getMembers("2025-11-07");
	$comm->ouputlog("json=".$a_members, $prgid, SYS_LOG_TYPE_INFO);

	//件数取得
	for ($i=0;$i<count($a_members);$i++) {
		if ($a_members[$i]['post'] != '') {
			$sum++;
		}
	}

    function m_callAPI_getMembers($usedate)
    {
        // 定義
        $apiurl = 'https://jmpop.net/PopNote/WebAPI/local/extsv_ref_mem_latelyuse/';    // URL
        $token = 'L2YyL8Wo.oVwD1_TGehdjonm5r62.6KYVTGMXKjzc+UlmL1uZqQQ_1-OvaL8TJZu';            // token
        $method = 'GET';

        // クエリパラメータ設定
        $param = '';
        $param .= '?'.'usedate='.$usedate;
        $apiurl .= $param;
                
        // ヘッダー生成
        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ];

		// ストリームコンテキストのオプションを作成
		$options = array(
			// HTTPコンテキストオプションをセット
			'http' => array(
				"protocol_version" => "1.1",
				'method'=> 'GET',
				'header'=> $headers
			)
		);

        try
        {
			$ch = curl_init($apiurl);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $token,
				'Content-Type: application/json' // 必要に応じて
			));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$raw_data = curl_exec($ch);
			curl_close($ch);
/*
			// ストリームコンテキストの作成
			$context = stream_context_create($options);

			$raw_data = file_get_contents($apiurl, false,$context);

*/
            // jsonをオブジェクトへ変換
            return json_decode($raw_data, true);

        } catch (\Throwable $e) {
            throw $e;
        }
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta http-equiv="Refresh" content="30;URL='./<? echo $prgid ?>.php'">
<link rel="icon" type="image/x-icon" href="images/favicon.png">
<title>個人情報管理</title>
<script src="http://maps.google.com/maps/api/js?sensor=false" charset="UTF-8" type="text/javascript"></script>
<script src="js/hpbmapscript1.js" charset="UTF-8" type="text/javascript">HPBMAP_20150620053222</script>

<script src="./js/jquery-1.11.1.min.js" charset="UTF-8" type="text/javascript"></script>
<script src="./js/memf.js" charset="UTF-8" type="text/javascript"></script>

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
	width:1000px;
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
	border:1px solid #FF8C00;
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
 background-image:url("./images/satei.jpg");
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
	height: 40px;
	width: 100%;
	padding-left: 40px;
	overflow: hidden;
}
/*段落タグの余白設定*/
#main p {
	padding: 0.5em 10px 1em;	/*左から、上、左右、下への余白*/
}
/*ヘッダー（ロゴが入っている最上段のブロック）
---------------------------------------------------------------------------*/
#header {
	background-repeat: no-repeat;
	height: 100px;	/*ヘッダーの高さ*/
	width: 100%;
	position: relative;
}
/*h1タグ設定*/
#header h1 {
	font-size: 10px;	/*文字サイズ*/
	line-height: 16px;	/*行間*/
	position: absolute;
	font-weight: normal;	/*文字サイズをデフォルトの太字から標準に。太字がいいならこの１行削除。*/
	right: 0px;		/*ヘッダーブロックに対して、右側から0pxの位置に配置*/
	bottom: 0px;	/*ヘッダーブロックに対して、下側から0pxの位置に配置*/
}
#header h1 a {
	text-decoration: none;
}
/*ロゴ画像設定*/
#header #logo {
	position: absolute;
	left: 10px;	/*ヘッダーブロックに対して、左側から10pxの位置に配置*/
	top: 12px;	/*ヘッダーブロックに対して、上側から12pxの位置に配置*/
}

/*コンテンツ（左右ブロックとフッターを囲むブロック）
---------------------------------------------------------------------------*/
#contents {
	clear: left;
	width: 100%;
	padding-top: 4px;
}

.btn-square-pop {
  position: relative;
  display: inline-block;
  padding: 0.25em 0.5em;
  text-decoration: none;
  color: #FFF;
  background: #fd9535;/*背景色*/
  border-bottom: solid 2px #d27d00;/*少し濃い目の色に*/
  border-radius: 4px;/*角の丸み*/
  box-shadow: inset 0 2px 0 rgba(255,255,255,0.2), 0 2px 2px rgba(0, 0, 0, 0.19);
  font-weight: bold;
  width: 200px;
  text-align: center;
}

.btn-square-pop:active {
  border-bottom: solid 2px #fd9535;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.30);
}

.btn-square-pop2 {
  position: relative;
  display: inline-block;
  padding: 0.25em 0.5em;
  text-decoration: none;
  color: #FFF;
  background: #e635fd38;/*背景色*/
  border-bottom: solid 2px #e635fd96;/*少し濃い目の色に*/
  border-radius: 4px;/*角の丸み*/
  box-shadow: inset 0 2px 0 rgba(255,255,255,0.2), 0 2px 2px rgba(0, 0, 0, 0.19);
  font-weight: bold;
  width: 200px;
  text-align: center;
}

.btn-square-pop2:active {
  border-bottom: solid 2px #e635fd38;
  box-shadow: 0 0 2px rgba(0, 0, 0, 0.30);
}
/* --- ヘッダーセル（th） --- */
th.tbd_th_p1 {
padding: 5px 4px; /* 見出しセルのパディング（上下、左右） */
color: white;
background-color: #2B8225; /* 見出しセルの背景色 */
border: 1px solid white;
text-align: center;
line-height: 130%;
}
td.tbd_td_p1_r {
width: 100px;
padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
border: 1px solid white;
text-align: right;
}
td.tbd_td_p1_l {
width: 60px;
padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
border: 1px solid white;
text-align: left;
}
td.tbd_td_p2_l {
width: 200px;
padding: 5px 5px 5px; /* データセルのパディング（上、左右、下） */
border: 1px solid white;
text-align: left;
}
.field {
display: flex;
}
</style>
<script type="text/javascript">
	<!--
	function hpbmapinit() {
		hpbmaponload();
	}
	//-->
</script>
<title>J-OFFICE | 会場予定表</title>
<?php $html->output_htmlheadinfo3($prgname); ?>
</head>
<body>
	<!--container-->
	<div id="container">
			<!--contents-->
			<div id="contents">
				<div id="main">
					<h2>本日の利用状況　　※合計：<? echo $sum ?>件</h2>
					<p><img src='images/g-1.png'>:1つのメモリは50使用数分となります。</p>
					<div class="field">
						<div>
							<h3><strong>■◇■時間別■◇■</strong></h3>
							<iframe width="500" height="500" src="https://j-office.work/office/pinfo_lank.php?kbn=1"></iframe>
						</div>
						　　　
						<div>
							<h3><strong>■◇■地域別■◇■</strong></h3>
							<iframe width="500" height="500" src="https://j-office.work/office/pinfo_lank.php?kbn=3"></iframe>
						</div>
						　　　
						<div>
							<h3><strong>■◇■購入日別■◇■</strong></h3>
							<iframe width="600" height="500" src="https://j-office.work/office/pinfo_lank.php?kbn=4"></iframe>
						</div>
					</div>
					<div class="field">
						<div>
							<h3><strong>■◇■CPU別■◇■</strong></h3>
							<iframe width="500" height="500" src="https://j-office.work/office/pinfo_lank.php?kbn=2"></iframe>
						</div>
					</div>
				</div>
			</div>
		<!--/contents-->
	</div>
	<!--/container-->
</body>
</html>
