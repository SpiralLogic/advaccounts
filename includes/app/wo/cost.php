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
	class WO_Cost {
		public static function add_material($stock_id, $qty, $date_) {
			$m_cost = 0;
			$result = WO::get_bom($stock_id);
			while ($bom_item = DB::fetch($result)) {
				$standard_cost = Item_Price::get_standard_cost($bom_item['component']);
				$m_cost += ($bom_item['quantity'] * $standard_cost);
			}
			$dec = User::price_dec();
			Num::price_decimal($m_cost, $dec);
			$sql = "SELECT material_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$material_cost = $myrow['material_cost'];
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$material_cost = ($qoh * $material_cost + $qty * $m_cost) / ($qoh + $qty);
			}
			$material_cost = Num::round($material_cost, $dec);
			$sql = "UPDATE stock_master SET material_cost=$material_cost
		WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

		public static function add_overhead($stock_id, $qty, $date_, $costs) {
			$dec = User::price_dec();
			Num::price_decimal($costs, $dec);
			if ($qty != 0) {
				$costs /= $qty;
			}
			$sql = "SELECT overhead_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$overhead_cost = $myrow['overhead_cost'];
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$overhead_cost = ($qoh * $overhead_cost + $qty * $costs) / ($qoh + $qty);
			}
			$overhead_cost = Num::round($overhead_cost, $dec);
			$sql = "UPDATE stock_master SET overhead_cost=" . DB::escape($overhead_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

		public static function add_labour($stock_id, $qty, $date_, $costs) {
			$dec = User::price_dec();
			Num::price_decimal($costs, $dec);
			if ($qty != 0) {
				$costs /= $qty;
			}
			$sql = "SELECT labour_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$labour_cost = $myrow['labour_cost'];
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$labour_cost = ($qoh * $labour_cost + $qty * $costs) / ($qoh + $qty);
			}
			$labour_cost = Num::round($labour_cost, $dec);
			$sql = "UPDATE stock_master SET labour_cost=" . DB::escape($labour_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

		public static function add_issue($stock_id, $qty, $date_, $costs) {
			if ($qty != 0) {
				$costs /= $qty;
			}
			$sql = "SELECT material_cost FROM stock_master WHERE stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql);
			$myrow = DB::fetch($result);
			$material_cost = $myrow['material_cost'];
			$dec = User::price_dec();
			Num::price_decimal($material_cost, $dec);
			$qoh = Item::get_qoh_on_date($stock_id, null, $date_);
			if ($qoh < 0) {
				$qoh = 0;
			}
			if ($qoh + $qty != 0) {
				$material_cost = ($qty * $costs) / ($qoh + $qty);
			}
			$material_cost = Num::round($material_cost, $dec);
			$sql = "UPDATE stock_master SET material_cost=material_cost+" . DB::escape($material_cost) . " WHERE stock_id=" . DB::escape($stock_id);
			DB::query($sql, "The cost details for the inventory item could not be updated");
		}

	}

?>