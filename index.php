<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>The tree hollow - anonymous messaging</title>
		<link rel="stylesheet" type="text/css" media="screen" href="style.css" />
	</head>
	<body>
			<h1>Tree hollow</h1>
			<h2>Anonymous messaging</h2>
<?php
	require 'database.php';
	define('SITE','localhost');
	define('MASTER','');

	function remove(){
		if(db_remove($_GET['rem'])) echo "<p>Message sucessfully removed!</p>\n";
		else echo "<p>Error while removing message!</p>\n";
	}

	function retrieve_form(){
		echo "<h3>Retrieve a message</h3>\n".
		"<form action=\"index.php\" method=\"post\">\n".
		"<input type=\"hidden\" name=\"msgid\" value=\"".$_GET['msg']."\" />\n".
		"<p>Password for message #".$_GET['msg'].": <input type=\"password\" name=\"pwd\" maxlength=\"100\" required /></p>\n".
		"<p><input type=\"submit\" value=\"Get\" /></p></form>\n";
	}

	function leave_form(){
		echo "<h3>Leave your message</h3>\n".
		"<form action=\"index.php\" method=\"post\" enctype=\"multipart/form-data\">\n".
		"<p><textarea autofocus cols=\"50\" name=\"msg\" required rows=\"10\">\n".
		"</textarea></p>\n".
		"<p>Password: <input type=\"password\" name=\"pwd\" maxlength=\"100\" required /></p>\n".
		"<p>Notify? <input type=\"email\" name=\"notify\" multiple /></p>\n".
		"<p><input type=\"submit\" value=\"Save\" /></p>\n".
		"</form>\n";
	}

	function retrieve(){
		$row=db_retrieve($_POST['msgid']) or die("Message doesn't exist");
		$key=hash("RIPEMD128",$_POST['pwd'],false);
		$key2=hash("SHA256",$_POST['pwd'],false);
		
		if ($key2==$row[1]){
			$enc_msg=base64_decode($row[0]);
			$iv_size=mcrypt_get_iv_size(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC);
			$iv_dec=substr($enc_msg,0,$iv_size);
			$enc_msg=substr($enc_msg,$iv_size);		
			$dec_msg=mcrypt_decrypt(MCRYPT_BLOWFISH,$key,$enc_msg,MCRYPT_MODE_CBC,$iv_dec);
			
			echo "<h3>Message #".$_POST['msgid']."</h3>\n<p>".$dec_msg."</p>\n";
			echo "<p><a href=\"?rem=".$_POST['msgid']."\">Remove message</a></p>";
		} else {
			echo "<p>Passwords didn't match!</p>\n";
			db_ban($_SERVER['REMOTE_ADDR']);
		}
	}

	function submit(){
		$key=hash("RIPEMD128",$_POST['pwd'],false);
		$key2=hash("SHA256",$_POST['pwd'],false);
		$msg=utf8_encode($_POST['msg']);
		$iv_size=mcrypt_get_iv_size(MCRYPT_BLOWFISH,MCRYPT_MODE_CBC);
		$iv=mcrypt_create_iv($iv_size,MCRYPT_RAND);
		$msg_enc=mcrypt_encrypt(MCRYPT_BLOWFISH,$key,$msg,MCRYPT_MODE_CBC,$iv);
		$msg_enc=$iv.$msg_enc;
		$msg_base64=base64_encode($msg_enc);

		date_default_timezone_set('Europe/Prague');
		$valid_until=date('Y-m-d',strtotime('+1 year'));

		$id=db_insert($msg_base64,$valid_until,$key2);
		echo "<p>Message left in a tree hollow. To retrieve it visit <a href=\"http://".SITE."?msg=".$id."\">".SITE."/msg/".$id."</a>. Message is valid until ".$valid_until." when it will be removed automatically!</p>\n";
		
		if (isset($_POST['notify'])&&$_POST['notify']!=''){
				$headers="From: ".MASTER."\r\nX-Mailer: PHP/".phpversion();
				$subject="You have been left a message in a tree hollow";
				$message="Dear user,\r\nthere is someone, who decided to leave you a message in a tree hollow.\n\rTo retrieve it visit http://tree-hollow.org/msg/".$id."\r\n";
				if (mail($_POST['notify'],$subject,$message,$headers)) echo $_POST['notify']." was notified successfully, remember to provide them with the password you have set!";
				else echo "Failed to notify ".$_POST['notify'];
		}
	}

	function gen_page(){
		//generate page
		if(isset($_GET['msg'])) retrieve_form();
		else{
			if(isset($_POST['msgid'])) retrieve();
			else if(isset($_POST['msg'])) submit();
			else if (isset($_GET['rem'])) remove();
			leave_form();
		}
	}

	if(db_check($_SERVER['REMOTE_ADDR'])) gen_page();
?>
	<p><a href="about.html">About Tree hollow</a> <a href="privacy.html">Privacy policy and terms of use</a>
