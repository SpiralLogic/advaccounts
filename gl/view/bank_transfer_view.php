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
	$page_security = 'SA_BANKTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "View Bank Transfer"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	$result = Bank_Trans::get(ST_BANKTRANSFER, $trans_no);
	if (DB::num_rows($result) != 2) {
		Errors::show_db_error("Bank transfer does not contain two records");
	}
	$trans1 = DB::fetch($result);
	$trans2 = DB::fetch($result);
	if ($trans1["amount"] < 0) {
		$from_trans = $trans1; // from trans is the negative one
		$to_trans = $trans2;
	} else {
		$from_trans = $trans2;
		$to_trans = $trans1;
	}
	$company_currency = Bank_Currency::for_company();
	$show_currencies = false;
	$show_both_amounts = false;
	if (($from_trans['bank_curr_code'] != $company_currency) || ($to_trans['bank_curr_code'] != $company_currency)) {
		$show_currencies = true;
	}
	if ($from_trans['bank_curr_code'] != $to_trans['bank_curr_code']) {
		$show_currencies = true;
		$show_both_amounts = true;
	}
	Display::heading($systypes_array[ST_BANKTRANSFER] . " #$trans_no");
	echo "<br>";
	start_table('tablestyle width90');
	start_row();
	label_cells(_("From Bank Account"), $from_trans['bank_account_name'], "class='tableheader2'");
	if ($show_currencies) {
		label_cells(_("Currency"), $from_trans['bank_curr_code'], "class='tableheader2'");
	}
	label_cells(_("Amount"), Num::format(-$from_trans['amount'], User::price_dec()), "class='tableheader2'", "class=right");
	if ($show_currencies) {
		end_row();
		start_row();
	}
	label_cells(_("To Bank Account"), $to_trans['bank_account_name'], "class='tableheader2'");
	if ($show_currencies) {
		label_cells(_("Currency"), $to_trans['bank_curr_code'], "class='tableheader2'");
	}
	if ($show_both_amounts) {
		label_cells(
			_("Amount"), Num::format(
				$to_trans['amount'], User::price_dec()
			), "class='tableheader2'", "class=right"
		);
	}
	end_row();
	start_row();
	label_cells(_("Date"), Dates::sql2date($from_trans['trans_date']), "class='tableheader2'");
	label_cells(
		_("Transfer Type"), $bank_transfer_types[$from_trans['account_type']],
		"class='tableheader2'"
	);
	label_cells(_("Reference"), $from_trans['ref'], "class='tableheader2'");
	end_row();
	DB_Comments::display_row(ST_BANKTRANSFER, $trans_no);
	end_table(1);
	Display::is_voided(ST_BANKTRANSFER, $trans_no, _("This transfer has been voided."));
	end_page(true);
?>