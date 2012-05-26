<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Debtor_Branch extends DB_abstract
  {
    /**
     * @var string
     */
    public $post_address = '';
    /**
     * @var int
     */
    public $branch_id = 0;
    /**
     * @var string
     */
    public $br_name = "New Address";
    /**
     * @var string
     */
    public $br_address = '';
    /**
     * @var string
     */
    public $city = '';
    /**
     * @var string
     */
    public $state = '';
    /**
     * @var string
     */
    public $postcode = '';
    /**
     * @var int
     */
    public $area = DEFAULT_AREA;
    /**
     * @var
     */
    public $br_post_address;
    /**
     * @var
     */
    public $debtor_id;
    /**
     * @var string
     */
    public $branch_ref = "New";
    /**
     * @var string
     */
    public $contact_name = "";
    /**
     * @var
     */
    public $default_location;
    /**
     * @var int
     */
    public $default_ship_via = DEFAULT_SHIP_VIA;
    /**
     * @var int
     */
    public $disable_trans = 0;
    /**
     * @var string
     */
    public $phone = '';
    /**
     * @var string
     */
    public $phone2 = '';
    /**
     * @var string
     */
    public $fax = '';
    /**
     * @var string
     */
    public $website = '';
    /**
     * @var string
     */
    public $email = '';
    /**
     * @var int
     */
    public $inactive = 0;
    /**
     * @var string
     */
    public $notes = '';
    /**
     * @var int
     */
    public $group_no = 1;
    /**
     * @var
     */
    public $payment_discount_account;
    /**
     * @var
     */
    public $receivables_account;
    /**
     * @var string
     */
    public $sales_account = "";
    /**
     * @var
     */
    public $sales_discount_account;
    /**
     * @var
     */
    public $salesman;
    /**
     * @var int
     */
    public $tax_group_id = DEFAULT_TAX_GROUP;
    /**
     * @var string
     */
    protected $_table = 'branches';
    /**
     * @var string
     */
    protected $_id_column = 'branch_id';
    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
      $this->id = &$this->branch_id;
      parent::__construct($id);
      $this->name         = &$this->br_name;
      $this->address      = &$this->br_address;
      $this->post_address = &$this->br_post_address;
    }
    /**
     * @return array|null
     */
    public function delete()
    {
      DB::delete('branches')->where('branch_id=', $this->branch_id)->exec();
      $this->_new();

      return $this->_status(true, 'delete', "Branch deleted.");
    }
    /**
     * @return string
     */
    public function getAddress()
    {
      $address = $this->br_address . "\n";
      if ($this->city) {
        $address .= $this->city;
      }
      if ($this->state) {
        $address .= ", " . strtoupper($this->state);
      }
      if ($this->postcode) {
        $address .= ", " . $this->postcode;
      }

      return $address;
    }
    /**
     * @return array|bool|null
     */
    protected function _canProcess()
    {
      if (strlen($this->br_name) < 1) {
        return $this->_status(false, 'write', 'Branch name can not be empty');
      }

      return true;
    }
    /**
     * @return void
     */
    protected function _countTransactions()
    {
    }
    /**
     * @return void
     */
    protected function _defaults()
    {
      $company_record                 = DB_Company::get_prefs();
      $this->branch_id                = 0;
      $this->default_location         = Config::get('default.location');
      $this->sales_discount_account   = $company_record['default_sales_discount_act'];
      $this->receivables_account      = $company_record['debtors_act'];
      $this->payment_discount_account = $company_record['default_prompt_payment_act'];
      $this->salesman                 = (User::i()) ? User::i()->salesmanid : 1;
    }
    /**
     * @return array|null
     */
    protected function _new()
    {
      $this->_defaults();

      return $this->_status(true, 'new', 'Now working with a new Branch');
    }
    /**
     * @param null $changes
     *
     * @return array|null|void
     */
    protected function setFromArray($changes = null)
    {
      parent::setFromArray($changes);
      if (!empty($this->city)) {
        $this->br_name = $this->city . " " . strtoupper($this->state);
      }
      if ($this->branch_ref != 'accounts') {
        $this->branch_ref = substr($this->br_name, 0, 30);
      }
    }
    /**
     * @param bool|int|null $params
     *
     * @return array|bool|null
     */
    protected function _read($params = false)
    {
      if (!$params) {
        return $this->_status(false, 'read', 'No Branch parameters provided');
      }
      $this->_defaults();
      if (!is_array($params)) {
        $params = array('branch_id' => $params);
      }
      $sql = DB::select('b.*', 'a.description', 's.salesman_name', 't.name AS tax_group_name')
        ->from('branches b, debtors c, areas a, salesman s, tax_groups t')->where(array(
                                                                                       'b.debtor_id=c.debtor_id',
                                                                                       'b.tax_group_id=t.id',
                                                                                       'b.area=a.area_code',
                                                                                       'b.salesman=s.salesman_code'
                                                                                  ));
      foreach ($params as $key => $value) {
        $sql->where("b.$key=", $value);
      }
      DB::fetch()->intoClass($this);

      return $this->_status(true, 'read', 'Read Branch from Database');
    }
    /**
     * @static
     *
     * @param      $customer_id
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_option
     * @param bool $enabled
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return string
     */
    public static function select($customer_id, $name, $selected_id = null, $spec_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
    {
      $sql
        = "SELECT branch_id, branch_ref FROM branches
            WHERE branch_ref <> 'accounts' AND inactive <> 1  AND debtor_id='" . $customer_id . "' ";
      $where = $enabled ? array("disable_trans = 0") : array();

      return select_box($name, $selected_id, $sql, 'branch_id', 'br_name', array(
                                                                                'where'         => $where,
                                                                                'order'         => array('branch_ref'),
                                                                                'spec_option'   => $spec_option === true ?
                                                                                  _('All branches') : $spec_option,
                                                                                'spec_id'       => ALL_TEXT,
                                                                                'select_submit' => $submit_on_change,
                                                                                'sel_hint'      => _('Select customer branch')
                                                                           ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $customer_id
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $enabled
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return void
     */
    public static function cells($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Debtor_Branch::select($customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $customer_id
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $enabled
     * @param bool $submit_on_change
     * @param bool $editkey
     *
     * @return void
     */
    public static function row($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Debtor_Branch::cells(null, $customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
      echo "</tr>";
    }
  }

