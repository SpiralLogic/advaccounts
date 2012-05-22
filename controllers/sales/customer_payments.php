<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::open_window(900, 500);
  JS::footerFile('/js/payalloc.js');
  Page::start(_($help_context = "Customer Payment Entry"), SA_SALESPAYMNT, Input::request('frame'));
  Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  $_POST['customer_id'] = Input::post_get('customer_id', FALSE);
  if (list_updated('branch_id') || !$_POST['customer_id']) {
    // when branch is selected via external editor also customer can change
    $br = Sales_Branch::get(get_post('branch_id'));
    $_POST['customer_id'] = $br['debtor_id'];
    Ajax::i()->activate('customer_id');
  }
  if (!isset($_POST['DateBanked'])) {
    $_POST['DateBanked'] = Dates::new_doc_date();
    if (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
      $_POST['DateBanked'] = Dates::end_fiscalyear();
    }
  }
  if (isset($_GET[ADDED_ID])) {
    $payment_no = $_GET[ADDED_ID];
    Event::success(_("The customer payment has been successfully entered."));
    Display::submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no . "-" . ST_CUSTPAYMENT, 'prtopt');
    Display::link_no_params("/sales/inquiry/customer_inquiry.php", _("Show Invoices"));
    Display::note(GL_UI::view(ST_CUSTPAYMENT, $payment_no, _("&View the GL Journal Entries for this Customer Payment")));
    //	Display::link_params( "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");
    Display::link_no_params("/sales/customer_payments.php", _("Enter Another &Customer Payment"));
    Page::footer_exit();
  }
  // validate inputs
  if (isset($_POST['_customer_id_button'])) {
    //	unset($_POST['branch_id']);
    Ajax::i()->activate('branch_id');
  }
  if (isset($_POST['_DateBanked_changed'])) {
    Ajax::i()->activate('_ex_rate');
  }
  if (Input::has_post('customer_id') || list_updated('bank_account')) {
    Ajax::i()->activate('_page_body');
  }
  if (isset($_POST['AddPaymentItem']) && Debtor_Payment::can_process(ST_CUSTPAYMENT)) {
    $cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
    $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
    $comp_currency = Bank_Currency::for_company();
    if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
      $rate = 0;
    }
    else {
      $rate = Validation::input_num('_ex_rate');
    }
    if (check_value('createinvoice')) {
      Gl_Allocation::create_miscorder(new Debtor($_POST['customer_id']), $_POST['branch_id'], $_POST['DateBanked'], $_POST['memo_'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'));
    }
    $payment_no = Debtor_Payment::add(0, $_POST['customer_id'], $_POST['branch_id'], $_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['memo_'], $rate, Validation::input_num('charge'));
    $_SESSION['alloc']->trans_no = $payment_no;
    $_SESSION['alloc']->write();
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$payment_no");
  }
  start_form();
  Table::startOuter('tablestyle2 width90 pad2');
  Table::section(1);
  Debtor::newselect();
  if (!isset($_POST['bank_account'])) // first page call
  {
    $_SESSION['alloc'] = new Gl_Allocation(ST_CUSTPAYMENT, 0);
  }
  if (isset($_POST["customer_id"])) {
    Validation::check(Validation::BRANCHES, _("No Branches for Customer") . $_POST["customer_id"], $_POST['customer_id']);
  }
  Debtor_Branch::row(_("Branch:"), $_POST['customer_id'], 'branch_id', NULL, FALSE, TRUE, TRUE);
  Debtor_Payment::read_customer_data($_POST['customer_id']);
  Session::i()->setGlobal('debtor', $_POST['customer_id']);
  if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0) {
    Table::endOuter();
    Event::error(_("This customer account is on hold."));
  }
  else {
    $display_discount_percent = Num::percent_format($_POST['pymt_discount'] * 100) . "%";
    Table::section(2);
    if (!list_updated('bank_account')) {
      $_POST['bank_account'] = Bank_Account::get_customer_default($_POST['customer_id']);
    }
    Bank_Account::row(_("Into Bank Account:"), 'bank_account', NULL, TRUE);
    text_row(_("Reference:"), 'ref', NULL, 20, 40);
    Table::section(3);
    date_row(_("Date of Deposit:"), 'DateBanked', '', TRUE, 0, 0, 0, NULL, TRUE);
    $comp_currency = Bank_Currency::for_company();
    $cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
    $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
    if ($cust_currency != $bank_currency) {
      GL_ExchangeRate::display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
    }
    amount_row(_("Bank Charge:"), 'charge', 0);
    Table::endOuter(1);
    if ($cust_currency == $bank_currency) {
      Display::div_start('alloc_tbl');
      $_SESSION['alloc']->read();
      Gl_Allocation::show_allocatable(FALSE);
      Display::div_end();
    }
    Table::start('tablestyle width70');
    Row::label(_("Customer prompt payment discount :"), $display_discount_percent);
    amount_row(_("Amount of Discount:"), 'discount', 0);
    if (User::i()->can_access(SS_SALES) && !Input::post('TotalNumberOfAllocs')) {
      check_row(_("Create invoice and apply for this payment: "), 'createinvoice');
    }
    amount_row(_("Amount:"), 'amount');
    textarea_row(_("Memo:"), 'memo_', NULL, 22, 4);
    Table::end(1);
    if ($cust_currency != $bank_currency) {
      Event::warning(_("Amount and discount are in customer's currency."));
    }
    Display::br();
    submit_center('AddPaymentItem', _("Add Payment"), TRUE, '', 'default');
  }
  Display::br();
  end_form();
  $js
    = <<<JS
var ci = $("#createinvoice"), ci_row = ci.closest('tr'),alloc_tbl = $('#alloc_tbl'),hasallocated = false;
 alloc_tbl.find('.amount').each(function() { if (this.value != 0) hasallocated = true});
 if (hasallocated && !ci.prop('checked')) ci_row.hide(); else ci_row.show();
JS;
  JS::addLiveEvent('a, :input', 'click change', $js, 'wrapper', TRUE);
  Page::end(!Input::request('frame'));
