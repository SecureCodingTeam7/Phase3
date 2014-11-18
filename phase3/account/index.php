<?php
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../include/helper.php");
$loginPage = "../login.php";
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
}  else if($_SESSION['user_level']){
		header("Location: ../login.php");
		die();
	}

else {
	/* Session Valid */
	$user = new User();
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	$selectedAccount = "none";
	if (isset( $_SESSION['selectedAccount'] )) {
		$selectedAccount = $_SESSION['selectedAccount'];
	}
	
	/* Create new Account */
	if( (isset( $_POST['createAccount'] )) ) { 
			$accNumber = randomDigits( 10 );
			$user->addAccount( $accNumber );
	}
	
	if ( (isset( $_POST['selectAccount']))) {
		$selectedAccount = $_POST['accountNumber'];
		$_SESSION['selectedAccount'] = $selectedAccount;
	}
?>
<!doctype html>
<html>
<head>
	<title>Phase1: Account Overview</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
					Account
					<a href="transfer.php">Transfer</a>
					<a href="history.php">History</a>
				</div>
				
				<div class="userpanel">
					<?php echo $_SESSION['user_email'] ?>
					<a href="../logout.php">Logout</a><br />
					<?php 
					if ($selectedAccount > 0) {
					echo "Account: ".$selectedAccount;	
					} else {
					echo "Account: none";
					}
					?>
				</div>
			</div>
		</div>
		
		<div class="main">
		<div class="accountList">
		<?php
		/* Get User Accounts */
		$userAccounts = $user->getAccounts();
		foreach ($userAccounts as $account) {
			?>
			<form method="post" action="">
				<input type="hidden" name="accountNumber" value="<?php echo $account ?>" />
				<input type="submit" name="selectAccount" class="pure-button pure-button-active" value="<?php echo $account ?> [Select]" />
			</form>
			<?php
		}
		?>
		</div>
		<div class="accountCreation">
			<form method="post" action="">
				<li class="buttons">
					<input type="submit" name="createAccount" value="Create New Account" class="pure-button pure-button-primary" id="createNewAccountButton" />
				</li>
			</ul>
			</form>
		</div>
			Welcome, <?php echo $_SESSION['user_email'] ?>. Below is a list of your accounts.
		</div>
	</div>
</body>
</html>

<?php
}
?>
