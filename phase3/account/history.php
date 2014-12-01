<?php
ini_set( 'session.cookie_httponly', 1 );
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
 else if ( $_SESSION['user_level'] ) {
		header("Location: ../login.php");
		die();
	}
else {
	/* Session Valid */
	$user = new User();
	$selectedAccount = "none";
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	
	if ( isset( $_SESSION['selectedAccount'] ) ) {
		
		/* Make sure account belongs to user */
		$accounts = $user->getAccounts();
		if(in_array($_SESSION['selectedAccount'], $accounts)) {
			$selectedAccount = $_SESSION['selectedAccount'];
		} else {
			/* Possible malicious activity: Account does not belong to user
			 * Raise Session Error and close the session
			 */
			$_SESSION['error'] =  "Account mismatch detected.";
		}
	}
	
	/* If error or possible malicious activity was detected close the session */
	if ( ( isset( $_SESSION['error'] ) ) ) {
		header("Location:../logout.php");
		die();
	}
?>
<!doctype html>
<html>
<head>
	<title>MyBank: Transfer History</title>
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
				
				<div class="balance"><?php if ($selectedAccount > 0) { echo "Account Balance: ".$user->getBalanceForAccount( $selectedAccount );}?></div>
				<div class="availableFunds"><?php if ($selectedAccount > 0) { echo "Available Funds: ".$user->getAvailableFundsForAccount( $selectedAccount );}?></div>
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
						<th>Src-Name</th>
						<th>Destination</th>
						<th>Dst-Name</th>
						<th>Amount</th>
						<th>Valid</th>
						<th>Description</th>
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
						<td><?php if ($transaction['source'] == $account) {
							 echo "<p class=\"selectedAccount\">".$transaction['source']."</p>";
						} else { echo $transaction['source']; } ?></td>
						<td><?php echo $transaction['source_name'] ?></td>
						<td><?php if ($transaction['destination'] == $account) {
							 echo "<p class=\"selectedAccount\">".$transaction['destination']."</p>";
						} else { echo $transaction['destination']; } ?></td>
					  	<td><?php echo $transaction['destination_name'] ?></td>
						<td><p class=<?php if($transaction['destination'] == $account) echo "\"income\">"; else echo "\"expense\">"; echo $transaction['amount']."</p>"; ?></td>
						<td><?php if ($transaction['is_approved'] > 0) echo "yes"; else echo "no"; ?></td>
						<td><?php echo $transaction['description'] ?></td>
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
