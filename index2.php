<?php

	include('includes/session.inc');

	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";

	DB::select()->from('tags');
$results=	DB::fetch()->asObject();
foreach ($results as	$r)
	$result = $r;
	unset ($result->id);
	$result->type = 5;
$result->description = 'sssdf5';
	echo ($result);
echo DB::update('tags')->values($result)->where('id=',2)->exec();


