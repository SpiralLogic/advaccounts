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
	class WO_Issue
	{
		public static function add($woid, $ref, $to_work_order, $items, $location, $workcentre, $date_, $memo_) {
			DB::begin_transaction();
			$details = WO::get($woid);
			if (strlen($details[0]) == 0) {
				echo _("The order number sent is not valid.");
				DB::cancel_transaction();
				exit;
			}
			if (WO::is_closed($woid)) {
				Errors::error("UNEXPECTED : Issuing items for a closed Work Order");
				DB::cancel_transaction();
				exit;
			}
			// insert the actual issue
			$sql = "INSERT INTO wo_issues (workorder_id, reference, issue_date, loc_code, workcentre_id)
		VALUES (" . DB::escape($woid) . ", " . DB::escape($ref) . ", '" . Dates::date2sql($date_) . "', " . DB::escape($location) . ", " . DB::escape($workcentre) . ")";
			DB::query($sql, "The work order issue could not be added");
			$number = DB::insert_id();
			foreach ($items as $item) {
				if ($to_work_order) {
					$item->quantity = -$item->quantity;
				}
				// insert a -ve stock move for each item
				Inv_Movement::add(ST_MANUISSUE, $item->stock_id, $number, $location, $date_, $memo_, -$item->quantity, 0);
				$sql = "INSERT INTO wo_issue_items (issue_id, stock_id, qty_issued)
			VALUES (" . DB::escape($number) . ", " . DB::escape($item->stock_id) . ", " . DB::escape($item->quantity) . ")";
				DB::query($sql, "A work order issue item could not be added");
			}
			if ($memo_) {
				DB_Comments::add(ST_MANUISSUE, $number, $date_, $memo_);
			}
			Ref::save(ST_MANUISSUE, $number, $ref);
			DB_AuditTrail::add(ST_MANUISSUE, $number, $date_);
			DB::commit_transaction();
		}

		public static function get_all($woid) {
			$sql = "SELECT * FROM wo_issues WHERE workorder_id=" . DB::escape($woid) . " ORDER BY issue_no";
			return DB::query($sql, "The work order issues could not be retrieved");
		}

		public static function get_additional($woid) {
			$sql = "SELECT wo_issues.*, wo_issue_items.*
		FROM wo_issues, wo_issue_items
		WHERE wo_issues.issue_no=wo_issue_items.issue_id
		AND wo_issues.workorder_id=" . DB::escape($woid) . " ORDER BY wo_issue_items.id";
			return DB::query($sql, "The work order issues could not be retrieved");
		}

		public static function get($issue_no) {
			$sql = "SELECT DISTINCT wo_issues.*, workorders.stock_id,
		stock_master.description, locations.location_name, " . "workcentres.name AS WorkCentreName
		FROM wo_issues, workorders, stock_master, " . "locations, workcentres
		WHERE issue_no=" . DB::escape($issue_no) . "
		AND workorders.id = wo_issues.workorder_id
		AND locations.loc_code = wo_issues.loc_code
		AND workcentres.id = wo_issues.workcentre_id
		AND stock_master.stock_id = workorders.stock_id";
			$result = DB::query($sql, "A work order issue could not be retrieved");
			return DB::fetch($result);
		}

		public static function get_details($issue_no) {
			$sql = "SELECT wo_issue_items.*," . "stock_master.description, stock_master.units
		FROM wo_issue_items, stock_master
		WHERE issue_id=" . DB::escape($issue_no) . "
		AND stock_master.stock_id=wo_issue_items.stock_id
		ORDER BY wo_issue_items.id";
			return DB::query($sql, "The work order issue items could not be retrieved");
		}

		public static function exists($issue_no) {
			$sql = "SELECT issue_no FROM wo_issues WHERE issue_no=" . DB::escape($issue_no);
			$result = DB::query($sql, "Cannot retreive a wo issue");
			return (DB::num_rows($result) > 0);
		}

		public static 	function display($woid) {
			$result = WO_Issue::get_all($woid);
			if (DB::num_rows($result) == 0) {
				Display::note(_("There are no Issues for this Order."), 0, 1);
			} else {
				start_table('tablestyle');
				$th = array(_("#"), _("Reference"), _("Date"));
				table_header($th);
				$k = 0; //row colour counter
				while ($myrow = DB::fetch($result)) {
					Display::alt_table_row_color($k);
					label_cell(GL_UI::trans_view(28, $myrow["issue_no"]));
					label_cell($myrow['reference']);
					label_cell(Dates::sql2date($myrow["issue_date"]));
					end_row();
				}
				end_table();
			}
		}

		public static function void($type_no) {
			DB::begin_transaction();
			// void the actual issue items and their quantities
			$sql = "UPDATE wo_issue_items Set qty_issued = 0 WHERE issue_id=" . DB::escape($type_no);
			DB::query($sql, "A work order issue item could not be voided");
			// void all related stock moves
			Inv_Movement::void(ST_MANUISSUE, $type_no);
			// void any related gl trans
			GL_Trans::void(ST_MANUISSUE, $type_no, true);
			DB::commit_transaction();
		}

		public static function add_to($order, $new_item, $new_item_qty, $standard_cost) {
			if ($order->find_cart_item($new_item)) {
				Errors::error(_("For Part: '") . $new_item . "' This item is already on this issue. You can change the quantity issued of the existing line if necessary.");
			} else {
				$order->add_to_cart(count($order->line_items), $new_item, $new_item_qty, $standard_cost);
			}
		}

		public static function display_items($title, &$order) {
			Display::heading($title);
			Display::div_start('items_table');
			start_table('tablestyle width90');
			$th = array(
				_("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Unit Cost"), '');
			if (count($order->line_items)) {
				$th[] = '';
			}
			table_header($th);
			//	$total = 0;
			$k = 0; //row colour counter
			$id = find_submit('Edit');
			foreach ($order->line_items as $line_no => $stock_item) {
				//		$total += ($stock_item->standard_cost * $stock_item->quantity);
				if ($id != $line_no) {
					Display::alt_table_row_color($k);
					Item_UI::status_cell($stock_item->stock_id);
					label_cell($stock_item->description);
					qty_cell($stock_item->quantity, false, Item::qty_dec($stock_item->stock_id));
					label_cell($stock_item->units);
					amount_cell($stock_item->standard_cost);
					//			amount_cell($stock_item->standard_cost * $stock_item->quantity);
					edit_button_cell("Edit$line_no", _("Edit"), _('Edit document line'));
					delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
					end_row();
				} else {
					WO_Issue::edit_controls($order, $line_no);
				}
			}
			if ($id == -1) {
				WO_Issue::edit_controls($order);
			}
			//	label_row(_("Total"), Num::format($total,User::price_dec()), "colspan=5", "class=right");
			end_table();
			Display::div_end();
		}

		public static function edit_controls($order, $line_no = -1) {
			$Ajax = Ajax::i();
			start_row();
			$id = find_submit('Edit');
			if ($line_no != -1 && $line_no == $id) {
				$_POST['stock_id'] = $order->line_items[$id]->stock_id;
				$_POST['qty'] = Item::qty_format($order->line_items[$id]->quantity, $order->line_items[$id]->stock_id, $dec);
				$_POST['std_cost'] = Num::price_format($order->line_items[$id]->standard_cost);
				$_POST['units'] = $order->line_items[$id]->units;
				hidden('stock_id', $_POST['stock_id']);
				label_cell($_POST['stock_id']);
				label_cell($order->line_items[$id]->description);
				$Ajax->activate('items_table');
			} else {
				$wo_details = WO::get($_SESSION['issue_items']->order_id);
				Item_UI::component_cells(null, 'stock_id', $wo_details["stock_id"], null, false, true);
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
				button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
				button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
				hidden('LineNo', $line_no);
				JS::set_focus('qty');
			} else {
				submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), true);
			}
			end_row();
		}

		public static function option_controls() {
			echo "<br>";
			start_table();
			ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_MANUISSUE));
			if (!isset($_POST['IssueType'])) {
				$_POST['IssueType'] = 0;
			}
			yesno_list_row(_("Type:"), 'IssueType', $_POST['IssueType'], _("Return Items to Location"),
				_("Issue Items to Work order"));
			Inv_Location::row(_("From Location:"), 'Location');
			workcenter_list_row(_("To Work Centre:"), 'WorkCentre');
			date_row(_("Issue Date:"), 'date_');
			textarea_row(_("Memo"), 'memo_', null, 50, 3);
			end_table(1);
		}
	}

?>