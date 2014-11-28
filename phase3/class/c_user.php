<?php
include_once(__DIR__.'/../include/conf.php');
include_once(__DIR__.'/../include/helper.php');
include_once(__DIR__.'/../include/crypt.php'); 
include_once(__DIR__.'/../include/TransferException.php');
include_once(__DIR__.'/../include/InvalidInputException.php');
include_once(__DIR__.'/../include/IsActiveException.php');
include_once(__DIR__.'/../include/SendEmailException.php');
include_once(__DIR__.'/../include/phpmailer/class.smtp.php');
require(__DIR__.'/../include/phpmailer/class.phpmailer.php');

class User {
	public $email = null;
	public $password = null;
	public $name = null;
	public $id = null;
	public $isEmployee = null;
	public $isActive = null;
	public $DEBUG = false;
	
	public function getAccountNumberID( $accountNumber ) {
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$sql = "SELECT id FROM accounts WHERE account_number = :account_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetch();
			
			if ($stmt->rowCount() > 0) {
				return $result['id'];
			} else {
				return -1;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}	
	}
	
	
	public function getTransactions( $accountNumber ) {
		/* Make sure account number belongs to this user */
		$userAccounts = $this->getAccounts();
		if ( !in_array($accountNumber, $userAccounts ) ) {
			return array ();
		}
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
			$sql = "SELECT *, BIN(`is_approved` + 0) AS `is_approved` FROM transactions WHERE source = :account_number OR destination = :account_number ORDER BY date_time";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetchAll();
				
			if ($stmt->rowCount() > 0) {
				return $result;
			} else {
				return array();
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	
	public function generateTANList( $accountNumber ) {
		/* Generate 100 random, unique transaction Codes of length 15 digits for this user */
		$maxNumTries = 100; // maximum number of rerolls in case a code is not unique
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			// Obtain ID of given accountNumber
			$accountID = $this->getAccountNumberID ( $accountNumber );
			
			if ($accountID < 0) {
				return false;
			}
			
			for ( $codeNumber = 0; $codeNumber < 100; $codeNumber++) {
				$tries = 0;
				$unique = false;
				while ( !$unique && ( $tries <= $maxNumTries ) ) {
					$tries++;
					
					// Generate random code of length 15
					$code = randomDigits(15);
					
					// Make sure this code is unique
					$sql = "SELECT code FROM trans_codes WHERE code = :code";
					$stmt = $connection->prepare ( $sql );
					$stmt->bindValue ( "code", $code, PDO::PARAM_STR );
					$stmt->execute();
					
					// If code was not found set unique to true
					if ( $stmt->rowCount() == 0 ) {
						$unique = true;
					}
				}
				
				if ($tries >= $maxNumTries) {
					echo "Failed to Generate TAN List, too many tries!";
					return false;
				}
				
				$tans[$codeNumber] = $code; 
				// Code is unique, insert it into db
				$sql = "INSERT INTO trans_codes (account_id, code_number, code, is_used) VALUES (:account_id, :code_number, :code, :is_used)";
				$stmt = $connection->prepare ( $sql );
				$stmt->bindValue ( "account_id", $accountID, PDO::PARAM_STR );
				$stmt->bindValue ( "code_number", $codeNumber, PDO::PARAM_STR );
				$stmt->bindValue ( "code", $code, PDO::PARAM_STR );
				$stmt->bindValue ( "is_used", false, PDO::PARAM_STR );
				
				$stmt->execute();
			}
			
			
			// Sanity Check
			$sql = "SELECT * FROM trans_codes WHERE account_id = :account_id";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue ( "account_id", $this->id, PDO::PARAM_STR );
			$stmt->execute();
			
			//TODO why is rowCount() =0 ?
			//~ echo $stmt->rowCount();
			//~ if ( $stmt->rowCount() >= 100 ) {
				
				$codes = "";
				for($i=0; $i<100;$i++){
					$codes.= 'TAN #'.$i.": ".$tans[$i]. "\n";
				}
				

				$message= "Dear User ".$this->email.".\n Your registration at mybank was successful, Please wait until an employee approves your request.\n Here are your transcation codes: \n".$codes;
				
				try{
					$this->sendMail($this->email, $message);
				}
				catch (SendEmailException $e){
					echo "<br/>".$e->errorMessage();	
					return false;
				}
					
				return true;
				
				
			//~ } else {
				//~ return false;
			//~ }
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (generateTANList): ". $e->getMessage();
			return false;
		}
	}
	
	function sendMail($email,$message){
		
			
		$mail = new PHPMailer();
	
		$mail->IsSMTP(); // enable SMTP
		$mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
		$mail->SMTPAuth = true;  // authentication enabled
		$mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for GMail
		$mail->Host = 'smtp.gmail.com';
		$mail->Port = 465;
		$mail->Username = "scteam07";
		$mail->Password = "#team7#beste";
	
		$mail->From     = "admin@mybank.com";
		$mail->AddAddress($email);
	
		$mail->Subject  = "registration confirmation";
		$mail->Body     = $message;
		$mail->WordWrap = 50;
	
		if(!$mail->Send()) {
			
			throw new SendEmailException($mail->ErrorInfo);
		} 
	}
	//~ public function sendMail($email, $message) {
		//~ 
					//~ echo "<br/> Sending mail";	
		//~ $header = 'From: info@mybank.com' . "\r\n";
		//~ $message = wordwrap($message, 70);
		//~ 
		//~ $result = mail($email, 'mybank registration', $message, $header);
		//~ 
		//~ if($result == 0) {
			//~ throw new SendMailExcceptio('mail() returned 0');
		//~ }
	//~ }
	
	public function commitTransaction( $source, $destination, $amount, $code, $description ) {
		$is_approved = true;
		if ( $amount >= 10000 ) {
			$is_approved = false;
		}
		
		/* Obtain Source & Destination Names */
		$sourceName = getAccountOwner( $source );
		$destName = getAccountOwner( $destination );
		// echo "<br />SrcAcc: ".$source;
		// echo "<br />SrcName: ".$sourceName;
		// echo "<br />DstName: ".$destName;
		
		if ($sourceName == "") {
			throw new TransferException("Unable to obtain Owner of Source Account.");
		}
		
		if ($destName == "") {
			throw new TransferException("Unable to obtain Owner of Destination Account.");
		}
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	
			$sql = "UPDATE trans_codes SET is_used = :is_used WHERE code = :code";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "code", $code, PDO::PARAM_STR );
			$stmt->bindValue( "is_used", true, PDO::PARAM_STR);
			$stmt->execute();
			
			if ( $stmt->rowCount() > 0 ) {
				if ( $this->updateNextTan( $source ) ) {
					$sql = "INSERT INTO transactions (source, source_name, destination, destination_name, amount, description, code, is_approved, date_time) VALUES (:source, :source_name, :destination, :destination_name, :amount, :description, :code, :is_approved, NOW())";
					$stmt = $connection->prepare( $sql );
					$stmt->bindValue( "source", $source, PDO::PARAM_STR );
					$stmt->bindValue( "source_name", $sourceName, PDO::PARAM_STR );
					$stmt->bindValue( "destination", $destination, PDO::PARAM_STR );
					$stmt->bindValue( "destination_name", $destName, PDO::PARAM_STR );
					$stmt->bindValue( "amount", $amount, PDO::PARAM_STR );
					$stmt->bindValue( "description", $description, PDO::PARAM_STR );
					$stmt->bindValue( "code", $code, PDO::PARAM_STR );
					$stmt->bindValue( "is_approved", $is_approved, PDO::PARAM_STR );
					$stmt->execute();
					
					if ( $stmt->rowCount() > 0) {
						return true;
					} else {
						throw new TransferException("Failed to insert transaction.");
					}
				}
			} else { throw new TransferException("TAN was already used.");}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
	
	
	public function verifyTAN( $accountNumber, $tan, $tanNumber ) {
		$accountID = $this->getAccountNumberID( $accountNumber );
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
			$sql = "SELECT code FROM trans_codes WHERE account_id = :account_id AND code_number = :code_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_id", $accountID, PDO::PARAM_STR );
			$stmt->bindValue( "code_number", $tanNumber, PDO::PARAM_STR );
			$stmt->execute();
			
			$result = $stmt->fetch();
			
			if ( $tan == $result['code'] ) {
				return true;
			} else {
				return false;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
	
	
	public function selectRandomTAN( $accountNumber ) {
		$accountID = $this->getAccountNumberID ( $accountNumber );
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				
			$sql = "SELECT code_number, code FROM trans_codes WHERE account_id = :account_id AND is_used = false";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue( "account_id", $accountID, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetchAll();
				
			$connection = null;
			
			if ($stmt->rowCount() > 0) {
				$index = rand ( 0, ($stmt->rowCount() - 1) );
				$tanNumber = $result[$index]['code_number'];
				$tan = $result[$index]['code'];
				//echo "<br />TAN NUMBER: " .$tanNumber;
				//echo "<br />TAN: " .$tan;
				return $tanNumber;
			} else {
				return -1;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}
	}
	
	
	public function transferCredits( $data = array(), $source ) {

		if ( isset( $data['description'] ) ) {
			$description = stripslashes( strip_tags( $data['description'] ) );
			if (!preg_match('/^[a-z0-9 .:,\-]+$/i', $description)) { 
				throw new TransferException("The description may only contain letters, numbers,<br />and the following characters: .,:-"); 
			}
			
			if (strlen($description) > 200) {
				throw new TransferException("Please shorten your description to 200 characters or less.");
			}
		} else throw new TransferException("Description invalid.");
		
		if ( isset( $data['destination'] ) ) {
			$destination = stripslashes( strip_tags( $data['destination'] ) );
			if (!ctype_digit ( $destination )) {
				throw new TransferException("The Destination Account may only contain digits.");
			}
		} else throw new TransferException("Destination invalid.");
		
		if ( isset( $data['amount'] ) ) {
			$amount = stripslashes( strip_tags( $data['amount'] ) );
			
			if ( !is_numeric( $amount ) ) {
				throw new TransferException("Amount must be a number.");;
			}

			if ( $amount <= 0 ) {
				throw new TransferException("Amount must be positive.");;
			}
		} else throw new TransferException("Amount Invalid.");
		
		if ( isset( $data['tan'] ) ) {
			$tan = stripslashes( strip_tags( $data['tan'] ) );
			
			if (!ctype_digit ( $tan )) {
				throw new TransferException("The TAN may only contain digits.");
			}
		} else throw new TransferException("TAN invalid.");
		
		if ( $destination == $source ) {
			throw new TransferException("Destination account must be different from source account.");
		}

		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			// Obtain user_id associated with given source account
			$sql = "SELECT user_id FROM accounts WHERE account_number = :account_number";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "account_number", $source, PDO::PARAM_STR );
			$stmt->execute();
			
			$result = $stmt->fetch();
			$connection = null;
			
			// Was user_id found for given source account?
			if ( $stmt->rowCount() > 0 ) {
				// Make sure source account belongs to this user
				if ( $result['user_id'] != $this->id ) {
					throw new TransferException("User mismatch detected. Please Log out and Sign back in.");
				} else {
					// source account belongs to user
					// make sure destination account exists
					if (!checkAccountExists( $destination )) {
						throw new TransferException("The destination account doesn't exist.");
					} else {
						$currentTANNumber = $this->getNextTan( $source );
						if ( $currentTANNumber < 0 )
							throw new TransferException("Unable to obtain TAN number.");
						if ( $this->verifyTAN( $source, $tan, $currentTANNumber ) ) {
							return $this->commitTransaction($source, $destination, $amount, $tan, $description);
						} else {
							throw new TransferException("Invalid TAN.");
						}
					}
				}
			} else {
				return false;
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
	
	
	public function register( $data = array() ) {
		// DEBUG
		if ($this->DEBUG) {
			echo "<br />===================================================<br />";
			echo "Call: register() with POST DATA:<br />";
			echo "EMAIL: ".$data['email']."<br />";
			echo "Name: ".$data['username']."<br />";
			echo "Pass: ".$data['password']."<br />";
			echo "ConfirmPass: ".$data['confirm_password']."<br />";
			echo "Status: ".$data['status']."<br />";
		}
		
		// validate input
		if( isset( $data['email'] ) ) {
			$this->email = stripslashes( strip_tags( $data['email'] ) ); 
		} else {
			throw new InvalidInputException("No email found. Please check the Email address.");
		}
		
		if (!isValidEmail( $this->email )) {
			throw new InvalidInputException("Email address invalid. Please check the Email address.");
		}
		
		if( checkUserExists( $this->email ) ){
			throw new InvalidInputException("There already exists a user with this email address.");
		}
		
		if ( isset( $data['username'] ) ) {
			$this->name = stripslashes( strip_tags( $data['username'] ) );
		} else {
			throw new InvalidInputException("No Name provided. Please check the Name.");
		}
		
		if ( preg_match('/[^a-z\s-]/i', $this->name ) ) {
			throw new InvalidInputException("Invalid Name. Please check the Name.");
		}
		
		if( isset( $data['password'] ) ) {
			$this->password = stripslashes( strip_tags( $data['password'] ) );
		} else {
			throw new InvalidInputException("Please check your password.");
		}
		
		if( isset( $data['confirm_password'] ) ) {
			$confirm_password = stripslashes( strip_tags( $data['confirm_password'] ) );
		} else {
			throw new InvalidInputException("Please check the confirmation password.");
		}
		
		if( isset( $data['status'] ) ) {
			$status = stripslashes( strip_tags( $data['status'] ) );
		} else {
			throw new InvalidInputException("Please select whether you are an Employee or Client.");
		}


		// Input seems valid, proceed with registration
		if ($data['status'] == 1){
			$this->isEmployee = true;
		} else {
			$this->isEmployee = false;
		}
		
		if (!$this->checkPassword($this->password,$confirm_password)){
		 	throw new InvalidInputException("The two passwords do not match. Please check your password and confirmation password.");
		}
		
		
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	 
			$sql = "INSERT INTO users (email,name,passwd,is_employee,is_active) VALUES (:email,:name,:password,:isEmployee,:isActive)";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $this->email, PDO::PARAM_STR );
			$stmt->bindValue( "name", $this->name, PDO::PARAM_STR );
			$stmt->bindValue( "password", generateSaltedHash($this->password), PDO::PARAM_STR );
			$stmt->bindValue( "isEmployee", $this->isEmployee, PDO::PARAM_STR );
			$stmt->bindValue( "isActive", false, PDO::PARAM_STR );
			$stmt->execute();
				
			$connection = null;
			
			if ( $stmt->rowCount() > 0 ) {
				$this->getUserDataFromEmail( $this->email );
				
				if(!$this->isEmployee){
					$this->addAccount( generateNewAccountNumber() );
				}
				return true;
			} else {
				return false;
			}
			
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (register): ". $e->getMessage();
			return false;
		}
	}
	
	
	public function getNextTan( $accountNumber ) {
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$sql = "SELECT next_tan FROM accounts WHERE account_number = :account_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue ( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			$result = $stmt->fetch();
			
			return $result['next_tan'];
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}
	}
	
	
	public function updateNextTan( $accountNumber ) {
		$randomTANNumber = $this->selectRandomTAN( $accountNumber );

		if ( $randomTANNumber < 0 )
			throw new TransferException("Failed to generate new TAN number (All TANs exhausted?).");
			
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			
			$sql = "UPDATE accounts SET next_tan = :next_tan WHERE account_number = :account_number";
			$stmt = $connection->prepare ( $sql );
			$stmt->bindValue ( "next_tan", $randomTANNumber, PDO::PARAM_STR );
			$stmt->bindValue ( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->execute();
			
			if ($stmt->rowCount() > 0) {
				return true;
			} else {
				throw new TransferException("Failed to update TAN number.");
			}
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return false;
		}
	}
	
	
	public function addAccount( $accountNumber ) {
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
			$sql = "INSERT INTO accounts (user_id,account_number,next_tan) VALUES (:user_id,:account_number,:next_tan)";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "user_id", $this->id, PDO::PARAM_STR );
			$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
			$stmt->bindValue( "next_tan", rand(0,99));
			$stmt->execute();
		
			$connection = null;
				
			if ( $stmt->rowCount() > 0 ) {
				$this->generateTANList( $accountNumber );
				return true;
			} else {
				return false;
			}
				
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (addAccount): ". $e->getMessage();
			return false;
		}
	}
	
	
	public function getAccounts () {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT * FROM accounts WHERE user_id = :id";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "id", $this->id, PDO::PARAM_STR );
			$stmt->execute();
		
			$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 2);
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function getAccountsForId ($id) {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT * FROM accounts WHERE user_id = :id";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "id", $id, PDO::PARAM_STR );
			$stmt->execute();
		
			$result = $stmt->fetchAll(PDO::FETCH_COLUMN, 2);
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function getUserDataFromEmail( $email ) {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, email, passwd, BIN(`is_employee` + 0) AS `is_employee`, BIN(`is_active` + 0) AS `is_active`, pw_recover_id FROM users WHERE email = :email LIMIT 1";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $email, PDO::PARAM_STR );
			$stmt->execute();
		
			$result = $stmt->fetch();
			
			$this->email = $result['email'];
			$this->name = $result['name'];
			$this->password = $result['passwd'];
			$this->isEmployee = $result['is_employee'];
			$this->isActive = $result['is_active'];
			$this->id = $result['id'];
			$this->pwRecoverId = $result['pw_recover_id'];
			
			if ($this->DEBUG) {
				echo "<br />===================================================<br />";
				echo "Call: getUserDataFromEmail() for ".$email.":<br />";
				echo "EMAIL: ".$result['email']."<br />";
				echo "Name: ".$result['name']."<br />";
				echo "Pass: ".$result['passwd']."<br />";
				echo "IsEmployee: ".$result['is_employee']."<br />";
				echo "IsActive: ".$result['is_active']."<br />";
				echo "ID: ".$result['id']."<br />";
				echo "PWRecoverID: ".$result['pw_recover_id']."<br />";
			}
			
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (getUserDataFromEmail): ". $e->getMessage();
			return array();
		}
	}
	
	public function getUserDataFromID( $id ) {
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, email, passwd, BIN(`is_employee` + 0) AS `is_employee`, BIN(`is_active` + 0) AS `is_active`, pw_recover_id FROM users WHERE id = :id LIMIT 1";
	
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "id", $id, PDO::PARAM_STR );
			$stmt->execute();
	
			$result = $stmt->fetch();
				
			$this->email = $result['email'];
			$this->name = $result['name'];
			$this->password = $result['passwd'];
			$this->isEmployee = $result['is_employee'];
			$this->isActive = $result['is_active'];
			$this->id = $result['id'];
			$this->pwRecoverId = $result['pw_recover_id'];
				
			if ($this->DEBUG) {
				echo "<br />===================================================<br />";
				echo "Call: getUserDataFromEmail() for ".$email.":<br />";
				echo "EMAIL: ".$result['email']."<br />";
				echo "Name: ".$result['name']."<br />";
				echo "Pass: ".$result['passwd']."<br />";
				echo "IsEmployee: ".$result['is_employee']."<br />";
				echo "IsActive: ".$result['is_active']."<br />";
				echo "ID: ".$result['id']."<br />";
				echo "PWRecoverID: ".$result['pw_recover_id']."<br />";
			}
				
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error (getUserDataFromEmail): ". $e->getMessage();
			return array();
		}
	}
	
	public function checkPassword($passwd,$confirm_passwd){
		
		
		$uppercase = preg_match('@[A-Z]@', $passwd);
		$lowercase = preg_match('@[a-z]@', $passwd);
		$number    = preg_match('@[0-9]@', $passwd);

		
		//TODO display rules for password
		 if(!$uppercase || !$lowercase || !$number || strlen($passwd) < 8) {
		//	echo " password not secure ";
			return false;
		}
		#compare passwords
		else if($passwd!=$confirm_passwd){
		//	echo "You entered different passwords";
			return false;
		}
		
		return true;
	}
	
	public function checkCredentials( $data = array() ) {
		if( isset( $data['email'] ) ) $this->email = stripslashes( strip_tags( $data['email'] ) );
		else return false;
		if( isset( $data['password'] ) ) $this->password = stripslashes( strip_tags( $data['password'] ) );
		else return false;
		
		$success = false;
		
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT passwd, BIN(`is_active` + 0) AS `is_active` FROM users WHERE email = :email LIMIT 1";
				
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "email", $this->email, PDO::PARAM_STR );
			$stmt->execute();
				
			$result = $stmt->fetch();
			if( $result ) {
				if($result['is_active'] == 0){
					$connection = null;
					throw new IsActiveException();
				}
				
				
				 if( crypt($this->password,$result['passwd']) === $result['passwd']){
				    $success = true;
				}
				
			}
				
			$connection = null;
			return $success;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return $success;
		}
	}
	
	public function getInApprovedUsers() {
		if(!$this->isEmployee) return array();
		
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, email,  BIN(`is_employee` + 0) AS `is_employee` FROM users WHERE is_active = 0";
		
			$stmt = $connection->prepare( $sql );
			$stmt->execute();
		
			$result = $stmt->fetchAll();
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function getInApprovedTransactions() {
		if(!$this->isEmployee) return array();
		
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, source, destination, amount, date_time FROM transactions WHERE is_approved = 0";
		
			$stmt = $connection->prepare( $sql );
			$stmt->execute();
		
			$result = $stmt->fetchAll();
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function approveUsers( $data = array() ) {
		if(!$this->isEmployee) return;
		
		/* Make sure POST Data contains array of userIDs */
		if (!isset($data['users']) || count($data['users']) <= 0) {
			throw new InvalidInputException("Submission data invalid. No users found.");
		}
		
		/* Obtain array of user IDs from POST Data */
		$userIDs = $data['users'];
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			
			foreach($userIDs as $userID) {
				
				/* Make sure userID is numeric */
				if ( !is_numeric( $userID ) ) {
					throw new InvalidInputException("User ID invalid.");
				}
				
				/* Make sure balance is set in POST Data */
				if ( !isset( $data['balance'.$userID] ) ) {
					throw new InvalidInputException("Submission data invalid. Balance for user ".$userID." not found.");
				}
				
				$newBalance = $data['balance'.$userID];
				
				/* Make sure balance is numeric */
				if ( !is_numeric( $newBalance ) || ( $newBalance < 0 ) ) {
					throw new InvalidInputException("Balance must be a positive number.");
				}
				
				/* Make sure user exists & is not an active user */
				if (isActiveUser( $userID )) {
					throw new InvalidInputException("This user is already active.");
				}
				
				
				/* Mark User as Active */
				$sql = "UPDATE users set is_active = 1 WHERE id = :id";
				$stmt = $connection->prepare( $sql );
				$stmt->bindValue( "id", $userID, PDO::PARAM_INT );
				$stmt->execute();
				
				/* Set Balance for User */
				$sql = "UPDATE accounts set balance = :balance WHERE user_id = :id";
				$stmt = $connection->prepare( $sql );
				$stmt->bindValue( "id", $userID, PDO::PARAM_INT );
				$stmt->bindValue( "balance", $newBalance, PDO::PARAM_INT );
				$stmt->execute();
				
				$count++;
			}
			
			$connection = null;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function approveTransactions($tansactionIds) {
		if(!$this->isEmployee) return;
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		
			foreach($tansactionIds as $tansactionId) {
				$sql = "UPDATE transactions set is_approved = 1 WHERE id = :id";
			
				$stmt = $connection->prepare( $sql );
				$stmt->bindValue( "id", $tansactionId, PDO::PARAM_INT );
				$stmt->execute();
			}
			
			$connection = null;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function getAllUsers() {
		if(!$this->isEmployee) return array();
		
		$result = array ();
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT id, email,  BIN(`is_active` + 0) AS `is_active` FROM users WHERE is_employee = 0";
		
			$stmt = $connection->prepare( $sql );
			$stmt->execute();
		
			$result = $stmt->fetchAll();
			// var_dump($result);
			$connection = null;
			return $result;
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return array();
		}
	}
	
	public function getBalanceForAccount( $accountNumber ) {
		try{
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "SELECT balance FROM accounts WHERE account_number = :accountNumber";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "accountNumber", $accountNumber, PDO::PARAM_INT );
			$stmt->execute();
		
			$result = $stmt->fetch();
			$connection = null;
			return $result['balance'];
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
			return -1;
		}
	}
	
	public function sendPwRecoveryMail() {
		
		$pwRecoverId = randomDigits(15);
		
		try {
			$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
			$sql = "UPDATE users set pw_recover_id = :pw_recover_id WHERE id = :id";
		
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "pw_recover_id", $pwRecoverId, PDO::PARAM_STR );
			$stmt->bindValue( "id", $this->id, PDO::PARAM_STR );
			$stmt->execute();
		
			$connection = null;
			
			// Send the mail
			
			$message= "Dear User ".$this->email.".\n You requested a new password. Please click on this link to get a new password via email: phase3/pw_recovery?email=$this->email&id=$pwRecoverId";
				
			
			$this->sendMail($this->email, $message);
			
		} catch ( PDOException $e ) {
			echo "<br />Connect Error: ". $e->getMessage();
		}
	}
	
	public function doPwRecovery($id) {
		if(strcmp($this->pwRecoverId, $id) == 0) {
			$newPassword = randomDigits(8);
			
			try {
				$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
				$sql = "UPDATE users set passwd = :password, pw_recover_id='NULL' WHERE id = :id";
		
				$stmt = $connection->prepare( $sql );
				$stmt->bindValue( "password", generateSaltedHash($newPassword), PDO::PARAM_STR );
				$stmt->bindValue( "id", $this->id, PDO::PARAM_STR );
				$stmt->execute();
		
				$connection = null;
			
				// Send the mail
			
				$message= "Dear User ".$this->email.".\n Your new Password is: $newPassword";
				
				$this->sendMail($this->email, $message);
				
				return true;
			
			} catch ( PDOException $e ) {
				echo "<br />Connect Error: ". $e->getMessage();
				return false;
			}
			
		} else {
			return false;
		}
		
	}
	
}
?>
