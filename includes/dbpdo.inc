<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Complex
 * Date: 29/12/10
 * Time: 4:41 AM
 * To change this template use File | Settings | File Templates.
 */
	class DB {

		protected $conn;
		protected $showsql = false;
		protected $debug = false;
		protected $result;
		protected $dbname;
		protected $dbpass;
		protected $dbuser;
		protected $dbhost = 'localhost';
		protected $dbport = 3306;
		protected $_prepared;
		protected static $_instance;

		private static function getinstance() {
			if (static::$_instance === null) static::$_instance = new static;
			return static::$_instance;
		}

		public static function query($sql) {
			$self = static::getinstance();
			return $self->_query($sql);
		}

		public static function prepare($sql) {
			$self = static::getinstance($sql);
			return $self->_prepare($sql);
		}

		public static function initConnect($name, $user, $password, $host = null, $port = null) {
			$self = static::getinstance();
			$self->_connect($name, $user, $password, $host, $port);
		}

		protected function __construct() {
		}

		protected function _query($sql) {
			if ($this->showsql) echo "<pre style='size:2, color:000099'>" . $sql . "</pre>\n";
			try {
				$query = $this->conn->query($sql);
				$results = array();
				while ($row = $query->fetch(PDO::FETCH_OBJ)) {
					$results[] = $row;
				}
			} catch (PDOException $e) {
				return $this->_error($e);
			}
			return $results;
		}

		protected function _prepare($sql) {
			if ($this->showsql) echo "<pre style='size:2, color:000099'>" . $sql . "</pre>\n";

			$this->_prepared = $this->conn->prepare($sql);
			return $this;
		}

		public function execute($data=null) {
			if (!$this->_prepared) return null;
			$this->_prepared->execute($data);
			
			return $this->_prepared->fetchAll();
		}

		protected function _connect($name, $user, $password, $host, $port) {
			if (!empty($host)) $this->dbhost = $host;
			$this->dbname = $name;
			$this->dbuser = $user;
			$this->dbpass = $password;
			if (!empty($port)) $this->dbport = $port;
			try {
				$this->conn = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $name, $user, $password,
					array(PDO::ATTR_PERSISTENT => true));
			} catch (PDOException $e) {
				$this->_error($e, true);
			}
		}

		protected function _error(PDOException $e, $exit = false, $rollback = false) {
			if (function_exists('xdebug_call_file')) {
				$error = '<p>DATABASE ERROR: <br>At file ' . xdebug_call_file() . ':' . xdebug_call_line() . ':<br>' . $e->getMessage() . '</p>';
			} else {
				$error = '<p>DATABASE ERROR: <pre>' . $e->getTraceAsString() . '</pre></p>';
			}
			if ($rollback) $this->conn->rollBack();
			trigger_error($error, E_USER_ERROR);
			if ($exit) exit;
			return false;
		}
	}
