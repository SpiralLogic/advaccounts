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
$path_to_root = "../..";
include($path_to_root . "/purchasing/includes/po_class.inc");

include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");

$js = "";
if (Config::get('ui.windows.popups'))
	$js .= get_js_open_window(900, 500);
page(_($help_context = "View Purchase Order Delivery"), true, false, "", $js);

include($path_to_root . "/purchasing/includes/purchasing_ui.inc");

if (!isset($_GET['trans_no'])) {
	die ("<BR>" . _("This page must be called with a Purchase Order Delivery number to review."));
}

$purchase_order = new purch_order;
read_grn($_GET["trans_no"], $purchase_order);

display_heading(_("Purchase Order Delivery") . " #" . $_GET['trans_no']);
echo "<BR>";
display_grn_summary($purchase_order);

display_heading2(_("Line Details"));

start_table("colspan=9 ".Config::get('tables.style') ." width=90%");
$th = array(_("Item Code"), _("Item Description"), _("Delivery Date"), _("Quantity"),
			_("Unit"), _("Price"), _("Line Total"), _("Quantity Invoiced"));

table_header($th);

$total = 0;
$k = 0; //row colour counter

foreach ($purchase_order->line_items as $stock_item)
{

	$line_total = $stock_item->qty_received * $stock_item->price;

	alt_table_row_color($k);

	label_cell($stock_item->stock_id);
	label_cell($stock_item->description);
	label_cell($stock_item->req_del_date, "nowrap align=right");
	$dec = get_qty_dec($stock_item->stock_id);
	qty_cell($stock_item->qty_received, false, $dec);
	label_cell($stock_item->units);
	amount_decimal_cell($stock_item->price);
	amount_cell($line_total);
	qty_cell($stock_item->qty_inv, false, $dec);
	end_row();

	$total += $line_total;
}

$display_total = number_format2($total, user_price_dec());
label_row(_("Total Excluding Tax/Shipping"), $display_total,
		  "colspan=6", "nowrap align=right");

end_table(1);

is_voided_display(ST_SUPPRECEIVE, $_GET['trans_no'], _("This delivery has been voided."));

end_page(true);

?>
