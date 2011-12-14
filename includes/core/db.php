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
		/**
		 * @var null
		 */
		protected static $debug = null;
		/**
		 * @var bool
		 */
		protected static $nested = false;
		/**
		 * @var DB_Query
		 */
		protected static $query = false;
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

		protected $intransaction = false;


		/***
		 * @var PDO
		 */
		protected $conn;
		/**
		 * @var DB
		 */
		protected static $i = null;

		/***
		 * @static
		 *
		 * @param array $config
		 *
		 * @internal PDO $conn
		 * @return DB
		 */
		protected static function i($config = array()) {
			if (static::$i === null) {
				$config = $config ? : Config::get('db_default');
				static::$i = new static($config);
			}
			return static::$i;
		}

		/**
		 * @param $config
		 */
		protected function __construct($config) {
			$this->name = $config['name'];
			$this->user = $config['user'];
			$this->pass = $config['pass'];
			$this->host = $config['host'];
			$this->port = $config['port'];
			static::$debug = false;
			$this->_connect();
		}

		/**
		 *
		 * @return bool
		 */
		protected function _connect() {
			try {
				$this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->name, $this->user, $this->pass, array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				return true;
			}
			catch (PDOException $e) {
				return $this->_error($e, true);
			}
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
			static::$prepared = null;
			try {
				static::$prepared = static::i()->_prepare($sql);
				static::$prepared->execute();
			}
			catch (PDOException $e) {
				static::i()->_error($e, $err_msg);
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
			return static::i()->conn->quote($value, $type);
		}

		/**
		 * @static
		 *
		 * @param			$value
		 * @param bool $null
		 * @internal param bool $paramaterized
		 *
		 * @return bool|mixed|string
		 */
		public static function escape($value, $null = false) {
			$value = trim($value);
			//check for null/unset/empty strings
			if (!isset($value) || is_null($value) || $value === "") {
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
			static::$data[] = array($value, $type);
			return ' ? ';
		}

		/**
		 * @static
		 *
		 * @param $sql
		 *
		 * @param bool $debug
		 * @return bool|PDOStatement
		 * @throws DB_Exception
		 */
		protected function _prepare($sql, $debug = false) {
			static::$debug = $debug;
			try {
				$prepared = $this->conn->prepare($sql);
				$sql = $prepared->queryString;
				if (static::$data && substr_count($sql, '?') > count(static::$data)) {
					throw new DB_Exception('There are more escaped values than there are placeholders!!');
				}
				foreach (static::$data as $k => $v) {
					$prepared->bindValue($k + 1, $v[0], $v[1]);
				}
				return $prepared;
			}
			catch (PDOException $e) {
				$this->_error($e);
			}
			return false;
		}

		/**
		 * @static
		 * @param $sql
		 * @param bool $debug
		 * @return null|PDOStatement
		 */
		public static function prepare($sql, $debug = false) {
			static::$prepared = static::i()->_prepare($sql, $debug);
			return static::$prepared;
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
				return static::$prepared->fetchAll(PDO::FETCH_ASSOC);
			}
			catch (PDOException $e) {
				return static::i()->_error($e);
			}
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function insert_id() {
			return static::i()->conn->lastInsertId();
		}

		/***
		 * @param string $columns,... Database columns to select
		 *
		 * @return DB_Query_Select
		 */
		public static function select($columns = null) {
			static::$prepared = null;
			$columns = (is_string($columns)) ? func_get_args() : array();
			static::$query = new DB_Query_Select($columns, static::i());
			return static::$query;
		}

		/**
		 * @static
		 *
		 * @param $into
		 *
		 * @return DB_Query_Update
		 */
		public static function update($into) {
			static::$prepared = null;
			static::$query = new DB_Query_Update($into, static::i());
			return static::$query;
		}

		/**
		 * @static
		 *
		 * @param $into
		 *
		 * @return DB_Query_Insert
		 */
		public static function insert($into) {
			static::$prepared = null;
			static::$query = new DB_Query_Insert($into, static::i());
			return static::$query;
		}

		/**
		 * @static
		 *
		 * @param $into
		 *
		 * @return DB_Query_Delete
		 */
		public static function delete($into) {
			static::$prepared = null;
			static::$query = new DB_Query_Delete($into, static::i());
			return static::$query;
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
				return static::$query->fetch();
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
		 * @return mixed
		 */
		public static function error_no() {
			$info = static::errorInfo();
			return $info[1];
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function errorInfo() {
			if (static::$prepared) return static::$prepared->errorInfo();
			return static::i()->conn->errorInfo();
		}

		/**
		 * @static
		 * @return mixed
		 */
		public static function error_msg() {
			$info = static::errorInfo();
			return $info[2];
		}

		/**
		 * @static
		 *
		 * @param int|PDO $value
		 *
		 * @return mixed
		 */
		public static function getAttribute(PDO $value) {
			return static::i()->conn->getAttribute($value);
		}

		/**
		 * @static
		 * @return bool
		 */
		public static function free_result() {
			$result = (static::$prepared) ? static::$prepared->closeCursor() : false;
			static::$prepared = null;
			return $result;
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

		/**
		 * @static
		 *
		 */
		public static function begin() {
			if (!static::i()->conn->inTransaction() && !static::i()->intransaction) {
				try {
					static::i()->conn->beginTransaction();
					static::i()->intransaction = true;
				}
				catch (PDOException $e) {
					static::i()->_error($e);
				}
			}
		}

		/**
		 * @static
		 *
		 */
		public static function commit() {
			if (static::i()->conn->inTransaction() || static::i()->intransaction) {
				static::i()->intransaction = false;
				try {
					static::i()->conn->commit();
				}
				catch (PDOException $e) {
					static::i()->_error($e);
				}
			}
		}

		/**
		 * @static
		 *
		 */
		public static function cancel() {
			if (static::i()->conn->inTransaction() || static::i()->intransaction) {
				try {
					static::i()->intransaction = false;
					static::i()->conn->rollBack();
				}
				catch (PDOException $e) {
					static::i()->_error($e);
				}
			}
		}

		//
		//
		/**
		 * @static
		 *
		 * @param $id
		 * @param $status
		 * @param $table
		 * @param $key
		 * Update record activity status.
		 * @return \DB_Query_Result
		 */
		public static function update_record_status($id, $status, $table, $key) {
			$result = static::update($table)->value('inactive', $status)->where($key . '=', $id)->exec();
			if (!$result) {
				$result = static::insert_record_status($id, $status, $table, $key);
			}
			return $result;
		}

		/**
		 * @static
		 * @param $id
		 * @param $status
		 * @param $table
		 * @param $key
		 * @return DB_Query_Result
		 */
		public static function insert_record_status($id, $status, $table, $key) {
			$result = static::insert($table)->values(array('inactive' => $status, $key => $id))->exec();
			return $result;
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
				$prepared = $this->_prepare($sql);
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
				return false;
			}
			catch (PDOException $e) {
				$this->_error($e);
			}
			return false;
		}

		/**
		 * @param PDOException $e
		 * @param bool $msg
		 * @param string|bool				 $exit
		 *
		 * @return bool
		 * @throws DB_Exception
		 */
		protected function _error(PDOException $e, $msg = false, $exit = false) {

			if (static::$data && static::$queryString) {
				$sql = static::$queryString;
				foreach (static::$data as $k => $v) {
					$sql = preg_replace(':' . $k, " '$v' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
				}
				foreach (static::$data as $v) {
					$sql = preg_replace('/\?/i', " '$v' ", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
				}
			}
			if (Config::get('debug_sql')) {
				$error = $e->getCode() . (!isset($error[2])) ? $e->getMessage() : $error[2];
			} elseif ($msg!=false) {
				$error = '<p>DATABASE ERROR: <pre>' . $msg . '</pre></p><p><pre></pre></p>';
			}else {
				$error = "Unknown Database Error";
			}
			if ($this->conn->inTransaction() || $this->intransaction) {
				$this->conn->rollBack();
				$this->intransaction = false;
			}
			if ($exit) {
				throw new DB_Exception($error);
			}
			Errors::show_db_error($error);
		}
	}
