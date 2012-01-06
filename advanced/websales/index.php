<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_OPEN;

	Page::start(_($help_context = "Websales to Accounting"));
	Page::end();
?>