<?php 
global $ADMIN;
if($_GET["admin_action"] == "deleteMessage")
{
	if($ADMIN->MESSAGE->deleteMessage($_GET["messageId"]))
		header("Location:$currentpage");
	else
		postMessage("\"Mesaj\" silinemedi!",true);
}
$msgList = $ADMIN->MESSAGE->listMessages();