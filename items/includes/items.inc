<?php
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Autoloader::add_path('items/includes/classes');
	$_SESSION['App']->selected_application = 'Items';

	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/banking.inc");
	  
