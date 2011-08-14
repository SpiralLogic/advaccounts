<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";
	DB::select()->from('contacts')->where('parent_id =', 5901);
	$test = DB::fetchClass('Contact');
	$test = $test[0];

	$test2 = DB::insert('contacts');
	DB::exec($test);
