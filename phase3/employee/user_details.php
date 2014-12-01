<?php
ini_set( 'session.cookie_httponly', 1 );
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
} else if(!$_SESSION['user_level']) {
	header("Location: ../login.php");
	die();
} else if(!isset($_POST['id'])) {
	header("Location:users.php");
	die();
} else if( !isset( $_POST['CSRFToken'] ) || !validateFormToken( $_POST['CSRFToken'] )) {
	$_SESSION['error'] = "The CSRF Token was not found or invalid.";
	header("Location:../logout.php");
	die();
} else {
	/* Session Valid */
	$user = new User();
	$user->getUserDataFromEmail( $_SESSION['user_email'] );	
?>
<!doctype html>
<html>
<head>
	<title>MyBank: Employee All Users</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				<a href="approve.php">Approve</a>
				Users
				</div>
				<div class="userpanel">
					<?php echo $_SESSION['user_email'] ?>
					<a href="../logout.php">Logout</a><br />
					Employee All Users
				</div>
			</div>
		</div>
		
		<div class="main">
		<?php 
		
			$selectedUser = new User();
			$selectedUser->getUserDataFromID( $_POST['id'] );
			echo "Details for ".$selectedUser->email;
			
			$accounts = $selectedUser->getAccounts();
			
			foreach($accounts as $account) {
				$transactions = $selectedUser->getTransactions( $account );
				$odd = true;
				$count = 0;
				
				
				?>
				<table class="pure-table">
				<caption style="caption-side:top"><?php echo "<br />Account #".$account;?></caption>
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
			}
				?>

			    </tbody>
			</table>

		<br>
		</div>
		</div>
	</div>
</body>
</html>

<?php
}
?>
