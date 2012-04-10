<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(800, 500);
  Page::start(_($help_context = "Transfer between Bank Accounts"), SA_BANKTRANSFER);
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (isset($_GET[ADDED_ID])) {
    $trans_no = $_GET[ADDED_ID];
    $trans_type = ST_BANKTRANSFER;
    Event::success(_("Transfer has been entered"));
    Display::note(GL_UI::view($trans_type, $trans_no, _("&View the GL Journal Entries for this Transfer")));
    Display::link_no_params($_SERVER['PHP_SELF'], _("Enter & Another Transfer"));
    Page::footer_exit();
  }
  if (isset($_POST['_DatePaid_changed'])) {
    Ajax::i()->activate('_ex_rate');
  }
  if (isset($_POST['AddPayment'])) {
    if (check_valid_entries() == TRUE) {
      handle_add_deposit();
    }
  }
  gl_payment_controls();
  Page::end();
  function gl_payment_controls() {
    $home_currency = Bank_Currency::for_company();
    start_form();
    start_outer_table('tablestyle2');
    table_section(1);
    Bank_Account::row(_("From Account:"), 'FromBankAccount', NULL, TRUE);
    Bank_Account::row(_("To Account:"), 'ToBankAccount', NULL, TRUE);
    date_row(_("Transfer Date:"), 'DatePaid', '', NULL, 0, 0, 0, NULL, TRUE);
    $from_currency = Bank_Currency::for_company($_POST['FromBankAccount']);
    $to_currency = Bank_Currency::for_company($_POST['ToBankAccount']);
    if ($from_currency != "" && $to_currency != "" && $from_currency != $to_currency) {
      amount_row(_("Amount:"), 'amount', NULL, NULL, $from_currency);
      amount_row(_("Bank Charge:"), 'charge', NULL, NULL, $from_currency);
      GL_ExchangeRate::display($from_currency, $to_currency, $_POST['DatePaid']);
    }
    else {
      amount_row(_("Amount:"), 'amount');
      amount_row(_("Bank Charge:"), 'charge');
    }
    table_section(2);
    ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKTRANSFER));
    textarea_row(_("Memo:"), 'memo_', NULL, 40, 4);
    end_outer_table(1); // outer table
    submit_center('AddPayment', _("Enter Transfer"), TRUE, '', 'default');
    end_form();
  }

  /**
   * @return bool
   */
  function check_valid_entries() {
    if (!Dates::is_date($_POST['DatePaid'])) {
      Event::error(_("The entered date is invalid ."));
      JS::set_focus('DatePaid');
      return FALSE;
    }
    if (!Dates::is_date_in_fiscalyear($_POST['DatePaid'])) {
      Event::error(_("The entered date is not in fiscal year . "));
      JS::set_focus('DatePaid');
      return FALSE;
    }
    if (!Validation::is_num('amount', 0)) {
      Event::error(_("The entered amount is invalid or less than zero ."));
      JS::set_focus('amount');
      return FALSE;
    }
    if (isset($_POST['charge']) && !Validation::is_num('charge', 0)) {
      Event::error(_("The entered amount is invalid or less than zero ."));
      JS::set_focus('charge');
      return FALSE;
    }
    if (isset($_POST['charge']) && Validation::input_num('charge') > 0 && DB_Company::get_pref('bank_charge_act') == '') {
      Event::error(_("The Bank Charge Account has not been set in System and General GL Setup ."));
      JS::set_focus('charge');
      return FALSE;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference ."));
      JS::set_focus('ref');
      return FALSE;
    }
    if (!Ref::is_new($_POST['ref'], ST_BANKTRANSFER)) {
      $_POST['ref'] = Ref::get_next(ST_BANKTRANSFER);
    }
    if ($_POST['FromBankAccount'] == $_POST['ToBankAccount']) {
      Event::error(_("The source and destination bank accouts cannot be the same ."));
      JS::set_focus('ToBankAccount');
      return FALSE;
    }
    return TRUE;
  }

  function handle_add_deposit() {
    $trans_no = GL_Bank::add_bank_transfer($_POST['FromBankAccount'], //
      $_POST['ToBankAccount'], //
      $_POST['DatePaid'], //
      Validation::input_num('amount'), //
      $_POST['ref'], //
      $_POST['memo_'], //
      Validation::input_num('charge'));
    Display::meta_forward($_SERVER['PHP_SELF'], "AddedID = $trans_no");
  }
