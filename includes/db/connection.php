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
		protected static $instances = array();

		static function instance($name, $config)
		{
			if (!isset(static::$instances[$name])) {
				new static($name, $config);
			}
			return static::$instances[$name];
		}

		protected $name;
		protected $user;
		protected $pass;
		protected $host;
		protected $port;
		protected $conn;
		protected $intransaction = false;

		protected function __construct($name, array $config)
		{
			$this->name = $name;
			$this->user = $config['user'];
			$this->pass = $config['pass'];
			$this->host = $config['host'];
			$this->port = $config['port'];
			$this->debug = false;
			$this->_connect();
			static::$instances[$name] = $this;
		}

		public function name()
		{
			return $this->name;
		}

		public function prepare($sql)
		{
			if (Config::get('debug_sql')) {
				(class_exists('FB')) ? FB::info($sql) : var_dump($sql);
			}
			return $this->conn->prepare($sql);
		}

		public function exec($sql, $type, $data = null)
		{
			try {
				$prepared = $this->prepare($sql);
				if (Config::get('debug_sql')) {
					(class_exists('FB')) ? FB::info($data) : var_dump($data);
				}
				switch ($type) {
				case DB::SELECT:
					return new DB_Result($prepared, $data);
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
		}

		public function begin()
		{
			$this->conn->beginTransaction();
			return $this;
		}

		public function lastInsertId()
		{
			return $this->conn->lastInsertId();
		}

		public function commit()
		{
			$this->conn->commit();
			$this->intransaction = false;
			return $this;
		}

		public function cancel()
		{
			$this->intransaction = false;
			$this->conn->rollBack();
			return $this;
		}

		public function query($sql, $fetchas = PDO::FETCH_OBJ)
		{
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

		public function quote($value)
		{
			return $this->conn->quote($value);
		}

		protected function _connect()
		{
			try {
				$this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->name, $this->user, $this->pass);
				$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch (PDOException $e) {
				$this->_error($e, true);
			}
		}

		public function errorCode()
		{
			return $this->conn->errorCode();
		}

		public function errorInfo()
		{
			return $this->conn->errorInfo();
		}
		public  function getAttribute(PDO $value)
			{					return $this->conn->getAttribute($value);
			 }
		protected function _error(PDOException $e, $exit = false)
		{
			if (function_exists('xdebug_call_file')) {
				$error = '<p>DATABASE ERROR: <br>At file ' . xdebug_call_file() . ':' . xdebug_call_line() . ':<br>' . $e->getMessage() . '</p>';
			}
			else {
				$error = '<p>DATABASE ERROR: <pre>' . var_export($e->getTrace(), true) . '</pre></p><p><pre>' . var_export($e->errorInfo, true) . '</pre></p>';
			}
			if ($this->intransaction) {
				$this->conn->rollBack();
			}
			trigger_error($error, E_USER_ERROR);
			if ($exit) {
				throw new DB_Exception($error);
			}
			return false;
		}
	}
