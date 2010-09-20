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

class purch_order 
{

	var $supplier_id;
	
	var $line_items; /*array of objects of class line_details using the product id as the pointer */
	var $curr_code;
	var $requisition_no;
	var $delivery_address;
	var $Comments;
	var $Location;
	var $supplier_name;
	var $orig_order_date;
	var $order_no; /*Only used for modification of existing orders otherwise only established when order committed */
	var $lines_on_order;
	var $freight;
	var $reference;
	
	function purch_order()
	{
		/*Constructor function initialises a new purchase order object */
		$this->line_items = array();
		$this->lines_on_order = $this->order_no = $this->supplier_id = 0;
	}

	function add_to_order($line_no, $stock_id, $qty, $item_descr, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount)
	{
		if ($qty != 0 && isset($qty))
		{
			$this->line_items[$line_no] = new line_details($line_no, $stock_id, $item_descr, $qty, $price, $uom, $req_del_date, $qty_inv, $qty_recd, $discount);
			$this->lines_on_order++;
			Return 1;
		}
		Return 0;
	}

	function update_order_item($line_no, $qty, $price, $req_del_date, $item_descr='',$discount=0)
	{
		$this->line_items[$line_no]->quantity = $qty;
		$this->line_items[$line_no]->price = $price;
        $this->line_items[$line_no]->discount = $discount;
    if (!empty($item_descr))    { $this->line_items[$line_no]->item_description = $item_descr; }
		$this->line_items[$line_no]->req_del_date = $req_del_date;
		$this->line_items[$line_no]->price = $price;
	}

	function remove_from_order($line_no)
	{
		 $this->line_items[$line_no]->Deleted = true;
	}
	
	function order_has_items() 
	{
		if (count($this->line_items) > 0)
		{
		   	foreach ($this->line_items as $ordered_items) 
		   	{
				if ($ordered_items->Deleted == false)
				{
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
		if (count($this->line_items) > 0)
		{
		   	foreach ($this->line_items as $ordered_items) 
		   	{
				if ($ordered_items->qty_received != 0 || $ordered_items->qty_inv != 0)
				{
					return 1;
				}
		   	}
		}
		return 0;
	}

	function some_already_received($line_no)
	{
		/* Checks if there have been deliveries or amounts invoiced against a specific line item */
		if (count($this->line_items) > 0)
		{
		   	if ($this->line_items[$line_no]->qty_received != 0 || 
		   		$this->line_items[$line_no]->qty_inv != 0)
		 	{
				return 1;
		   	}
		}
		return 0;
	}
} /* end of class defintion */

class line_details 
{

	Var $line_no;
	Var $po_detail_rec;
	Var $stock_id;
	Var $item_description;
	Var $quantity;
	Var $price;
	Var $units;
	Var $req_del_date;
	Var $qty_inv;
	Var $qty_received;
    var $discount;
	Var $standard_cost;
	Var $receive_qty;
	Var $Deleted;	
	
	function line_details($line_no, $stock_item, $item_descr, $qty, $prc, $uom, $req_del_date, $qty_inv, $qty_recd, $discount)
	{

		/* Constructor function to add a new LineDetail object with passed params */
		$this->line_no = $line_no;
		$this->stock_id = $stock_item;
		$this->item_description = $item_descr;
		$this->quantity = $qty;
		$this->req_del_date = $req_del_date;
		$this->price = $prc;
		$this->units = $uom;
		$this->qty_received = $qty_recd;
        $this->discount = $discount;
		$this->qty_inv = $qty_inv;
		$this->receive_qty = 0;	/*initialise these last two only */
		$this->standard_cost =0;
		$this->Deleted = false;
	}
}
        class cart {
            var $trans_type; // invoice, order, quotation, delivery note ...
            var $trans_no = array(); // array (num1=>ver1,..) or 0 for new
            var $so_type = 0; // for sales order: simple=0 template=1
            var $cart_id; // used to detect multi-tab edition conflits
            var $line_items; //array of objects of class line_details

            var $src_docs = array(); // array of arrays(num1=>ver1,...) or 0 for no src
            var $src_date; // src document date (for info only)

            var $document_date;
            var $due_date;
            var $salesman;
            var $sales_type; // set to the customer's sales type
            var $sales_type_name; // set to customer's sales type name
            var $tax_included;
            var $customer_currency; // set to the customer's currency
            var $default_discount; // set to the customer's discount %
            var $customer_name;
            var $customer_id;
            var $Branch;
            var $email;

            var $deliver_to;
            var $delivery_address;
            var $phone;

            var $cust_ref;
            var $reference;
            var $Comments;
            var $Location;
            var $location_name;
            var $order_no; // the original order number
            var $trans_link = 0;

            var $ship_via;
            var $freight_cost = 0;

            var $tax_group_id;
            var $tax_group_name;
            var $tax_group_array = null; // saves db queries
            var $price_factor; // ditto for price calculations

            var $pos; // user assigned POS
            var $cash; // cash transaction
            var $cash_account;
            var $account_name;
            var $cash_discount; // not used as of FA 2.1
            var $dimension_id;
            var $dimension2_id;
        }