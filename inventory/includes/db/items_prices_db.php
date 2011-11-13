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
	function add_item_price($stock_id, $sales_type_id, $curr_abrev, $price, $stockid = null)
	{
		if ($stockid == null) {
			$stockid = Item::get_stockid($stock_id);
		}
		$sql = "INSERT INTO prices (stockid, stock_id, sales_type_id, curr_abrev, price)
		VALUES (" . DB::escape($stockid) . ", " . DB::escape($stock_id) . ", " . DB::escape($sales_type_id)
					 . ", " . DB::escape($curr_abrev) . ", " . DB::escape($price) . ")";
		DB::query($sql, "an item price could not be added");
	}

	function update_item_price($price_id, $sales_type_id, $curr_abrev, $price)
	{
		$sql = "UPDATE prices SET sales_type_id=" . DB::escape($sales_type_id) . ",
		curr_abrev=" . DB::escape($curr_abrev) . ",
		price=" . DB::escape($price) . " WHERE id=" . DB::escape($price_id);
		DB::query($sql, "an item price could not be updated");
	}

	function delete_item_price($price_id)
	{
		$sql = "DELETE FROM prices WHERE id= " . DB::escape($price_id);
		DB::query($sql, "an item price could not be deleted");
	}

	function get_prices($stock_id)
	{
		$sql = "SELECT sales_types.sales_type, prices.*
		FROM prices, sales_types
		WHERE prices.sales_type_id = sales_types.id
		AND stock_id=" . DB::escape($stock_id)
					 . " ORDER BY curr_abrev, sales_type_id";
		return DB::query($sql, "item prices could not be retreived");
	}

	function get_stock_price($price_id)
	{
		$sql = "SELECT * FROM prices WHERE id=" . DB::escape($price_id);
		$result = DB::query($sql, "price could not be retreived");
		return DB::fetch($result);
	}

	function get_standard_cost($stock_id)
	{
		$sql = "SELECT IF(s.mb_flag='" . STOCK_SERVICE . "', 0, material_cost + labour_cost + overhead_cost) AS std_cost
			FROM stock_master s WHERE stock_id=" . DB::escape($stock_id);
		$result = DB::query($sql, "The standard cost cannot be retrieved");
		$myrow = DB::fetch_row($result);
		return $myrow[0];
	}
	//----------------------------------------------------------------------------------------
	function get_calculated_price($stock_id, $add_pct)
	{
		$avg = get_standard_cost($stock_id);
		if ($avg == 0) {
			return 0;
		}
		return Num::round($avg * (1 + $add_pct / 100), User::price_dec());
	}
	//--------------------------------------------------------------------------------------
	function get_price($stock_id, $currency, $sales_type_id, $factor = null, $date = null)
	{
		if ($date == null) {
			$date = Dates::new_doc_date();
		}
		if ($factor === null) {
			$myrow = Sales_Type::get($sales_type_id);
			$factor = $myrow['factor'];
		}
		$add_pct = DB_Company::get_pref('add_pct');
		$base_id = DB_Company::get_base_sales_type();
		$home_curr = Banking::get_company_currency();
		//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
		$sql
		 = "SELECT price, curr_abrev, sales_type_id
		FROM prices
		WHERE stock_id = " . DB::escape($stock_id) . "
			AND (curr_abrev = " . DB::escape($currency) . " OR curr_abrev = " . DB::escape($home_curr) . ")";
		$result = DB::query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
		$num_rows = DB::num_rows($result);
		$rate = Num::round(Banking::get_exchange_rate_from_home_currency($currency, $date),
			User::exrate_dec());
		$round_to = DB_Company::get_pref('round_to');
		$prices = array();
		while ($myrow = DB::fetch($result))
		{
			$prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
		}
		$price = false;
		if (isset($prices[$sales_type_id][$currency])) {
			$price = $prices[$sales_type_id][$currency];
		}
		elseif (isset($prices[$base_id][$currency]))
		{
			$price = $prices[$base_id][$currency] * $factor;
		}
		elseif (isset($prices[$sales_type_id][$home_curr]))
		{
			$price = $prices[$sales_type_id][$home_curr] / $rate;
		}
		elseif (isset($prices[$base_id][$home_curr]))
		{
			$price = $prices[$base_id][$home_curr] * $factor / $rate;
		}
			/*
							 if (isset($prices[$sales_type_id][$home_curr]))
							 {
								 $price = $prices[$sales_type_id][$home_curr] / $rate;
							 }
							 elseif (isset($prices[$base_id][$currency]))
							 {
								 $price = $prices[$base_id][$currency] * $factor;
							 }
							 elseif (isset($prices[$base_id][$home_curr]))
							 {
								 $price = $prices[$base_id][$home_curr] * $factor / $rate;
							 }
						 */
		elseif ($num_rows == 0 && $add_pct != -1)
		{
			$price = get_calculated_price($stock_id, $add_pct);
			if ($currency != $home_curr) {
				$price /= $rate;
			}
			if ($factor != 0) {
				$price *= $factor;
			}
		}
		if ($price === false) {
			return 0;
		}
		elseif ($round_to != 1)
		{
			return Num::round_to_nearest($price, $round_to);
		} else {
			return Num::round($price, User::price_dec());
		}
	}

		//----------------------------------------------------------------------------------------
		//
		//	Get price for given item or kit.
		//  When $std==true price is calculated as a sum of all included stock items,
		//	otherwise all prices set for kits and items are accepted.
		//
		function get_kit_price($item_code, $currency, $sales_type_id, $factor = null,
													 $date = null, $std = false)
		{
			$kit_price = 0.00;
			if (!$std) {
				$kit_price = get_price($item_code, $currency, $sales_type_id,
					$factor, $date);
				if ($kit_price !== false) {
					return $kit_price;
				}
			}
			// no price for kit found, get total value of all items
			$kit = Item_Code::get_kit($item_code);
			while ($item = DB::fetch($kit)) {
				if ($item['item_code'] != $item['stock_id']) {
					// foreign/kit code
					$kit_price += $item['quantity'] * get_kit_price($item['stock_id'],
						$currency, $sales_type_id, $factor, $date, $std);
				}
				else {
					// stock item
					$kit_price += $item['quantity'] * get_price($item['stock_id'],
						$currency, $sales_type_id, $factor, $date);
				}
			}
			return $kit_price;
		}

		//----------------------------------------------------------------------------------------
		function get_purchase_price($supplier_id, $stock_id)
		{
			$sql
			 = "SELECT price, conversion_factor FROM purch_data
			WHERE supplier_id = " . DB::escape($supplier_id) . "
			AND stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");
			if (DB::num_rows($result) == 1) {
				$myrow = DB::fetch($result);
				return $myrow["price"] / $myrow['conversion_factor'];
			} else {
				return 0;
			}
		}

?>
