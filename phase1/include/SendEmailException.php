<?php
class SendEmailException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Sending Email failed: '.$this->getMessage();
		return $errorMsg;
	}
}
?> 
