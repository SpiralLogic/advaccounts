<?php

	include('includes/session.inc');
	ini_set('display_errors', 'On');
	error_reporting(E_ALL);
	echo "<pre>";

		DB::select()
			 ->from('cust_branch')
			 ->where('debtor_no=', 4689)
			 ->where('branch_ref !=', 'accounts')
			 ->where('disable_trans=', 0);
			$branch = new Branch();
			$results = DB::fetch()->all();
			echo count($results);

///var_dump($branch);

0401351605