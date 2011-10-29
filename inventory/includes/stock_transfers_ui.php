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

	//--------------------------------------------------------------------------------

	function add_to_order(&$order, $new_item, $new_item_qty, $standard_cost) {
		if ($order->find_cart_item($new_item))
			ui_msgs::display_error(_("For Part :") . $new_item . " " . "This item is already on this order.  You can change the quantity ordered of the existing line if necessary.");
		else
			$order->add_to_cart(count($order->line_items), $new_item, $new_item_qty, $standard_cost);
	}

	//--------------------------------------------------------------------------------

	function display_order_header(&$order) {

		start_outer_table("width=70%  " . Config::get('tables_style'));

		table_section(1);

		locations_list_row(_("From Location:"), 'FromStockLocation', null);
		locations_list_row(_("To Location:"), 'ToStockLocation', null);

		table_section(2, "33%");

		ref_row(_("Reference:"), 'ref', '', Refs::get_next(ST_LOCTRANSFER));

		date_row(_("Date:"), 'AdjDate', '', true);

		table_section(3, "33%");

		movement_types_list_row(_("Transfer Type:"), 'type', null);

		end_outer_table(1); // outer table
	}

	//---------------------------------------------------------------------------------

	function display_transfer_items($title, &$order) {

		ui_msgs::display_heading($title);
		div_start('items_table');
		start_table(Config::get('tables_style') . "  width=90%");
		$th = array(_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), '');
		if (count($order->line_items)) $th[] = '';
		table_header($th);
		$subtotal = 0;
		$k = 0; //row colour counter

		$id = find_submit('Edit');
		foreach ($order->line_items as $line_no => $stock_item)
		{

			if ($id != $line_no) {
				alt_table_row_color($k);

				ui_view::view_stock_status_cell($stock_item->stock_id);
				label_cell($stock_item->description);
				qty_cell($stock_item->quantity, false, get_qty_dec($stock_item->stock_id));
				label_cell($stock_item->units);

				edit_button_cell("Edit$line_no", _("Edit"),
					_('Edit document line'));
				delete_button_cell("Delete$line_no", _("Delete"),
					_('Remove line from document'));
				end_row();
			}
			else
			{
				transfer_edit_item_controls($order, $line_no);
			}
		}

		if ($id == -1)
			transfer_edit_item_controls($order);

		end_table();
		div_end();
	}

	//---------------------------------------------------------------------------------

	function transfer_edit_item_controls(&$order, $line_no = -1) {
		$Ajax = Ajax::instance();
		start_row();

		$id = find_submit('Edit');
		if ($line_no != -1 && $line_no == $id) {
			$_POST['stock_id'] = $order->line_items[$id]->stock_id;
			$_POST['qty'] = qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
			$_POST['units'] = $order->line_items[$id]->units;

			hidden('stock_id', $_POST['stock_id']);
			label_cell($_POST['stock_id']);
			label_cell($order->line_items[$id]->description);
			$Ajax->activate('items_table');
		}
		else
		{
			stock_costable_items_list_cells(null, 'stock_id', null, false, true);
			if (list_updated('stock_id')) {
				$Ajax->activate('units');
				$Ajax->activate('qty');
			}

			$item_info = get_item_edit_info(Input::post('stock_id'));

			$dec = $item_info['decimals'];
			$_POST['qty'] = number_format2(0, $dec);
			$_POST['units'] = $item_info["units"];
		}

		small_qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);
		label_cell($_POST['units'], '', 'units');

		if ($id != -1) {
			button_cell('UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE);
			button_cell('CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL);
			hidden('LineNo', $line_no);
			ui_view::set_focus('qty');
		}
		else
		{
			submit_cells('AddItem', _("Add Item"), "colspan=2",
				_('Add new item to document'), true);
		}

		end_row();
	}

	//---------------------------------------------------------------------------------

	function transfer_options_controls() {
		echo "<br>";
		start_table();

		textarea_row(_("Memo"), 'memo_', null, 50, 3);

		end_table(1);
	}

	//---------------------------------------------------------------------------------

?>