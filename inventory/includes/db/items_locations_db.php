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
		VALUES (" . DBOld::escape($loc_code) . ", " . DBOld::escape($location_name) . ", " . DBOld::escape($delivery_address) . ", "
		 . DBOld::escape($phone) . ", " . DBOld::escape($phone2) . ", " . DBOld::escape($fax) . ", " . DBOld::escape($email) . ", "
		 . DBOld::escape($contact) . ")";

		DBOld::query($sql, "a location could not be added");

		/* Also need to add loc_stock records for all existing items */
		$sql = "INSERT INTO loc_stock (loc_code, stock_id, reorder_level)
		SELECT " . DBOld::escape($loc_code) . ", stock_master.stock_id, 0 FROM stock_master";

		DBOld::query($sql, "a location could not be added");
	}

	//------------------------------------------------------------------------------------

	function update_item_location($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
		$sql = "UPDATE locations SET location_name=" . DBOld::escape($location_name) . ",
    	delivery_address=" . DBOld::escape($delivery_address) . ",
    	phone=" . DBOld::escape($phone) . ", phone2=" . DBOld::escape($phone2) . ", fax=" . DBOld::escape($fax) . ",
    	email=" . DBOld::escape($email) . ", contact=" . DBOld::escape($contact) . "
    	WHERE loc_code = " . DBOld::escape($loc_code);

		DBOld::query($sql, "a location could not be updated");
	}

	//------------------------------------------------------------------------------------

	function delete_item_location($item_location) {
		$sql = "DELETE FROM locations WHERE loc_code=" . DBOld::escape($item_location);
		DBOld::query($sql, "a location could not be deleted");

		$sql = "DELETE FROM loc_stock WHERE loc_code =" . DBOld::escape($item_location);
		DBOld::query($sql, "a location could not be deleted");
	}

	//------------------------------------------------------------------------------------

	function get_item_location($item_location) {
		$sql = "SELECT * FROM locations WHERE loc_code=" . DBOld::escape($item_location);

		$result = DBOld::query($sql, "a location could not be retrieved");

		return DBOld::fetch($result);
	}

	//------------------------------------------------------------------------------------

	function set_reorder_level($stock_id, $loc_code, $reorder_level) {
		$sql = "UPDATE loc_stock SET reorder_level = $reorder_level
		WHERE stock_id = " . DBOld::escape($stock_id) . " AND loc_code = " . DBOld::escape($loc_code);

		DBOld::query($sql, "an item reorder could not be set");
	}

	//------------------------------------------------------------------------------------

	function get_loc_details($stock_id) {
		$sql = "SELECT loc_stock.*, locations.location_name
		FROM loc_stock, locations
		WHERE loc_stock.loc_code=locations.loc_code
		AND loc_stock.stock_id = " . DBOld::escape($stock_id)
		 . " ORDER BY loc_stock.loc_code";
		return DBOld::query($sql, "an item reorder could not be retreived");
	}

	//------------------------------------------------------------------------------------
	function get_location_name($loc_code) {
		$sql = "SELECT location_name FROM locations WHERE loc_code="
		 . DBOld::escape($loc_code);

		$result = DBOld::query($sql, "could not retreive the location name for $loc_code");

		if (DBOld::num_rows($result) == 1) {
			$row = DBOld::fetch_row($result);
			return $row[0];
		}

		Errors::show_db_error("could not retreive the location name for $loc_code", $sql, true);
	}

?>