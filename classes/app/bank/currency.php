<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Bank_Currency
  {
    /**
     * @static
     *
     * @param $currency
     *
     * @return bool
     */
    public static function is_company($currency)
    {
      return (static::for_company() == $currency);
    }
    /**
     * @static
     * @return bool
     */
    public static function for_company()
    {
      try {
        $result = DB::select('curr_default')->from('company')->fetch()->one();

        return $result['curr_default'];
      }
      catch (DBSelectException $e) {
        Event::error('Could not get company currency');
      }

      return false;
    }
    /**
     * @static
     *
     * @param $curr_code
     */
    public static function clear_default($curr_code)
    {
      $sql = "UPDATE bank_accounts SET dflt_curr_act=0 WHERE bank_curr_code=" . DB::escape($curr_code);
      DB::query($sql, "could not update default currency account");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function for_bank_account($id)
    {
      $sql    = "SELECT bank_curr_code FROM bank_accounts WHERE id='$id'";
      $result = DB::query($sql, "retreive bank account currency");
      $myrow  = DB::fetchRow($result);

      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $customer_id
     *
     * @return mixed
     */
    public static function for_debtor($customer_id)
    {
      $sql    = "SELECT curr_code FROM debtors WHERE debtor_id = '$customer_id'";
      $result = DB::query($sql, "Retreive currency of customer $customer_id");
      $myrow  = DB::fetchRow($result);

      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $supplier_id
     *
     * @return mixed
     */
    public static function for_creditor($supplier_id)
    {
      $sql    = "SELECT curr_code FROM suppliers WHERE supplier_id = '$supplier_id'";
      $result = DB::query($sql, "Retreive currency of supplier $supplier_id");
      $myrow  = DB::fetchRow($result);

      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $type
     * @param $person_id
     *
     * @return bool
     */
    public static function for_payment_person($type, $person_id)
    {
      switch ($type) {
        case PT_MISC :
        case PT_QUICKENTRY :
        case PT_WORKORDER :
          return Bank_Currency::for_company();
        case PT_CUSTOMER :
          return Bank_Currency::for_debtor($person_id);
        case PT_SUPPLIER :
          return Bank_Currency::for_creditor($person_id);
        default :
          return Bank_Currency::for_company();
      }
    }
    /**
     * @static
     *
     * @param $currency_code
     * @param $date_
     *
     * @return float
     */
    public static function exchange_rate_from_home($currency_code, $date_)
    {
      if ($currency_code == static::for_company() || $currency_code == null) {
        return 1.0000;
      }
      $date = Dates::dateToSql($date_);
      $sql
              = "SELECT rate_buy, max(date_) as date_ FROM exchange_rates WHERE curr_code = '$currency_code'
                        AND date_ <= '$date' GROUP BY rate_buy ORDER BY date_ Desc LIMIT 1";
      $result = DB::query($sql, "could not query exchange rates");
      if (DB::numRows($result) == 0) {
        // no stored exchange rate, just return 1
        Event::error(sprintf(_("Cannot retrieve exchange rate for currency %s as of %s. Please add exchange rate manually on Exchange Rates page."), $currency_code, $date_));

        return 1.000;
      }
      $myrow = DB::fetchRow($result);

      return $myrow[0];
    }
    /**
     * @static
     *
     * @param $currency_code
     * @param $date_
     *
     * @return float
     */
    public static function exchange_rate_to_home($currency_code, $date_)
    {
      return 1 / static::exchange_rate_from_home($currency_code, $date_);
    }
    /**
     * @static
     *
     * @param $amount
     * @param $currency_code
     * @param $date_
     *
     * @return float
     */
    public static function to_home($amount, $currency_code, $date_)
    {
      $ex_rate = static::exchange_rate_to_home($currency_code, $date_);

      return Num::round($amount / $ex_rate, User::price_dec());
    }
  }
