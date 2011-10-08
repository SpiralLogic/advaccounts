<?php
	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
	Autoload::add_path('items/includes/classes');
	$_SESSION['App']->selected_application = 'Items';

	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/banking.inc");
	  
