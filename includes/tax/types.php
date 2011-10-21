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
	class Tax_Types {
		public static function add($name, $sales_gl_code, $purchasing_gl_code, $rate) {
			$sql = "INSERT INTO tax_types (name, sales_gl_code, purchasing_gl_code, rate)
		VALUES (" . DBOld::escape($name) . ", " . DBOld::escape($sales_gl_code)
			 . ", " . DBOld::escape($purchasing_gl_code) . ", $rate)";

			DBOld::query($sql, "could not add tax type");
		}

		public static function update($type_id, $name, $sales_gl_code, $purchasing_gl_code, $rate) {
			$sql = "UPDATE tax_types SET name=" . DBOld::escape($name) . ",
		sales_gl_code=" . DBOld::escape($sales_gl_code) . ",
		purchasing_gl_code=" . DBOld::escape($purchasing_gl_code) . ",
		rate=$rate
		WHERE id=" . DBOld::escape($type_id);

			DBOld::query($sql, "could not update tax type");
		}

		public static function get_all($all = false) {
			$sql = "SELECT tax_types.*,
		Chart1.account_name AS SalesAccountName,
		Chart2.account_name AS PurchasingAccountName
		FROM tax_types, chart_master AS Chart1,
		chart_master AS Chart2
		WHERE tax_types.sales_gl_code = Chart1.account_code
		AND tax_types.purchasing_gl_code = Chart2.account_code";

			if (!$all) $sql .= " AND !tax_types.inactive";
			return DBOld::query($sql, "could not get all tax types");
		}

		public static function get_all_simple() {
			$sql = "SELECT * FROM tax_types";

			return DBOld::query($sql, "could not get all tax types");
		}

		public static function get($type_id) {
			$sql = "SELECT tax_types.*,
		Chart1.account_name AS SalesAccountName,
		Chart2.account_name AS PurchasingAccountName
		FROM tax_types, chart_master AS Chart1,
		chart_master AS Chart2
		WHERE tax_types.sales_gl_code = Chart1.account_code
		AND tax_types.purchasing_gl_code = Chart2.account_code AND id=" . DBOld::escape($type_id);

			$result = DBOld::query($sql, "could not get tax type");
			return DBOld::fetch($result);
		}

		public static function get_default_rate($type_id) {
			$sql = "SELECT rate FROM tax_types WHERE id=" . DBOld::escape($type_id);

			$result = DBOld::query($sql, "could not get tax type rate");

			$row = DBOld::fetch_row($result);
			return $row[0];
		}

		public static function delete_tax_type($type_id) {
			DBOld::begin_transaction();

			$sql = "DELETE FROM tax_types WHERE id=" . DBOld::escape($type_id);

			DBOld::query($sql, "could not delete tax type");

			// also delete any item tax exemptions associated with this type
			$sql = "DELETE FROM item_tax_type_exemptions WHERE tax_type_id=$type_id";

			DBOld::query($sql, "could not delete item tax type exemptions");

			DBOld::commit_transaction();
		}

		/*
			 Check if gl_code is used by more than 2 tax types,
			 or check if the two gl codes are not used by any other
			 than selected tax type.
			 Necessary for pre-2.2 installations.
		 */
		public static function is_tax_gl_unique($gl_code, $gl_code2 = -1, $selected_id = -1) {

			$purch_code = $gl_code2 == -1 ? $gl_code : $gl_code2;

			$sql = "SELECT count(*) FROM "
			 . "tax_types
		WHERE (sales_gl_code=" . DBOld::escape($gl_code)
			 . " OR purchasing_gl_code=" . DBOld::escape($purch_code) . ")";

			if ($selected_id != -1)
				$sql .= " AND id!=" . DBOld::escape($selected_id);

			$res = DBOld::query($sql, "could not query gl account uniqueness");
			$row = DBOld::fetch($res);

			return $gl_code2 == -1 ? ($row[0] <= 1) : ($row[0] == 0);
		}
	}

?>