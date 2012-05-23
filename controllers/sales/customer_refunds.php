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
  JS::headerFile('/js/payalloc.js');
  Page::start(_($help_context = "Customer Refund Entry"), SA_SALESREFUND, Input::request('frame'));
  Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (!isset($_POST['customer_id']) && Session::i()->getGlobal('debtor')) {
    $customer = new Debtor(Session::i()->getGlobal('debtor'));
  }
  if (!isset($_POST['DateBanked'])) {
    $_POST['DateBanked'] = Dates::new_doc_date();
    if (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
      $_POST['DateBanked'] = Dates::end_fiscalyear();
    }
  }
  if (isset($_GET[ADDED_ID])) {
    $refund_id = $_GET[ADDED_ID];
    Event::success(_("The customer refund has been successfully entered."));
    Display::submenu_print(_("&Print This Receipt"), ST_CUSTREFUND, $refund_id . "-" . ST_CUSTREFUND, 'prtopt');
    Display::link_no_params("/sales/inquiry/customer_inquiry.php", _("Show Invoices"));
    Display::note(GL_UI::view(ST_CUSTREFUND, $refund_id, _("&View the GL Journal Entries for this Customer Refund")));
    Page::footer_exit();
  }

  // validate inputs
  if (isset($_POST['AddRefundItem'])) {
    if (!Debtor_Payment::can_process(ST_CUSTREFUND)) {
      unset($_POST['AddRefundItem']);
    }
  }
  if (isset($_POST['_DateBanked_changed'])) {
    JS::setfocus('_DataBanked_changed');
  }
  if (list_updated('customer_id') || list_updated('bank_account')) {
    $_SESSION['alloc']->read();
    Ajax::i()->activate('alloc_tbl');
  }
  if (isset($_POST['AddRefundItem'])) {
    $cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
    $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
    $comp_currency = Bank_Currency::for_company();
    if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
      $rate = 0;
    }
    else {
      $rate = Validation::input_num('_ex_rate');
    }
    Dates::new_doc_date($_POST['DateBanked']);
    $refund_id = Debtor_Refund::add(0, $_POST['customer_id'], $_POST['branch_id'], $_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['memo_'], $rate, Validation::input_num('charge'));
    $_SESSION['alloc']->trans_no = $refund_id;
    $_SESSION['alloc']->write();
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$refund_id");
  }
  start_form();
  Table::startOuter('tablestyle2 width60 pad5');
  Table::section(1);
  Debtor::newselect();
  if (!isset($_POST['bank_account'])) // first page call
  {
    $_SESSION['alloc'] = new Gl_Allocation(ST_CUSTREFUND, 0);
  }
  if (count($customer->branches) == 0) {
    Validation::check(Validation::BRANCHES, _("No Branches for Customer") . $_POST["customer_id"], $_POST['customer_id']);
  }
  elseif (!isset($_POST['branch_id'])) {
    Debtor_Branch::row(_("Branch:"), $_POST['customer_id'], 'branch_id', NULL, FALSE, TRUE, TRUE);
  }
  else {
    hidden('branch_id', ANY_NUMERIC);
  }
  Debtor_Payment::read_customer_data($customer->id, TRUE);
  Session::i()->setGlobal('debtor',$customer->id);
  $display_discount_percent = Num::percent_format($_POST['payment_discount'] * 100) . "%";
  Table::section(2);
  Bank_Account::row(_("Into Bank Account:"), 'bank_account', NULL, TRUE);
  text_row(_("Reference:"), 'ref', NULL, 20, 40);
  Table::section(3);
  date_row(_("Date of Deposit:"), 'DateBanked', '', TRUE, 0, 0, 0, NULL, TRUE);
  $comp_currency = Bank_Currency::for_company();
  $cust_currency = Bank_Currency::for_debtor($customer->id);
  $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
  if ($cust_currency != $bank_currency) {
    GL_ExchangeRate::display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
  }
  amount_row(_("Bank Charge:"), 'charge');
  Table::endOuter(1);
  if ($cust_currency == $bank_currency) {
    Display::div_start('alloc_tbl');
    Gl_Allocation::show_allocatable(TRUE);
    Display::div_end();
  }
  Table::start('tablestyle width60');
  amount_row(_("Amount:"), 'amount');
  textarea_row(_("Memo:"), 'memo_', NULL, 22, 4);
  Table::end(1);
  if ($cust_currency != $bank_currency) {
    Event::warning(_("Amount and discount are in customer's currency."));
  }
  Display::br();
  submit_center('AddRefundItem', _("Add Refund"), TRUE, '', 'default');
  Display::br();
  end_form();
  Page::end(!Input::request('frame'));
