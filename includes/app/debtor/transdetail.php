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
	class Debtor_TransDetail
	{
		public static function get($debtor_trans_type, $debtor_trans_no) {
			if (!is_array($debtor_trans_no)) {
				$debtor_trans_no = array(0 => $debtor_trans_no);
			}
			$sql = "SELECT debtor_trans_details.*,
		debtor_trans_details.unit_price+debtor_trans_details.unit_tax AS FullUnitPrice,
		debtor_trans_details.description As StockDescription,
		stock_master.units
		FROM debtor_trans_details,stock_master
		WHERE (";
			$tr = array();
			foreach ($debtor_trans_no as $trans_no) {
				$tr[] = 'debtor_trans_no=' . $trans_no;
			}
			$sql .= implode(' OR ', $tr);
			$sql .= ") AND debtor_trans_type=" . DB::escape($debtor_trans_type) . "
		AND stock_master.stock_id=debtor_trans_details.stock_id
		ORDER BY id";
			return DB::query($sql, "The debtor transaction detail could not be queried");
		}
		public static function void($type, $type_no) {
			$sql = "UPDATE debtor_trans_details SET quantity=0, unit_price=0,
		unit_tax=0, discount_percent=0, standard_cost=0
		WHERE debtor_trans_no=" . DB::escape($type_no) . "
		AND debtor_trans_type=" . DB::escape($type);
			DB::query($sql, "The debtor transaction details could not be voided");
			// clear the stock move items
			Inv_Movement::void($type, $type_no);
		}
		public static function add($debtor_trans_type, $debtor_trans_no, $stock_id, $description, $quantity, $unit_price, $unit_tax, $discount_percent, $std_cost, $line_id = 0) {
			if ($line_id != 0) {
				$sql = "UPDATE debtor_trans_details SET
			stock_id=" . DB::escape($stock_id) . ",
			description=" . DB::escape($description) . ",
			quantity=$quantity,
			unit_price=$unit_price,
			unit_tax=$unit_tax,
			discount_percent=$discount_percent,
			standard_cost=$std_cost WHERE
			id=" . DB::escape($line_id);
			}
			else {
				$sql = "INSERT INTO debtor_trans_details (debtor_trans_no,
				debtor_trans_type, stock_id, description, quantity, unit_price,
				unit_tax, discount_percent, standard_cost)
			VALUES (" . DB::escape($debtor_trans_no) . ", " . DB::escape($debtor_trans_type) . ", " . DB::escape($stock_id) . ", " . DB::escape($description) . ",
				$quantity, $unit_price, $unit_tax, $discount_percent, $std_cost)";
			}
			DB::query($sql, "The debtor transaction detail could not be written");
		}
		// add a debtor-related gl transaction
		// $date_ is display date (non-sql)
		// $amount is in CUSTOMER'S currency
		public static function add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, $amount, $customer_id, $err_msg = "", $rate = 0) {
			if ($err_msg == "") {
				$err_msg = "The customer GL transaction could not be inserted";
			}
			return GL_Trans::add($type, $type_no, $date_, $account, $dimension, $dimension2, "", $amount, Bank_Currency::for_debtor($customer_id), PT_CUSTOMER, $customer_id, $err_msg, $rate);
		}
	}
