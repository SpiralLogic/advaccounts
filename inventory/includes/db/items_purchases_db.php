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

	function add_item_purchasing_data($supplier_id, $stock_id, $price,
																		$suppliers_uom, $conversion_factor, $supplier_description, $stockid = null) {

		if ($stockid == null) $stockid = get_stockid($stock_id);

		$sql = "INSERT INTO " . TB_PREF . "purch_data (supplier_id, stockid, stock_id, price, suppliers_uom,
		conversion_factor, supplier_description) VALUES (";
		$sql .= DB::escape($supplier_id) . ", " . DB::escape($stock_id) . ", " . DB::escape($stockid) . ", "
		 . $price . ", " . DB::escape($suppliers_uom) . ", "
		 . $conversion_factor . ", "
		 . DB::escape($supplier_description) . ")";

		DBOld::query($sql, "The supplier purchasing details could not be added");
	}

	function update_item_purchasing_data($selected_id, $stock_id, $price,
																			 $suppliers_uom, $conversion_factor, $supplier_description) {
		$sql = "UPDATE " . TB_PREF . "purch_data SET price=" . $price . ",
		suppliers_uom=" . DB::escape($suppliers_uom) . ",
		conversion_factor=" . $conversion_factor . ",
		supplier_description=" . DB::escape($supplier_description) . "
		WHERE stock_id=" . DB::escape($stock_id) . " AND
		supplier_id=" . DB::escape($selected_id);
		DBOld::query($sql, "The supplier purchasing details could not be updated");
	}

	function delete_item_purchasing_data($selected_id, $stock_id) {
		$sql = "DELETE FROM " . TB_PREF . "purch_data WHERE supplier_id=" . DB::escape($selected_id) . "
		AND stock_id=" . DB::escape($stock_id);
		DBOld::query($sql, "could not delete purchasing data");
	}

	function get_items_purchasing_data($stock_id) {
		$sql = "SELECT " . TB_PREF . "purch_data.*," . TB_PREF . "suppliers.supp_name,"
		 . TB_PREF . "suppliers.curr_code
		FROM " . TB_PREF . "purch_data INNER JOIN " . TB_PREF . "suppliers
		ON " . TB_PREF . "purch_data.supplier_id=" . TB_PREF . "suppliers.supplier_id
		WHERE stock_id = " . DB::escape($stock_id);

		return DBOld::query($sql, "The supplier purchasing details for the selected part could not be retrieved");
	}

	function get_item_purchasing_data($selected_id, $stock_id) {
		$sql = "SELECT " . TB_PREF . "purch_data.*," . TB_PREF . "suppliers.supp_name FROM " . TB_PREF . "purch_data
		INNER JOIN " . TB_PREF . "suppliers ON " . TB_PREF . "purch_data.supplier_id=" . TB_PREF . "suppliers.supplier_id
		WHERE " . TB_PREF . "purch_data.supplier_id=" . DB::escape($selected_id) . "
		AND " . TB_PREF . "purch_data.stock_id=" . DB::escape($stock_id);

		$result = DBOld::query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");

		return DBOld::fetch($result);
	}

?>