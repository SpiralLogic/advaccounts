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
	Page::start(_($help_context = "View Bank Payment"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	// get the pay-from bank payment info
	$result = Bank_Trans::get(ST_BANKPAYMENT, $trans_no);
	if (DB::num_rows($result) != 1) {
		Errors::show_db_error("duplicate payment bank transaction found", "");
	}
	$from_trans = DB::fetch($result);
	$company_currency = Banking::get_company_currency();
	$show_currencies = false;
	if ($from_trans['bank_curr_code'] != $company_currency) {
		$show_currencies = true;
	}
	Display::heading(_("GL Payment") . " #$trans_no");
	echo "<br>";
	start_table(Config::get('tables_style') . "  width=90%");
	if ($show_currencies) {
		$colspan1 = 5;
		$colspan2 = 8;
	} else {
		$colspan1 = 3;
		$colspan2 = 6;
	}
	start_row();
	label_cells(_("From Bank Account"), $from_trans['bank_account_name'], "class='tableheader2'");
	if ($show_currencies) {
		label_cells(_("Currency"), $from_trans['bank_curr_code'], "class='tableheader2'");
	}
	label_cells(_("Amount"), Num::format($from_trans['amount'], User::price_dec()), "class='tableheader2'", "align=right");
	label_cells(_("Date"), Dates::sql2date($from_trans['trans_date']), "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Pay To"), Banking::payment_person_name($from_trans['person_type_id'], $from_trans['person_id']), "class='tableheader2'", "colspan=$colspan1");
	label_cells(_("Payment Type"), $bank_transfer_types[$from_trans['account_type']], "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Reference"), $from_trans['ref'], "class='tableheader2'", "colspan=$colspan2");
	end_row();
	Display::comments_row(ST_BANKPAYMENT, $trans_no);
	end_table(1);
	$voided = Display::is_voided(ST_BANKPAYMENT, $trans_no, _("This payment has been voided."));
	$items = GL_Trans::get_many(ST_BANKPAYMENT, $trans_no);
	if (DB::num_rows($items) == 0) {
		Errors::warning(_("There are no items for this payment."));
	} else {
		Display::heading(_("Items for this Payment"));
		if ($show_currencies) {
			Display::heading(_("Item Amounts are Shown in :") . " " . $company_currency);
		}
		echo "<br>";
		start_table(Config::get('tables_style') . "  width=90%");
		$dim = DB_Company::get_pref('use_dimension');
		if ($dim == 2) {
			$th = array(
				_("Account Code"), _("Account Description"), _("Dimension") . " 1", _("Dimension") . " 2", _("Amount"), _("Memo"));
		} else if ($dim == 1) {
			$th = array(
				_("Account Code"), _("Account Description"), _("Dimension"), _("Amount"), _("Memo"));
		} else {
			$th = array(
				_("Account Code"), _("Account Description"), _("Amount"), _("Memo"));
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
					label_cell(Dimensions::get_string($item['dimension_id'], true));
				}
				if ($dim > 1) {
					label_cell(Dimensions::get_string($item['dimension2_id'], true));
				}
				amount_cell($item["amount"]);
				label_cell($item["memo_"]);
				end_row();
				$total_amount += $item["amount"];
			}
		}
		label_row(_("Total"), Num::format($total_amount, User::price_dec()), "colspan=" . (2 + $dim) . " align=right", "align=right");
		end_table(1);
		if (!$voided) {
			Display::allocations_from($from_trans['person_type_id'], $from_trans['person_id'], 1, $trans_no, -$from_trans['amount']);
		}
	}
	end_page(true);
?>