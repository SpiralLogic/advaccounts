<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   *
   **/
  Class DB_Query_Insert extends DB_Query {
    /**
     * @var
     */
    protected $table;
    /**
     * @var array
     */
    protected $values = array();
    /**
     * @var array
     */
    protected $fields = array();
    /**
     * @var array
     */
    protected $hasfields = array();
    /**
     * @var array
     */
    public $data = array();
    /**
     * @param bool $table
     * @param      $db
     */
    public function __construct($table = FALSE, $db) {
      parent::__construct($db);
      if ($table) {
        $this->into($table);
      }
      $this->type = DB::INSERT;
      $this->hasfields = Cache::get('INFORMATION_SCHEMA.COLUMNS.' . $table);
      if (!$this->hasfields) {
        $query = DB::query('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = ' . DB::quote($table), FALSE);
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
          $this->hasfields[] = $row['COLUMN_NAME'];
        }
        Cache::set('INFORMATION_SCHEMA.COLUMNS.' . $table, $this->hasfields);
      }
      return $this;
    }
    /**
     * @param $table
     *
     * @return DB_Query_Insert
     */
    public function into($table) {
      $this->table = $table;
      return $this;
    }
    /**
     * @param $values array key pair
     *
     * @return DB_Query_Insert|DB_Query_Update
     */
    public function values($values) {
      $this->data = (array) $values + $this->data;
      return $this;
    }
    /**
     * @param $feild
     * @param $value
     *
     * @return DB_Query_Insert
     * @throws Adv_Exception
     */
    public function value($feild, $value) {
      if (is_array($feild) && is_array($value)) {
        if (count($feild) != count($value)) {
          throw new Adv_Exception('Feild count and Value count unequal');
        }
        else {
          $this->values(array_combine($feild, $value));
        }
      }
      elseif (is_array($feild) && !is_array($value)) {
        $values = array_fill(0, count($feild), $value);
        $this->values(array_combine($feild, $values));
      }
      else {
        $this->values(array($feild => $value));
      }
      return $this;
    }
    /**
     * @param null $data
     *
     * @return string
     */
    protected function execute($data = NULL) {
      if ($this->data !== NULL) {
        $this->values((array) $data);
      }
      $this->data = array_intersect_key($this->data, array_flip($this->hasfields));
      $this->fields = array_keys($this->data);
      return $this->_buildQuery();
    }
    /**
     * @return string
     */
    protected function _buildQuery() {
      $sql = "INSERT INTO " . $this->table . " (";
      $sql .= implode(', ', $this->fields) . ") VALUES (";
      $sql .= ':' . implode(', :', str_replace('-', '_', $this->fields));
      $sql .= ') ';
      return $sql;
    }
  }
