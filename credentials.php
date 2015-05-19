<?php
/*
 * @brief Logic to connect to the tist database
 */
$user="tist_user";
$password="juyhnm,ki8";
$database="tist_db";
$host="mysql.the-tist.com";

$db = new mysqli($host, $user, $password, $database);
if($db->connect_errno > 0)
{
	die('Unable to connect to database [' . $db->connect_error . ']');
}
error_reporting(E_ERROR);

?>
