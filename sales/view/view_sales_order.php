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
	JS::open_window(900, 600);
	if ($_GET['trans_type'] == ST_SALESQUOTE) {
		Page::start(_($help_context = "View Sales Quotation"), true);
	}
	else {
		Page::start(_($help_context = "View Sales Order"), true);
	}
	if (isset($_SESSION['View'])) {
		unset ($_SESSION['View']);
	}
	$_SESSION['View'] = $view = new Sales_Order($_GET['trans_type'], $_GET['trans_no'], true);
	start_table('tablesstyle2 pad0 width95');
	echo "<tr class='tableheader2 top'><th colspan=4>";
	if ($_GET['trans_type'] != ST_SALESQUOTE) {
		Display::heading(sprintf(_("Sales Order #%d"), $_GET['trans_no']));
	}
	else {
		Display::heading(sprintf(_("Sales Quotation #%d"), $_GET['trans_no']));
	}
	echo "</td></tr>";
	echo "<tr class='top'><td colspan=4>";
	start_table('tablestyle width100');
	start_row();
	label_cells(_("Customer Name"), $_SESSION['View']->customer_name, "class='label pointer customer_id_label'", 'class="pointer customer_id_label"');
	hidden("customer_id", $_SESSION['View']->customer_id);
	label_cells(_("Deliver To Branch"), $_SESSION['View']->deliver_to, "class='label'");
	label_cells(_("Person Ordering"), nl2br($_SESSION['View']->name), "class='label'");
	end_row();
	start_row();
	label_cells(_("Reference"), $_SESSION['View']->reference, "class='label'");
	if ($_GET['trans_type'] == ST_SALESQUOTE) {
		label_cells(_("Valid until"), $_SESSION['View']->due_date, "class='label'");
	}
	else {
		label_cells(_("Requested Delivery"), $_SESSION['View']->due_date, "class='label'");
	}
	label_cells(_("Telephone"), $_SESSION['View']->phone, "class='label'");
	end_row();
	start_row();
	label_cells(_("Customer PO #"), $_SESSION['View']->cust_ref, "class='label'");
	label_cells(_("Deliver From Location"), $_SESSION['View']->location_name, "class='label'");
	label_cells(_("Delivery Address"), nl2br($_SESSION['View']->delivery_address), "class='label'");
	end_row();
	start_row();
	label_cells(_("Order Currency"), $_SESSION['View']->customer_currency, "class='label'");
	label_cells(_("Ordered On"), $_SESSION['View']->document_date, "class='label'");
	label_cells(_("E-mail"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>", "class='label'", "colspan=3");
	end_row();
	label_row(_("Comments"), $_SESSION['View']->Comments, "class='label'", "colspan=5");
	end_table();
	if ($_GET['trans_type'] != ST_SALESQUOTE) {
		echo "</td></tr><tr><td class='top'>";
		start_table('tablestyle width90');
		Display::heading(_("Delivery Notes"));
		$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
		table_header($th);
		$sql = "SELECT * FROM debtor_trans WHERE type=" . ST_CUSTDELIVERY . " AND order_=" . DB::escape($_GET['trans_no']);
		$result = DB::query($sql, "The related delivery notes could not be retreived");
		$delivery_total = 0;
		$k = 0;
		$dn_numbers = array();
		while ($del_row = DB::fetch($result)) {
			alt_table_row_color($k);
			$dn_numbers[] = $del_row["trans_link"];
			$this_total = $del_row["ov_freight"] + $del_row["ov_amount"] + $del_row["ov_freight_tax"] + $del_row["ov_gst"];
			$delivery_total += $this_total;
			label_cell(Debtor::trans_view($del_row["type"], $del_row["trans_no"]));
			label_cell($del_row["reference"]);
			label_cell(Dates::sql2date($del_row["tran_date"]));
			amount_cell($this_total);
			end_row();
		}
		label_row(null, Num::price_format($delivery_total), " ", "colspan=4 class=right");
		end_table();
		echo "</td><td class='top'>";
		start_table('tablestyle width90');
		Display::heading(_("Sales Invoices"));
		$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
		table_header($th);
		$inv_numbers = array();
		$invoices_total = 0;
		if (count($dn_numbers)) {
			$sql = "SELECT * FROM debtor_trans WHERE type=" . ST_SALESINVOICE . " AND trans_no IN(" . implode(',', array_values($dn_numbers)) . ")";
			$result = DB::query($sql, "The related invoices could not be retreived");
			$k = 0;
			while ($inv_row = DB::fetch($result)) {
				alt_table_row_color($k);
				$this_total = $inv_row["ov_freight"] + $inv_row["ov_freight_tax"] + $inv_row["ov_gst"] + $inv_row["ov_amount"];
				$invoices_total += $this_total;
				$inv_numbers[] = $inv_row["trans_no"];
				label_cell(Debtor::trans_view($inv_row["type"], $inv_row["trans_no"]));
				label_cell($inv_row["reference"]);
				label_cell(Dates::sql2date($inv_row["tran_date"]));
				amount_cell($this_total);
				end_row();
			}
		}
		label_row(null, Num::price_format($invoices_total), " ", "colspan=4 class=right");
		end_table();
		echo "</td><td class='top'>";
		start_table('tablestyle width90');
		Display::heading(_("Payments"));
		$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
		table_header($th);
		$payments_total = 0;
		if (count($inv_numbers)) {
			$sql = "SELECT a.*, d.reference FROM cust_allocations a, debtor_trans d WHERE a.trans_type_from=" . ST_CUSTPAYMENT . " AND a.trans_no_to=d.trans_no AND d.type=" . ST_CUSTPAYMENT . " AND a.trans_no_to IN(" . implode(',', array_values($inv_numbers)) . ")";
			$result = DB::query($sql, "The related payments could not be retreived");
			$k = 0;
			while ($payment_row = DB::fetch($result)) {
				alt_table_row_color($k);
				$this_total = $payment_row["amt"];
				$payments_total += $this_total;
				label_cell(Debtor::trans_view($payment_row["trans_type_from"], $payment_row["trans_no_from"]));
				label_cell($payment_row["reference"]);
				label_cell(Dates::sql2date($payment_row["date_alloc"]));
				amount_cell($this_total);
				end_row();
			}
		}
		label_row(null, Num::price_format($payments_total), " ", "colspan=4 class=right");
		end_table();
		echo "</td><td class='top'>";
		start_table('tablestyle width90');
		Display::heading(_("Credit Notes"));
		$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
		table_header($th);
		$credits_total = 0;
		if (count($inv_numbers)) {
			// FIXME - credit notes retrieved here should be those linked to invoices containing
			// at least one line from this order
			$sql = "SELECT * FROM debtor_trans WHERE type=" . ST_CUSTCREDIT . " AND trans_link IN(" . implode(',', array_values($inv_numbers)) . ")";
			$result = DB::query($sql, "The related credit notes could not be retreived");
			$k = 0;
			while ($credits_row = DB::fetch($result)) {
				alt_table_row_color($k);
				$this_total = $credits_row["ov_freight"] + $credits_row["ov_freight_tax"] + $credits_row["ov_gst"] + $credits_row["ov_amount"];
				$credits_total += $this_total;
				label_cell(Debtor::trans_view($credits_row["type"], $credits_row["trans_no"]));
				label_cell($credits_row["reference"]);
				label_cell(Dates::sql2date($credits_row["tran_date"]));
				amount_cell(-$this_total);
				end_row();
			}
		}
		label_row(null, "<font color=red>" . Num::price_format(-$credits_total) . "</font>", " ", "colspan=4 class=right");
		end_table();
		echo "</td></tr>";
		end_table();
	}
	echo "<div class='center'>";
	if ($_SESSION['View']->so_type == 1) {
		Errors::warning(_("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
	}
	Display::heading(_("Line Details"));
	start_table('tablestyle width95');
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount"), _("Total"), _("Quantity Delivered"));
	table_header($th);
	$k = 0; //row colour counter
	foreach ($_SESSION['View']->line_items as $stock_item) {
		$line_total = Num::round($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent), User::price_dec());
		alt_table_row_color($k);
		label_cell($stock_item->stock_id);
		label_cell($stock_item->description);
		$dec = Item::qty_dec($stock_item->stock_id);
		qty_cell($stock_item->quantity, false, $dec);
		label_cell($stock_item->units);
		amount_cell($stock_item->price);
		amount_cell($stock_item->discount_percent * 100);
		amount_cell($line_total);
		qty_cell($stock_item->qty_done, false, $dec);
		end_row();
	}
	$display_total = 0;
	$qty_remaining = array_sum(array_map(function($line) {
		return ($line->quantity - $line->qty_done);
	}, $_SESSION['View']->line_items));
	$items_total = $_SESSION['View']->get_items_total();
	label_row(_("Shipping"), Num::price_format($_SESSION['View']->freight_cost), "class=right colspan=6", "nowrap class=right", 1);
	$taxes = $view->get_taxes_for_order();
	foreach ($taxes as $tax) {
		$display_total += $tax['Value'];
		label_row(_("Tax: " . $tax['tax_type_name']), Num::price_format($tax['Value']), "class=right colspan=6", "nowrap class=right", 1);
	}
	$display_total = Num::price_format($items_total + $_SESSION['View']->freight_cost);
	label_row(_("Total Order Value"), $display_total, "class=right colspan=6", "nowrap class=right", 1);
	end_table(2);
	if (Input::get('frame')) {
		return;
	}
	$modify = ($_GET['trans_type'] == ST_SALESORDER ? "ModifyOrderNumber" : "ModifyQuotationNumber");
	if (ST_SALESORDER) {
		Display::submenu_option(_("Clone This Order"), "/sales/sales_order_entry.php?CloneOrder={$_GET['trans_no']}' target='_top' ");
	}
	Display::submenu_option(_('Edit Order'), "/sales/sales_order_entry.php?{$modify}={$_GET['trans_no']}' target='_top' ");
	Display::submenu_print(_("&Print Order"), ST_SALESORDER, $_GET['trans_no'], 'prtopt');
	Display::submenu_print(_("Print Proforma Invoice"), ST_PROFORMA, $_GET['trans_no'], 'prtopt');
	if ($qty_remaining > 0) {
		Display::submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber={$_GET['trans_no']}' target='_top' ");
	}
	else {
		Display::submenu_option(_("Invoice Items On This Order"), "/sales/customer_delivery.php?OrderNumber={$_GET['trans_no']}' target='_top' ");
	}
	Display::submenu_option(_("Enter a &New Order"), "/sales/sales_order_entry.php?NewOrder=0' target='_top' ");
	//UploadHandler::insert($_GET['trans_no']);
	Debtor::addEditDialog();
	Page::end(true);

?>
