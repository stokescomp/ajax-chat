<?php
if(!isset($_SESSION)) session_start();
require_once('conn.php');
require_once('functions.php');
$error = array();
if(isset($_POST['register'])){
  //validate the registration and add user to the db plus log them in
  if (empty($_POST['first'])) {
	  $error['name'] = 'Enter your first name';
	}
  if (empty($_POST['last'])) {
	  $error['name'] = 'Enter your last name';
	}
	
	if (empty($_POST['first']) && empty($_POST['last'])) {
	  $error['name'] = 'Enter both your first and last name';
	}
	
	if (empty($_POST['username'])) {
	  $error['username'] = 'Enter your username';
	}
	if(!isset($error['username'])) {
	  //check to see if there is already an email in the database by that name
	  $finduser = "SELECT user_name FROM user WHERE user_name = ?";
	  //echo $finduser . $_POST['username'];
	  $statement = $db->prepare($finduser);
    $statement->bind_param('s', $_POST['username']);
	  $statement->execute();
	  $statement->store_result(); //we need to do this before getting the number of rows
    $returnNum = $statement->num_rows;
    $statement->close();
    if($returnNum > 0) $error['email_already_registered'] = 'Another user already is using your username please pick another';
	}
	
	if (empty($_POST['password'])) {
	  $error['password'] = 'Enter your password';
	}
	
	if(!$error) { //no errors above
  	$first = ucwords($_POST['first']);
  	$last = ucwords($_POST['last']);
    $salt = random(64);
  	$password = sha1('RandomCharactersBeforePassword'.sha1($_POST['password']).$salt.'AfterSaltRandomCharacters');
    
    $insertsql = "INSERT INTO user ( user_id, user_name, user_first, user_last, salt, user_password, user_level ) VALUES (NULL,?,?,?,?,?,'user' );";
    $statement = $db->prepare($insertsql);
    $statement->bind_param('sssss', $_POST['username'], $first,$last,$salt,$password);
  	if($statement->execute())
  	 $_SESSION['message'] = '<h2>You have sucessfully registered, please log in to start chatting</h2>';
    else 
      $_SESSION['message'] = "<h2>There was an error:".$db->error."</h2>";
  	header(sprintf("Location: %s", "index.php"));
  	exit; //this stops the rest of the page from processing before the next page loads
  }
}

//make the form to register
?>
<html>
	<head>
		<title>Register to the chat</title>
    <link rel="stylesheet" type="text/css" media="screen" href="style.css" />
    <link rel="icon" type="image/png" href="favicon.ico" />
	</head>
	<body>
    <a href="index.php">Back to home</a>
	  <h1>Register to chat</h1>
	  <?php
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
		<form method="post" action="">
		<fieldset style="padding: 20px;">
	    <legend>Register to chat</legend>
      <label for="first">First Name </label><input type="text" name="first" id="first" value="<?php if(isset($_POST['first'])) echo $_POST['first'];?>" /><br />
      <label for="last">Last Name </label><input type="text" name="last" id="last" value="<?php if(isset($_POST['first'])) echo $_POST['last'];?>" /><br />
      <label for="username">Username </label><input type="text" name="username" id="username" value="<?php if(isset($_POST['first'])) echo $_POST['username'];?>" /><br />
      <label for="password">Password </label><input type="password" name="password" id="password" /><br />
      <input type="submit" name="register" value="Register" class="button-primary" />
      <script>document.getElementById("first").focus()</script>
    </fieldset>
    </form>
	</body>
</html>