<?php
//==================================================================================================
// ■機能概要
//   ・ログイン
//
// ■履歴
//   2019.06 バージョン更新対応 (PHP5.4.16 → PHP7.0.33)	K.Mizutani
//==================================================================================================

		//----------------------------------------------------------------------------------------------
		// 初期処理
		//----------------------------------------------------------------------------------------------
		//ファイル読込
		require_once("./lib/comm.php");
		require_once("./lib/define.php");
		require_once("./lib/dbaccess.php");
		require_once("./lib/html.php");
		//タイムゾーン
		date_default_timezone_set('Asia/Tokyo');

		//オブジェクト生成
		$comm = new comm();
		$html = new html();
		$dba = new dbaccess();
		//データベース接続
		$result = $dba->mysql_con($db);
		$e_login="";

		//----------------------------------------------------------------------------------------------
		// ログイン処理
		//----------------------------------------------------------------------------------------------
		if (isset($_GET['proc'])) {
			if ($_GET['proc'] == "login") {
				//引数の取得(POST)
				if (!empty($_POST['uid'])) {
					$p_uid = $_POST['uid'];
				}
				else {
					$e_login = "<font color=\"red\"><br>ユーザIDの入力がありません。</font>";
				}
				if (empty($e_login)) {
					if (!empty($_POST['pwd'])) {
						$p_pwd = $_POST['pwd'];
					}
					else {
						$e_login = "<font color=\"red\"><br>パスワードの入力がありません。</font>";
					}
				}
				if (empty($e_login)) {
					//パスワード情報取得
					$query = "SELECT
								staff
								,Authority
								,accounting
								,schedule
								,schedule_b
								,companycd
								,Manager
								,upddt
								,updcount
								,kintaiflg
								,tm
								,ns
								,repair
								,leaderflg
								,password
								,scrutiny
								,expensesflg
								,tel_mng_flg
								,nsorder_mng_flg
								,attendance_mng_flg
								,inquiry_hanbai_flg
								,bank_flg
								,high_spec_flg
								,zaiko_flg
								,facflg
								,shipping_flg
								,slip_flg
							FROM php_l_user
							WHERE userid = '".$p_uid."' ";
					if (!($rs = $db->query($query))) {echo "データ取得エラー";}
					else {
						if ($rs->num_rows > 0) {
							while ($row = $rs->fetch_array()) {
								$p_staff = $row['staff'];	//担当者
								$p_Authority = $row['Authority'];	//権限
								$p_accounting = $row['accounting'];	//経理
								$p_schedule = $row['schedule'];	//スケジュール
								$p_schedule_b = $row['schedule_b'];	//バイトスケジュール
								$p_companycd = $row['companycd'];	//会社コード
								$p_Manager = $row['Manager'];	//経営者
								$p_upddt = $row['upddt'];	//更新日時
								$updcount = $row['updcount'];	//更新回数
								$p_kintaiflg = $row['kintaiflg'];	//勤怠対象者
								$p_tm = $row['tm'];	//タウンメール担当者
								$p_ns = $row['ns'];	//ネット通販担当者
								$p_repair = $row['repair'];	//再生通販担当者
								$p_leaderflg = $row['leaderflg'];	//リーダーフラグ
								$p_pass = $row['password'];	//パスワード
								$p_scrutiny = $row['scrutiny'];	//精査
								$p_expensesflg = $row['expensesflg'];	//経費
								$p_tel_mng_flg = $row['tel_mng_flg'];	//電話対応管理
								$p_nsorder_mng_flg = $row['nsorder_mng_flg'];	//ネット注文管理
								$p_attendance_mng_flg = $row['attendance_mng_flg'];	//勤怠管理
								$p_inquiry_hanbai_flg = $row['inquiry_hanbai_flg'];	//販売実績
								$p_bank_flg = $row['bank_flg'];	//銀行
								$p_h_flg = $row['high_spec_flg'];	//ハイスペックPCフラグ
								$p_z_flg = $row['zaiko_flg'];	//在庫フラグ
								$p_fac_flg = $row['facflg'];	//会場取フラグ
								$p_shipping_flg = $row['shipping_flg'];	//出荷管理フラグ
								$p_slip_flg = $row['slip_flg'];	//伝票発行フラグ
							}
							if (password_verify($p_pwd,$p_pass)) {
								// クッキーに保存
								setcookie ('j_office_Uid', '', time()-3600);
								setcookie ('j_office_Uid', $p_uid);
								setcookie ('j_office_Pwd', '', time()-3600);
								setcookie ('j_office_Pwd', $p_pwd);
								setcookie ('con_perf_staff', '', time()-3600);
								setcookie ('con_perf_staff', $p_staff);
								setcookie ('con_perf_Auth', '', time()-3600);
								setcookie ('con_perf_Auth', $p_Authority);
								setcookie ('con_perf_compcd', '', time()-3600);
								setcookie ('con_perf_compcd', $p_companycd);
								setcookie ('con_perf_acco', '', time()-3600);
								setcookie ('con_perf_acco', $p_accounting);
								setcookie ('con_perf_sche', '', time()-3600);
								setcookie ('con_perf_sche', $p_schedule);
								setcookie ('con_perf_sche_b', '', time()-3600);
								setcookie ('con_perf_sche_b', $p_schedule_b);
								setcookie ('con_perf_mana', '', time()-3600);
								setcookie ('con_perf_mana', $p_Manager);
								setcookie ('con_perf_upddt', '', time()-3600);
								setcookie ('con_perf_upddt', $p_upddt);
								setcookie ('con_perf_kintaiflg', '', time()-3600);
								setcookie ('con_perf_kintaiflg', $p_kintaiflg);
								setcookie ('con_perf_tm', '', time()-3600);
								setcookie ('con_perf_tm', $p_tm);
								setcookie ('con_perf_ns', '', time()-3600);
								setcookie ('con_perf_ns', $p_ns);
								setcookie ('con_perf_repair', '', time()-3600);
								setcookie ('con_perf_repair', $p_repair);
								setcookie ('con_perf_leaderflg', '', time()-3600);
								setcookie ('con_perf_leaderflg', $p_leaderflg);
								setcookie ('con_perf_scrutiny', '', time()-3600);
								setcookie ('con_perf_scrutiny', $p_scrutiny);
								setcookie ('con_perf_expensesflg', '', time()-3600);
								setcookie ('con_perf_expensesflg', $p_expensesflg);
								setcookie ('con_tel_mng', '', time()-3600);
								setcookie ('con_tel_mng', $p_tel_mng_flg);
								setcookie ('con_nsorder_mng', '', time()-3600);
								setcookie ('con_nsorder_mng', $p_nsorder_mng_flg);
								setcookie ('con_attendance_mng', '', time()-3600);
								setcookie ('con_attendance_mng', $p_attendance_mng_flg);
								setcookie ('con_inquiry_hanbai', '', time()-3600);
								setcookie ('con_inquiry_hanbai', $p_inquiry_hanbai_flg);
								setcookie ('con_perf_bank', '', time()-3600);
								setcookie ('con_perf_bank', $p_bank_flg);
								setcookie ('con_h_flg', '', time()-3600);
								setcookie ('con_h_flg', $p_h_flg);
								setcookie ('con_z_flg', '', time()-3600);
								setcookie ('con_z_flg', $p_z_flg);
								setcookie ('con_fac_flg', '', time()-3600);
								setcookie ('con_fac_flg', $p_fac_flg);
								setcookie ('con_shipping_flg', '', time()-3600);
								setcookie ('con_shipping_flg', $p_shipping_flg);
								setcookie ('con_slip_flg', '', time()-3600);
								setcookie ('con_slip_flg', $p_slip_flg);

								//ログ出力
								$comm->ouputlog("担当者=". $p_staff, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("権限=". $p_Authority, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("経理=". $p_accounting, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("スケジュール=". $p_schedule, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("バイトスケジュール=". $p_schedule_b, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("会社コード=". $p_companycd, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("経営者=". $p_Manager, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("勤怠対象者=". $p_kintaiflg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("タウンメール担当者=". $p_tm, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("ネット通販担当者=". $p_ns, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("再生担当者=". $p_repair, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("更新日時=". $p_upddt, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("電話対応管理=". $p_tel_mng_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("ネット注文管理=". $p_nsorder_mng_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("勤怠管理=". $p_attendance_mng_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("販売実績=". $p_inquiry_hanbai_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("ハイスペック登録=". $p_h_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("在庫フラグ=". $p_z_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("会場取フラグ=". $p_fac_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("出荷管理フラグ=". $p_shipping_flg, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("伝票発行フラグ=". $p_slip_flg, "test", SYS_LOG_TYPE_DBUG);
								
								//更新日時のフォーマット変換
								$f_upddt = strtotime($p_upddt);
								$today = date('Ymd');
								$today2 = strtotime($today);
								$con_day = date('Y/m/d');
	 							$day = ($today2 - $f_upddt) / (60 * 60 * 24);
								setcookie ('con_day', '', time()-3600);
								setcookie ('con_day', $con_day);
								$comm->ouputlog("日付=".$_COOKIE['con_day'], SYS_LOG_TYPE_DBUG);
								
								//ログ出力
								$comm->ouputlog("本日=". $today, "test", SYS_LOG_TYPE_DBUG);
								$comm->ouputlog("前回パスワード変更からの経過時間=".$day, "test", SYS_LOG_TYPE_DBUG);

								
								if ($day > 90) {
									echo '<script type="text/javascript">alert("前回パスワードを変更してから90日以上経っています。\nパスワードを変更してください。");
									location.href = "./password_change.php"</script>';
								}else if($updcount == 0){
									echo '<script type="text/javascript">alert("パスワードが初期設定のままです。\nパスワードを変更してください。");
									location.href = "./password_change.php"</script>';
								}else{
									//リングロー・融興の場合
									if ($p_companycd == "R" || $p_companycd == "Y" || $p_companycd == "U") {
										if ($p_inquiry_hanbai_flg == 0) {
											//Urlへ送信
											header("Location: ./menu_k.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
										} else if ($p_inquiry_hanbai_flg == 9) {
											//Urlへ送信
											header("Location: ./menu.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
										} else {
											//Urlへ送信
											header("Location: ./inquiry_hanbai.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
										}
									//電話注文担当の場合
									}else if ($p_companycd == "E") {
										//Urlへ送信
										header("Location: ./menu_e.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
									//周辺機器ブース担当者の場合
									}else if ($p_companycd == "B") {
										//Urlへ送信
										header("Location: ./menu_b.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
									//ヒューマネクスの場合
									}else if ($p_companycd == "N") {
										//Urlへ送信
										header("Location: ./kaijyo_humaknex_input.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
									}else if ($p_companycd == "P") {
										//Urlへ送信
										header("Location: ./get_productkey.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
									}else if ($p_companycd == "K") {
										//Urlへ送信
										header("Location: ./zei_support_menu.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
									}else if ($p_companycd == "L") {
										//Urlへ送信
										header("Location: ./honbu_pinfo_menu.php"); //法務部アルバイト
									}else if ($p_companycd == "A") {
										//Urlへ送信
										header("Location: ./rice_order.php"); //アドネット
									}else if ($p_companycd == "AD") {
										//Urlへ送信
										header("Location: ./syuukei_list.php"); //アドネット(会場)
									}else if ($p_companycd == "H" && $p_shipping_flg == 1) {
										//Urlへ送信
										header("Location: ./repair_zaiko_menu.php"); //補修センター（出荷管理）
									//それ以外
									} else {
										//Urlへ送信
										header("Location: ./menu.php"); //これを使うときは、これ以前にブラウザの書き込みは全てしないこと！
									}
									exit();
								}
							}
							else {
								$e_login = "<font color=\"red\"><br>パスワードに誤りがあります。</font>";
							}
						}
						else {
							$e_login = "<font color=\"red\"><br>ユーザIDに誤りがあります。</font>";
						}
					}
				}
			}
		} else {
			//初回起動時
// ----- 2019.06 ver7.0対応
//			$p_uid = $_COOKIE['j_office_Uid'];
//			$p_pwd = $_COOKIE['j_office_Pwd'];
			$p_uid = $_COOKIE['j_office_Uid'] ?? null;
			$p_pwd = $_COOKIE['j_office_Pwd'] ?? null;
		}
?>


<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<title>ログイン | J-Office</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="content-script-type" content="text/JavaScript">
	<meta http-equiv="content-style-type" content="text/css">
	<style type="text/css">
		body,p,form,input{margin: 0}
		#form{
		    width: 600px;
		    margin: 30px auto;
		    padding: 20px;
		    border: 1px solid #555;
		}
		 
		form p{
		    font-size: 25px;
		}
		 
		.form-title{
		    font-size: 50px;
		    text-align: center;
		    margin-bottom: 20px;
		    border-bottom:solid 3px #fff;"
		}
		 
		.username {
		    margin-bottom: 20px;
		}
		 
		.password {
		    margin-bottom: 20px;
		}
		 
		input[type="text"],
		input[type="password"] {
		    width: 350px;
		    padding: 4px;
		    font-size: 30px;
		    margin: 0 auto;
		}
		    
		.submit{
		    text-align: center;
		    margin-bottom: 15px;
		}

		.link {
		    font-size:10px;
		    color:white;
		    text-align:right;
		    margin-bottom: 5px;
		}
		
		 
		/* skin */
		 
		#form{
		  background:#053352;
		  background-image: -webkit-linear-gradient(top, #053352, Courier New);
		  background-image: -moz-linear-gradient(top, #053352, Courier New);
		  background-image: -ms-linear-gradient(top, #053352, Courier New);
		  background-image: -o-linear-gradient(top, #053352, Courier New);
		  background-image: linear-gradient(to bottom, #053352, Courier New);
		  -webkit-border-radius: 6;
		  -moz-border-radius: 6;
		  border-radius: 6px;
		  font-family: Courier New;
		  color: #ffffff;
		  font-size: 20px;
		  padding: 20px 20px 20px 20px;
		  text-decoration: none;
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
		  font-size: 16px;
		  padding: 10px 20px 10px 20px;
		  text-decoration: none;
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
		 
		#form{
		  background: #053352;
		  background-image: -webkit-linear-gradient(top, #053352, Courier New);
		  background-image: -moz-linear-gradient(top, #053352, Courier New);
		  background-image: -ms-linear-gradient(top, #053352, Courier New);
		  background-image: -o-linear-gradient(top, #053352, Courier New);
		  background-image: linear-gradient(to bottom, #053352, Courier New);
		  -webkit-border-radius: 6;
		  -moz-border-radius: 6;
		  border-radius: 6px;
		  font-family: Courier New;
		  color: #ffffff;
		  font-size: 20px;
		  padding: 20px 20px 20px 20px;
		  text-decoration: none;
		}
		.show {
		    font-size:15px;
		    margin-bottom: 20px;
		    margin-top: 0px;
		    text-align: center;
		}
	</style>
	<script type="text/javascript">
	//パスワードの表示
	function Show_pwd(id){
		if(document.forms['frm'].elements[id+'_check'].checked == true){
			document.forms['frm'].elements[id].type = "text";
		}else{
			document.forms['frm'].elements[id].type = "password";
		}
	}
	//入力内容を半角に変換
	function sethalf(getvalue){
		strVal = getvalue.value;
		var halfVal = strVal.replace(/[！-～]/g,
		function( tmpStr ) {
		// 文字コードをシフト
		return String.fromCharCode( tmpStr.charCodeAt(0) - 0xFEE0 );
		}
		);
		getvalue.value = halfVal;
	}
	</script>

</head>
<body text="#D3D3D3" onLoad="document.frm.uid.focus()">
<div id="form">
	<center>
		<br><br>
		<p class="form-title">J-Office Login</p>
		<form name="frm" action="./login.php?proc=login" method="post">
			<p>Username</p>
			<p class="username"><input type="text" name="uid" value="<?php if(!empty($p_uid)) {echo $p_uid;} ?>" size="15" style="ime-mode:disabled;" oninput="Javascript:sethalf(this);"></p>
			<p>Password</p>
			<p class="password"><input type="password" name="pwd" value="<?php if(!empty($p_pwd)) {echo $p_pwd;} ?>" size="15" style="ime-mode:disabled;"></p>
			<p class="show"><label><input type="checkbox" id="pwd_check" onChange="Javascript:Show_pwd('pwd')"/>パスワードを表示する</label></p>
			<p class="submit"><input type="submit" value="ログイン"></p>
			<p class="link">※パスワードの変更は<a href ="./password_change.php" style ="color:white;">こちら</a></p>
			<p class="link">※パスワードを忘れた方は<a href ="./password_renew.php" style ="color:white;">こちら</a></p>
			<?php echo "<br>".$e_login; ?>
	</center>
</div>
</body>

</html>


<?php
		//----------------------------------------------------------------------------------------------
		// データベース切断
		//----------------------------------------------------------------------------------------------
		$dba->mysql_discon($db)
?>
