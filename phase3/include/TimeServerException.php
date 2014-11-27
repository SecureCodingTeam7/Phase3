<?php
class TimeServerException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Time Server Exception: '.$this->getMessage();
		return $errorMsg;
	}
}
?>
