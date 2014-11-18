<?php
include_once("conf.php");

// connect db
try 
{
	$connection = new PDO(DB_NAME, DB_USER, DB_PASS);
	} catch (PDOException $e) {
		//echo "<br />Connect Error: ". $e->getMessage();
	}
?>
