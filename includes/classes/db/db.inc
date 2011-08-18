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
	 protected $type;
	 protected static $_instance;
	 protected static $_instances = array();

	 const SELECT = 0;
	 const INSERT = 1;
	 const UPDATE = 2;

	 static function __callStatic($func, $args = array()) {
		if (static::$_instance === null) static::$_instance = new static;
		$func = (method_exists(static::$_instance, $func)) ? $func : '_' . $func;
		return call_user_func_array(array(static::$_instance, $func), $args);
	 }

	 static function init($name, $user, $password, $host = false, $port = false) {

		if (static::$_instance === null) {
		  static::$_instance = new static;
		  $self = static::$_instance;
		} else {
		  static::$_instances[$name] = new static;
		  $self = static::$_instances[$name];
		}
		$self->dbname = $name;
		$self->dbuser = $user;
		$self->dbpass = $password;
		$self->dbhost = ($host) ? $host : 'localhost';
		$self->dbport = ($port) ? $port : '3306';
		$self->_connect();
		return $self;
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
		}
		catch (PDOException $e) {
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
		switch ($this->type) {
		  case DB::INSERT:
			 return $this->conn->lastInsertId();
			 break;
		  case DB::SELECT:
			 return $this->_prepared->fetchAll();
			 break;
		  case DB::UPDATE:
			 return;
		}

	 }

	 public function fetch($data = null) {

		if ($this->query) $this->_exec($data);
		$this->_prepared->setFetchMode(PDO::FETCH_ASSOC);
		return $this->_prepared->fetch();
	 }

	 public function fetchAll($data = null) {
		$this->_exec($data);
		return $this->_prepared->fetchAll();
	 }

	 public function fetchClass($type, $data = null) {
		if ($this->query) $this->_exec($data);
		$this->_prepared->setFetchMode(PDO::FETCH_CLASS, $type);
		return $this->_prepared->fetch();
	 }

	 public function fetchIntoClass($type, $data = null) {
		if ($this->query) $this->_exec($data);
		$this->_prepared->setFetchMode(PDO::FETCH_INTO, $type);
		return $this->_prepared->fetchAll();
	 }

	 public function begin() {
		$this->conn->beginTransaction();
		return $this;
	 }

	 public function commit() {
		$this->conn->commit();
		return $this;
	 }

	 public function rowCount() {
		if ($this->query) $this->_exec(null);

		return $this->_prepared->rowCount();
	 }

	 protected function _connect() {
		try {
		  $this->conn = $conn = new PDO('mysql:host=' . $this->dbhost . ';dbname=' . $this->dbname, $this->dbuser, $this->dbpass, array(PDO::ATTR_PERSISTENT => true));
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

	 public function select($column = null, $orderby = false, $groupby = false) {
		if (!$this->query) $this->query = new Select();
		$this->type = DB::SELECT;
		return $this->query->select($column, $orderby, $groupby);
	 }

	 public function update($into) {
		if (!$this->query) return $this->query = new Update($into);
		$this->type = DB::UPDATE;
	 }

	 public function insert($into) {
		if (!$this->query) return $this->query = new Insert($into);
		$this->type = DB::INSERT;
	 }

	 protected function _exec($data = null) {
		$sql = $this->query->exec($data);
		var_dump($sql);
		$this->_prepare($sql);
		$this->_prepared->execute($this->query->data);
		$this->query = null;

	 }
  }
