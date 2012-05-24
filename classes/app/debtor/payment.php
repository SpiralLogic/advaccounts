<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /*
         Write/update customer payment.
       */
  /**

   */
  class Debtor_Payment {

    /**
     * @static
     *
     * @param     $trans_no
     * @param     $customer_id
     * @param     $branch_id
     * @param     $bank_account
     * @param     $date_
     * @param     $ref
     * @param     $amount
     * @param     $discount
     * @param     $memo_
     * @param int $rate
     * @param int $charge
     * @param int $tax
     *
     * @return int
     */
    static public function add($trans_no, $customer_id, $branch_id, $bank_account, $date_, $ref, $amount, $discount, $memo_, $rate = 0, $charge = 0, $tax = 0) {
      DB::begin();
      $company_record  = DB_Company::get_prefs();
      $payment_no      = Debtor_Trans::write(ST_CUSTPAYMENT, $trans_no, $customer_id, $branch_id, $date_, $ref, $amount, $discount, $tax, 0, 0, 0, 0, 0, 0, $date_, 0, $rate);
      $bank_gl_account = Bank_Account::get_gl($bank_account);
      if ($trans_no != 0) {
        DB_Comments::delete(ST_CUSTPAYMENT, $trans_no);
        Bank_Trans::void(ST_CUSTPAYMENT, $trans_no, TRUE);
        GL_Trans::void(ST_CUSTPAYMENT, $trans_no, TRUE);
        Sales_Allocation::void(ST_CUSTPAYMENT, $trans_no, $date_);
      }
      $total = 0;
      /* Bank account entry first */
      $total += Debtor_TransDetail::add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_, $bank_gl_account, 0, 0, $amount - $charge, $customer_id, "Cannot insert a GL transaction for the bank account debit", $rate);
      if ($branch_id != ANY_NUMERIC) {
        $branch_data      = Sales_Branch::get_accounts($branch_id);
        $debtors_account  = $branch_data["receivables_account"];
        $discount_account = $branch_data["payment_discount_account"];
        $tax_group        = Tax_Groups::get($branch_data["payment_discount_account"]);
      }
      else {
        $debtors_account  = $company_record["debtors_act"];
        $discount_account = $company_record["default_prompt_payment_act"];
      }
      if (($discount + $amount) != 0) {
        /* Now Credit Debtors account with receipts + discounts */
        $total += Debtor_TransDetail::add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_, $debtors_account, 0, 0, -($discount + $amount), $customer_id, "Cannot insert a GL transaction for the debtors account credit", $rate);
      }
      if ($discount != 0) {
        /* Now Debit discount account with discounts allowed*/
        $total += Debtor_TransDetail::add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_, $discount_account, 0, 0, $discount, $customer_id, "Cannot insert a GL transaction for the payment discount debit", $rate);
      }
      if ($charge != 0) {
        /* Now Debit bank charge account with charges */
        $charge_act = DB_Company::get_pref('bank_charge_act');
        $total += Debtor_TransDetail::add_gl_trans(ST_CUSTPAYMENT, $payment_no, $date_, $charge_act, 0, 0, $charge, $customer_id, "Cannot insert a GL transaction for the payment bank charge debit", $rate);
      }
      if ($tax != 0) {
        $taxes = Tax_Groups::get_for_item($tax_group);
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance(ST_CUSTPAYMENT, $payment_no, $date_, -$total, PT_CUSTOMER, $customer_id);
      /*now enter the bank_trans entry */
      Bank_Trans::add(ST_CUSTPAYMENT, $payment_no, $bank_account, $ref, $date_, $amount - $charge, PT_CUSTOMER, $customer_id, Bank_Currency::for_debtor($customer_id), "", $rate);
      DB_Comments::add(ST_CUSTPAYMENT, $payment_no, $date_, $memo_);
      Ref::save(ST_CUSTPAYMENT, $ref);
      DB::commit();
      return $payment_no;
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
      Sales_Allocation::void($type, $type_no);
      Debtor_Trans::void($type, $type_no);
      DB::commit();
    }
    /**
     * @static
     *
     * @param        $customer
     * @param        $credit
     * @param string $parms
     */
    static public function credit_row($customer, $credit, $parms = '') {
      Row::label(_("Current Credit:"), "<a target='_blank' " . ($credit < 0 ? ' class="redfg openWindow"' :
        '') . " href='" . e('/sales/inquiry/customer_inquiry.php?frame=1&customer_id=' . $customer) . "'>" . Num::price_format
      ($credit) . "</a>", $parms);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     */
    static public function allocations_select($label, $name, $selected = NULL) {
      if ($label != NULL) {
        Cell::label($label);
      }
      echo "<td>\n";
      $allocs = array(
        ALL_TEXT => _("All Types"),
        '1'      => _("Sales Invoices"),
        '2'      => _("Overdue Invoices"),
        '3'      => _("Payments"),
        '4'      => _("Credit Notes"),
        '5'      => _("Delivery Notes"),
        '6'      => _("Invoices Only")
      );
      echo array_selector($name, $selected, $allocs);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $customer_id
     * @param bool $refund
     */
    static public function read_customer_data($customer_id, $refund = FALSE) {
      if ($refund == FALSE) {
        $myrow = Debtor::get_habit($customer_id);
        $type  = ST_CUSTPAYMENT;
      }
      else {
        $sql
                = "SELECT debtors.payment_discount,
      			credit_status.dissallow_invoices
      			FROM debtors, credit_status
      			WHERE debtors.credit_status = credit_status.id
      				AND debtors.debtor_id = " . $customer_id;
        $result = DB::query($sql, "could not query customers");
        $myrow  = DB::fetch($result);
        $type   = ST_CUSTREFUND;
      }
      $_POST['HoldAccount']      = $myrow["dissallow_invoices"];
      $_POST['payment_discount'] = $myrow["payment_discount"];
      $_POST['ref']              = Ref::get_next($type);
    }
    /**
     * @static
     *
     * @param $type
     *
     * @return bool
     */
    static public function can_process($type) {
      if (!get_post('customer_id')) {
        Event::error(_("There is no customer selected."));
        JS::set_focus('customer_id');
        return FALSE;
      }
      if (!get_post('branch_id')) {
        Event::error(_("This customer has no branch defined."));
        JS::set_focus('branch_id');
        return FALSE;
      }
      if (!isset($_POST['DateBanked']) || !Dates::is_date($_POST['DateBanked'])) {
        Event::error(_("The entered date is invalid. Please enter a valid date for the payment."));
        JS::set_focus('DateBanked');
        return FALSE;
      }
      elseif (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
        Event::error(_("The entered date is not in fiscal year."));
        JS::set_focus('DateBanked');
        return FALSE;
      }
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        JS::set_focus('ref');
        return FALSE;
      }
      if (!Ref::is_new($_POST['ref'], $type)) {
        $_POST['ref'] = Ref::get_next($type);
      }
      if (!Validation::post_num('amount', 0)) {
        Event::error(_("The entered amount is invalid or negative and cannot be processed."));
        JS::set_focus('amount');
        return FALSE;
      }
      if (isset($_POST['charge']) && !Validation::post_num('charge', 0)) {
        Event::error(_("The entered amount is invalid or negative and cannot be processed."));
        JS::set_focus('charge');
        return FALSE;
      }
      if (isset($_POST['charge']) && Validation::input_num('charge') > 0) {
        $charge_acct = DB_Company::get_pref('bank_charge_act');
        if (GL_Account::get($charge_acct) == FALSE) {
          Event::error(_("The Bank Charge Account has not been set in System and General GL Setup."));
          JS::set_focus('charge');
          return FALSE;
        }
      }
      if (isset($_POST['_ex_rate']) && !Validation::post_num('_ex_rate', 0.000001)) {
        Event::error(_("The exchange rate must be numeric and greater than zero."));
        JS::set_focus('_ex_rate');
        return FALSE;
      }
      if ($_POST['discount'] == "") {
        $_POST['discount'] = 0;
      }
      if (!Validation::post_num('discount')) {
        Event::error(_("The entered discount is not a valid number."));
        JS::set_focus('discount');
        return FALSE;
      }
      if ($type == ST_CUSTPAYMENT && !User::i()->salesmanid) {
        Event::error(_("You do not have a salesman id, this is needed to create an invoice."));
        return FALSE;
      }

      //if ((Validation::input_num('amount') - Validation::input_num('discount') <= 0)) {
      if ($type == ST_CUSTPAYMENT && Validation::input_num('amount', 0, 0) <= 0) {
        Event::error(_("The balance of the amount and discount is zero or negative. Please enter valid amounts."));
        JS::set_focus('discount');
        return FALSE;
      }
      if ($type == ST_CUSTREFUND && Validation::input_num('amount') >= 0) {
        Event::error(_("The balance of the amount and discount is zero or positive. Please enter valid amounts."));
        JS::setfocus('[name="amount"]');
        return FALSE;
      }
      $_SESSION['alloc']->amount = Validation::input_num('amount');
      if (isset($_POST["TotalNumberOfAllocs"])) {
        return Gl_Allocation::check();
      }
      else {
        return TRUE;
      }
    }
  }
