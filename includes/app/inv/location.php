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
	class Inv_Location
	{
		public static function add($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
			$sql = "INSERT INTO locations (loc_code, location_name, delivery_address, phone, phone2, fax, email, contact)
		VALUES (" . DB::escape($loc_code) . ", " . DB::escape($location_name) . ", " . DB::escape($delivery_address) . ", " . DB::escape($phone) . ", " . DB::escape($phone2) . ", " . DB::escape($fax) . ", " . DB::escape($email) . ", " . DB::escape($contact) . ")";
			DB::query($sql, "a location could not be added");
			/* Also need to add loc_stock records for all existing items */
			$sql = "INSERT INTO loc_stock (loc_code, stock_id, reorder_level)
		SELECT " . DB::escape($loc_code) . ", stock_master.stock_id, 0 FROM stock_master";
			DB::query($sql, "a location could not be added");
		}

		public static function update($loc_code, $location_name, $delivery_address, $phone, $phone2, $fax, $email, $contact) {
			$sql = "UPDATE locations SET location_name=" . DB::escape($location_name) . ",
 	delivery_address=" . DB::escape($delivery_address) . ",
 	phone=" . DB::escape($phone) . ", phone2=" . DB::escape($phone2) . ", fax=" . DB::escape($fax) . ",
 	email=" . DB::escape($email) . ", contact=" . DB::escape($contact) . "
 	WHERE loc_code = " . DB::escape($loc_code);
			DB::query($sql, "a location could not be updated");
		}

		public static function delete($item_location) {
			$sql = "DELETE FROM locations WHERE loc_code=" . DB::escape($item_location);
			DB::query($sql, "a location could not be deleted");
			$sql = "DELETE FROM loc_stock WHERE loc_code =" . DB::escape($item_location);
			DB::query($sql, "a location could not be deleted");
		}

		public static function get($item_location) {
			$sql = "SELECT * FROM locations WHERE loc_code=" . DB::escape($item_location);
			$result = DB::query($sql, "a location could not be retrieved");
			return DB::fetch($result);
		}

		public static function set_reorder($stock_id, $loc_code, $reorder_level) {
			$sql = "UPDATE loc_stock SET reorder_level = $reorder_level
		WHERE stock_id = " . DB::escape($stock_id) . " AND loc_code = " . DB::escape($loc_code);
			DB::query($sql, "an item reorder could not be set");
		}

				public static function set_shelves($stock_id, $loc_code, $primary_location, $secondary_location) {
					$sql = "UPDATE loc_stock SET shelf_primary =  " . DB::escape($primary_location) . " ,  shelf_secondary =  " . DB::escape($secondary_location) . "   WHERE stock_id = " . DB::escape($stock_id) . " AND loc_code = " . DB::escape($loc_code);
					DB::query($sql, "an item reorder could not be set");
				}

		public static function get_details($stock_id) {
			$sql = "SELECT loc_stock.*, locations.location_name
		FROM loc_stock, locations
		WHERE loc_stock.loc_code=locations.loc_code
		AND loc_stock.stock_id = " . DB::escape($stock_id) . " ORDER BY loc_stock.loc_code";
			return DB::query($sql, "an item reorder could not be retreived");
		}

		public static function get_name($loc_code) {
			$sql = "SELECT location_name FROM locations WHERE loc_code=" . DB::escape($loc_code);
			$result = DB::query($sql, "could not retreive the location name for $loc_code");
			if (DB::num_rows($result) == 1) {
				$row = DB::fetch_row($result);
				return $row[0];
			}
			Errors::show_db_error("could not retreive the location name for $loc_code", $sql, true);
		}

		/***
		 * @static
		 *
		 * @param $order
		 *
		 * @return DB_Query_Result|null
		 *
		 * find inventory location for given transaction
		 *
		 */
		public static function get_for_trans($order) {
			$sql = "SELECT locations.* FROM stock_moves," . "locations" . " WHERE type=" . DB::escape($order->trans_type) . " AND trans_no=" . key($order->trans_no) . " AND qty!=0 " . " AND locations.loc_code=stock_moves.loc_code";
			$result = DB::query($sql, 'Retreiving inventory location');
			if (DB::num_rows($result)) {
				return DB::fetch($result);
			}
			return null;
		}

		public static function select($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
			$sql = "SELECT loc_code, location_name, inactive FROM locations";
			return select_box($name, $selected_id, $sql, 'loc_code', 'location_name', array(
																																										 'spec_option' => $all_option === true ?
																																											_("All Locations") :
																																											$all_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change));
		}

		public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Inv_Location::select($name, $selected_id, $all_option, $submit_on_change);
			echo "</td>\n";
		}

		public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			Inv_Location::cells(null, $name, $selected_id, $all_option, $submit_on_change);
			echo "</tr>\n";
		}
	}