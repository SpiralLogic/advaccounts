<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Validation {

    const CUSTOMERS = "debtors";
    const CURRENCIES = "currencies";
    const SALES_TYPES = "sales_types";
    const ITEM_TAX_TYPES = "item_tax_types";
    const TAX_TYPES = "tax_types";
    const TAX_GROUP = "tax_groups";
    const MOVEMENT_TYPES = "movement_types";
    const BRANCHES = "branches WHERE debtor_no=";
    const BRANCHES_ACTIVE = "branches WHERE !inactive";
    const SALESPERSONS = "salesman";
    const SALES_AREA = "areas";
    const SHIPPERS = "shippers";
    const OPEN_WORKORDERS = "workorders WHERE closed=0";
    const WORKORDERS = "workorders";
    const OPEN_DIMENSIONS = "dimensions WHERE closed=0";
    const DIMENSIONS = "dimensions";
    const SUPPLIERS = "suppliers";
    const STOCK_ITEMS = "stock_master";
    const BOM_ITEMS = "stock_master WHERE mb_flag=";
    const MANUFACTURE_ITEMS = "stock_master WHERE mb_flag=";
    const PURCHASE_ITEMS = "stock_master WHERE mb_flag=";
    const COST_ITEMS = "stock_master WHERE mb_flag!=";
    const STOCK_CATEGORIES = "stock_category";
    const WORKCENTRES = "workcentres";
    const LOCATIONS = "locations";
    const BANK_ACCOUNTS = "bank_accounts";
    const CASH_ACCOUNTS = "bank_accounts";
    const  GL_ACCOUNTS = "chart_master";
    const GL_ACCOUNT_GROUPS = "chart_types";
    const QUICK_ENTRIES = "quick_entries";
    const TAGS = "FROM tags WHERE type=";
    const EMPTY_RESULT = "";
    /**
     * @static
     *
     * @param        $validate
     * @param string $msg
     * @param null   $extra
     * @param null   $default
     *
     * @return int|null
     * @throws Adv_Exception
     */
    static public function check($validate, $msg = '', $extra = NULL, $default = NULL) {
      if ($extra === FALSE) {
        return 0;
      }
      $cachekey = 'validation.' . md5($validate . $extra);
      if (Cache::get($cachekey)) {
        return 1;
      }
      if ($extra !== NULL) {
        if (empty($extra)) {
          return $default;
        }
        if (is_string($extra)) {
          $extra = DB::escape($extra);
        }
      }
      else {
        $extra = '';
      }

      $result = DB::query('SELECT COUNT(*) FROM ' . $validate . ' ' . $extra, 'Could not do check empty query');
      $myrow = DB::fetch_row($result);
      if (!($myrow[0] > 0)) {
        throw new Adv_Exception($msg);
      }
      else {
        Cache::set($cachekey, TRUE);
        return $myrow[0];
      }
    }
    //
    //	Integer input check
    //	Return 1 if number has proper form and is within <min, max> range
    //
    /**
     * @static
     *
     * @param      $postname
     * @param null $min
     * @param null $max
     *
     * @return bool|int
     */
    static public function is_int($postname, $min = NULL, $max = NULL) {
      if (!isset($_POST) || !isset($_POST[$postname])) {
        return 0;
      }
      $options = array();
      if ($min !== NULL) {
        $options['min_range'] = $min;
      }
      if ($max !== NULL) {
        $options['max_range'] = $max;
      }
      $result = filter_var($_POST[$postname], FILTER_VALIDATE_INT, $options);
      return ($result === FALSE || $result === NULL) ? FALSE : 1;
    }
    //
    //	Numeric input check.
    //	Return 1 if number has proper form and is within <min, max> range
    //	Empty/not defined fields are defaulted to $dflt value.
    //
    /**
     * @static
     *
     * @param      $postname
     * @param null $min
     * @param null $max
     * @param int  $default
     *
     * @return int
     */
    static public function is_num($postname, $min = NULL, $max = NULL, $default = 0) {
      if (!isset($_POST) || !isset($_POST[$postname])) {
        $_POST[$postname] = $default;
      }
      $result = filter_var($_POST[$postname], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
      if ($min !== NULL && $result < $min) {
        $result = FALSE;
      }
      if ($max !== NULL && $result > $max) {
        $result = FALSE;
      }
      return ($result === FALSE || $result === NULL) ? $default : 1;
    }
    /**
     *   Read numeric value from user formatted input
     *
     * @param null $postname
     * @param int  $default
     *
     * @param null $min
     * @param null $max
     *
     * @internal param int $dflt
     * @return bool|float|int|mixed|string
     */
    static public function input_num($postname = NULL, $default = 0, $min = NULL, $max = NULL) {
      if (!isset($_POST) || !isset($_POST[$postname])) {
        $_POST[$postname] = $default;
      }
      $result = filter_var($_POST[$postname], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
      if ($min !== NULL && $result < $min) {
        $result = FALSE;
      }
      if ($max !== NULL && $result > $max) {
        $result = FALSE;
      }
      return ($result === FALSE || $result === NULL) ? 0 : User::numeric($result);
    }
  }
