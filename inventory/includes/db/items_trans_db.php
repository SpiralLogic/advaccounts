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

	function stock_cost_update($stock_id, $material_cost, $labour_cost, $overhead_cost,
														 $last_cost) {
		$mb_flag = Manufacturing::get_mb_flag($stock_id);

		if ($_POST['mb_flag'] == STOCK_SERVICE) {
			Errors::show_db_error("Cannot do cost update for Service item : $stock_id", "");
		}

		$update_no = -1;

		DBOld::begin_transaction();

		$sql = "UPDATE stock_master SET material_cost=" . DBOld::escape($material_cost) . ",
		labour_cost=" . DBOld::escape($labour_cost) . ",
		overhead_cost=" . DBOld::escape($overhead_cost) . ",
		last_cost=" . DBOld::escape($last_cost) . "
		WHERE stock_id=" . DBOld::escape($stock_id);
		DBOld::query($sql, "The cost details for the inventory item could not be updated");

		$qoh = get_qoh_on_date($_POST['stock_id']);

		$date_ = Dates::Today();
		if ($qoh > 0) {

			$update_no = SysTypes::get_next_trans_no(ST_COSTUPDATE);
			if (!Dates::is_date_in_fiscalyear($date_))
				$date_ = Dates::end_fiscalyear();

			$stock_gl_code = get_stock_gl_code($stock_id);

			$new_cost = $material_cost + $labour_cost + $overhead_cost;

			$value_of_change = $qoh * ($new_cost - $last_cost);

			$memo_ = "Cost was " . $last_cost . " changed to " . $new_cost . " x quantity on hand of $qoh";
			add_gl_trans_std_cost(ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["adjustment_account"],
				$stock_gl_code["dimension_id"],
				$stock_gl_code["dimension2_id"], $memo_, (-$value_of_change));

			add_gl_trans_std_cost(ST_COSTUPDATE, $update_no, $date_, $stock_gl_code["inventory_account"], 0, 0, $memo_,
				$value_of_change);
		}

		DB_AuditTrail::add(ST_COSTUPDATE, $update_no, $date_);
		DBOld::commit_transaction();

		return $update_no;
	}

	//-------------------------------------------------------------------------------------------------------------

?>