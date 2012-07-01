<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  //	Check if given account is used by any bank_account.
  //	Returns id of first bank_account using account_code, null otherwise.
  //
  //	Keep in mind that direct posting to bank account is depreciated
  //	because we have no way to select right bank account if
  //	there is more than one using given gl account.
  //
  class Bank
  {
    /**
     * @static
     *
     * @param $from_curr_code
     * @param $to_curr_code
     * @param $date_
     *
     * @return float
     */
    public static function get_exchange_rate_from_to($from_curr_code, $to_curr_code, $date_)
    {
      //	echo "converting from $from_curr_code to $to_curr_code <BR>";
      if ($from_curr_code == $to_curr_code) {
        return 1.0000;
      }
      $home_currency = static::for_company();
      if ($to_curr_code == $home_currency) {
        return static::get_exchange_rate_to_home_currency($from_curr_code, $date_);
      }
      if ($from_curr_code == $home_currency) {
        return static::get_exchange_rate_from_home_currency($to_curr_code, $date_);
      }
      // neither from or to are the home currency
      return static::get_exchange_rate_to_home_currency($from_curr_code, $date_) / static::get_exchange_rate_to_home_currency($to_curr_code, $date_);
    }
    /**
     * @static
     *
     * @param $amount
     * @param $from_curr_code
     * @param $to_curr_code
     * @param $date_
     *
     * @return float
     */
    public static function exchange_from_to($amount, $from_curr_code, $to_curr_code, $date_)
    {
      $ex_rate = static::get_exchange_rate_from_to($from_curr_code, $to_curr_code, $date_);

      return $amount / $ex_rate;
    }
    // Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////
    /**
     * @static
     *
     * @param      $pyt_type
     * @param      $pyt_no
     * @param      $type
     * @param      $trans_no
     * @param      $pyt_date
     * @param      $amount
     * @param      $person_type
     * @param bool $neg
     *
     * @return mixed
     */
    public static function exchange_variation($pyt_type, $pyt_no, $type, $trans_no, $pyt_date, $amount, $person_type, $neg = false)
    {
      global $systypes_array;
      if ($person_type == PT_CUSTOMER) {
        $trans     = Debtor_Trans::get($trans_no, $type);
        $pyt_trans = Debtor_Trans::get($pyt_no, $pyt_type);
        $ar_ap_act = $trans['receivables_account'];
        $person_id = $trans['debtor_id'];
        $curr      = $trans['curr_code'];
        $date      = Dates::sqlToDate($trans['tran_date']);
      } else {
        $trans         = Creditor_Trans::get($trans_no, $type);
        $pyt_trans     = Creditor_Trans::get($pyt_no, $pyt_type);
        $supplier_accs = Creditor::get_accounts_name($trans['supplier_id']);
        $ar_ap_act     = $supplier_accs['payable_account'];
        $person_id     = $trans['supplier_id'];
        $curr          = $trans['SupplierCurrCode'];
        $date          = Dates::sqlToDate($trans['tran_date']);
      }
      if (Bank_Currency::is_company($curr)) {
        return;
      }
      $inv_amt = Num::round($amount * $trans['rate'], User::price_dec());
      $pay_amt = Num::round($amount * $pyt_trans['rate'], User::price_dec());
      if ($inv_amt != $pay_amt) {
        $diff = $inv_amt - $pay_amt;
        if ($person_type == PT_SUPPLIER) {
          $diff = -$diff;
        }
        if ($neg) {
          $diff = -$diff;
        }
        $exc_var_act = DB_Company::get_pref('exchange_diff_act');
        if (Dates::isGreaterThan($date, $pyt_date)) {
          $memo = $systypes_array[$pyt_type] . " " . $pyt_no;
          GL_Trans::add($type, $trans_no, $date, $ar_ap_act, 0, 0, $memo, -$diff, null, $person_type, $person_id);
          GL_Trans::add($type, $trans_no, $date, $exc_var_act, 0, 0, $memo, $diff, null, $person_type, $person_id);
        } else {
          $memo = $systypes_array[$type] . " " . $trans_no;
          GL_Trans::add($pyt_type, $pyt_no, $pyt_date, $ar_ap_act, 0, 0, $memo, -$diff, null, $person_type, $person_id);
          GL_Trans::add($pyt_type, $pyt_no, $pyt_date, $exc_var_act, 0, 0, $memo, $diff, null, $person_type, $person_id);
        }
      }
    }
    /**
     * @static
     *
     * @param $type
     *
     * @return bool|int|null
     */
    public static function payment_person_has_items($type)
    {
      switch ($type) {
        case PT_MISC :
          return true;
        case PT_QUICKENTRY :
          return Validation::check(Validation::QUICK_ENTRIES);
        case PT_WORKORDER : // 070305 changed to open workorders JH

          return Validation::check(Validation::OPEN_WORKORDERS);
        case PT_CUSTOMER :
          return Validation::check(Validation::CUSTOMERS);
        case PT_SUPPLIER :
          return Validation::check(Validation::SUPPLIERS);
        default :
          Errors::databaseError("Invalid type sent to has_items", "");

          return false;
      }
    }
    /**
     * @static
     *
     * @param      $type
     * @param      $person_id
     * @param bool $full
     * @param null $trans_no
     *
     * @return string
     */
    public static function payment_person_name($type, $person_id, $full = true, $trans_no = null)
    {
      global $payment_person_types;
      switch ($type) {
        case PT_MISC :
          return $person_id;
        case PT_QUICKENTRY :
          $qe      = GL_QuickEntry::get($person_id);
          $comment = '';
          if (!is_null($trans_no)) {
            $comment = "<br>" . DB_Comments::get_string(ST_BANKPAYMENT, $trans_no);
          }

          return ($full ? $payment_person_types[$type] . " " : "") . $qe["description"] . $comment;
        case PT_WORKORDER :
          global $wo_cost_types;

          return $wo_cost_types[$type];
        case PT_CUSTOMER :
          return ($full ? $payment_person_types[$type] . " " : "") . Debtor::get_name($person_id);
        case PT_SUPPLIER :
          return ($full ? $payment_person_types[$type] . " " : "") . Creditor::get_name($person_id);
        default :
          //DisplayDBerror("Invalid type sent to person_name");
          //return;
          return '';
      }
    }
  }

