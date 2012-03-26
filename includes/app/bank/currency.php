<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 5/12/11
   * Time: 10:59 AM
   * To change this template use File | Settings | File Templates.
   */
  class Bank_Currency {
    static public function is_company($currency) {
      return (static::for_company() == $currency);
    }
    static public function for_company() {
      try {
        $result = DB::select('curr_default')->from('company')->fetch()->one();
        return $result['curr_default'];
      }
      catch (DBSelectException $e) {
        Event::error('Could not get company currency');
      }
      return FALSE;
    }
    static public function clear_default($curr_code) {
      $sql = "UPDATE bank_accounts SET dflt_curr_act=0 WHERE bank_curr_code="
        . DB::escape($curr_code);
      DB::query($sql, "could not update default currency account");
    }
    static public function for_bank_account($id) {
      $sql = "SELECT bank_curr_code FROM bank_accounts WHERE id='$id'";
      $result = DB::query($sql, "retreive bank account currency");
      $myrow = DB::fetch_row($result);
      return $myrow[0];
    }
    static public function for_debtor($customer_id) {
      $sql = "SELECT curr_code FROM debtors WHERE debtor_no = '$customer_id'";
      $result = DB::query($sql, "Retreive currency of customer $customer_id");
      $myrow = DB::fetch_row($result);
      return $myrow[0];
    }
    static public function for_creditor($supplier_id) {
      $sql = "SELECT curr_code FROM suppliers WHERE supplier_id = '$supplier_id'";
      $result = DB::query($sql, "Retreive currency of supplier $supplier_id");
      $myrow = DB::fetch_row($result);
      return $myrow[0];
    }
    static public function for_payment_person($type, $person_id) {
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
    static public function exchange_rate_from_home($currency_code, $date_) {
      if ($currency_code == static::for_company() || $currency_code == NULL) {
        return 1.0000;
      }
      $date = Dates::date2sql($date_);
      $sql
        = "SELECT rate_buy, max(date_) as date_ FROM exchange_rates WHERE curr_code = '$currency_code'
						AND date_ <= '$date' GROUP BY rate_buy ORDER BY date_ Desc LIMIT 1";
      $result = DB::query($sql, "could not query exchange rates");
      if (DB::num_rows($result) == 0) {
        // no stored exchange rate, just return 1
        Event::error(sprintf(_("Cannot retrieve exchange rate for currency %s as of %s. Please add exchange rate manually on Exchange Rates page."), $currency_code, $date_));
        return 1.000;
      }
      $myrow = DB::fetch_row($result);
      return $myrow[0];
    }
    static public function exchange_rate_to_home($currency_code, $date_) {
      return 1 / static::exchange_rate_from_home($currency_code, $date_);
    }
    static public function to_home($amount, $currency_code, $date_) {
      $ex_rate = static::exchange_rate_to_home($currency_code, $date_);
      return Num::round($amount / $ex_rate, User::price_dec());
    }
  }
