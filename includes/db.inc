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
		protected $query;
		protected static $_instance;

		static function __callStatic($func, $args = array()) {
			if (static::$_instance === null) static::$_instance = new static;
			return call_user_func_array(array(static::$_instance, '_' . $func), $args);
		}

		protected function _select($column = null, $orderby = false, $groupby = false) {
			if (!$this->query) $this->query = new DBQuery();
			return $this->query->select($column, $orderby, $groupby);
		}

		protected function _exec() {
			$this->_prepare($this->query->exec());
			return $this->_execute($this->query->data);
		}

		protected function _init($name, $user, $password, $host = false, $port = false) {
			$this->dbname = $name;
			$this->dbuser = $user;
			$this->dbpass = $password;
			$this->dbhost = ($host) ? $host : 'localhost';
			$this->dbport = ($port) ? $port : '3306';
			$this->_connect();
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
				return static::_error($e);
			}
			return $results;
		}

		protected function _prepare($sql) {
			if ($this->showsql) echo "<pre style='size:2, color:000099'>" . $sql . "</pre>\n";
			$this->_prepared = $this->conn->prepare($sql);
	return $this;
		}

		protected function _execute($data = null) {
			if (!$this->_prepared) return;
			$this->_prepared->execute((array)$data);
			return $this->_prepared->fetchAll();
		}

		protected function _fetchClass($data, $type) {
			$this->_prepare($this->query->exec());
			$this->_prepared->execute();
			$this->_prepared->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE,$type);
			return $this->_prepared->fetchAll();
		}

		protected function _connect() {
			try {
				$this->conn = $conn = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname, $this->dbuser, $this->dbpass, array(PDO::ATTR_PERSISTENT => true));
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
