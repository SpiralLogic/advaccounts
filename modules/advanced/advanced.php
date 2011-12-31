<?php
	$page_security = 'SA_ADVANCED';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Extensions::add_access();
	// Turn these next two lines on for debugging
	//error_reporting(E_ALL);
	//ini_set("display_errors", "on");
	// Begin the UI
	Page::start("Advanced");
	Page::end();
