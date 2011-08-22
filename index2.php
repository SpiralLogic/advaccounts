<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";

	DB::select()->from('contacts')->where('parent_id=',4689);
			$contacts = DB::fetch()->asClass('Contact')->all();



	var_dump($contacts[1]->id);



