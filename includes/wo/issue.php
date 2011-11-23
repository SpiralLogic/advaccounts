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
	//--------------------------------------------------------------------------------------
	class WO_Issue
	{
		public static function add($woid, $ref, $to_work_order, $items, $location, $workcentre, $date_, $memo_)
		{
			DB::begin_transaction();
			$details = WO_WorkOrder::get($woid);
			if (strlen($details[0]) == 0) {
				echo _("The order number sent is not valid.");
				DB::cancel_transaction();
				exit;
			}
			if (WO_WorkOrder::is_closed($woid)) {
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
			Refs::save(ST_MANUISSUE, $number, $ref);
			DB_AuditTrail::add(ST_MANUISSUE, $number, $date_);
			DB::commit_transaction();
		}

		//--------------------------------------------------------------------------------------
		public static function get_all($woid)
		{
			$sql = "SELECT * FROM wo_issues WHERE workorder_id=" . DB::escape($woid) . " ORDER BY issue_no";
			return DB::query($sql, "The work order issues could not be retrieved");
		}

		public static function get_additional($woid)
		{
			$sql = "SELECT wo_issues.*, wo_issue_items.*
		FROM wo_issues, wo_issue_items
		WHERE wo_issues.issue_no=wo_issue_items.issue_id
		AND wo_issues.workorder_id=" . DB::escape($woid) . " ORDER BY wo_issue_items.id";
			return DB::query($sql, "The work order issues could not be retrieved");
		}

		//--------------------------------------------------------------------------------------
		public static function get($issue_no)
		{
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

		//--------------------------------------------------------------------------------------
		public static function get_details($issue_no)
		{
			$sql = "SELECT wo_issue_items.*," . "stock_master.description, stock_master.units
		FROM wo_issue_items, stock_master
		WHERE issue_id=" . DB::escape($issue_no) . "
		AND stock_master.stock_id=wo_issue_items.stock_id
		ORDER BY wo_issue_items.id";
			return DB::query($sql, "The work order issue items could not be retrieved");
		}

		//--------------------------------------------------------------------------------------
		public static function exists($issue_no)
		{
			$sql = "SELECT issue_no FROM wo_issues WHERE issue_no=" . DB::escape($issue_no);
			$result = DB::query($sql, "Cannot retreive a wo issue");
			return (DB::num_rows($result) > 0);
		}

		//--------------------------------------------------------------------------------------
		public static function void($type_no)
		{
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
		//--------------------------------------------------------------------------------------
	}

?>