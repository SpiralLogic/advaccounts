<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";

$test =new Customer(4689);
	var_dump($test);