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
	function add_credit_status($description, $disallow_invoicing) {
		$sql = "INSERT INTO credit_status (reason_description, dissallow_invoices)
		VALUES (" . DBOld::escape($description) . "," . DBOld::escape($disallow_invoicing) . ")";

		DBOld::query($sql, "could not add credit status");
	}

	function update_credit_status($status_id, $description, $disallow_invoicing) {
		$sql = "UPDATE credit_status SET reason_description=" . DBOld::escape($description) . ",
		dissallow_invoices=" . DBOld::escape($disallow_invoicing) . " WHERE id=" . DBOld::escape($status_id);

		DBOld::query($sql, "could not update credit status");
	}

	function get_all_credit_status($all = false) {
		$sql = "SELECT * FROM credit_status";
		if (!$all) $sql .= " WHERE !inactive";

		return DBOld::query($sql, "could not get all credit status");
	}

	function get_credit_status($status_id) {
		$sql = "SELECT * FROM credit_status WHERE id=" . DBOld::escape($status_id);

		$result = DBOld::query($sql, "could not get credit status");

		return DBOld::fetch($result);
	}

	function delete_credit_status($status_id) {
		$sql = "DELETE FROM credit_status WHERE id=" . DBOld::escape($status_id);

		DBOld::query($sql, "could not delete credit status");
	}

?>