<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 29/12/10
	 * Time: 4:41 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB
	{
		/**
		 *
		 */
		const SELECT = 0;
		/**
		 *
		 */
		const INSERT = 1;
		/**
		 *
		 */
		const UPDATE = 2;
		/**
		 *
		 */
		const DELETE = 4;
		/**
		 * @var array
		 */
		protected static $connections = array();
		/**
		 * @var bool
		 */
		protected static $current = false;
		/**
		 * @var array
		 */
		protected static $data = array();
		/***
		 * @var string
		 */
		public static $queryString = array();
		/***
		 * @var PDOStatement
		 */
		protected static $prepared = null;
		protected static $debug = null;
		protected static $nested = false;

		/**
		 *
		 */
		final function __construct() {
		}

		/***
		 * @static
		 *
		 * @param null	$conn
		 * @param array $config
		 *
		 * @return DB_Connection
		 */
		protected static function _get($conn = null, $config = array()) {
			static::$prepared = null;
			if ($conn === null && static::$current) {
				return static::$current;
			} elseif ($conn === null) {
				$config = $config ? : Config::get('db_default');
				$conn = $config['name'];
			}
			if (!isset(static::$connections[$conn])) {
				static::$connections[$conn] = static::$current = DB_Connection::i($conn, $config);
			}
			return static::$current;
		}

		/**
		 * @static
		 *
		 * @param $db
		 *
		 * @return bool
		 * @throws DB_Exception
		 */
		public static function set($db) {
			if (!isset(static::$connections[$db])) {
				throw new DB_Exception('There is no connection: ' . $db);
			}
			static::$current = static::$connections[$db];
			return static::$current;
		}

		/**
		 * @static
		 *
		 * @param			$sql
		 * @param null $err_msg
		 *
		 * @return null|PDOStatement
		 */
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

		/**
		 * @static
		 *
		 * @param			$value
		 * @param null $type
		 *
		 * @return mixed
		 */
		public static function quote($value, $type = null) {
			return static::_get()->quote($value, $type);
		}

		/**
		 * @static
		 *
		 * @param			$value
		 * @param bool $null
		 * @param bool $paramaterized
		 *
		 * @return bool|mixed|string
		 */
		public static function escape($value, $null = false, $paramaterized = true) {
			$value = trim($value);
			//check for null/unset/empty strings
			if ((!isset($value)) || (is_null($value)) || ($value === "")) {
				$value = ($null) ? 'NULL' : '';
				$type = PDO::PARAM_NULL;
			} elseif (is_int($value)) {
				$type = PDO::PARAM_INT;
			} elseif (is_bool($value)) {
				$type = PDO::PARAM_BOOL;
			} elseif (is_string($value)) {
				$type = PDO::PARAM_STR;
			} else {
				$type = FALSE;
			}
			if ($paramaterized) {
				static::$data[] = array($value, $type);
				return ' ? ';
			}
			$value = static::quote($value, $type);
			return $value;
		}

		/**
		 * @static
		 *
		 * @param $sql
		 *
		 * @return bool|null|PDOStatement
		 * @throws DB_Exception
		 */
		public static function prepare($sql, $debug = false) {
			static::$debug = $debug;
			try {
				static::$prepared = static::_get()->prepare($sql);
				$sql = static::$prepared->queryString;
				if (static::$data && substr_count($sql, '?') > count(static::$data)) {
					throw new DB_Exception('There are more escaped values than there are placeholders!!');
				}
				foreach (static::$data as $k => $v) {
					static::$prepared->bindValue($k + 1, $v[0], $v[1]);
					if ($debug || Config::get('debug_sql')) {
						$sql = preg_replace('/\?/i', " '$v[0]' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
				}
				static::$queryString = $sql;
				return static::$prepared;
			}
			catch (PDOException $e) {
				$error = '<p>DATABASE ERROR (prepared): ' . $e->getMessage() . ' <pre>' . $e->getTraceAsString()
				 . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
				if ($debug || Config::get('debug_sql')) {
					FB::info(static::$queryString);
				}
				Errors::error($error);
				return false;
			}
		}

		/**
		 * @static
		 *
		 * @param $data
		 *
		 * @return array|bool
		 */
		public static function execute($data) {
			if (!static::$prepared) {
				return false;
			}
			try {
				static::$prepared->execute($data);
				if (static::$debug) {
					$sql = static::$queryString;
					foreach ($data as $k => $v) {
						$sql = preg_replace('/\?/i', " '$v' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
					FB::info($sql);
				}
				return static::$prepared->fetchAll(PDO::FETCH_ASSOC);
			}
			catch (PDOException $e) {
				$error = '<p>DATABASE ERROR (execute): ' . $e->getMessage() . ' <pre>' . $e->getTraceAsString()
				 . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
				if (Config::get('debug_sql')) {
					FB::info(static::$queryString);
				}
				Errors::error($error);
				return false;
			}
		}

		/**
		 * @static
		 * @return mixed
		 */
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

		/**
		 * @static
		 *
		 * @param $into
		 *
		 * @return DB_Query_Update
		 */
		public static function update($into) {
			return new DB_Query_Update($into, static::_get());
		}

		/**
		 * @static
		 *
		 * @param $into
		 *
		 * @return DB_Query_Insert
		 */
		public static function insert($into) {
			return new DB_Query_Insert($into, static::_get());
		}

		/**
		 * @static
		 *
		 * @param $into
		 *
		 * @return DB_Query_Delete
		 */
		public static function delete($into) {
			return new DB_Query_Delete($into, static::_get());
		}

		/***
		 * @static
		 *
		 * @param PDOStatement $result The result of the query or whatever cunt
		 *
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

		/**
		 * @static
		 * @return mixed
		 */
		public static function fetch_row() {
			return static::$prepared->fetch(PDO::FETCH_NUM);
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function fetch_assoc() {
			return static::$prepared->fetch(PDO::FETCH_ASSOC);
		}

		/**
		 * @static
		 * @return array
		 */
		public static function fetch_all() {
			return static::$prepared->fetchAll(PDO::FETCH_ASSOC);
		}

		/**
		 * @static
		 * @return DB_Connection
		 */
		public static function begin() {
			return static::_get()->begin();
		}

		/**
		 * @static
		 * @return DB_Connection
		 */
		public static function commit() {
			return static::_get()->commit();
		}

		/**
		 * @static
		 * @return DB_Connection
		 */
		public static function cancel() {
			return static::_get()->cancel();
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function error_no() {
			return static::_get()->errorCode();
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function error_msg() {
			$info = static::_get()->errorInfo();
			return $info[2];
		}

		/**
		 * @static
		 *
		 * @param PDO $value
		 *
		 * @return mixed
		 */
		public static function getAttribute(PDO $value) {
			return static::_get()->getAttribute($value);
		}

		/**
		 * @static
		 * @return bool
		 */
		public static function free_result() {
			return (static::$prepared) ? static::$prepared->closeCursor() : false;
		}

		/**
		 * @static
		 * @return int
		 */
		public static function num_rows() {
			return static::$prepared->rowCount();
		}

		/**
		 * @static
		 * @return int
		 */
		public static function num_fields() {
			return static::$prepared->columnCount();
		}

		//DB wrapper functions to change only once for whole application
		/**
		 * @static
		 * @return int
		 */
		public static function num_affected_rows() {
			return static::$prepared->rowCount();
		}

		/**
		 * @static
		 *
		 */
		public static function begin_transaction($nested = false) {
			if (!static::$nested) {
				DB::begin("could not start a transaction");
			}
			if ($nested) {
				static::$nested = true;
			}
		}

		/**
		 * @static
		 *
		 */
		public static function commit_transaction($nested = false) {
			if ($nested) {
				static::$nested = false;
			}
			if (!static::$nested) {
				DB::commit("could not commit a transaction");
			}
		}

		/**
		 * @static
		 *
		 * @return bool
		 */
		public static function cancel_transaction() {
			static::$nested = false;
			DB::cancel("could not commit a transaction");
			return false;
		}

		//	Update record activity status.
		//
		/**
		 * @static
		 *
		 * @param $id
		 * @param $status
		 * @param $table
		 * @param $key
		 *
		 * @return \DB_Query_Result
		 */
		public static function update_record_status($id, $status, $table, $key) {
			$sql = "UPDATE " . $table . " SET inactive = " . DB::escape($status) . " WHERE $key=" . DB::escape($id);
			$result = DB::query($sql, "Can't update record status");
			return DB::fetch($result);
		}

		public static function insert_record_status($id, $status, $table, $key) {
			return DB::insert($table)->value('inactive', $status)->value($key, $id)->exec();
		}
	}
