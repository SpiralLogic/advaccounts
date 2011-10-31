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

	class Items_Cart {
		var $trans_type;
		var $line_items;
		var $gl_items;

		var $order_id;

		var $editing_item, $deleting_item;

		var $from_loc;
		var $to_loc;
		var $tran_date;
		var $transfer_type;
		var $increase;
		var $memo_;
		var $person_id;
		var $branch_id;
		var $reference;

		function __construct($type) {
			$this->trans_type = $type;
			$this->clear_items();
		}

		// --------------- line item functions

		function add_to_cart($line_no, $stock_id, $qty, $standard_cost, $description = null) {

			if (isset($stock_id) && $stock_id != "" && isset($qty)) {
				$this->line_items[$line_no] = new Items_Line($stock_id, $qty,
					$standard_cost, $description);
				return true;
			}
			else
			{
				// shouldn't come here under normal circumstances
				Errors::show_db_error("unexpected - adding an invalid item or null quantity", "", true);
			}

			return false;
		}

		function find_cart_item($stock_id) {
			foreach ($this->line_items as $line_no => $line) {
				if ($line->stock_id == $stock_id)
					return $this->line_items[$line_no];
			}
			return null;
		}

		function update_cart_item($line_no, $qty, $standard_cost) {
			$this->line_items[$line_no]->quantity = $qty;
			$this->line_items[$line_no]->standard_cost = $standard_cost;
		}

		function remove_from_cart($line_no) {
			array_splice($this->line_items, $line_no, 1);
		}

		function count_items() {
			return count($this->line_items);
		}

		function check_qoh($location, $date_, $reverse = false) {
			foreach ($this->line_items as $line_no => $line_item)
			{
				$item_ret = $line_item->check_qoh($location, $date_, $reverse);
				if ($item_ret != null)
					return $line_no;
			}
			return -1;
		}

		// ----------- GL item functions

		function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $reference, $description = null) {
			if (isset($code_id) && $code_id != "" && isset($amount) && isset($dimension_id) && isset($dimension2_id)) {
				$this->gl_items[] = new Items_Gl($code_id, $dimension_id, $dimension2_id, $amount, $reference, $description);
				return true;
			} else {
				// shouldn't come here under normal circumstances
				Errors::show_db_error("unexpected - invalid parameters in add_gl_item($code_id, $dimension_id, $dimension2_id, $amount,...)", "", true);
			}

			return false;
		}

		function update_gl_item($index, $code_id, $dimension_id, $dimension2_id, $amount, $reference, $description = null) {
			$this->gl_items[$index]->code_id = $code_id;
			$this->gl_items[$index]->dimension_id = $dimension_id;
			$this->gl_items[$index]->dimension2_id = $dimension2_id;
			$this->gl_items[$index]->amount = $amount;
			$this->gl_items[$index]->reference = $reference;
			if ($description == null)
				$this->gl_items[$index]->description = get_gl_account_name($code_id);
			else
				$this->gl_items[$index]->description = $description;
		}

		function remove_gl_item($index) {
			array_splice($this->gl_items, $index, 1);
		}

		function count_gl_items() {
			return count($this->gl_items);
		}

		function gl_items_total() {
			$total = 0;
			foreach ($this->gl_items as $gl_item)
			{
				$total += $gl_item->amount;
			}
			return $total;
		}

		function gl_items_total_debit() {
			$total = 0;
			foreach ($this->gl_items as $gl_item)
			{
				if ($gl_item->amount > 0)
					$total += $gl_item->amount;
			}
			return $total;
		}

		function gl_items_total_credit() {
			$total = 0;
			foreach ($this->gl_items as $gl_item)
			{
				if ($gl_item->amount < 0)
					$total += $gl_item->amount;
			}
			return $total;
		}

		// ------------ common functions

		function clear_items() {
			unset($this->line_items);
			$this->line_items = array();

			unset($this->gl_items);
			$this->gl_items = array();
		}
	}

	//--------------------------------------------------------------------------------------------


?>
