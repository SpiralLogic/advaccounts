<?php
  use ADV\App\Display;
  use ADV\Core\Ajax;
  use ADV\Core\JS;
  use ADV\App\Bank\Bank;
  use ADV\App\SysTypes;
  use ADV\Core\DB\DB;
  use ADV\Core\Cell;
  use ADV\App\Dates;
  use ADV\App\Validation;
  use ADV\App\Dimensions;
  use ADV\Core\Table;
  use ADV\App\Forms;
  use ADV\Core\Input\Input;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::_setFocus('account');
  JS::_openWindow(950, 500);
  Page::start(_($help_context = "General Ledger Inquiry"), SA_GLTRANSVIEW);
  // Ajax updates
  //
  if (Input::_post('Show')) {
    Ajax::_activate('trans_tbl');
  }
  if (isset($_GET["account"])) {
    $_POST["account"] = $_GET["account"];
  }
  if (isset($_GET["TransFromDate"])) {
    //  $_POST["TransFromDate"] = $_GET["TransFromDate"];
  }
  if (isset($_GET["TransToDate"])) {
    //   $_POST["TransToDate"] = $_GET["TransToDate"];
  }
  if (isset($_GET["Dimension"])) {
    $_POST["Dimension"] = $_GET["Dimension"];
  }
  if (isset($_GET["Dimension2"])) {
    $_POST["Dimension2"] = $_GET["Dimension2"];
  }
  if (isset($_GET["amount_max"])) {
    $_POST["amount_max"] = $_GET["amount_max"];
  }
  if (isset($_GET["amount_min"])) {
    $_POST["amount_min"] = $_GET["amount_min"];
  }
  Forms::start();
  Display::div_start('trans_tbl');
  $dim = DB_Company::get_pref('use_dimension');
  Table::start('noborder');
  echo '<tr>';
  GL_UI::all_cells(_("Account:"), 'account', null, false, false, "All Accounts");
  Forms::dateCells(_("from:"), 'TransFromDate', '', null, -30);
  Forms::dateCells(_("to:"), 'TransToDate');
  echo '</tr>';
  Table::end();
  Table::start();
  echo '<tr>';
  if ($dim >= 1) {
    Dimensions::cells(_("Dimension") . " 1:", 'Dimension', null, true, " ", false, 1);
  }
  if ($dim > 1) {
    Dimensions::cells(_("Dimension") . " 2:", 'Dimension2', null, true, " ", false, 2);
  }
  Forms::amountCellsSmall(_("Amount min:"), 'amount_min', null);
  Forms::amountCellsSmall(_("Amount max:"), 'amount_max', null);
  Forms::submitCells('Show', _("Show"), '', '', 'default');
  echo '</tr>';
  Table::end();
  echo '<hr>';
  Display::div_end();
  if (Input::_post('Show') || Input::_post('account')) {
    show_results();
  }
  Forms::end();
  Page::end();

  function show_results() {
    if (!isset($_POST["account"])) {
      $_POST["account"] = null;
    }
    $act_name = $_POST["account"] ? GL_Account::get_name($_POST["account"]) : "";
    $sql      = GL_Trans::getSQL('2011-01-01', '2012-01-01', -1, $_POST["account"], null, Validation::input_num('amount_min'), Validation::input_num('amount_max'));
    if ($_POST["account"] != null) {
      Display::heading($_POST["account"] . "&nbsp;&nbsp;&nbsp;" . $act_name);
    }
    // Only show balances if an account is specified AND we're not filtering by amounts
    $cols = [ //
      _("Type")       => ['fun'=> 'formatType'], //
      _("#")          => ['fun'=> 'formatView'], //
      _("Date")       => ['type'=> 'date'],
      _("Account")    => ['ord'=> '', 'fun'=> 'formatAccount'],
      _("Person/Item")=> ['fun'=> 'formatPerson'], //
      _("Debit")      => ['fun'=> 'formatDebit'], //
      _("Credit")     => ['insert'=> true, 'fun'=> 'formatCredit'], //
      _("Balance")    => ['insert'=> true, 'fun'=> 'formatBalance'],
      _("Memo"),
      //

    ];
    /*    if ($_POST["account"] != null) {
    unset($cols[_("Account")]);
  }
  if (!$show_balances) {
    unset($cols[_("Balance")]);
  }
  if ($_POST["account"] != null && GL_Account::is_balancesheet($_POST["account"])) {
    $begin = "";
  } else {
    $begin = Dates::_beginFiscalYear();
    if (Dates::_isGreaterThan($begin, $_POST['TransFromDate'])) {
      $begin = $_POST['TransFromDate'];
    }
    $begin = Dates::_addDays($begin, -1);
  }
  $bfw = 0;
  if ($show_balances) {
    $bfw = GL_Trans::get_balance_from_to($begin, $_POST['TransFromDate'], $_POST["account"], $_POST['Dimension'], $_POST['Dimension2']);
    echo "<tr class='inquirybg'>";
    Cell::label("<span class='bold'>" . _("Opening Balance") . " - " . $_POST['TransFromDate'] . "</span>", "colspan=");
    Cell::debitOrCredit($bfw);
    Cell::label("");
    Cell::label("");
    echo '</tr>';
  }
  $running_total = $bfw;*/
    //  DB_Pager::kill('GL_Account');
    $table        = DB_Pager::newPager('GL_Account', $sql, $cols);
    $table->width = "90";
    $table->display();
    //end of while loop
    /*    if ($show_balances) {
      echo "<tr class='inquirybg'>";
      Cell::label("<span class='bold'>" . _("Ending Balance") . " - " . $_POST['TransToDate'] . "</span>", "colspan=");
      Cell::debitOrCredit($running_total);
      Cell::label("");
      Cell::label("");
      echo '</tr>';
    }
    Table::end(2);
    if (DB::_numRows() == 0) {
      Event::warning(_("No general ledger transactions have been created for the specified criteria."), 0, 1);
    }*/
  }

  function formatView($row) {
    return GL_UI::view($row["type"], $row["type_no"], $row["type_no"], true);
  }

  function formatAccount($row) {
    return $row["account"] . ' ' . GL_Account::get_name($row["account"]);
  }

  function formatPerson($row) {
    return Bank::payment_person_name($row["person_type_id"], $row["person_id"]);
  }

  function formatBalance($row) {
    static $running_total = 0;
    $running_total += $row["amount"];
    return Num::_priceFormat($running_total);
  }

  function formatType($row) {
    return SysTypes::$names[$row["type"]];
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function formatDebit($row) {
    $value = $row["amount"];
    if ($value > 0) {
      return '<span class="bold">' . Num::_priceFormat($value) . '</span>';
    }
    return '';
  }

  /**
   * @param $row
   *
   * @return int|string
   */
  function formatCredit($row) {
    $value = -$row["amount"];
    if ($value <= 0) {
      return '';
    }
    return '<span class="bold">' . Num::_priceFormat($value) . '</span>';
  }
