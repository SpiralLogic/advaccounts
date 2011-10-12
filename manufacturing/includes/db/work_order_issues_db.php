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

	function add_work_order_issue($woid, $ref, $to_work_order, $items, $location, $workcentre,
																$date_, $memo_) {

		DBOld::begin_transaction();

		$details = get_work_order($woid);

		if (strlen($details[0]) == 0) {
			echo _("The order number sent is not valid.");
			DBOld::cancel_transaction();
			exit;
		}

		if (work_order_is_closed($woid)) {
			ui_msgs::display_error("UNEXPECTED : Issuing items for a closed Work Order");
			DBOld::cancel_transaction();
			exit;
		}

		// insert the actual issue
		$sql = "INSERT INTO wo_issues (workorder_id, reference, issue_date, loc_code, workcentre_id)
		VALUES (" . DBOld::escape($woid) . ", " . DBOld::escape($ref) . ", '" .
		 Dates::date2sql($date_) . "', " . DBOld::escape($location) . ", " . DBOld::escape($workcentre) . ")";
		DBOld::query($sql, "The work order issue could not be added");

		$number = DBOld::insert_id();

		foreach ($items as $item)
		{

			if ($to_work_order)
				$item->quantity = -$item->quantity;

			// insert a -ve stock move for each item
			add_stock_move(ST_MANUISSUE, $item->stock_id, $number,
				$location, $date_, $memo_, -$item->quantity, 0);

			$sql = "INSERT INTO wo_issue_items (issue_id, stock_id, qty_issued)
			VALUES (" . DBOld::escape($number) . ", " . DBOld::escape($item->stock_id) . ", "
			 . DBOld::escape($item->quantity) . ")";
			DBOld::query($sql, "A work order issue item could not be added");
		}

		if ($memo_)
			DB_Comments::add(ST_MANUISSUE, $number, $date_, $memo_);

		Refs::save(ST_MANUISSUE, $number, $ref);
		DB_AuditTrail::add(ST_MANUISSUE, $number, $date_);

		DBOld::commit_transaction();
	}

	//--------------------------------------------------------------------------------------

	function get_work_order_issues($woid) {
		$sql = "SELECT * FROM wo_issues WHERE workorder_id=" . DBOld::escape($woid)
		 . " ORDER BY issue_no";
		return DBOld::query($sql, "The work order issues could not be retrieved");
	}

	function get_additional_issues($woid) {
		$sql = "SELECT wo_issues.*, wo_issue_items.*
		FROM wo_issues, wo_issue_items
		WHERE wo_issues.issue_no=wo_issue_items.issue_id
		AND wo_issues.workorder_id=" . DBOld::escape($woid)
		 . " ORDER BY wo_issue_items.id";
		return DBOld::query($sql, "The work order issues could not be retrieved");
	}

	//--------------------------------------------------------------------------------------

	function get_work_order_issue($issue_no) {
		$sql = "SELECT DISTINCT wo_issues.*, workorders.stock_id,
		stock_master.description, locations.location_name, "
		 . "workcentres.name AS WorkCentreName
		FROM wo_issues, workorders, stock_master, "
		 . "locations, workcentres
		WHERE issue_no=" . DBOld::escape($issue_no) . "
		AND workorders.id = wo_issues.workorder_id
		AND locations.loc_code = wo_issues.loc_code
		AND workcentres.id = wo_issues.workcentre_id
		AND stock_master.stock_id = workorders.stock_id";
		$result = DBOld::query($sql, "A work order issue could not be retrieved");

		return DBOld::fetch($result);
	}

	//--------------------------------------------------------------------------------------

	function get_work_order_issue_details($issue_no) {
		$sql = "SELECT wo_issue_items.*,"
		 . "stock_master.description, stock_master.units
		FROM wo_issue_items, stock_master
		WHERE issue_id=" . DBOld::escape($issue_no) . "
		AND stock_master.stock_id=wo_issue_items.stock_id
		ORDER BY wo_issue_items.id";
		return DBOld::query($sql, "The work order issue items could not be retrieved");
	}

	//--------------------------------------------------------------------------------------

	function exists_work_order_issue($issue_no) {
		$sql = "SELECT issue_no FROM wo_issues WHERE issue_no=" . DBOld::escape($issue_no);
		$result = DBOld::query($sql, "Cannot retreive a wo issue");

		return (DBOld::num_rows($result) > 0);
	}

	//--------------------------------------------------------------------------------------

	function void_work_order_issue($type_no) {
		DBOld::begin_transaction();

		// void the actual issue items and their quantities
		$sql = "UPDATE wo_issue_items Set qty_issued = 0 WHERE issue_id="
		 . DBOld::escape($type_no);
		DBOld::query($sql, "A work order issue item could not be voided");

		// void all related stock moves
		void_stock_move(ST_MANUISSUE, $type_no);

		// void any related gl trans
		void_gl_trans(ST_MANUISSUE, $type_no, true);

		DBOld::commit_transaction();
	}

	//--------------------------------------------------------------------------------------

?>