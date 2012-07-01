<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Creditor_Payment
  {
    /**
     * @static
     *
     * @param     $supplier_id
     * @param     $date_
     * @param     $bank_account
     * @param     $amount
     * @param     $discount
     * @param     $ref
     * @param     $memo_
     * @param int $rate
     * @param int $charge
     *
     * @return int
     */
    public static function add($supplier_id, $date_, $bank_account, $amount, $discount, $ref, $memo_, $rate = 0, $charge = 0)
    {
      DB::begin();
      $supplier_currency     = Bank_Currency::for_creditor($supplier_id);
      $bank_account_currency = Bank_Currency::for_company($bank_account);
      $bank_gl_account       = Bank_Account::get_gl($bank_account);
      if ($rate == 0) {
        $supplier_amount   = Bank::exchange_from_to($amount, $bank_account_currency, $supplier_currency, $date_);
        $supplier_discount = Bank::exchange_from_to($discount, $bank_account_currency, $supplier_currency, $date_);
        $supplier_charge   = Bank::exchange_from_to($charge, $bank_account_currency, $supplier_currency, $date_);
      } else {
        $supplier_amount   = round($amount / $rate, User::price_dec());
        $supplier_discount = round($discount / $rate, User::price_dec());
        $supplier_charge   = round($charge / $rate, User::price_dec());
      }
      // it's a supplier payment
      $trans_type = ST_SUPPAYMENT;
      /* Create a creditor_trans entry for the supplier payment */
      $payment_id = Creditor_Trans::add($trans_type, $supplier_id, $date_, $date_, $ref, "", -$supplier_amount, 0, -$supplier_discount, "", $rate);
      // Now debit creditors account with payment + discount
      $total             = 0;
      $supplier_accounts = Creditor::get_accounts_name($supplier_id);
      $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_, $supplier_accounts["payable_account"], 0, 0, $supplier_amount + $supplier_discount, $supplier_id, "", $rate);
      // Now credit discount received account with discounts
      if ($supplier_discount != 0) {
        $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_, $supplier_accounts["payment_discount_account"], 0, 0, -$supplier_discount, $supplier_id, "", $rate);
      }
      if ($supplier_charge != 0) {
        $charge_act = DB_Company::get_pref('bank_charge_act');
        $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_, $charge_act, 0, 0, $supplier_charge, $supplier_id, "", $rate);
      }
      if ($supplier_amount != 0) {
        $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_, $bank_gl_account, 0, 0, -($supplier_amount + $supplier_charge), $supplier_id, "", $rate);
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance($trans_type, $payment_id, $date_, -$total, PT_SUPPLIER, $supplier_id);
      /*now enter the bank_trans entry */
      Bank_Trans::add($trans_type, $payment_id, $bank_account, $ref, $date_, -($amount + $supplier_charge), PT_SUPPLIER, $supplier_id, $bank_account_currency, "Could not add the supplier payment bank transaction");
      DB_Comments::add($trans_type, $payment_id, $date_, $memo_);
      Ref::save($trans_type, $ref);
      DB::commit();

      return $payment_id;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    public static function void($type, $type_no)
    {
      DB::begin();
      Bank_Trans::void($type, $type_no, true);
      GL_Trans::void($type, $type_no, true);
      Purch_Allocation::void($type, $type_no);
      Creditor_Trans::void($type, $type_no);
      DB::commit();
    }
    /**
     * @static
     * @return bool
     */
    public static function    can_process()
    {
      if (!Input::post('supplier_id')) {
        Event::error(_("There is no supplier selected."));
        JS::setFocus('supplier_id');

        return false;
      }
      if ($_POST['amount'] == "") {
        $_POST['amount'] = Num::priceFormat(0);
      }
      if (!Validation::post_num('amount', 0)) {
        Event::error(_("The entered amount is invalid or less than zero."));
        JS::setFocus('amount');

        return false;
      }
      if (isset($_POST['charge']) && !Validation::post_num('charge', 0)) {
        Event::error(_("The entered amount is invalid or less than zero."));
        JS::setFocus('charge');

        return false;
      }
      if (isset($_POST['charge']) && Validation::input_num('charge') > 0) {
        $charge_acct = DB_Company::get_pref('bank_charge_act');
        if (GL_Account::get($charge_acct) == false) {
          Event::error(_("The Bank Charge Account has not been set in System and General GL Setup."));
          JS::setFocus('charge');

          return false;
        }
      }
      if (isset($_POST['_ex_rate']) && !Validation::post_num('_ex_rate', 0.000001)) {
        Event::error(_("The exchange rate must be numeric and greater than zero."));
        JS::setFocus('_ex_rate');

        return false;
      }
      if ($_POST['discount'] == "") {
        $_POST['discount'] = 0;
      }
      if (!Validation::post_num('discount', 0)) {
        Event::error(_("The entered discount is invalid or less than zero."));
        JS::setFocus('amount');

        return false;
      }
      //if (Validation::input_num('amount') - Validation::input_num('discount') <= 0)
      if (Validation::input_num('amount') <= 0) {
        Event::error(_("The total of the amount and the discount is zero or negative. Please enter positive values."));
        JS::setFocus('amount');

        return false;
      }
      if (!Dates::isDate($_POST['DatePaid'])) {
        Event::error(_("The entered date is invalid."));
        JS::setFocus('DatePaid');

        return false;
      } elseif (!Dates::isDateInFiscalYear($_POST['DatePaid'])) {
        Event::error(_("The entered date is not in fiscal year."));
        JS::setFocus('DatePaid');

        return false;
      }
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        JS::setFocus('ref');

        return false;
      }
      if (!Ref::is_new($_POST['ref'], ST_SUPPAYMENT)) {
        $_POST['ref'] = Ref::get_next(ST_SUPPAYMENT);
      }
      $_SESSION['alloc']->amount = -Validation::input_num('amount');
      if (isset($_POST["TotalNumberOfAllocs"])) {
        return Gl_Allocation::check();
      } else {
        return true;
      }
    }
  }
