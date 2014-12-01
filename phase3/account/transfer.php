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
} 
 else if($_SESSION['user_level']){
		header("Location: ../login.php");
		die();
	}
else {
	
	/* Session Valid */
	$user = new User();
	$selectedAccount = "none";
	$transferSuccess = 0;
	$transferMessage = "";
	
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	$requiredTAN = "-1";
	
	if ( isset( $_SESSION['selectedAccount'] ) ) {
		/* Make sure account belongs to user */
		$accounts = $user->getAccounts();
		
		if(in_array($_SESSION['selectedAccount'], $accounts)) {
			
			$selectedAccount = $_SESSION['selectedAccount'];
			$requiredTAN = $user->getNextTAN( $selectedAccount );
			
			if ( isset( $_POST['creditTransfer'] ) ) {
				//echo $_POST['amount'];
				//echo $_POST['destination'];
				//echo $_POST['description'];
				//echo $_POST['tan'];
				if (isset( $_POST['CSRFToken']) && validateFormToken($_POST['CSRFToken'])) {
					try {
						if( $user->transferCredits( $_POST, $selectedAccount ) ) {
							$transferSuccess = 1;
							$transferMessage = "Successfully transferred " .$_POST['amount']. " Euro to " .$_POST['destination'];
						} else {
							$transferSuccess = -1;
							$transferMessage = "Transfer Failed.";
						}
					} catch (Exception $e) {
						$transferMessage = $e->errorMessage();
					}
				} else {
					$_SESSION['error'] =  "CSRF Token invalid.";
				}
			}
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
	<title>MyBank: Credit Transfer</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="../style/style.css" type="text/css" rel="stylesheet" />
	<link href="../style/pure.css" type="text/css" rel="stylesheet" />
	
	<script language="javascript" type="text/javascript">
	function limitArea(limitField, limitCount, limitNum) {
		if (limitField.value.length > limitNum) {
			limitField.value = limitField.value.substring(0, limitNum);
		} else {
			limitCount.value = limitNum - limitField.value.length;
		}
	}
	</script>
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
					<a href="index.php">Account</a>
					Transfer
					<a href="history.php">History</a>
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
				echo "Credit Transfer for Account <em>#".$selectedAccount."</em>";
				echo "<p>   ".$transferMessage."</p>";
			?>
		<form method="post" action="" class="pure-form pure-form-aligned" enctype='multipart/form-data'>
			
		    <fieldset>
		    	<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
		        <div class="pure-control-group">
		            <label for="destination">Destination</label>
		            <input id="destination" name="destination" type="text" placeholder="Account Number" 
		            value="<?php if (isset($_POST['destination'])) echo $_POST['destination']; ?>" required>
		        </div>
		
		        <div class="pure-control-group">
		            <label for="amount">Amount</label>
		            <input id="amount" name="amount" type="text" placeholder="Amount in Eur" 
		             value="<?php if (isset($_POST['amount'])) echo $_POST['amount']; ?>" required>
		        </div>
		        
        		<div class="pure-control-group">
		            <label for="description">Description</label>
		            <textarea id="desc2" name="description" type="text" style="width:190px; height:200px;" 
		            onKeyDown="limitArea(this.form.description,this.form.countdown,200);"
					onKeyUp="limitArea(this.form.description,this.form.countdown,200);"
					required><?php if (isset($_POST['description'])) echo $_POST['description']; ?></textarea>
		        </div>
		        

		        
        		<div class="pure-control-group">
				<?php if($user->useScs) {
					echo "<label for=\"amount\"><em>SCS TAN</em></label>";
				} else {
					echo "<label for=\"amount\">TAN <em>#".$user->getNextTAN( $selectedAccount )."</em></label>";
				}?>
            		<input id="tan" name="tan" type="text" placeholder="TAN"
            		value="<?php if (isset($_POST['tan'])) echo $_POST['tan']; ?>"
            		required>
        		</div>
		
		        <div class="pure-controls">
		            <button type="submit" name="creditTransfer" class="pure-button pure-button-primary">Transfer</button>
		        </div>
		        
			<div class = "pure-controls" > OR </div>
		    </fieldset>
		</form>
		
		<form method="POST" action="upload.php" class="pure-form pure-form-aligned">
		  <div class="pure-controls">
		            <button type ="submit" name="upload" class="pure-button pure-button-primary">Upload File</button>
		        </div>
		</form>
		
		
		<?php
		}
		?>
		</div>
		</div>
	</div>
</body>
</html>

<?php
}
?>
