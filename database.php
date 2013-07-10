<?php
	define('HOST','localhost');
	define('USER','alex');
	define('PWD','***');
	define('DB','alex');

	function db_remove($id){
		$con=connect();
		$query="remove from tree_hollow where id=".$id;
		return $con->query($query);
	}

	function db_retrieve($msgid){
		$con=connect();
		$query="select content,password from tree_hollow where id=".$msgid;
		if(($result=$con->query($query)) && ($result->num_rows>0)) return $result->fetch_row();
		else die("Message ".$msgid." doesn't exist");
	}

	function db_ban($ip){
		//ban user for 3 hours
		//is user in db?
		$con=connect();
		$q1="select attempts from th_blacklist where ip=\"".$ip."\"";
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

	function db_check($ip){
		//get user's IP address and check against th_blacklist
		$con=connect();
		$query="select attempts, last from th_blacklist where ip=\"".$ip."\"";
		if($result=$con->query($query)){
			if(($result->num_rows)>0){
				$row=$result->fetch_row();
				$attempts=$row[0];
				$last=$row[1];
				date_default_timezone_set("Europe/Prague");
				$dteStart=new DateTime($last);
				$interval=$dteStart->diff(new DateTime("now"));
				if ($attempts<3) return true;
				else if($interval->format("%H")>3){
					//remove record from DB
					$query="delete from th_blacklist where ip=\"".$ip."\"";
					if(!($result2=$con->query($query))) echo "<p>Error while removing ban record - this will not affect access privileges</p>\n";
					return true;
				} 
				else echo "<p>Access temporarily denied due to many failed password attempts!</p>\n";
		} else return true;
	} else echo "<p>Error checking for bans</p>\n";//logit
	}

	function db_insert($msg,$valid,$key){
		$con=connect();
		$query = "INSERT INTO tree_hollow (content,date,password) VALUES (\"".$msg."\",\"".$valid."\",\"".$key."\")";

		if ($result=$con->query($query,MYSQLI_USE_RESULT)){
			$query = "SELECT MAX(id) from tree_hollow";
			$result2=$con->query($query);
			$row=$result2->fetch_row();
			$id=$row[0];
			$result2->close();
			return $id;
		} else die("Failed to leave a message");//log it!

			
	}

	function connect(){
		$con=new mysqli(HOST,USER,PWD,DB);
		if ($con->connect_error) die('Connect Error ('.$con->connect_errno.') '.$con->connect_error);//zalogovat
		else return $con;
	}
?>