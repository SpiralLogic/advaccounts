<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
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
  function gl_inquiry_controls() {
    start_form();
    start_table('tablestyle_noborder');
    date_cells(_("From:"), 'TransFromDate', '', NULL, -30);
    date_cells(_("To:"), 'TransToDate');
    check_cells(_("No zero values"), 'NoZero', NULL);
    check_cells(_("Only balances"), 'Balance', NULL);
    submit_cells('Show', _("Show"), '', '', 'default');
    end_table();
    end_form();
  }

  function display_trial_balance() {
    Display::div_start('balance_tbl');
    start_table('tablestyle');
    $tableheader = "<tr>
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
    $k = 0;
    $accounts = GL_Account::get_all();
    $pdeb = $pcre = $cdeb = $ccre = $tdeb = $tcre = $pbal = $cbal = $tbal = 0;
    $begin = Dates::begin_fiscalyear();
    if (Dates::date1_greater_date2($begin, $_POST['TransFromDate'])) {
      $begin = $_POST['TransFromDate'];
    }
    $begin = Dates::add_days($begin, -1);
    while ($account = DB::fetch($accounts)) {
      $prev = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransFromDate'], FALSE, FALSE);
      $curr = GL_Trans::get_balance($account["account_code"], 0, 0, $_POST['TransFromDate'], $_POST['TransToDate'], TRUE, TRUE);
      $tot = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransToDate'], FALSE, TRUE);
      if (check_value("NoZero") && !$prev['balance'] && !$curr['balance'] && !$tot['balance']) {
        continue;
      }
      alt_table_row_color($k);
      $url = "<a href='" . PATH_TO_ROOT . "/gl/inquiry/gl_account_inquiry.php?TransFromDate=" . $_POST["TransFromDate"] .
        "&TransToDate=" . $_POST["TransToDate"] . "&account=" . $account["account_code"] . "'>" . $account["account_code"] . "</a>";
      label_cell($url);
      label_cell($account["account_name"]);
      if (check_value('Balance')) {
        debit_or_credit_cells($prev['balance']);
        debit_or_credit_cells($curr['balance']);
        debit_or_credit_cells($tot['balance']);
      }
      else {
        amount_cell($prev['debit']);
        amount_cell($prev['credit']);
        amount_cell($curr['debit']);
        amount_cell($curr['credit']);
        amount_cell($tot['debit']);
        amount_cell($tot['credit']);
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
      end_row();
    }
    //$prev = GL_Trans::get_balance(null, $begin, $_POST['TransFromDate'], false, false);
    //$curr = GL_Trans::get_balance(null, $_POST['TransFromDate'], $_POST['TransToDate'], true, true);
    //$tot = GL_Trans::get_balance(null, $begin, $_POST['TransToDate'], false, true);
    if (!check_value('Balance')) {
      start_row("class='inquirybg' style='font-weight:bold'");
      label_cell(_("Total") . " - " . $_POST['TransToDate'], "colspan=2");
      amount_cell($pdeb);
      amount_cell($pcre);
      amount_cell($cdeb);
      amount_cell($ccre);
      amount_cell($tdeb);
      amount_cell($tcre);
      end_row();
    }
    start_row("class='inquirybg' style='font-weight:bold'");
    label_cell(_("Ending Balance") . " - " . $_POST['TransToDate'], "colspan=2");
    debit_or_credit_cells($pbal);
    debit_or_credit_cells($cbal);
    debit_or_credit_cells($tbal);
    end_row();
    end_table(1);
    Display::div_end();
  }
