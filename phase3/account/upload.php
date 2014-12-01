
<?php
	ini_set( 'session.cookie_httponly', 1 );
    include_once(__DIR__."/../class/c_user.php");
    include_once(__DIR__."/../include/helper.php");
    $loginPage = "../login.php";
    $loginRedirectHeader = "Location: ".$loginPage;
    $accountPage = "index.php";
    $accountRedirectHeader = "Location: ".$accountPage;
    
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
    
    else if ( !isset($_SESSION['selectedAccount']) || $_SESSION['selectedAccount'] == "" ) {
        header($accountRedirectHeader);
    }
    else {
        
        /* Session Valid */
        $user = new User();
        $selectedAccount = "none";
        $requiredTAN = -1;
        $uploadMessage = "";
     
        $user->getUserDataFromEmail( $_SESSION['user_email'] );
        
        if ( isset( $_SESSION['selectedAccount'] ) ) {
            $selectedAccount = $_SESSION['selectedAccount'];
            
            
            if(isset($_POST['uploadFile'])){
               
            	if (isset( $_POST['CSRFToken']) && validateFormToken($_POST['CSRFToken'])) {
	                //~ $name       = "transactionFile";
	                //~ $temp_name  = $_FILES['myfile']['tmp_name'];
	                
	               $uploadStatus = $_FILES['myfile']['error'];
	
					switch($uploadStatus){
						case UPLOAD_ERR_OK:
									
								$tan_number = "";
								if($user->useScs == "1") {
									$tan_number = "-1";
	                			} else {
									$tan_number = $user->getNextTan($selectedAccount);
								}
									
	                            $command = "transfer_parser ".$user->id." ".$selectedAccount." ".$tan_number." ".$_FILES['myfile']['tmp_name']." 2>&1";
	                            $result="";
	                            exec($command,$result,$return);
	
	                            if($return == 0 ){
	                                $uploadMessage=" Transaction commited";
									if($user->useScs == "0") {
	                               	 $user->updateNextTan( $selectedAccount);
	}
	                                
	                            }
	      
	                            else {
									
	                                $uploadMessage = "Error: ".$result[0];
	                            }
	                            break;
	                            
	                     case UPLOAD_ERR_INI_SIZE: 
									$uploadMessage = " Error: uploaded file is too big";
									break;
	                     default:
								    $uploadMessage = " Error: please upload your file again"; 
								    break;
					}
	            } else {
	            	$_SESSION['error'] =  "CSRF Token invalid.";
	            }
            }
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
</head>
<body>
<div class="content">
<div class="top_block header">
<div class="content">
<div class="navigation">
<a href="index.php">Account</a>
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



<div class="main">
	<div id="description">
		Example file:<br /><br />
		code:299347049962292<br /><br />
		destination:2510053093<br />
		amount:123<br />
		description:my description<br /><br />
		destination:123450976<br />
		amount:100.80<br />
		description:my description2<br /><br />
		...<br />
	</div>
<form method="post" action="" class="pure-form pure-form-aligned" enctype='multipart/form-data'>

<fieldset>
<input type="hidden" name="CSRFToken" value="<?php echo $_SESSION['CSRFToken']; ?>" />
<div class = "pure-controls" > Source : #<?php echo $selectedAccount ;?>
</div>
<?php if($user->useScs == "0") { ?>
<div class = "pure-controls" > Required Tan : #<?php echo $user->getNextTan($selectedAccount );?>
</div>
<?php
}
?>

<div class="pure-controls">
<input type="file" name="myfile"><br>
</div>
<div class="pure-controls">
<button type="submit" name = "uploadFile" class="pure-button pure-button-primary" > Submit</button>
</div>
<div class="pure-controls">
<?php echo $uploadMessage; ?>
</div>
</form>
</fieldset>	

</div>
</div>
</body>

</html>
