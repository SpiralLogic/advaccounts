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
  use PDO, PDOStatement, PDOException, PDORow, Cache;

  /**
   * @method query($sql, $err_msg = null)
   * @method select($columns = null)
   * @method update($into)
   * @method escape($value, $null = false)
   * @method fetch($result = null, $fetch_mode = \PDO::FETCH_BOTH)
   * @method fetch_row($result = null)
   * @method fetch_assoc()
   * @method num_rows($sql = null)
   * @method begin()
   * @method commit()
   * @method error_no()
   * @method \ADV\Core\DB\Query_Select select($columns = null)
   * @method \ADV\Core\DB\Query_Update update($into)
   */
  class DB
  {
    use \ADV\Core\Traits\StaticAccess;

    const SELECT = 0;
    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 4;
    /** @var array */
    protected static $connections = array();
    /** @var array */
    protected $data = array();
    /*** @var string */
    public $queryString = array();
    /*** @var \PDOStatement */
    protected $prepared = null;
    /**   @var null */
    protected $debug = null;
    /** @var bool */
    protected $nested = false;
    /** @var Query */
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
     * @var \Config
     */
    protected $config = false;
    /**
     * @var bool
     */
    protected $intransaction = false;
    /*** @var \PDO */
    protected $conn = false;
    /**
     * @var
     */
    protected $default_connection;
    /** @var \Cache */
    protected $cache;
    /**
     * @throws DBException
     */
    public function __construct($name='default', \Config $config = null, $cache = null)
    {
      $this->config   = $config ? : \Config::i();
      $this->useCache = class_exists('Cache');
      if (!$this->config) {
        throw new DBException('No database configuration provided');
      }
      $config      = $this->config->_get('db.'.$name);
      $this->debug = false;
      $this->_connect($config);
      $this->default_connection = $config['name'];
    }
    /**
     * @param  $config
     *
     * @throws \ADV\Core\DB\DBException
     * @return bool
     */
    protected function _connect($config)
    {
      try {
        $conn = new \PDO('mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'], $config['user'], $config['pass'], array(\PDO::MYSQL_ATTR_FOUND_ROWS => true));
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING);
        static::$connections[$config['name']] = $conn;
        if ($this->conn === false) {
          $this->conn = $conn;
        }
        return true;
      }
      catch (\PDOException $e) {
        throw new DBException('Could not connect to database:' . $config['name'] . ', check configuration!');
      }
    }
    /**
     * @static
     *
     * @param      $sql
     * @param null $err_msg
     *
     * @return null|\PDOStatement
     */
    /**
     * @param      $sql
     * @param null $err_msg
     *
     * @return null|\PDOStatement
     */
    public function _query($sql, $err_msg = null)
    {
      $this->prepared = null;
      try {
        $this->prepared = $this->_prepare($sql);
        try {
          $this->prepared->execute();
        }
        catch (\PDOException $e) {
          $this->error($e, " (execute) " . $err_msg);
        }
      }
      catch (\PDOException $e) {
        $this->error($e, " (prepare) " . $err_msg);
      }
      $this->data = array();
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
    public function _quote($value, $type = null)
    {
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
    public function _escape($value, $null = false)
    {
      $value = trim($value);
      if (!isset($value) || is_null($value) || $value === "") {
        $value = ($null) ? 'null' : '';
        $type  = \PDO::PARAM_NULL;
      } elseif (is_int($value)) {
        $type = \PDO::PARAM_INT;
      } elseif (is_bool($value)) {
        $type = \PDO::PARAM_BOOL;
      } elseif (is_string($value)) {
        $type = \PDO::PARAM_STR;
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
     * @return bool|\PDOStatement
     */
    protected function _prepare($sql, $debug = false)
    {
      $this->debug     = $debug;
      $this->errorInfo = false;
      $this->errorSql  = $sql;
      $data            = $this->data;
      try {
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
      }
      catch (\PDOException $e) {
        $prepared = false;
        $this->error($e);
      }
      if ($debug) {
        $this->queryString = $sql;
      }
      $this->data     = array();
      $this->prepared = $prepared;
      return $prepared;
    }
    /**
     * @param      $data
     * @param bool $debug
     *
     * @return array|bool
     */
    public function _execute($data, $debug = false)
    {
      if (!$this->prepared) {
        return false;
      }
      if ($debug) {
        $this->queryString = $this->placeholderValues($this->queryString, $data);
      }
      $this->data = $data;
      try {
        $this->prepared->execute($data);
        $result = $this->prepared->fetchAll(\PDO::FETCH_ASSOC);
      }
      catch (\PDOException $e) {
        $result = $this->error($e);
      }
      $this->data = array();
      return $result;
    }
    /**
     * @static
     * @return string
     */
    public function _insert_id()
    {
      return $this->conn->lastInsertId();
    }
    /***
     * @param string $columns,... Database columns to select
     *
     * @return Query_Select
     */
    public function _select($columns = null)
    {
      $this->prepared = null;
      $columns        = (is_string($columns)) ? func_get_args() : array();
      $this->query    = new Query_Select($columns, static::i());
      return $this->query;
    }
    /**
     * @static
     *
     * @param $into
     *
     * @return Query_Update
     */
    public function _update($into)
    {
      $this->prepared = null;
      $this->query    = new Query_Update($into, $this);
      return $this->query;
    }
    /**
     * @param $into
     *
     * @return Query_Insert|bool
     */
    public function _insert($into)
    {
      $this->prepared = null;
      $this->query    = new Query_Insert($into, $this);
      return $this->query;
    }
    /**
     * @param $into
     *
     * @return \ADV\Core\DB\Query|bool
     */
    public function _delete($into)
    {
      $this->prepared = null;
      $this->query    = new Query_Delete($into, $this);
      return $this->query;
    }
    /***
     * @param \PDOStatement $result     The result of the query or whatever cunt
     * @param int           $fetch_mode
     *
     * @return Query_Result|Array This is something
     */
    public function _fetch($result = null, $fetch_mode = \PDO::FETCH_BOTH)
    {
      try {
        if ($result !== null) {
          return $result->fetch($fetch_mode);
        }
        if ($this->prepared === null) {
          return $this->query->fetch($fetch_mode);
        }
        return $this->prepared->fetch($fetch_mode);
      }
      catch (\Exception $e) {
        $this->error($e);
      }
      return false;
    }
    /**
     * @param null|\PDOStatement $result
     *
     * @return Query_Result|Array
     */
    public function _fetch_row($result = null)
    {
      return $this->_fetch($result, \PDO::FETCH_NUM);
    }
    /**
     * @return bool|mixed
     */
    public function _fetch_assoc()
    {
      return is_a($this->prepared, '\PDOStatement') ? $this->prepared->fetch(\PDO::FETCH_ASSOC) : false;
    }
    /**
     * @param int $fetch_type
     *
     * @return array|bool
     */
    public function _fetch_all($fetch_type = \PDO::FETCH_ASSOC)
    {
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
    public function _error_no()
    {
      $info = $this->_errorInfo();
      return $info[1];
    }
    /**
     * @static
     * @return mixed
     */
    public function _errorInfo()
    {
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
    public function _error_msg()
    {
      $info = $this->_errorInfo();
      return isset($info[2]) ? $info[2] : false;
    }
    /**
     * @static
     *
     * @param int|\PDO $value
     *
     * @return mixed
     */
    public function _getAttribute($value)
    {
      return $this->conn->getAttribute($value);
    }
    /**
     * @static
     * @return bool
     */
    public function _free_result()
    {
      $result         = ($this->prepared) ? $this->prepared->closeCursor() : false;
      $this->errorSql = $this->errorInfo = $this->prepared = null;
      $this->data     = array();
      return $result;
    }
    /**
     * @static
     *
     * @param null|\PDOStatement $sql
     *
     * @return int
     */
    public function _num_rows($sql = null)
    {
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
      $rows = $this->_query($sql)->rowCount();
      if ($this->cache) {
        $this->cache->set('sql.rowcount.' . md5($sql), $rows);
      }
      return $rows;
    }
    /**
     * @static
     * @return int
     */
    public function _num_fields()
    {
      return $this->prepared->columnCount();
    }
    /**
     * @static

     */
    public function _begin()
    {
      /** @noinspection PhpUndefinedMethodInspection */
      if (!$this->conn->inTransaction() && !$this->intransaction) {
        try {
          $this->conn->beginTransaction();
          $this->intransaction = true;
        }
        catch (\PDOException $e) {
          $this->error($e);
        }
      }
    }
    /**
     * @static

     */
    public function _commit()
    {
      /** @noinspection PhpUndefinedMethodInspection */
      if ($this->conn->inTransaction() || $this->intransaction) {
        $this->intransaction = false;
        try {
          $this->conn->commit();
        }
        catch (\PDOException $e) {
          $this->error($e);
        }
      }
    }
    /**
     * @static

     */
    public function _cancel()
    {
      /** @noinspection PhpUndefinedMethodInspection */
      if ($this->conn->inTransaction() || $this->intransaction) {
        try {
          $this->intransaction = false;
          $this->conn->rollBack();
        }
        catch (\PDOException $e) {
          $this->error($e);
        }
      }
      $this->data = array();
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
     * @return Query_Result
     */
    public function _update_record_status($id, $status, $table, $key)
    {
      try {
        $this->_update($table)->value('inactive', $status)->where($key . '=', $id)->exec();
      }
      catch (DBUpdateException $e) {
        static::_insert_record_status($id, $status, $table, $key);
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
     * @return Query_Result
     */
    public function _insert_record_status($id, $status, $table, $key)
    {
      try {
        $this->_insert($table)->values(array('inactive' => $status, $key => $id))->exec();
      }
      catch (DBInsertException $e) {
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
     * @return Query_Result|int
     */
    public function exec($sql, $type, $data = array())
    {
      $this->errorInfo = false;
      $this->errorSql  = $sql;
      $this->data      = $data;
      if ($data && is_array(reset($data))) {
        $this->queryString = $this->placeholderValues($this->errorSql, $data);
      } elseif ($data) {
        $this->queryString = $this->namedValues($this->errorSql, $data);
      }
      try {
        $prepared = $this->_prepare($sql);
        switch ($type) {
          case DB::SELECT:
            return new Query_Result($prepared, $data);
          case DB::INSERT:
            $prepared->execute($data);
            return $this->conn->lastInsertId();
          case DB::UPDATE or DB::DELETE:
            $prepared->execute($data);
            return true;
        }
      }
      catch (\PDOException $e) {
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
      $this->data = array();
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
    protected function namedValues($sql, array $data)
    {
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
    protected function placeholderValues($sql, array $data)
    {
      foreach ($data as $v) {
        if (is_array($v)) {
          $v = $v[0];
        }
        $sql = preg_replace('/\?/i', "'$v'", $sql, 1); // outputs '123def abcdef abcdef' str_replace(,,$sql);
      }
      return $sql;
    }
    /**
     * @param \Exception|\PDOException $e
     * @param bool                     $msg
     *
     * @throws \ADV\Core\DB\DBDuplicateException
     * @throws \ADV\Core\DB\DBException
     * @internal param bool|string $exit
     * @return bool
     */
    protected function error(\Exception $e, $msg = false)
    {
      $data       = $this->data;
      $this->data = array();
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
      if (is_a($this->conn, '\PDO') && ($this->conn->inTransaction() || $this->intransaction)) {
        $this->conn->rollBack();
        $this->intransaction = false;
      }
      if ($this->errorInfo[1] == 1062) {
        throw new DBDuplicateException($this->errorInfo[2]);
      }
      if (!class_exists('Errors')) {
        throw new DBException($error);
      }
      \Errors::db_error($error, $this->errorSql, $data);
    }
    public function __sleep()
    {
      $this->conn = null;
      return array_keys((array) $this);
    }
  }
