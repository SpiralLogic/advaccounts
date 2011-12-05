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
	class Sales_Point {
		public static function		 add($name, $location, $account, $cash, $credit) {
			$sql = "INSERT INTO sales_pos (pos_name, pos_location, pos_account, cash_sale, credit_sale) VALUES (" . DB::escape($name) . "," . DB::escape($location) . "," . DB::escape($account) . ",$cash,$credit)";
			DB::query($sql, "could not add point of sale");
		}

		public static function update($id, $name, $location, $account, $cash, $credit) {
			$sql = "UPDATE sales_pos SET pos_name=" . DB::escape($name) . ",pos_location=" . DB::escape($location) . ",pos_account=" . DB::escape($account) . ",cash_sale =$cash" . ",credit_sale =$credit" . " WHERE id = " . DB::escape($id);
			DB::query($sql, "could not update sales type");
		}

		public static function get_all($all = false) {
			$sql = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM " . "sales_pos as pos
		LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
		LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id";
			if (!$all) {
				$sql .= " WHERE !pos.inactive";
			}
			return DB::query($sql, "could not get all POS definitions");
		}

		public static function get($id) {
			$sql = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM " . "sales_pos as pos
		LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
		LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id
		WHERE pos.id=" . DB::escape($id);
			$result = DB::query($sql, "could not get POS definition");
			return DB::fetch($result);
		}

		public static function get_name($id) {
			$sql = "SELECT pos_name FROM sales_pos WHERE id=" . DB::escape($id);
			$result = DB::query($sql, "could not get POS name");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		public static function delete($id) {
			$sql = "DELETE FROM sales_pos WHERE id=" . DB::escape($id);
			DB::query($sql, "The point of sale record could not be deleted");
		}

		public static function row($label, $name, $selected_id = null, $spec_option = false, $submit_on_change = false) {
			$sql = "SELECT id, pos_name, inactive FROM sales_pos";
			JS::default_focus($name);
			echo '<tr>';
			if ($label != null) {
				echo "<td class='label'>$label</td>\n";
			}
			echo "<td>";
			echo select_box($name, $selected_id, $sql, 'id', 'pos_name', array(
				'select_submit' => $submit_on_change, 'async' => true, 'spec_option' => $spec_option, 'spec_id' => -1, 'order' => array('pos_name')));
			echo "</td></tr>\n";
		}
	}