<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::openWindow(900, 500);
  JS::footerFile('/js/payalloc.js');
  Page::start(_($help_context = "Customer Payment Entry"), SA_SALESPAYMNT, Input::request('frame'));
  Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  $_POST['customer_id'] = Input::postGet('customer_id', false);
  if (Forms::isListUpdated('branch_id') || !$_POST['customer_id']) {
    // when branch is selected via external editor also customer can change
    $br                   = Sales_Branch::get(Input::post('branch_id'));
    $_POST['customer_id'] = $br['debtor_id'];
    Ajax::activate('customer_id');
  }
  if (!isset($_POST['DateBanked'])) {
    $_POST['DateBanked'] = Dates::newDocDate();
    if (!Dates::isDateInFiscalYear($_POST['DateBanked'])) {
      $_POST['DateBanked'] = Dates::endFiscalYear();
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
    Ajax::activate('branch_id');
  }
  if (isset($_POST['_DateBanked_changed'])) {
    Ajax::activate('_ex_rate');
  }
  if (Input::hasPost('customer_id') || Forms::isListUpdated('bank_account')) {
    Ajax::activate('_page_body');
  }
  if (isset($_POST['AddPaymentItem']) && Debtor_Payment::can_process(ST_CUSTPAYMENT)) {
    $cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
    $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
    $comp_currency = Bank_Currency::for_company();
    if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
      $rate = 0;
    } else {
      $rate = Validation::input_num('_ex_rate');
    }
    if (Forms::hasPost('createinvoice')) {
      Gl_Allocation::create_miscorder(new Debtor($_POST['customer_id']), $_POST['branch_id'], $_POST['DateBanked'], $_POST['memo_'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'));
    }
    $payment_no                  = Debtor_Payment::add(0, $_POST['customer_id'], $_POST['branch_id'], $_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['memo_'], $rate, Validation::input_num('charge'));
    $_SESSION['alloc']->trans_no = $payment_no;
    $_SESSION['alloc']->write();
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$payment_no");
  }
  Forms::start();
  Table::startOuter('tablestyle2 width90 pad2');
  Table::section(1);
  Debtor::newselect($_POST['customer_id']);
  if (!isset($_POST['bank_account'])) // first page call
  {
    $_SESSION['alloc'] = new Gl_Allocation(ST_CUSTPAYMENT, 0);
  }
  if (isset($_POST["customer_id"])) {
    Validation::check(Validation::BRANCHES, _("No Branches for Customer") . $_POST["customer_id"], $_POST['customer_id']);
  }
  Debtor_Branch::row(_("Branch:"), $_POST['customer_id'], 'branch_id', null, false, true, true);
  Debtor_Payment::read_customer_data($_POST['customer_id']);
  Session::setGlobal('debtor', $_POST['customer_id']);
  if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0) {
    Table::endOuter();
    Event::error(_("This customer account is on hold."));
  } else {
    $display_discount_percent = Num::percentFormat($_POST['payment_discount'] * 100) . "%";
    Table::section(2);
    if (!Forms::isListUpdated('bank_account')) {
      $_POST['bank_account'] = Bank_Account::get_customer_default($_POST['customer_id']);
    }
    Bank_Account::row(_("Into Bank Account:"), 'bank_account', null, true);
    Forms::textRow(_("Reference:"), 'ref', null, 20, 40);
    Table::section(3);
    Forms::dateRow(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
    $comp_currency = Bank_Currency::for_company();
    $cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
    $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
    if ($cust_currency != $bank_currency) {
      GL_ExchangeRate::display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
    }
    Forms::AmountRow(_("Bank Charge:"), 'charge', 0);
    Table::endOuter(1);
    if ($cust_currency == $bank_currency) {
      Display::div_start('alloc_tbl');
      $_SESSION['alloc']->read();
      Gl_Allocation::show_allocatable(false);
      Display::div_end();
    }
    Table::start('tablestyle width70');
    Row::label(_("Customer prompt payment discount :"), $display_discount_percent);
    Forms::AmountRow(_("Amount of Discount:"), 'discount', 0);
    if (User::i()->hasAccess(SS_SALES) && !Input::post('TotalNumberOfAllocs')) {
      Forms::checkRow(_("Create invoice and apply for this payment: "), 'createinvoice');
    }
    Forms::AmountRow(_("Amount:"), 'amount');
    Forms::textareaRow(_("Memo:"), 'memo_', null, 22, 4);
    Table::end(1);
    if ($cust_currency != $bank_currency) {
      Event::warning(_("Amount and discount are in customer's currency."));
    }
    Display::br();
    Forms::submitCenter('AddPaymentItem', _("Add Payment"), true, '', 'default');
  }
  Display::br();
  Forms::end();
  $js
    = <<<JS
var ci = $("#createinvoice"), ci_row = ci.closest('tr'),alloc_tbl = $('#alloc_tbl'),hasallocated = false;
 alloc_tbl.find('.amount').each(function() { if (this.value != 0) hasallocated = true});
 if (hasallocated && !ci.prop('checked')) ci_row.hide(); else ci_row.show();
JS;
  JS::addLiveEvent('a, :input', 'click change', $js, 'wrapper', true);
  Page::end(!Input::request('frame'));
