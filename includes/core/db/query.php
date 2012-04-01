<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
namespace Core\DB;
use PDO, PDOStatement, PDOException, PDORow;

  abstract class Query extends Query_Where {

    /**
     * @var DB_Query
     */
    static protected $query = NULL;
    /**
     * @var bool
     */
    protected $compiled_query = FALSE;
    /**
     * @var
     */
    protected $type;
    /**
     * @var DB
     */
    protected $conn;
    /**
     * @abstract

     */
    protected abstract function execute();
    /**
     * @param $conn
     */
    protected function __construct($conn) {
      $this->conn = $conn;
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
     * @return DB_Query_Result|int|bool
     */
    public function exec($data = NULL) {
      $result = $this->conn->exec($this->getQuery($data), $this->type, $this->data);
      return $result;
    }
    /***
     * @return DB_Query_Result
     */
    public function fetch() {
      return $this->exec(NULL);
    }
  }
