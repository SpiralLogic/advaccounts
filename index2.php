<?php

	include('includes/session.inc');
	ini_set('display_errors','On');
	error_reporting(E_ALL);
	echo 'test';
	echo "<pre>";
	echo	 DB::select()->from('contacts')->where('parent_id=5901')->exec();
	print_r(DB::fetchClass(array(),'Customer'));