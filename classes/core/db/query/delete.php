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
  use PDO, PDOStatement, PDOException, PDORow;

  /**

   */
  class Query_Delete extends Query
  {

    /**
     * @var bool
     */
    protected $table;
    /**
     * @param bool $table
     * @param      $db
     */
    public function __construct($table = false, $db) {
      $this->table = $table;
      $this->type  = DB::DELETE;
      parent::__construct($db);
    }
    /**
     * @return string
     */
    protected function execute() {
      return $this->_buildQuery();
    }
    /**
     * @return string
     */
    protected function _buildQuery() {
      $sql = "DELETE FROM " . $this->table;
      $sql .= $this->_buildWhere();
      return $sql;
    }
  }
