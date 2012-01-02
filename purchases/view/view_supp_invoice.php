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
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_SUPPTRANSVIEW;

	JS::open_window(900, 500);
	Page::start(_($help_context = "View Supplier Invoice"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	elseif (isset($_POST["trans_no"])) {
		$trans_no = $_POST["trans_no"];
	}
	$supp_trans = new Creditor_Trans();
	$supp_trans->is_invoice = true;
	Purch_Invoice::get($trans_no, ST_SUPPINVOICE, $supp_trans);
	$supplier_curr_code = Bank_Currency::for_creditor($supp_trans->supplier_id);
	Display::heading(_("SUPPLIER INVOICE") . " # " . $trans_no);
	echo "<br>";
	start_table('tablestyle width95');
	start_row();
	label_cells(_("Supplier"), $supp_trans->supplier_name, "class='tableheader2'");
	label_cells(_("Reference"), $supp_trans->reference, "class='tableheader2'");
	label_cells(_("Supplier's Reference"), $supp_trans->supp_reference, "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Invoice Date"), $supp_trans->tran_date, "class='tableheader2'");
	label_cells(_("Due Date"), $supp_trans->due_date, "class='tableheader2'");
	if (!Bank_Currency::is_company($supplier_curr_code)) {
		label_cells(_("Currency"), $supplier_curr_code, "class='tableheader2'");
	}
	end_row();
	DB_Comments::display_row(ST_SUPPINVOICE, $trans_no);
	end_table(1);
	$total_gl = Purch_GLItem::display_items($supp_trans, 2);
	$total_grn = Purch_GRN::display_items($supp_trans, 2);
	$display_sub_tot = Num::format($total_gl + $total_grn, User::price_dec());
	start_table('tablestyle width95');
	label_row(_("Sub Total"), $display_sub_tot, "class=right", "nowrap class=right width=15%");
	$tax_items = GL_Trans::get_tax_details(ST_SUPPINVOICE, $trans_no);
	$tax_total = Creditor_Trans::trans_tax_details($tax_items, 1, $supp_trans->ov_gst);
	$display_total = Num::format($supp_trans->ov_amount + $supp_trans->ov_gst, User::price_dec());
	label_row(_("TOTAL INVOICE"), $display_total, "colspan=1 class=right", "nowrap class=right");
	end_table(1);
	Display::is_voided(ST_SUPPINVOICE, $trans_no, _("This invoice has been voided."));
	if (Input::get('frame')) {
		return;
	}
	Page::end(true);

?>