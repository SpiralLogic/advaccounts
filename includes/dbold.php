<?php

	/*     * ********************************************************************
		Copyright (C) FrontAccounting, LLC.
		Released under the terms of the GNU General Public License, GPL,
		as published by the Free Software Foundation, either version 3
		of the License, or (at your option) any later version.
		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		* ********************************************************************* */
	class DBOld
	{
		public static $db = null;

		public static function getInstance()
		{
			if (static::$db === null) {
				static::$db = CurrentUser::instance()->get_db_connection();
			}
			return static::$db;
		}

		public static function query($sql, $err_msg = null)
		{
			$db = static::getInstance();
			if (Config::get('debug_sql')) {
				if (class_exists('FB')) {
					FB::info($sql);
				} else {
					echo "<font face=arial size=2 color=000099><b>SQL..</b></font>";
					echo "<pre>";
					echo $sql;
					echo "</pre>\n";
				}
			}
			$result = mysql_query($sql, $db);
			if (Config::get('debug_query_log')) {
				DBOld::set_info();
				if (Config::get('debug_select_log') || (strstr($sql, 'SELECT') === false)) {
					mysql_query("INSERT INTO sql_trail (`sql`, `result`, `msg`) VALUES(" . DBOld::escape($sql) . "," . ($result ? 1 : 0) . ", " . DBOld::escape($err_msg) . ")", $db);
				}
			}
			if ($err_msg != null || Config::get('debug')) {
				$exit = $err_msg != null;
				(function_exists('xdebug_call_file')) ?
				 Errors::check_db_error('<br>At file ' . xdebug_call_file() . ':' . xdebug_call_line() . ':<br>' . $err_msg, $sql, $exit)
				 : Errors::check_db_error($err_msg, $sql, $exit);
			}
			return $result;
		}

		public static function fetch_row($result)
		{
			return mysql_fetch_row($result);
		}

		public static function fetch_assoc($result)
		{
			return mysql_fetch_assoc($result);
		}

		public static function fetch($result)
		{
			return mysql_fetch_array($result);
		}

		public static function seek(&$result, $record)
		{
			return mysql_data_seek($result, $record);
		}

		public static function free_result($result)
		{
			if ($result) {
				mysql_free_result($result);
			}
		}

		public static function num_rows($result)
		{
			return mysql_num_rows($result);
		}

		public static function num_fields($result)
		{
			return mysql_num_fields($result);
		}

		//DB wrapper functions to change only once for whole application
		public static function escape($value = "", $nullify = false)
		{
			$db = static::getInstance();

			$value = trim($value);
			//reset default if second parameter is skipped
			$nullify = ($nullify === null) ? (false) : ($nullify);
			//check for null/unset/empty strings
			if ((!isset($value)) || (is_null($value)) || ($value === "")) {

				$value = ($nullify) ? ("NULL") : ("''");
			} else {

				if (is_string($value)) {
					//value is a string and should be quoted; determine best method based on available extensions
					if (function_exists('mysql_real_escape_string')) {
						$value = "'" . mysql_real_escape_string($value,$db) . "'";
					} else {
						$value = "'" . mysql_escape_string($value) . "'";
					}
				} elseif (!is_numeric($value)) {
					//value is not a string nor numeric
					throw new DB_Exception("ERROR: incorrect data type send to sql query");
				}
			}

			return $value;
		}

		public static function error_no()
		{
			$db = static::getInstance();
			return mysql_errno($db);
		}

		public static function error_msg($conn)
		{
			return mysql_error($conn);
		}

		public static function insert_id($sqltrail = false)
		{
			$db = static::getInstance();
			if ($sqltrail) {
				return $_SESSION['db_info']['insert_id'];
			}
			return mysql_insert_id($db);
		}

		public static function set_info()
		{
			$db                                   = static::getInstance();
			$_SESSION['db_info']['insert_id']     = mysql_insert_id($db);
			$_SESSION['db_info']['affected_rows'] = mysql_affected_rows($db);
		}

		public static function num_affected_rows()
		{
			$db = static::getInstance();
			return mysql_affected_rows($db);
		}

		public static function begin_transaction()
		{
			DBOld::query("BEGIN", "could not start a transaction");
		}

		public static function commit_transaction()
		{
			DBOld::query("COMMIT", "could not commit a transaction");
		}

		public static function cancel_transaction()
		{
			DBOld::query("ROLLBACK", "could not cancel a transaction");
		}

		//-----------------------------------------------------------------------------
		//	Update record activity status.
		//
		public static function update_record_status($id, $status, $table, $key)
		{
			$sql = "UPDATE " . $table . " SET inactive = " . DBOld::escape($status) . " WHERE $key=" . DBOld::escape($id);
			DBOld::query($sql, "Can't update record status");
		}
	}

?>
