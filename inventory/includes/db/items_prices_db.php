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
	function add_item_price($stock_id, $sales_type_id, $curr_abrev, $price, $stockid = null) {
		if ($stockid == null) $stockid = get_stockid($stock_id);
		$sql = "INSERT INTO prices (stockid, stock_id, sales_type_id, curr_abrev, price)
		VALUES (" . DBOld::escape($stockid) . ", " . DBOld::escape($stock_id) . ", " . DBOld::escape($sales_type_id)
		 . ", " . DBOld::escape($curr_abrev) . ", " . DBOld::escape($price) . ")";

		DBOld::query($sql, "an item price could not be added");
	}

	function update_item_price($price_id, $sales_type_id, $curr_abrev, $price) {

		$sql = "UPDATE prices SET sales_type_id=" . DBOld::escape($sales_type_id) . ",
		curr_abrev=" . DBOld::escape($curr_abrev) . ",
		price=" . DBOld::escape($price) . " WHERE id=" . DBOld::escape($price_id);
		DBOld::query($sql, "an item price could not be updated");
	}

	function delete_item_price($price_id) {
		$sql = "DELETE FROM prices WHERE id= " . DBOld::escape($price_id);
		DBOld::query($sql, "an item price could not be deleted");
	}

	function get_prices($stock_id) {
		$sql = "SELECT sales_types.sales_type, prices.*
		FROM prices, sales_types
		WHERE prices.sales_type_id = sales_types.id
		AND stock_id=" . DBOld::escape($stock_id)
		 . " ORDER BY curr_abrev, sales_type_id";
		return DBOld::query($sql, "item prices could not be retreived");
	}

	function get_stock_price($price_id) {
		$sql = "SELECT * FROM prices WHERE id=" . DBOld::escape($price_id);

		$result = DBOld::query($sql, "price could not be retreived");

		return DBOld::fetch($result);
	}

	function get_standard_cost($stock_id) {
		$sql = "SELECT IF(s.mb_flag='" . STOCK_SERVICE . "', 0, material_cost + labour_cost + overhead_cost) AS std_cost
			FROM stock_master s WHERE stock_id=" . DBOld::escape($stock_id);
		$result = DBOld::query($sql, "The standard cost cannot be retrieved");

		$myrow = DBOld::fetch_row($result);

		return $myrow[0];
	}

	//--------------------------------------------------------------------------------------
?>
