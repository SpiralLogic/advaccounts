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
	class Item_Order
	{
		public $trans_type;
		public $line_items;
		public $gl_items;
		public $order_id;
		public $editing_item, $deleting_item;
		public $from_loc;
		public $to_loc;
		public $tran_date;
		public $transfer_type;
		public $increase;
		public $memo_;
		public $person_id;
		public $branch_id;
		public $reference;

		public function __construct($type) {
			$this->trans_type = $type;
			$this->clear_items();
		}

		public function add_to_order($line_no, $stock_id, $qty, $standard_cost, $description = null) {
			if (isset($stock_id) && $stock_id != "" && isset($qty)) {
				$this->line_items[$line_no] = new Item_Line($stock_id, $qty, $standard_cost, $description);
				return true;
			} else {
				// shouldn't come here under normal circumstances
				Errors::show_db_error("unexpected - adding an invalid item or null quantity", "", true);
			}
			return false;
		}

		public function find_order_item($stock_id) {
			foreach ($this->line_items as $line_no => $line) {
				if ($line->stock_id == $stock_id) {
					return $this->line_items[$line_no];
				}
			}
			return null;
		}

		public function update_order_item($line_no, $qty, $standard_cost) {
			$this->line_items[$line_no]->quantity = $qty;
			$this->line_items[$line_no]->standard_cost = $standard_cost;
		}

		public function remove_from_order($line_no) {
			array_splice($this->line_items, $line_no, 1);
		}

		public function count_items() {
			return count($this->line_items);
		}

		public function check_qoh($location, $date_, $reverse = false) {
			foreach ($this->line_items as $line_no => $line_item) {
				$item_ret = $line_item->check_qoh($location, $date_, $reverse);
				if ($item_ret != null) {
					return $line_no;
				}
			}
			return -1;
		}

		public function add_gl_item($code_id, $dimension_id, $dimension2_id, $amount, $reference, $description = null) {
			if (isset($code_id) && $code_id != "" && isset($amount) && isset($dimension_id) && isset($dimension2_id)) {
				$this->gl_items[] = new Item_Gl($code_id, $dimension_id, $dimension2_id, $amount, $reference, $description);
				return true;
			} else {
				// shouldn't come here under normal circumstances
				Errors::show_db_error("unexpected - invalid parameters in add_gl_item($code_id, $dimension_id, $dimension2_id, $amount,...)", "", true);
			}
			return false;
		}

		public function update_gl_item($index, $code_id, $dimension_id, $dimension2_id, $amount, $reference, $description = null) {
			$this->gl_items[$index]->code_id = $code_id;
			$this->gl_items[$index]->dimension_id = $dimension_id;
			$this->gl_items[$index]->dimension2_id = $dimension2_id;
			$this->gl_items[$index]->amount = $amount;
			$this->gl_items[$index]->reference = $reference;
			if ($description == null) {
				$this->gl_items[$index]->description = GL_Account::get_name($code_id);
			} else {
				$this->gl_items[$index]->description = $description;
			}
		}

		public function remove_gl_item($index) {
			array_splice($this->gl_items, $index, 1);
		}

		public function count_gl_items() {
			return count($this->gl_items);
		}

		public function gl_items_total() {
			$total = 0;
			foreach ($this->gl_items as $gl_item) {
				$total += $gl_item->amount;
			}
			return $total;
		}

		public function gl_items_total_debit() {
			$total = 0;
			foreach ($this->gl_items as $gl_item) {
				if ($gl_item->amount > 0) {
					$total += $gl_item->amount;
				}
			}
			return $total;
		}

		public function gl_items_total_credit() {
			$total = 0;
			foreach ($this->gl_items as $gl_item) {
				if ($gl_item->amount < 0) {
					$total += $gl_item->amount;
				}
			}
			return $total;
		}

		public function clear_items() {
			unset($this->line_items);
			$this->line_items = array();
			unset($this->gl_items);
			$this->gl_items = array();
		}

		static public function add_line($order, $new_item, $new_item_qty, $standard_cost) {
			if ($order->find_order_item($new_item)) {
				Errors::error(_("For Part: '") . $new_item . "' This item is already on this order. You can change the quantity ordered of the existing line if necessary.");
			} else {
				$order->add_to_order(count($order->line_items), $new_item, $new_item_qty, $standard_cost);
			}
		}
	}

?>
