<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::setFocus('account');
  JS::openWindow(950, 500);
  Page::start(_($help_context = "General Ledger Inquiry"), SA_GLTRANSVIEW);
  // Ajax updates
  //
  if (Input::post('Show')) {
    Ajax::activate('trans_tbl');
  }
  if (isset($_GET["account"])) {
    $_POST["account"] = $_GET["account"];
  }
  if (isset($_GET["TransFromDate"])) {
    $_POST["TransFromDate"] = $_GET["TransFromDate"];
  }
  if (isset($_GET["TransToDate"])) {
    $_POST["TransToDate"] = $_GET["TransToDate"];
  }
  if (isset($_GET["Dimension"])) {
    $_POST["Dimension"] = $_GET["Dimension"];
  }
  if (isset($_GET["Dimension2"])) {
    $_POST["Dimension2"] = $_GET["Dimension2"];
  }
  if (isset($_GET["amount_min"])) {
    $_POST["amount_min"] = $_GET["amount_min"];
  }
  if (isset($_GET["amount_max"])) {
    $_POST["amount_max"] = $_GET["amount_max"];
  }
  if (!isset($_POST["amount_min"])) {
    $_POST["amount_min"] = Num::priceFormat(0);
  }
  if (!isset($_POST["amount_max"])) {
    $_POST["amount_max"] = Num::priceFormat(0);
  }

  gl_inquiry_controls();
  Display::div_start('trans_tbl');
  if (Input::post('Show') || Input::post('account')) {
    show_results();
  }
  Display::div_end();
  Page::end();
  function gl_inquiry_controls()
  {
    $dim = DB_Company::get_pref('use_dimension');
    Forms::start();
    Table::start('tablestyle_noborder');
    Row::start();
    GL_UI::all_cells(_("Account:"), 'account', null, false, false, "All Accounts");
    Forms::dateCells(_("from:"), 'TransFromDate', '', null, -30);
    Forms::dateCells(_("to:"), 'TransToDate');
    Row::end();
    Table::end();
    Table::start();
    Row::start();
    if ($dim >= 1) {
      Dimensions::cells(_("Dimension") . " 1:", 'Dimension', null, true, " ", false, 1);
    }
    if ($dim > 1) {
      Dimensions::cells(_("Dimension") . " 2:", 'Dimension2', null, true, " ", false, 2);
    }
    Forms::amountCellsSmall(_("Amount min:"), 'amount_min', null);
    Forms::amountCellsSmall(_("Amount max:"), 'amount_max', null);
    Forms::submitCells('Show', _("Show"), '', '', 'default');
    Row::end();
    Table::end();
    echo '<hr>';
    Forms::end();
  }

  function show_results()
  {
    global $systypes_array;
    if (!isset($_POST["account"])) {
      $_POST["account"] = null;
    }
    $act_name = $_POST["account"] ? GL_Account::get_name($_POST["account"]) : "";
    $dim      = DB_Company::get_pref('use_dimension');
    /*Now get the transactions */
    if (!isset($_POST['Dimension'])) {
      $_POST['Dimension'] = 0;
    }
    if (!isset($_POST['Dimension2'])) {
      $_POST['Dimension2'] = 0;
    }
    $result  = GL_Trans::get($_POST['TransFromDate'], $_POST['TransToDate'], -1, $_POST["account"], $_POST['Dimension'], $_POST['Dimension2'], null, Validation::input_num('amount_min'), Validation::input_num('amount_max'));
    $colspan = ($dim == 2 ? "6" : ($dim == 1 ? "5" : "4"));
    if ($_POST["account"] != null) {
      Display::heading($_POST["account"] . "&nbsp;&nbsp;&nbsp;" . $act_name);
    }
    // Only show balances if an account is specified AND we're not filtering by amounts
    $show_balances = $_POST["account"] != null && Validation::input_num("amount_min") == 0 && Validation::input_num("amount_max") == 0;
    Table::start('tablestyle grid');
    $first_cols = array(_("Type"), _("#"), _("Date"));
    if ($_POST["account"] == null) {
      $account_col = array(_("Account"));
    } else {
      $account_col = [];
    }
    if ($dim == 2) {
      $dim_cols = array(_("Dimension") . " 1", _("Dimension") . " 2");
    } else {
      if ($dim == 1) {
        $dim_cols = array(_("Dimension"));
      } else {
        $dim_cols = [];
      }
    }
    if ($show_balances) {
      $remaining_cols = array(_("Person/Item"), _("Debit"), _("Credit"), _("Balance"), _("Memo"));
    } else {
      $remaining_cols = array(_("Person/Item"), _("Debit"), _("Credit"), _("Memo"));
    }
    $th = array_merge($first_cols, $account_col, $dim_cols, $remaining_cols);
    Table::header($th);
    if ($_POST["account"] != null && GL_Account::is_balancesheet($_POST["account"])) {
      $begin = "";
    } else {
      $begin = Dates::beginFiscalYear();
      if (Dates::isGreaterThan($begin, $_POST['TransFromDate'])) {
        $begin = $_POST['TransFromDate'];
      }
      $begin = Dates::addDays($begin, -1);
    }
    $bfw = 0;
    if ($show_balances) {
      $bfw = GL_Trans::get_balance_from_to($begin, $_POST['TransFromDate'], $_POST["account"], $_POST['Dimension'], $_POST['Dimension2']);
      Row::start("class='inquirybg'");
      Cell::label("<span class='bold'>" . _("Opening Balance") . " - " . $_POST['TransFromDate'] . "</span>", "colspan=$colspan");
      Cell::debitOrCredit($bfw);
      Cell::label("");
      Cell::label("");
      Row::end();
    }
    $running_total = $bfw;
    $j             = 1;
    $k             = 0; //row colour counter
    while ($myrow = DB::fetch($result)) {

      $running_total += $myrow["amount"];
      $trandate = Dates::sqlToDate($myrow["tran_date"]);
      Cell::label($systypes_array[$myrow["type"]]);
      Cell::label(GL_UI::view($myrow["type"], $myrow["type_no"], $myrow["type_no"], true));
      Cell::label($trandate);
      if ($_POST["account"] == null) {
        Cell::label($myrow["account"] . ' ' . GL_Account::get_name($myrow["account"]));
      }
      if ($dim >= 1) {
        Cell::label(Dimensions::get_string($myrow['dimension_id'], true));
      }
      if ($dim > 1) {
        Cell::label(Dimensions::get_string($myrow['dimension2_id'], true));
      }
      Cell::label(Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"]));
      Cell::debitOrCredit($myrow["amount"]);
      if ($show_balances) {
        Cell::amount($running_total);
      }
      Cell::label($myrow['memo_']);
      Row::end();
      $j++;
      if ($j == 12) {
        $j = 1;
        Table::header($th);
      }
    }
    //end of while loop
    if ($show_balances) {
      Row::start("class='inquirybg'");
      Cell::label("<span class='bold'>" . _("Ending Balance") . " - " . $_POST['TransToDate'] . "</span>", "colspan=$colspan");
      Cell::debitOrCredit($running_total);
      Cell::label("");
      Cell::label("");
      Row::end();
    }
    Table::end(2);
    if (DB::numRows($result) == 0) {
      Event::warning(_("No general ledger transactions have been created for the specified criteria."), 0, 1);
    }
  }
