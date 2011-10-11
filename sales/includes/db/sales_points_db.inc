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
	function add_sales_point($name, $location, $account, $cash, $credit) {
		$sql = "INSERT INTO sales_pos (pos_name, pos_location, pos_account, cash_sale, credit_sale) VALUES (" . DBOld::escape($name)
		 . "," . DBOld::escape($location) . "," . DBOld::escape($account)
		 . ",$cash,$credit)";
		DBOld::query($sql, "could not add point of sale");
	}

	function update_sales_point($id, $name, $location, $account, $cash, $credit) {

		$sql = "UPDATE sales_pos SET pos_name=" . DBOld::escape($name)
		 . ",pos_location=" . DBOld::escape($location)
		 . ",pos_account=" . DBOld::escape($account)
		 . ",cash_sale =$cash"
		 . ",credit_sale =$credit"
		 . " WHERE id = " . DBOld::escape($id);

		DBOld::query($sql, "could not update sales type");
	}

	function get_all_sales_points($all = false) {
		$sql = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM "
		 . "sales_pos as pos
		LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
		LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id";
		if (!$all) $sql .= " WHERE !pos.inactive";

		return DBOld::query($sql, "could not get all POS definitions");
	}

	function get_sales_point($id) {
		$sql = "SELECT pos.*, loc.location_name, acc.bank_account_name FROM "
		 . "sales_pos as pos
		LEFT JOIN locations as loc on pos.pos_location=loc.loc_code
		LEFT JOIN bank_accounts as acc on pos.pos_account=acc.id
		WHERE pos.id=" . DBOld::escape($id);

		$result = DBOld::query($sql, "could not get POS definition");

		return DBOld::fetch($result);
	}

	function get_sales_point_name($id) {
		$sql = "SELECT pos_name FROM sales_pos WHERE id=" . DBOld::escape($id);

		$result = DBOld::query($sql, "could not get POS name");

		$row = DBOld::fetch_row($result);
		return $row[0];
	}

	function delete_sales_point($id) {
		$sql = "DELETE FROM sales_pos WHERE id=" . DBOld::escape($id);
		DBOld::query($sql, "The point of sale record could not be deleted");
	}

?>