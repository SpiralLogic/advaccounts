<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 3/02/12
	 * Time: 4:52 PM
	 * To change this template use File | Settings | File Templates.
	 */
	namespace Modules;
	class Jobsboard
	{
		function __construct() {
			echo 'test';
		}
	static	function tasks() {
			\DB::connect(\Config::get('db.jobsboard'));
			\DB::change_connection('jobsboard');
			$result = false;
			try {
				\DB::query('UPDATE Job_List SET priority_changed = NOW() , Main_Employee_Responsible = previous_user WHERE
			Priority_Level<5 AND priority_changed < (NOW() - INTERVAL 3 DAY) AND Main_Employee_Responsible<>previous_user AND priority_changed>0');
				$result = \DB::num_rows();
			}
			catch (\Exception $e) {
			}
			if ($result) {
				\Event::notice($result .' Jobs were returned to their previous responslble person.');
			}
			$result = false;
			try {
				\DB::query('UPDATE Job_List SET has_worked_change = NOW() , Can_work_be_done_today	 = 0 WHERE
			Priority_Level<5 AND has_worked_change < (NOW() - INTERVAL 3 DAY) AND Can_work_be_done_today=-1 AND has_worked_change>0');
				$result = \DB::num_rows();
			}
			catch (\Exception $e) {
			}
			if ($result) {
				\Event::notice($result .' Jobs were changed back to having "work can be done" due to inactivity.');
			}
			\DB::change_connection();
		}
	}
