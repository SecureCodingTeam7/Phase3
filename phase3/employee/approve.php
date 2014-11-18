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
} else if(!$_SESSION['user_level']) {
	header("Location: ../login.php");
	die();
} else {
	/* Session Valid */
	$user = new User();
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	
	$submitSucces = false;
	
	if(isset($_POST['transactions'])) {
		$submitSucces = true;
		$user->approveTransactions($_POST['transactions']);
	}
	
	if(isset($_POST['users'])) {
		$submitSucces = true;
		$user->approveUsers($_POST['users']);
	}
	
?>
<!doctype html>
<html>
<head>
	<title>Phase1: Employee Approve</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				Approve
				<a href="users.php">Users</a>
				</div>
				<div class="userpanel">
					<?php echo $_SESSION['user_email'] ?>
					<a href="../logout.php">Logout</a><br />
					Employee Approve
				</div>
			</div>
		</div>
		
		<div class="main">
		<form method="post">
		<?php 
			if($submitSucces) {
				echo "Approval was successful!";
				echo "<br>";
			}
			echo "Currently Inapproved Transactions";
			
			$transactions = $user->getInApprovedTransactions();
			$odd = true;
			$count = 0;
			
			?>
			<table class="pure-table">
			    <thead>
			        <tr>
			            <th>#</th>
			            <th>Source</th>
			            <th>Destination</th>
			            <th>Amount</th>
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
			            <td><?php echo "<input type=\"checkbox\" name=\"transactions[]\" value=\"".$transaction['id']."\">" ?></td>
			            <td><?php echo $transaction['source']; ?></td>
			            <td><?php echo $transaction['destination']; ?></td>
			            <td><?php echo $transaction['amount']; ?></td>
			            <td><?php echo $transaction['date_time']; ?></td>
			        </tr>
			<?php
			}
			?>

			    </tbody>
			</table>
			<br>
			
		<?php 
			echo "Currently Inapproved Users";
			
			$users = $user->getInApprovedUsers();
			$odd = true;
			$count = 0;
			
			?>
			<table class="pure-table">
			    <thead>
			        <tr>
			            <th>#</th>
			            <th>Email</th>
			            <th>Employee</th>
			        </tr>
			    </thead>
			
			    <tbody>
			<?php 
			foreach ($users as $user) { 
					if ($odd) {
						echo "<tr class=\"pure-table-odd\">";
						$odd = false;
					} else { 
						echo "<tr>";
						$odd = true; 
					}?>
			            <td><?php echo "<input type=\"checkbox\" name=\"users[]\" value=\"".$user['id']."\">" ?></td>
			            <td><?php echo $user['email']; ?></td>
			            <td><?php if ($user['is_employee'] > 0) echo "yes"; else echo "no"; ?></td>
			        </tr>
			<?php
			}
			?>

			    </tbody>
			</table>

		<br>
		<div class="pure-controls">
            <button id="approveButton" type="submit" name="approve" class="pure-button pure-button-primary">Approve</button>
		</div>
		</form>
		</div>
		</div>
	</div>
</body>
</html>

<?php
}
?>
