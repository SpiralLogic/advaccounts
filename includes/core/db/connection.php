<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 21/08/11
	 * Time: 11:15 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class DB_Connection
	{
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
		static function i($name, $config) {
			if (!isset(static::$instances[$name])) {
				new static($name, $config);
			}
			return static::$instances[$name];
		}

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
			return $this->conn->prepare($sql);
		}

		/***
		 * @param $sql
		 * @param $type
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
						return $this->lastInsertId();
					case DB::UPDATE or DB::DELETE:
						$prepared->execute($data);
						return $prepared->rowCount();
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
			$this->conn->beginTransaction();
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
			$this->conn->commit();
			$this->intransaction = false;
			return $this;
		}

		/**
		 * @return DB_Connection
		 */
		public function cancel() {
			$this->intransaction = false;
			$this->conn->rollBack();
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
				$this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->name, $this->user, $this->pass);
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch (PDOException $e) {
				$this->_error($e, true);
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
				$error = $e->errorInfo[2];
				//	Errors::show_db_error(' <pre>' . '</pre></p>');
			}
			if ($this->intransaction) {
				$this->conn->rollBack();
			}
			trigger_error($error, E_USER_ERROR);
			if ($exit) {
				//		throw new DB_Exception($error);
			}
			return false;
		}
	}
