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


  class Query_Select extends Query {

    /**
     * @var array
     */
    protected $select = array();
    /**
     * @var array
     */
    protected $from = array();
    /**
     * @var array
     */
    protected $limit = array();
    /**
     * @var array
     */
    protected $orderby = array();
    /**
     * @var array
     */
    protected $groupby = array();
    /**
     * @var array
     */
    protected $union = array();
    /**
     * @var array
     */
    protected $union_or = array();
    /***
     * @param string $columns,... Database columns to select
     * @param        DB_C
     *
     * @return DB_Query_Select
     */
    public function __construct($columns, $db) {
      parent::__construct($db);
      $this->type = DB::SELECT;
      call_user_func_array(array($this, 'select'), $columns);
    }
    /***
     * @param mixed ... Database columns to select
     *
     * @return DB_Query_Select
     */
    public function select() {
      $columns = func_get_args();
      $this->select = array_merge($this->select, $columns);
      return $this;
    }
    /***
     * @param null $tables
     *
     * @return DB_Query_Select
     */
    public function from($tables = NULL) {
      if (is_null($tables)) {
        return $this;
      }
      $tables = func_get_args();
      $this->from = array_merge($this->from, $tables);
      return $this;
    }
    /**
     * @param null $by
     *
     * @return DB_Query_Select
     */
    function orderby($by = NULL) {
      if (is_null($by)) {
        return $this;
      }
      $by = func_get_args();
      $this->orderby = array_merge($this->orderby, $by);
      return $this;
    }
    /**
     * @param null $by
     *
     * @return DB_Query_Select
     */
    function groupby($by = NULL) {
      if (is_null($by)) {
        return $this;
      }
      $by = func_get_args();
      $this->groupby = array_merge($this->groupby, $by);
      return $this;
    }
    /**
     * @param      $start
     * @param null $quantity
     *
     * @return DB_Query_Select
     */
    public function limit($start = 0, $quantity = NULL) {
      $this->limit = ($quantity == NULL) ? $start : "$start, $quantity";
      return $this;
    }
    /**
     * @return DB_Query_Select
     */
    public function union() {
      $this->union[] = '(' . $this->_buildQuery() . ')';
      $this->select = $this->from = $this->orderby = $this->groupby = array();
      $this->limit = '';
      $this->resetWhere();
      return $this;
    }
    /**
     * @param $condition
     * @param $var
     */
    public function union_or($condition, $var) {
      $this->union_or[$condition] = $var;
    }
    /**
     * @return string
     */
    protected function execute() {
      if ($this->union) {
        return implode(' UNION ', $this->union);
      }
      return $this->_buildQuery();
    }
    /**
     * @return string
     */
    protected function _buildQuery() {
      $sql = "SELECT ";
      $sql .= (empty($this->select)) ? '*' : implode(', ', $this->select);
      $sql .= " FROM " . implode(', ', $this->from);
      $sql .= parent::_buildWhere();
      if (!empty($this->union_or)) {
        //$data = $this->data;
        $finalsql = array();
        foreach ($this->union_or as $k => $v) {
          $finalsql[] = $sql . ' AND ' . $k . ' ' . $v;
        }
      }
      if (!empty($this->groupby)) {
        $sql .= ' GROUP BY ' . implode(', ', $this->groupby);
      }
      if (!empty($this->orderby)) {
        $sql .= ' ORDER BY ' . implode(', ', $this->orderby);
      }
      if (!empty($this->limit)) {
        $sql .= ' LIMIT ' . $this->limit;
      }
      return $sql;
    }
  }

