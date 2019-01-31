<?php
if(!isset($_SESSION)) session_start();
require_once('conn.php');

//fix the logging out. When you are logged out in the database then you need to be forced out insteadof trying to send message that never go though
//index.php
//http://etherpad.com/pwFPZaPSji
//processAJAX.php
//http://etherpad.com/veA8jZHT0h
//new code to implement
//http://etherpad.com/p82c3ewAvf

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
	else {
	 return "false";
	}
}

$error = array();
if(isset($_POST['login'])){
  //validate the registration and add use to the db plus log them in
  if (empty($_POST['username'])) {
      $error['username'] = 'Enter your username';
    }
    
    if (empty($_POST['password'])) {
      $error['password'] = 'Enter your password';
    }

  if(!$error) { //no errors above
    $selectuser = "SELECT user_id, user_first, user_last FROM user where user_name = ? AND user_password = SHA1(CONCAT('RandomCharactersBeforePassword',?,salt,'AfterSaltRandomCharacters')) LIMIT 1";
    $statement = $db->prepare($selectuser);
    $password = sha1($_POST['password']);
    $statement->bind_param('ss', $_POST['username'], $password);
    $statement->execute();
    $statement->store_result(); //we need to do this before getting the number of rows
    $returnNum = $statement->num_rows;
    
    if($returnNum > 0){
        $statement->bind_result($user_id, $user_first, $user_last);
        $statement->fetch();
        $statement->close();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_first'] = $user_first;
        $_SESSION['user_last'] = $user_last;
        $_SESSION['username'] = $_POST['username'];
        //update user_lastlogin = now() and user_logged_in = true
        $updateuser = "UPDATE user SET user_lastlogin = NOW(), user_last_activity = NOW(), user_logged_in = 1 WHERE user_id = ?";
        $statement = $db->prepare($updateuser);
      $statement->bind_param('i', $_SESSION['user_id']);
        $statement->execute();
        //$_SESSION['message'] = "<h2>You are logged in ".$_SESSION['user_first'] . " " .    $_SESSION['user_last']."</h2>";
        header(sprintf("Location: %s", "index.php"));
        exit; //this stops the rest of the page from processing before the next page loads
      } else
        $error['badpass'] = 'Your password or user does not exist or is wrong';
  }
}
if(isset($_POST['logout'])){
  //change the database for the current user and make th
  $updateuser = "UPDATE user SET user_logged_in = 0 WHERE user_id = ?";
  $statement = $db->prepare($updateuser);
  $statement->bind_param('s', $_SESSION['user_id']);
  $statement->execute();
  $statement->close();
  $session_id = getSessionId();
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
  session_unset();
  $_SESSION['message'] = 'You have been logged out successfully';
  unset($_POST);
  //call clean database function
  //delete anything that is accosiated with you in 3 tables
  //header("Location: index.php");
  //exit;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF8" />
    <title>Welcome to AJAX Chat</title>
    <link rel="stylesheet" type="text/css" media="screen" href="style.css" />
    <link rel="icon" type="image/png" href="favicon.ico" />
    <?php 
    if(isset($_SESSION['user_id'])){
      $id = uniqid("");
      ?>
      <script type='text/javascript' src='ajax.js'></script>
      <script type='text/javascript' src='notify.js'></script>
      <script type='text/javascript'>
        window.onload=loadme;
        used_chat_ids = '';
        
        function loadme(){
          userListTimer = setInterval('getuserlist()',1000);
          pendingRequestTimer = setInterval('getPending()',1000);
          getRequestTimer = setInterval('getRequests()',1000);
          findAcceptTimer = setInterval('findAccept()',1000);
          //15 minutes
          idleTimer = setInterval('checkIdle()',1000*15*60);
          clientIdleCheckTimer = setInterval('clientIdleCheck()',1000);
        }
        
        //ACTIVE AJAX REQUESTS
        function clientIdleCheck(){
        	makeHttpRequest('processAJAX.php?clientIdleCheck=true',catchIdleCheck);
        }
        
        function checkIdle(){
        	makeHttpRequest('processAJAX.php?idleCheck=true',isIdle);
        } 
        
        function getuserlist(){
          makeHttpRequest('processAJAX.php?getList=true',showList);
        }
        
        function getPending(){
          makeHttpRequest('processAJAX.php?requestPending=true',showPending);
        }
        
        function getRequests(){
          makeHttpRequest('processAJAX.php?getRequests=true',showReceived);
        }
        
        function findAccept(){
          makeHttpRequest('processAJAX.php?findAccept=true',catchAcceptance);
        }
        function  catchAcceptance(text){
          //if it is true then show the chatbox and hide both the left and right side panels and stop the listeners and start new listener for the chat
          if(text != 'false') {
            deleteAllRequest();
            document.getElementById('chatContainer').style.display = 'block';
            document.getElementById('leftside').style.display = 'none';
            clearInterval(userListTimer);
            clearInterval(pendingRequestTimer);
            clearInterval(getRequestTimer);
            clearInterval(findAcceptTimer);
            chatBoxTimer = setInterval('getChatContent()',1000);
            document.getElementById('chatBox').focus();
          }
        }
        
        //SEMI ACTIVE AJAX
        function getChatContent(){//count++;console.log('count:'+count);
          makeHttpRequest('processAJAX.php?getChatContent=true',showChatContents);
        }
        
        //NON-ACTIVE AJAX (MANUAL ON REQUEST)
        function deleteAllRequest(){
          makeHttpRequest('processAJAX.php?deleteAllRequests=true');
        }
        
        function requestChat(toid){
          makeHttpRequest('processAJAX.php?requestChat=true&toid='+toid);
        }
        
        function deleteRequest(session_id){
          makeHttpRequest('processAJAX.php?deleteRequest=true&my_session_id='+session_id);
        }
        
        function acceptRequest(session_id){
          makeHttpRequest('processAJAX.php?acceptRequest=true&my_session_id='+session_id);
        }
        
        function stopChat(){
          clearInterval(chatBoxTimer);
          userListTimer = setInterval('getuserlist()',1000);
          pendingRequestTimer = setInterval('getPending()',1000);
          getRequestTimer = setInterval('getRequests()',1000);
          findAcceptTimer = setInterval('findAccept()',1000);
          document.getElementById('chatContainer').style.display = 'none';
          document.getElementById('leftside').style.display = 'block';
          makeHttpRequest('processAJAX.php?deleteChat=true');
          document.title='Ajax Chat';
          document.getElementById('chatWindow').innerHTML = '';
        }
        
        function submitChat(){
          submit_file = false;
          //for now when you click submit it will submit the form and if there is no file being uploaded then nothing will come of it
          if(fileUpload == true){
            if(document.getElementById('chatFile').value == ''){
              showChatContents('0,<b>No file was chosen. Pick again.</b><br />');
              return;
            }
            document.getElementById('sendButton').value = 'Send';
            setTimeout("getProgress()", 500);
            fileUpload = false;
            startUploadAnimator();
            submit_file = true;
          }
          var chatText = document.getElementById('chatBox').value.replace(/\n/g,"_NeW_LiNe_");
          if(chatText != "\n" && chatText != "") {
            makeHttpRequest('processAJAX.php?submitChat=true&chatText='+chatText);
          }
          document.getElementById('chatBox').value = '';
          document.getElementById('chatBox').focus();
          if(submit_file) 
            return true;
          else
            return false;
        }
        
        function getProgress(){
          makeHttpRequest('processAJAX.php?getProgress=true&progress_key=<?php echo($id)?>',showProgress);
        }

        //DISPLAY AJAX RESULTS (CALLBACK FUNCTIONS)
        function isIdle(text){
          if(text == 'true') alert('you should be logged out');
          document.getElementById('debug').innerHTML += text + "<br/>";
        }
        
        function showProgress(text){
          var outputArray = text.split(',');
        	document.getElementById("time_left_value").innerHTML = outputArray[2];
        	document.getElementById("speed_value").innerHTML = outputArray[1];
          document.getElementById("percent_finished").innerHTML = outputArray[0]+"%";
          document.getElementById("progressinner").style.width = outputArray[0]+"%"; 
          if (outputArray[0] < 100){
            setTimeout("getProgress()", 500);
          }
        }
        
        function showList(text){
          document.getElementById('userlist').innerHTML = text;
        }
        
        function catchIdleCheck(text){
          if(text == 'true') window.location = 'index.php';
        }
        
        function showPending(text){
          document.getElementById('requestsPending').innerHTML = text;
        }
        
        function showReceived(text){
          document.getElementById('requestsReceived').innerHTML = text;
        }
        
        function showChatContents(text){
          from_me = 0;
          //see if its from me
          if(text.indexOf('_from_me_') !== -1){
            from_me = 1;
            text.replace("_from_me_","")
          }
          if(text.indexOf(',') !== -1){
            //only allow each chat message to be shown once. Chrome is calling showChatContents 2 times in a row sometimes.
            chat_id = text.substring(0,text.indexOf(','));
            if(used_chat_ids.indexOf(chat_id+',') !== -1) return;
            text = text.substring(text.indexOf(',')+1);
            used_chat_ids += chat_id+',';
          } else
            return;
          if(document.getElementById('debug').innerHTML.length > 50000){
            document.getElementById('debug').innerHTML = '';
          }
          if(text.length > 0) {
            if( text.search("_D_E_B_U_G_") != -1){
              document.getElementById('debug').style.display = 'block';
              debug(text.replace("_D_E_B_U_G_",""));
            } else {
              document.title = text.replace(/(<([^>]+)>)/ig,"").replace(/_NeW_LiNe_/g,"").substring(0,30)+'...';
              document.getElementById('chatWindow').innerHTML += text.replace(/_NeW_LiNe_/g,"<br/>");
              var obj = document.getElementById('chatWindow');
              obj.scrollTop = obj.scrollHeight;
              //only play the sound if it is not from me
              if(from_me == 0) {
                console.log('got a message. play a sound');
                if(play_sound_check(from_me)) notify2.play();
              } else {
                console.log('sent a message. play a sound');
                if(play_sound_check(from_me)) notify.play();
              }
            }
          }
        }

        function play_sound_check(check){//alert('check:'+check);
          result = false;
          //you sent a message. check if you want to play the sound
          if(check == 1)
            if(document.getElementById('sending_notification_check').checked){//alert('sending check ios:'+ios+' notify_sound_done:'+notify_sound_done);
              if(ios && notify_sound_done == 0) notifyInit();
              result = true;
            }
          //you got a message. check if you want to play the sound
          if(check == 0)
            if(document.getElementById('receiving_notification_check').checked){//alert('receiving check ios:'+ios+' notify2_sound_done:'+notify2_sound_done);
              if(ios && notify2_sound_done == 0) notify2Init();
              result = true;
            }
          return result;
        }
        
        function debug(text){
          document.getElementById('debug').innerHTML += text + "<br/>";
        }
        
        //CAPTURES KEY STROKES IN CHAT
        function imposeMaxLength(Object, MaxLen, e){
          if (!e) var e = window.event;
          if (e.keyCode) mykey = e.keyCode;
	        else if (e.which) mykey = e.which;
        	shift = e.shiftKey;
        	if(mykey == 8 || mykey == 0){
				return true;
        	}
        	if(shift == false && mykey == 13) {
        		submitChat();
                return false;
        	}
          return (Object.value.length <= MaxLen);
        }
        
        //DO THE FILE PROCESSING
        var fileUpload = false;
        function fileUploadChanged(){
          document.getElementById('sendButton').value = 'Upload';
          fileUpload = true;
        }
        
        function startUploadAnimator(){
          document.getElementById('animate_upload_process').style.display = 'block';
          return true;
        }
        
        function stopUpload(result){
          document.getElementById('animate_upload_process').style.display = 'none';
          document.sendChatForm.reset();
          if (result == 0){
            showChatContents('0,<b>Upload Failed</b><br />');
          }
          if (result == 2){
            showChatContents('0,<b>Upload file was too big</b><br />');
          }
          document.getElementById('animate_upload_process').style.block = 'none';
          return true;
        }
        
      </script>
<?php
    }
    ?>
    </head>
  <body>
    <h1>Welcome to AJAX Chat</h1>
    <?php
    if(isset($_SESSION['user_id'])){
      echo "Have fun chatting " . $_SESSION['user_first'] . " " .    $_SESSION['user_last'] . "<br /><br />";
    }
    if(isset($_SESSION['message'])) {
      echo '<p><span class="warning bold">' . $_SESSION['message'] . '</span></p>'; 
      $_SESSION['message'] = "";
    }
    if($error) {
          echo '<ul class="warning">';
          foreach ($error as $alert) {
            echo "<li>$alert</li>"; 
        }
        echo '</ul>';
    }
    ?>
    <div>
      <form method="post" action="">
      <?php
      //take care of the login and logoff
    if(isset($_SESSION['user_id'])){
      //logout
      echo "<input type='submit' name='logout' value='Logout' class='button-primary' /><br />
      <div class='soundNotify'><label for='sending_notification_check'>Sending notification: </label><input type='checkbox' id='sending_notification_check' checked='checked' /></div>
      <div class='soundNotify'><label for='receiving_notification_check'>Receiving notification: </label><input type='checkbox' id='receiving_notification_check' checked='checked' /></div>";
    } else {
      //ask to register
      echo '<div><label for="username">Username </label><input type="text" name="username" id="username" /></div>';
      echo '<div><label for="password">Password </label><input type="password" name="password" id="password" /></div>';
      echo '<div><input type="submit" name="login" value="Login" class="button-primary" /><br /><a href="register.php">Register here</a></div>';
      echo '<script>document.getElementById("username").focus()</script>';
    }
    echo "</form></div>";
    
    if(isset($_SESSION['user_id'])){
      //the div that will store the list of users
    ?>
    	
    	<div id="leftside">
    	  <div id="userheading">User List</div>
      	<div id="leftsideinside">
          <div id='userlist'>loading list of users ...</div>
        </div>
        <div id='requestsContainer'>
          <div id="receivedheading">Requests Received</div>
          <div id="leftsideinside">
            <div id='requestsReceived'></div>
          </div>
        </div>
        <div id="pendingheading">Request Pending</div>
        <div id="leftsideinside">
          <div id='requestsPending'></div>
        </div>
      </div>
      
      <div id='chatContainer'>
        <div id='chatWindow'></div>
        <br />
        <div id='chatBoxContainer'>
          <textarea onkeypress='return imposeMaxLength(this, 500,event);' cols='34' rows='5' id='chatBox' name='chatBox'></textarea>
          
          <form enctype="multipart/form-data" action="processAJAX.php" method="post" target="upload_target" name="sendChatForm" onsubmit="return submitChat()">
            <input type="hidden" name="APC_UPLOAD_PROGRESS" id="progress_key" value="<?php echo $id?>" />
            <input type="file" id="chatFile" name="chatFile" onchange="fileUploadChanged()" class="button-primary" /><br/>
            <input type="submit" value="Send" id="sendButton" class="button-primary" />
            <input type="button" onclick="stopChat();" value="Stop" name="stopchat" class="button-primary" />
          </form>
          <div id="animate_upload_process"> 
            <div id="uploadstats"><span id="upload_time_left"><span id="time_left_value"></span></span>
            <span id="upload_speed"><span id="speed_value"></span></span>
            
            <div id="progressouter">
              <div id="progressinner"><span id="percent_finished">&nbsp;</span> </div>
            </div><img src="ajax-loader.gif" id="fileAnimator"/></div>
          </div>
        </div>
      </div>
        <iframe id="upload_target" name="upload_target" style="width:500;height:100;border:1px solid #000; clear:both;display:none;"></iframe>
        <div id='debug' style="display:none">debug here</div>
    <?php
    }
    ?>
  </body>
</html>