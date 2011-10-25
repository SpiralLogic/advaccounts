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

	include_once(APP_PATH . "purchasing/includes/purchasing_ui.php");

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 500);
	page(_($help_context = "View Supplier Invoice"), true, false, "", $js);

	if (isset($_GET["trans_no"])) {
		$trans_no = $_GET["trans_no"];
	}
	elseif (isset($_POST["trans_no"]))
	{
		$trans_no = $_POST["trans_no"];
	}

	$supp_trans = new suppTrans();
	$supp_trans->is_invoice = true;

	read_supp_invoice($trans_no, ST_SUPPINVOICE, $supp_trans);

	$supplier_curr_code = Banking::get_supplier_currency($supp_trans->supplier_id);

	ui_msgs::display_heading(_("SUPPLIER INVOICE") . " # " . $trans_no);
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
	if (!Banking::is_company_currency($supplier_curr_code))
		label_cells(_("Currency"), $supplier_curr_code, "class='tableheader2'");
	end_row();
	ui_view::comments_display_row(ST_SUPPINVOICE, $trans_no);

	end_table(1);

	$total_gl = display_gl_items($supp_trans, 2);
	$total_grn = display_grn_items($supp_trans, 2);

	$display_sub_tot = number_format2($total_gl + $total_grn, user_price_dec());

	start_table("width=95%  " . Config::get('tables_style'));
	label_row(_("Sub Total"), $display_sub_tot, "align=right", "nowrap align=right width=15%");

	$tax_items = get_trans_tax_details(ST_SUPPINVOICE, $trans_no);
	$tax_total = ui_view::display_supp_trans_tax_details($tax_items, 1, $supp_trans->ov_gst);

	$display_total = number_format2($supp_trans->ov_amount + $supp_trans->ov_gst, user_price_dec());

	label_row(_("TOTAL INVOICE"), $display_total, "colspan=1 align=right", "nowrap align=right");

	end_table(1);

	ui_view::is_voided_display(ST_SUPPINVOICE, $trans_no, _("This invoice has been voided."));

	end_page(true);

?>