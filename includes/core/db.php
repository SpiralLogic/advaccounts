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
		protected static $conn = array();
		protected static $current = false;
		protected static $data = array();
		/***
		 * @var string
		 */
		public static $queryString = array();
		/***
		 * @var PDOStatement
		 */
		protected static $prepared = null;

		final function __construct() {
		}

		/***
		 * @static
		 *
		 * @param null	$db
		 * @param array $config
		 *
		 * @return DB_Connection
		 */
		protected static function _get($db = null, $config = array()) {
			static::$prepared = null;
			if ($db === null && static::$current) {
				return static::$current;
			} elseif ($db === null) {
				$config = $config ? : Config::get('db_default');
				$db = $config['name'];
			}
			if (!isset($conn[$db])) {
				static::$conn[$db] = static::$current = DB_Connection::i($db, $config);
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
			try {

				$prepared = static::prepare($sql);
				$prepared->execute();
				static::$prepared = $prepared;
			}
			catch (PDOException $e) {
				$error = '<p>DATABASE ERROR (query): ' . $err_msg . ' <pre>' . var_export($e->getTrace(),
					true) . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
				if (Config::get('debug_sql')) {
					FB::info(static::$queryString);
				}
				Errors::error($error);
			}
			static::$data = array();

			return static::$prepared;
		}

		public static function quote($value, $type = null) {
			return static::_get()->quote($value, $type);
		}

		public static function escape($value, $null = false, $paramaterized = true) {
			$value = trim($value);
			//check for null/unset/empty strings
			$type = null;
			if ((!isset($value)) || (is_null($value)) || ($value === "")) {
				$value = ($null) ? 'NULL' : '';
				$type = PDO::PARAM_NULL;
			} elseif (is_numeric($value)) {
				$value = $value + 0;
				if (is_int($value)) {
					$type = PDO::PARAM_INT;
				}
			} elseif (is_bool($value)) {
				$value = (bool)$value;
				$type = PDO::PARAM_BOOL;
			} elseif (is_string($value)) {
				$value = (string)$value;
				$type = PDO::PARAM_STR;
			}
			if ($paramaterized) {
				static::$data[] = array($value, $type);
				return ' ? ';
			}
			$value = static::quote($value, $type);
			return $value;
		}

		public static function prepare($sql) {
			try {
				static::$prepared = static::_get()->prepare($sql);
				$sql = static::$prepared->queryString;
				if (static::$data && substr_count($sql, '?') > count(static::$data)) {
					throw new DB_Exception('There are more escaped values than there are placeholders!!');
				}
				foreach (static::$data as $k => $v) {
					static::$prepared->bindValue($k + 1, $v[0], $v[1]);
					if (Config::get('debug_sql')) {
						$sql = preg_replace('/\?/i', " '$v[0]' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
				}
				static::$queryString = $sql;
				return static::$prepared;
			}
			catch (PDOException $e) {
				$error = '<p>DATABASE ERROR (prepared): ' . $e->getMessage() . ' <pre>' . $e->getTraceAsString()
				 . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
				if (Config::get('debug_sql')) {
					FB::info(static::$queryString);
				}
				Errors::error($error);
			}
		}

		public static function execute($data) {

			if (!static::$prepared) {
				return false;
			}
			try {
				static::$prepared->execute($data);
				return static::$prepared->fetchAll(PDO::FETCH_ASSOC);
			}
			catch (PDOException $e) {
				$error = '<p>DATABASE ERROR (execute): ' . $e->getMessage() . ' <pre>' . $e->getTraceAsString()
				 . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
				if (Config::get('debug_sql')) {
					FB::info(static::$queryString);
				}
				Errors::error($error);
			}
		}


		public static function insert_id() {
			return static::_get()->lastInsertId();
		}

		/***
		 * @param string $columns,... Database columns to select
		 *
		 * @return DB_Query_Select
		 */
		public static function select($columns = null) {
			$columns = (is_string($columns)) ? func_get_args() : array();
			return new DB_Query_Select($columns, static::_get());
		}

		public static function update($into) {
			return new DB_Query_Update($into, static::_get());
		}

		public static function insert($into) {
			return new DB_Query_Insert($into, static::_get());
		}

		public static function delete($into) {
			return new DB_Query_Delete($into, static::_get());
		}

		/***
		 * @static
		 * @param PDOStatement $result The result of the query or whatever cunt
		 * @return DB_Query_Result This is something
		 */
		public static function fetch($result = null) {

			if ($result !== null) {
				return $result->fetch();
			}
			if (static::$prepared === null) {
				return DB_Query::_fetch(static::_get());
			}
			return static::$prepared->fetch(PDO::FETCH_BOTH);
		}

		public static function fetch_row() {
			return static::$prepared->fetch(PDO::FETCH_NUM);
		}

		public static function fetch_assoc() {
			return static::$prepared->fetch(PDO::FETCH_ASSOC);
		}

		public static function fetch_all() {
			return static::$prepared->fetchAll(PDO::FETCH_ASSOC);
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

		public static function free_result() {
			return (static::$prepared) ? static::$prepared->closeCursor() : false;
		}

		public static function num_rows() {
			return static::$prepared->rowCount();
		}

		public static function num_fields() {
			return static::$prepared->columnCount();
		}

		//DB wrapper functions to change only once for whole application
		public static function num_affected_rows() {
			return static::$prepared->rowCount();
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


		//	Update record activity status.
		//
		public static function update_record_status($id, $status, $table, $key) {
			$sql = "UPDATE " . $table . " SET inactive = " . DB::escape($status) . " WHERE $key=" . DB::escape($id);
			DB::query($sql, "Can't update record status");
		}
	}
