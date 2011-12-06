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
	class Tax_ItemType {
		public static function add($name, $exempt, $exempt_from) {
			DB::begin_transaction();

			$sql = "INSERT INTO item_tax_types (name, exempt)
		VALUES (" . DB::escape($name) . "," . DB::escape($exempt) . ")";

			DB::query($sql, "could not add item tax type");

			$id = DB::insert_id();

			// add the exemptions
			static::add_exemptions($id, $exempt_from);

			DB::commit_transaction();
		}

		public static function update($id, $name, $exempt, $exempt_from) {
			DB::begin_transaction();

			$sql = "UPDATE item_tax_types SET name=" . DB::escape($name) .
			 ",	exempt=" . DB::escape($exempt) . " WHERE id=" . DB::escape($id);

			DB::query($sql, "could not update item tax type");

			// readd the exemptions
			static::delete_exemptions($id);
			static::add_exemptions($id, $exempt_from);

			DB::commit_transaction();
		}

		public static function get_all() {
			$sql = "SELECT * FROM item_tax_types";

			return DB::query($sql, "could not get all item tax type");
		}

		public static function get($id) {
			$sql = "SELECT * FROM item_tax_types WHERE id=" . DB::escape($id);

			$result = DB::query($sql, "could not get item tax type");

			return DB::fetch($result);
		}

		public static function get_for_item($stock_id) {
			$sql = "SELECT item_tax_types.* FROM item_tax_types,stock_master WHERE
		stock_master.stock_id=" . DB::escape($stock_id) . "
		AND item_tax_types.id=stock_master.tax_type_id";

			$result = DB::query($sql, "could not get item tax type");

			return DB::fetch($result);
		}

		public static function delete($id) {
			DB::begin_transaction();

			$sql = "DELETE FROM item_tax_types WHERE id=" . DB::escape($id);

			DB::query($sql, "could not delete item tax type");
			// also delete all exemptions
			static::delete_exemptions($id);

			DB::commit_transaction();
		}

		public static function add_exemptions($id, $exemptions) {
			for ($i = 0; $i < count($exemptions); $i++)
			{
				$sql = "INSERT INTO item_tax_type_exemptions (item_tax_type_id, tax_type_id)
			VALUES (" . DB::escape($id) . ", " . DB::escape($exemptions[$i]) . ")";
				DB::query($sql, "could not add item tax type exemptions");
			}
		}

		public static function delete_exemptions($id) {
			$sql = "DELETE FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DB::escape($id);

			DB::query($sql, "could not delete item tax type exemptions");
		}

		public static function get_exemptions($id) {
			$sql = "SELECT * FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DB::escape($id);

			return DB::query($sql, "could not get item tax type exemptions");
		}

			// ITEM TAX TYPES
			public static function select($name, $selected_id = null) {
				$sql = "SELECT id, name FROM item_tax_types";
				return select_box($name, $selected_id, $sql, 'id', 'name', array('order' => 'id'));
			}

			public static function cells($label, $name, $selected_id = null) {
				if ($label != null) {
					echo "<td>$label</td>\n";
				}
				echo "<td>";
				echo Tax_ItemType::select($name, $selected_id);
				echo "</td>\n";
			}

			public static function row($label, $name, $selected_id = null) {
				echo "<tr><td class='label'>$label</td>";
				Tax_ItemType::cells(null, $name, $selected_id);
				echo "</tr>\n";
			}

	}

?>