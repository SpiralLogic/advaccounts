<?php
/*
	include('config_db.php');
	include('includes/db.inc');
	ini_set('display_errors', 'on');
	DB::initConnect($db_connections[0]['dbname'], $db_connections[0]['dbuser'], $db_connections[0]['dbpassword']);
	$result = DB::prepare("SELECT * FROM users WHERE real_name LIKE :name");
	$result = $result->execute(array('name'=>'%z%'));
echo "<pre>";
	print_r($result);
echo "</pre>";*/