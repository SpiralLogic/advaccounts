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

  use ADV\App\Validation;
  use ADV\Core\DB\DB;
  use ADV\Core\Num;
  use ADV\App\User;

  /**

   */
  class Person extends \ADV\App\DB\Base
  {
    protected $_id_column = 'salesman_code';
    protected $_table = 'salesman';
    public $salesman_code;
    public $salesman_name;
    public $user_id;
    public $salesman_phone;
    public $salesman_fax;
    public $salesman_email;
    public $inactive;
    public $provision;
    public $break_pt;
    public $provision2;
    /**
     * @param int   $id
     * @param array $extra
     */
    public function __construct($id = 0, $extra = []) {
      parent::__construct($id, $extra);
    }
    public function delete() {
      // TODO: Implement delete() method.
    }
    /**
     * @return bool
     */
    protected function canProcess() {
      $this->provision  = Validation::input_num($this->provision, 0, 0);
      $this->break_pt   = Validation::input_num($this->break_pt, 0, $this->provision);
      $this->provision2 = Validation::input_num($this->provision2, 0, $this->break_pt);
      if ($this->provision === false) {
        return $this->status(false, 'saving', 'Provisions needs to be a number and not less than 0', 'provision');
      }
      if ($this->break_pt === false) {
        return $this->status(false, 'saving', 'Break point needs to be a number and not less than 0 and no greater than inital provision', 'break_pt');
      }
      if ($this->provision === false) {
        return $this->status(false, 'saving', 'Provisions 2 needs to be a number and not less than 0 and greater than break point');
      }

      return true;
    }
    /**
     * @param int|null $id
     * @param array    $extra
     *
     * @return bool|void
     */
    protected function read($id, $extra) {
      parent::read($id, $extra);
      $this->provision  = Num::_percentFormat($this->provision);
      $this->break_pt   = Num::_priceFormat($this->break_pt);
      $this->provision2 = Num::_percentFormat($this->provision2);
    }
    public static function getAll($inactive = false) {
      $sql = "SELECT s.*,u.user_id,u.id FROM salesman s, users u WHERE s.user_id=u.id";
      if (!$inactive) {
        $sql .= " AND !s.inactive";
      }
      DB::_query($sql, 'Could not fetch sales people');

      return DB::_fetchAll();
    }
  }
