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
	class Item_Purchase {
		public static function add($supplier_id, $stock_id, $price,
																		$suppliers_uom, $conversion_factor, $supplier_description, $stockid = null)
	{
		if ($stockid == null) {
			$stockid = Item::get_stockid($stock_id);
		}
		$sql = "INSERT INTO purch_data (supplier_id, stockid, stock_id, price, suppliers_uom,
		conversion_factor, supplier_description) VALUES (";
		$sql .= DB::escape($supplier_id) . ", " . DB::escape($stock_id) . ", " . DB::escape($stockid) . ", "
						. $price . ", " . DB::escape($suppliers_uom) . ", "
						. $conversion_factor . ", "
						. DB::escape($supplier_description) . ")";
		DB::query($sql, "The supplier purchasing details could not be added");
	}

	public static function update($selected_id, $stock_id, $price,
																			 $suppliers_uom, $conversion_factor, $supplier_description)
	{
		$sql = "UPDATE purch_data SET price=" . $price . ",
		suppliers_uom=" . DB::escape($suppliers_uom) . ",
		conversion_factor=" . $conversion_factor . ",
		supplier_description=" . DB::escape($supplier_description) . "
		WHERE stock_id=" . DB::escape($stock_id) . " AND
		supplier_id=" . DB::escape($selected_id);
		DB::query($sql, "The supplier purchasing details could not be updated");
	}

	public static function delete($selected_id, $stock_id)
	{
		$sql = "DELETE FROM purch_data WHERE supplier_id=" . DB::escape($selected_id) . "
		AND stock_id=" . DB::escape($stock_id);
		DB::query($sql, "could not delete purchasing data");
	}

	public static function get_all($stock_id)
	{
		$sql = "SELECT purch_data.*,suppliers.supp_name, suppliers.curr_code
		FROM purch_data INNER JOIN suppliers
		ON purch_data.supplier_id=suppliers.supplier_id
		WHERE stock_id = " . DB::escape($stock_id);
		return DB::query($sql, "The supplier purchasing details for the selected part could not be retrieved");
	}

	public static function get($selected_id, $stock_id)
	{
		$sql = "SELECT purch_data.*,suppliers.supp_name FROM purch_data
		INNER JOIN suppliers ON purch_data.supplier_id=suppliers.supplier_id
		WHERE purch_data.supplier_id=" . DB::escape($selected_id) . "
		AND purch_data.stock_id=" . DB::escape($stock_id);
		$result = DB::query($sql, "The supplier purchasing details for the selected supplier and item could not be retrieved");
		return DB::fetch($result);
	}
	}
?>