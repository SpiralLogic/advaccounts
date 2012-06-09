<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::open_window(800, 500);
  Page::start(_($help_context = "Bank Statement"), SA_BANKTRANSVIEW);
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  // Ajax updates
  //
  if (Input::post('Show')) {
    Ajax::i()->activate('trans_tbl');
  }
  Form::start();
  Table::start('tablestyle_noborder');
  Row::start();
  Bank_Account::cells(_("Account:"), 'bank_account', null);
   Form::dateCells(_("From:"), 'TransAfterDate', '', null, -30);
   Form::dateCells(_("To:"), 'TransToDate');
  Form::submitCells('Show', _("Show"), '', '', 'default');
  Row::end();
  Table::end();
  Form::end();
  $date_after = Dates::date2sql($_POST['TransAfterDate']);
  $date_to    = Dates::date2sql($_POST['TransToDate']);
  if (!isset($_POST['bank_account'])) {
    $_POST['bank_account'] = "";
  }
  $sql
          = "SELECT bank_trans.* FROM bank_trans
    WHERE bank_trans.bank_act = " . DB::escape($_POST['bank_account']) . "
    AND trans_date >= '$date_after'
    AND trans_date <= '$date_to'
    ORDER BY trans_date,bank_trans.id";
  $result = DB::query($sql, "The transactions for '" . $_POST['bank_account'] . "' could not be retrieved");
  Display::div_start('trans_tbl');
  $act = Bank_Account::get($_POST["bank_account"]);
  Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
  Table::start('tablestyle grid');
  $th = array(
    _("Type"), _("#"), _("Reference"), _("Date"), _("Debit"), _("Credit"), _("Balance"), _("Person/Item"), ""
  );
  Table::header($th);
  $sql        = "SELECT SUM(amount) FROM bank_trans WHERE bank_act=" . DB::escape($_POST['bank_account']) . "
    AND trans_date < '$date_after'";
  $before_qty = DB::query($sql, "The starting balance on hand could not be calculated");
  Row::start("class='inquirybg'");
  Cell::label("<span class='bold'>" . _("Opening Balance") . " - " . $_POST['TransAfterDate'] . "</span>", "colspan=4");
  $bfw_row = DB::fetch_row($before_qty);
  $bfw     = $bfw_row[0];
  Cell::debitOrCredit($bfw);
  Cell::label("");
  Cell::label("", "colspan=2");
  Row::end();
  $running_total = $bfw;
  $j             = 1;
  $k             = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {
    $running_total += $myrow["amount"];
    $trandate = Dates::sql2date($myrow["trans_date"]);
    Cell::label($systypes_array[$myrow["type"]]);
    Cell::label(GL_UI::trans_view($myrow["type"], $myrow["trans_no"]));
    Cell::label(GL_UI::trans_view($myrow["type"], $myrow["trans_no"], $myrow['ref']));
    Cell::label($trandate);
    Cell::debitOrCredit($myrow["amount"]);
    Cell::amount($running_total);
    Cell::label(Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"]));
    Cell::label(GL_UI::view($myrow["type"], $myrow["trans_no"]));
    Row::end();
    if ($j == 12) {
      $j = 1;
      Table::header($th);
    }
    $j++;
  }
  //end of while loop
  Row::start("class='inquirybg'");
  Cell::label("<span class='bold'>" . _("Ending Balance") . " - " . $_POST['TransToDate'] . "</span>", "colspan=4");
  Cell::debitOrCredit($running_total);
  Cell::label("");
  Cell::label("", "colspan=2");
  Row::end();
  Table::end(2);
  Display::div_end();
  Page::end();
