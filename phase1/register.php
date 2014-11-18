<?php
include_once(__DIR__."/include/db_connect.php"); 
include_once(__DIR__."/class/c_user.php");

if( !(isset( $_POST['checkRegister'] ) ) ) { ?>
<!doctype html>
<html>
<head>
	<title>Phase1: Register Page</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<link href="style/style.css" type="text/css" rel="stylesheet" />
	<link href="style/pure.css" type="text/css" rel="stylesheet" />
</head>
<body>
	<div class="content">
		<div class="top_block header">
			<div class="content">
				<div class="navigation">
				Register
				<a href="login.php">Login</a>
				</div>
				
				<div class="userpanel">
				</div>
			</div>
		</div>
		
		<div class="main">
		<p>Already have an account? <a href="login.php">Click here</a> to sign in.</p>
	
			<form method="post" action="" class="pure-form pure-form-aligned">
		    <fieldset>
		        <div class="pure-control-group">
		            <label for="email">Email</label>
		            <input name="email" id="email" type="email" placeholder="YourAccount@bank.de" required>
		        </div>
		
		        <div class="pure-control-group">
		            <label for="password">Password</label>
		            <input name="password" id="password" type="password" placeholder="***********" onkeyup="check_pw()" required>
		            <b id=password_info></b>
		        </div>
		        
		        <div class="pure-control-group">
		            <label for="confirm_password">Confirm Password</label>
		            <input name="confirm_password" id="confirm_password" type="password" placeholder="***********" onkeyup="check_confirm_pw()" required>
		            <b id=confirm_password_info></b>
		        </div>
		       
		       <div class="pure-control-group">
				<label for="status">Your Status</label>
		        <select id="state" name="status" size="1">
				<option value="0">Client</option>
				<option value="1">Employee</option>
			    </select>
		        </div> 
		        
		        <div class="pure-controls">
		            <button id="SignInButton" type="submit" name="checkRegister" class="pure-button pure-button-primary">Finish Registration</button>
		        </div>
		    </fieldset>
			</form>

		</div>
		</div>
	</div>
</body>
<script>
	var pw_info = document.getElementById("password_info")
	var confirm_pw_info = document.getElementById("confirm_password_info")
	
	var pw_field = document.getElementById("password")
	var confirm_pw_field = document.getElementById("confirm_password")
	
	var submit_button = document.getElementById("SignInButton")
	submit_button.disabled = true
	
	function check_pw() {
		// lets check if the pw is strong enough
		var pw = pw_field.value
		var lowercase = pw.search("[A-Z]")
		var uppercase = pw.search("[a-z]")
		var number = pw.search("[0-9]")
		
		if(pw.length <= 8 || lowercase == -1 || uppercase == -1 || number == -1) {
			pw_info.textContent = "Password must have at least eight characters, one upper case, one lower case letter and one number"
			submit_button.disabled = true
		} else {
			pw_info.textContent = ""
			if(confirm_pw_field.value.length > 0) {
				check_confirm_pw()
			}
		}
	}
	
	function check_confirm_pw() {
		if(confirm_pw_field.value != pw_field.value) {
			confirm_pw_info.textContent = "The two passwords do not match!"
			submit_button.disabled = true
		} else {
			confirm_pw_info.textContent = ""
			submit_button.disabled = false
		}
		check_pw()
	}
</script>
</html>
<?php 
} else {
	//~ echo "checkRegister Post";

	$user = new User();
	//~ echo "<br />[DEBUG] Email: ". $_POST['email'];
	//~ echo "<br />[DEBUG] Password: " . $_POST['password'];
	//~ echo "<br />[DEBUG] Status: " . $_POST['status'];
		
	if( $user->register( $_POST ) ) {
		echo "<br />Registration for ". $_POST['email']." successful. Go to <a href='login.php'>Sign in</a>.";
	} else {
		echo "<br />Unable to register at this time. Please <a href='register.php'>try again</a>.";
	}
}
?>
