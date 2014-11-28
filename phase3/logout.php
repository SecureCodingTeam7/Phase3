<?php
ini_set( 'session.cookie_httponly', 1 );
$loginPage = "login.php";
$loginRedirectHeader = "Location: ".$loginPage;
session_start();
if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login']) ) {
	echo "Session Invalid. <a href='$loginPage'>Click here</a> to sign in.";

	/* No Session -> Redirect to Login */
	//header($loginRedirectHeader);
} else if ( $_SESSION['user_email'] == "" || $_SESSION['user_level'] == "" || $_SESSION['user_login'] == "") {
	echo "Empty Session Data. <a href='$loginPage'>Click here</a> to sign in.";

	/* Destroy Session */
	$_SESSION = array();
	session_destroy();

	/* Session Data Invalid -> Redirect to Login */
	//header($loginRedirectHeader);
} else {
	/* Session Valid, display any Session Errors if appropriate */
	if (isset($_SESSION['error'])) {
		echo "Session Error: ".$_SESSION['error']."<br />";
	}
	
	/* Close Session */
	$_SESSION = array();
	session_destroy();
	
	echo "You have been logged out. <a href='$loginPage'>Click here</a> to log back in.";
	
	/* Session Closed -> Redirect to Login */
	//header($loginRedirectHeader);
}
?>
