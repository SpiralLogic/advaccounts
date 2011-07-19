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
	function add_item_location($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
		$sql = "INSERT INTO locations (loc_code, location_name, delivery_address, phone, phone2, fax, email, contact)
		VALUES (" . db_escape($loc_code) . ", " . db_escape($location_name) . ", " . db_escape($delivery_address) . ", "
				. db_escape($phone) . ", " . db_escape($phone2) . ", " . db_escape($fax) . ", " . db_escape($email) . ", "
				. db_escape($contact) . ")";

		db_query($sql, "a location could not be added");

		/* Also need to add loc_stock records for all existing items */
		$sql = "INSERT INTO loc_stock (loc_code, stock_id, reorder_level)
		SELECT " . db_escape($loc_code) . ", stock_master.stock_id, 0 FROM stock_master";

		db_query($sql, "a location could not be added");
	}

	//------------------------------------------------------------------------------------

	function update_item_location($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
		$sql = "UPDATE locations SET location_name=" . db_escape($location_name) . ", delivery_address=" . db_escape($delivery_address) . ",
    	phone=" . db_escape($phone) . ", phone2=" . db_escape($phone2) . ", fax=" . db_escape($fax) . ",
    	email=" . db_escape($email) . ", contact=" . db_escape($contact) . "
    	WHERE loc_code = " . db_escape($loc_code);

		db_query($sql, "a location could not be updated");
	}

	//------------------------------------------------------------------------------------

	function delete_item_location($item_location) {
		$sql = "DELETE FROM locations WHERE loc_code=" . db_escape($item_location);
		db_query($sql, "a location could not be deleted");

		$sql = "DELETE FROM loc_stock WHERE loc_code =" . db_escape($item_location);
		db_query($sql, "a location could not be deleted");
	}

	//------------------------------------------------------------------------------------

	function get_item_location($item_location) {
		$sql = "SELECT * FROM locations WHERE loc_code=" . db_escape($item_location);

		$result = db_query($sql, "a location could not be retrieved");

		return db_fetch($result);
	}

	//------------------------------------------------------------------------------------

	function get_item_locations($show_inactive) {
		$sql = "SELECT * FROM locations";
		if (!$show_inactive) {
			$sql .= " WHERE !inactive";
		}
		return db_query($sql, "could not query locations");
		;
	}

	//------------------------------------------------------------------------------------

	function set_reorder_level($stock_id, $loc_code, $reorder_level) {
		$sql = "INSERT INTO loc_stock (stock_id,loc_code,reorder_level) VALUES (" . db_escape($stock_id) . "," . db_escape($loc_code) . "," . db_escape($reorder_level) . ") ON DUPLICATE KEY UPDATE reorder_level =" . db_escape($reorder_level);
		db_query($sql, "an item reorder could not be set");
	}

	//------------------------------------------------------------------------------------

	function get_loc_details($stock_id) {
		$sql = "SELECT stock.loc_code, stock.location_name, "
				. db_escape($stock_id) . " as stock_id, reorders.reorder_level
		FROM locations stock LEFT JOIN " . TB_PREF . "loc_stock reorders ON
		reorders.loc_code=stock.loc_code
		AND reorders.stock_id = " . db_escape($stock_id)
				. " ORDER BY reorders.loc_code";
		return db_query($sql, "an item reorder could not be retreived");
	}

?>