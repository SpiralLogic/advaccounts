<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      6/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\App\Sales;

  use ADV\Core\DB\DB;
  use ADV\App\Validation;

  /**

   */
  class Types extends \ADV\App\DB\Base
  {
    protected $_table = 'sales_types';
    protected $_classname = 'Sales_types';
    protected $_id_column = 'id';
    public $id;
    public $sales_type;
    public $tax_included = 0;
    public $factor = 1.000;
    public $inactive = 0;
    /**
     * @return \ADV\Core\Traits\Status|bool
     */
    protected function canProcess() {
      if (strlen($this->sales_type) > 50) {
        return $this->status(false, 'Sales_type must be not be longer than 50 characters!', 'sales_type');
      }
      if (!Validation::is_num($this->tax_included, null)) {
        return $this->status(false, 'Tax_included must be a number', 'tax_included');
      }
      if (!Validation::is_num($this->factor, null)) {
        return $this->status(false, 'Factor must be a number', 'factor');
      }

      return true;
    }
    /**
     * @param bool $inactive
     *
     * @return array
     */
    public static function getAll($inactive = false) {
      $q = DB::_select()->from('sales_types');
      if (!$inactive) {
        $q->andWhere('inactive=', 0);
      }

      return $q->fetch()->all();
    }
  }
