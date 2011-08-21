<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";

DB::select()->from('contacts');

	$test = DB::fetch();

var_dump(current($test));
var_dump(next($test));
var_dump(current($test));var_dump(next($test));var_dump(current($test));