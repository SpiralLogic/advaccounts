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

   */
  class DB
  {
    const SELECT = 0;
    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 4;
    /** @var array */
    protected static $connections = array();
    /** @var array */
    protected static $data = array();
    /*** @var string */
    public static $queryString = array();
    /*** @var \PDOStatement */
    protected static $prepared = null;
    /**   @var null */
    protected static $debug = null;
    /** @var bool */
    protected static $nested = false;
    /** @var Query */
    protected static $query = false;
    /**
     * @var bool
     */
    protected static $results = false;
    /**
     * @var bool
     */
    protected static $errorSql = false;
    /**
     * @var bool
     */
    protected static $errorInfo = false;
    /**
     * @var bool
     */
    protected $useCache = false;
    /**
     * @var bool
     */
    protected $useConfig = false;
    /**
     * @var bool
     */
    protected $intransaction = false;
    /*** @var \PDO */
    protected $conn = false;
    /** @var DB */
    protected static $i = null;
    /**
     * @var
     */
    protected $default_connection;
    /***
     * @static
     *
     * @param array $config
     *
     * @internal \PDO $conn
     * @return DB
     */
    protected static function i($config = array())
    {
      if (static::$i === null) {
        static::$i = new static($config);
      }

      return static::$i;
    }
    /**
     * @param $config
     *
     * @throws \ADV\Core\DB\DBException
     */
    protected function __construct($config)
    {
      $this->useConfig = class_exists('Config');
      $this->useCache  = class_exists('Cache');
      if (!$config && !$this->useConfig) {
        throw new DBException('No database configuration provided');
      }
      $config        = $config ? : \Config::get('db.default');
      static::$debug = false;
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
      } catch (\PDOException $e) {
        throw new DBException('Could not connect to database:' . $config['name'] . ', check configuration!');
      }
    }
    /**
     * @static
     *
     * @param mixed $name
     *
     * @throws \ADV\Core\DB\DBException
     * @return void
     */
    public static function change_connection($name = false)
    {
      $name = $name ? : static::i()->default_connection;
      if (!isset(static::$connections[$name])) {
        if (static::i()->useConfig && $name && !is_array($name)) {
          $config = \Config::get('db.' . $name);
        } elseif (is_array($name)) {
          $config = $name;
        } else {
          throw new DBException('No database configuration provided');
        }
        static::i()->_connect($config);
      }
      if (isset(static::$connections[$name])) {
        static::i()->conn = static::$connections[$name];
      } else {
        throw new DBException("There is no connection with this name");
      }
    }
    /**
     * @static
     *
     * @param $config
     */
    public static function connect($config)
    {
      static::i()->_connect($config);
    }
    /**
     * @static
     *
     * @param      $sql
     * @param null $err_msg
     *
     * @return null|\PDOStatement
     */
    public static function query($sql, $err_msg = null)
    {
      static::$prepared = null;
      try {
        static::$prepared = static::i()->_prepare($sql);
        try {
          static::$prepared->execute();
        } catch (\PDOException $e) {
          static::i()->_error($e, " (execute) " . $err_msg);
        }
      } catch (\PDOException $e) {
        static::i()->_error($e, " (prepare) " . $err_msg);
      }
      static::$data = array();

      return static::$prepared;
    }
    /**
     * @static
     *
     * @param      $value
     * @param null $type
     *
     * @return mixed
     */
    public static function quote($value, $type = null)
    {
      return static::i()->conn->quote($value, $type);
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
    public static function escape($value, $null = false)
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
      static::$data[] = array($value, $type);

      return ' ? ';
    }
    /**
     * @static
     *
     * @param      $sql
     * @param bool $debug
     *
     * @throws \ADV\Core\DB\DBException
     * @return bool|\PDOStatement
     */
    protected function _prepare($sql, $debug = false)
    {
      static::$debug     = $debug;
      static::$errorInfo = false;
      static::$errorSql  = $sql;
      $data              = static::$data;
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
      } catch (\PDOException $e) {
        $prepared = false;
        $this->_error($e);
      }
      if ($debug) {
        static::$queryString = $sql;
      }
      static::$data = array();

      return $prepared;
    }
    /**
     * @static
     *
     * @param      $sql
     * @param bool $debug
     *
     * @return null|\PDOStatement
     */
    public static function prepare($sql, $debug = false)
    {
      static::$prepared = static::i()->_prepare($sql, $debug);

      return static::$prepared;
    }
    /**
     * @static
     *
     * @param      $data
     * @param bool $debug
     *
     * @return array|bool
     */
    public static function execute($data, $debug = false)
    {
      if (!static::$prepared) {
        return false;
      }
      if ($debug) {
        static::$queryString = static::$i->placeholderValues(static::$queryString, $data);
      }
      static::$data = $data;
      try {
        static::$prepared->execute($data);
        $result = static::$prepared->fetchAll(\PDO::FETCH_ASSOC);
      } catch (\PDOException $e) {
        $result = static::i()->_error($e);
      }
      static::$data = array();

      return $result;
    }
    /**
     * @static
     * @return string
     */
    public static function insert_id()
    {
      return static::i()->conn->lastInsertId();
    }
    /***
     * @param string $columns,... Database columns to select
     *
     * @return Query_Select
     */
    public static function select($columns = null)
    {
      static::$prepared = null;
      $columns          = (is_string($columns)) ? func_get_args() : array();
      static::$query    = new Query_Select($columns, static::i());

      return static::$query;
    }
    /**
     * @static
     *
     * @param $into
     *
     * @return Query_Update
     */
    public static function update($into)
    {
      static::$prepared = null;
      static::$query    = new Query_Update($into, static::i());

      return static::$query;
    }
    /**
     * @static
     *
     * @param $into
     *
     * @return Query_Insert
     */
    public static function insert($into)
    {
      static::$prepared = null;
      static::$query    = new Query_Insert($into, static::i());

      return static::$query;
    }
    /**
     * @static
     *
     * @param $into
     *
     * @return Query_Delete
     */
    public static function delete($into)
    {
      static::$prepared = null;
      static::$query    = new Query_Delete($into, static::i());

      return static::$query;
    }
    /***
     * @static
     *
     * @param \PDOStatement $result     The result of the query or whatever cunt
     * @param int           $fetch_mode
     *
     * @return Query_Result|Array This is something
     */
    public static function fetch($result = null, $fetch_mode = \PDO::FETCH_BOTH)
    {
      try {
        if ($result !== null) {
          return $result->fetch($fetch_mode);
        }
        if (static::$prepared === null) {
          return static::$query->fetch($fetch_mode);
        }

        return static::$prepared->fetch($fetch_mode);
      } catch (\Exception $e) {
        static::_error($e);
      }

      return false;
    }
    /**
     * @static
     *
     * @param null $result
     *
     * @return mixed
     */
    public static function fetch_row($result = null)
    {
      return static::fetch($result, \PDO::FETCH_NUM);
    }
    /**
     * @static
     * @return mixed
     */
    public static function fetch_assoc()
    {
      return is_a(static::$prepared, '\PDOStatement') ? static::$prepared->fetch(\PDO::FETCH_ASSOC) : false;
    }
    /**
     * @static
     *
     * @param int $fetch_type
     *
     * @return array
     */
    public static function fetch_all($fetch_type = \PDO::FETCH_ASSOC)
    {
      $results = static::$results;
      if (!static::$results) {
        $results = static::$prepared->fetchAll($fetch_type);
      }
      static::$results = false;

      return $results;
    }
    /**
     * @static
     * @return mixed
     */
    public static function error_no()
    {
      $info = static::errorInfo();

      return $info[1];
    }
    /**
     * @static
     * @return mixed
     */
    public static function errorInfo()
    {
      if (static::$errorInfo) {
        return static::$errorInfo;
      }
      if (static::$prepared) {
        return static::$prepared->errorInfo();
      }

      return static::i()->conn->errorInfo();
    }
    /**
     * @static
     * @return mixed
     */
    public static function error_msg()
    {
      $info = static::errorInfo();

      return isset($info[2]) ? $info[2] : false;
    }
    /**
     * @static
     *
     * @param int|\PDO $value
     *
     * @return mixed
     */
    public static function getAttribute($value)
    {
      return static::i()->conn->getAttribute($value);
    }
    /**
     * @static
     * @return bool
     */
    public static function free_result()
    {
      $result           = (static::$prepared) ? static::$prepared->closeCursor() : false;
      static::$errorSql = static::$errorInfo = static::$prepared = null;
      static::$data     = array();

      return $result;
    }
    /**
     * @static
     *
     * @param null|\PDOStatement $sql
     *
     * @return int
     */
    public static function num_rows($sql = null)
    {
      if ($sql === null) {
        return static::$prepared->rowCount();
      }
      if (is_object($sql)) {
        return $sql->rowCount();
      }
      $rows = (static::i()->useCache) ? Cache::get('sql.rowcount.' . md5($sql)) : false;
      if ($rows !== false) {
        return (int) $rows;
      }
      $rows = static::query($sql)->rowCount();
      if (static::$i->useCache) {
        Cache::set('sql.rowcount.' . md5($sql), $rows);
      }

      return $rows;
    }
    /**
     * @static
     * @return int
     */
    public static function num_fields()
    {
      return static::$prepared->columnCount();
    }
    /**
     * @static

     */
    public static function begin()
    {
      /** @noinspection PhpUndefinedMethodInspection */
      if (!static::i()->conn->inTransaction() && !static::i()->intransaction) {
        try {
          static::i()->conn->beginTransaction();
          static::i()->intransaction = true;
        } catch (\PDOException $e) {
          static::i()->_error($e);
        }
      }
    }
    /**
     * @static

     */
    public static function commit()
    {
      /** @noinspection PhpUndefinedMethodInspection */
      if (static::i()->conn->inTransaction() || static::i()->intransaction) {
        static::i()->intransaction = false;
        try {
          static::i()->conn->commit();
        } catch (\PDOException $e) {
          static::i()->_error($e);
        }
      }
    }
    /**
     * @static

     */
    public static function cancel()
    {
      /** @noinspection PhpUndefinedMethodInspection */
      if (static::i()->conn->inTransaction() || static::i()->intransaction) {
        try {
          static::i()->intransaction = false;
          static::i()->conn->rollBack();
        } catch (\PDOException $e) {
          static::i()->_error($e);
        }
      }
      static::$data = array();
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
    public static function update_record_status($id, $status, $table, $key)
    {
      try {
        static::update($table)->value('inactive', $status)->where($key . '=', $id)->exec();
      } catch (DBUpdateException $e) {
        static::insert_record_status($id, $status, $table, $key);
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
    public static function insert_record_status($id, $status, $table, $key)
    {
      try {
        static::insert($table)->values(array('inactive' => $status, $key => $id))->exec();
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
     * @return Query_Result|int
     */
    public function exec($sql, $type, $data = array())
    {
      static::$errorInfo = false;
      static::$errorSql  = $sql;
      static::$data      = $data;
      if ($data && is_array(reset($data))) {
        static::$queryString = static::placeholderValues(static::$errorSql, $data);
      } elseif ($data) {
        static::$queryString = static::namedValues(static::$errorSql, $data);
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
      } catch (\PDOException $e) {
        $this->_error($e);
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
      static::$data = array();

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
    protected static function namedValues($sql, array $data)
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
    protected static function placeholderValues($sql, array $data)
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
    protected function _error(\Exception $e, $msg = false)
    {
      $data         = static::$data;
      static::$data = array();
      if ($data && is_array(reset($data))) {
        static::$errorSql = static::placeholderValues(static::$errorSql, $data);
      } elseif ($data) {
        static::$errorSql = static::namedValues(static::$errorSql, $data);
      }
      static::$queryString = static::$errorSql;
      static::$errorInfo   = $error = $e->errorInfo;
      $error['debug']      = $e->getCode() . (!isset($error[2])) ? $e->getMessage() : $error[2];
      $error['message']    = ($msg != false) ? $msg : $e->getMessage();
      /** @noinspection PhpUndefinedMethodInspection */
      if (is_a($this->conn, '\PDO') && ($this->conn->inTransaction() || $this->intransaction)) {
        $this->conn->rollBack();
        $this->intransaction = false;
      }
      if (static::$errorInfo[1] == 1062) {
        throw new DBDuplicateException(static::$errorInfo[2]);
      }
      if (!class_exists('Errors')) {
        throw new DBException($error);
      }
      \Errors::db_error($error, static::$errorSql, $data);
    }
  }
