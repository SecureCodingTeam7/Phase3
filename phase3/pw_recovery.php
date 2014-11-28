<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/include/db_connect.php"); 
include_once(__DIR__."/class/c_user.php");
session_start();
if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login']) ) {
    // thats okay
} else if ( $_SESSION['user_email'] == "" || $_SESSION['user_level'] == "" || $_SESSION['user_login'] == "") {
	// thats okay
} else {
	// user already logged in
	if($_SESSION['user_level']) {
		header("Location: employee/approve.php");
	} else {
		header("Location: account/index.php");
	}
	die();
}


if( isset($_GET['email']) && isset($_GET['id']) ) {
	
	$user = new User();
	$user->getUserDataFromEmail($_GET['email']);
	$success = false;
	if($user->email) {
		// if we found the mail it is a valid user
		$success = $user->doPwRecovery($_GET['id']);
	}?>
	
	<!doctype html>
<html>
<head>
	<title>Password Recovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="login.php">Login</a>
				Recover Password
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
		<p>
			<?php 
			if($success) {
				echo "Your new password has been sent to you via email!";
			} else {
				echo "Something went wrong, please try again later!";
			}?>
		</p>
			
		</div>
		</div>
	</div>
</body>
</html>

<?php 
} else if( !(isset( $_POST['recoverPassword'] ) ) ) { ?>
<!doctype html>
<html>
<head>
	<title>Password Recovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="login.php">Login</a>
				Recover Password
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
		<p>Remember it again? <a href="login.php">Click here</a> to login.</p>
			<form method="post" action="" class="pure-form pure-form-aligned">
		    <fieldset>
		        <div class="pure-control-group">
		            <label for="email">Email</label>
		            <input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
		        </div>
		
		        <div class="pure-controls">
		            <button id="SignInButton" onclick="setTimeout(disableFunction, 1)" type="submit" name="recoverPassword" class="pure-button pure-button-primary">Send new Password</button>
		        </div>
		        
		        
		    </fieldset>
			</form>
			
			
	<script>
		function disableFunction() {
		    document.getElementById("SignInButton").disabled = 'true';
		}
	</script>


	    

		</div>
		</div>
	</div>
</body>
</html>

<?php 
} else {
	
	$user = new User();
	
	$user->getUserDataFromEmail($_POST['email']);
	
	if($user->email) {
		// if we found the mail it is a valid user
		$user->sendPwRecoveryMail();
	}?>
	<!doctype html>
<html>
<head>
	<title>Password Recovery</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="login.php">Login</a>
				Recover Password
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
		<p>A link to recover you password has been sent to the provided email address!</p>
			
		</div>
		</div>
	</div>
</body>
</html>
<?php 
}
?>
