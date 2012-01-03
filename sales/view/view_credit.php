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

	JS::open_window(900, 500);
	Page::start(_($help_context = "View Credit Note"), true);
	if (isset($_GET["trans_no"])) {
		$trans_id = $_GET["trans_no"];
	}
	elseif (isset($_POST["trans_no"])) {
		$trans_id = $_POST["trans_no"];
	}
	$myrow = Debtor_Trans::get($trans_id, ST_CUSTCREDIT);
	$branch = Sales_Branch::get($myrow["branch_code"]);
	Display::heading("<font color=red>" . sprintf(_("CREDIT NOTE #%d"), $trans_id) . "</font>");
	echo "<br>";
	start_table('tablestyle2 width95');
	echo "<tr class='top'><td>"; // outer table
	/*Now the customer charged to details in a sub table*/
	start_table('tablestyle width100');
	$th = array(_("Customer"));
	table_header($th);
	label_row(null, $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "nowrap");
	end_table();
	/*end of the small table showing charge to account details */
	echo "</td><td>"; // outer table
	start_table('tablestyle width100');
	$th = array(_("Branch"));
	table_header($th);
	label_row(null, $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "nowrap");
	end_table();
	echo "</td><td>"; // outer table
	start_table('tablestyle width100');
	start_row();
	label_cells(_("Ref"), $myrow["reference"], "class='tableheader2'");
	label_cells(_("Date"), Dates::sql2date($myrow["tran_date"]), "class='tableheader2'");
	label_cells(_("Currency"), $myrow["curr_code"], "class='tableheader2'");
	end_row();
	start_row();
	label_cells(_("Sales Type"), $myrow["sales_type"], "class='tableheader2'");
	label_cells(_("Shipping Company"), $myrow["shipper_name"], "class='tableheader2'");
	end_row();
	DB_Comments::display_row(ST_CUSTCREDIT, $trans_id);
	end_table();
	echo "</td></tr>";
	end_table(1); // outer table
	$sub_total = 0;
	$result = Debtor_TransDetail::get(ST_CUSTCREDIT, $trans_id);
	start_table('tablestyle width95');
	if (DB::num_rows($result) > 0) {
		$th = array(
			_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount %"), _("Total")
		);
		table_header($th);
		$k = 0; //row colour counter
		$sub_total = 0;
		while ($myrow2 = DB::fetch($result)) {
			if ($myrow2["quantity"] == 0) {
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
			label_cell($display_discount, "class=right");
			amount_cell($value);
			end_row();
		} //end while there are line items to print out
	}
	else {
		Errors::warning(_("There are no line items on this credit note."), 1, 2);
	}
	$display_sub_tot = Num::price_format($sub_total);
	$display_freight = Num::price_format($myrow["ov_freight"]);
	$credit_total = $myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"];
	$display_total = Num::price_format($credit_total);
	/*Print out the invoice text entered */
	if ($sub_total != 0) {
		label_row(_("Sub Total"), $display_sub_tot, "colspan=6 class=right", "nowrap class=right width=15%");
	}
	label_row(_("Shipping"), $display_freight, "colspan=6 class=right", "nowrap class=right");
	$tax_items = GL_Trans::get_tax_details(ST_CUSTCREDIT, $trans_id);
	Debtor_Trans::display_tax_details($tax_items, 6);
	label_row("<font color=red>" . _("TOTAL CREDIT") . "</font", "<span class='red'>$display_total</span>", "colspan=6 class=right", "nowrap class=right");
	end_table(1);
	$voided = Display::is_voided(ST_CUSTCREDIT, $trans_id, _("This credit note has been voided."));
	if (!$voided) {
		GL_Allocation::from(PT_CUSTOMER, $myrow['debtor_no'], ST_CUSTCREDIT, $trans_id, $credit_total);
	}
	if (Input::get('frame')) {
		return;
	}
	/* end of check to see that there was an invoice record to print */
	Display::submenu_print(_("&Print This Credit Note"), ST_CUSTCREDIT, $_GET['trans_no'], 'prtopt');
	Page::end(true);

?>