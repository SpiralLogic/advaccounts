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
	$page_security = SA_SALESTRANSVIEW;
	JS::open_window(900, 600);
	Page::start(_($help_context = "View Sales Dispatch"), true);
	if (isset($_GET["trans_no"])) {
		$trans_id = $_GET["trans_no"];
	}
	elseif (isset($_POST["trans_no"])) {
		$trans_id = $_POST["trans_no"];
	}
	// 3 different queries to get the information - what a JOKE !!!!
	$myrow = Debtor_Trans::get($trans_id, ST_CUSTDELIVERY);
	$branch = Sales_Branch::get($myrow["branch_code"]);
	$sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER);
	start_table('tablestyle2 width90');
	echo "<tr class='tableheader2 top'><th colspan=6>";
	Display::heading(sprintf(_("DISPATCH NOTE #%d"), $trans_id));
	echo "</td></tr>";
	echo "<tr class='top'><td colspan=3>";
	start_table('tablestyle width100');
	label_row(_("Charge To"), $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "class='label' nowrap", "colspan=5");
	start_row();
	label_cells(_("Charge Branch"), $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "class='label' nowrap", "colspan=2");
	label_cells(_("Delivered To"), $sales_order["deliver_to"] . "<br>" . nl2br($sales_order["delivery_address"]), "class='label' nowrap", "colspan=2");
	end_row();
	start_row();
	label_cells(_("Reference"), $myrow["reference"], "class='label'");
	label_cells(_("Currency"), $sales_order["curr_code"], "class='label'");
	label_cells(_("Our Order No"), Debtor::trans_view(ST_SALESORDER, $sales_order["order_no"]), "class='label'");
	end_row();
	start_row();
	label_cells(_("PO#"), $sales_order["customer_ref"], "class='label'");
	label_cells(_("Shipping Company"), $myrow["shipper_name"], "class='label'");
	label_cells(_("Sales Type"), $myrow["sales_type"], "class='label'");
	end_row();
	start_row();
	label_cells(_("Dispatch Date"), Dates::sql2date($myrow["tran_date"]), "class='label'", "nowrap");
	label_cells(_("Due Date"), Dates::sql2date($myrow["due_date"]), "class='label'", "nowrap");
	label_cells(_("Deliveries"), Debtor::trans_view(ST_CUSTDELIVERY, Debtor_Trans::get_parent(ST_SALESINVOICE, $trans_id)), "class='label'");
	end_row();
	DB_Comments::display_row(ST_CUSTDELIVERY, $trans_id);
	end_table();
	echo "</td></tr>";
	end_table(1); // outer table
	$result = Debtor_TransDetail::get(ST_CUSTDELIVERY, $trans_id);
	start_table('tablestyle width95');
	if (DB::num_rows($result) > 0) {
		$th = array(
			_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount %"), _("Total"));
		table_header($th);
		$k = 0; //row colour counter
		$sub_total = 0;
		while ($myrow2 = DB::fetch($result)) {
			if ($myrow2['quantity'] == 0) {
				continue;
			}
			alt_table_row_color($k);
			$value = Num::round(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::price_dec());
			$sub_total += $value;
			if ($myrow2["discount_percent"] == 0) {
				$display_discount = "";
			}
			else {
				$display_discount = Num::percent_format($myrow2["discount_percent"] * 100) . "%";
			}
			label_cell($myrow2["stock_id"]);
			label_cell($myrow2["StockDescription"]);
			qty_cell($myrow2["quantity"], false, Item::qty_dec($myrow2["stock_id"]));
			label_cell($myrow2["units"], "class=right");
			amount_cell($myrow2["unit_price"]);
			label_cell($display_discount, "nowrap class=right");
			amount_cell($value);
			end_row();
		} //end while there are line items to print out
	}
	else {
		Errors::warning(_("There are no line items on this dispatch."), 1, 2);
	}
	$display_sub_tot = Num::price_format($sub_total);
	$display_freight = Num::price_format($myrow["ov_freight"]);
	/*Print out the delivery note text entered */
	label_row(_("Sub-total"), $display_sub_tot, "colspan=6 class=right", "nowrap class=right width=15%");
	label_row(_("Shipping"), $display_freight, "colspan=6 class=right", "nowrap class=right");
	$tax_items = GL_Trans::get_tax_details(ST_CUSTDELIVERY, $trans_id);
	Debtor_Trans::display_tax_details($tax_items, 6);
	$display_total = Num::price_format($myrow["ov_freight"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"] + $myrow["ov_gst"]);
	label_row(_("TOTAL VALUE"), $display_total, "colspan=6 class=right", "nowrap class=right");
	end_table(1);
	Display::is_voided(ST_CUSTDELIVERY, $trans_id, _("This dispatch has been voided."));
	if (Input::get('frame')) {
		return;
	}
	Display::submenu_print(_("&Print This Delivery Note"), ST_CUSTDELIVERY, $_GET['trans_no'], 'prtopt');
	Page::end(true);
