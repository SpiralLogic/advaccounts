<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::_openWindow(950, 500);
  Page::start(_($help_context = "View Payment to Supplier"), SA_SUPPTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  $receipt           = Creditor_Trans::get($trans_no, ST_SUPPAYMENT);
  $company_currency  = Bank_Currency::for_company();
  $show_currencies   = false;
  $show_both_amounts = false;
  if (($receipt['bank_curr_code'] != $company_currency) || ($receipt['SupplierCurrCode'] != $company_currency)) {
    $show_currencies = true;
  }
  if ($receipt['bank_curr_code'] != $receipt['SupplierCurrCode']) {
    $show_currencies   = true;
    $show_both_amounts = true;
  }
  echo "<div class='center'>";
  Display::heading(_("Payment to Supplier") . " #$trans_no");
  echo "<br>";
  Table::start('tablestyle2 width90');
  Row::start();
  Cell::labels(_("To Supplier"), $receipt['supplier_name'], "class='tablerowhead'");
  Cell::labels(_("From Bank Account"), $receipt['bank_account_name'], "class='tablerowhead'");
  Cell::labels(_("Date Paid"), Dates::_sqlToDate($receipt['tran_date']), "class='tablerowhead'");
  Row::end();
  Row::start();
  if ($show_currencies) {
    Cell::labels(_("Payment Currency"), $receipt['bank_curr_code'], "class='tablerowhead'");
  }
  Cell::labels(_("Amount"), Num::_format(-$receipt['BankAmount'], User::price_dec()), "class='tablerowhead'");
  Cell::labels(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tablerowhead'");
  Row::end();
  Row::start();
  if ($show_currencies) {
    Cell::labels(_("Supplier's Currency"), $receipt['SupplierCurrCode'], "class='tablerowhead'");
  }
  if ($show_both_amounts) {
    Cell::labels(_("Amount"), Num::_format(-$receipt['Total'], User::price_dec()), "class='tablerowhead'");
  }
  Cell::labels(_("Reference"), $receipt['ref'], "class='tablerowhead'");
  Row::end();
  DB_Comments::display_row(ST_SUPPAYMENT, $trans_no);
  Table::end(1);
  $voided = Display::is_voided(ST_SUPPAYMENT, $trans_no, _("This payment has been voided."));
  // now display the allocations for this payment
  if (!$voided) {
    GL_Allocation::from(PT_SUPPLIER, $receipt['creditor_id'], ST_SUPPAYMENT, $trans_no, -$receipt['Total']);
  }
  if (Input::_get('frame')) {
    return;
  }
  Page::end(true);

