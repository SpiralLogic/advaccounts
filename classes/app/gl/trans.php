<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  // Base function for adding a GL transaction
  // $date_ is display date (non-sql)
  // $amount is in $currency currency
  // if $currency is not set, then defaults to no conversion
  class GL_Trans {

    /**
     * @static
     *
     * @param        $type
     * @param        $trans_id
     * @param        $date_
     * @param        $account
     * @param        $dimension
     * @param        $dimension2
     * @param        $memo_
     * @param        $amount
     * @param null   $currency
     * @param null   $person_type_id
     * @param null   $person_id
     * @param string $err_msg
     * @param int    $rate
     *
     * @return float
     */
    public static function add($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_, $amount, $currency = NULL, $person_type_id = NULL, $person_id = NULL, $err_msg = "", $rate = 0) {
      $date = Dates::date2sql($date_);
      if ($currency != NULL) {
        if ($rate == 0) {
          $amount_in_home_currency = Bank_Currency::to_home($amount, $currency, $date_);
        }
        else {
          $amount_in_home_currency = Num::round($amount * $rate, User::price_dec());
        }
      }
      else {
        $amount_in_home_currency = Num::round($amount, User::price_dec());
      }
      if ($dimension == NULL || $dimension < 0) {
        $dimension = 0;
      }
      if ($dimension2 == NULL || $dimension2 < 0) {
        $dimension2 = 0;
      }
      if (Config::get('accounts.logs_audits')) {
        if ($memo_ == "" || $memo_ == NULL) {
          $memo_ = User::i()->username;
        }
        else {
          $memo_ = User::i()->username . " - " . $memo_;
        }
      }
      $sql = "INSERT INTO gl_trans ( type, type_no, tran_date,
		account, dimension_id, dimension2_id, memo_, amount";
      if ($person_type_id != NULL) {
        $sql .= ", person_type_id, person_id";
      }
      $sql .= ") ";
      $sql .= "VALUES (" . DB::escape($type) . ", " . DB::escape($trans_id) . ", '$date',
		" . DB::escape($account) . ", " . DB::escape($dimension) . ", " . DB::escape($dimension2) . ", " . DB::escape($memo_) . ", " . DB::escape($amount_in_home_currency);
      if ($person_type_id != NULL) {
        $sql .= ", " . DB::escape($person_type_id) . ", " . DB::escape($person_id);
      }
      $sql .= ") ";
      if ($err_msg == "") {
        $err_msg = "The GL transaction could not be inserted";
      }
      DB::query($sql, $err_msg);
      return $amount_in_home_currency;
    }

    /***
     * @static
     *
     * @param        $type
     * @param        $trans_id
     * @param        $date_
     * @param        $account
     * @param        $dimension
     * @param        $dimension2
     * @param        $memo_
     * @param        $amount
     * @param null   $person_type_id
     * @param null   $person_id
     * @param string $err_msg
     *
     * @return float|int
     * GL Trans for standard costing, always home currency regardless of person
     * $date_ is display date (non-sql)
     * $amount is in HOME currency
     */
    public static function add_std_cost($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_, $amount, $person_type_id = NULL, $person_id = NULL, $err_msg = "") {
      if ($amount != 0) {
        return static::add($type, $trans_id, $date_, $account, $dimension, $dimension2, $memo_, $amount, NULL, $person_type_id, $person_id, $err_msg);
      }
      else {
        return 0;
      }
    }

    /***
     * @static
     *
     * @param      $type
     * @param      $trans_id
     * @param      $date_
     * @param      $amount
     * @param null $person_type_id
     * @param null $person_id
     *
     * @return float|int
     * public static function for even out rounding problems
     */
    public static function add_balance($type, $trans_id, $date_, $amount, $person_type_id = NULL, $person_id = NULL) {
      $amount = Num::round($amount, User::price_dec());
      if ($amount != 0) {
        return static::add($type, $trans_id, $date_, DB_Company::get_pref('exchange_diff_act'), 0, 0, "", $amount, NULL, $person_type_id, $person_id,
          "The balanced GL transaction could not be inserted");
      }
      else {
        return 0;
      }
    }
    /**
     * @static
     *
     * @param      $from_date
     * @param      $to_date
     * @param int  $trans_no
     * @param null $account
     * @param int  $dimension
     * @param int  $dimension2
     * @param null $filter_type
     * @param null $amount_min
     * @param null $amount_max
     *
     * @return null|PDOStatement
     */
    public static function get($from_date, $to_date, $trans_no = 0, $account = NULL, $dimension = 0, $dimension2 = 0, $filter_type = NULL, $amount_min = NULL, $amount_max = NULL) {
      $from = Dates::date2sql($from_date);
      $to   = Dates::date2sql($to_date);
      $sql  = "SELECT gl_trans.*, " . "chart_master.account_name FROM gl_trans, " . "chart_master
		WHERE chart_master.account_code=gl_trans.account
		AND tran_date >= '$from'
		AND tran_date <= '$to'";
      if ($trans_no > 0) {
        $sql .= " AND gl_trans.type_no LIKE " . DB::escape('%' . $trans_no);
      }
      if ($account != NULL) {
        $sql .= " AND gl_trans.account = " . DB::escape($account);
      }
      if ($dimension != 0) {
        $sql .= " AND gl_trans.dimension_id = " . ($dimension < 0 ? 0 : DB::escape($dimension));
      }
      if ($dimension2 != 0) {
        $sql .= " AND gl_trans.dimension2_id = " . ($dimension2 < 0 ? 0 : DB::escape($dimension2));
      }
      if ($filter_type != NULL AND is_numeric($filter_type)) {
        $sql .= " AND gl_trans.type= " . DB::escape($filter_type);
      }
      if ($amount_min != NULL) {
        $sql .= " AND ABS(gl_trans.amount) >= ABS(" . DB::escape($amount_min) . ")";
      }
      if ($amount_max != NULL) {
        $sql .= " AND ABS(gl_trans.amount) <= ABS(" . DB::escape($amount_max) . ")";
      }
      $sql .= " ORDER BY tran_date, counter";
      return DB::query($sql, "The transactions for could not be retrieved");
    }
    /**
     * @static
     *
     * @param $type
     * @param $trans_id
     *
     * @return null|PDOStatement
     */
    public static function get_many($type, $trans_id) {
      $sql = "SELECT gl_trans.*, " . "chart_master.account_name FROM " . "gl_trans, chart_master
		WHERE chart_master.account_code=gl_trans.account
		AND gl_trans.type=" . DB::escape($type) . " AND gl_trans.type_no=" . DB::escape($trans_id);
      return DB::query($sql, "The gl transactions could not be retrieved");
    }
    /**
     * @static
     *
     * @param $trans_id
     * @param $person_id
     *
     * @return null|PDOStatement
     */
    public static function get_wo_cost($trans_id, $person_id = -1) {
      $sql = "SELECT gl_trans.*, chart_master.account_name FROM " . "gl_trans, chart_master
		WHERE chart_master.account_code=gl_trans.account
		AND gl_trans.type=" . ST_WORKORDER . " AND gl_trans.type_no=" . DB::escape($trans_id) . "
		AND gl_trans.person_type_id=" . PT_WORKORDER;
      if ($person_id != -1) {
        $sql .= " AND gl_trans.person_id=" . DB::escape($person_id);
      }
      $sql .= " AND amount < 0";
      return DB::query($sql, "The gl transactions could not be retrieved");
    }
    /**
     * @static
     *
     * @param     $from_date
     * @param     $to_date
     * @param     $account
     * @param int $dimension
     * @param int $dimension2
     *
     * @return mixed
     */
    public static function get_balance_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {
      $from = Dates::date2sql($from_date);
      $to   = Dates::date2sql($to_date);
      $sql  = "SELECT SUM(amount) FROM gl_trans
		WHERE account='$account'";
      if ($from_date != "") {
        $sql .= " AND tran_date > '$from'";
      }
      if ($to_date != "") {
        $sql .= " AND tran_date < '$to'";
      }
      if ($dimension != 0) {
        $sql .= " AND dimension_id = " . ($dimension < 0 ? 0 : DB::escape($dimension));
      }
      if ($dimension2 != 0) {
        $sql .= " AND dimension2_id = " . ($dimension2 < 0 ? 0 : DB::escape($dimension2));
      }
      $result = DB::query($sql, "The starting balance for account $account could not be calculated");
      $row    = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param     $from_date
     * @param     $to_date
     * @param     $account
     * @param int $dimension
     * @param int $dimension2
     *
     * @return mixed
     */
    public static function get_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {
      $from = Dates::date2sql($from_date);
      $to   = Dates::date2sql($to_date);
      $sql  = "SELECT SUM(amount) FROM gl_trans
		WHERE account='$account'";
      if ($from_date != "") {
        $sql .= " AND tran_date >= '$from'";
      }
      if ($to_date != "") {
        $sql .= " AND tran_date <= '$to'";
      }
      if ($dimension != 0) {
        $sql .= " AND dimension_id = " . ($dimension < 0 ? 0 : DB::escape($dimension));
      }
      if ($dimension2 != 0) {
        $sql .= " AND dimension2_id = " . ($dimension2 < 0 ? 0 : DB::escape($dimension2));
      }
      $result = DB::query($sql, "Transactions for account $account could not be calculated");
      $row    = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param      $account
     * @param      $dimension
     * @param      $dimension2
     * @param      $from
     * @param      $to
     * @param bool $from_incl
     * @param bool $to_incl
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get_balance($account, $dimension, $dimension2, $from, $to, $from_incl = TRUE, $to_incl = TRUE) {
      $sql = "SELECT SUM(IF(amount >= 0, amount, 0)) as debit,
		SUM(IF(amount < 0, -amount, 0)) as credit, SUM(amount) as balance 
		FROM gl_trans,chart_master," . "chart_types, chart_class
		WHERE gl_trans.account=chart_master.account_code AND " . "chart_master.account_type=chart_types.id
		AND chart_types.class_id=chart_class.cid AND";
      if ($account != NULL) {
        $sql .= " account=" . DB::escape($account) . " AND";
      }
      if ($dimension != 0) {
        $sql .= " dimension_id = " . ($dimension < 0 ? 0 : DB::escape($dimension)) . " AND";
      }
      if ($dimension2 != 0) {
        $sql .= " dimension2_id = " . ($dimension2 < 0 ? 0 : DB::escape($dimension2)) . " AND";
      }
      $from_date = Dates::date2sql($from);
      if ($from_incl) {
        $sql .= " tran_date >= '$from_date' AND";
      }
      else {
        $sql .= " tran_date > IF(ctype>0 AND ctype<" . CL_INCOME . ", '0000-00-00', '$from_date') AND";
      }
      $to_date = Dates::date2sql($to);
      if ($to_incl) {
        $sql .= " tran_date <= '$to_date' ";
      }
      else {
        $sql .= " tran_date < '$to_date' ";
      }
      $result = DB::query($sql, "No general ledger accounts were returned");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param     $from_date
     * @param     $to_date
     * @param     $account
     * @param int $dimension
     * @param int $dimension2
     *
     * @return mixed
     */
    public static function get_budget_from_to($from_date, $to_date, $account, $dimension = 0, $dimension2 = 0) {
      $from = Dates::date2sql($from_date);
      $to   = Dates::date2sql($to_date);
      $sql  = "SELECT SUM(amount) FROM budget_trans
		WHERE account=" . DB::escape($account);
      if ($from_date != "") {
        $sql .= " AND tran_date >= '$from' ";
      }
      if ($to_date != "") {
        $sql .= " AND tran_date <= '$to' ";
      }
      if ($dimension != 0) {
        $sql .= " AND dimension_id = " . ($dimension < 0 ? 0 : DB::escape($dimension));
      }
      if ($dimension2 != 0) {
        $sql .= " AND dimension2_id = " . ($dimension2 < 0 ? 0 : DB::escape($dimension2));
      }
      $result = DB::query($sql, "No budget accounts were returned");
      $row    = DB::fetch_row($result);
      return $row[0];
    }

    //	Stores journal/bank transaction tax details if applicable
    //
    /**
     * @static
     *
     * @param $gl_code
     * @param $trans_type
     * @param $trans_no
     * @param $amount
     * @param $ex_rate
     * @param $date
     * @param $memo
     *
     * @return mixed
     */
    public static function add_gl_tax_details($gl_code, $trans_type, $trans_no, $amount, $ex_rate, $date, $memo) {
      $tax_type = Tax::is_account($gl_code);
      if (!$tax_type) {
        return;
      } // $gl_code is not tax account
      $tax = Tax_Types::get($tax_type);
      if ($gl_code == $tax['sales_gl_code']) {
        $amount = -$amount;
      }
      // we have to restore net amount as we cannot know the base amount
      if ($tax['rate'] == 0) {
        //		Event::warning(_("You should not post gl transactions
        //			to tax account with	zero tax rate."));
        $net_amount = 0;
      }
      else {
        // calculate net amount
        $net_amount = $amount / $tax['rate'] * 100;
      }
      static::add_tax_details($trans_type, $trans_no, $tax['id'], $tax['rate'], 0, $amount, $net_amount, $ex_rate, $date, $memo);
    }

    //
    //	Store transaction tax details for fiscal purposes with 'freezed'
    //	actual tax type rate.
    //
    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     * @param $tax_id
     * @param $rate
     * @param $included
     * @param $amount
     * @param $net_amount
     * @param $ex_rate
     * @param $tran_date
     * @param $memo
     */
    public static function add_tax_details($trans_type, $trans_no, $tax_id, $rate, $included, $amount, $net_amount, $ex_rate, $tran_date, $memo) {
      $sql = "INSERT INTO trans_tax_details
		(trans_type, trans_no, tran_date, tax_type_id, rate, ex_rate,
			included_in_price, net_amount, amount, memo)
		VALUES (" . DB::escape($trans_type) . "," . DB::escape($trans_no) . ",'" . Dates::date2sql($tran_date) . "'," . DB::escape($tax_id) . "," . DB::escape($rate) . "," . DB::escape($ex_rate) . "," . ($included ?
        1 :
        0) . "," . DB::escape($net_amount) . "," . DB::escape($amount) . "," . DB::escape($memo) . ")";
      DB::query($sql, "Cannot save trans tax details");
    }
    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     *
     * @return null|PDOStatement
     */
    public static function get_tax_details($trans_type, $trans_no) {
      $sql = "SELECT trans_tax_details.*, " . "tax_types.name AS tax_type_name
		FROM trans_tax_details,tax_types
		WHERE trans_type = " . DB::escape($trans_type) . "
		AND trans_no = " . DB::escape($trans_no) . "
		AND (net_amount != 0 OR amount != 0)
		AND tax_types.id = trans_tax_details.tax_type_id";
      return DB::query($sql, "The transaction tax details could not be retrieved");
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void_tax_details($type, $type_no) {
      $sql = "UPDATE trans_tax_details SET amount=0, net_amount=0
		WHERE trans_no=" . DB::escape($type_no) . " AND trans_type=" . DB::escape($type);
      DB::query($sql, "The transaction tax details could not be voided");
    }
    /**
     * @static
     *
     * @param $from
     * @param $to
     *
     * @return null|PDOStatement
     */
    public static function get_tax_summary($from, $to) {
      $fromdate = Dates::date2sql($from);
      $todate   = Dates::date2sql($to);
      $sql      = "SELECT
				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE . " || trans_type=" . ST_JOURNAL . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE . " || (trans_type=" . ST_JOURNAL . " AND amount<0)" . " || trans_type=" . ST_CUSTCREDIT . ", net_amount*ex_rate,0)) net_output,

				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE . " || trans_type=" . ST_JOURNAL . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE . " || (trans_type=" . ST_JOURNAL . " AND amount<0)" . " || trans_type=" . ST_CUSTCREDIT . ", amount*ex_rate,0)) payable,

				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE . " || (trans_type=" . ST_JOURNAL . " AND amount<0)" . " || trans_type=" . ST_CUSTCREDIT . ", 0, net_amount*ex_rate)) net_input,

				SUM(IF(trans_type=" . ST_CUSTCREDIT . " || trans_type=" . ST_SUPPINVOICE . ",-1,1)*
				IF(trans_type=" . ST_BANKDEPOSIT . " || trans_type=" . ST_SALESINVOICE . " || (trans_type=" . ST_JOURNAL . " AND amount<0)" . " || trans_type=" . ST_CUSTCREDIT . ", 0, amount*ex_rate)) collectible,
				taxrec.rate,
				ttype.id,
				ttype.name
		FROM tax_types ttype,
			 trans_tax_details taxrec
		WHERE taxrec.tax_type_id=ttype.id
			AND taxrec.trans_type != " . ST_CUSTDELIVERY . "
			AND taxrec.tran_date >= '$fromdate'
			AND taxrec.tran_date <= '$todate'
		GROUP BY ttype.id";
      //Event::error($sql);
      return DB::query($sql, "Cannot retrieve tax summary");
    }
    /**
     * @static
     *
     * @param $type
     * @param $trans_id
     *
     * @return bool
     */
    public static function exists($type, $trans_id) {
      $sql    = "SELECT type_no FROM gl_trans WHERE type=" . DB::escape($type) . " AND type_no=" . DB::escape($trans_id);
      $result = DB::query($sql, "Cannot retreive a gl transaction");
      return (DB::num_rows($result) > 0);
    }
    /**
     * @static
     *
     * @param      $type
     * @param      $trans_id
     * @param bool $nested
     */
    public static function void($type, $trans_id, $nested = FALSE) {
      if (!$nested) {
        DB::begin();
      }
      $sql = "UPDATE gl_trans SET amount=0 WHERE type=" . DB::escape($type) . " AND type_no=" . DB::escape($trans_id);
      DB::query($sql, "could not void gl transactions for type=$type and trans_no=$trans_id");
      if (!$nested) {
        DB::commit();
      }
    }
    /**
     * @static
     *
     * @param $account
     * @param $type
     * @param $trans_no
     *
     * @return mixed
     */
    public static function get_value($account, $type, $trans_no) {
      $sql    = "SELECT SUM(amount) FROM gl_trans WHERE account=" . DB::escape($account) . " AND type=" . DB::escape($type) . " AND type_no=" . DB::escape($trans_no);
      $result = DB::query($sql, "query for gl trans value");
      $row    = DB::fetch_row($result);
      return $row[0];
    }
  }


