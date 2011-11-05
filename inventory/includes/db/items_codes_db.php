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
	/*
	 item_codes table is used to store both multiply foreign codes and
	 sale kits definition.
 */
	function update_item_code($id, $item_code, $stock_id, $description, $category, $qty, $foreign = 0) {
		$sql = "UPDATE item_codes SET
	 	item_code = " . DB::escape($item_code) . ",
	 	stock_id = " . DB::escape($stock_id) . ",
	 	description = " . DB::escape($description) . ",
	 	category_id = " . DB::escape($category) . ",
	 	quantity = " . DB::escape($qty) . ",
	 	is_foreign = " . DB::escape($foreign) . "
        	WHERE ";

		if ($id == -1) // update with unknown $id i.e. from items table editor
			$sql .= "item_code = " . DB::escape($item_code)
			 . " AND stock_id = " . DB::escape($stock_id);
		else
			$sql .= "id = " . DB::escape($id);

		DBOld::query($sql, "an item code could not be updated");
	}

	function add_item_code($item_code, $stock_id, $description, $category, $qty, $foreign = 0) {
		$sql = "INSERT INTO item_codes
			(item_code, stock_id, description, category_id, quantity, is_foreign) 
			VALUES( " . DB::escape($item_code) . "," . DB::escape($stock_id) . ",
	  		" . DB::escape($description) . "," . DB::escape($category)
		 . "," . DB::escape($qty) . "," . DB::escape($foreign) . ")";

		DBOld::query($sql, "an item code could not be added");
	}

	function delete_item_code($id) {
		$sql = "DELETE FROM item_codes WHERE id=" . DB::escape($id);
		DBOld::query($sql, "an item code could not be deleted");
	}

	function get_item_code($id) {
		$sql = "SELECT * FROM item_codes WHERE id=" . DB::escape($id);

		$result = DBOld::query($sql, "item code could not be retrieved");

		return DBOld::fetch($result);
	}

	function get_all_item_codes($stock_id, $foreign = 1) {
		$sql = "SELECT i.*, c.description as cat_name FROM "
		 . "item_codes as i,"
		 . "stock_category as c
		WHERE stock_id=" . DB::escape($stock_id) . "
		AND i.category_id=c.category_id
		AND i.is_foreign=" . DB::escape($foreign);

		$result = DBOld::query($sql, "all item codes could not be retrieved");

		return $result;
	}

	function delete_item_kit($item_code) {
		$sql = "DELETE FROM item_codes WHERE item_code=" . DB::escape($item_code);
		DBOld::query($sql, "an item kit could not be deleted");
	}

	function get_item_kit($item_code) {
		$sql = "SELECT DISTINCT kit.*, item.units, comp.description as comp_name
		FROM "
		 . "item_codes kit,"
		 . "item_codes comp
		LEFT JOIN "
		 . "stock_master item
		ON 
			item.stock_id=comp.item_code
		WHERE
			kit.stock_id=comp.item_code
			AND kit.item_code=" . DB::escape($item_code);

		$result = DBOld::query($sql, "item kit could not be retrieved");

		return $result;
	}

	function is_item_kit($item_code) {
		$sql = "SELECT * FROM item_codes WHERE item_code=" . DB::escape($item_code);
		return DBOld::query($sql, "Could not do shit for some reason");
	}

	function get_item_code_dflts($stock_id) {
		$sql = "SELECT units, decimals, description, category_id
		FROM stock_master,item_units
		WHERE stock_id=" . DB::escape($stock_id);

		$result = DBOld::query($sql, "item code defaults could not be retrieved");
		return DBOld::fetch($result);
	}

	//
	//	Check if kit contains given item, optionally recursive.
	//
	function check_item_in_kit($old_id, $kit_code, $item_code, $recurse = false) {
		$result = get_item_kit($kit_code);
		if ($result != 0) {
			while ($myrow = DBOld::fetch($result))
			{
				if ($myrow['id'] == $old_id)
					continue;

				if ($myrow['stock_id'] == $item_code) {
					return 1;
				}

				if ($recurse && $myrow['item_code'] != $myrow['stock_id']
				 && check_item_in_kit($old_id, $item_code, $myrow['stock_id'], true)
				) {
					return 1;
				}
			}
		}
		return 0;
	}

	function get_kit_props($kit_code) {
		$sql = "SELECT description, category_id FROM item_codes "
		 . " WHERE item_code=" . DB::escape($kit_code);
		$res = DBOld::query($sql, "kit name query failed");
		return DBOld::fetch($res);
	}

	function update_kit_props($kit_code, $name, $category) {
		$sql = "UPDATE item_codes SET description="
		 . DB::escape($name) . ",category_id=" . DB::escape($category)
		 . " WHERE item_code=" . DB::escape($kit_code);
		DBOld::query($sql, "kit name update failed");
	}

	function get_where_used($item_code) {
		$sql = "SELECT item_code, description FROM "
		 . "item_codes "
		 . " WHERE stock_id=" . DB::escape($item_code) . "
			AND item_code!=" . DB::escape($item_code);
		return DBOld::query($sql, "where used query failed");
	}

?>