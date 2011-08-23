<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 21/08/11
 * Time: 11:15 PM
 * To change this template use File | Settings | File Templates.
 */

	class DBconnection {

		protected static $instances = array();

		static function instance($name = null, $config = array()) {
			if (!isset(static::$instances[$name])) {
				$default = Config::get('db.default');
				if ($name === null) $name = $default['name'];
				$config = array_merge($default, $config);
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
		protected $last_query;

		protected function __construct($name, array $config) {
			$this->name = $name;
			$this->user = $config['user'];
			$this->pass = $config['pass'];
			$this->host = $config['host'];
			$this->port = $config['port'];
			$this->debug = $config['debug'];
			$this->_connect();
			static::$instances[$name] = $this;
		}

		public function prepare($sql) {
			if ($this->debug) {
				(class_exists('FB')) ? FB::info($sql) : var_dump($sql);
			}
			unset($this->last_query);
			$this->last_query = $this->conn->prepare($sql);
			return $this->last_query;
		}

		public function begin() {
			$this->conn->beginTransaction();
			return $this;
		}

		public function commit() {
			$this->conn->commit();
			return $this;
		}

		public function query($sql) {
			try {
				$query = $this->conn->query($sql);
				$results = array();
				while ($row = $query->fetch(PDO::FETCH_OBJ)) {
					$results[] = $row;
				}
			}
			catch (PDOException $e) {
				return static::_error($e);
			}
			return $results;
		}

		protected function _connect() {
			try {
				$this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->name, $this->user, $this->pass, array(PDO::ATTR_PERSISTENT => true));
				$this->conn->setAttribute(PDO::ATTR_ERRMODE,
																	PDO::ERRMODE_EXCEPTION);

			}
			catch (PDOException $e) {
				$this->_error($e, true);
			}
		}

		protected function _error(PDOException $e, $exit = false, $rollback = false) {
			if (function_exists('xdebug_call_file')) {
				$error = '<p>DATABASE ERROR: <br>At file ' . xdebug_call_file() . ':' . xdebug_call_line() . ':<br>' . $e->getMessage() . '</p>';
			}
			else {
				$error = '<p>DATABASE ERROR: <pre>' . $e->getTraceAsString() . '</pre></p>';
			}
			if ($rollback) $this->conn->rollBack();
			trigger_error($error, E_USER_ERROR);
			if ($exit) exit;
			return false;
		}

	}


