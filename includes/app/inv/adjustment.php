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
	class Inv_Adjustment
	{
		static public function add($items, $location, $date_, $type, $increase, $reference, $memo_) {
			DB::begin();
			$adj_id = SysTypes::get_next_trans_no(ST_INVADJUST);
			foreach ($items as $line_item) {
				if (!$increase) {
					$line_item->quantity = -$line_item->quantity;
				}
				static::add_item($adj_id, $line_item->stock_id, $location, $date_, $type, $reference,
					$line_item->quantity, $line_item->standard_cost, $memo_);
			}
			DB_Comments::add(ST_INVADJUST, $adj_id, $date_, $memo_);
			Ref::save(ST_INVADJUST, $reference);
			DB_AuditTrail::add(ST_INVADJUST, $adj_id, $date_);
			DB::commit();
			return $adj_id;
		}

		static public function void($type_no) {
			GL_Trans::void(ST_INVADJUST, $type_no);
			Inv_Movement::void(ST_INVADJUST, $type_no);
		}

		static public function get($trans_no) {
			$result = Inv_Movement::get(ST_INVADJUST, $trans_no);
			if (DB::num_rows($result) == 0) {
				return null;
			}
			return $result;
		}

		static public function add_item($adj_id, $stock_id, $location, $date_, $type, $reference, $quantity, $standard_cost, $memo_) {
			$mb_flag = WO::get_mb_flag($stock_id);
			if (Input::post('mb_flag') == STOCK_SERVICE) {
				Errors::db_error("Cannot do inventory adjustment for Service item : $stock_id", "");
			}
			Purch_GRN::update_average_material_cost(null, $stock_id, $standard_cost, $quantity, $date_);
			Inv_Movement::add(ST_INVADJUST, $stock_id, $adj_id, $location,
				$date_, $reference, $quantity, $standard_cost, $type);
			if ($standard_cost > 0) {
				$stock_gl_codes = Item::get_gl_code($stock_id);
				GL_Trans::add_std_cost(ST_INVADJUST, $adj_id, $date_,
					$stock_gl_codes['adjustment_account'], $stock_gl_codes['dimension_id'],
					$stock_gl_codes['dimension2_id'], $memo_, ($standard_cost * -($quantity)));
				GL_Trans::add_std_cost(ST_INVADJUST, $adj_id, $date_,
					$stock_gl_codes['inventory_account'], 0, 0, $memo_, ($standard_cost * $quantity));
			}
		}

		static public function header($order) {
			start_outer_table('tablestyle2 width70'); // outer table
			table_section(1);
			Inv_Location::row(_("Location:"), 'StockLocation', null);
			ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_INVADJUST));
			table_section(2, "33%");
			date_row(_("Date:"), 'AdjDate', '', true);
			table_section(3, "33%");
			Inv_Movement::row(_("Detail:"), 'type', null);
			if (!isset($_POST['Increase'])) {
				$_POST['Increase'] = 1;
			}
			yesno_list_row(_("Type:"), 'Increase', $_POST['Increase'], _("Positive Adjustment"), _("Negative Adjustment"));
			end_outer_table(1); // outer table
		}

		static public function display_items($title, $order) {
			Display::heading($title);
			Display::div_start('items_table');
			start_table('tablestyle width90');
			$th = array(
				_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Unit Cost"), _("Total"), "");
			if (count($order->line_items)) {
				$th[] = '';
			}
			table_header($th);
			$total = 0;
			$k = 0; //row colour counter
			$id = find_submit(MODE_EDIT);
			foreach ($order->line_items as $line_no => $stock_item) {
				$total += ($stock_item->standard_cost * $stock_item->quantity);
				if ($id != $line_no) {
					alt_table_row_color($k);
					Item_UI::status_cell($stock_item->stock_id);
					label_cell($stock_item->description);
					qty_cell($stock_item->quantity, false, Item::qty_dec($stock_item->stock_id));
					label_cell($stock_item->units);
					amount_decimal_cell($stock_item->standard_cost);
					amount_cell($stock_item->standard_cost * $stock_item->quantity);
					edit_button_cell("Edit$line_no", _("Edit"), _('Edit document line'));
					delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
					end_row();
				} else {
					Inv_Adjustment::item_controls($order, $line_no);
				}
			}
			if ($id == -1) {
				Inv_Adjustment::item_controls($order);
			}
			label_row(_("Total"), Num::format($total, User::price_dec()), "class=right colspan=5", "class='right'", 2);
			end_table();
			Display::div_end();
		}

		static public function item_controls($order, $line_no = -1) {

			start_row();
			$dec2 = 0;
			$id = find_submit(MODE_EDIT);
			if ($line_no != -1 && $line_no == $id) {
				$_POST['stock_id'] = $order->line_items[$id]->stock_id;
				$_POST['qty'] = Item::qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
				//$_POST['std_cost'] = Num::price_format($order->line_items[$id]->standard_cost);
				$_POST['std_cost'] = Num::price_decimal($order->line_items[$id]->standard_cost, $dec2);
				$_POST['units'] = $order->line_items[$id]->units;
				hidden('stock_id', $_POST['stock_id']);
				label_cell($_POST['stock_id']);
				label_cell($order->line_items[$id]->description, ' class="nowrap"');
				Ajax::i()->activate('items_table');
			} else {
				Item_UI::costable_cells(null, 'stock_id', null, false, true);
				if (list_updated('stock_id')) {
					Ajax::i()->activate('units');
					Ajax::i()->activate('qty');
					Ajax::i()->activate('std_cost');
				}
				$item_info = Item::get_edit_info((isset($_POST['stock_id']) ? $_POST['stock_id'] : ''));
				$dec = $item_info['decimals'];
				$_POST['qty'] = Num::format(0, $dec);
				//$_POST['std_cost'] = Num::price_format($item_info["standard_cost"]);
				$_POST['std_cost'] = Num::price_decimal($item_info["standard_cost"], $dec2);
				$_POST['units'] = $item_info["units"];
			}
			qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);
			label_cell($_POST['units'], '', 'units');
			//amount_cells(null, 'std_cost', $_POST['std_cost']);
			amount_cells(null, 'std_cost', null, null, null, $dec2);
			label_cell("&nbsp;");
			if ($id != -1) {
				button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
				button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
				hidden('LineNo', $line_no);
				JS::set_focus('qty');
			} else {
				submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), true);
			}
			end_row();
		}

		static public function option_controls() {
			echo "<br>";
			start_table('center');
			textarea_row(_("Memo"), 'memo_', null, 50, 3);
			end_table(1);
		}
	}

?>
