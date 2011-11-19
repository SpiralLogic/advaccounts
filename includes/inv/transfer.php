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
	//-------------------------------------------------------------------------------------------------------------
	class Inv_Transfer
	{
		public static function add($Items, $location_from, $location_to, $date_, $type, $reference, $memo_)
		{
			DB::begin_transaction();
			$transfer_id = SysTypes::get_next_trans_no(ST_LOCTRANSFER);
			foreach ($Items as $line_item) {
				Inv_Transfer::add_item($transfer_id, $line_item->stock_id, $location_from, $location_to, $date_, $type, $reference, $line_item->quantity);
			}
			DB_Comments::add(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);
			Refs::save(ST_LOCTRANSFER, $transfer_id, $reference);
			DB_AuditTrail::add(ST_LOCTRANSFER, $transfer_id, $date_);
			DB::commit_transaction();
			return $transfer_id;
		}

		//-------------------------------------------------------------------------------------------------------------
		// add 2 stock_moves entries for a stock transfer
		// $date_ is display date (not sql)
		// std_cost is in HOME currency
		// it seems the standard_cost field is not used at all
		public static function add_item($transfer_id, $stock_id, $location_from, $location_to, $date_, $type, $reference, $quantity)
		{
			Inv_Movement::add(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_from, $date_, $reference, -$quantity, 0, $type);
			Inv_Movement::add(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to, $date_, $reference, $quantity, 0, $type);
		}

		//-------------------------------------------------------------------------------------------------------------
		public static function get($trans_no)
		{
			$result = Inv_Transfer::get_items($trans_no);
			if (DB::num_rows($result) < 2) {
				Errors::show_db_error("transfer with less than 2 items : $trans_no", "");
			}
			// this public static function is very bad that it assumes that 1st record and 2nd record contain the
			// from and to locations - if get_stock_moves uses a different ordering than trans_no then
			// it will bomb
			$move1 = DB::fetch($result);
			$move2 = DB::fetch($result);
			// return an array of (From, To)
			if ($move1['qty'] < 0) {
				return array($move1, $move2);
			} else {
				return array($move2, $move1);
			}
		}

		//-------------------------------------------------------------------------------------------------------------
		public static function get_items($trans_no)
		{
			$result = Inv_Movement::get(ST_LOCTRANSFER, $trans_no);
			if (DB::num_rows($result) == 0) {
				return null;
			}
			return $result;
		}

		//-------------------------------------------------------------------------------------------------------------
		public static function void($type_no)
		{
			Inv_Movement::void(ST_LOCTRANSFER, $type_no);
		}

		public static function update_pid($type, $stock_id, $from, $to, $pid, $cost)
		{
			$from = Dates::date2sql($from);
			$to = Dates::date2sql($to);
			$sql = "UPDATE stock_moves SET standard_cost=" . DB::escape($cost) . " WHERE type=" . DB::escape($type) . "	AND stock_id=" . DB::escape($stock_id) . "  AND tran_date>='$from' AND tran_date<='$to'
				AND person_id = " . DB::escape($pid);
			DB::query($sql, "The stock movement standard_cost cannot be updated");
		}
	}