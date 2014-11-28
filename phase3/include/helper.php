<?php
function randomDigits( $length ) {
	$digits = '';

	for($i = 0; $i < $length; $i++) {
		$digits .= mt_rand(0, 9);
	}

	return $digits;
}

function isValidEmail( $email ){
	return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateFormToken() {
	return hash( 'sha256', openssl_random_pseudo_bytes(32) );
	//return md5(md5(uniqid().uniqid().mt_rand()));
}

function validateFormToken( $POSTToken ) {
	if ( isset ($_SESSION['CSRFToken']) && ($_SESSION['CSRFToken'] == $POSTToken )) {
		return true;
	} else {
		return false;
	}
}

function uploadFile($file) {
	// $_FILES["uploadFile"]["name"]
	$target_dir = __DIR__."/../uploads/";
	$target_dir = $target_dir . "file.txt";

	if (move_uploaded_file($file, $target_dir)) {
		echo "The file has been uploaded.";
	} else {
		echo "Sorry, there was an error uploading your file.";
	}
}

function generateNewAccountNumber() {
	$accountNumber = randomDigits(10);
	
	// make sure account is unique
	while ( checkAccountExists( $accountNumber )) {
		$accountNumber = randomDigits(10);
	}
	
	return $accountNumber;
}

function checkAccountExists( $accountNumber ) {
	try {
		$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		// Obtain user_id associated with given account
		$sql = "SELECT user_id FROM accounts WHERE account_number = :account_number";
		$stmt = $connection->prepare( $sql );
		$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
		$stmt->execute();
		
		$result = $stmt->fetch();
		
		// If Account was found result is > 0
		if ( $stmt->rowCount() > 0 ) {
			return true;
		} else {
			return false;
		}
	} catch (PDOException $e) {
		//echo "<br />Connect Error: ". $e->getMessage();
	}
}


function getAccountOwner( $accountNumber ) {
	try {
		$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		// Obtain user_id associated with given account
		$sql = "SELECT user_id FROM accounts WHERE account_number = :account_number";
		$stmt = $connection->prepare( $sql );
		$stmt->bindValue( "account_number", $accountNumber, PDO::PARAM_STR );
		$stmt->execute();

		$result = $stmt->fetch();

		// If Account was found result is > 0
		if ( $stmt->rowCount() > 0 ) {
			$user_id = $result['user_id'];
			
			// Obtain Name associated with given account
			$sql = "SELECT name FROM users WHERE id = :user_id";
			$stmt = $connection->prepare( $sql );
			$stmt->bindValue( "user_id", $user_id, PDO::PARAM_STR );
			$stmt->execute();
			
			$result = $stmt->fetch();
			if ( $stmt->rowCount() > 0 ) {
				return $result['name'];
			}
		}
		
		return "";
	} catch (PDOException $e) {
		//echo "<br />Connect Error: ". $e->getMessage();
	}
}


function checkUserExists( $email ) {
	try {
		$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		
		// Obtain user_id associated with given account
		$sql = "SELECT * FROM users WHERE email = :email";
		$stmt = $connection->prepare( $sql );
		$stmt->bindValue( "email", $email, PDO::PARAM_STR );
		$stmt->execute();
		
		$result = $stmt->fetch();
		
		// Make sure Source Account belongs to this user
		if ( $stmt->rowCount() > 0 ) {
			return true;
		} else {
			return false;
		}
	} catch (PDOException $e) {
		//echo "<br />Connect Error: ". $e->getMessage();
	}
}


function isActiveUser( $userID ) {
	try {
		$connection = new PDO( DB_NAME, DB_USER, DB_PASS );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

		// Obtain user_id associated with given account
		$sql = "SELECT is_active FROM users WHERE id = :id";
		$stmt = $connection->prepare( $sql );
		$stmt->bindValue( "id", $userID, PDO::PARAM_STR );
		$stmt->execute();

		$result = $stmt->fetch();

		// Make sure Source Account belongs to this user
		if ( $stmt->rowCount() > 0 ) {
			if ($result['is_active'] == 1)
				return true;
			else
				return false;
		} else {
			throw new InvalidInputException("No user with that ID.");
		}
	} catch (PDOException $e) {
		echo "<br />Connect Error: ". $e->getMessage();
	}
}


?>
