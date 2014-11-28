<?php
class InvalidInputException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Some Input was invalid: '.$this->getMessage();
		return $errorMsg;
	}
}
?>