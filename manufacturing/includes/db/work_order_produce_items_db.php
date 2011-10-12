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
	function work_order_produce($woid, $ref, $quantity, $date_, $memo_, $close_wo) {

		DBOld::begin_transaction();

		$details = get_work_order($woid);

		if (strlen($details[0]) == 0) {
			echo _("The order number sent is not valid.");
			exit;
		}

		if (work_order_is_closed($woid)) {
			ui_msgs::display_error("UNEXPECTED : Producing Items for a closed Work Order");
			DBOld::cancel_transaction();
			exit;
		}

		$date = Dates::date2sql($date_);

		$sql = "INSERT INTO wo_manufacture (workorder_id, reference, quantity, date_)
		VALUES (" . DBOld::escape($woid) . ", " . DBOld::escape($ref) . ", " . DBOld::escape($quantity)
		 . ", '$date')";

		DBOld::query($sql, "A work order manufacture could not be added");

		$id = DBOld::insert_id();

		// -------------------------------------------------------------------------

		work_order_quick_costs($woid, $details["stock_id"], $quantity, $date_, $id);

		// -------------------------------------------------------------------------
		// insert a +ve stock move for the item being manufactured
		// negative means "unproduce" or unassemble
		add_stock_move(ST_MANURECEIVE, $details["stock_id"], $id,
			$details["loc_code"], $date_, $memo_, $quantity, 0);
		// update wo quantity and close wo if requested
		work_order_update_finished_quantity($woid, $quantity, $close_wo);

		if ($memo_)
			DB_Comments::add(ST_MANURECEIVE, $id, $date_, $memo_);

		Refs::save(ST_MANURECEIVE, $id, $ref);
		DB_AuditTrail::add(ST_MANURECEIVE, $id, $date_, _("Production."));

		DBOld::commit_transaction();
	}

	//--------------------------------------------------------------------------------------------

	function get_work_order_produce($id) {
		$sql = "SELECT wo_manufacture.*,workorders.stock_id, "
		 . "stock_master.description AS StockDescription
		FROM wo_manufacture, workorders, stock_master
		WHERE wo_manufacture.workorder_id=workorders.id
		AND stock_master.stock_id=workorders.stock_id
		AND wo_manufacture.id=" . DBOld::escape($id);
		$result = DBOld::query($sql, "The work order production could not be retrieved");

		return DBOld::fetch($result);
	}

	//--------------------------------------------------------------------------------------

	function get_work_order_productions($woid) {
		$sql = "SELECT * FROM wo_manufacture WHERE workorder_id="
		 . DBOld::escape($woid) . " ORDER BY id";
		return DBOld::query($sql, "The work order issues could not be retrieved");
	}

	//--------------------------------------------------------------------------------------

	function exists_work_order_produce($id) {
		$sql = "SELECT id FROM wo_manufacture WHERE id=" . DBOld::escape($id);
		$result = DBOld::query($sql, "Cannot retreive a wo production");

		return (DBOld::num_rows($result) > 0);
	}

	//--------------------------------------------------------------------------------------------

	function void_work_order_produce($type_no) {
		DBOld::begin_transaction();

		$row = get_work_order_produce($type_no);

		// deduct the quantity of this production from the parent work order
		work_order_update_finished_quantity($row["workorder_id"], -$row["quantity"]);

		work_order_quick_costs(
			$row['workorder_id'], $row['stock_id'], -$row['quantity'], Dates::sql2date($row['date_']), $type_no);

		// clear the production record
		$sql = "UPDATE wo_manufacture SET quantity=0 WHERE id=" . DBOld::escape($type_no);
		DBOld::query($sql, "Cannot void a wo production");

		// void all related stock moves
		void_stock_move(ST_MANURECEIVE, $type_no);

		// void any related gl trans
		void_gl_trans(ST_MANURECEIVE, $type_no, true);

		DBOld::commit_transaction();
	}

?>