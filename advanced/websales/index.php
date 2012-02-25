<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Page::start(_($help_context = "Websales to Accounting"), SA_OPEN);

	$test = new \Modules\Volusion();
	$test->doWebsales();
	Page::end();
?>
