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
	include_once(APP_PATH . "purchases/includes/purchasing_ui.php");
	JS::open_window(900, 500);
	Page::start(_($help_context = "View Supplier Invoice"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	} elseif (isset($_POST["trans_no"])) {
		$trans_no = $_POST["trans_no"];
	}
	$supp_trans = new Purch_Trans();
	$supp_trans->is_invoice = true;
	Purch_Invoice::get($trans_no, ST_SUPPINVOICE, $supp_trans);
	$supplier_curr_code = Banking::get_supplier_currency($supp_trans->supplier_id);
	Display::heading(_("SUPPLIER INVOICE") . " # " . $trans_no);
	echo "<br>";
	start_table(Config::get('tables_style') . "  width=95%");
	start_row();
	label_cells(_("Supplier"), $supp_trans->supplier_name, "class='tableheader2'");
	label_cells(_("Reference"), $supp_trans->reference, "class='tableheader2'");
	label_cells(_("Supplier's Reference"), $supp_trans->supp_reference, "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Invoice Date"), $supp_trans->tran_date, "class='tableheader2'");
	label_cells(_("Due Date"), $supp_trans->due_date, "class='tableheader2'");
	if (!Banking::is_company_currency($supplier_curr_code)) {
		label_cells(_("Currency"), $supplier_curr_code, "class='tableheader2'");
	}
	end_row();
	Display::comments_row(ST_SUPPINVOICE, $trans_no);
	end_table(1);
	$total_gl = display_gl_items($supp_trans, 2);
	$total_grn = display_grn_items($supp_trans, 2);
	$display_sub_tot = Num::format($total_gl + $total_grn, User::price_dec());
	start_table("width=95%  " . Config::get('tables_style'));
	label_row(_("Sub Total"), $display_sub_tot, "align=right", "nowrap align=right width=15%");
	$tax_items = GL_Trans::get_tax_details(ST_SUPPINVOICE, $trans_no);
	$tax_total = Display::supp_trans_tax_details($tax_items, 1, $supp_trans->ov_gst);
	$display_total = Num::format($supp_trans->ov_amount + $supp_trans->ov_gst, User::price_dec());
	label_row(_("TOTAL INVOICE"), $display_total, "colspan=1 align=right", "nowrap align=right");
	end_table(1);
	Display::is_voided(ST_SUPPINVOICE, $trans_no, _("This invoice has been voided."));
	end_page(true);

?>