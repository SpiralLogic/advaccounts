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
	class Sales_CreditStatus {
	function add($description, $disallow_invoicing)
	{
		$sql
		 = "INSERT INTO credit_status (reason_description, dissallow_invoices)
		VALUES (" . DB::escape($description) . "," . DB::escape($disallow_invoicing) . ")";
		DB::query($sql, "could not add credit status");
	}

	function update($status_id, $description, $disallow_invoicing)
	{
		$sql = "UPDATE credit_status SET reason_description=" . DB::escape($description) . ",
		dissallow_invoices=" . DB::escape($disallow_invoicing) . " WHERE id=" . DB::escape($status_id);
		DB::query($sql, "could not update credit status");
	}

	function get_all($all = false)
	{
		$sql = "SELECT * FROM credit_status";
		if (!$all) {
			$sql .= " WHERE !inactive";
		}
		return DB::query($sql, "could not get all credit status");
	}

	function get($status_id)
	{
		$sql = "SELECT * FROM credit_status WHERE id=" . DB::escape($status_id);
		$result = DB::query($sql, "could not get credit status");
		return DB::fetch($result);
	}

	function delete($status_id)
	{
		$sql = "DELETE FROM credit_status WHERE id=" . DB::escape($status_id);
		DB::query($sql, "could not delete credit status");
	}

	}