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
	$page_security = 'SA_SUPPTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "View Payment to Supplier"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	$receipt = Purch_Trans::get($trans_no, ST_SUPPAYMENT);
	$company_currency = Bank_Currency::for_company();
	$show_currencies = false;
	$show_both_amounts = false;
	if (($receipt['bank_curr_code'] != $company_currency) || ($receipt['SupplierCurrCode'] != $company_currency)) {
		$show_currencies = true;
	}
	if ($receipt['bank_curr_code'] != $receipt['SupplierCurrCode']) {
		$show_currencies = true;
		$show_both_amounts = true;
	}
	echo "<div class='center'>";
	Display::heading(_("Payment to Supplier") . " #$trans_no");
	echo "<br>";
	start_table('tablestyle2 width90');
	start_row();
	label_cells(_("To Supplier"), $receipt['supplier_name'], "class='tableheader2'");
	label_cells(_("From Bank Account"), $receipt['bank_account_name'], "class='tableheader2'");
	label_cells(_("Date Paid"), Dates::sql2date($receipt['tran_date']), "class='tableheader2'");
	end_row();
	start_row();
	if ($show_currencies) {
		label_cells(_("Payment Currency"), $receipt['bank_curr_code'], "class='tableheader2'");
	}
	label_cells(_("Amount"), Num::format(-$receipt['BankAmount'], User::price_dec()), "class='tableheader2'");
	label_cells(_("Payment Type"), $bank_transfer_types[$receipt['BankTransType']], "class='tableheader2'");
	end_row();
	start_row();
	if ($show_currencies) {
		label_cells(_("Supplier's Currency"), $receipt['SupplierCurrCode'], "class='tableheader2'");
	}
	if ($show_both_amounts) {
		label_cells(_("Amount"), Num::format(-$receipt['Total'], User::price_dec()), "class='tableheader2'");
	}
	label_cells(_("Reference"), $receipt['ref'], "class='tableheader2'");
	end_row();
	DB_Comments::display_row(ST_SUPPAYMENT, $trans_no);
	end_table(1);
	$voided = Display::is_voided(ST_SUPPAYMENT, $trans_no, _("This payment has been voided."));
	// now display the allocations for this payment
	if (!$voided) {
		GL_Allocation::display(PT_SUPPLIER, $receipt['supplier_id'], ST_SUPPAYMENT, $trans_no, -$receipt['Total']);
	}
	if (Input::get('popup')) {
		return;
	}
	end_page(true);
?>