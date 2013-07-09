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
	function remove(){
		$con=new mysqli("localhost","alex","","alex");
		if ($con->connect_error) die('Connect Error ('.$con->connect_errno.') '.$con->connect_error);
		$query="remove from tree_hollow where id=".$_GET['rem'];
		if($con->query($query)===true) echo "<p>Message sucessfully removed!</p>\n";
		
	}
	function retrieve_form(){
		echo "<h3>Retrieve a message</h3>\n<form action=\"index.php\" method=\"post\">\n<input type=\"hidden\" name=\"msgid\" value=\"".$_GET['msg']."\" />\n<p>Password for message #".$_GET['msg'].": <input type=\"password\" name=\"pwd\" maxlength=\"100\" required /></p>\n<p><input type=\"submit\" value=\"Get\" /></p></form>";
	}

	function leave_form(){
		echo "<h3>Leave your message</h3>\n<form action=\"index.php\" method=\"post\" enctype=\"multipart/form-data\">\n<p><textarea autofocus cols=\"50\" name=\"msg\" required rows=\"10\">\n</textarea></p>\n<p>Password: <input type=\"password\" name=\"pwd\" maxlength=\"100\" required /></p>\n<p>Notify? <input type=\"email\" name=\"notify\" multiple /></p>\n<p><input type=\"submit\" value=\"Save\" /></p>\n</form>";
	}

	function retrieve(){
		$con=new mysqli("localhost","alex","","alex");
		if ($con->connect_error) die('Connect Error ('.$con->connect_errno.') '.$con->connect_error);
		$query="select content,password from tree_hollow where id=".$_POST['msgid'];
		$key=hash("RIPEMD128",$_POST['pwd'],false);
		$key2=hash("SHA256",$_POST['pwd'],false);
		if($result=$con->query($query)){
			if ($result->num_rows>0){
				$row=$result->fetch_row();
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

					//ban user for 3 hours
					//is user in db?
					$q1="select attempts from th_blacklist where ip=\"".$_SERVER['REMOTE_ADDR']."\"";
					if($r1=$con->query($q1)){
						if ($r1->num_rows>0){
							//user is in db - increment attempts count
							$attempts=$r1->fetch_row()[0]+1;
							$left=3-$attempts;
							$q3="update th_blacklist set attempts=".$attempts.", last=NOW() where ip=\"".$_SERVER['REMOTE_ADDR']."\"";
							if($con->query($q3)===true) echo "<p>".$left." password attempts left!</p>\n";
						} else{
							//user is not in db - add record
							$q2="insert into th_blacklist(ip,attempts,last) values (\"".$_SERVER['REMOTE_ADDR']."\", 1, NOW())";
							$con->query($q2);
							echo "<p>2 password attempts left!</p>\n";	
						}
					}
				}
			} else echo "<p>Message ".$_POST['msgid']." doesn't exist.</p>";
		} else echo "Error in MySQL occured. Please report!";
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

		$con=new mysqli("localhost","alex","","alex");
		if ($con->connect_error) die('Connect Error ('.$con->connect_errno.') '.$con->connect_error);
		$query = "INSERT INTO tree_hollow (content,date,password) VALUES (\"".$msg_base64."\",\"".$valid_until."\",\"".$key2."\")";

		if ($result=$con->query($query,MYSQLI_USE_RESULT)){
			$query = "SELECT MAX(id) from tree_hollow";
			$result2=$con->query($query);
			$row=$result2->fetch_row();
			$id=$row[0];
			$result2->close();

			echo "<p>Message left in a tree hollow. To retrieve it visit <a href=\"?msg=".$id."\">tree-hollow.org/msg/".$id."</a>. Message is valid until ".$valid_until." when it will be removed automatically!</p>\n";

			if (isset($_POST['notify'])&&$_POST['notify']!=''){
				$from="alexander.mansurov@gmail.com";
				$headers="From: ".$from."\r\nX-Mailer: PHP/".phpversion();
				$subject="You have been left a message in a tree hollow";
				$message="Dear user,\r\nthere is someone, who decided to leave you a message in a tree hollow.\n\rTo retrieve it visit http://tree-hollow.org/msg/".$id."\r\n";
				if (mail($_POST['notify'],$subject,$message)) echo $_POST['notify']." was notified successfully, remember to provide them with the password you have set!";
				else echo "Failed to notify ".$_POST['notify'];
			}
		} else echo "Failed to leave a message!";
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

	//get user's IP address and check against th_blacklist
	$con=new mysqli("localhost","alex","","alex");
	if ($con->connect_error) die('Connect Error ('.$con->connect_errno.') '.$con->connect_error);
	$query="select attempts, last from th_blacklist where ip=\"".$_SERVER['REMOTE_ADDR']."\"";
	if($result=$con->query($query)){
		if(($result->num_rows)>0){
			$row=$result->fetch_row();
			$attempts=$row[0];
			$last=$row[1];
			date_default_timezone_set("Europe/Prague");
			$dteStart=new DateTime($last);
			$interval=$dteStart->diff(new DateTime("now"));
			if ($attempts<3) gen_page();
			else if($interval->format("%H")>3){
				//remove record from DB
				$query="delete from th_blacklist where ip=\"".$_SERVER['REMOTE_ADDR']."\"";
				if(!($result2=$con->query($query))) echo "<p>Error while removing ban record - this will not affect access privileges</p>\n";
				gen_page();
			}
			else echo "<p>Access temporarily denied due to many failed password attempts!</p>\n";
		} else gen_page();
	} else echo "<p>Error checking for bans</p>\n";
?>
	<p><a href="about.html">About Tree hollow</a> <a href="privacy.html">Privacy policy and terms of use</a>
