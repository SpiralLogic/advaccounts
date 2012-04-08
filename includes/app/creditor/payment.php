<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Creditor_Payment {
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
    static public function add($supplier_id, $date_, $bank_account,
                               $amount, $discount, $ref, $memo_, $rate = 0, $charge = 0) {
      DB::begin();
      $supplier_currency = Bank_Currency::for_creditor($supplier_id);
      $bank_account_currency = Bank_Currency::for_company($bank_account);
      $bank_gl_account = Bank_Account::get_gl($bank_account);
      if ($rate == 0) {
        $supp_amount = Bank::exchange_from_to($amount, $bank_account_currency, $supplier_currency, $date_);
        $supp_discount = Bank::exchange_from_to($discount, $bank_account_currency, $supplier_currency, $date_);
        $supp_charge = Bank::exchange_from_to($charge, $bank_account_currency, $supplier_currency, $date_);
      }
      else {
        $supp_amount = round($amount / $rate, User::price_dec());
        $supp_discount = round($discount / $rate, User::price_dec());
        $supp_charge = round($charge / $rate, User::price_dec());
      }
      // it's a supplier payment
      $trans_type = ST_SUPPAYMENT;
      /* Create a creditor_trans entry for the supplier payment */
      $payment_id = Creditor_Trans::add($trans_type, $supplier_id, $date_, $date_,
        $ref, "", -$supp_amount, 0, -$supp_discount, "", $rate);
      // Now debit creditors account with payment + discount
      $total = 0;
      $supplier_accounts = Creditor::get_accounts_name($supplier_id);
      $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_, $supplier_accounts["payable_account"], 0, 0,
        $supp_amount + $supp_discount, $supplier_id, "", $rate);
      // Now credit discount received account with discounts
      if ($supp_discount != 0) {
        $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_,
          $supplier_accounts["payment_discount_account"], 0, 0,
          -$supp_discount, $supplier_id, "", $rate);
      }
      if ($supp_charge != 0) {
        $charge_act = DB_Company::get_pref('bank_charge_act');
        $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_, $charge_act, 0, 0,
          $supp_charge, $supplier_id, "", $rate);
      }
      if ($supp_amount != 0) {
        $total += Creditor_Trans::add_gl($trans_type, $payment_id, $date_, $bank_gl_account, 0, 0,
          -($supp_amount + $supp_charge), $supplier_id, "", $rate);
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance($trans_type, $payment_id, $date_, -$total, PT_SUPPLIER, $supplier_id);
      /*now enter the bank_trans entry */
      Bank_Trans::add($trans_type, $payment_id, $bank_account, $ref,
        $date_, -($amount + $supp_charge), PT_SUPPLIER,
        $supplier_id, $bank_account_currency,
        "Could not add the supplier payment bank transaction");
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
    static public function void($type, $type_no) {
      DB::begin();
      Bank_Trans::void($type, $type_no, TRUE);
      GL_Trans::void($type, $type_no, TRUE);
      Purch_Allocation::void($type, $type_no);
      Creditor_Trans::void($type, $type_no);
      DB::commit();
    }
  }
