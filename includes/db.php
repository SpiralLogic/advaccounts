<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 29/12/10
	 * Time: 4:41 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB {
		const SELECT = 0;
		const INSERT = 1;
		const UPDATE = 2;
		const DELETE = 4;
		protected static $_prepared = array();
		protected static $conn = array();
		protected static $current = false;
		protected static $data = array();
		protected static $prepared = null;

		final function __construct() {
		}

		 static function _get($db = null, $config = array()) {
			if ($db === null && static::$current) {
				return static::$current;
			}
			elseif ($db === null) {
				$config = $config ? : Config::get('db_default');
				$db = $config['name'];
			}
			if (!isset($conn[$db])) {
				static::$conn[$db] = static::$current = DB_Connection::instance($db, $config);
			}
			return static::$current;
		}

		public static function set($db) {
			if (!isset(static::$conn[$db])) {
				throw new DB_Exception('There is no connection: ' . $db);
			}
			static::$current = static::$conn[$db];
			return static::$current;
		}

		public static function query($sql, $err_msg = null) {
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
			try {

				static::$prepared = static::prepare($sql);
				static::$prepared->execute();
				static::$data = array();
				return static::$prepared;
			}
			catch (PDOException $e) {
				$error = '<p>DATABASE ERROR: <pre>' . var_export($e->getTrace(), true) . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
				Errors::error($error);
			}
		}

		public static function quote($value, $type = null) {
			return static::_get()->quote($value, $type);
		}

		public static function escape($value, $null = false, $paramaterized = true) {

			$value = trim($value);
			//check for null/unset/empty strings
			if ((!isset($value)) || (is_null($value)) || ($value === "")) {
				$value = ($null) ? 'NULL' : '';
				$type = PDO::PARAM_NULL;
			} elseif (is_numeric($value) || is_int($value)) {
				$value = (int)$value;
				$type = PDO::PARAM_INT;
			} elseif (is_bool($value)) {
				$value = (bool)$value;
				$type = PDO::PARAM_BOOL;
			} elseif (is_string($value)) {
				$value = (string)$value;
				$type = PDO::PARAM_STR;
			} else {
				$type = null;
			}
			if ($paramaterized) {
				static::$data[] = array($value, $type);
				return ' ? ';
			}
			$value = static::quote($value, $type);
			return $value;
		}

		public static function prepare($sql) {
			$prepared = static::_get()->prepare($sql);
			foreach (static::$data as $k => $v) {
				$prepared->bindValue($k + 1, $v[0], $v[1]);
			}
			static::$prepared = $prepared;
			return $prepared;
		}

		public static function execute($data) {
			if (static::$_prepared) {
				if (Config::get('debug_sql')) {
					$sql = static::$_prepared[static::$current->name()]->queryString;
					foreach ($data as $k => $v) {
						$sql = preg_replace('/\?/i', " '$v' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
					FB::info($sql);
				}
				static::$_prepared[static::$current->name()]->execute($data);
				return static::$_prepared[static::$current->name()]->fetchAll(PDO::FETCH_ASSOC);
			}
		}

		 	public static function insert_id() {
			return static::_get()->lastInsertId();
		}

		public static function select() {
			$columns = func_get_args();
			return new DB_Select($columns, static::_get());
		}

		public static function update($into) {
			return new DB_Update($into, static::_get());
		}

		public static function insert($into) {
			return new DB_Insert($into, static::_get());
		}

		public static function delete($into) {
			return new DB_Delete($into, static::_get());
		}

		public static function fetch($result = null) {
			if ($result === null) {
				return DB_Query::_fetch(static::_get());
			} else {
				return $result->fetch(PDO::FETCH_BOTH);
			}
		}

		public static function fetch_row($result) {
			return $result->fetch(PDO::FETCH_NUM);
		}

		public static function fetch_assoc($result) {
			return $result->fetch(PDO::FETCH_ASSOC);
		}

		public static function begin() {
			return static::_get()->begin();
		}

		public static function commit() {
			return static::_get()->commit();
		}

		public static function cancel() {
			return static::_get()->cancel();
		}

		public static function error_no() {
			return static::_get()->errorCode();
		}

		public static function error_msg() {
			$info = static::_get()->errorInfo();
			return $info[2];
		}

		public static function getAttribute(PDO $value) {
			return static::_get()->getAttribute($value);
		}

		public static function free_result($result) {
			if ($result) {
				return $result->closeCursor();
			}
		}

		public static function num_rows($result) {

			return $result->rowCount();
		}

		public static function num_fields($result) {
			return $result->columnCount();
		}

		//DB wrapper functions to change only once for whole application

		public static function num_affected_rows($results) {

			return $results->rowCount();
		}

		public static function begin_transaction() {
			DB::begin("could not start a transaction");
		}

		public static function commit_transaction() {
			DB::commit("could not commit a transaction");
		}

		public static function cancel_transaction() {
			DB::cancel("could not commit a transaction");
		}

		//-----------------------------------------------------------------------------
		//	Update record activity status.
		//
		public static function update_record_status($id, $status, $table, $key) {
			$sql = "UPDATE " . $table . " SET inactive = " . DB::escape($status) . " WHERE $key=" . DB::escape($id);
			DB::query($sql, "Can't update record status");
		}
	}
