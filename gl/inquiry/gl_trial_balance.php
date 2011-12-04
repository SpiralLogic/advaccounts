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
	$page_security = 'SA_GLANALYTIC';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$js = "";
	Page::start(_($help_context = "Trial Balance"));

	// Ajax updates
	//
	if (Display::get_post('Show')) {
		$Ajax->activate('balance_tbl');
	}
	function gl_inquiry_controls()
	{
		Display::start_form();
		Display::start_table('tablestyle_noborder');
		date_cells(_("From:"), 'TransFromDate', '', null, -30);
		date_cells(_("To:"), 'TransToDate');
		check_cells(_("No zero values"), 'NoZero', null);
		check_cells(_("Only balances"), 'Balance', null);
		submit_cells('Show', _("Show"), '', '', 'default');
		Display::end_table();
		Display::end_form();
	}


	function display_trial_balance()
	{
		Display::div_start('balance_tbl');
		Display::start_table('tablestyle');
		$tableheader
		 = "<tr>
        <td rowspan=2 class='tableheader'>" . _("Account") . "</td>
        <td rowspan=2 class='tableheader'>" . _("Account Name") . "</td>
		<td colspan=2 class='tableheader'>" . _("Brought Forward") . "</td>
		<td colspan=2 class='tableheader'>" . _("This Period") . "</td>
		<td colspan=2 class='tableheader'>" . _("Balance") . "</td>
		</tr><tr>
		<td class='tableheader'>" . _("Debit") . "</td>
        <td class='tableheader'>" . _("Credit") . "</td>
		<td class='tableheader'>" . _("Debit") . "</td>
		<td class='tableheader'>" . _("Credit") . "</td>
        <td class='tableheader'>" . _("Debit") . "</td>
        <td class='tableheader'>" . _("Credit") . "</td>
        </tr>";
		echo $tableheader;
		$k = 0;
		$accounts = GL_Account::get_all();
		$pdeb     = $pcre = $cdeb = $ccre = $tdeb = $tcre = $pbal = $cbal = $tbal = 0;
		$begin    = Dates::begin_fiscalyear();
		if (Dates::date1_greater_date2($begin, $_POST['TransFromDate'])) {
			$begin = $_POST['TransFromDate'];
		}
		$begin = Dates::add_days($begin, -1);
		while ($account = DB::fetch($accounts))
		{
			$prev = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransFromDate'], false, false);
			$curr = GL_Trans::get_balance($account["account_code"], 0, 0, $_POST['TransFromDate'], $_POST['TransToDate'], true, true);
			$tot  = GL_Trans::get_balance($account["account_code"], 0, 0, $begin, $_POST['TransToDate'], false, true);
			if (check_value("NoZero") && !$prev['balance'] && !$curr['balance'] && !$tot['balance']) {
				continue;
			}
			Display::alt_table_row_color($k);
			$url = "<a hre'" . PATH_TO_ROOT . "/gl/inquiry/gl_account_inquiry.php?TransFromDate=" .
			 $_POST["TransFromDate"] . "&TransToDate=" . $_POST["TransToDate"] . "&account=" .
			 $account["account_code"] . "'>" . $account["account_code"] . "</a>";
			label_cell($url);
			label_cell($account["account_name"]);
			if (check_value('Balance')) {
				Display::debit_or_credit_cells($prev['balance']);
				Display::debit_or_credit_cells($curr['balance']);
				Display::debit_or_credit_cells($tot['balance']);
} else {
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
			Display::end_row();
		}
		//$prev = GL_Trans::get_balance(null, $begin, $_POST['TransFromDate'], false, false);
		//$curr = GL_Trans::get_balance(null, $_POST['TransFromDate'], $_POST['TransToDate'], true, true);
		//$tot = GL_Trans::get_balance(null, $begin, $_POST['TransToDate'], false, true);
		if (!check_value('Balance')) {
			Display::start_row("class='inquirybg' style='font-weight:bold'");
			label_cell(_("Total") . " - " . $_POST['TransToDate'], "colspan=2");
			amount_cell($pdeb);
			amount_cell($pcre);
			amount_cell($cdeb);
			amount_cell($ccre);
			amount_cell($tdeb);
			amount_cell($tcre);
			Display::end_row();
		}
		Display::start_row("class='inquirybg' style='font-weight:bold'");
		label_cell(_("Ending Balance") . " - " . $_POST['TransToDate'], "colspan=2");
		Display::debit_or_credit_cells($pbal);
		Display::debit_or_credit_cells($cbal);
		Display::debit_or_credit_cells($tbal);
		Display::end_row();
		Display::end_table(1);
		Display::div_end();
	}


	gl_inquiry_controls();
	display_trial_balance();

	end_page();

?>

