<?php




	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";
	$result = Price::getPrices(26382,Price::SALE);
	$result[0]->price = 10;
	$result[0]->save();
	$result = Price::getPrices(26382,Price::SALE);
	var_dump($result);
