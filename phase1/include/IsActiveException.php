<?php
class IsActiveException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Account is not active! Please wait until somone approved it.';
		return $errorMsg;
	}
}
?>
