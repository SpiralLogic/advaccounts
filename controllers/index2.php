<?php
  namespace ADV\Core\DB;

  /**

   */
  class Generator
  {
    /** @var \ADV\Core\DB\DB  */
    protected $DB;
    protected $vars = [];
    protected $valids = [];
    protected $name;
    protected $inactive = '';
    protected $id_column;
    /**

     */
    public function __construct() {
      $this->DB = DB::i();
    }
    /**
     * @param $table
     */
    public function getTableDetails($table) {
      $this->vars[] = 'protected $_table = \'' . $table . '\';';
      $this->name   = ucfirst($table);
      $this->vars[] = 'protected $_classname = \'' . $this->name . '\';';
      $table        = $this->DB->select()->from('INFORMATION_SCHEMA.COLUMNS')->where('TABLE_NAME=', $table)->fetch();
      foreach ($table as $row) {
        $this->findVars($row);
        $this->findValids($row);
        if ($row['COLUMN_NAME'] == 'inactive') {
          $this->inactive
            = 'if ($inactive) {
                  $q->andWhere(\'inactive=\',1);
                }';
        }
      }
      echo nl2br($this->generate());
    }
    protected function findVars($row) {
      $name = $row['COLUMN_NAME'];
      if ($row['COLUMN_KEY'] === 'PRI') {
        $this->vars[]    = 'protected $_id_column = \'' . $name . '\';' . PHP_EOL;
        $this->id_column = $name;
      }
      $var = 'public $' . $name;
      if ($row['COLUMN_DEFAULT'] !== '') {
        $var .= ' = ' . $row['COLUMN_DEFAULT'];
      }
      $var .= ';';
      $this->vars[] = $var;
    }
    protected function findValids($row) {
      if ($row['COLUMN_NAME'] == $this->id_column) {
        return;
      }
      $name = $row['COLUMN_NAME'];
      if (in_array($row['DATA_TYPE'], ['int', 'float', 'double', 'decimal'])) {
        $min            = (strpos($row['COLUMN_TYPE'], 'unsigned')) ? '0' : 'null';
        $this->valids[] = 'if (!Validation::is_num($this->' . $name . ', ' . $min . ')){
            return $this->status(false,\'' . ucfirst($name) . ' must be a number\',\'' . $name . '\');' . PHP_EOL . '}' . PHP_EOL;
      }
      if (in_array($row['DATA_TYPE'], ['varchar', 'text', 'char'])) {
        if ($row['NULLABLE'] === 'NO' && $row['COLUMN_DEFAULT'] === 'NONE') {
          $this->valids[] = 'if (empty($this->' . $name . ')){
            return $this->status(false,\'' . ucfirst($name) . ' must be not be empty\',\'' . $name . '\');' . PHP_EOL . '}' . PHP_EOL;
        }
        if ($row['CHARACTER_MAXIMUM_LENGTH'] !== '') {
          $len            = $row['CHARACTER_MAXIMUM_LENGTH'];
          $this->valids[] = 'if (strlen($this->' . $name . ')>' . $len . '){
                  return $this->status(false,\'' . ucfirst(
            $name
          ) . ' must be not be longer than ' . $len . ' characters!\',\'' . $name . '\');' . PHP_EOL . '}' . PHP_EOL;
        }
      }
    }
    protected function hasInactive($row) {
    }
    public function generate() {
      $name     = $this->name;
      $vars     = implode("\n", $this->vars) . PHP_EOL;
      $valids   = implode("", $this->valids) . PHP_EOL;
      $inactive = $this->inactive . PHP_EOL;

      return <<<CLASS
      <?php
  use ADV\Core\DB\DB;
  use ADV\App\Validation;
/**

   */
class $name extends \ADV\App\DB\Base {

  $vars
  /**
       * @return \ADV\Core\Traits\Status|bool
       */
  protected function canProcess() {
    $valids
    return true;
  }

  /**
  * @param bool \$inactive
  *
  * @return array
  */
      public static function getAll(\$inactive = false) {
        \$q = DB::_select()->from('$name');
        $inactive
        return \$q->fetch()->all();
      }
}
CLASS;
    }
  }

  $test = new Generator();
  $test->getTableDetails('locations');
