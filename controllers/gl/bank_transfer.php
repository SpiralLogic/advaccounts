<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::openWindow(800, 500);
  Page::start(_($help_context = "Transfer between Bank Accounts"), SA_BANKTRANSFER);
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  if (isset($_GET[ADDED_ID])) {
    $trans_no   = $_GET[ADDED_ID];
    $trans_type = ST_BANKTRANSFER;
    Event::success(_("Transfer has been entered"));
    Display::note(GL_UI::view($trans_type, $trans_no, _("&View the GL Journal Entries for this Transfer")));
    Display::link_no_params($_SERVER['DOCUMENT_URI'], _("Enter & Another Transfer"));
    Page::footer_exit();
  }
  if (isset($_POST['_DatePaid_changed'])) {
    Ajax::activate('_ex_rate');
  }
  if (isset($_POST['AddPayment'])) {
    if (check_valid_entries() == true) {
      handle_add_deposit();
    }
  }
  gl_payment_controls();
  Page::end();
  function gl_payment_controls()
  {
    $home_currency = Bank_Currency::for_company();
    Forms::start();
    Table::startOuter('tablestyle2');
    Table::section(1);
    Bank_Account::row(_("From Account:"), 'FromBankAccount', null, true);
    Bank_Account::row(_("To Account:"), 'ToBankAccount', null, true);
    Forms::dateRow(_("Transfer Date:"), 'DatePaid', '', null, 0, 0, 0, null, true);
    $from_currency = Bank_Currency::for_company($_POST['FromBankAccount']);
    $to_currency   = Bank_Currency::for_company($_POST['ToBankAccount']);
    if ($from_currency != "" && $to_currency != "" && $from_currency != $to_currency) {
      Forms::AmountRow(_("Amount:"), 'amount', null, null, $from_currency);
      Forms::AmountRow(_("Bank Charge:"), 'charge', null, null, $from_currency);
      GL_ExchangeRate::display($from_currency, $to_currency, $_POST['DatePaid']);
    } else {
      Forms::AmountRow(_("Amount:"), 'amount');
      Forms::AmountRow(_("Bank Charge:"), 'charge');
    }
    Table::section(2);
    Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_BANKTRANSFER));
    Forms::textareaRow(_("Memo:"), 'memo_', null, 40, 4);
    Table::endOuter(1); // outer table
    Forms::submitCenter('AddPayment', _("Enter Transfer"), true, '', 'default');
    Forms::end();
  }

  /**
   * @return bool
   */
  function check_valid_entries()
  {
    if (!Dates::isDate($_POST['DatePaid'])) {
      Event::error(_("The entered date is invalid ."));
      JS::setFocus('DatePaid');

      return false;
    }
    if (!Dates::isDateInFiscalYear($_POST['DatePaid'])) {
      Event::error(_("The entered date is not in fiscal year . "));
      JS::setFocus('DatePaid');

      return false;
    }
    if (!Validation::post_num('amount', 0)) {
      Event::error(_("The entered amount is invalid or less than zero ."));
      JS::setFocus('amount');

      return false;
    }
    if (isset($_POST['charge']) && !Validation::post_num('charge', 0)) {
      Event::error(_("The entered amount is invalid or less than zero ."));
      JS::setFocus('charge');

      return false;
    }
    if (isset($_POST['charge']) && Validation::input_num('charge') > 0 && DB_Company::get_pref('bank_charge_act') == '') {
      Event::error(_("The Bank Charge Account has not been set in System and General GL Setup ."));
      JS::setFocus('charge');

      return false;
    }
    if (!Ref::is_valid($_POST['ref'])) {
      Event::error(_("You must enter a reference ."));
      JS::setFocus('ref');

      return false;
    }
    if (!Ref::is_new($_POST['ref'], ST_BANKTRANSFER)) {
      $_POST['ref'] = Ref::get_next(ST_BANKTRANSFER);
    }
    if ($_POST['FromBankAccount'] == $_POST['ToBankAccount']) {
      Event::error(_("The source and destination bank accouts cannot be the same ."));
      JS::setFocus('ToBankAccount');

      return false;
    }

    return true;
  }

  function handle_add_deposit()
  {
    $trans_no = GL_Bank::add_bank_transfer($_POST['FromBankAccount'], //
                                           $_POST['ToBankAccount'], //
                                           $_POST['DatePaid'], //
                                           Validation::input_num('amount'), //
                                           $_POST['ref'], //
                                           $_POST['memo_'], //
                                           Validation::input_num('charge'));
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID = $trans_no");
  }
