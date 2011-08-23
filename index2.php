<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";


DB::select()->from('cust_branch')->where('debtor_no =', 4689);
	$result = DB::fetch()->asClassLate('Branch')->all();

echo	count($result);

	DB::select()->from('debtors_master')->where('debtor_no =', 4689);
	$result = DB::fetch()->asClassLate('Customer')->all();

echo	count($result);

