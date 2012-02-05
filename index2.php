<?php
	include 'bootstrap.php';

DB::connect(Config::get('db.jobsboard'));
	DB::change_connection('jobsboard');
	try{
$result = DB::query('UPDATE Job_List SET priority_changed = NOW() , Main_Employee_Responsible = previous_user WHERE
Priority_Level<5 AND priority_changed < (NOW() - INTERVAL 3 DAY) AND Main_Employee_Responsible<>previous_user AND priority_changed>0');
	} catch(Exception $e) {
	var_dump( DB::$queryString);
	}
	try{
$result = DB::query('UPDATE Job_List SET has_worked_change = NOW() , Can_work_be_done_today	 = 0 WHERE
Priority_Level<5 AND has_worked_change < (NOW() - INTERVAL 3 DAY) AND Can_work_be_done_today=-1 AND has_worked_change>0');
	} catch(Exception $e) {
	var_dump( DB::$queryString);
	}
