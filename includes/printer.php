<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 22/10/11
	 * Time: 5:23 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class Printer {

		function write_def($id, $name, $descr, $queue, $host, $port, $timeout) {
			if ($id > 0)
				$sql = "UPDATE printers SET description=" . DBOld::escape($descr)
				 . ",name=" . DBOld::escape($name) . ",queue=" . DBOld::escape($queue)
				 . ",host=" . DBOld::escape($host) . ",port=" . DBOld::escape($port) . ",timeout=" . DBOld::escape($timeout)
				 . " WHERE id=" . DBOld::escape($id);
			else
				$sql = "INSERT INTO printers ("
				 . "name,description,queue,host,port,timeout) "
				 . "VALUES (" . DBOld::escape($name) . "," . DBOld::escape($descr) . ","
				 . DBOld::escape($queue) . "," . DBOld::escape($host) . "," . DBOld::escape($port) . "," . DBOld::escape($timeout) . ")";

			return DBOld::query($sql, "could not write printer definition");
		}

		function get_all() {
			$sql = "SELECT * FROM printers";
			return DBOld::query($sql, "could not get printer definitions");
		}

		function get_printer($id) {
			$sql    = "SELECT * FROM printers WHERE id=" . DBOld::escape($id);
			$result = DBOld::query($sql, "could not get printer definition");
			return DBOld::fetch($result);
		}

		//============================================================================
		// printer profiles functions
		//
		function update_profile($name, $dest) {
			foreach ($dest as $rep => $printer) {
				if ($printer != '' || $rep == '') {
					$sql = "REPLACE INTO print_profiles "
					 . "(profile, report, printer) VALUES ("
					 . DBOld::escape($name) . ","
					 . DBOld::escape($rep) . ","
					 . DBOld::escape($printer) . ")";
				} else {
					$sql = "DELETE FROM print_profiles WHERE ("
					 . "report=" . DBOld::escape($rep)
					 . " AND profile=" . DBOld::escape($name) . ")";
				}
				$result = DBOld::query($sql, "could not update printing profile");
				if (!$result) {
					return false;
				}
			}
			return true;
		}

		//
		//	Get destination for report defined in given printing profile.
		//
		function get_report($profile, $report) {
			$sql = "SELECT printer FROM print_profiles WHERE "
			 . "profile=" . DBOld::escape($profile) . " AND report=";

			$result = DBOld::query($sql . DBOld::escape($report), 'report printer lookup failed');

			if (!$result) return false;
			$ret = DBOld::fetch($result);
			if ($ret === false) {
				$result = DBOld::query($sql . "''", 'default report printer lookup failed');
				if (!$result) return false;

				$ret = DBOld::fetch($result);
				if (!$ret) return false;
			}
			return get_printer($ret['printer']);
		}

		function delete_profile($name) {
			$sql = "DELETE FROM print_profiles WHERE profile=" . DBOld::escape($name);
			return DBOld::query($sql, "could not delete printing profile");
		}

		//
		// Get all report destinations for given profile.
		//
		function get_profile($name) {
			$sql = "SELECT	* FROM print_profiles WHERE profile=" . DBOld::escape($name);
			return DBOld::query($sql, "could not get printing profile");
		}
	}
