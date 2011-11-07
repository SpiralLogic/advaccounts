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
	/* Definition of the purch_order class to hold all the information for a purchase order and delivery
 */
	class Purchase_Order
	{
		public $supplier_id;
		public $supplier_details;
		public $line_items; /*array of objects of class Sales_Line using the product id as the pointer */
		public $curr_code;
		public $requisition_no;
		public $delivery_address;
		public $Comments;
		public $Location;
		public $supplier_name;
		public $orig_order_date;
		public $order_no; /*Only used for modification of existing orders otherwise only established when order committed */
		public $lines_on_order;
		public $freight;
		public $salesman;
		public $reference;

		function __construct()
		{
			/*Constructor function initialises a new purchase order object */
			$this->line_items = array();
			$this->lines_on_order = $this->order_no = $this->supplier_id = 0;
			$this->set_salesman();
		}

		function add_to_order($line_no, $stock_id, $qty, $item_descr, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount)
		{
			if ($qty != 0 && isset($qty)) {
				$this->line_items[$line_no] = new Purchase_Line($line_no, $stock_id, $item_descr, $qty, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount);
				$this->lines_on_order++;
				Return 1;
			}
			Return 0;
		}

		function set_salesman($salesman_code = null)
		{
			if ($salesman_code == null) {
				$salesman_name = $_SESSION['current_user']->name;
				$sql = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::escape($salesman_name);
				$query = DB::query($sql, 'Couldn\'t find current salesman');
				$result = DB::fetch_assoc($query);
				if (!empty($result['salesman_code'])) {
					$salesman_code = $result['salesman_code'];
				}
			}
			if ($salesman_code != null) {
				$this->salesman = $salesman_code;
			}
		}

		function update_order_item($line_no, $qty, $price, $req_del_date, $item_descr = '', $discount = 0)
		{
			$this->line_items[$line_no]->quantity = $qty;
			$this->line_items[$line_no]->price = $price;
			$this->line_items[$line_no]->discount = $discount;
			if (!empty($item_descr)) {
				$this->line_items[$line_no]->description = $item_descr;
			}
			$this->line_items[$line_no]->req_del_date = $req_del_date;
			$this->line_items[$line_no]->price = $price;
		}

		function remove_from_order($line_no)
		{
			$this->line_items[$line_no]->Deleted = true;
		}

		function order_has_items()
		{
			if (count($this->line_items) > 0) {
				foreach ($this->line_items as $ordered_items)
				{
					if ($ordered_items->Deleted == false) {
						return true;
					}
				}
			}
			return false;
		}

		function clear_items()
		{
			unset($this->line_items);
			$this->line_items = array();
			$this->lines_on_order = 0;
			$this->order_no = 0;
		}

		function any_already_received()
		{
			/* Checks if there have been deliveries or invoiced entered against any of the line items */
			if (count($this->line_items) > 0) {
				foreach ($this->line_items as $ordered_items)
				{
					if ($ordered_items->qty_received != 0 || $ordered_items->qty_inv != 0) {
						return 1;
					}
				}
			}
			return 0;
		}

		function some_already_received($line_no)
		{
			/* Checks if there have been deliveries or amounts invoiced against a specific line item */
			if (count($this->line_items) > 0) {
				if ($this->line_items[$line_no]->qty_received != 0
				 || $this->line_items[$line_no]->qty_inv != 0
				) {
					return 1;
				}
			}
			return 0;
		}
	} /* end of class defintion */


