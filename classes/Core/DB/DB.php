<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core.db
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\DB;

  use PDO, PDOStatement, PDOException;
  use ADV\Core\DB\Query\Select;

  /**
   * @method static \ADV\Core\DB\DB i()
   * @method static \PDOStatement _query($sql, $err_msg = null)
   * @method static \ADV\Core\DB\Query\Select _select($columns = null)
   * @method static \ADV\Core\DB\Query\Insert _insert($into)
   * @method static \ADV\Core\DB\Query\Update _update($into)
   * @method static _escape($value, $null = false)
   * @method static \ADV\Core\DB\Query\Result|Array _fetch($result = null, $fetch_mode = \PDO::FETCH_BOTH)
   * @method static _fetchRow($result = null)
   * @method static _fetchAll($fetch_type = \PDO::FETCH_ASSOC)
   * @method static _fetchAssoc()
   * @method static _errorMsg()
   * @method static _insertId()
   * @method static _numRows($sql = null)
   * @method static _numFields()
   * @method static DB _begin()
   * @method static DB  _commit()
   * @method static DB  _prepare($sql, $debug = false)
   * @method static DB  _execute($data, $debug = false)
   * @method  static DB _updateRecordStatus($id, $status, $table, $key)
   * @method  static DB _cancel()
   * @method static \ADV\Core\DB\Query\Delete _delete()
   * @method static _errorNo()
   * @method  static _quote($value, $type = null)
   */
  class DB
  {
    use \ADV\Core\Traits\StaticAccess2;

    const SELECT = 0;
    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 4;
    /** @var array */
    protected static $connections = [];
    /** @var array */
    protected $data = [];
    /*** @var string */
    public $queryString = [];
    /*** @var \PDOStatement */
    protected $prepared = null;
    /**   @var null */
    protected $debug = null;
    /** @var bool */
    protected $nested = false;
    /** @var Query\Query|Query\Select|Query\Update $query */
    protected $query = false;
    /**
     * @var bool
     */
    protected $results = false;
    /**
     * @var bool
     */
    protected $errorSql = false;
    /**
     * @var bool
     */
    protected $errorInfo = false;
    /**
     * @var bool
     */
    protected $useCache = false;
    /**
     * @var array
     */
    protected $config = false;
    /**
     * @var bool
     */
    protected $intransaction = false;
    /*** @var \PDO */
    protected $conn = false;
    /** @var */
    protected $default_connection;
    /** @var \Cache */
    protected $cache;
    /**
     * @throws DBException
     */
    public function __construct($name = 'default', \Config $config = null, $cache = null) {
      $Config         = $config ? : \Config::i();
      $this->useCache = class_exists('ADV\\Core\\Cach\\Cache', false);
      if (!$Config) {
        throw new DBException('No database configuration provided');
      }
      $this->config = $Config->get('db.' . $name);
      $this->debug  = false;
      $this->connect($this->config);
      $this->default_connection = $this->config['name'];
    }
    /**
     * @param  $config
     *
     * @throws \ADV\Core\DB\DBException
     * @return bool
     */
    protected function connect($config) {
      try {
        $conn = new PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['user'], $config['pass'], array(PDO::MYSQL_ATTR_FOUND_ROWS => true));
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);
 //       $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        static::$connections[$config['name']] = $conn;
        if ($this->conn === false) {
          $this->conn = $conn;
        }

        return true;
      } catch (PDOException $e) {
        throw new DBException('Could not connect to database:' . $config['name'] . ', check configuration!');
      }
    }
    /**
     * @param      $sql
     * @param null $err_msg
     *
     * @return null|PDOStatement
     */
    public function query($sql, $err_msg = null) {
      $this->prepared = null;
      try {
        $this->prepared = $this->prepare($sql);
        try {
          $this->prepared->execute();
        } catch (PDOException $e) {
          $this->error($e, " (execute) " . $err_msg);
        }
      } catch (PDOException $e) {
        $this->error($e, " (prepare) " . $err_msg);
      }
      $this->data = [];

      return $this->prepared;
    }
    /**
     * @static
     *
     * @param      $value
     * @param null $type
     *
     * @return mixed
     */
    /**
     * @param          $value
     * @param int|null $type
     *
     * @return string
     */
    public function quote($value, $type = null) {
      return $this->conn->quote($value, $type);
    }
    /**
     * @static
     *
     * @param      $value
     * @param bool $null
     *
     * @internal param bool $paramaterized
     * @return bool|mixed|string
     */
    public function escape($value, $null = false) {
      $value = trim($value);
      if (!isset($value) || is_null($value) || $value === "") {
        $value = ($null) ? 'null' : '';
        $type  = PDO::PARAM_NULL;
      } elseif (is_int($value)) {
        $type = PDO::PARAM_INT;
      } elseif (is_bool($value)) {
        $type = PDO::PARAM_BOOL;
      } elseif (is_string($value)) {
        $type = PDO::PARAM_STR;
      } else {
        $type = false;
      }
      $this->data[] = array($value, $type);

      return ' ? ';
    }
    /**
     * @param      $sql
     * @param bool $debug
     *
     * @throws DBException
     * @return bool|PDOStatement
     */
    protected function prepare($sql, $debug = false) {
      $this->debug     = $debug;
      $this->errorInfo = false;
      $this->errorSql  = $sql;
      $data            = $this->data;
      try {
        /** @var \PDOStatement $prepared  */
        $prepared = $this->conn->prepare($sql);
        $params   = substr_count($sql, '?');
        if ($data && $params > count($data)) {
          throw new DBException('There are more escaped values than there are placeholders!!');
        }
        $k = 1;
        while (($v = array_shift($data)) && $k <= $params) {
          $prepared->bindValue($k, $v[0], $v[1]);
          $k++;
        }
      } catch (PDOException $e) {
        $prepared = false;
        $this->error($e);
      }
      if ($debug) {
        $this->queryString = $sql;
      }
      $this->data     = [];
      $this->prepared = $prepared;

      return $prepared;
    }
    /**
     * @param      $data
     * @param bool $debug
     *
     * @return array|bool
     */
    public function execute($data, $debug = false) {
      if (!$this->prepared) {
        return false;
      }
      if ($debug) {
        $this->queryString = $this->placeholderValues($this->queryString, $data);
      }
      $this->data = $data;
      try {
        $this->prepared->execute($data);
        $result = $this->prepared->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        $result = $this->error($e);
      }
      $this->data = [];

      return $result;
    }
    /**
     * @static
     * @return string
     */
    public function insertId() {
      return $this->conn->lastInsertId();
    }
    /***
     * @param string $columns,... Database columns to select
     *
     * @return Query\Select
     */
    public function select($columns = null) {
      $this->prepared = null;
      $columns        = (is_string($columns)) ? func_get_args() : [];
      $this->query    = new Select($columns, $this);

      return $this->query;
    }
    /**
     * @static
     *
     * @param $into
     *
     * @return \ADV\Core\DB\Query\Update
     */
    public function update($into) {
      $this->prepared = null;
      $this->query    = new Query\Update($into, $this);

      return $this->query;
    }
    /**
     * @param $into
     *
     * @return Query\Insert|bool
     */
    public function insert($into) {
      $this->prepared = null;
      $this->query    = new Query\Insert($into, $this);

      return $this->query;
    }
    /**
     * @param $into
     *
     * @return \ADV\Core\DB\Query\Query|bool
     */
    public function delete($into) {
      $this->prepared = null;
      $this->query    = new Query\Delete($into, $this);

      return $this->query;
    }
    /***
     * @param \PDOStatement $result     The result of the query or whatever cunt
     * @param int           $fetch_mode
     *
     * @return Query\Result|Array This is something
     */
    public function fetch($result = null, $fetch_mode = PDO::FETCH_BOTH) {
      try {
        if ($result !== null) {
          return $result->fetch($fetch_mode);
        }
        if ($this->prepared === null) {
          return $this->query->fetch($fetch_mode);
        }

        return $this->prepared->fetch($fetch_mode);
      } catch (\Exception $e) {
        $this->error($e);
      }

      return false;
    }
    /**
     * @param null|PDOStatement $result
     *
     * @return Query\Result|Array
     */
    public function fetchRow($result = null) {
      return $this->fetch($result, PDO::FETCH_NUM);
    }
    /**
     * @return bool|mixed
     */
    public function fetchAssoc() {
      return is_a($this->prepared, 'PDOStatement') ? $this->prepared->fetch(PDO::FETCH_ASSOC) : false;
    }
    /**
     * @param int $fetch_type
     *
     * @return array|bool
     */
    public function fetchAll($fetch_type = PDO::FETCH_ASSOC) {
      $results = $this->results;
      if (!$this->results) {
        $results = $this->prepared->fetchAll($fetch_type);
      }
      $this->results = false;

      return $results;
    }
    /**
     * @static
     * @return mixed
     */
    public function errorNo() {
      $info = $this->errorInfo();

      return $info[1];
    }
    /**
     * @static
     * @return mixed
     */
    public function errorInfo() {
      if ($this->errorInfo) {
        return $this->errorInfo;
      }
      if ($this->prepared) {
        return $this->prepared->errorInfo();
      }

      return $this->conn->errorInfo();
    }
    /**
     * @static
     * @return mixed
     */
    public function errorMsg() {
      $info = $this->errorInfo();

      return isset($info[2]) ? $info[2] : false;
    }
    /**
     * @static
     *
     * @param int|PDO $value
     *
     * @return mixed
     */
    public function getAttribute($value) {
      return $this->conn->getAttribute($value);
    }
    /**
     * @static
     * @return bool
     */
    public function freeResult() {
      $result         = ($this->prepared) ? $this->prepared->closeCursor() : false;
      $this->errorSql = $this->errorInfo = $this->prepared = null;
      $this->data     = [];

      return $result;
    }
    /**
     * @static
     *
     * @param null|PDOStatement $sql
     *
     * @return int
     */
    public function numRows($sql = null) {
      if ($sql === null) {
        return $this->prepared->rowCount();
      }
      if (is_object($sql)) {
        return $sql->rowCount();
      }
      $rows = ($this->cache) ? $this->cache->get('sql.rowcount.' . md5($sql)) : false;
      if ($rows !== false) {
        return (int) $rows;
      }
      $rows = $this->query($sql)->rowCount();
      if ($this->cache) {
        $this->cache->set('sql.rowcount.' . md5($sql), $rows);
      }

      return $rows;
    }
    /**
     * @static
     * @return int
     */
    public function numFields() {
      return $this->prepared->columnCount();
    }
    /**
     * @static

     */
    public function begin() {
      /** @noinspection PhpUndefinedMethodInspection */
      if (!$this->conn->inTransaction() && !$this->intransaction) {
        try {
          $this->conn->beginTransaction();
          $this->intransaction = true;
        } catch (PDOException $e) {
          $this->error($e);
        }
      }
    }
    /**
     * @static

     */
    public function commit() {
      /** @noinspection PhpUndefinedMethodInspection */
      if ($this->conn->inTransaction() || $this->intransaction) {
        $this->intransaction = false;
        try {
          $this->conn->commit();
        } catch (PDOException $e) {
          $this->error($e);
        }
      }
    }
    /**
     * @static

     */
    public function cancel() {
      /** @noinspection PhpUndefinedMethodInspection */
      if ($this->conn->inTransaction() || $this->intransaction) {
        try {
          $this->intransaction = false;
          $this->conn->rollBack();
        } catch (PDOException $e) {
          $this->error($e);
        }
      }
      $this->data = [];
    }
    /**
     * @static
     *
     * @param $id
     * @param $status
     * @param $table
     * @param $key
     * Update record activity status.
     *
     * @return Query\Result
     */
    public function updateRecordStatus($id, $status, $table, $key) {
      try {
        $this->update($table)->value('inactive', $status)->where($key . '=', $id)->exec();
      } catch (DBUpdateException $e) {
        static::insertRecordStatus($id, $status, $table, $key);
      }
    }
    /**
     * @static
     *
     * @param $id
     * @param $status
     * @param $table
     * @param $key
     *
     * @throws \ADV\Core\DB\DBUpdateException
     * @return Query\Result
     */
    public function insertRecordStatus($id, $status, $table, $key) {
      try {
        $this->insert($table)->values(array('inactive' => $status, $key => $id))->exec();
      } catch (DBInsertException $e) {
        throw new DBUpdateException('Could not update record inactive status');
      }
    }
    /***
     * @param            $sql
     * @param            $type
     * @param array|null $data
     *
     * @throws \ADV\Core\DB\DBDeleteException
     * @throws \ADV\Core\DB\DBUpdateException
     * @throws \ADV\Core\DB\DBInsertException
     * @throws \ADV\Core\DB\DBSelectException
     * @return Query\Result|int
     */
    public function exec($sql, $type, $data = []) {
      $this->errorInfo = false;
      $this->errorSql  = $sql;
      $this->data      = $data;
      if ($data && is_array(reset($data))) {
        $this->queryString = $this->placeholderValues($this->errorSql, $data);
      } elseif ($data) {
        $this->queryString = $this->namedValues($this->errorSql, $data);
      }
      try {
        $prepared = $this->prepare($sql);
        switch ($type) {
          case DB::SELECT:
            return new Query\Result($prepared, $data);
          case DB::INSERT:
            $prepared->execute($data);

            return $this->conn->lastInsertId();
          case DB::UPDATE:
          case DB::DELETE:
            $prepared->execute($data);

            return $prepared->rowCount();
          default:
            return false;
        }
      } catch (PDOException $e) {
        $this->error($e);
        switch ($type) {
          case DB::SELECT:
            throw new DBSelectException('Could not select from database.');
            break;
          case DB::INSERT:
            throw new DBInsertException('Could not insert into database.');
            break;
          case DB::UPDATE:
            throw new DBUpdateException('Could not update database.');
            break;
          case DB::DELETE:
            throw new DBDeleteException('Could not delete from database.');
            break;
        }
      }
      $this->data = [];

      return false;
    }
    /**
     * @static
     *
     * @param       $sql
     * @param array $data
     *
     * @return mixed
     */
    protected function namedValues($sql, array $data) {
      foreach ($data as $k => $v) {
        $sql = str_replace(":$k", " '$v' ", $sql); // outputs '123def abcdef abcdef' str_replace(,,$sql);
      }

      return $sql;
    }
    /**
     * @static
     *
     * @param       $sql
     * @param array $data
     *
     * @return mixed
     */
    protected function placeholderValues($sql, array $data) {
      foreach ($data as $v) {
        if (is_array($v)) {
          $v = $v[0];
        }
        $sql = preg_replace('/\?/i', "'$v'", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
      }

      return $sql;
    }
    /**
     * @param \Exception|PDOException  $e
     * @param bool                     $msg
     *
     * @throws \ADV\Core\DB\DBDuplicateException
     * @throws \ADV\Core\DB\DBException
     * @internal param bool|string $exit
     * @return bool
     */
    protected function error(\Exception $e, $msg = false) {
      $data       = $this->data;
      $this->data = [];
      if ($data && is_array(reset($data))) {
        $this->errorSql = $this->placeholderValues($this->errorSql, $data);
      } elseif ($data) {
        $this->errorSql = $this->namedValues($this->errorSql, $data);
      }
      $this->queryString = $this->errorSql;
      $this->errorInfo   = $error = $e->errorInfo;
      $error['debug']    = $e->getCode() . (!isset($error[2])) ? $e->getMessage() : $error[2];
      $error['message']  = ($msg != false) ? $msg : $e->getMessage();
      /** @noinspection PhpUndefinedMethodInspection */
      if (is_a($this->conn, 'PDO') && ($this->conn->inTransaction() || $this->intransaction)) {
        $this->conn->rollBack();
        $this->intransaction = false;
      }
      if (isset($this->errorInfo[1]) && $this->errorInfo[1] == 1062) {
        throw new DBDuplicateException($this->errorInfo[2]);
      }
      if (!class_exists('Errors')) {
        throw new DBException($error);
      }
      \Errors::databaseError($error, $this->errorSql, $data);
    }
    /**
     * @return array
     */
    public function __sleep() {
      $this->conn     = null;
      $this->prepared = null;

      return array_keys((array) $this);
    }
    public function __wakeup() {
      $this->connect($this->config);
    }
  }
