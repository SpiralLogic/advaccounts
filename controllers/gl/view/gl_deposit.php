<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "View Bank Deposit"), SA_BANKTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  // get the pay-to bank payment info
  $result = Bank_Trans::get(ST_BANKDEPOSIT, $trans_no);
  if (DB::num_rows($result) != 1) {
    Errors::db_error("duplicate payment bank transaction found", "");
  }
  $to_trans         = DB::fetch($result);
  $company_currency = Bank_Currency::for_company();
  $show_currencies  = false;
  if ($to_trans['bank_curr_code'] != $company_currency) {
    $show_currencies = true;
  }
  echo "<div class='center'>";
  Display::heading(_("GL Deposit") . " #$trans_no");
  echo "<br>";
  Table::start('tablestyle width90');
  if ($show_currencies) {
    $colspan1 = 5;
    $colspan2 = 8;
  } else {
    $colspan1 = 3;
    $colspan2 = 6;
  }
  Row::start();
  Cell::labels(_("To Bank Account"), $to_trans['bank_account_name'], "class='tablerowhead'");
  if ($show_currencies) {
    Cell::labels(_("Currency"), $to_trans['bank_curr_code'], "class='tablerowhead'");
  }
  Cell::labels(_("Amount"), Num::format($to_trans['amount'], User::price_dec()), "class='tablerowhead'", "class='right'");
  Cell::labels(_("Date"), Dates::sql2date($to_trans['trans_date']), "class='tablerowhead'");
  Row::end();
  Row::start();
  Cell::labels(_("From"), Bank::payment_person_name($to_trans['person_type_id'], $to_trans['person_id']), "class='tablerowhead'", "colspan=$colspan1");
  Cell::labels(_("Deposit Type"), $bank_transfer_types[$to_trans['account_type']], "class='tablerowhead'");
  Row::end();
  Row::start();
  Cell::labels(_("Reference"), $to_trans['ref'], "class='tablerowhead'", "colspan=$colspan2");
  Row::end();
  DB_Comments::display_row(ST_BANKDEPOSIT, $trans_no);
  Table::end(1);
  Display::is_voided(ST_BANKDEPOSIT, $trans_no, _("This deposit has been voided."));
  $items = GL_Trans::get_many(ST_BANKDEPOSIT, $trans_no);
  if (DB::num_rows($items) == 0) {
    Event::warning(_("There are no items for this deposit."));
  } else {
    Display::heading(_("Items for this Deposit"));
    if ($show_currencies) {
      Display::heading(_("Item Amounts are Shown in :") . " " . $company_currency);
    }
    Table::start('tablestyle grid width90');
    $dim = DB_Company::get_pref('use_dimension');
    if ($dim == 2) {
      $th = array(
        _("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Amount"), _("Memo")
      );
    } else {
      if ($dim == 1) {
        $th = array(
          _("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo")
        );
      } else {
        $th = array(
          _("Account Code"), _("Account Description"), _("Amount"), _("Memo")
        );
      }
    }
    Table::header($th);
    $k            = 0; //row colour counter
    $total_amount = 0;
    while ($item = DB::fetch($items)) {
      if ($item["account"] != $to_trans["account_code"]) {

        Cell::label($item["account"]);
        Cell::label($item["account_name"]);
        if ($dim >= 1) {
          Cell::label(Dimensions::get_string($item['dimension_id'], true));
        }
        if ($dim > 1) {
          Cell::label(Dimensions::get_string($item['dimension2_id'], true));
        }
        Cell::amount($item["amount"]);
        Cell::label($item["memo_"]);
        Row::end();
        $total_amount += $item["amount"];
      }
    }
    Row::label(_("Total"), Num::format($total_amount, User::price_dec()), "colspan=" . (2 + $dim) . " class='right'", "class='right'");
    Table::end(1);
    GL_Allocation::from($to_trans['person_type_id'], $to_trans['person_id'], 2, $trans_no, $to_trans['amount']);
  }
  Page::end(true);
