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
	$page_security = 'SA_GLTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::set_focus('account');
	JS::open_window(800, 500);
	Page::start(_($help_context = "General Ledger Inquiry"));

	// Ajax updates
	//
	if (get_post('Show')) {
		$Ajax->activate('trans_tbl');
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
		$_POST["amount_min"] = Num::price_format(0);
	}
	if (!isset($_POST["amount_max"])) {
		$_POST["amount_max"] = Num::price_format(0);
	}

	function gl_inquiry_controls()
	{
		$dim = DB_Company::get_pref('use_dimension');
		start_form();
		start_table('tablestyle_noborder');
		start_row();
		GL_UI::all_cells(_("Account:"), 'account', null, false, false, "All Accounts");
		date_cells(_("from:"), 'TransFromDate', '', null, -30);
		date_cells(_("to:"), 'TransToDate');
		end_row();
		end_table();
		start_table();
		start_row();
		if ($dim >= 1) {
			Dimensions::cells(_("Dimension") . " 1:", 'Dimension', null, true, " ", false, 1);
		}
		if ($dim > 1) {
			Dimensions::cells(_("Dimension") . " 2:", 'Dimension2', null, true, " ", false, 2);
		}
		small_amount_cells(_("Amount min:"), 'amount_min', null);
		small_amount_cells(_("Amount max:"), 'amount_max', null);
		submit_cells('Show', _("Show"), '', '', 'default');
		end_row();
		end_table();
		echo '<hr>';
		end_form();
	}


	function show_results()
	{
		global $systypes_array;
		if (!isset($_POST["account"])) {
			$_POST["account"] = null;
		}
		$act_name = $_POST["account"] ? GL_Account::get_name($_POST["account"]) : "";
		$dim = DB_Company::get_pref('use_dimension');
		/*Now get the transactions */
		if (!isset($_POST['Dimension'])) {
			$_POST['Dimension'] = 0;
		}
		if (!isset($_POST['Dimension2'])) {
			$_POST['Dimension2'] = 0;
		}
		$result = GL_Trans::get($_POST['TransFromDate'], $_POST['TransToDate'], -1, $_POST["account"], $_POST['Dimension'], $_POST['Dimension2'], null, Validation::input_num('amount_min'), Validation::input_num('amount_max'));
		$colspan = ($dim == 2 ? "6" : ($dim == 1 ? "5" : "4"));
		if ($_POST["account"] != null) {
			Display::heading($_POST["account"] . "&nbsp;&nbsp;&nbsp;" . $act_name);
		}
		// Only show balances if an account is specified AND we're not filtering by amounts
		$show_balances = $_POST["account"] != null && Validation::input_num("amount_min") == 0 && Validation::input_num("amount_max") == 0;
		start_table('tablestyle');
		$first_cols = array(_("Type"), _("#"), _("Date"));
		if ($_POST["account"] == null) {
			$account_col = array(_("Account"));
		} else {
			$account_col = array();
		}
		if ($dim == 2) {
			$dim_cols = array(_("Dimension") . " 1", _("Dimension") . " 2");
		} else if ($dim == 1) {
			$dim_cols = array(_("Dimension"));
		} else {
			$dim_cols = array();
		}
		if ($show_balances) {
			$remaining_cols = array(_("Person/Item"), _("Debit"), _("Credit"), _("Balance"), _("Memo"));
		} else {
			$remaining_cols = array(_("Person/Item"), _("Debit"), _("Credit"), _("Memo"));
		}
		$th = array_merge($first_cols, $account_col, $dim_cols, $remaining_cols);
		table_header($th);
		if ($_POST["account"] != null && GL_Account::is_balancesheet($_POST["account"])) {
			$begin = "";
		} else {
			$begin = Dates::begin_fiscalyear();
			if (Dates::date1_greater_date2($begin, $_POST['TransFromDate'])) {
				$begin = $_POST['TransFromDate'];
			}
			$begin = Dates::add_days($begin, -1);
		}
		$bfw = 0;
		if ($show_balances) {
			$bfw = GL_Trans::get_balance_from_to($begin, $_POST['TransFromDate'], $_POST["account"], $_POST['Dimension'], $_POST['Dimension2']);
			start_row("class='inquirybg'");
			label_cell("<b>" . _("Opening Balance") . " - " . $_POST['TransFromDate'] . "</b>", "colspan=$colspan");
			debit_or_credit_cells($bfw);
			label_cell("");
			label_cell("");
			end_row();
		}
		$running_total = $bfw;
		$j = 1;
		$k = 0; //row colour counter
		while ($myrow = DB::fetch($result)) {
			Display::alt_table_row_color($k);
			$running_total += $myrow["amount"];
			$trandate = Dates::sql2date($myrow["tran_date"]);
			label_cell($systypes_array[$myrow["type"]]);
			label_cell(GL_UI::view($myrow["type"], $myrow["type_no"], $myrow["type_no"], true));
			label_cell($trandate);
			if ($_POST["account"] == null) {
				label_cell($myrow["account"] . ' ' . GL_Account::get_name($myrow["account"]));
			}
			if ($dim >= 1) {
				label_cell(Dimensions::get_string($myrow['dimension_id'], true));
			}
			if ($dim > 1) {
				label_cell(Dimensions::get_string($myrow['dimension2_id'], true));
			}
			label_cell(Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"]));
			debit_or_credit_cells($myrow["amount"]);
			if ($show_balances) {
				amount_cell($running_total);
			}
			label_cell($myrow['memo_']);
			end_row();
			$j++;
			if ($j == 12) {
				$j = 1;
				table_header($th);
			}
		}
		//end of while loop
		if ($show_balances) {
			start_row("class='inquirybg'");
			label_cell("<b>" . _("Ending Balance") . " - " . $_POST['TransToDate'] . "</b>", "colspan=$colspan");
			debit_or_credit_cells($running_total);
			label_cell("");
			label_cell("");
			end_row();
		}
		end_table(2);
		if (DB::num_rows($result) == 0) {
			Errors::warning(_("No general ledger transactions have been created for the specified criteria."), 0, 1);
		}
	}


	gl_inquiry_controls();
	Display::div_start('trans_tbl');
	if (get_post('Show') || get_post('account')) {
		show_results();
	}
	Display::div_end();

	end_page();

?>
