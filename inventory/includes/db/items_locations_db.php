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
		VALUES (" . DB::escape($loc_code) . ", " . DB::escape($location_name) . ", " . DB::escape($delivery_address) . ", "
		 . DB::escape($phone) . ", " . DB::escape($phone2) . ", " . DB::escape($fax) . ", " . DB::escape($email) . ", "
		 . DB::escape($contact) . ")";

		DB::query($sql, "a location could not be added");

		/* Also need to add loc_stock records for all existing items */
		$sql = "INSERT INTO loc_stock (loc_code, stock_id, reorder_level)
		SELECT " . DB::escape($loc_code) . ", stock_master.stock_id, 0 FROM stock_master";

		DB::query($sql, "a location could not be added");
	}

	//------------------------------------------------------------------------------------

	function update_item_location($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
		$sql = "UPDATE locations SET location_name=" . DB::escape($location_name) . ",
    	delivery_address=" . DB::escape($delivery_address) . ",
    	phone=" . DB::escape($phone) . ", phone2=" . DB::escape($phone2) . ", fax=" . DB::escape($fax) . ",
    	email=" . DB::escape($email) . ", contact=" . DB::escape($contact) . "
    	WHERE loc_code = " . DB::escape($loc_code);

		DB::query($sql, "a location could not be updated");
	}

	//------------------------------------------------------------------------------------

	function delete_item_location($item_location) {
		$sql = "DELETE FROM locations WHERE loc_code=" . DB::escape($item_location);
		DB::query($sql, "a location could not be deleted");

		$sql = "DELETE FROM loc_stock WHERE loc_code =" . DB::escape($item_location);
		DB::query($sql, "a location could not be deleted");
	}

	//------------------------------------------------------------------------------------

	function get_item_location($item_location) {
		$sql = "SELECT * FROM locations WHERE loc_code=" . DB::escape($item_location);

		$result = DB::query($sql, "a location could not be retrieved");

		return DB::fetch($result);
	}

	//------------------------------------------------------------------------------------

	function set_reorder_level($stock_id, $loc_code, $reorder_level) {
		$sql = "UPDATE loc_stock SET reorder_level = $reorder_level
		WHERE stock_id = " . DB::escape($stock_id) . " AND loc_code = " . DB::escape($loc_code);

		DB::query($sql, "an item reorder could not be set");
	}

	//------------------------------------------------------------------------------------

	function get_loc_details($stock_id) {
		$sql = "SELECT loc_stock.*, locations.location_name
		FROM loc_stock, locations
		WHERE loc_stock.loc_code=locations.loc_code
		AND loc_stock.stock_id = " . DB::escape($stock_id)
		 . " ORDER BY loc_stock.loc_code";
		return DB::query($sql, "an item reorder could not be retreived");
	}

	//------------------------------------------------------------------------------------
	function get_location_name($loc_code) {
		$sql = "SELECT location_name FROM locations WHERE loc_code="
		 . DB::escape($loc_code);

		$result = DB::query($sql, "could not retreive the location name for $loc_code");

		if (DB::num_rows($result) == 1) {
			$row = DB::fetch_row($result);
			return $row[0];
		}

		Errors::show_db_error("could not retreive the location name for $loc_code", $sql, true);
	}

		//--------------------------------------------------------------------------------------------------
		// find inventory location for given transaction
		//
		function get_location(&$cart)
		{
			$sql = "SELECT locations.* FROM stock_moves,"
			 . "locations" .
			 " WHERE type=" . DB::escape($cart->trans_type) .
			 " AND trans_no=" . key($cart->trans_no) .
			 " AND qty!=0 " .
			 " AND locations.loc_code=stock_moves.loc_code";
			$result = DB::query($sql, 'Retreiving inventory location');
			if (DB::num_rows($result)) {
				return DB::fetch($result);
			}
			return null;
		}

?>