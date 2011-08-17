<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";
$terms = 'test';
	DB::select(array('debtor_no as id',array('name as label',true),'name as value'))->from('debtors_master')->where('name LIKE ',"$terms%")->limit(20)
			->union()->select(array('debtor_no as id','name as label','name as value'))->from('debtors_master')->where('debtor_ref LIKE',"%$terms%")
			->or_where('name LIKE',"%$terms%")->or_where('debtor_no LIKE',"%$terms%")->limit(20)->union();
	$test = DB::fetchAll();
	 

	var_dump($test);