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
	$page_security = 'SA_SALESTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	include_once(APP_PATH . "sales/includes/sales_ui.php");
	JS::open_window(900, 600);
	Page::start(_($help_context = "View Sales Invoice"), true);
	if (isset($_GET["trans_no"])) {
		$trans_id = $_GET["trans_no"];
	} elseif (isset($_POST["trans_no"])) {
		$trans_id = $_POST["trans_no"];
	}
	// 3 different queries to get the information - what a JOKE !!!!
	$myrow = Sales_Trans::get($trans_id, ST_SALESINVOICE);
	$branch = Sales_Branch::get($myrow["branch_code"]);
	$sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER);
	start_table(Config::get('tables_style2') . "  width=90%");
	echo "<tr valign=top class='tableheader2'><th colspan=6>";
	Display::heading(sprintf(_("SALES INVOICE #%d"), $trans_id));
	echo "</td></tr>";
	echo "<tr valign=top><td colspan=3>";
	start_table(Config::get('tables_style') . "  width=100% ");
	label_row(_("Charge To"), $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "class='label' nowrap", "colspan=5");
	start_row();
	label_cells(_("Charge Branch"), $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "class='label' nowrap",
		"colspan=2");
	label_cells(_("Delivered To"), $sales_order["deliver_to"] . "<br>" . nl2br($sales_order["delivery_address"]),
		"class='label' nowrap",
		"colspan=2");
	end_row();
	start_row();
	label_cells(_("Reference"), $myrow["reference"], "class='label'");
	label_cells(_("Currency"), $sales_order["curr_code"], "class='label'");
	label_cells(_("Our Order No"), ui_view::get_customer_trans_view_str(ST_SALESORDER, $sales_order["order_no"]), "class='label'");
	end_row();
	start_row();
	label_cells(_("PO #"), $sales_order["customer_ref"], "class='label'");
	label_cells(_("Shipping Company"), $myrow["shipper_name"], "class='label'");
	label_cells(_("Sales Type"), $myrow["sales_type"], "class='label'");
	end_row();
	start_row();
	label_cells(_("Invoice Date"), Dates::sql2date($myrow["tran_date"]), "class='label'", "nowrap");
	label_cells(_("Due Date"), Dates::sql2date($myrow["due_date"]), "class='label'", "nowrap");
	label_cells(_("Deliveries"),
		ui_view::get_customer_trans_view_str(ST_CUSTDELIVERY, Sales_Trans::get_parent(ST_SALESINVOICE, $trans_id)), "class='label'");
	end_row();
	Display::comments_row(ST_SALESINVOICE, $trans_id);
	end_table();
	echo "</td></tr>";
	end_table(1); // outer table
	$result = Sales_Debtor_Trans::get(ST_SALESINVOICE, $trans_id);
	start_table(Config::get('tables_style') . "  width=95%");
	if (DB::num_rows() > 0) {
		$th = array(
			_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount %"), _("Total"));
		table_header($th);
		$k = 0; //row colour counter
		$sub_total = 0;
		while ($myrow2 = $result->fetch()) {
			if ($myrow2["quantity"] == 0) {
				continue;
			}
			alt_table_row_color($k);
			$value = Num::round(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::price_dec());
			$sub_total += $value;
			if ($myrow2["discount_percent"] == 0) {
				$display_discount = "";
			} else {
				$display_discount = Num::percent_format($myrow2["discount_percent"] * 100) . "%";
			}
			label_cell($myrow2["stock_id"]);
			label_cell($myrow2["StockDescription"]);
			qty_cell($myrow2["quantity"], false, Num::qty_dec($myrow2["stock_id"]));
			label_cell($myrow2["units"], "align=right");
			amount_cell($myrow2["unit_price"]);
			label_cell($display_discount, "nowrap align=right");
			amount_cell($value);
			end_row();
		} //end while there are line items to print out
	} else {
		Errors::warning(_("There are no line items on this invoice."), 1, 2);
	}
	$display_sub_tot = Num::price_format($sub_total);
	$display_freight = Num::price_format($myrow["ov_freight"]);
	/*Print out the invoice text entered */
	label_row(_("Sub-total"), $display_sub_tot, "colspan=6 align=right", "nowrap align=right width=15%");
	label_row(_("Shipping"), $display_freight, "colspan=6 align=right", "nowrap align=right");
	$tax_items = GL_Trans::get_tax_details(ST_SALESINVOICE, $trans_id);
	Display::customer_trans_tax_details($tax_items, 6);
	$display_total = Num::price_format($myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"]);
	label_row(_("TOTAL INVOICE"), $display_total, "colspan=6 align=right", "nowrap align=right");
	end_table(1);
	Display::is_voided(ST_SALESINVOICE, $trans_id, _("This invoice has been voided."));
	if (Input::get('popup')) {
		return;
	}
	$customer = new Contacts_Customer($myrow['debtor_no']);
	$emails = $customer->getEmailAddresses();
	submenu_print(_("&Print This Invoice"), ST_SALESINVOICE, $_GET['trans_no'], 'prtopt');
	Reporting::email_link($_GET['trans_no'], _("Email This Invoice"), true, ST_SALESINVOICE, 'EmailLink',null, $emails, 1);
	end_page(true);

?>