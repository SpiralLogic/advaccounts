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
	class Tax_Types
	{
		static public function add($name, $sales_gl_code, $purchasing_gl_code, $rate) {
			$sql = "INSERT INTO tax_types (name, sales_gl_code, purchasing_gl_code, rate)
		VALUES (" . DB::escape($name) . ", " . DB::escape($sales_gl_code)
			 . ", " . DB::escape($purchasing_gl_code) . ", $rate)";
			DB::query($sql, "could not add tax type");
		}

		static public function update($type_id, $name, $sales_gl_code, $purchasing_gl_code, $rate) {
			$sql = "UPDATE tax_types SET name=" . DB::escape($name) . ",
		sales_gl_code=" . DB::escape($sales_gl_code) . ",
		purchasing_gl_code=" . DB::escape($purchasing_gl_code) . ",
		rate=$rate
		WHERE id=" . DB::escape($type_id);
			DB::query($sql, "could not update tax type");
		}

		static public function get_all($all = false) {
			$sql = "SELECT tax_types.*,
		Chart1.account_name AS SalesAccountName,
		Chart2.account_name AS PurchasingAccountName
		FROM tax_types, chart_master AS Chart1,
		chart_master AS Chart2
		WHERE tax_types.sales_gl_code = Chart1.account_code
		AND tax_types.purchasing_gl_code = Chart2.account_code";
			if (!$all) {
				$sql .= " AND !tax_types.inactive";
			}
			return DB::query($sql, "could not get all tax types");
		}

		static public function get_all_simple() {
			$sql = "SELECT * FROM tax_types";
			return DB::query($sql, "could not get all tax types");
		}

		static public function get($type_id) {
			$sql = "SELECT tax_types.*,
		Chart1.account_name AS SalesAccountName,
		Chart2.account_name AS PurchasingAccountName
		FROM tax_types, chart_master AS Chart1,
		chart_master AS Chart2
		WHERE tax_types.sales_gl_code = Chart1.account_code
		AND tax_types.purchasing_gl_code = Chart2.account_code AND id=" . DB::escape($type_id);
			$result = DB::query($sql, "could not get tax type");
			return DB::fetch($result);
		}

		static public function get_default_rate($type_id) {
			$sql = "SELECT rate FROM tax_types WHERE id=" . DB::escape($type_id);
			$result = DB::query($sql, "could not get tax type rate");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		static public function delete($type_id) {
			DB::begin();
			$sql = "DELETE FROM tax_types WHERE id=" . DB::escape($type_id);
			DB::query($sql, "could not delete tax type");
			// also delete any item tax exemptions associated with this type
			$sql = "DELETE FROM item_tax_type_exemptions WHERE tax_type_id=$type_id";
			DB::query($sql, "could not delete item tax type exemptions");
			DB::commit();
		}

		/**
		Check if gl_code is used by more than 2 tax types,
		or check if the two gl codes are not used by any other
		than selected tax type.
		Necessary for pre-2.2 installations.
		 */
		static public function is_tax_gl_unique($gl_code, $gl_code2 = -1, $selected_id = -1) {
			$purch_code = $gl_code2 == -1 ? $gl_code : $gl_code2;
			$sql = "SELECT count(*) FROM "
			 . "tax_types
		WHERE (sales_gl_code=" . DB::escape($gl_code)
			 . " OR purchasing_gl_code=" . DB::escape($purch_code) . ")";
			if ($selected_id != -1) {
				$sql .= " AND id!=" . DB::escape($selected_id);
			}
			$res = DB::query($sql, "could not query gl account uniqueness");
			$row = DB::fetch($res);
			return $gl_code2 == -1 ? ($row[0] <= 1) : ($row[0] == 0);
		}

		static public function select($name, $selected_id = null, $none_option = false, $submit_on_change = false) {
			$sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM tax_types";
			return select_box($name, $selected_id, $sql, 'id', 'name', array(
																																			'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
		}

		static public function cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Tax_Types::select($name, $selected_id, $none_option, $submit_on_change);
			echo "</td>\n";
		}

		static public function row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			Tax_Types::cells(null, $name, $selected_id, $none_option, $submit_on_change);
			echo "</tr>\n";
		}
	}

?>