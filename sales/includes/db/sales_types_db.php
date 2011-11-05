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
	function add_sales_type($name, $tax_included, $factor)
	{
		$sql = "INSERT INTO sales_types (sales_type,tax_included,factor) VALUES (" . DB::escape($name) . ","
		 . DB::escape($tax_included) . "," . DB::escape($factor) . ")";
		DBOld::query($sql, "could not add sales type");
	}

	function update_sales_type($id, $name, $tax_included, $factor)
	{
		$sql = "UPDATE sales_types SET sales_type = " . DB::escape($name) . ",
	tax_included =" . DB::escape($tax_included) . ", factor=" . DB::escape($factor) . " WHERE id = " . DB::escape($id);
		DBOld::query($sql, "could not update sales type");
	}

	function get_all_sales_types($all = false)
	{
		$sql = "SELECT * FROM sales_types";
		if (!$all) {
			$sql .= " WHERE !inactive";
		}
		return DBOld::query($sql, "could not get all sales types");
	}

	function get_sales_type($id)
	{
		$sql = "SELECT * FROM sales_types WHERE id=" . DB::escape($id);
		$result = DBOld::query($sql, "could not get sales type");
		return DBOld::fetch($result);
	}

	function get_sales_type_name($id)
	{
		$sql = "SELECT sales_type FROM sales_types WHERE id=" . DB::escape($id);
		$result = DBOld::query($sql, "could not get sales type");
		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function delete_sales_type($id)
	{
		$sql = "DELETE FROM sales_types WHERE id=" . DB::escape($id);
		DBOld::query($sql, "The Sales type record could not be deleted");
		$sql = "DELETE FROM prices WHERE sales_type_id=" . DB::escape($id);
		DBOld::query($sql, "The Sales type prices could not be deleted");
	}

?>