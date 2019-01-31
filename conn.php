<?php
$hostname = "localhost";
$username = "user";
$password = "password";
$database = "ajaxchat";
$port = 3306;
$db = new mysqli($hostname, $username, $password, $database, $port);
$db->autocommit(TRUE);
if($db) ;
else echo "You are not connected to the database";

//for protection from injection attacks
function qs($s){
    return mysql_real_escape_string($s);
}
function qi($s) {
    return (0+@$s);
}
?>