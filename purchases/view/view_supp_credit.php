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
	Page::start(_($help_context = "View Supplier Credit Note"), true);
	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	elseif (isset($_POST["trans_no"]))
	{
		$trans_no = $_POST["trans_no"];
	}
	$supp_trans = new Purch_Trans();
	$supp_trans->is_invoice = false;
	Purch_Invoice::get($trans_no, ST_SUPPCREDIT, $supp_trans);
	Display::heading(_("SUPPLIER CREDIT NOTE") . " # " . $trans_no);
	echo "<br>";
	start_table(Config::get('tables_style2'));
	start_row();
	label_cells(_("Supplier"), $supp_trans->supplier_name, "class='tableheader2'");
	label_cells(_("Reference"), $supp_trans->reference, "class='tableheader2'");
	label_cells(_("Supplier's Reference"), $supp_trans->supp_reference, "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Invoice Date"), $supp_trans->tran_date, "class='tableheader2'");
	label_cells(_("Due Date"), $supp_trans->due_date, "class='tableheader2'");
	label_cells(_("Currency"), Banking::get_supplier_currency($supp_trans->supplier_id), "class='tableheader2'");
	end_row();
	Display::comments_row(ST_SUPPCREDIT, $trans_no);
	end_table(1);
	$total_gl = display_gl_items($supp_trans, 3);
	$total_grn = display_grn_items($supp_trans, 2);
	$display_sub_tot = Num::format($total_gl + $total_grn, User::price_dec());
	start_table(Config::get('tables_style') . "  width=95%");
	label_row(_("Sub Total"), $display_sub_tot, "align=right", "nowrap align=right width=17%");
	$tax_items = GL_Trans::get_tax_details(ST_SUPPCREDIT, $trans_no);
	Display::supp_trans_tax_details($tax_items, 1);
	$display_total = Num::format(-($supp_trans->ov_amount + $supp_trans->ov_gst), User::price_dec());
	label_row(_("TOTAL CREDIT NOTE"), $display_total, "colspan=1 align=right", "nowrap align=right");
	end_table(1);
	$voided = Display::is_voided(ST_SUPPCREDIT, $trans_no, _("This credit note has been voided."));
	if (!$voided) {
		Display::allocations_from(PT_SUPPLIER, $supp_trans->supplier_id, ST_SUPPCREDIT, $trans_no, -($supp_trans->ov_amount + $supp_trans->ov_gst));
	}
	end_page(true);

?>