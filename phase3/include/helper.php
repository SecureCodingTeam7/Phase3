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

function deleteDir($dirPath) {
    if (! is_dir($dirPath)) {
        throw new InvalidArgumentException("$dirPath must be a directory");
    }
    if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
        $dirPath .= '/';
    }
    $files = glob($dirPath . '*', GLOB_MARK);
    foreach ($files as $file) {
        if (is_dir($file)) {
            self::deleteDir($file);
        } else {
            unlink($file);
        }
    }
    rmdir($dirPath);
}

//TODO adjust jaFilePath
//TODO insert header
	function generateZipArchive($pin) {
		
		$path = tempnam(sys_get_temp_dir(),"");
		mkdir("$path"."_dir");
		$jarFile = __DIR__."/../SCS.jar";
		$jarFileCopy = $path."_dir/scs.jar";
		copy($jarFile,$jarFileCopy);
		
		$txtFile = $path."_dir/YOUR_PIN.txt";
		
		
		file_put_contents($txtFile,"Your PIN for generating transaction codes via SCS: ".$pin);
		
		$zipFile = $path.".zip";
		$zip = new ZipArchive();
		if($zip->open($zipFile,ZipArchive::CREATE)==true){
			if(file_exists($jarFileCopy))
				$zip->addFromString("scs.jar",file_get_contents($jarFileCopy));
				
			if(file_exists($txtFile))
				$zip->addFromString("YOUR_PIN.txt",file_get_contents($txtFile));
			
		//var_dump($zip);
		$zip->close();
		unlink($path);
		deleteDir($path."_dir");

		if(file_exists($path.".zip")) {
			$file_name = basename($path).".zip";
			return $file_name;
			//echo "<a href='download.php?file=".$file_name."'>Download file</a>";
			}
	}
}

function query_time_server ($timeserver, $socket)
{
    $fp = fsockopen($timeserver,$socket,$err,$errstr,5);
        # parameters: server, socket, error code, error text, timeout
    if($fp)
    {
        fputs($fp, "\n");
        $timevalue = fread($fp, 49);
        fclose($fp); # close the connection
    }
    else
    {
        $timevalue = " ";
    }

    $ret = array();
    $ret[] = $timevalue;
    $ret[] = $err;     # error code
    $ret[] = $errstr;  # error text
    return($ret);
} # function query_time_server

	function getUTCTime(){
	
		$timeserver = "ptbtime1.ptb.de";
		$timercvd = query_time_server($timeserver, 37);

	//if no error from query_time_server
		if(!$timercvd[1])
		{
			$timevalue = bin2hex($timercvd[0]);
			$timevalue = abs(HexDec('7fffffff') - HexDec($timevalue) - HexDec('7fffffff'));
			$tmestamp = $timevalue - 2208988800; # convert to UNIX epoch time stamp
			$datum = date("Y-m-d (D) H:i:s",$tmestamp - date("Z",$tmestamp)); /* incl time zone offset */
			$doy = (date("z",$tmestamp)+1);

			return $tmestamp;
		}
		else
		{
			throw new TimeServerException("Unfortunately, the time server $timeserver could not be reached at this time. ");  
		}	
	}

	function pdfEncrypt ($origFile, $password, $destFile){
        
        $pdf =& new FPDI_Protection();
        $pdf->FPDF('P', 'in');
        //Calculate the number of pages from the original document.
        $pagecount = $pdf->setSourceFile($origFile);
        //Copy all pages from the old unprotected pdf in the new one.
        for ($loop = 1; $loop <= $pagecount; $loop++) {
            $tplidx = $pdf->importPage($loop);
            $pdf->addPage();
            $pdf->useTemplate($tplidx);
        }
        //Protect the new pdf file, and allow no printing, copy, etc. and
        //leave only reading allowed.
        $pdf->SetProtection(array('copy'), $password, $password);
        $pdf->Output($destFile, 'F');
        return $destFile;
    }
    
    function createPDF($pdf_file,$trans_codes,$password,$accountNumber) {
		$pdf = new FPDF();

		$pdf -> AddPage('P');
		$pdf -> SetTitle("The Bank Transaction Codes for Customer Name");
		$pdf ->SetFont('Arial','B',16);
		$pdf->SetXY(10,10);
		$pdf->SetFontSize(12);
		$pdf->Write(5,'Dear Customer. Here are your Transaction Codes for the account: '.$accountNumber);
		$pdf->Ln();
		for($i=0;$i<100;$i++){
			if($i%3==0)
				$pdf ->Ln();
			if($i<10)	
				$pdf->Write(5, 'TAN #0'.$i.' :  '.$trans_codes[$i]."    ");
			else
				$pdf->Write(5, 'TAN #'.$i.' :  '.$trans_codes[$i]."    ");
			
		}
		$pdf->Output($pdf_file,"F");
		pdfEncrypt($pdf_file,$password,$pdf_file);
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
