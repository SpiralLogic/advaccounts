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
  Page::start(_($help_context = "Supplier Payment Entry"), SA_SUPPLIERPAYMNT);
  $_POST['supplier_id'] = Input::getPostGlobal('supplier_id', Input::NUMERIC, -1);
  Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (!isset($_POST['DatePaid'])) {
    $_POST['DatePaid'] = Dates::newDocDate();
    if (!Dates::isDateInFiscalYear($_POST['DatePaid'])) {
      $_POST['DatePaid'] = Dates::endFiscalYear();
    }
  }
  if (isset($_POST['_DatePaid_changed'])) {
    Ajax::activate('_ex_rate');
  }
  if (Forms::isListUpdated('supplier_id') || Forms::isListUpdated('bank_account')) {
    $_SESSION['alloc']->read();
    Ajax::activate('alloc_tbl');
  }
  if (isset($_GET[ADDED_ID])) {
    $payment_id = $_GET[ADDED_ID];
    Event::success(_("Payment has been sucessfully entered"));
    Display::submenu_print(_("&Print This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, 'prtopt');
    Display::submenu_print(_("&Email This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, null, 1);
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Invoice"), "New=1", true, 'class="button"');
    HTML::br();
    Display::note(GL_UI::view(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment"), false, 'button'));
    // Display::link_params($path_to_root . "/purchases/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter another supplier &payment"), "supplier_id=" . $_POST['supplier_id'], true, 'class="button"');
    Page::footer_exit();
  }
  if (isset($_POST['ProcessSuppPayment']) && Creditor_Payment::can_process()) {
    $supplier_currency = Bank_Currency::for_creditor($_POST['supplier_id']);
    $bank_currency     = Bank_Currency::for_company($_POST['bank_account']);
    $comp_currency     = Bank_Currency::for_company();
    if ($comp_currency != $bank_currency && $bank_currency != $supplier_currency) {
      $rate = 0;
    } else {
      $rate = Validation::input_num('_ex_rate');
    }
    $payment_id = Creditor_Payment::add($_POST['supplier_id'], $_POST['DatePaid'], $_POST['bank_account'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['ref'], $_POST['memo_'], $rate, Validation::input_num('charge'));
    Dates::newDocDate($_POST['DatePaid']);
    $_SESSION['alloc']->trans_no = $payment_id;
    $_SESSION['alloc']->write();
    //unset($_POST['supplier_id']);
    unset($_POST['bank_account'], $_POST['DatePaid'], $_POST['currency'], $_POST['memo_'], $_POST['amount'], $_POST['discount'], $_POST['ProcessSuppPayment']);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$payment_id&supplier_id=" . $_POST['supplier_id']);
    Page::end();
    exit;
  }
  Forms::start();
  Table::startOuter('tablestyle2 width80 pad5');
  Table::section(1);
  Creditor::newselect();
  if (!isset($_POST['bank_account'])) // first page call
  {
    $_SESSION['alloc'] = new Gl_Allocation(ST_SUPPAYMENT, 0);
  }
  Session::setGlobal('creditor', $_POST['supplier_id']);
  Bank_Account::row(_("Bank Account:"), 'bank_account', null, true);
  Table::section(2);
  Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_SUPPAYMENT));
  Forms::dateRow(_("Date Paid") . ":", 'DatePaid', '', true, 0, 0, 0, null, true);
  Table::section(3);
  $supplier_currency = Bank_Currency::for_creditor($_POST['supplier_id']);
  $bank_currency     = Bank_Currency::for_company($_POST['bank_account']);
  if ($bank_currency != $supplier_currency) {
    GL_ExchangeRate::display($bank_currency, $supplier_currency, $_POST['DatePaid'], true);
  }
  Forms::AmountRow(_("Bank Charge:"), 'charge');
  Table::endOuter(1); // outer table
  if ($bank_currency == $supplier_currency) {
    Display::div_start('alloc_tbl');
    Gl_Allocation::show_allocatable(false);
    Display::div_end();
  }
  Table::start('tablestyle width60');
  Forms::AmountRow(_("Amount of Discount:"), 'discount');
  Forms::AmountRow(_("Amount of Payment:"), 'amount');
  Forms::textareaRow(_("Memo:"), 'memo_', null, 22, 4);
  Table::end(1);
  if ($bank_currency != $supplier_currency) {
    Event::warning(_("The amount and discount are in the bank account's currency."), 0, 1);
  }
  Forms::submitCenter('ProcessSuppPayment', _("Enter Payment"), true, '', 'default');
  Forms::end();
  Page::end();

