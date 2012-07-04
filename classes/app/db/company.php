<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\Core\Traits\StaticAccess;

  /**
   * @property null i
   * @method DB_Company i()
   * @method get_pref($pref_name)
   * @method get_current_fiscalyear()
   */
  class DB_Company extends DB_Base
  {

    use StaticAccess;

    /**
     * @var int
     */
    public $id = 0;
    public $coy_code;
    public $coy_name;
    public $gst_no;
    public $coy_no;
    public $tax_last;
    public $postal_address;
    public $phone;
    public $fax;
    public $email;
    public $coy_logo;
    public $suburb;
    public $curr_default;
    public $debtors_act;
    public $pyt_discount_act;
    public $bank_charge_act;
    public $exchange_diff_act;
    public $profit_loss_year_act;
    public $retained_earnings_act;
    public $freight_act;
    public $default_sales_act;
    public $default_sales_discount_act;
    public $default_prompt_pament_act;
    public $default_inventory_act;
    public $default_cogs_act;
    public $default_adj_act;
    public $default_inv_sales_act;
    public $default_assembly_act;
    public $payroll_act;
    public $allow_negative_stock;
    public $po_over_receive;
    public $po_over_charge;
    public $default_credit_limit;
    public $default_workorder_required;
    public $default_dim_required;
    public $past_due_days;
    public $use_dimension;
    public $f_year;
    public $no_item_list;
    public $no_customer_list;
    public $no_supplier_list;
    public $base_sales;
    public $foreign_codes;
    public $accumulate_shipping;
    public $legal_text;
    public $default_delivery_required;
    public $version_id;
    public $time_zone;
    public $custom0_name;
    public $custom0_value;
    public $add_pct;
    public $round_to;
    public $login_tout;
    /**
     * @param int $name
     *
     * @internal param int $id
     */
    public function __construct($name = 0) {
      $name    = $name ? : User::i()->company;
      $company = Config::get('db.' . Input::post('login_company', null, $name));
      parent::__construct($company);
      $this->id = &$this->coy_code;
    }
    /**
     * @param array|null $changes
     *
     * @return array|bool|int|null|Status
     */
    public function save($changes = null) {
      if (is_array($changes)) {
        $this->setFromArray($changes);
      }
      if (!$this->_canProcess()) {
        return false;
      }
      if ($this->id == 0) {
        $this->_saveNew();
      }
      DB::begin();
      DB::update('company')->values((array) $this)->where('coy_code=', $this->id)->exec();
      DB::commit();
      $_SESSION['config']['company'] = $this;
      return $this->_status(true, 'Processing', "Company has been updated.");
    }
    public function delete() {
      // TODO: Implement delete() method.
    }
    /**
     * @return bool
     */
    protected function _canProcess() {
      return true;
      // TODO: Implement _canProcess() method.
    }
    protected function _defaults() {
      // TODO: Implement _defaults() method.
    }
    protected function _new() {
      // TODO: Implement _new() method.
    }
    /**
     * @param int|null $id
     * @param array    $extra
     *
     * @return bool|void
     */
    protected function _read($id = null, $extra = array()) {
      $id = $id ? : 0;
      DB::select()->from('company')->where('coy_code=', $id)->fetch()->intoObject($this);
    }
    /**
     * @return bool|int|void
     */
    protected function _saveNew() {
      // TODO: Implement _saveNew() method.
    }
    /**
     * @static
     *
     * @param $from_date
     * @param $to_date
     * @param $closed
     *
     * @return void
     */
    public function _add_fiscalyear($from_date, $to_date, $closed) {
      $from = Dates::dateToSql($from_date);
      $to   = Dates::dateToSql($to_date);
      $sql
            = "INSERT INTO fiscal_year (begin, end, closed)
 VALUES (" . DB::escape($from) . "," . DB::escape($to) . ", " . DB::escape($closed) . ")";
      DB::query($sql, "could not add fiscal year");
    }
    /**
     * @static
     *
     * @param $daysOrFoll
     * @param $terms
     * @param $dayNumber
     *
     * @return void
     */
    public function _add_payment_terms($daysOrFoll, $terms, $dayNumber) {
      if ($daysOrFoll) {
        $sql
          = "INSERT INTO payment_terms (terms,
 days_before_due, day_in_following_month)
 VALUES (" . DB::escape($terms) . ", " . DB::escape($dayNumber) . ", 0)";
      } else {
        $sql
          = "INSERT INTO payment_terms (terms,
 days_before_due, day_in_following_month)
 VALUES (" . DB::escape($terms) . ",
 0, " . DB::escape($dayNumber) . ")";
      }
      DB::query($sql, "The payment term could not be added");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return void
     */
    public function _delete_fiscalyear($id) {
      DB::begin();
      $sql = "DELETE FROM fiscal_year WHERE id=" . DB::escape($id);
      DB::query($sql, "could not delete fiscal year");
      DB::commit();
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return void
     */
    public function _delete_payment_terms($selected_id) {
      DB::query("DELETE FROM payment_terms WHERE terms_indicator=" . DB::escape($selected_id) . " could not delete a payment terms");
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    public function _getAll_fiscalyears() {
      $sql = "SELECT * FROM fiscal_year ORDER BY begin";
      return DB::query($sql, "could not get all fiscal years");
    }
    /**
     * @static
     * @return mixed
     */
    public function _get_base_sales_type() {
      $sql    = "SELECT base_sales FROM company WHERE coy_code=1";
      $result = DB::query($sql, "could not get base sales type");
      $myrow  = DB::fetch($result);
      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return array
     */
    public function _get_company_extensions($id = -1) {
      $file                 = BASE_URL . ($id == -1 ? '' : 'company/' . $id) . '/installed_extensions.php';
      $installed_extensions = array();
      if (is_file($file)) {
        include($file);
      }
      return $installed_extensions;
    }
    /**
     * @static
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function _get_current_fiscalyear() {
      $year   = $this->_get_pref('f_year');
      $sql    = "SELECT * FROM fiscal_year WHERE id=" . DB::escape($year);
      $result = DB::query($sql, "could not get current fiscal year");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function _get_fiscalyear($id) {
      $sql    = "SELECT * FROM fiscal_year WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not get fiscal year");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $pref_name
     *
     * @return mixed
     */
    public function _get_pref($pref_name) {
      $prefs = (array) $this;
      return $prefs[$pref_name];
    }
    /**
     * @static
     * @return array
     */
    public function _get_prefs() {
      return (array) $this;
    }
    /**
     * @static
     *
     * @param $selected_id
     * @param $daysOrFoll
     * @param $terms
     * @param $dayNumber
     *
     * @return void
     */
    public function _update_payment_terms($selected_id, $daysOrFoll, $terms, $dayNumber) {
      if ($daysOrFoll) {
        $sql = "UPDATE payment_terms SET terms=" . DB::escape($terms) . ",
 day_in_following_month=0,
 days_before_due=" . DB::escape($dayNumber) . "
 WHERE terms_indicator = " . DB::escape($selected_id);
      } else {
        $sql = "UPDATE payment_terms SET terms=" . DB::escape($terms) . ",
 day_in_following_month=" . DB::escape($dayNumber) . ",
 days_before_due=0
 WHERE terms_indicator = " . DB::escape($selected_id);
      }
      DB::query($sql, "The payment term could not be updated");
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public function _get_payment_terms($selected_id) {
      $sql
              = "SELECT *, (t.days_before_due=0) AND (t.day_in_following_month=0) as cash_sale
 FROM payment_terms t WHERE terms_indicator=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not get payment term");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $show_inactive
     *
     * @return null|PDOStatement
     */
    public function _get_payment_terms_all($show_inactive) {
      $sql = "SELECT * FROM payment_terms";
      if (!$show_inactive) {
        $sql .= " WHERE !inactive";
      }
      return DB::query($sql, "could not get payment terms");
    }
    /**
     *  Return number of records in tables, where some foreign key $id is used.
     * $id - searched key value
     * $tables - array of table names (without prefix); when table name is used as a key, then
     * value is name of foreign key field. For numeric keys $stdkey field name is used.
     * $stdkey - standard name of foreign key.
     * @static
     *
     * @param      $id
     * @param      $tables
     * @param      $stdkey
     * @param bool $escaped
     *
     * @return mixed
     */
    public function _key_in_foreign_table($id, $tables, $stdkey, $escaped = false) {
      if (!$escaped) {
        $id = DB::escape($id);
      }
      if (!is_array($tables)) {
        $tables = array($tables);
      }
      $sqls = array();
      foreach ($tables as $tbl => $key) {
        if (is_numeric($tbl)) {
          $tbl = $key;
          $key = $stdkey;
        }
        $sqls[] = "(SELECT COUNT(*) as cnt FROM `$tbl` WHERE `$key`=" . DB::escape($id) . ")\n";
      }
      $sql    = "SELECT sum(cnt) FROM (" . implode(' UNION ', $sqls) . ") as counts";
      $result = DB::query($sql, "check relations for " . implode(',', $tables) . " failed");
      $count  = DB::fetch($result);
      return $count[0];
    }
    /**
     * @static
     *
     * @param $id
     * @param $closed
     *
     * @return void
     */
    public function _update_fiscalyear($id, $closed) {
      $sql = "UPDATE fiscal_year SET closed=" . DB::escape($closed) . "
 WHERE id=" . DB::escape($id);
      DB::query($sql, "could not update fiscal year");
    }
    /**
     * @static
     *
     * @param array|null $data
     *
     * @return void
     */
    public function _update_gl_setup(array $data = null) {
      $this->save($data);
    }
    /**
     * @static
     *
     * @param array|null $data
     *
     * @return void
     */
    public function _update_setup(array $data = null) {
      if ($this->f_year == null) {
        $this->f_year = 0;
      }
      $this->save($data);
    }
  }
