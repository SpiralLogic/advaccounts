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
	class Tax_Groups
	{
		static function clear_shipping_tax_group()
		{
			$sql = "UPDATE tax_groups SET tax_shipping=0 WHERE 1";
			DBOld::query($sql, "could not update tax_shipping fields");
		}

		static function add_tax_group($name, $tax_shipping, $taxes, $rates)
		{
			DBOld::begin_transaction();
			if ($tax_shipping) // only one tax group for shipping
			{
				static::clear_shipping_tax_group();
			}
			$sql = "INSERT INTO tax_groups (name, tax_shipping) VALUES (" . DB::escape($name) . ", " . DB::escape($tax_shipping) . ")";
			DBOld::query($sql, "could not add tax group");
			$id = DBOld::insert_id();
			static::add_tax_group_items($id, $taxes, $rates);
			DBOld::commit_transaction();
		}

		static function update_tax_group($id, $name, $tax_shipping, $taxes, $rates)
		{
			DBOld::begin_transaction();
			if ($tax_shipping) // only one tax group for shipping
			{
				static::clear_shipping_tax_group();
			}
			$sql = "UPDATE tax_groups SET name=" . DB::escape($name) . ",tax_shipping=" . DB::escape($tax_shipping) . " WHERE id=" . DB::escape($id);
			DBOld::query($sql, "could not update tax group");
			static::delete_tax_group_items($id);
			static::add_tax_group_items($id, $taxes, $rates);
			DBOld::commit_transaction();
		}

		static function get_all_tax_groups($all = false)
		{
			$sql = "SELECT * FROM tax_groups";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			return DBOld::query($sql, "could not get all tax group");
		}

		static function get_tax_group($type_id)
		{
			$sql = "SELECT * FROM tax_groups WHERE id=" . DB::escape($type_id);
			$result = DBOld::query($sql, "could not get tax group");
			return DBOld::fetch($result);
		}

		static function delete_tax_group($id)
		{
			DBOld::begin_transaction();
			$sql = "DELETE FROM tax_groups WHERE id=" . DB::escape($id);
			DBOld::query($sql, "could not delete tax group");
			static::delete_tax_group_items($id);
			DBOld::commit_transaction();
		}

		static function add_tax_group_items($id, $items, $rates)
		{
			for (
				$i = 0; $i < count($items); $i++
			)
			{
				$sql
				 = "INSERT INTO tax_group_items (tax_group_id, tax_type_id, rate)
			VALUES (" . DB::escape($id) . ",  " . DB::escape($items[$i]) . ", " . $rates[$i] . ")";
				DBOld::query($sql, "could not add item tax group item");
			}
		}

		static function delete_tax_group_items($id)
		{
			$sql = "DELETE FROM tax_group_items WHERE tax_group_id=" . DB::escape($id);
			DBOld::query($sql, "could not delete item tax group items");
		}

		static function get_for_item($id)
		{
			$sql
			 = "SELECT tax_group_items.*, tax_types.name AS tax_type_name, tax_types.rate,
		tax_types.sales_gl_code, tax_types.purchasing_gl_code
		FROM tax_group_items, tax_types	WHERE tax_group_id=" . DB::escape($id) . "	AND tax_types.id=tax_type_id";
			return DBOld::query($sql, "could not get item tax type group items");
		}

		static function get_tax_group_items_as_array($id)
		{
			$ret_tax_array = array();
			$tax_group_items = static::get_for_item($id);
			while ($tax_group_item = DBOld::fetch($tax_group_items))
			{
				$index                                       = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_id']        = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_name']      = $tax_group_item['tax_type_name'];
				$ret_tax_array[$index]['sales_gl_code']      = $tax_group_item['sales_gl_code'];
				$ret_tax_array[$index]['purchasing_gl_code'] = $tax_group_item['purchasing_gl_code'];
				$ret_tax_array[$index]['rate']               = $tax_group_item['rate'];
				$ret_tax_array[$index]['Value']              = 0;
			}
			return $ret_tax_array;
		}

		static function get_shipping_tax_group_items()
		{
			$sql
			 = "SELECT tax_group_items.*, tax_types.name AS tax_type_name, tax_types.rate,
		tax_types.sales_gl_code, tax_types.purchasing_gl_code
		FROM tax_group_items, tax_types, tax_groups
		WHERE tax_groups.tax_shipping=1
		AND tax_groups.id=tax_group_id
		AND tax_types.id=tax_type_id";
			return DBOld::query($sql, "could not get shipping tax group items");
		}

		static function get_shipping_tax_as_array()
		{
			$ret_tax_array = array();
			$tax_group_items = static::get_shipping_tax_group_items();
			while ($tax_group_item = DBOld::fetch($tax_group_items))
			{
				$index                                       = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_id']        = $tax_group_item['tax_type_id'];
				$ret_tax_array[$index]['tax_type_name']      = $tax_group_item['tax_type_name'];
				$ret_tax_array[$index]['sales_gl_code']      = $tax_group_item['sales_gl_code'];
				$ret_tax_array[$index]['purchasing_gl_code'] = $tax_group_item['purchasing_gl_code'];
				$ret_tax_array[$index]['rate']               = $tax_group_item['rate'];
				$ret_tax_array[$index]['Value']              = 0;
			}
			return $ret_tax_array;
		}
	}

?>