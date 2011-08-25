<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";
	$result=DB::insert('tags')->values(array('type'=>3,'name'=>'test2','description'=>'testtt','inactive'=>0));
	
	echo $result->exec();