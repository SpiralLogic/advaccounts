<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	$page_security = 'SA_SUPPTRANSVIEW';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 500);
	page(_($help_context = "View Payment to Supplier"), true, false, "", $js);

	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}

	$receipt = get_supp_trans($trans_no, ST_SUPPAYMENT);

	$company_currency = Banking::get_company_currency();

	$show_currencies = false;
	$show_both_amounts = false;

	if (($receipt['bank_curr_code'] != $company_currency) || ($receipt['SupplierCurrCode'] != $company_currency))
		$show_currencies = true;

	if ($receipt['bank_curr_code'] != $receipt['SupplierCurrCode']) {
		$show_currencies = true;
		$show_both_amounts = true;
	}

	echo "<center>";

	ui_msgs::display_heading(_("Payment to Supplier") . " #$trans_no");

	echo "<br>";
	start_table(Config::get('tables.style2') . " width=90%");

	start_row();
	label_cells(_("To Supplier"), $receipt['supplier_name'], "class='tableheader2'");
	label_cells(_("From Bank Account"), $receipt['bank_account_name'], "class='tableheader2'");
	label_cells(_("Date Paid"), Dates::sql2date($receipt['tran_date']), "class='tableheader2'");
	end_row();
	start_row();
	if ($show_currencies)
		label_cells(_("Payment Currency"), $receipt['bank_curr_code'], "class='tableheader2'");
	label_cells(_("Amount"), number_format2(-$receipt['BankAmount'], user_price_dec()), "class='tableheader2'");
	label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
	end_row();
	start_row();
	if ($show_currencies) {
		label_cells(_("Supplier's Currency"), $receipt['SupplierCurrCode'], "class='tableheader2'");
	}
	if ($show_both_amounts)
		label_cells(_("Amount"), number_format2(-$receipt['Total'], user_price_dec()), "class='tableheader2'");
	label_cells(_("Reference"), $receipt['ref'], "class='tableheader2'");
	end_row();
	ui_view::comments_display_row(ST_SUPPAYMENT, $trans_no);

	end_table(1);

	$voided = ui_view::is_voided_display(ST_SUPPAYMENT, $trans_no, _("This payment has been voided."));

	// now display the allocations for this payment
	if (!$voided) {
		ui_view::display_allocations_from(PT_SUPPLIER, $receipt['supplier_id'], ST_SUPPAYMENT, $trans_no, -$receipt['Total']);
	}

	end_page(true);
?>