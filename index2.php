<?php

	include('includes/session.inc');

	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";


$c = new Customer(4689);

$c->name = 'test test';

$c = json_encode($c);

$_POST = (array)json_decode($c);

$n = new Customer($_POST);
var_dump($n);
	$n->save($_POST);
