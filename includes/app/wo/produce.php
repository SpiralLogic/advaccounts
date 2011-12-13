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
	class WO_Produce
	{
		public static function add($woid, $ref, $quantity, $date_, $memo_, $close_wo) {
			DB::begin();
			$details = WO::get($woid);
			if (strlen($details[0]) == 0) {
				echo _("The order number sent is not valid.");
				exit;
			}
			if (WO::is_closed($woid)) {
				Errors::error("UNEXPECTED : Producing Items for a closed Work Order");
				DB::cancel();
				exit;
			}
			$date = Dates::date2sql($date_);
			$sql = "INSERT INTO wo_manufacture (workorder_id, reference, quantity, date_)
		VALUES (" . DB::escape($woid) . ", " . DB::escape($ref) . ", " . DB::escape($quantity) . ", '$date')";
			DB::query($sql, "A work order manufacture could not be added");
			$id = DB::insert_id();
			// -------------------------------------------------------------------------
			WO_Quick::costs($woid, $details["stock_id"], $quantity, $date_, $id);
			// -------------------------------------------------------------------------
			// insert a +ve stock move for the item being manufactured
			// negative means "unproduce" or unassemble
			Inv_Movement::add(ST_MANURECEIVE, $details["stock_id"], $id, $details["loc_code"], $date_, $memo_, $quantity, 0);
			// update wo quantity and close wo if requested
			WO::update_finished_quantity($woid, $quantity, $close_wo);
			if ($memo_) {
				DB_Comments::add(ST_MANURECEIVE, $id, $date_, $memo_);
			}
			Ref::save(ST_MANURECEIVE,  $ref);
			DB_AuditTrail::add(ST_MANURECEIVE, $id, $date_, _("Production."));
			DB::commit();
		}

		public static function get($id) {
			$sql = "SELECT wo_manufacture.*,workorders.stock_id, " . "stock_master.description AS StockDescription
		FROM wo_manufacture, workorders, stock_master
		WHERE wo_manufacture.workorder_id=workorders.id
		AND stock_master.stock_id=workorders.stock_id
		AND wo_manufacture.id=" . DB::escape($id);
			$result = DB::query($sql, "The work order production could not be retrieved");
			return DB::fetch($result);
		}

		public static function get_all($woid) {
			$sql = "SELECT * FROM wo_manufacture WHERE workorder_id=" . DB::escape($woid) . " ORDER BY id";
			return DB::query($sql, "The work order issues could not be retrieved");
		}

		public static function exists($id) {
			$sql = "SELECT id FROM wo_manufacture WHERE id=" . DB::escape($id);
			$result = DB::query($sql, "Cannot retreive a wo production");
			return (DB::num_rows($result) > 0);
		}

		public static function void($type_no) {
			DB::begin();
			$row = WO_Produce::get($type_no);
			// deduct the quantity of this production from the parent work order
			WO::update_finished_quantity($row["workorder_id"], -$row["quantity"]);
			WO_Quick::costs($row['workorder_id'], $row['stock_id'], -$row['quantity'], Dates::sql2date($row['date_']), $type_no);
			// clear the production record
			$sql = "UPDATE wo_manufacture SET quantity=0 WHERE id=" . DB::escape($type_no);
			DB::query($sql, "Cannot void a wo production");
			// void all related stock moves
			Inv_Movement::void(ST_MANURECEIVE, $type_no);
			// void any related gl trans
			GL_Trans::void(ST_MANURECEIVE, $type_no, true);
			DB::commit();
		}

		public static function display($woid) {
			$result = WO_Produce::get_all($woid);
			if (DB::num_rows($result) == 0) {
				Display::note(_("There are no Productions for this Order."), 1, 1);
			} else {
				start_table('tablestyle');
				$th = array(_("#"), _("Reference"), _("Date"), _("Quantity"));
				table_header($th);
				$k = 0; //row colour counter
				$total_qty = 0;
				while ($myrow = DB::fetch($result)) {
					alt_table_row_color($k);
					$total_qty += $myrow['quantity'];
					label_cell(GL_UI::trans_view(29, $myrow["id"]));
					label_cell($myrow['reference']);
					label_cell(Dates::sql2date($myrow["date_"]));
					qty_cell($myrow['quantity'], false, Item::qty_dec($myrow['reference']));
					end_row();
				}
				//end of while
				label_row(_("Total"), Num::format($total_qty, User::qty_dec()), "colspan=3", "nowrap class=right");
				end_table();
			}
		}
	}

?>