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
	function add_movement_type($name) {
		$sql = "INSERT INTO movement_types (name)
		VALUES (" . DB::escape($name) . ")";

		DB::query($sql, "could not add item movement type");
	}

	function update_movement_type($type_id, $name) {
		$sql = "UPDATE movement_types SET name=" . DB::escape($name) . "
			WHERE id=" . DB::escape($type_id);

		DB::query($sql, "could not update item movement type");
	}

	function get_all_movement_type($all = false) {
		$sql = "SELECT * FROM movement_types";
		if (!$all) $sql .= " WHERE !inactive";

		return DB::query($sql, "could not get all item movement type");
	}

	function get_movement_type($type_id) {
		$sql = "SELECT * FROM movement_types WHERE id=" . DB::escape($type_id);

		$result = DB::query($sql, "could not get item movement type");

		return DB::fetch($result);
	}

	function delete_movement_type($type_id) {
		$sql = "DELETE FROM movement_types WHERE id=" . DB::escape($type_id);

		DB::query($sql, "could not delete item movement type");
	}

?>