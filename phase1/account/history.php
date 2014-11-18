<?php
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../include/helper.php");
$loginPage = "../login.php";
$loginRedirectHeader = "Location: ".$loginPage;
session_start();
if ( !isset($_SESSION['user_email']) || !isset($_SESSION['user_level']) || !isset($_SESSION['user_login'])  ) {
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
} 
 else if($_SESSION['user_level']){
		header("Location: ../login.php");
		die();
	}
else {
	/* Session Valid */
	$user = new User();
	$selectedAccount = "none";
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	
	if ( isset( $_SESSION['selectedAccount'] ) ) {
		$selectedAccount = $_SESSION['selectedAccount'];
	}
?>
<!doctype html>
<html>
<head>
	<title>Phase1: Transfer History</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
					<a href="index.php">Account</a>
					<a href="transfer.php">Transfer</a>
					History
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
		<?php 
			if (!isset( $_SESSION['selectedAccount'] )) {
				echo "No account is active at the moment.<br />";
				echo "You can set the active account on the <a href=\"index.php\">Overview page</a>.";
			} else {
				echo "Transfer History for Account #".$selectedAccount;
			}
			
			$transactions = $user->getTransactions( $selectedAccount );
			$odd = true;
			$count = 0;
			
			?>
			<table class="pure-table">
			    <thead>
			        <tr>
			            <th>#</th>
			            <th>Source</th>
			            <th>Destination</th>
			            <th>Amount (Eur)</th>
			            <th>Approved</th>
			            <th>Time</th>
			        </tr>
			    </thead>
			
			    <tbody>
			<?php 
			foreach ($transactions as $transaction) { 
					if ($odd) {
						echo "<tr class=\"pure-table-odd\">";
						$odd = false;
					} else { 
						echo "<tr>";
						$odd = true; 
					}?>
			            <td><?php echo ++$count; ?></td>
			            <td><?php if ($transaction['source'] == $selectedAccount) {
			            	 echo "<p class=\"selectedAccount\">".$transaction['source']."</p>";
			            } else { echo $transaction['source']; } ?></td>
			            <td><?php if ($transaction['destination'] == $selectedAccount) {
			            	 echo "<p class=\"selectedAccount\">".$transaction['destination']."</p>";
			            } else { echo $transaction['destination']; } ?></td>
			          
			            <td><p class=<?php if($transaction['destination'] == $selectedAccount) echo "\"income\">"; else echo "\"expense\">"; echo $transaction['amount']."</p>"; ?></td>
			            <td><?php if ($transaction['is_approved']) echo "yes"; else echo "no"; ?></td>
			            <td><?php echo $transaction['date_time']; ?></td>
			        </tr>
			<?php
			}
			?>

			    </tbody>
			</table>

		</div>
		</div>
	</div>
</body>
</html>

<?php
}
?>
