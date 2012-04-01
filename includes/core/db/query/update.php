<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Core\DB;
  use PDO, PDOStatement, PDOException, PDORow;

  /**

   */
  class Query_Update extends Query_Insert {

    /**
     * @param bool $table
     * @param      $db
     */
    public function __construct($table = FALSE, $db) {
      parent::__construct($table, $db);
      $this->type = DB::UPDATE;
    }
    /**
     * @return string
     */
    protected function _buildQuery() {
      $sql = "UPDATE " . $this->table . " SET ";
      foreach ($this->fields as &$feild) {
        $feild = " $feild = :$feild";
      }
      $sql .= implode(', ', $this->fields);
      $sql .= $this->_buildWhere();
      return $sql;
    }
  }
