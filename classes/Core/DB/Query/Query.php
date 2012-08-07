<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.core.db
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\DB\Query;
  use PDO, PDOStatement, PDOException, PDORow;

  /**

   */
  abstract class Query extends Where
  {
    /**
     * @var \ADV\Core\DB\Query\Query
     */
    protected static $query = null;
    /**
     * @var bool
     */
    protected $compiled_query = false;
    /** @var */
    protected $type;
    /**
     * @var \ADV\Core\DB\DB
     */
    protected $conn;
    /**
     * @abstract
     * @return
     */
    abstract protected function execute();
    /**
     * @param $conn
     */
    protected function __construct($conn) {
      $this->conn    = $conn;
      static::$query = $this;
    }
    /**
     * @param $data
     *
     * @return bool
     */
    protected function getQuery($data) {
      if (!$this->compiled_query) {
        $this->compiled_query = $this->execute($data);
      }

      return $this->compiled_query;
    }
    /***
     * @param null $data
     *
     * @return \ADV\Core\DB\Query\Result|int|bool
     */
    public function exec($data = null) {
      $result = $this->conn->exec($this->getQuery($data), $this->type, $this->data);

      return $result;
    }
    /***
     * @return \ADV\Core\DB\Query\Result
     */
    public function fetch() {
      return $this->exec(null);
    }
  }
