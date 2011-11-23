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
	Page::start(_($help_context = "View Purchase Order"), true);
	if (!isset($_GET['trans_no'])) {
		die ("<br>" . _("This page must be called with a purchase order number to review."));
	}
	$purchase_order = new Purch_Order;
	Purch_Order::get($_GET['trans_no'], $purchase_order);
	echo "<br>";
	Purch_Order::display_summary($purchase_order, true);
	start_table(Config::get('tables_style') . "  width=90%", 6);
	Display::heading(_("Line Details"));
	start_table("colspan=9 " . Config::get('tables_style') . " width=100%");
	$th = array(
		_("Code"), _("Item"), _("Qty"), _("Unit"), _("Price"), _("Disc"), _("Total"), _("Needed By"),
		_("Received"), _("Invoiced"));
	table_header($th);
	$total = $k = 0;
	$overdue_items = false;
	foreach ($purchase_order->line_items as $stock_item) {
		$line_total = $stock_item->quantity * $stock_item->price * (1 - $stock_item->discount);
		// if overdue and outstanding quantities, then highlight as so
		if (($stock_item->quantity - $stock_item->qty_received > 0) && Dates::date1_greater_date2(Dates::Today(),
			$stock_item->req_del_date)
		) {
			start_row("class='overduebg'");
			$overdue_items = true;
		} else {
			alt_table_row_color($k);
		}
		label_cell($stock_item->stock_id);
		label_cell($stock_item->description);
		$dec = Num::qty_dec($stock_item->stock_id);
		qty_cell($stock_item->quantity, false, $dec);
		label_cell($stock_item->units);
		amount_decimal_cell($stock_item->price);
		percent_cell($stock_item->discount * 100);
		amount_cell($line_total);
		label_cell($stock_item->req_del_date);
		qty_cell($stock_item->qty_received, false, $dec);
		qty_cell($stock_item->qty_inv, false, $dec);
		end_row();
		$total += $line_total;
	}
	$display_total = Num::format($total, User::price_dec());
	label_row(_("Total Excluding Tax/Shipping"), $display_total, "align=right colspan=6", "nowrap align=right", 3);
	end_table();
	if ($overdue_items) {
		Errors::warning(_("Marked items are overdue."), 0, 0, "class='overduefg'");
	}
	//----------------------------------------------------------------------------------------------------
	$k = 0;
	$grns_result = Purch_GRN::get_for_po($_GET['trans_no']);
	if (DB::num_rows($grns_result) > 0) {
		echo "</td><td valign=top>"; // outer table
		Display::heading(_("Deliveries"));
		start_table(Config::get('tables_style'));
		$th = array(_("#"), _("Reference"), _("Delivered On"));
		table_header($th);
		while ($myrow = DB::fetch($grns_result)) {
			alt_table_row_color($k);
			label_cell(ui_view::get_trans_view_str(ST_SUPPRECEIVE, $myrow["id"]));
			label_cell($myrow["reference"]);
			label_cell(Dates::sql2date($myrow["delivery_date"]));
			end_row();
		}
		end_table();
	}
	$invoice_result = Purch_Invoice::get_po_credits($_GET['trans_no']);
	$k = 0;
	if (DB::num_rows($invoice_result) > 0) {
		echo "</td><td valign=top>"; // outer table
		Display::heading(_("Invoices/Credits"));
		start_table(Config::get('tables_style'));
		$th = array(_("#"), _("Date"), _("Total"));
		table_header($th);
		while ($myrow = DB::fetch($invoice_result)) {
			alt_table_row_color($k);
			label_cell(ui_view::get_trans_view_str($myrow["type"], $myrow["trans_no"]));
			label_cell(Dates::sql2date($myrow["tran_date"]));
			amount_cell($myrow["Total"]);
			end_row();
		}
		end_table();
	}
	echo "</td></tr>";
	end_table(1); // outer table
	if (Input::get('popup')) {
		return;
	}
	submenu_print(_("Print This Order"), ST_PURCHORDER, $_GET['trans_no'], 'prtopt');
	submenu_option(_("&Edit This Order"), "/purchases/po_entry_items.php?ModifyOrderNumber=" . $_GET['trans_no']);
	//----------------------------------------------------------------------------------------------------
	end_page(true);

?>
