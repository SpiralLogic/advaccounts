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
	class Tax_Groups {
		public static function clear_shipping_tax_group() {
			$sql = "UPDATE tax_groups SET tax_shipping=0 WHERE 1";
			DB::query($sql, "could not update tax_shipping fields");
		}

		public static function add($name, $tax_shipping, $taxes, $rates) {
			DB::begin_transaction();
			if ($tax_shipping) // only one tax group for shipping
			{
				static::clear_shipping_tax_group();
			}
			$sql = "INSERT INTO tax_groups (name, tax_shipping) VALUES (" . DB::escape($name) . ", " . DB::escape($tax_shipping) . ")";
			DB::query($sql, "could not add tax group");
			$id = DB::insert_id();
			static::add_items($id, $taxes, $rates);
			DB::commit_transaction();
		}

		public static function update($id, $name, $tax_shipping, $taxes, $rates) {
			DB::begin_transaction();
			if ($tax_shipping) // only one tax group for shipping
			{
				static::clear_shipping_tax_group();
			}
			$sql = "UPDATE tax_groups SET name=" . DB::escape($name) . ",tax_shipping=" . DB::escape($tax_shipping) . " WHERE id=" . DB::escape($id);
			DB::query($sql, "could not update tax group");
			static::delete_items($id);
			static::add_items($id, $taxes, $rates);
			DB::commit_transaction();
		}

		public static function get_all($all = false) {
			$sql = "SELECT * FROM tax_groups";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			return DB::query($sql, "could not get all tax group");
		}

		public static function get($type_id) {
			$sql = "SELECT * FROM tax_groups WHERE id=" . DB::escape($type_id);
			$result = DB::query($sql, "could not get tax group");
			return DB::fetch($result);
		}

		public static function delete($id) {
			DB::begin_transaction();
			$sql = "DELETE FROM tax_groups WHERE id=" . DB::escape($id);
			DB::query($sql, "could not delete tax group");
			static::delete_items($id);
			DB::commit_transaction();
		}

		public static function add_items($id, $items, $rates) {
			for (
				$i = 0; $i < count($items); $i++
			)
			{
				$sql
				 = "INSERT INTO tax_group_items (tax_group_id, tax_type_id, rate)
			VALUES (" . DB::escape($id) . ", " . DB::escape($items[$i]) . ", " . $rates[$i] . ")";
				DB::query($sql, "could not add item tax group item");
			}
		}

		public static function delete_items($id) {
			$sql = "DELETE FROM tax_group_items WHERE tax_group_id=" . DB::escape($id);
			DB::query($sql, "could not delete item tax group items");
		}

		public static function get_for_item($id) {
			$sql
			 = "SELECT tax_group_items.*, tax_types.name AS tax_type_name, tax_types.rate,
		tax_types.sales_gl_code, tax_types.purchasing_gl_code
		FROM tax_group_items, tax_types	WHERE tax_group_id=" . DB::escape($id) . "	AND tax_types.id=tax_type_id";
			return DB::query($sql, "could not get item tax type group items");
		}

		public static function get_items_as_array($id) {
			$ret_tax_array = array();
			$tax_group_items = static::get_for_item($id);
			while ($tax_group_item = DB::fetch($tax_group_items))
			{
				$index = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_id'] = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_name'] = $tax_group_item['tax_type_name'];
				$ret_tax_array[$index]['sales_gl_code'] = $tax_group_item['sales_gl_code'];
				$ret_tax_array[$index]['purchasing_gl_code'] = $tax_group_item['purchasing_gl_code'];
				$ret_tax_array[$index]['rate'] = $tax_group_item['rate'];
				$ret_tax_array[$index]['Value'] = 0;
			}
			return $ret_tax_array;
		}

		public static function get_shipping_items() {
			$sql
			 = "SELECT tax_group_items.*, tax_types.name AS tax_type_name, tax_types.rate,
		tax_types.sales_gl_code, tax_types.purchasing_gl_code
		FROM tax_group_items, tax_types, tax_groups
		WHERE tax_groups.tax_shipping=1
		AND tax_groups.id=tax_group_id
		AND tax_types.id=tax_type_id";
			return DB::query($sql, "could not get shipping tax group items");
		}

		public static function for_shipping_as_array() {
			$ret_tax_array = array();
			$tax_group_items = static::get_shipping_items();
			while ($tax_group_item = DB::fetch($tax_group_items))
			{
				$index = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_id'] = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_name'] = $tax_group_item['tax_type_name'];
				$ret_tax_array[$index]['sales_gl_code'] = $tax_group_item['sales_gl_code'];
				$ret_tax_array[$index]['purchasing_gl_code'] = $tax_group_item['purchasing_gl_code'];
				$ret_tax_array[$index]['rate'] = $tax_group_item['rate'];
				$ret_tax_array[$index]['Value'] = 0;
			}
			return $ret_tax_array;
		}


		// TAX GROUPS
		public static function select($name, $selected_id = null, $none_option = false, $submit_on_change = false) {
			$sql = "SELECT id, name FROM tax_groups";
			return select_box($name, $selected_id, $sql, 'id', 'name', array(
				'order' => 'id', 'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
		}

		public static function groups_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Tax_Groups::select($name, $selected_id, $none_option, $submit_on_change);
			echo "</td>\n";
		}

		public static function groups_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			Tax_Groups::cells(null, $name, $selected_id, $none_option, $submit_on_change);
			echo "</tr>\n";
		}
	}

?>