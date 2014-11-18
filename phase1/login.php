<?php
include_once(__DIR__."/include/db_connect.php"); 
include_once(__DIR__."/class/c_user.php");
session_start();
if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login']) ) {
    // thats okay, the user can login
} else if ( $_SESSION['user_email'] == "" || $_SESSION['user_level'] == "" || $_SESSION['user_login'] == "") {
	// thats okay, the user can login
} else {
	// user already logged in
	if($_SESSION['user_level']) {
		header("Location: employee/approve.php");
	} else {
		header("Location: account/index.php");
	}
	die();
}

if( !(isset( $_POST['checkLogin'] ) ) ) { ?>
<!doctype html>
<html>
<head>
	<title>Phase1: Login Landing Page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="register.php">Register</a>
				Login
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
		<p>No Account yet? <a href="register.php">Click here</a> to register with us.</p>
			<form method="post" action="" class="pure-form pure-form-aligned">
		    <fieldset>
		        <div class="pure-control-group">
		            <label for="email">Email</label>
		            <input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
		        </div>
		
		        <div class="pure-control-group">
		            <label for="password">Password</label>
		            <input name="password" id="password" type="password" placeholder="***********" required>
		        </div>
		
		        <div class="pure-controls">
		            <button id="SignInButton" type="submit" name="checkLogin" class="pure-button pure-button-primary">Sign In</button>
		        </div>
		    </fieldset>
			</form>


	    

		</div>
		</div>
	</div>
</body>
</html>

<?php 
} else {
	//~ echo "Login Post";
	
	$user = new User();
	//~ echo "<br />[DEBUG]Email: ".  $_POST['email'];
	//~ echo "<br />[DEBUG]Password: " .  $_POST['password'];
	
	try { 
	
		if( $user->checkCredentials( $_POST ) ) {
			/* Set Session */
			$user->getUserDataFromEmail($_POST['email']);
			session_start();
			$_SESSION['user_email'] = $user->email;
			$_SESSION['user_level'] = $user->isEmployee;
			$_SESSION['user_login'] = 1;
			
			if($user->isEmployee) {
				header("Location: employee/approve.php");
			} else {
				header("Location: account/index.php");
			}
			die();
			
			//~ echo "<br />Successful Login. <a href='account/index.php'>Click here</a> to continue.";
			//~ echo "<br />[DEBUG] Session Data: user_email: " . $_SESSION['user_email'] .
														  //~ ", user_level: " . $_SESSION['user_level'] .
														  //~ ", user_login: " . $_SESSION['user_login'];	
		} else {
			/* Completeley destroy Session */
			//$_SESSION = array();
			//session_destroy();
			
			echo "<br />Incorrect Email/Password. Please <a href='login.php'>Try again</a>.";	
		}
	} catch(IsActiveException $e) {
		echo "<br />Your account was not approved yet, please wait until someone does!";
	} 
}
?>
