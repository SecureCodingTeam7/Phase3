<?php
class TransferException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Transfer Failed: '.$this->getMessage();
		return $errorMsg;
	}
}
?>