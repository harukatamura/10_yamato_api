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
	//タイムゾーン
	date_default_timezone_set('Asia/Tokyo');

	//オブジェクト生成
	$comm = new comm();
	$dba = new dbaccess();

	//実行プログラム名取得
	$prgid = str_replace(".php","",basename($_SERVER['PHP_SELF']));
	$prgname = "個人情報入力メニュー";

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
	//1:時間別　2:カテゴリ別　3:地域別　4:購入日別
	$p_kbn = 1;
	if (isset($_GET['kbn'])) {
		$p_kbn = $_GET['kbn'];
	}

	// ================================================
	// ■　□　■　□　本日利用者取得　■　□　■　□
	// ================================================
	//
	$sum = 0;
	// StoresAPIよりオーダ一覧情報を取得
	$a_members = m_callAPI_getMembers(date('Y-m-d'));
	//$a_members = m_callAPI_getMembers("2025-11-07");
	$comm->ouputlog("json=".$a_members, $prgid, SYS_LOG_TYPE_INFO);

	$colName = "";
	if ($p_kbn == 1) {
		$colName = "時間";
		// ================================================
		// ■　□　■　□　時間別利用数集計　■　□　■　□
		// ================================================
		for ($i=0;$i<count($a_members);$i++) {
			if ($a_members[$i]['post'] != '') {
				//対象時間取得
				$date = new DateTime($a_members[$i]['usedate']);
				$time = $date->format('H:00');

				//対象時間単位に集計
				$flg = 0;
				for ($j=0;$j<count($display_list);$j++) {
					if ($display_list[$j]['item'] == $time) {
						$display_list[$j]['num']++;
						$sum++;
						$flg = 1;
						break;
					}
				}
				if ($flg==0) {
					$display_list[] = ['item' => $time,'num' => 1];
					$sum++;
				}
			}
		}
		// ================================================
		// ■　□　■　□　並び替え　■　□　■　□
		// ================================================
		// Age と Name の列を取得
		$num = array_column($display_list, 'item');
		// まず age でソート、次に name でソート
		array_multisort($num, SORT_ASC, $display_list);
	}
	if ($p_kbn == 2) {
		$colName = "CPU";
		// ================================================
		// ■　□　■　□　時間別利用数集計　■　□　■　□
		// ================================================
		$display_list = [
			['item' => 'Celeron','num' => 0],
			['item' => 'i3','num' => 0],
			['item' => 'i5','num' => 0],
			['item' => 'i7','num' => 0],
			['item' => 'Ryzen 3','num' => 0],
			['item' => 'Ryzen 5','num' => 0],
			['item' => 'Ryzen 7','num' => 0],
			['item' => 'other','num' => 0]
		];
		$graph_list = [
			['ratio' => 0],
			['ratio' => 0],
			['ratio' => 0],
			['ratio' => 0],
			['ratio' => 0],
			['ratio' => 0],
			['ratio' => 0],
			['ratio' => 0]
		];
		for ($i=0;$i<count($a_members);$i++) {
			if ($a_members[$i]['post'] != '') {
				//対象時間単位に集計
				$flg = 0;
				//
				$cpu = $a_members[$i]['cpu'];
				for ($j=0;$j<count($display_list);$j++) {
					$serch = $display_list[$j]['item'];
					$comm->ouputlog("json=".$cpu, $prgid, SYS_LOG_TYPE_INFO);
					$comm->ouputlog("json=".$serch, $prgid, SYS_LOG_TYPE_INFO);
					if (strpos($cpu, $serch)) {
						$display_list[$j]['num']++;
						$flg = 1;
						break;
					}
				}
				if ($flg==0) {
					$display_list[count($display_list)-1]['num']++;
				}
				$sum++;
			}
		}
		// ================================================
		// ■　□　■　□　割合計算　■　□　■　□
		// ================================================
		$total = 0;
		for ($j=0;$j<count($display_list)-1;$j++) {
			//割合計算
			$display_list[$j]['num'] = round($display_list[$j]['num'] / $sum * 100,1);
			//積み上げ
			$graph_list[$j]['ratio'] = $display_list[$j]['num'] + $total;
			//
			$total += $display_list[$j]['num'];
		}
		//
		$display_list[count($display_list)-1]['num'] = 0;
		$graph_list[count($graph_list)-1]['ratio'] = 0;
		if ($total < 100) {
			$display_list[count($display_list)-1]['num'] = round(100 - $total);
			$graph_list[count($graph_list)-1]['ratio'] = round(100 - $total,1);
		}
	}
	if ($p_kbn == 3) {
		$colName = "都道府県";
		// ================================================
		// ■　□　■　□　郵便番号データ抽出　■　□　■　□
		// ================================================
		$query = "
			SELECT
				LEFT(postcd_n, 3) as postcd
				,add1_kz
				,0 AS num
			FROM
				php_postcd
			WHERE 1
			GROUP BY
				postcd
				,add1_kz;
		";
		$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
		$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
		if (!($rs = $db->query($query))) {
			$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
		}
		while ($row = $rs->fetch_array()) {
			$p_postcdlist[] = $row;
		}

		// ================================================
		// ■　□　■　□　都道府県別利用数集計　■　□　■　□
		// ================================================
		for ($i=0;$i<count($a_members);$i++) {
			if ($a_members[$i]['post'] != '') {
				//対象都道府県取得
				$keyIndex = array_search(substr($a_members[$i]['post'],0,3), array_column($p_postcdlist, 'postcd'));
				//
				$prefectures = $p_postcdlist[$keyIndex]['add1_kz'];
				//都道府県単位に集計
				$flg = 0;
				for ($j=0;$j<count($display_list);$j++) {
					if ($display_list[$j]['item'] == $prefectures) {
						$display_list[$j]['num']++;
						$sum++;
						$flg = 1;
						break;
					}
				}
				if ($flg==0) {
					$display_list[] = ['item' => $prefectures,'num' => 1];
					$sum++;
				}
			}
		}
		// ================================================
		// ■　□　■　□　並び替え　■　□　■　□
		// ================================================
		// Age と Name の列を取得
		$num = array_column($display_list, 'num');
		// まず age でソート、次に name でソート
		array_multisort($num, SORT_DESC, $display_list);
	}
	if ($p_kbn == 4) {
		$colName = "購入日";
		// ================================================
		// ■　□　■　□　購入日別利用数集計　■　□　■　□
		// ================================================
		for ($i=0;$i<count($a_members);$i++) {
			if ($a_members[$i]['post'] != '') {
				//購入日単位に集計
				$flg = 0;
				for ($j=0;$j<count($display_list);$j++) {
					if ($display_list[$j]['item'] == $a_members[$i]['shop']) {
						$display_list[$j]['num']++;
						$sum++;
						$flg = 1;
						break;
					}
				}
				if ($flg==0) {
					$display_list[] = ['item' => $a_members[$i]['shop'],'num' => 1];
					$sum++;
				}
			}
		}
		// ================================================
		// ■　□　■　□　並び替え　■　□　■　□
		// ================================================
		// Age と Name の列を取得
		$num = array_column($display_list, 'item');
		// まず age でソート、次に name でソート
		array_multisort($num, SORT_DESC, $display_list);

		if (count($a_members) > 0) {
			// ================================================
			// ■　□　■　□　会場情報抽出　■　□　■　□
			// ================================================
			$query = "
				SELECT
					CONCAT( REPLACE(A.buydt,'-',''), LPAD( A.lane, 2,  '0' ), '-' , A.branch ) AS venueid
					,B.prefecture
				FROM
					php_performance A
					LEFT OUTER JOIN php_facility B
						ON A.facility_id = B.facility_id 
				WHERE 
					CONCAT( REPLACE(A.buydt,'-',''), LPAD( A.lane, 2,  '0' ), '-' , A.branch ) IN (
			";
			//変数初期化
			$flg = 0;
			for ($j=0;$j<count($display_list);$j++) {
				if ($display_list[$j]['item'] != '') {
					if ($flg == 1) {
						$query .= ",";
					}
					$vid = $display_list[$j]['item'];
					$query .= "'$vid'";
					$flg = 1;
				}
			}
			$query .= "
				);
			";
			$comm->ouputlog("データ抽出 実行", $prgid, SYS_LOG_TYPE_INFO);
			$comm->ouputlog($query, $prgid, SYS_LOG_TYPE_DBUG);
			if (!($rs = $db->query($query))) {
				$comm->ouputlog("☆★☆データ追加エラー☆★☆ " . $db->errno . ": " . $db->error, $prgid, SYS_LOG_TYPE_ERR);
			}
			while ($row = $rs->fetch_array()) {
				$p_venueid[] = $row;
			}
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
<title>Jemtc Office</title>
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
	background-repeat: no-repeat;			/*背景をリピートしない*/
	background-position: center top;		/*背景を中央、上部に配置*/
}
#formWrap {
	width:600px;
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
	width: 600px;	/*コンテナー幅*/
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
.pie-chart-3 {
    display: flex;
    align-items: center;
}

.pie-chart-3 > div {
    width: 300px;
    height: 300px;
    margin: 0;
    border-radius: 50%;
    background-image: 
		conic-gradient(
		 #1c0485ff <? echo $graph_list[0]['ratio'];?>% /* Celeron */
		, #0b07dbff <? echo $graph_list[0]['ratio'];?>% <? echo $graph_list[1]['ratio'];?>% /* Ci3 */
		, #5ba9f7 <? echo $graph_list[1]['ratio'];?>% <? echo $graph_list[2]['ratio'];?>% /* Ci5 */
		, #5be0f7ff <? echo $graph_list[2]['ratio'];?>% <? echo $graph_list[3]['ratio'];?>% /* Ci7 */
		, #f75ba9ff <? echo $graph_list[3]['ratio'];?>% <? echo $graph_list[4]['ratio'];?>% /* Ryzen 3 */
		, #f18fc0ff <? echo $graph_list[4]['ratio'];?>% <? echo $graph_list[5]['ratio'];?>% /* Ryzen 5 */
		, #f7b8d8ff <? echo $graph_list[5]['ratio'];?>% <? echo $graph_list[6]['ratio'];?>% /* Ryzen 7 */
		, #f2f2f2 <? echo $graph_list[6]['ratio'];?>% 100%); /* other */
}

.pie-chart-3 li {
    display: flex;
    list-style-type: none;
    align-items: center;
    font-size: .8em;
}

.pie-chart-3 li::before {
    display: inline-block;
    width: 1.2em;
    height: .8em;
    margin-right: 5px;
    content: '';
}

.pie-chart-3 li:nth-child(1)::before {
    background-color: #1c0485ff;
}

.pie-chart-3 li:nth-child(2)::before {
    background-color: #060494ff;
}
.pie-chart-3 li:nth-child(3)::before {
    background-color: #5ba9f7;
}
.pie-chart-3 li:nth-child(4)::before {
    background-color: #5be0f7ff;
}
.pie-chart-3 li:nth-child(5)::before {
    background-color: #f75ba9ff;
}
.pie-chart-3 li:nth-child(6)::before {
    background-color: #f18fc0ff;
}
.pie-chart-3 li:nth-child(7)::before {
    background-color: #f7b8d8ff;
}
.pie-chart-3 li:nth-child(8)::before {
    background-color: #f2f2f2;
}

.pie-chart-3 span {
    margin-right: 10px;
    font-weight: 600;
}
</style>
<script type="text/javascript">
	<!--
	function hpbmapinit() {
		hpbmaponload();
	}
	//-->
</script>
</head>
<body>
	<!--container-->
	<div id="container">
			<!--contents-->
			<div id="contents">
				<div id="main">
				<?
				if ($p_kbn == 2) {
				?>
					<br><br><br>
					<div class="pie-chart-3">
						<div></div>
						<ol>
						<?
						for ($i=0;$i<count($display_list);$i++) {
						?>
							<li><span><? echo $display_list[$i]['item']?></span><? echo $display_list[$i]['num']?>%</li>
						<?
						}
						?>
						</ol>
					</div>
				<?
				} else {
				?>
					<table class="tbd" cellspacing="0" cellpadding="0" border="0" summary="ベーステーブル" id= "TBL" style="width:500px;">
					<tr>
						<th class="tbd_th_p1"><strong><? echo $colName;?></strong></th>
						<th class="tbd_th_p1"><strong>使用数</strong></th>
						<th class="tbd_th_p1"><strong></strong></th>
					</tr>
					<?
					//変数初期化
					$rowcnt = 0;
					for ($i=0;$i<count($display_list);$i++) {
						if ($display_list[$i]['num'] == 0 && $p_kbn <> 2) {
							break;
						}
						if (($rowcnt % 2) == 0) {
					?>
						<tr>
					<?
						} else {
					?>
						<tr style="background-color:#EDEDED;">
					<?
						}
						$rowcnt++;
					?>
							<?
							$item = "";
							if ($p_kbn == 4) {
								$comm->ouputlog("display_list=".$display_list[$i]['item'], $prgid, SYS_LOG_TYPE_INFO);
								for ($j=0;$j<count($p_venueid);$j++) {
									if ($p_venueid[$j]['venueid'] == $display_list[$i]['item']) {
										// 日付 + 都道府県
										$item = date('Y/m/d', strtotime(substr($display_list[$i]['item'],0,8))) . " " .$p_venueid[$j]['prefecture'];
										break;
									}
								}
							?>
							<td class="tbd_td_p2_l"><? echo $item ?></td>
							<?
							} else {
								$item = $display_list[$i]['item'];
							?>
							<td class="tbd_td_p1_l"><? echo $item ?></td>
							<?
							}
							?>
							<td class="tbd_td_p1_r"><? echo $display_list[$i]['num'] ?></td>
							<?
							$img = "";
							$cnt = 0;
							for ($j=0;$j<$display_list[$i]['num'];$j++) {
								$cnt++;
								if ($cnt == 50) {
									$img .=  "<img src='images/g-1.png'>";
									$cnt = 0;
								}
							}
							?>
							<td class="tbd_td_p2_l">
								<? echo $img ?>
							</td>
						</tr>
					<?
					}
					?>
					</table>
				<?
				}
				?>
				</div>
			</div>
		<!--/contents-->
	</div>
	<!--/container-->
</body>
</html>
