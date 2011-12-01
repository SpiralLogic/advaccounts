<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/10/11
	 * Time: 5:23 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Printer
	{
		public static function write_def($id, $name, $descr, $queue, $host, $port, $timeout)
			{
				if ($id > 0) {
					$sql = "UPDATE printers SET description=" . DB::escape($descr) . ",name=" . DB::escape($name) . ",queue=" . DB::escape($queue) . ",host=" . DB::escape($host) . ",port=" . DB::escape($port) . ",timeout=" . DB::escape($timeout) . " WHERE id=" . DB::escape($id);
				} else {
					$sql = "INSERT INTO printers (" . "name,description,queue,host,port,timeout) " . "VALUES (" . DB::escape($name) . "," . DB::escape($descr) . "," . DB::escape($queue) . "," . DB::escape($host) . "," . DB::escape($port) . "," . DB::escape($timeout) . ")";
				}
				return DB::query($sql, "could not write printer definition");
			}

		public static function get_all()
			{
				$sql = "SELECT * FROM printers";
				return DB::query($sql, "could not get printer definitions");
			}

		public static function get($id)
			{
				$sql = "SELECT * FROM printers WHERE id=" . DB::escape($id);
				$result = DB::query($sql, "could not get printer definition");
				return DB::fetch($result);
			}

		//============================================================================
		// printer profiles public static functions
		//
		public static function update_profile($name, $dest)
			{
				foreach ($dest as $rep => $printer) {
					if ($printer != '' || $rep == '') {
						$sql = "REPLACE INTO print_profiles " . "(profile, report, printer) VALUES (" . DB::escape($name) . "," . DB::escape($rep) . "," . DB::escape($printer) . ")";
					} else {
						$sql = "DELETE FROM print_profiles WHERE (" . "report=" . DB::escape($rep) . " AND profile=" . DB::escape($name) . ")";
					}
					$result = DB::query($sql, "could not update printing profile");
					if (!$result) {
						return false;
					}
				}
				return true;
			}

		//
		//	Get destination for report defined in given printing profile.
		//
		public static function get_report($profile, $report)
			{
				$sql = "SELECT printer FROM print_profiles WHERE  profile=" . DB::escape($profile, false,
					false) . " AND report=" . DB::escape($report, false, false);
				$result = DB::query($sql, 'report printer lookup failed');
				if (!$result) {
					return false;
				}
				$ret = DB::fetch($result);
				if ($ret === false) {
					$result = DB::query($sql . "''", 'default report printer lookup failed');
					if (!$result) {
						return false;
					}
					$ret = DB::fetch($result);
					if (!$ret) {
						return false;
					}
				}
				return static::get($ret['printer']);
			}

		public static function delete_profile($name)
			{
				$sql = "DELETE FROM print_profiles WHERE profile=" . DB::escape($name);
				return DB::query($sql, "could not delete printing profile");
			}

		//
		// Get all report destinations for given profile.
		//
		public static function get_profile($name)
			{
				$sql = "SELECT	* FROM print_profiles WHERE profile=" . DB::escape($name);
				return DB::query($sql, "could not get printing profile");
			}
	}