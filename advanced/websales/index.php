<?php

	$page_security = 'SA_OPEN';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Websales to Accounting"), Input::request('frame'));
	Page::end(true, true);
?>