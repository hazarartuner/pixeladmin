<?php
$messageId = $_GET["messageId"];
global $ADMIN;
	$ADMIN->MESSAGE->setReadStatus($messageId,"read");
