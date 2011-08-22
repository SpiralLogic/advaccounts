<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";

	DB::select()->from('debtors_master');

	$test = DB::fetch();


	
foreach($test as $t) {

	var_dump($t);
}
	

