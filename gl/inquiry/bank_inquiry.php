<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(800, 500);
  Page::start(_($help_context = "Bank Statement"), SA_BANKTRANSVIEW);
  Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
  // Ajax updates
  //
  if (get_post('Show')) {
    Ajax::i()->activate('trans_tbl');
  }
  start_form();
  start_table('tablestyle_noborder');
  start_row();
  Bank_Account::cells(_("Account:"), 'bank_account', NULL);
  date_cells(_("From:"), 'TransAfterDate', '', NULL, -30);
  date_cells(_("To:"), 'TransToDate');
  submit_cells('Show', _("Show"), '', '', 'default');
  end_row();
  end_table();
  end_form();
  $date_after = Dates::date2sql($_POST['TransAfterDate']);
  $date_to = Dates::date2sql($_POST['TransToDate']);
  if (!isset($_POST['bank_account'])) {
    $_POST['bank_account'] = "";
  }
  $sql = "SELECT bank_trans.* FROM bank_trans
	WHERE bank_trans.bank_act = " . DB::escape($_POST['bank_account']) . "
	AND trans_date >= '$date_after'
	AND trans_date <= '$date_to'
	ORDER BY trans_date,bank_trans.id";
  $result = DB::query($sql, "The transactions for '" . $_POST['bank_account'] . "' could not be retrieved");
  Display::div_start('trans_tbl');
  $act = Bank_Account::get($_POST["bank_account"]);
  Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
  start_table('tablestyle');
  $th = array(
    _("Type"), _("#"), _("Reference"), _("Date"), _("Debit"), _("Credit"), _("Balance"), _("Person/Item"), ""
  );
  table_header($th);
  $sql = "SELECT SUM(amount) FROM bank_trans WHERE bank_act=" . DB::escape($_POST['bank_account']) . "
	AND trans_date < '$date_after'";
  $before_qty = DB::query($sql, "The starting balance on hand could not be calculated");
  start_row("class='inquirybg'");
  label_cell("<span class='bold'>" . _("Opening Balance") . " - " . $_POST['TransAfterDate'] . "</span>", "colspan=4");
  $bfw_row = DB::fetch_row($before_qty);
  $bfw = $bfw_row[0];
  debit_or_credit_cells($bfw);
  label_cell("");
  label_cell("", "colspan=2");
  end_row();
  $running_total = $bfw;
  $j = 1;
  $k = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    $running_total += $myrow["amount"];
    $trandate = Dates::sql2date($myrow["trans_date"]);
    label_cell($systypes_array[$myrow["type"]]);
    label_cell(GL_UI::trans_view($myrow["type"], $myrow["trans_no"]));
    label_cell(GL_UI::trans_view($myrow["type"], $myrow["trans_no"], $myrow['ref']));
    label_cell($trandate);
    debit_or_credit_cells($myrow["amount"]);
    amount_cell($running_total);
    label_cell(Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"]));
    label_cell(GL_UI::view($myrow["type"], $myrow["trans_no"]));
    end_row();
    if ($j == 12) {
      $j = 1;
      table_header($th);
    }
    $j++;
  }
  //end of while loop
  start_row("class='inquirybg'");
  label_cell("<span class='bold'>" . _("Ending Balance") . " - " . $_POST['TransToDate'] . "</span>", "colspan=4");
  debit_or_credit_cells($running_total);
  label_cell("");
  label_cell("", "colspan=2");
  end_row();
  end_table(2);
  Display::div_end();
  Page::end();
