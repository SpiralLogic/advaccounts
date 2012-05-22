<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 500);
  JS::footerFile('/js/payalloc.js');
  Page::start(_($help_context = "Supplier Payment Entry"), SA_SUPPLIERPAYMNT);
  $_POST['supplier_id'] = Input::get_post_global('supplier_id',Input::NUMERIC,-1);
  Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (!isset($_POST['DatePaid'])) {
    $_POST['DatePaid'] = Dates::new_doc_date();
    if (!Dates::is_date_in_fiscalyear($_POST['DatePaid'])) {
      $_POST['DatePaid'] = Dates::end_fiscalyear();
    }
  }
  if (isset($_POST['_DatePaid_changed'])) {
    Ajax::i()->activate('_ex_rate');
  }
  if (list_updated('supplier_id') || list_updated('bank_account')) {
    $_SESSION['alloc']->read();
    Ajax::i()->activate('alloc_tbl');
  }
  if (isset($_GET[ADDED_ID])) {
    $payment_id = $_GET[ADDED_ID];
    Event::success(_("Payment has been sucessfully entered"));
    Display::submenu_print(_("&Print This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, 'prtopt');
    Display::submenu_print(_("&Email This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, NULL, 1);
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Invoice"), "New=1", TRUE, 'class="button"');
    HTML::br();
    Display::note(GL_UI::view(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment"), FALSE, 'button'));
    // Display::link_params($path_to_root . "/purchases/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter another supplier &payment"), "supplier_id=" . $_POST['supplier_id'], TRUE, 'class="button"');
    Page::footer_exit();
  }
  if (isset($_POST['ProcessSuppPayment']) && Creditor_Payment::can_process()) {
    $supplier_currency = Bank_Currency::for_creditor($_POST['supplier_id']);
    $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
    $comp_currency = Bank_Currency::for_company();
    if ($comp_currency != $bank_currency && $bank_currency != $supplier_currency) {
      $rate = 0;
    }
    else {
      $rate = Validation::input_num('_ex_rate');
    }
    $payment_id = Creditor_Payment::add($_POST['supplier_id'], $_POST['DatePaid'], $_POST['bank_account'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['ref'], $_POST['memo_'], $rate, Validation::input_num('charge'));
    Dates::new_doc_date($_POST['DatePaid']);
    $_SESSION['alloc']->trans_no = $payment_id;
    $_SESSION['alloc']->write();
    //unset($_POST['supplier_id']);
    unset($_POST['bank_account'], $_POST['DatePaid'], $_POST['currency'], $_POST['memo_'], $_POST['amount'], $_POST['discount'], $_POST['ProcessSuppPayment']);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$payment_id&supplier_id=" . $_POST['supplier_id']);
    Page::end();
    exit;
  }
  start_form();
  Table::startOuter('tablestyle2 width60 pad5');
  Table::section(1);
  Creditor::row(_("Payment To:"), 'supplier_id', NULL, FALSE, TRUE);
  if (!isset($_POST['bank_account'])) // first page call
  {
    $_SESSION['alloc'] = new Gl_Allocation(ST_SUPPAYMENT, 0);
  }
  Session::i()->setGlobal('creditor',$_POST['supplier_id']);
  Bank_Account::row(_("From Bank Account:"), 'bank_account', NULL, TRUE);
  Table::section(2);
  ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_SUPPAYMENT));
  date_row(_("Date Paid") . ":", 'DatePaid', '', TRUE, 0, 0, 0, NULL, TRUE);
  Table::section(3);
  $supplier_currency = Bank_Currency::for_creditor($_POST['supplier_id']);
  $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
  if ($bank_currency != $supplier_currency) {
    GL_ExchangeRate::display($bank_currency, $supplier_currency, $_POST['DatePaid'], TRUE);
  }
  amount_row(_("Bank Charge:"), 'charge');
  Table::endOuter(1); // outer table
  if ($bank_currency == $supplier_currency) {
    Display::div_start('alloc_tbl');
    Gl_Allocation::show_allocatable(FALSE);
    Display::div_end();
  }
  Table::start('tablestyle width60');
  amount_row(_("Amount of Discount:"), 'discount');
  amount_row(_("Amount of Payment:"), 'amount');
  textarea_row(_("Memo:"), 'memo_', NULL, 22, 4);
  Table::end(1);
  if ($bank_currency != $supplier_currency) {
    Event::warning(_("The amount and discount are in the bank account's currency."), 0, 1);
  }
  submit_center('ProcessSuppPayment', _("Enter Payment"), TRUE, '', 'default');
  end_form();
  Page::end();

