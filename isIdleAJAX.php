<?php
if(!isset($_SESSION)) session_start();
require_once("conn.php");

//echo "Current: ".time()." Last: ".$_SESSION['lastActivity'];
//echo "Subract: ".((time() - $_SESSION['lastActivity']) / 60 )."<br/>";

if(isset($_GET['idleCheck'])){
  checkActivityAllUsers();
}

if(isset($_GET['clientIdleCheck'])){
  $isLogout = checkDBActivity();
  if($isLogout == "true"){
    session_unset();
    $_SESSION['message'] = 'You have been logged out for being idle for 15 minutes<br />or you logged out of your user in another browser';
  }
  echo  $isLogout;
}

function setLastActivity(){
	global $db;
	$update = "UPDATE user SET user_last_activity = now() WHERE user_id = ?";
	$statement = $db->prepare($update);
	$statement->bind_param('i', $_SESSION['user_id']);
	$statement->execute();
}
//logs out the current user or the user_id passed
function logoutUser($usertologout){
	global $db;
	$updateLog = "UPDATE user SET user_logged_in = 0 WHERE user_id = ?";
	$statement = $db->prepare($updateLog);
	$statement->bind_param('i', $usertologout);
	$statement->execute();
	$statement->close();
	clearAllData($usertologout);
}

function clearAllData($logoutid){
  global $db;
  $select= "SELECT session_id FROM session WHERE ( session_request_to = ? OR session_request_from = ?)";
  $statement = $db->prepare($select);
  $statement->bind_param('ii', $logoutid, $logoutid);
  $statement->execute();
  $statement->bind_result($session_id);
  $sessions = array();
  while($statement->fetch()){
    $sessions[] = $session_id;
  }
   $statement->close();
  foreach($sessions as $session_id){
      deleteChats($session_id);
  }
}

function deleteChats($session_id){
	global $db;
		$selectChat = "DELETE FROM chat WHERE session_id = ?";
		$statement = $db->prepare($selectChat);
		$statement->bind_param('i', $session_id);
		$statement->execute();
		$statement->close();
		$selectSession = "DELETE FROM session WHERE session_id = ?";
		$statement = $db->prepare($selectSession);
		$statement->bind_param('i', $session_id);
		$statement->execute();
		$statement->close();
}


function checkDBActivity(){
  //checks with users that are still chatting when the DB says they should be logged out and logs them out
  global $db;
	$select = "SELECT user_id, user_logged_in FROM user WHERE user_id = ?";
	$statement = $db->prepare($select);
	$statement->bind_param('i', $_SESSION['user_id']);
	$statement->execute();
	$statement->bind_result($user_id, $user_logged_in);
	$statement->fetch();
	$statement->close();
	if($user_logged_in == 0){
    logoutUser($user_id);
    return "true";
  } else {
    return "false";
  }
}

function checkActivityAllUsers(){
  global $db;
	$select= "SELECT user_id, user_last_activity FROM user WHERE user_logged_in = 1";
	$statement = $db->prepare($select);
	$statement->execute();
	$statement->bind_result($user_id, $user_last_activity);
  $users = array();
  while($statement->fetch()){
    //checks to see if they have been active for at least this many seconds
    if(time() > wp_mktime($user_last_activity) + (1*60)){
      $users[] = $user_id;
    }
  }
  $statement->close();
    foreach($users as $user){
      logoutUser($user);
  }
}

function wp_mktime($_timestamp = ''){
  //turn the timestamp into normal unix time()
  if($_timestamp){
    $_split_datehour = explode(' ',$_timestamp);
    $_split_data = explode("-", $_split_datehour[0]);
    $_split_hour = explode(":", $_split_datehour[1]);
    return mktime ($_split_hour[0], $_split_hour[1], $_split_hour[2], $_split_data[1], $_split_data[2], $_split_data[0]);
  }
}