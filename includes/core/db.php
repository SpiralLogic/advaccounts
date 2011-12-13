<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 29/12/10
	 * Time: 4:41 AM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB {
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
			if ($conn === null ) {
				$config = $config ? : Config::get('db_default');
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
				$error = '<p>DATABASE ERROR (execute): ' . $e->getMessage() . '</pre></p><p><pre>' . $e->errorInfo[2] . '</pre></p>';
				Errors::show_db_error($error);
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
				}
				return static::$prepared;
			}
			catch (PDOException $e) {
				foreach (static::$data as $k => $v) {
					if ($debug || Config::get('debug_sql')) {
						$sql = preg_replace('/\?/i', " '$v[0]' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
					}
				}
				static::$queryString = $sql;
				$error = '<p>DATABASE ERROR (prepared): ' . $e->getMessage() . '</p><p><pre>' . $e->errorInfo[2] . '</pre></p>';
				Errors::show_db_error($error, $sql);
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
				}
				return static::$prepared->fetchAll(PDO::FETCH_ASSOC);
			}
			catch (PDOException $e) {
				$error = '<p>DATABASE ERROR (execute): ' . $e->getMessage() . '</p><p><pre>' . $e->errorInfo[2] . '</pre></p>';
				Errors::show_db_error($error, static::$queryString);
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
		public static function begin($nested = false) {
			return static::begin_transaction($nested);
		}

		/**
		 * @static
		 * @return DB_Connection
		 */
		public static function commit($nested = false) {
			return static::commit_transaction($nested);
		}

		/**
		 * @static
		 * @return DB_Connection
		 */
		public static function cancel($nested = false) {
			return static::cancel_transaction($nested);
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
			return $info;
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
				static::_get()->begin("could not start a transaction");
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
				static::_get()->commit("could not commit a transaction");
			}
		}

		/**
		 * @static
		 *
		 */
		public static function cancel_transaction() {
			static::$nested = false;
			static::_get()->cancel("could not commit a transaction");
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
		 */
		public static function update_record_status($id, $status, $table, $key) {
			$reuslt = DB::update($table)->value('inactive', $status)->where($key . '=', $id)->exec();
			if (!$reuslt) {
				static::insert_record_status($id, $status, $table, $key);
			}
			return $reuslt;
		}

		public static function insert_record_status($id, $status, $table, $key) {

			$reuslt = DB::insert($table)->values(array('inactive' => $status, $key => $id))->exec();

			return $reuslt;
		}
	}

	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 21/08/11
	 * Time: 11:15 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB_Connection {
		/**
		 * @var array
		 */
		protected static $instances = array();

		/**
		 * @static
		 *
		 * @param $name
		 * @param $config
		 *
		 * @return mixed
		 */

		/**
		 * @var
		 */
		protected $name;
		/**
		 * @var
		 */
		protected $user;
		/**
		 * @var
		 */
		protected $pass;
		/**
		 * @var
		 */
		protected $host;
		/**
		 * @var
		 */
		protected $port;
		/**
		 * @var PDO
		 */
		protected $conn;
		/**
		 * @var bool
		 */
		protected $intransaction = false;

		/**
		 * @param			 $name
		 * @param array $config
		 */
		protected function __construct($name, array $config) {
			$this->name = $name;
			$this->user = $config['user'];
			$this->pass = $config['pass'];
			$this->host = $config['host'];
			$this->port = $config['port'];
			$this->debug = false;
			$this->_connect();
			static::$instances[$name] = $this;
		}

		/**
		 * @return mixed
		 */
		public function name() {
			return $this->name;
		}

		/**
		 * @param $sql
		 *
		 * @return PDOStatement
		 *
		 */
		public function prepare($sql) {
			try {
				return $this->conn->prepare($sql);
			}
			catch (PDOException $e) {
				$this->_error($e);
			}
		}

		/***
		 * @param			$sql
		 * @param			$type
		 * @param null $data
		 *
		 * @return DB_Query_Result|int
		 */
		public function exec($sql, $type, $data = null) {
			try {
				$prepared = $this->prepare($sql);
				switch ($type) {
					case DB::SELECT:
						return new DB_Query_Result($prepared, $data);
					case DB::INSERT:
						$prepared->execute($data);
						return $this->conn->lastInsertId();
					case DB::UPDATE or DB::DELETE:
						$prepared->execute($data);
						return true;
				}
			}
			catch (PDOException $e) {
				$this->_error($e);
			}
			return false;
		}

		/**
		 * @return DB_Connection
		 */
		public function begin() {
			if ($this->intransaction == true) {
				return $this;
			}
			try {
				$this->conn->beginTransaction();
			}
			catch (PDOException $e) {
				static::_error($e);
			}
			$this->intransaction = true;
			return $this;
		}

		/**
		 * @return mixed
		 */
		public function lastInsertId() {
			return $this->conn->lastInsertId();
		}

		/**
		 * @return DB_Connection
		 */
		public function commit() {
			if ($this->intransaction == false) {
				return $this;
			}
			try {
				$this->conn->commit();
			}
			catch (PDOException $e) {
				static::_error($e);
			}
			$this->intransaction = false;
			return $this;
		}

		/**
		 * @return DB_Connection
		 */
		public function cancel() {
			if ($this->intransaction == false) {
				return $this;
			}
			try {
				$this->conn->rollBack();
			}
			catch (PDOException $e) {
				static::_error($e);
			}
			$this->intransaction = false;
			return $this;
		}

		/**
		 * @param		 $sql
		 * @param int $fetchas
		 *
		 * @return bool
		 */
		public function query($sql, $fetchas = PDO::FETCH_OBJ) {
			try {
				$query = $this->conn->prepare($sql);
				if ($fetchas == false) {
					return $query->execute();
				}
				$results = $query->fetchAll($fetchas);
			}
			catch (PDOException $e) {
				return static::_error($e);
			}
			return $results;
		}

		/**
		 * @param $value
		 *
		 * @return mixed
		 */
		public function quote($value) {
			return $this->conn->quote($value);
		}

		/**
		 *
		 */
		protected function _connect() {
			try {
				$this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->name, $this->user, $this->pass, array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch (PDOException $e) {
				return $this->_error($e, true);
			}
		}

		/**
		 * @return mixed
		 */
		public function errorCode() {
			return $this->conn->errorCode();
		}

		/**
		 * @return mixed
		 */
		public function errorInfo() {
			return $this->conn->errorInfo();
		}

		/**
		 * @param PDO|int $value
		 *
		 * @return mixed
		 */
		public function getAttribute(PDO $value) {
			return $this->conn->getAttribute($value);
		}

		/**
		 * @param PDOException $e
		 * @param bool				 $exit
		 *
		 * @return bool
		 * @throws DB_Exception
		 */
		protected function _error(PDOException $e, $exit = false) {
			if (Config::get('debug_sql')) {
				$error = '<p>DATABASE ERROR: <pre>' . '</pre></p><p><pre></pre></p>';
			} else {
				$error = $e->errorInfo;
				$error = (!isset($error[2])) ? $e->getMessage() : $error[2];
			}
			if ($this->conn->inTransaction()) {
				$this->conn->rollBack();
				$this->intransaction = false;
			}
			if ($exit) {
				throw new DB_Exception($error);
			}
			Errors::show_db_error($error);
		}
	}
