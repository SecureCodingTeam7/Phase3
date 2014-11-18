<?php
include_once(__DIR__."/../class/c_user.php");
include_once(__DIR__."/../include/helper.php");

$user = new User;
$user->getUserDataFromEmail( "user@bank.de" ); 
?>

Email: <?php echo $user->email ?><br />
Password: <?php echo $user->password ?><br />

<?php if ($user->verifyTAN(2787325635,828548392057810, 29)) {
 echo "yes";
} else echo "no";?>