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
	class Tax_ItemType {
		public static function add($name, $exempt, $exempt_from) {
			DBOld::begin_transaction();

			$sql = "INSERT INTO item_tax_types (name, exempt)
		VALUES (" . DBOld::escape($name) . "," . DBOld::escape($exempt) . ")";

			DBOld::query($sql, "could not add item tax type");

			$id = DBOld::insert_id();

			// add the exemptions
			static::add_exemptions($id, $exempt_from);

			DBOld::commit_transaction();
		}

		public static function update($id, $name, $exempt, $exempt_from) {
			DBOld::begin_transaction();

			$sql = "UPDATE item_tax_types SET name=" . DBOld::escape($name) .
			 ",	exempt=" . DBOld::escape($exempt) . " WHERE id=" . DBOld::escape($id);

			DBOld::query($sql, "could not update item tax type");

			// readd the exemptions
			static::delete_exemptions($id);
			static::add_exemptions($id, $exempt_from);

			DBOld::commit_transaction();
		}

		public static function get_all() {
			$sql = "SELECT * FROM item_tax_types";

			return DBOld::query($sql, "could not get all item tax type");
		}

		public static function get($id) {
			$sql = "SELECT * FROM item_tax_types WHERE id=" . DBOld::escape($id);

			$result = DBOld::query($sql, "could not get item tax type");

			return DBOld::fetch($result);
		}

		public static function get_for_item($stock_id) {
			$sql = "SELECT item_tax_types.* FROM item_tax_types,stock_master WHERE
		stock_master.stock_id=" . DBOld::escape($stock_id) . "
		AND item_tax_types.id=stock_master.tax_type_id";

			$result = DBOld::query($sql, "could not get item tax type");

			return DBOld::fetch($result);
		}

		public static function delete($id) {
			DBOld::begin_transaction();

			$sql = "DELETE FROM item_tax_types WHERE id=" . DBOld::escape($id);

			DBOld::query($sql, "could not delete item tax type");
			// also delete all exemptions
			static::delete_exemptions($id);

			DBOld::commit_transaction();
		}

		public static function add_exemptions($id, $exemptions) {
			for ($i = 0; $i < count($exemptions); $i++)
			{
				$sql = "INSERT INTO item_tax_type_exemptions (item_tax_type_id, tax_type_id)
			VALUES (" . DBOld::escape($id) . ",  " . DBOld::escape($exemptions[$i]) . ")";
				DBOld::query($sql, "could not add item tax type exemptions");
			}
		}

		public static function delete_exemptions($id) {
			$sql = "DELETE FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DBOld::escape($id);

			DBOld::query($sql, "could not delete item tax type exemptions");
		}

		public static function get_exemptions($id) {
			$sql = "SELECT * FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DBOld::escape($id);

			return DBOld::query($sql, "could not get item tax type exemptions");
		}
	}

?>