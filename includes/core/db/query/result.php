<?php
  /**
   * PHP version 5
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @license   ADV http://www.advancedgroup.com.au
   * @link      http://www.advancedgroup.com.au
   **/
  namespace Core;
  use PDO, PDOStatement, PDOException, PDORow;


  class DB_Query_Result implements \Countable, \Iterator {

    /**
     * @var \PDOStatement thing
     */
    public $prepared;
    /**
     * @var
     */
    protected $current;
    /**
     * @var
     */
    protected $count;
    /**
     * @var int
     */
    protected $cursor = -1;
    /**
     * @var null
     */
    protected $data;
    /**
     * @var
     */
    protected $valid;
    /**
     * @param      $prepared
     * @param null $data
     */
    public function __construct($prepared, $data = NULL) {
      $this->data = $data;
      $this->prepared = $prepared;
      $this->prepared->setFetchMode(\PDO::FETCH_ASSOC);
      $this->execute();
    }
    /**

     */
    protected function execute() {
      $this->cursor = 0;
      $this->valid = $this->prepared->execute($this->data);
      $this->count = $this->prepared->rowCount();
    }
    /**
     * @return array
     */
    public function all() {
      $result = $this->prepared->fetchAll();
      $this->prepared = NULL;
      return $result;
    }
    /**
     * @param null $column
     *
     * @return mixed
     */
    public function one($column = NULL) {
      $result = $this->prepared->fetch();
      return ($column !== NULL && isset($result[$column])) ? $result[$column] : $result;
    }
    /**
     * @return DB_Query_Result
     */
    public function assoc() {
      $this->prepared->setFetchMode(\PDO::FETCH_ASSOC);
      return $this;
    }
    /**
     * @return DB_Query_Result
     */
    public function num() {
      $this->prepared->setFetchMode(\PDO::FETCH_NUM);
      return $this;
    }
    /**
     * @param       $class
     * @param array $construct
     *
     * @return DB_Query_Result
     */
    public function asClassLate($class, $construct = array()) {
      $this->prepared->setFetchMode(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, $class, $construct);
      return $this;
    }
    /**
     * @param       $class
     * @param array $construct
     *
     * @return DB_Query_Result
     */
    public function asClass($class, $construct = array()) {
      $this->prepared->setFetchMode(\PDO::FETCH_CLASS, $class, $construct);
      return $this;
    }
    /**
     * @param $object
     */
    public function intoClass($object) {
      return $this->intoObject($object);
    }
    /**
     * @param $object
     */
    public function intoObject($object) {
      $this->prepared->setFetchMode(\PDO::FETCH_INTO, $object);
      $this->prepared->fetch();
      $this->count = $this->prepared->rowCount();
      $this->prepared = NULL;
      return $this->count;
    }
    /**
     * @return DB_Query_Result
     */
    public function asObject() {
      $this->prepared->setFetchMode(\PDO::FETCH_OBJ);
      return $this;
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current() {
      return $this->current;
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next() {
      $this->current = $this->prepared->fetch();
      ++$this->cursor;
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar scalar on success, integer
     * 0 on failure.
     */
    public function key() {
      return $this->cursor;
    }
    /**
     * @return mixed
     */
    public function valid() {
      if (!$this->current) {
        $this->valid = FALSE;
      }
      return $this->valid;
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind() {
      if ($this->cursor > -1) {
        $this->prepared->closeCursor();
      }
      $this->execute();
      $this->next();
    }
    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     *       The return value is cast to an integer.
     */
    public function count() {
      return $this->count;
    }
    /**
     * @return mixed
     */
    public function __toString() {
      if ($this->cursor === 0) {
        $this->next();
      }
      return var_export($this->current(), TRUE);
    }
  }
