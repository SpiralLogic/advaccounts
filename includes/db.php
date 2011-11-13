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
		const SELECT = 0;
		const INSERT = 1;
		const UPDATE = 2;
		const DELETE = 4;
		protected static $conn = array();
		protected static $current = false;
		protected static $data = array();
		protected static $prepared = null;

		final function __construct()
		{
		}

		protected static function _get($db = null, $config = array())
		{
			if ($db === null && static::$current) {
				return static::$current;
			} elseif ($db === null) {
				$config = $config ? : Config::get('db_default');
				$db = $config['name'];
			}
			if (!isset($conn[$db])) {
				static::$conn[$db] = static::$current = DB_Connection::instance($db, $config);
			}
			return static::$current;
		}

		public static function set($db)
		{
			if (!isset(static::$conn[$db])) {
				throw new DB_Exception('There is no connection: ' . $db);
			}
			static::$current = static::$conn[$db];
			return static::$current;
		}

		public static function query($sql, $err_msg = null)
		{
			try {
				$prepared = static::prepare($sql);
				$prepared->execute();
				static::$data = array();
				static::$prepared = $prepared;
				return static::$prepared;
			} catch (PDOException $e) {
				$error = '<p>DATABASE ERROR: ' . $err_msg . ' <pre>' . var_export($e->getTrace(), true) . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
				//Errors::error($error);
			}
		}

		public static function quote($value, $type = null)
		{
			return static::_get()->quote($value, $type);
		}

		public static function escape($value, $null = false, $paramaterized = true)
		{
			$value = trim($value);
			//check for null/unset/empty strings
			if ((!isset($value)) || (is_null($value)) || ($value === "")) {
				$value = ($null) ? 'NULL' : '';
				$type = PDO::PARAM_NULL;
			} elseif (is_numeric($value) && is_int($value)) {
				$value = (int)$value;
				$type = PDO::PARAM_INT;
			} elseif (is_float($value)) {
				$value = (double)$value;
				$type = null;
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

		public static function prepare($sql)
		{
			static::$prepared = static::_get()->prepare($sql);
			foreach (static::$data as $k => $v) {
				static::$prepared->bindValue($k + 1, $v[0], $v[1]);
				if (Config::get('debug_sql')) {
					$sql = preg_replace('/\?/i', " '$v[0]' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
				}
			}
			if (Config::get('debug_sql')) {
				FB::info($sql);
			}
			return static::$prepared;
			;
		}

		public static function execute($data)
		{
			if (static::$prepared) {
				if (Config::get('debug_sql')) {
					$sql = static::$prepared->queryString;
					foreach ($data as $k => $v) {
						$sql = preg_replace('/\?/i', " '$v' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
					FB::info($sql);
				}
				static::$prepared->execute($data);
				return static::$prepared->fetchAll(PDO::FETCH_ASSOC);
			}
		}

		public static function insert_id()
		{
			return static::_get()->lastInsertId();
		}

		public static function select()
		{
			$columns = func_get_args();
			return new DB_Query_Select($columns, static::_get());
		}

		public static function update($into)
		{
			return new DB_Query_Update($into, static::_get());
		}

		public static function insert($into)
		{
			return new DB_Query_Insert($into, static::_get());
		}

		public static function delete($into)
		{
			return new DB_Query_Delete($into, static::_get());
		}

		public static function fetch($result = null)
		{
			if ($result === null) {
				return DB_Query::_fetch(static::_get());
			} else {
				return $result->fetch(PDO::FETCH_BOTH);
			}
		}

		public static function fetch_row($result)
		{
			return static::$prepared->fetch(PDO::FETCH_NUM);
		}

		public static function fetch_assoc($result)
		{
			return $result->fetch(PDO::FETCH_ASSOC);
		}

		public static function fetch_all($result)
		{
			return $result->fetchAll(PDO::FETCH_ASSOC);
		}

		public static function begin()
		{
			return static::_get()->begin();
		}

		public static function commit()
		{
			return static::_get()->commit();
		}

		public static function cancel()
		{
			return static::_get()->cancel();
		}

		public static function error_no()
		{
			return static::_get()->errorCode();
		}

		public static function error_msg()
		{
			$info = static::_get()->errorInfo();
			return $info[2];
		}

		public static function getAttribute(PDO $value)
		{
			return static::_get()->getAttribute($value);
		}

		public static function free_result($result)
		{
			if ($result) {
				return $result->closeCursor();
			}
		}

		public static function num_rows($result)
		{
			return static::$prepared->rowCount();
		}

		public static function num_fields($result)
		{
			return $result->columnCount();
		}

		//DB wrapper functions to change only once for whole application
		public static function num_affected_rows($result)
		{
			return $result->rowCount();
		}

		public static function begin_transaction()
		{
			DB::begin("could not start a transaction");
		}

		public static function commit_transaction()
		{
			DB::commit("could not commit a transaction");
		}

		public static function cancel_transaction()
		{
			DB::cancel("could not commit a transaction");
		}

		//-----------------------------------------------------------------------------
		//	Update record activity status.
		//
		public static function update_record_status($id, $status, $table, $key)
		{
			$sql = "UPDATE " . $table . " SET inactive = " . DB::escape($status) . " WHERE $key=" . DB::escape($id);
			DB::query($sql, "Can't update record status");
		}
	}
