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
	//--------------------------------------------------------------------------------------
	function add_material_cost($stock_id, $qty, $date_)
	{
		$m_cost = 0;
		$result = Manufacturing::get_bom($stock_id);
		while ($bom_item = DBOld::fetch($result))
		{
			$standard_cost = get_standard_cost($bom_item['component']);
			$m_cost += ($bom_item['quantity'] * $standard_cost);
		}
		$dec = user_price_dec();
		Num::price_decimal($m_cost, $dec);
		$sql = "SELECT material_cost FROM stock_master WHERE stock_id = "
		 . DB::escape($stock_id);
		$result = DBOld::query($sql);
		$myrow = DBOld::fetch($result);
		$material_cost = $myrow['material_cost'];
		$qoh = get_qoh_on_date($stock_id, null, $date_);
		if ($qoh < 0) {
			$qoh = 0;
		}
		if ($qoh + $qty != 0) {
			$material_cost = ($qoh * $material_cost + $qty * $m_cost) / ($qoh + $qty);
		}
		$material_cost = Num::round($material_cost, $dec);
		$sql
		 = "UPDATE stock_master SET material_cost=$material_cost
		WHERE stock_id=" . DB::escape($stock_id);
		DBOld::query($sql, "The cost details for the inventory item could not be updated");
	}

	function add_overhead_cost($stock_id, $qty, $date_, $costs)
	{
		$dec = user_price_dec();
		Num::price_decimal($costs, $dec);
		if ($qty != 0) {
			$costs /= $qty;
		}
		$sql = "SELECT overhead_cost FROM stock_master WHERE stock_id = "
		 . DB::escape($stock_id);
		$result = DBOld::query($sql);
		$myrow = DBOld::fetch($result);
		$overhead_cost = $myrow['overhead_cost'];
		$qoh = get_qoh_on_date($stock_id, null, $date_);
		if ($qoh < 0) {
			$qoh = 0;
		}
		if ($qoh + $qty != 0) {
			$overhead_cost = ($qoh * $overhead_cost + $qty * $costs) / ($qoh + $qty);
		}
		$overhead_cost = Num::round($overhead_cost, $dec);
		$sql = "UPDATE stock_master SET overhead_cost=" . DB::escape($overhead_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
		DBOld::query($sql, "The cost details for the inventory item could not be updated");
	}

	function add_labour_cost($stock_id, $qty, $date_, $costs)
	{
		$dec = user_price_dec();
		Num::price_decimal($costs, $dec);
		if ($qty != 0) {
			$costs /= $qty;
		}
		$sql = "SELECT labour_cost FROM stock_master WHERE stock_id = "
		 . DB::escape($stock_id);
		$result = DBOld::query($sql);
		$myrow = DBOld::fetch($result);
		$labour_cost = $myrow['labour_cost'];
		$qoh = get_qoh_on_date($stock_id, null, $date_);
		if ($qoh < 0) {
			$qoh = 0;
		}
		if ($qoh + $qty != 0) {
			$labour_cost = ($qoh * $labour_cost + $qty * $costs) / ($qoh + $qty);
		}
		$labour_cost = Num::round($labour_cost, $dec);
		$sql = "UPDATE stock_master SET labour_cost=" . DB::escape($labour_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
		DBOld::query($sql, "The cost details for the inventory item could not be updated");
	}

	function add_issue_cost($stock_id, $qty, $date_, $costs)
	{
		if ($qty != 0) {
			$costs /= $qty;
		}
		$sql = "SELECT material_cost FROM stock_master WHERE stock_id = "
		 . DB::escape($stock_id);
		$result = DBOld::query($sql);
		$myrow = DBOld::fetch($result);
		$material_cost = $myrow['material_cost'];
		$dec = user_price_dec();
		Num::price_decimal($material_cost, $dec);
		$qoh = get_qoh_on_date($stock_id, null, $date_);
		if ($qoh < 0) {
			$qoh = 0;
		}
		if ($qoh + $qty != 0) {
			$material_cost = ($qty * $costs) / ($qoh + $qty);
		}
		$material_cost = Num::round($material_cost, $dec);
		$sql = "UPDATE stock_master SET material_cost=material_cost+"
		 . DB::escape($material_cost)
		 . " WHERE stock_id=" . DB::escape($stock_id);
		DBOld::query($sql, "The cost details for the inventory item could not be updated");
	}

	function add_work_order($wo_ref, $loc_code, $units_reqd, $stock_id,
													$type, $date_, $required_by, $memo_, $costs, $cr_acc, $labour, $cr_lab_acc)
	{
		if (!($type == WO_ADVANCED)) {
			return add_work_order_quick($wo_ref, $loc_code, $units_reqd, $stock_id, $type, $date_, $memo_, $costs, $cr_acc, $labour, $cr_lab_acc);
		}
		DBOld::begin_transaction();
		add_material_cost($stock_id, $units_reqd, $date_);
		$date = Dates::date2sql($date_);
		$required = Dates::date2sql($required_by);
		$sql
		 = "INSERT INTO workorders (wo_ref, loc_code, units_reqd, stock_id,
		type, date_, required_by)
    	VALUES (" . DB::escape($wo_ref) . ", " . DB::escape($loc_code) . ", "
		 . DB::escape($units_reqd) . ", " . DB::escape($stock_id) . ",
		" . DB::escape($type) . ", '$date', " . DB::escape($required) . ")";
		DBOld::query($sql, "could not add work order");
		$woid = DBOld::insert_id();
		DB_Comments::add(ST_WORKORDER, $woid, $required_by, $memo_);
		Refs::save(ST_WORKORDER, $woid, $wo_ref);
		DB_AuditTrail::add(ST_WORKORDER, $woid, $date_);
		DBOld::commit_transaction();
		return $woid;
	}

	//--------------------------------------------------------------------------------------
	function update_work_order($woid, $loc_code, $units_reqd, $stock_id,
														 $date_, $required_by, $memo_)
	{
		DBOld::begin_transaction();
		add_material_cost($_POST['old_stk_id'], -$_POST['old_qty'], $date_);
		add_material_cost($stock_id, $units_reqd, $date_);
		$date = Dates::date2sql($date_);
		$required = Dates::date2sql($required_by);
		$sql = "UPDATE workorders SET loc_code=" . DB::escape($loc_code) . ",
		units_reqd=" . DB::escape($units_reqd) . ", stock_id=" . DB::escape($stock_id) . ",
		required_by=" . DB::escape($required) . ",
		date_='$date'
		WHERE id = " . DB::escape($woid);
		DBOld::query($sql, "could not update work order");
		DB_Comments::update(ST_WORKORDER, $woid, null, $memo_);
		DB_AuditTrail::add(ST_WORKORDER, $woid, $date_, _("Updated."));
		DBOld::commit_transaction();
	}

	function delete_work_order($woid)
	{
		DBOld::begin_transaction();
		add_material_cost($_POST['stock_id'], -$_POST['quantity'], $_POST['date_']);
		// delete the work order requirements
		delete_wo_requirements($woid);
		// delete the actual work order
		$sql = "DELETE FROM workorders WHERE id=" . DB::escape($woid);
		DBOld::query($sql, "The work order could not be deleted");
		DB_Comments::delete(ST_WORKORDER, $woid);
		DB_AuditTrail::add(ST_WORKORDER, $woid, $_POST['date_'], _("Canceled."));
		DBOld::commit_transaction();
	}

	//--------------------------------------------------------------------------------------
	function get_work_order($woid, $allow_null = false)
	{
		$sql
		 = "SELECT workorders.*, stock_master.description As StockItemName,
		locations.location_name, locations.delivery_address
		FROM workorders, stock_master, locations
		WHERE stock_master.stock_id=workorders.stock_id
		AND	locations.loc_code=workorders.loc_code
		AND workorders.id=" . DB::escape($woid) . "
		GROUP BY workorders.id";
		$result = DBOld::query($sql, "The work order issues could not be retrieved");
		if (!$allow_null && DBOld::num_rows($result) == 0) {
			Errors::show_db_error("Could not find work order $woid", $sql);
		}
		return DBOld::fetch($result);
	}

	//--------------------------------------------------------------------------------------
	function work_order_has_productions($woid)
	{
		$sql = "SELECT COUNT(*) FROM wo_manufacture WHERE workorder_id=" . DB::escape($woid);
		$result = DBOld::query($sql, "query work order for productions");
		$myrow = DBOld::fetch_row($result);
		return ($myrow[0] > 0);
	}

	//--------------------------------------------------------------------------------------
	function work_order_has_issues($woid)
	{
		$sql = "SELECT COUNT(*) FROM wo_issues WHERE workorder_id=" . DB::escape($woid);
		$result = DBOld::query($sql, "query work order for issues");
		$myrow = DBOld::fetch_row($result);
		return ($myrow[0] > 0);
	}

	//--------------------------------------------------------------------------------------
	function work_order_has_payments($woid)
	{
		$result = get_gl_wo_cost_trans($woid);
		return (DBOld::num_rows($result) != 0);
	}

	//--------------------------------------------------------------------------------------
	function release_work_order($woid, $releaseDate, $memo_)
	{
		DBOld::begin_transaction();
		$myrow = get_work_order($woid);
		$stock_id = $myrow["stock_id"];
		$date = Dates::date2sql($releaseDate);
		$sql
		 = "UPDATE workorders SET released_date='$date',
		released=1 WHERE id = " . DB::escape($woid);
		DBOld::query($sql, "could not release work order");
		// create Work Order Requirements based on the bom
		create_wo_requirements($woid, $stock_id);
		DB_Comments::add(ST_WORKORDER, $woid, $releaseDate, $memo_);
		DB_AuditTrail::add(ST_WORKORDER, $woid, $releaseDate, _("Released."));
		DBOld::commit_transaction();
	}

	//--------------------------------------------------------------------------------------
	function close_work_order($woid)
	{
		$sql = "UPDATE workorders SET closed=1 WHERE id = " . DB::escape($woid);
		DBOld::query($sql, "could not close work order");
	}

	//--------------------------------------------------------------------------------------
	function work_order_is_closed($woid)
	{
		$sql = "SELECT closed FROM workorders WHERE id = " . DB::escape($woid);
		$result = DBOld::query($sql, "could not query work order");
		$row = DBOld::fetch_row($result);
		return ($row[0] > 0);
	}

	//--------------------------------------------------------------------------------------
	function work_order_update_finished_quantity($woid, $quantity, $force_close = 0)
	{
		$sql = "UPDATE workorders SET units_issued = units_issued + " . DB::escape($quantity) . ",
		closed = ((units_issued >= units_reqd) OR " . DB::escape($force_close) . ")
		WHERE id = " . DB::escape($woid);
		DBOld::query($sql, "The work order issued quantity couldn't be updated");
	}

	//--------------------------------------------------------------------------------------
	function void_work_order($woid)
	{
		DBOld::begin_transaction();
		$work_order = get_work_order($woid);
		if (!($work_order["type"] == WO_ADVANCED)) {
			$date = Dates::sql2date($work_order['date_']);
			$qty = $work_order['units_reqd'];
			add_material_cost($work_order['stock_id'], -$qty, $date); // remove avg. cost for qty
			$cost = get_gl_wo_cost($woid, WO_LABOUR); // get the labour cost and reduce avg cost
			if ($cost != 0) {
				add_labour_cost($work_order['stock_id'], -$qty, $date, $cost);
			}
			$cost = get_gl_wo_cost($woid, WO_OVERHEAD); // get the overhead cost and reduce avg cost
			if ($cost != 0) {
				add_overhead_cost($work_order['stock_id'], -$qty, $date, $cost);
			}
			$sql = "UPDATE workorders SET closed=1,units_reqd=0,units_issued=0 WHERE id = "
			 . DB::escape($woid);
			DBOld::query($sql, "The work order couldn't be voided");
			// void all related stock moves
			void_stock_move(ST_WORKORDER, $woid);
			// void any related gl trans
			void_gl_trans(ST_WORKORDER, $woid, true);
			// clear the requirements units received
			void_wo_requirements($woid);
		} else {
			// void everything inside the work order : issues, productions, payments
			$date = Dates::sql2date($work_order['date_']);
			add_material_cost($work_order['stock_id'], -$work_order['units_reqd'], $date); // remove avg. cost for qty
			$result = get_work_order_productions($woid); // check the produced quantity
			$qty = 0;
			while ($row = DBOld::fetch($result))
			{
				$qty += $row['quantity'];
				// clear the production record
				$sql = "UPDATE wo_manufacture SET quantity=0 WHERE id=" . $$row['id'];
				DBOld::query($sql, "Cannot void a wo production");
				void_stock_move(ST_MANURECEIVE, $row['id']); // and void the stock moves;
			}
			$result = get_additional_issues($woid); // check the issued quantities
			$cost = 0;
			$issue_no = 0;
			while ($row = DBOld::fetch($result))
			{
				$std_cost = get_standard_cost($row['stock_id']);
				$icost = $std_cost * $row['qty_issued'];
				$cost += $icost;
				if ($issue_no == 0) {
					$issue_no = $row['issue_no'];
				}
				// void the actual issue items and their quantities
				$sql = "UPDATE wo_issue_items SET qty_issued = 0 WHERE issue_id="
				 . DB::escape($row['id']);
				DBOld::query($sql, "A work order issue item could not be voided");
			}
			if ($issue_no != 0) {
				void_stock_move(ST_MANUISSUE, $issue_no);
			} // and void the stock moves
			if ($cost != 0) {
				add_issue_cost($work_order['stock_id'], -$qty, $date, $cost);
			}
			$cost = get_gl_wo_cost($woid, WO_LABOUR); // get the labour cost and reduce avg cost
			if ($cost != 0) {
				add_labour_cost($work_order['stock_id'], -$qty, $date, $cost);
			}
			$cost = get_gl_wo_cost($woid, WO_OVERHEAD); // get the overhead cost and reduce avg cost
			if ($cost != 0) {
				add_overhead_cost($work_order['stock_id'], -$qty, $date, $cost);
			}
			$sql = "UPDATE workorders SET closed=1,units_reqd=0,units_issued=0 WHERE id = "
			 . DB::escape($woid);
			DBOld::query($sql, "The work order couldn't be voided");
			// void all related stock moves
			void_stock_move(ST_WORKORDER, $woid);
			// void any related gl trans
			void_gl_trans(ST_WORKORDER, $woid, true);
			// clear the requirements units received
			void_wo_requirements($woid);
		}
		DBOld::commit_transaction();
	}

	//--------------------------------------------------------------------------------------
	function get_gl_wo_cost($woid, $cost_type)
	{
		$cost = 0;
		$result = get_gl_wo_cost_trans($woid, $cost_type);
		while ($row = DBOld::fetch($result))
		{
			$cost += -$row['amount'];
		}
		return $cost;
	}

?>