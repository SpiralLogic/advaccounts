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
	function add_to_issue($order, $new_item, $new_item_qty, $standard_cost)
	{
		if ($order->find_cart_item($new_item)) {
			Errors::error(_("For Part: '") . $new_item . "' This item is already on this issue.  You can change the quantity issued of the existing line if necessary.");
		} else {
			$order->add_to_cart(count($order->line_items), $new_item, $new_item_qty, $standard_cost);
		}
	}

	//---------------------------------------------------------------------------------
	function display_issue_items($title, &$order)
	{
		Display::heading($title);
		div_start('items_table');
		start_table(Config::get('tables_style') . "  width=90% colspan=7");
		$th = array(
			_("Item Code"), _("Item Description"), _("Quantity"),
			_("Unit"), _("Unit Cost"), ''
		);
		if (count($order->line_items)) {
			$th[] = '';
		}
		table_header($th);
		//	$total = 0;
		$k = 0; //row colour counter
		$id = find_submit('Edit');
		foreach (
			$order->line_items as $line_no => $stock_item
		)
		{
			//		$total += ($stock_item->standard_cost * $stock_item->quantity);
			if ($id != $line_no) {
				alt_table_row_color($k);
				ui_view::stock_status_cell($stock_item->stock_id);
				label_cell($stock_item->description);
				qty_cell($stock_item->quantity, false, Num::qty_dec($stock_item->stock_id));
				label_cell($stock_item->units);
				amount_cell($stock_item->standard_cost);
				//			amount_cell($stock_item->standard_cost * $stock_item->quantity);
				edit_button_cell(
					"Edit$line_no", _("Edit"),
					_('Edit document line')
				);
				delete_button_cell(
					"Delete$line_no", _("Delete"),
					_('Remove line from document')
				);
				end_row();
			} else {
				issue_edit_item_controls($order, $line_no);
			}
		}
		if ($id == -1) {
			issue_edit_item_controls($order);
		}
		//	label_row(_("Total"), Num::format($total,User::price_dec()), "colspan=5", "align=right");
		end_table();
		div_end();
	}

	//---------------------------------------------------------------------------------
	function issue_edit_item_controls($order, $line_no = -1)
	{
		$Ajax = Ajax::instance();
		start_row();
		$id = find_submit('Edit');
		if ($line_no != -1 && $line_no == $id) {
			$_POST['stock_id'] = $order->line_items[$id]->stock_id;
			$_POST['qty'] = Num::qty_format(
				$order->line_items[$id]->quantity,
				$order->line_items[$id]->stock_id, $dec
			);
			$_POST['std_cost'] = Num::price_format($order->line_items[$id]->standard_cost);
			$_POST['units'] = $order->line_items[$id]->units;
			hidden('stock_id', $_POST['stock_id']);
			label_cell($_POST['stock_id']);
			label_cell($order->line_items[$id]->description);
			$Ajax->activate('items_table');
		} else {
			$wo_details = get_work_order($_SESSION['issue_items']->order_id);
			stock_component_items_list_cells(
				null, 'stock_id',
				$wo_details["stock_id"], null, false, true
			);
			if (list_updated('stock_id')) {
				$Ajax->activate('units');
				$Ajax->activate('qty');
				$Ajax->activate('std_cost');
			}
			$item_info = Item::get_edit_info($_POST['stock_id']);
			$dec = $item_info["decimals"];
			$_POST['qty'] = Num::format(0, $dec);
			$_POST['std_cost'] = Num::price_format($item_info["standard_cost"]);
			$_POST['units'] = $item_info["units"];
		}
		qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);
		label_cell($_POST['units'], '', 'units');
		amount_cells(null, 'std_cost', $_POST['std_cost']);
		if ($id != -1) {
			button_cell(
				'UpdateItem', _("Update"),
				_('Confirm changes'), ICON_UPDATE
			);
			button_cell(
				'CancelItemChanges', _("Cancel"),
				_('Cancel changes'), ICON_CANCEL
			);
			hidden('LineNo', $line_no);
			JS::set_focus('qty');
		} else {
			submit_cells(
				'AddItem', _("Add Item"), "colspan=2",
				_('Add new item to document'), true
			);
		}
		end_row();
	}

	//---------------------------------------------------------------------------------
	function issue_options_controls()
	{
		echo "<br>";
		start_table();
		ref_row(_("Reference:"), 'ref', '', Refs::get_next(28));
		if (!isset($_POST['IssueType'])) {
			$_POST['IssueType'] = 0;
		}
		yesno_list_row(
			_("Type:"), 'IssueType', $_POST['IssueType'],
			_("Return Items to Location"), _("Issue Items to Work order")
		);
		locations_list_row(_("From Location:"), 'Location');
		workcenter_list_row(_("To Work Centre:"), 'WorkCentre');
		date_row(_("Issue Date:"), 'date_');
		textarea_row(_("Memo"), 'memo_', null, 50, 3);
		end_table(1);
	}

	//---------------------------------------------------------------------------------
?>