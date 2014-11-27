<?php
include_once(__DIR__."/class/c_user.php");

$user = new User();

$accountNumber = 1234567890;
$amount = 100;
$pin = 123456;
$tan = 0000000000;
$user-> verifyGeneratedTAN($accountNumber,$amount,$pin, $tan);

?>

