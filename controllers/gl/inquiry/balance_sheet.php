<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  $js = "";
  Page::start(_($help_context = "Balance Sheet Drilldown"), SA_GLANALYTIC);
  // Ajax updates
  if (get_post('Show')) {
    Ajax::i()->activate('balance_tbl');
  }
  if (isset($_GET["TransFromDate"])) {
    $_POST["TransFromDate"] = $_GET["TransFromDate"];
  }
  if (isset($_GET["TransToDate"])) {
    $_POST["TransToDate"] = $_GET["TransToDate"];
  }
  if (isset($_GET["AccGrp"])) {
    $_POST["AccGrp"] = $_GET["AccGrp"];
  }
  start_form();
  inquiry_controls();
  display_balance_sheet();
  end_form();
  Page::end();
  /**
   * @param $type
   * @param $typename
   * @param $from
   * @param $to
   * @param $convert
   * @param $drilldown
   *
   * @return int|mixed
   */
  function display_type($type, $typename, $from, $to, $convert, $drilldown) {
    global $levelptr, $k;
    $dimension = $dimension2 = 0;
    $acctstotal = 0;
    $typestotal = 0;
    //Get Accounts directly under this group/type
    $result = GL_Account::get_all(NULL, NULL, $type);
    while ($account = DB::fetch($result)) {
      $prev_balance = GL_Trans::get_balance_from_to("", $from, $account["account_code"], $dimension, $dimension2);
      $curr_balance = GL_Trans::get_from_to($from, $to, $account["account_code"], $dimension, $dimension2);
      if (!$prev_balance && !$curr_balance) {
        continue;
      }
      if ($drilldown && $levelptr == 0) {
        $url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/gl_account_inquiry.php?TransFromDate=" . $from . "&TransToDate=" . $to . "&account=" . $account['account_code'] . "'>" . $account['account_code'] . " " . $account['account_name'] . "</a>";
        start_row("class='stockmankobg'");
        label_cell($url);
        amount_cell(($curr_balance + $prev_balance) * $convert);
        end_row();
      }
      $acctstotal += $curr_balance + $prev_balance;
    }
    $levelptr = 1;
    //Get Account groups/types under this group/type
    $result = GL_Type::get_all(FALSE, FALSE, $type);
    while ($accounttype = DB::fetch($result)) {
      $typestotal += display_type($accounttype["id"], $accounttype["name"], $from, $to, $convert, $drilldown);
    }
    //Display Type Summary if total is != 0
    if (($acctstotal + $typestotal) != 0) {
      if ($drilldown && $type == $_POST["AccGrp"]) {
        start_row("class='inquirybg' style='font-weight:bold'");
        label_cell(_('Total') . " " . $typename);
        amount_cell(($acctstotal + $typestotal) * $convert);
        end_row();
      }
      //START Patch#1 : Display only direct child types
      $acctype1 = GL_Type::get($type);
      $parent1 = $acctype1["parent"];
      if ($drilldown && $parent1 == $_POST["AccGrp"]
      ) //END Patch#2
        //elseif ($drilldown && $type != $_POST["AccGrp"])
      {
        $url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/balance_sheet.php?TransFromDate=" . $from . "&TransToDate=" . $to . "&AccGrp=" . $type . "'>" . $typename . "</a>";
        alt_table_row_color($k);
        label_cell($url);
        amount_cell(($acctstotal + $typestotal) * $convert);
        end_row();
      }
    }
    return ($acctstotal + $typestotal);
  }

  function inquiry_controls() {
    start_table('tablestyle_noborder');
    date_cells(_("As at:"), 'TransToDate');
    submit_cells('Show', _("Show"), '', '', 'default');
    end_table();
    hidden('TransFromDate');
    hidden('AccGrp');
  }

  function display_balance_sheet() {
    $from = Dates::begin_fiscalyear();
    $to = $_POST['TransToDate'];
    $dim = DB_Company::get_pref('use_dimension');
    $dimension = $dimension2 = 0;
    $lconvert = $econvert = 1;
    if (isset($_POST["AccGrp"]) && (strlen($_POST['AccGrp']) > 0)) {
      $drilldown = 1;
    } // Deeper Level
    else {
      $drilldown = 0;
    } // Root level
    Display::div_start('balance_tbl');
    start_table('tablestyle width30');
    if (!$drilldown) //Root Level
    {
      $equityclose = $lclose = $calculateclose = 0.0;
      $parent = -1;
      //Get classes for BS
      $classresult = GL_Class::get_all(FALSE, 1);
      while ($class = DB::fetch($classresult)) {
        $classclose = 0.0;
        $convert = Systypes::get_class_type_convert($class["ctype"]);
        $ctype = $class["ctype"];
        $classname = $class["class_name"];
        //Print class Name
        table_section_title($class["class_name"]);
        //Get Account groups/types under this group/type
        $typeresult = GL_Type::get_all(FALSE, $class['cid'], -1);
        while ($accounttype = DB::fetch($typeresult)) {
          $TypeTotal = display_type($accounttype["id"], $accounttype["name"], $from, $to, $convert, $drilldown);
          //Print Summary
          if ($TypeTotal != 0) {
            $url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/balance_sheet.php?TransFromDate=" . $from . "&TransToDate=" . $to . "&AccGrp=" . $accounttype['id'] . "'>" . $accounttype['name'] . "</a>";
            alt_table_row_color($k);
            label_cell($url);
            amount_cell($TypeTotal * $convert);
            end_row();
          }
          $classclose += $TypeTotal;
        }
        //Print class Summary
        start_row("class='inquirybg' style='font-weight:bold'");
        label_cell(_('Total') . " " . $class["class_name"]);
        amount_cell($classclose * $convert);
        end_row();
        if ($ctype == CL_EQUITY) {
          $equityclose += $classclose;
          $econvert = $convert;
        }
        if ($ctype == CL_LIABILITIES) {
          $lclose += $classclose;
          $lconvert = $convert;
        }
        $calculateclose += $classclose;
      }
      if ($lconvert == 1) {
        $calculateclose *= -1;
      }
      //Final Report Summary
      $url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/profit_loss.php?TransFromDate=" . $from . "&TransToDate=" . $to . "&Compare=0'>" . _('Calculated Return') . "</a>";
      start_row("class='inquirybg' style='font-weight:bold'");
      label_cell($url);
      amount_cell($calculateclose);
      end_row();
      start_row("class='inquirybg' style='font-weight:bold'");
      label_cell(_('Total') . " " . _('Liabilities') . _(' and ') . _('Equities'));
      amount_cell($lclose * $lconvert + $equityclose * $econvert + $calculateclose);
      end_row();
    }
    else //Drill Down
    {
      //Level Pointer : Global variable defined in order to control display of root
      global $levelptr;
      $levelptr = 0;
      $accounttype = GL_Type::get($_POST["AccGrp"]);
      $classid = $accounttype["class_id"];
      $class = GL_Class::get($classid);
      $convert = Systypes::get_class_type_convert($class["ctype"]);
      //Print class Name
      table_section_title(GL_Type::get_name($_POST["AccGrp"]));
      $classclose = display_type($accounttype["id"], $accounttype["name"], $from, $to, $convert, $drilldown, PATH_TO_ROOT);
    }
    end_table(1); // outer table
    Display::div_end();
  }
