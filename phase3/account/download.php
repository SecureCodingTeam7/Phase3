<?php
ob_start();
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
}  else if($_SESSION['user_level']){
		header("Location: ../login.php");
		die();
	}

else {
	/* Session Valid */
	$user = new User();
	$user->getUserDataFromEmail( $_SESSION['user_email'] );
	
	if($user->useScs == "0") {
		header("Location: ../login.php");
	}
	$selectedAccount = "none";
	
	/* Selected Account Detected */
	if ( isset( $_SESSION['selectedAccount'] )) {
		$selectedAccount = $_SESSION['selectedAccount'];
	}
	
	/* File Name Submission */
	if( ( isset( $_POST['downloadSCS'] )) ) { 
		/* Check presence & validity of CSRF Token */
		if (isset( $_POST['CSRFToken']) && validateFormToken($_POST['CSRFToken'])) {
			/* Perform Account Creation */
			$user = new User();
			$user->getUserDataFromEmail( $_SESSION['user_email'] );
			
			$file_name = generateZipArchive( $user->pin );
			$success = true;
		} else {
			$_SESSION['error'] = "CSRF Token Invalid.";
		}
	}
	
	/* If error or possible malicious activity was detected close the session */ 
	if ( ( isset( $_SESSION['error'] ) ) ) {
		header("Location:../logout.php");
		die();
	}
	
	if ($success) {
		/**
		 * Copyright 2012 Armand Niculescu - media-division.com
		 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:
		 * 1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
		 * 2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.
		 * THIS SOFTWARE IS PROVIDED BY THE FREEBSD PROJECT "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE FREEBSD PROJECT OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
		 */
		// get the file request, throw error if nothing supplied
		 
		// hide notices
		@ini_set('error_reporting', E_ALL & ~ E_NOTICE);
		 
		//- turn off compression on the server
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 'Off');
		 
		// sanitize the file request, keep just the name and extension
		// also, replaces the file location with a preset one ('./myfiles/' in this example)
		$file_path  = sys_get_temp_dir()."/". $file_name;
		

		if(!file_exists($file_path)){
			exit("file doesn't exists");
		}
					$type = "application/x-zip-compressed";
					
					ob_clean();   // discard any data in the output buffer (if possible)
					
					header("Pragma: public");
					header("Expires: 0");
					header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
					header("Cache-Control: public", false);
					header("Content-Description: File Transfer");
					header("Content-Type: " . $type);
					header("Accept-Ranges: bytes");
					header("Content-Disposition: attachment; filename=\"" . "scs.zip" . "\";");
					header("Content-Transfer-Encoding: binary");
					ob_clean();   // discard any data in the output buffer (if possible)
					flush();      // flush headers (if possible)
					//header("Content-Length: " . filesize($file_path));
					// Send file for download
					@readfile($file_path);
					ob_end_flush();
					@unlink($file_path);
			}
		}
