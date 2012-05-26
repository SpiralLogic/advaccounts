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
  Page::start(_($help_context = "Trial Balance"), SA_GLANALYTIC);
  // Ajax updates
  //
  if (get_post('Show')) {
    Ajax::i()->activate('balance_tbl');
  }
  gl_inquiry_controls();
  display_trial_balance();
  Page::end();
  function gl_inquiry_controls()
  {
    start_form();
    Table::start('tablestyle_noborder');
    date_cells(_("From:"), 'TransFromDate', '', null, -30);
    date_cells(_("To:"), 'TransToDate');
    check_cells(_("No zero values"), 'NoZero', null);
    check_cells(_("Only balances"), 'Balance', null);
    submit_cells('Show', _("Show"), '', '', 'default');
    Table::end();
    end_form();
  }

  function display_trial_balance()
  {
    Display::div_start('balance_tbl');
    Table::start('tablestyle grid');
    $tableheader
      = "<tr>
 <td rowspan=2 class='tablehead'>" . _("Account") . "</td>
 <td rowspan=2 class='tablehead'>" . _("Account Name") . "</td>
        <td colspan=2 class='tablehead'>" . _("Brought Forward") . "</td>
        <td colspan=2 class='tablehead'>" . _("This Period") . "</td>
        <td colspan=2 class='tablehead'>" . _("Balance") . "</td>
        </tr><tr>
        <td class='tablehead'>" . _("Debit") . "</td>
 <td class='tablehead'>" . _("Credit") . "</td>
        <td class='tablehead'>" . _("Debit") . "</td>
        <td class='tablehead'>" . _("Credit") . "</td>
 <td class='tablehead'>" . _("Debit") . "</td>
 <td class='tablehead'>" . _("Credit") . "</td>
 </tr>";
    echo $tableheader;
    $k        = 0;
    $accounts = GL_Account::get_all();
    $pdeb     = $pcre = $cdeb = $ccre = $tdeb = $tcre = $pbal = $cbal = $tbal = 0;
    $begin    = Dates::begin_fiscalyear();
    if (Dates::date1_greater_date2($begin, $_POST['TransFromDate'])) {
      $begin = $_POST['TransFromDate'];
    }
    $begin = Dates::add_days($begin, -1);
    while ($account = DB::fetch($accounts)) {
      $prev = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransFromDate'], false, false);
      $curr = GL_Trans::get_balance($account["account_code"], 0, 0, $_POST['TransFromDate'], $_POST['TransToDate'], true, true);
      $tot  = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransToDate'], false, true);
      if (check_value("NoZero") && !$prev['balance'] && !$curr['balance'] && !$tot['balance']) {
        continue;
      }
      $url = "<a href='" . BASE_URL . "gl/inquiry/gl_account.php?TransFromDate=" . $_POST["TransFromDate"] . "&TransToDate=" . $_POST["TransToDate"] . "&account=" . $account["account_code"] . "'>" . $account["account_code"] . "</a>";
      Cell::label($url);
      Cell::label($account["account_name"]);
      if (check_value('Balance')) {
        Cell::debitOrCredit($prev['balance']);
        Cell::debitOrCredit($curr['balance']);
        Cell::debitOrCredit($tot['balance']);
      } else {
        Cell::amount($prev['debit']);
        Cell::amount($prev['credit']);
        Cell::amount($curr['debit']);
        Cell::amount($curr['credit']);
        Cell::amount($tot['debit']);
        Cell::amount($tot['credit']);
        $pdeb += $prev['debit'];
        $pcre += $prev['credit'];
        $cdeb += $curr['debit'];
        $ccre += $curr['credit'];
        $tdeb += $tot['debit'];
        $tcre += $tot['credit'];
      }
      $pbal += $prev['balance'];
      $cbal += $curr['balance'];
      $tbal += $tot['balance'];
      Row::end();
    }
    //$prev = GL_Trans::get_balance(null, $begin, $_POST['TransFromDate'], false, false);
    //$curr = GL_Trans::get_balance(null, $_POST['TransFromDate'], $_POST['TransToDate'], true, true);
    //$tot = GL_Trans::get_balance(null, $begin, $_POST['TransToDate'], false, true);
    if (!check_value('Balance')) {
      Row::start("class='inquirybg' style='font-weight:bold'");
      Cell::label(_("Total") . " - " . $_POST['TransToDate'], "colspan=2");
      Cell::amount($pdeb);
      Cell::amount($pcre);
      Cell::amount($cdeb);
      Cell::amount($ccre);
      Cell::amount($tdeb);
      Cell::amount($tcre);
      Row::end();
    }
    Row::start("class='inquirybg' style='font-weight:bold'");
    Cell::label(_("Ending Balance") . " - " . $_POST['TransToDate'], "colspan=2");
    Cell::debitOrCredit($pbal);
    Cell::debitOrCredit($cbal);
    Cell::debitOrCredit($tbal);
    Row::end();
    Table::end(1);
    Display::div_end();
  }
