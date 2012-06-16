<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 600);
  $trans_type = $_GET['trans_type'];
  Page::start("", SA_SALESTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_id = $_GET["trans_no"];
  }
  if (isset($_POST)) {
    unset($_POST);
  }
  $receipt = Debtor_Trans::get($trans_id, $trans_type);
  echo "<br>";
  Table::start('tablestyle2 width90');
  echo "<tr class='tablerowhead top'><th colspan=6>";
  if ($trans_type == ST_CUSTPAYMENT) {
    Display::heading(sprintf(_("Customer Payment #%d"), $trans_id));
  }
  else {
    Display::heading(sprintf(_("Customer Refund #%d"), $trans_id));
  }
  echo "</td></tr>";
  Row::start();
  Cell::labels(_("From Customer"), $receipt['DebtorName']);
  Cell::labels(_("Into Bank Account"), $receipt['bank_account_name']);
  Cell::labels(_("Date of Deposit"), Dates::sql2date($receipt['tran_date']));
  Row::end();
  Row::start();
  Cell::labels(_("Payment Currency"), $receipt['curr_code']);
  Cell::labels(_("Amount"), Num::price_format($receipt['Total'] - $receipt['ov_discount']));
  Cell::labels(_("Discount"), Num::price_format($receipt['ov_discount']));
  Row::end();
  Row::start();
  Cell::labels(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']]);
  Cell::labels(_("Reference"), $receipt['reference'], 'class="label" colspan=1');
  Forms::end();
  Row::end();
  DB_Comments::display_row($trans_type, $trans_id);
  Table::end(1);
  $voided = Display::is_voided($trans_type, $trans_id, _("This customer payment has been voided."));
  if (!$voided && ($trans_type != ST_CUSTREFUND)) {
    GL_Allocation::from(PT_CUSTOMER, $receipt['debtor_id'], ST_CUSTPAYMENT, $trans_id, $receipt['Total']);
  }
  if (Input::get('frame')) {
    return;
  }
  Display::submenu_print(_("&Print This Receipt"), $trans_type, $_GET['trans_no'], 'prtopt');
  Page::end();

