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

	function add_stock_transfer($Items, $location_from, $location_to, $date_, $type, $reference, $memo_) {

		begin_transaction();

		$transfer_id = SysTypes::get_next_trans_no(ST_LOCTRANSFER);

		foreach ($Items as $line_item)
		{
			add_stock_transfer_item($transfer_id, $line_item->stock_id, $location_from,
				$location_to, $date_, $type, $reference, $line_item->quantity);
		}

		add_comments(ST_LOCTRANSFER, $transfer_id, $date_, $memo_);

		Refs::save(ST_LOCTRANSFER, $transfer_id, $reference);
		add_audit_trail(ST_LOCTRANSFER, $transfer_id, $date_);

		commit_transaction();

		return $transfer_id;
	}

	//-------------------------------------------------------------------------------------------------------------

	// add 2 stock_moves entries for a stock transfer
	// $date_ is display date (not sql)
	// std_cost is in HOME currency
	// it seems the standard_cost field is not used at all

	function add_stock_transfer_item($transfer_id, $stock_id, $location_from, $location_to,
																	 $date_, $type, $reference, $quantity) {
		add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_from,
			$date_, $reference, -$quantity, 0, $type);

		add_stock_move(ST_LOCTRANSFER, $stock_id, $transfer_id, $location_to,
			$date_, $reference, $quantity, 0, $type);
	}

	//-------------------------------------------------------------------------------------------------------------

	function get_stock_transfer($trans_no) {
		$result = get_stock_transfer_items($trans_no);
		if (db_num_rows($result) < 2) {
			Errors::show_db_error("transfer with less than 2 items : $trans_no", "");
		}

		// this function is very bad that it assumes that 1st record and 2nd record contain the
		// from and to locations - if get_stock_moves uses a different ordering than trans_no then
		// it will bomb
		$move1 = db_fetch($result);
		$move2 = db_fetch($result);

		// return an array of (From, To)
		if ($move1['qty'] < 0)
			return array($move1, $move2);
		else
			return array($move2, $move1);
	}

	//-------------------------------------------------------------------------------------------------------------

	function get_stock_transfer_items($trans_no) {
		$result = get_stock_moves(ST_LOCTRANSFER, $trans_no);

		if (db_num_rows($result) == 0) {
			return null;
		}

		return $result;
	}

	//-------------------------------------------------------------------------------------------------------------

	function void_stock_transfer($type_no) {
		void_stock_move(ST_LOCTRANSFER, $type_no);
	}

	function add_stock_move($type, $stock_id, $trans_no, $location,
													$date_, $reference, $quantity, $std_cost, $person_id = 0, $show_or_hide = 1,
													$price = 0, $discount_percent = 0, $error_msg = "") {
		// do not add a stock move if it's a non-inventory item
		if (!is_inventory_item($stock_id)) {
			return null;
		}

		$date = Dates::date2sql($date_);

		$sql = "INSERT INTO stock_moves (stock_id, trans_no, type, loc_code,
			tran_date, person_id, reference, qty, standard_cost, visible, price,
			discount_percent) VALUES (" . db_escape($stock_id)
		 . ", " . db_escape($trans_no) . ", " . db_escape($type)
		 . ",	" . db_escape($location) . ", '$date', "
		 . db_escape($person_id) . ", " . db_escape($reference) . ", "
		 . db_escape($quantity) . ", " . db_escape($std_cost) . ","
		 . db_escape($show_or_hide) . ", " . db_escape($price) . ", "
		 . db_escape($discount_percent) . ")";

		if ($error_msg == "") {
			$error_msg = "The stock movement record cannot be inserted";
		}

		db_query($sql, $error_msg);

		return db_insert_id();
	}

	function update_stock_move_pid($type, $stock_id, $from, $to, $pid, $cost) {
		$from = Dates::date2sql($from);
		$to = Dates::date2sql($to);
		$sql = "UPDATE stock_moves SET standard_cost=" . db_escape($cost)
		 . " WHERE type=" . db_escape($type)
		 . "	AND stock_id=" . db_escape($stock_id)
		 . "  AND tran_date>='$from' AND tran_date<='$to'
				AND person_id = " . db_escape($pid);
		db_query($sql, "The stock movement standard_cost cannot be updated");
	}

	//--------------------------------------------------------------------------------------------------

	function get_stock_moves($type, $type_no, $visible = false) {
		$sql = "SELECT stock_moves.*, stock_master.description, "
		 . "stock_master.units,locations.location_name,"
		 . "stock_master.material_cost + "
		 . "stock_master.labour_cost + "
		 . "stock_master.overhead_cost AS FixedStandardCost
			FROM stock_moves,locations,stock_master
			WHERE stock_moves.stock_id = stock_master.stock_id
			AND locations.loc_code=stock_moves.loc_code
			AND type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no) . " ORDER BY trans_id";
		if ($visible) {
			$sql .= " AND stock_moves.visible=1";
		}

		return db_query($sql, "Could not get stock moves");
	}

	//--------------------------------------------------------------------------------------------------

	function void_stock_move($type, $type_no) {
		$sql = "UPDATE stock_moves SET qty=0, price=0, discount_percent=0,
			standard_cost=0	WHERE type=" . db_escape($type) . " AND trans_no=" . db_escape($type_no);

		db_query($sql, "Could not void stock moves");
	}

	//--------------------------------------------------------------------------------------------------

?>