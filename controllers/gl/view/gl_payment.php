<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "View Bank Payment"), SA_BANKTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_no = $_GET["trans_no"];
  }
  // get the pay-from bank payment info
  $result = Bank_Trans::get(ST_BANKPAYMENT, $trans_no);
  if (DB::num_rows($result) != 1) {
    Errors::db_error("duplicate payment bank transaction found", "");
  }
  $from_trans = DB::fetch($result);
  $company_currency = Bank_Currency::for_company();
  $show_currencies = FALSE;
  if ($from_trans['bank_curr_code'] != $company_currency) {
    $show_currencies = TRUE;
  }
  Display::heading(_("GL Payment") . " #$trans_no");
  echo "<br>";
  start_table('tablestyle width90');
  if ($show_currencies) {
    $colspan1 = 5;
    $colspan2 = 8;
  }
  else {
    $colspan1 = 3;
    $colspan2 = 6;
  }
  start_row();
  label_cells(_("From Bank Account"), $from_trans['bank_account_name'], "class='tablerowhead'");
  if ($show_currencies) {
    label_cells(_("Currency"), $from_trans['bank_curr_code'], "class='tablerowhead'");
  }
  label_cells(_("Amount"), Num::format($from_trans['amount'], User::price_dec()), "class='tablerowhead'", "class='right'");
  label_cells(_("Date"), Dates::sql2date($from_trans['trans_date']), "class='tablerowhead'");
  end_row();
  start_row();
  label_cells(_("Pay To"), Bank::payment_person_name($from_trans['person_type_id'], $from_trans['person_id']), "class='tablerowhead'", "colspan=$colspan1");
  label_cells(_("Payment Type"), $bank_transfer_types[$from_trans['account_type']], "class='tablerowhead'");
  end_row();
  start_row();
  label_cells(_("Reference"), $from_trans['ref'], "class='tablerowhead'", "colspan=$colspan2");
  end_row();
  DB_Comments::display_row(ST_BANKPAYMENT, $trans_no);
  end_table(1);
  $voided = Display::is_voided(ST_BANKPAYMENT, $trans_no, _("This payment has been voided."));
  $items = GL_Trans::get_many(ST_BANKPAYMENT, $trans_no);
  if (DB::num_rows($items) == 0) {
    Event::warning(_("There are no items for this payment."));
  }
  else {
    Display::heading(_("Items for this Payment"));
    if ($show_currencies) {
      Display::heading(_("Item Amounts are Shown in :") . " " . $company_currency);
    }
    echo "<br>";
    start_table('tablestyle width90');
    $dim = DB_Company::get_pref('use_dimension');
    if ($dim == 2) {
      $th = array(
        _("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Amount"), _("Memo")
      );
    }
    else {
      if ($dim == 1) {
        $th = array(
          _("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo")
        );
      }
      else {
        $th = array(
          _("Account Code"), _("Account Description"), _("Amount"), _("Memo")
        );
      }
    }
    table_header($th);
    $k = 0; //row colour counter
    $total_amount = 0;
    while ($item = DB::fetch($items)) {
      if ($item["account"] != $from_trans["account_code"]) {
        alt_table_row_color($k);
        label_cell($item["account"]);
        label_cell($item["account_name"]);
        if ($dim >= 1) {
          label_cell(Dimensions::get_string($item['dimension_id'], TRUE));
        }
        if ($dim > 1) {
          label_cell(Dimensions::get_string($item['dimension2_id'], TRUE));
        }
        amount_cell($item["amount"]);
        label_cell($item["memo_"]);
        end_row();
        $total_amount += $item["amount"];
      }
    }
    label_row(_("Total"), Num::format($total_amount, User::price_dec()), "colspan=" . (2 + $dim) . " class='right'", "class='right'");
    end_table(1);
    if (!$voided) {
      GL_Allocation::from($from_trans['person_type_id'], $from_trans['person_id'], 1, $trans_no, -$from_trans['amount']);
    }
  }
  Page::end(TRUE);