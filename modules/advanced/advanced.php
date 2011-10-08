<?php
	$page_security = 'SA_ADVANCED';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	add_access_extensions();

	// Turn these next two lines on for debugging
	//error_reporting(E_ALL);
	//ini_set("display_errors", "on");

	//--------------------------------------------------------------------------------------------------

	// Begin the UI

	page("Advanced");

	end_page();
