<?php
session_start();
/*
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
*/
require_once("conn.php");
require_once("isIdleAJAX.php");

if(isset($_GET['getList'])){
	//checkActivity();
  $selectlist = "SELECT user_id, user_name FROM user WHERE user_logged_in = 1 AND user_id != ?";
  $statement = $db->prepare($selectlist);
  $statement->bind_param('i', $_SESSION['user_id']);
	$statement->execute();
	$statement->store_result(); //we need to do this before getting the number of rows
  $returnNum = $statement->num_rows;
  
  if($returnNum > 0){
  	$statement->bind_result($user_id, $user_name);
  	while($statement->fetch()){
  	  //requestChat has to parameters (toid, fromid)
  	  echo '<a class="username" onclick="requestChat('.$user_id.')">'.$user_name.'</a><br />';
    }
  }
}

if(isset($_GET['requestChat'])){
  $toid = $_GET['toid'];
  $selectlist = "SELECT session_id FROM session WHERE session_request_to = ? AND session_request_from = ?";
  $statement = $db->prepare($selectlist);
  $statement->bind_param('ii', $toid,$_SESSION['user_id']);
  $statement->execute();
  $statement->store_result(); //we need to do this before getting the number of rows
  $returnNum = $statement->num_rows;
  if($returnNum == 0){
    $insert = "INSERT INTO session (session_request_to, session_request_from) VALUES (? ,?)";
    $statement = $db->prepare($insert);
    $statement->bind_param('ii', $toid, $_SESSION['user_id']);
    $statement->execute();
  }
  $_SESSION['lastChatID'] = 0;
  setLastActivity();
}

if(isset($_GET['requestPending'])){
  $selectlist = "SELECT session_id, user_id, user_name FROM user u, session c WHERE c.session_request_to = u.user_id AND c.session_request_from = ?";
  $statement = $db->prepare($selectlist);
  $statement->bind_param('i', $_SESSION['user_id']);
	$statement->execute();
	$statement->store_result(); //we need to do this before getting the number of rows
  $returnNum = $statement->num_rows;
  
  if($returnNum > 0){
  	$statement->bind_result($session_id, $user_id, $user_name);
  	
  	while($statement->fetch()){
  	  echo "<br /><input type='button' onclick='deleteRequest($session_id);' value='Cancel' />$user_name <br />";
    }
  }
}

if(isset($_GET['deleteRequest'])){
  $session_id = $_GET['my_session_id'];
  $insert = "DELETE FROM session WHERE session_id = ?";
  $statement = $db->prepare($insert);
  $statement->bind_param('i', $session_id);
  $statement->execute();
  setLastActivity();
}


if(isset($_GET['getRequests'])){
  $selectlist = "SELECT session_id, user_id, user_name FROM user u, session c WHERE c.session_request_from = u.user_id AND c.session_request_to = ?";
  $statement = $db->prepare($selectlist);
  $statement->bind_param('i', $_SESSION['user_id']);
	$statement->execute();
	$statement->store_result(); //we need to do this before getting the number of rows
  $returnNum = $statement->num_rows;
  if($returnNum > 0){
  	$statement->bind_result($session_id, $user_id, $user_name);  	
  	while($statement->fetch()){
  	  echo '<br />'.$user_name.'<br /><input type="button" onclick="acceptRequest('.$session_id.')" value="Accept" />&nbsp;<input type="button" onclick="deleteRequest('.$session_id.')" value="Deny" /><br /><br />';
    }
  }
}

if(isset($_GET['acceptRequest'])){
  $_SESSION['lastChatID'] = 0;
  $session_id = $_GET['my_session_id'];
  $insert = "UPDATE session SET session_accepted = 1 WHERE session_id = ?";
  $statement = $db->prepare($insert);
  $statement->bind_param('i', $session_id);
  $statement->execute();

  //delete pendings from the person that clicked on accept
  $insert = "DELETE FROM session WHERE session_request_from = ?";
  $statement = $db->prepare($insert);
  $statement->bind_param('i', $_SESSION['user_id']);
  $statement->execute();
  setLastActivity();
}

if(isset($_GET['findAccept'])){
	echo getSessionId();
}

if(isset($_GET['deleteChat'])){
  setLastActivity();
	$session_id = getSessionId();
	if($session_id){
		$delete = "DELETE FROM session WHERE session_id = ?";
		$statement = $db->prepare($delete);
		$statement->bind_param('i', $session_id);
		$statement->execute();
		$delete = "DELETE FROM chat WHERE session_id = ?";
		$statement = $db->prepare($delete);
		$statement->bind_param('i', $session_id);
		$statement->execute();
	}
	$_SESSION['lastChatID'] = 0;
}

if(isset($_GET['getChatContent'])){
	$session_id = getSessionId();
	if($session_id){
	  if(empty($_SESSION['lastChatID'])) { $_SESSION['lastChatID'] = 0;}
		$selectlist = "SELECT chat_id, chat_text, from_user_id FROM chat WHERE session_id = ? AND chat_id > ?";
		$statement = $db->prepare($selectlist);
		$statement->bind_param('ii', $session_id, $_SESSION['lastChatID']);
		$statement->execute();
		$statement->store_result(); //we need to do this before getting the number of rows
		$returnNum = $statement->num_rows;
		if($returnNum > 0){
			$statement->bind_result($chat_id, $chat_text, $from_user_id);
			while($statement->fetch()){
        if($from_user_id == $_SESSION['user_id']) echo "_from_me_";
			  $_SESSION['lastChatID'] = $chat_id;
			  echo $chat_id.','.str_replace('_$$__$$$__$$_', $chat_id , $chat_text)."<br />\n";
		  }
		}
	}
}

if(isset($_GET['submitChat'])){
    $chatText = "<b>".$_SESSION['username'];
    $chatText .= ":</b> ";
    $chatText .= htmlspecialchars($_GET['chatText']);
    $session_id = getSessionId();
    $insert = "INSERT INTO chat (from_user_id, session_id, chat_text) VALUES (?, ? ,?)";
    $statement = $db->prepare($insert);
    $statement->bind_param('iis', $_SESSION['user_id'], $session_id, $chatText);
    $statement->execute();
    setLastActivity();
}

if(isset($_GET['deleteAllRequests'])){
    $acitveID = getSessionId();
    //clearAllData();
    $delete = "DELETE FROM session WHERE session_accepted = 0 AND session_request_from = ?";
		$statement = $db->prepare($delete);
		$statement->bind_param('i',$_SESSION['user_id']);
		$statement->execute();
		$delete = "DELETE FROM chat WHERE session_id = ?";
		$statement = $db->prepare($delete);
		$statement->bind_param('i', $session_id);
		$statement->execute();
}

if(isset($_GET['getProgress'])) {
  //runs when a file is being uploaded and needs APC installed on the server as a php extension to work
  if(function_exists('apc_fetch')) {
    //the prefix is usually upload_
    $status = apc_fetch(ini_get("apc.rfc1867_prefix").$_GET['progress_key']);
    
    //example status Array ( [total] => 6920131 [current] => 1802204 [filename] => Mormon Ad - Homefront 91 - A Little Attention - HD.flv [name] => test_file [done] => 0 [start_time] => 1260831951.75 ) 
    if(!isset($status['filename'])){ echo "1,Upload starting,";exit();}
    $percentDone = round($status['current'] / $status['total']*100,0);
    $rate = round(($status['current'] / ( time() - $status['start_time'])) / 1024,2);
    $mins =  round((($status['total'] - $status['current']) / ($rate * 1024)) / 60,0);
    $seconds = round((($status['total'] - $status['current']) / ($rate * 1024)) % 60,0);
    if(strlen($seconds) < 2) $seconds = "0".$seconds;
    $timeleft = $mins.":".$seconds;
    echo $percentDone.",".$rate." KB/s,".$timeleft;
  } else echo "100,Not able to follow upload progress,";
}

if(isset($_POST['APC_UPLOAD_PROGRESS']) && $_FILES['chatFile']['name'] > ''){
  //example $_FILES Array ( [chatFile] => Array ( [name] => 100_3697.JPG [type] => image/jpeg [tmp_name] => /tmp/phpZzsGto [error] => 0 [size] => 254383 ) ) 
  setLastActivity();
  //if the filesize is geater than upload_max_filesize then show an error
  //file was probably too big if the tmp_name is blank
  if($_FILES['chatFile']['tmp_name'] == ''){stopUploadResult(2);exit();}
  //print_r($_FILES);
  $session_id = getSessionId();
  $blob_name = basename($_FILES['chatFile']['name']);
  $chat_text = "<b>" . $_SESSION['username'] . ':</b> <a href="getFile.php?id=_$$__$$$__$$_" target="_blank" />'.htmlspecialchars($blob_name).'</a>';
  $insert = "INSERT INTO chat ( chat_blob_file, session_id, chat_text, chat_blob_name) VALUES (?, ?, ?, ?)";
  $statement = $db->prepare($insert);
  $blob_file = NULL;
  $statement->bind_param('biss', $blob_file, $session_id, $chat_text,  $blob_name);
  
  $fp = fopen($_FILES['chatFile']['tmp_name'], "r");
  if($fp){
    while (!feof($fp)) {
       $statement->send_long_data(0, fread($fp, 8000));
    }
  }
  if($statement->execute()){
    stopUploadResult(1);
  } else {echo "error:".$db->error;
    stopUploadResult(0);
  }
  sleep(1); //make the process take at least 1 second even for small files
}

function stopUploadResult($upload_result){
  // success = 1, SQL error = 0, file was too big = 2
  ?>
  <script language="javascript" type="text/javascript">
    window.top.window.stopUpload(<?php echo $upload_result; ?>);
  </script>
  <?php
}

function getSessionId(){
	$session_id = false;
	global $db;
	$selectlist = "SELECT session_id FROM session WHERE (session_request_from = ? OR session_request_to = ?) AND session_accepted = 1 LIMIT 1";
	$statement = $db->prepare($selectlist);
	$statement->bind_param('ii', $_SESSION['user_id'], $_SESSION['user_id']);
	$statement->execute();
	$statement->store_result(); //we need to do this before getting the number of rows
	$returnNum = $statement->num_rows;
	if($returnNum == 1){
		$statement->bind_result($session_id);
		$statement->fetch();
		return $session_id;
	}
	else{
	 return "false";
	}
}