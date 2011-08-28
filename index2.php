<?php

	include('includes/session.inc');

	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";


echo DB::delete('tags')->where('id=',2)->exec();


