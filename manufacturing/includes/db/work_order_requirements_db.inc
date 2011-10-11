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
	function get_wo_requirements($woid) {
		$sql = "SELECT wo_requirements.*, stock_master.description,
		stock_master.mb_flag,
		locations.location_name,
		workcentres.name AS WorkCentreDescription FROM
		(wo_requirements, locations, "
		 . "workcentres) INNER JOIN stock_master ON
		wo_requirements.stock_id = stock_master.stock_id
		WHERE workorder_id=" . DBOld::escape($woid) . "
		AND locations.loc_code = wo_requirements.loc_code
		AND workcentres.id=workcentre";

		return DBOld::query($sql, "The work order requirements could not be retrieved");
	}

	//--------------------------------------------------------------------------------------

	function create_wo_requirements($woid, $stock_id) {
		// create Work Order Requirements based on the bom
		$result = get_bom($stock_id);

		while ($myrow = DBOld::fetch($result))
		{

			$sql = "INSERT INTO wo_requirements (workorder_id, stock_id, workcentre, units_req, loc_code)
			VALUES (" . DBOld::escape($woid) . ", '" .
			 $myrow["component"] . "', '" .
			 $myrow["workcentre_added"] . "', '" .
			 $myrow["quantity"] . "', '" .
			 $myrow["loc_code"] . "')";

			DBOld::query($sql, "The work order requirements could not be added");
		}
	}

	//--------------------------------------------------------------------------------------

	function delete_wo_requirements($woid) {
		$sql = "DELETE FROM wo_requirements WHERE workorder_id=" . DBOld::escape($woid);
		DBOld::query($sql, "The work order requirements could not be deleted");
	}

	//--------------------------------------------------------------------------------------

	function update_wo_requirement_issued($woid, $stock_id, $quantity) {
		$sql = "UPDATE wo_requirements SET units_issued = units_issued + " . DBOld::escape($quantity) . "
		WHERE workorder_id = " . DBOld::escape($woid) . " AND stock_id = " . DBOld::escape($stock_id);

		DBOld::query($sql, "The work requirements issued quantity couldn't be updated");
	}

	//--------------------------------------------------------------------------------------

	function void_wo_requirements($woid) {
		$sql = "UPDATE wo_requirements SET units_issued = 0 WHERE workorder_id = "
		 . DBOld::escape($woid);

		DBOld::query($sql, "The work requirements issued quantity couldn't be voided");
	}

	//--------------------------------------------------------------------------------------

?>