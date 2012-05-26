<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class DB_Company extends DB_abstract
  {
    /**
     * @var int
     */
    public $id = 0;
    /**
     * @var
     */
    public $coy_code;
    /**
     * @var
     */
    public $coy_name;
    /**
     * @var
     */
    public $gst_no;
    /**
     * @var
     */
    public $coy_no;
    /**
     * @var
     */
    public $tax_prd;
    /**
     * @var
     */
    public $tax_last;
    /**
     * @var
     */
    public $postal_address;
    /**
     * @var
     */
    public $phone;
    /**
     * @var
     */
    public $fax;
    /**
     * @var
     */
    public $email;
    /**
     * @var
     */
    public $coy_logo;
    /**
     * @var
     */
    public $suburb;
    /**
     * @var
     */
    public $curr_default;
    /**
     * @var
     */
    public $debtors_act;
    /**
     * @var
     */
    public $pyt_discount_act;
    /**
     * @var
     */
    public $creditors_act;
    /**
     * @var
     */
    public $bank_charge_act;
    /**
     * @var
     */
    public $exchange_diff_act;
    /**
     * @var
     */
    public $profit_loss_year_act;
    /**
     * @var
     */
    public $retained_earnings_act;
    /**
     * @var
     */
    public $freight_act;
    /**
     * @var
     */
    public $default_sales_act;
    /**
     * @var
     */
    public $default_sales_discount_act;
    /**
     * @var
     */
    public $default_prompt_pament_act;
    /**
     * @var
     */
    public $default_inventory_act;
    /**
     * @var
     */
    public $default_cogs_act;
    /**
     * @var
     */
    public $default_adj_act;
    /**
     * @var
     */
    public $default_inv_sales_act;
    /**
     * @var
     */
    public $default_assembly_act;
    /**
     * @var
     */
    public $payroll_act;
    /**
     * @var
     */
    public $allow_negative_stock;
    /**
     * @var
     */
    public $po_over_receive;
    /**
     * @var
     */
    public $po_over_charge;
    /**
     * @var
     */
    public $default_credit_limit;
    /**
     * @var
     */
    public $default_workorder_required;
    /**
     * @var
     */
    public $default_dim_required;
    /**
     * @var
     */
    public $past_due_days;
    /**
     * @var
     */
    public $use_dimension;
    /**
     * @var
     */
    public $f_year;
    /**
     * @var
     */
    public $no_item_list;
    /**
     * @var
     */
    public $no_customer_list;
    /**
     * @var
     */
    public $no_supplier_list;
    /**
     * @var
     */
    public $base_sales;
    /**
     * @var
     */
    public $foreign_codes;
    /**
     * @var
     */
    public $accumulate_shipping;
    /**
     * @var
     */
    public $legal_text;
    /**
     * @var
     */
    public $default_delivery_required;
    /**
     * @var
     */
    public $version_id;
    /**
     * @var
     */
    public $time_zone;
    /**
     * @var
     */
    public $custom0_name;
    /**
     * @var
     */
    public $custom0_value;
    /**
     * @var
     */
    public $add_pct;
    /**
     * @var
     */
    public $round_to;
    /**
     * @var
     */
    public $login_tout;
    /**
     * @param int $id
     */
    public function __construct($id = 0)
    {
      parent::__construct($id);
      $this->id = &$this->coy_code;
    }
    /**
     * @param array|null $changes
     *
     * @return array|bool|int|null|Status
     */
    public function save($changes = null)
    {
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
      $result = DB::update('company')->values((array) $this)->where('coy_code=', $this->id)->exec();
      DB::commit();
      $_SESSION['config']['company'] = $this;

      return $this->_status(true, 'Processing', "Company has been updated.");
    }
    public function delete()
    {
      // TODO: Implement delete() method.
    }
    /**
     * @return bool
     */
    protected function _canProcess()
    {
      return true;
      // TODO: Implement _canProcess() method.
    }
    protected function _defaults()
    {
      // TODO: Implement _defaults() method.
    }
    protected function _new()
    {
      // TODO: Implement _new() method.
    }
    /**
     * @param int|null $id
     *
     * @return bool|void
     */
    protected function _read($id = 0)
    {
      $result = DB::select()->from('company')->where('coy_code=', $id)->fetch()->intoObject($this);
    }
    /**
     * @return bool|int|void
     */
    protected function _saveNew()
    {
      // TODO: Implement _saveNew() method.
    }
    /***
     * @var DB_Company
     */
    protected static $i = null;
    /***
     * @static
     *
     * @param null $id
     *
     * @return DB_Company
     */
    public static function i($id = null)
    {
      if (static::$i === null) {
        if (isset($_POST['login_comapny'])) {
          $company = Config::get('db.' . $_POST['login_company']);
        }
        if (!isset($company)) {
          $id      = $id ? : User::i()->company;
          $company = Config::get('db.' . $id);
        }
        $id        = $company['id'];
        static::$i = isset($_SESSION['config']['company']) ? $_SESSION['config']['company'] : new static($id);
      }

      return static::$i;
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
    public static function add_fiscalyear($from_date, $to_date, $closed)
    {
      $from = Dates::date2sql($from_date);
      $to   = Dates::date2sql($to_date);
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
    public static function add_payment_terms($daysOrFoll, $terms, $dayNumber)
    {
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
    public static function delete_fiscalyear($id)
    {
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
    public static function delete_payment_terms($selected_id)
    {
      DB::query("DELETE FROM payment_terms WHERE terms_indicator=" . DB::escape($selected_id) . " could not delete a payment terms");
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    public static function get_all_fiscalyears()
    {
      $sql = "SELECT * FROM fiscal_year ORDER BY begin";

      return DB::query($sql, "could not get all fiscal years");
    }
    /**
     * @static
     * @return mixed
     */
    public static function get_base_sales_type()
    {
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
    public static function get_company_extensions($id = -1)
    {
      $file                 = BASE_URL . ($id == -1 ? '' : 'company/' . $id) . '/installed_extensions.php';
      $installed_extensions = array();
      if (is_file($file)) {
        include($file);
      }

      return $installed_extensions;
    }
    /**
     * @static
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get_current_fiscalyear()
    {
      $year   = DB_Company::get_pref('f_year');
      $sql    = "SELECT * FROM fiscal_year WHERE id=" . DB::escape($year);
      $result = DB::query($sql, "could not get current fiscal year");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get_fiscalyear($id)
    {
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
    public static function get_pref($pref_name)
    {
      $prefs = (static::$i === null) ? DB_Company::get_prefs() : (array) $_SESSION['config']['company'];

      return $prefs[$pref_name];
    }
    /**
     * @static
     * @return array
     */
    public static function get_prefs()
    {
      if (static::$i === null) {
        if (!isset($_SESSION['config']['company'])) {
          $_SESSION['config']['company'] = static::i();
        }
      }

      return (array) $_SESSION['config']['company'];
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
    public static function update_payment_terms($selected_id, $daysOrFoll, $terms, $dayNumber)
    {
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
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get_payment_terms($selected_id)
    {
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
    public static function get_payment_terms_all($show_inactive)
    {
      $sql = "SELECT * FROM payment_terms";
      if (!$show_inactive) {
        $sql .= " WHERE !inactive";
      }

      return DB::query($sql, "could not get payment terms");
    }
    /*
                       Return number of records in tables, where some foreign key $id is used.
                       $id - searched key value
                       $tables - array of table names (without prefix); when table name is used as a key, then
                         value is name of foreign key field. For numeric keys $stdkey field name is used.
                       $stdkey - standard name of foreign key.
                     */
    /**
     * @static
     *
     * @param      $id
     * @param      $tables
     * @param      $stdkey
     * @param bool $escaped
     *
     * @return mixed
     */
    public static function key_in_foreign_table($id, $tables, $stdkey, $escaped = false)
    {
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
    public static function update_fiscalyear($id, $closed)
    {
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
    public static function update_gl_setup(array $data = null)
    {
      static::i()->save($data);
    }
    /**
     * @static
     *
     * @param array|null $data
     *
     * @return void
     */
    public static function update_setup(array $data = null)
    {
      if (static::i()->f_year == null) {
        static::$i->f_year = 0;
      }
      static::$i->save($data);
    }
  }
