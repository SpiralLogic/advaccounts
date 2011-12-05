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
	class Sales_Type
	{
		public static function add($name, $tax_included, $factor)
		{
			$sql = "INSERT INTO sales_types (sales_type,tax_included,factor) VALUES (" . DB::escape($name) . ","
			 . DB::escape($tax_included) . "," . DB::escape($factor) . ")";
			DB::query($sql, "could not add sales type");
		}

		public static function update($id, $name, $tax_included, $factor)
		{
			$sql = "UPDATE sales_types SET sales_type = " . DB::escape($name) . ",
	tax_included =" . DB::escape($tax_included) . ", factor=" . DB::escape($factor) . " WHERE id = " . DB::escape($id);
			DB::query($sql, "could not update sales type");
		}

		public static function get_all($all = false)
		{
			$sql = "SELECT * FROM sales_types";
			if (!$all) {
				$sql .= " WHERE !inactive";
			}
			return DB::query($sql, "could not get all sales types");
		}

		public static function get($id)
		{
			$sql = "SELECT * FROM sales_types WHERE id=" . DB::escape($id);
			$result = DB::query($sql, "could not get sales type");
			return DB::fetch($result);
		}

		public static function get_name($id)
		{
			$sql = "SELECT sales_type FROM sales_types WHERE id=" . DB::escape($id);
			$result = DB::query($sql, "could not get sales type");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		public static function delete($id)
		{
			$sql = "DELETE FROM sales_types WHERE id=" . DB::escape($id);
			DB::query($sql, "The Sales type record could not be deleted");
			$sql = "DELETE FROM prices WHERE sales_type_id=" . DB::escape($id);
			DB::query($sql, "The Sales type prices could not be deleted");
		}
		// SALES TYPES
				public static function	select($name, $selected_id = null, $submit_on_change = false, $special_option = false) {
					$sql = "SELECT id, sales_type, inactive FROM sales_types";
					return select_box($name, $selected_id, $sql, 'id', 'sales_type', array(
																																								 'spec_option' => $special_option === true ? _("All Sales Types") :
																																									$special_option, 'spec_id' => 0, 'select_submit' => $submit_on_change, //	 'async' => false,
																																						));
				}

				public static function	cells($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
					if ($label != null) {
						echo "<td>$label</td>\n";
					}
					echo "<td>";
					echo static::select($name, $selected_id, $submit_on_change, $special_option);
					echo "</td>\n";
				}

				public static function	row($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
					echo "<tr><td class='label'>$label</td>";
					static::cells(null, $name, $selected_id, $submit_on_change, $special_option);
					echo "</tr>\n";
				}

	}

?>