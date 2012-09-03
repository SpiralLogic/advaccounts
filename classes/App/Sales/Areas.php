<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      1/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Sales;

  use ADV\Core\DB\DB;

  /**

   */
  class Areas extends \ADV\App\DB\Base
  {
    protected $_id_column = 'area_code';
    protected $_table = 'areas';
    protected $_classname = 'Sales Area';
    public $description;
    public $area_code;
    public $inactive=0;

    /**
     * @return bool
     */
    protected function canProcess() {
      if (strlen($this->description)==0){
        return $this->status(false,"save","You must not be too short",'description');
      }
      return true;
    }
    /**
     * @param bool $inactive
     *
     * @return mixed
     */
    public static function getAll($inactive = false) {
      $sql = "SELECT * FROM areas";
      DB::_query($sql, 'Could not fetch Sales Areas');
      return DB::_fetchAll();
    }
  }
