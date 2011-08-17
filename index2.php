<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";
	DB::select()->from('debtors_master')->where('debtor_no=',5901);

  $test = DB::fetchClass('Customer');
var_dump($test);