<?php
  namespace ADV\App\Sales;

  use ADV\Core\DB\DB;
  use ADV\App\Validation;

  /**

   */
  class Areas extends \ADV\App\DB\Base
  {
    protected $_table = 'areas';
    protected $_classname = 'Areas';
    protected $_id_column = 'area_code';
    public $area_code;
    public $description;
    public $inactive = 0;
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (empty($this->description)) {
        return $this->status(false, 'Description must be not be empty', 'description');
      }
      if (strlen($this->description) > 60) {
        return $this->status(false, 'Description must be not be longer than 60 characters!', 'description');
      }

      return true;
    }
    /**
     * @param bool $inactive
     *
     * @return array
     */
    public static function getAll($inactive = false) {
      $q = DB::_select()->from('areas');
      if ($inactive) {
        $q->andWhere('inactive=', 1);
      }

      return $q->fetch()->all();
    }
  }
