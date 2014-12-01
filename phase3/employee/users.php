<?php
ini_set( 'session.cookie_httponly', 1 );
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../include/helper.php");
$loginPage = "../login.php";
$loginRedirectHeader = "Location: ".$loginPage;
session_start();
/* Generate Form Token (valid for this session) */
if (!isset($_SESSION['CSRFToken'])) {
	$_SESSION['CSRFToken'] = generateFormToken();
}

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
			echo "Current Customers (Click on the email to see details)";
			
			$users = $user->getAllUsers();
			$odd = true;
			$count = 0;
			
			?>
			<table class="pure-table">
			    <thead>
			        <tr>
			            <th>#</th>
			            <th>Email</th>
			            <th>Approved</th>
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
			            <td><?php echo ++$count ?></td>
			            <td>
			            <form method="post" action="user_details.php">
							<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
							<input type="hidden" name="id" value="<?php echo $user['id']; ?>" />
							<input type="submit" name="userDetailsSubmit" class="pure-button2" value="<?php echo $user['email']; ?>" />
						</form>
						</td>
			            <td><?php if ($user['is_active'] > 0) echo "yes"; else echo "no"; ?></td>
			        </tr>
			<?php
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
