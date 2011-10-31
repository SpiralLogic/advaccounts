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
	$page_security = 'SA_SALESTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	include_once(APP_PATH . "sales/includes/sales_ui.php");
	JS::get_js_open_window(900, 600);
	if ($_GET['trans_type'] == ST_SALESQUOTE) {
		Page::start(_($help_context = "View Sales Quotation"), true);
		ui_msgs::display_heading(sprintf(_("Sales Quotation #%d"), $_GET['trans_no']));
	}
	else {
		Page::start(_($help_context = "View Sales Order"), true);
		ui_msgs::display_heading(sprintf(_("Sales Order #%d"), $_GET['trans_no']));
	}
	if (isset($_SESSION['View'])) {
		unset ($_SESSION['View']);
	}
	$_SESSION['View'] = new Sales_Order($_GET['trans_type'], $_GET['trans_no'], true);
	start_table(Config::get('tables_style2') . " width=95%", 5);
	echo "<tr valign=top class='tableheader2'><td >";
	if ($_GET['trans_type'] != ST_SALESQUOTE) {
		ui_msgs::display_heading2(_("Order Information"));
		echo "</td><td>";
		ui_msgs::display_heading2(_("Deliveries"));
		echo "</td><td>";
		ui_msgs::display_heading2(_("Invoices/Credits"));
	} else {
		ui_msgs::display_heading2(_("Quote Information"));
	}
	echo "</td></tr>";
	echo "<tr valign=top><td>";
	start_table(Config::get('tables_style') . "  width=95%");
	label_row(_("Customer Name"), $_SESSION['View']->customer_name, "class='label'", "colspan=3");
	start_row();
	label_cells(_("Customer Purchase Order #"), $_SESSION['View']->cust_ref, "class='label'");
	label_cells(_("Deliver To Branch"), $_SESSION['View']->deliver_to, "class='label'");
	end_row();
	start_row();
	label_cells(_("Ordered On"), $_SESSION['View']->document_date, "class='label'");
	if ($_GET['trans_type'] == ST_SALESQUOTE) {
		label_cells(_("Valid until"), $_SESSION['View']->due_date, "class='label'");
	}
	else
	{
		label_cells(_("Requested Delivery"), $_SESSION['View']->due_date, "class='label'");
	}
	end_row();
	start_row();
	label_cells(_("Order Currency"), $_SESSION['View']->customer_currency, "class='label'");
	label_cells(_("Deliver From Location"), $_SESSION['View']->location_name, "class='label'");
	end_row();
	label_row(_("Person Ordering"), nl2br($_SESSION['View']->name), "class='label'", "colspan=3");
	label_row(_("Delivery Address"), nl2br($_SESSION['View']->delivery_address), "class='label'", "colspan=3");
	label_row(_("Reference"), $_SESSION['View']->reference, "class='label'", "colspan=3");
	label_row(_("Telephone"), $_SESSION['View']->phone, "class='label'", "colspan=3");
	label_row(_("E-mail"), "<a href='mailto:" . $_SESSION['View']->email . "'>" . $_SESSION['View']->email . "</a>", "class='label'", "colspan=3");
	label_row(_("Comments"), $_SESSION['View']->Comments, "class='label'", "colspan=3");
	end_table();
	if ($_GET['trans_type'] != ST_SALESQUOTE) {
		echo "</td><td valign='top'>";
		start_table(Config::get('tables_style'));
		ui_msgs::display_heading2(_("Delivery Notes"));
		$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
		table_header($th);
		$sql            = "SELECT * FROM debtor_trans WHERE type=" . ST_CUSTDELIVERY . " AND order_=" . DBOld::escape($_GET['trans_no']);
		$result         = DBOld::query($sql, "The related delivery notes could not be retreived");
		$delivery_total = 0;
		$k              = 0;
		$dn_numbers     = array();
		while ($del_row = DBOld::fetch($result)) {
			alt_table_row_color($k);
			$dn_numbers[] = $del_row["trans_link"];
			$this_total   = $del_row["ov_freight"] + $del_row["ov_amount"] + $del_row["ov_freight_tax"] + $del_row["ov_gst"];
			$delivery_total += $this_total;
			label_cell(ui_view::get_customer_trans_view_str($del_row["type"], $del_row["trans_no"]));
			label_cell($del_row["reference"]);
			label_cell(Dates::sql2date($del_row["tran_date"]));
			amount_cell($this_total);
			end_row();
		}
		label_row(null, price_format($delivery_total), " ", "colspan=4 align=right");
		end_table();
		echo "</td><td valign='top'>";
		start_table(Config::get('tables_style'));
		ui_msgs::display_heading2(_("Sales Invoices"));
		$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
		table_header($th);
		$inv_numbers    = array();
		$invoices_total = 0;
		if (count($dn_numbers)) {
			$sql    = "SELECT * FROM debtor_trans WHERE type=" . ST_SALESINVOICE . " AND trans_no IN(" . implode(',', array_values($dn_numbers)) . ")";
			$result = DBOld::query($sql, "The related invoices could not be retreived");
			$k      = 0;
			while ($inv_row = DBOld::fetch($result)) {
				alt_table_row_color($k);
				$this_total = $inv_row["ov_freight"] + $inv_row["ov_freight_tax"] + $inv_row["ov_gst"] + $inv_row["ov_amount"];
				$invoices_total += $this_total;
				$inv_numbers[] = $inv_row["trans_no"];
				label_cell(ui_view::get_customer_trans_view_str($inv_row["type"], $inv_row["trans_no"]));
				label_cell($inv_row["reference"]);
				label_cell(Dates::sql2date($inv_row["tran_date"]));
				amount_cell($this_total);
				end_row();
			}
		}
		label_row(null, price_format($invoices_total), " ", "colspan=4 align=right");
		end_table();
		ui_msgs::display_heading2(_("Credit Notes"));
		start_table(Config::get('tables_style'));
		$th = array(_("#"), _("Ref"), _("Date"), _("Total"));
		table_header($th);
		$credits_total = 0;
		if (count($inv_numbers)) {
			// FIXME -  credit notes retrieved here should be those linked to invoices containing
			// at least one line from this order
			$sql    = "SELECT * FROM debtor_trans WHERE type=" . ST_CUSTCREDIT . " AND trans_link IN(" . implode(',', array_values($inv_numbers)) . ")";
			$result = DBOld::query($sql, "The related credit notes could not be retreived");
			$k      = 0;
			while ($credits_row = DBOld::fetch($result)) {
				alt_table_row_color($k);
				$this_total = $credits_row["ov_freight"] + $credits_row["ov_freight_tax"] + $credits_row["ov_gst"] + $credits_row["ov_amount"];
				$credits_total += $this_total;
				label_cell(ui_view::get_customer_trans_view_str($credits_row["type"], $credits_row["trans_no"]));
				label_cell($credits_row["reference"]);
				label_cell(Dates::sql2date($credits_row["tran_date"]));
				amount_cell(-$this_total);
				end_row();
			}
		}
		label_row(null, "<font color=red>" . price_format(-$credits_total) . "</font>", " ", "colspan=4 align=right");
		end_table();
		echo "</td></tr>";
		end_table();
	}
	echo "<center>";
	if ($_SESSION['View']->so_type == 1) {
		ui_msgs::display_warning(_("This Sales Order is used as a Template."), 0, 0, "class='currentfg'");
	}
	ui_msgs::display_heading2(_("Line Details"));
	start_table("colspan=9 width=95%  " . Config::get('tables_style'));
	$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount"), _("Total"), _("Quantity Delivered"));
	table_header($th);
	$k = 0; //row colour counter
	foreach (
		$_SESSION['View']->line_items as $stock_item
	) {
		$line_total = round2($stock_item->quantity * $stock_item->price * (1 - $stock_item->discount_percent), user_price_dec());
		alt_table_row_color($k);
		label_cell($stock_item->stock_id);
		label_cell($stock_item->description);
		$dec = get_qty_dec($stock_item->stock_id);
		qty_cell($stock_item->quantity, false, $dec);
		label_cell($stock_item->units);
		amount_cell($stock_item->price);
		amount_cell($stock_item->discount_percent * 100);
		amount_cell($line_total);
		qty_cell($stock_item->qty_done, false, $dec);
		end_row();
	}
	$qty_remaining = array_sum(
		array_map(
			function($line)
			{
				return ($line->quantity - $line->qty_done);
			}, $_SESSION['View']->line_items
		)
	);
	$items_total   = $_SESSION['View']->get_items_total();
	$display_total = price_format($items_total + $_SESSION['View']->freight_cost);
	label_row(_("Shipping"), price_format($_SESSION['View']->freight_cost), "align=right colspan=6", "nowrap align=right", 1);
	label_row(_("Total Order Value"), $display_total, "align=right colspan=6", "nowrap align=right", 1);
	end_table(2);
	$modify = ($_GET['trans_type'] == ST_SALESORDER ? "ModifyOrderNumber" : "ModifyQuotationNumber");
	if (ST_SALESORDER) {
		submenu_option(_("Clone This Order"), "/sales/sales_order_entry.php?CloneOrder={$_GET['trans_no']}'  target='_top' ");
	}
	submenu_option(_('Edit This Order'), "/sales/sales_order_entry.php?{$modify}={$_GET['trans_no']}' target='_top' ");
	submenu_print(_("&Print This Order"), ST_SALESORDER, $_GET['trans_no'], 'prtopt');
	submenu_print(_("Print Proforma Invoice"), ST_PROFORMA, $_GET['trans_no'], 'prtopt');
	if ($qty_remaining > 0) {
		submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber={$_GET['trans_no']}'  target='_top' ");
	} else {
		submenu_option(_("Invoice Items On This Order"), "/sales/customer_delivery.php?OrderNumber={$_GET['trans_no']}'  target='_top' ");
	}
	submenu_option(_("Enter a &New Order"), "/sales/sales_order_entry.php?NewOrder=0'  target='_top' ");
	//UploadHandler::insert($_GET['trans_no']);
	end_page(true);

?>
