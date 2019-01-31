<?php
require_once("conn.php");
$id = $_GET['id'];
//we need to also make sure the session_id for the file belongs to them
//we need  to fix it when a ' or " is in the file name
$selectBlob = "SELECT chat_blob_file, chat_blob_name FROM chat WHERE chat_id = ?";
$statement = $db->prepare($selectBlob);
$statement->bind_param('i', $id);
$statement->execute();
$statement->store_result(); //we need to do this before getting the number of rows
$returnNum = $statement->num_rows;
if($returnNum > 0){
	$statement->bind_result($chat_blob_file, $chat_blob_name);
	while($statement->fetch()){
    header('Content-Disposition: attachment; filename="'.$chat_blob_name.'"');
    header("Content-Type: application/octet-stream;");
    echo $chat_blob_file;
  }
}
?>