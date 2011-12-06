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

	// insert/update sales delivery
	//
	class Sales_Delivery {
		function add(&$delivery, $bo_policy)

	{
		$trans_no = $delivery->trans_no;
		if (is_array($trans_no)) {
			$trans_no = key($trans_no);
		}
		DB::begin_transaction();
		$customer = Debtor::get($delivery->customer_id);
		$delivery_items_total = $delivery->get_items_total_dispatch();
		$freight_tax = $delivery->get_shipping_tax();
		// mark sales order for concurrency conflicts check
		Sales_Order::update_version($delivery->src_docs);
		$tax_total = 0;
		$taxes = $delivery->get_taxes(); // all taxes with freight_tax
		foreach ($taxes as $taxitem) {
			$taxitem['Value'] = Num::round($taxitem['Value'], User::price_dec());
			$tax_total += $taxitem['Value'];
		}
		/* Insert/update the debtor_trans */
		$delivery_no = Sales_Trans::write(ST_CUSTDELIVERY, $trans_no, $delivery->customer_id,
																			$delivery->Branch, $delivery->document_date, $delivery->reference,
																			$delivery_items_total, 0,
																			$delivery->tax_included ? 0 : $tax_total - $freight_tax,
																			$delivery->freight_cost,
																			$delivery->tax_included ? 0 : $freight_tax,
																			$delivery->sales_type, $delivery->order_no, 0,
																			$delivery->ship_via, $delivery->due_date, 0, 0, $delivery->dimension_id, $delivery->dimension2_id);
		if ($trans_no == 0) {
			$delivery->trans_no = array($delivery_no => 0);
		}
		else {
			GL_Trans::void(ST_CUSTDELIVERY, $delivery_no, true);
			Inv_Movement::void(ST_CUSTDELIVERY, $delivery_no);
			GL_Trans::void_tax_details(ST_CUSTDELIVERY, $delivery_no);
			DB_Comments::delete(ST_CUSTDELIVERY, $delivery_no);
		}
		foreach ($delivery->line_items as $line_no => $delivery_line) {
			$line_price = $delivery_line->line_price();
			$line_taxfree_price = Tax::tax_free_price($delivery_line->stock_id,
																															 $delivery_line->price, 0, $delivery->tax_included,
																															 $delivery->tax_group_array);
			$line_tax = Tax::full_price_for_item($delivery_line->stock_id, $delivery_line->price,
																								 0, $delivery->tax_included, $delivery->tax_group_array) - $line_taxfree_price;
			if ($trans_no != 0) // Inserted 2008-09-25 Joe Hunt
			{
				$delivery_line->standard_cost = Item_Price::get_standard_cost($delivery_line->stock_id);
			}
			/* add delivery details for all lines */
			Debtor_Trans::add(ST_CUSTDELIVERY, $delivery_no, $delivery_line->stock_id,
																			 $delivery_line->description, $delivery_line->qty_dispatched,
																			 $delivery_line->line_price(), $line_tax,
																			 $delivery_line->discount_percent, $delivery_line->standard_cost,
																			 $trans_no ? $delivery_line->id : 0);
			// Now update sales_order_details for the quantity delivered
			if ($delivery_line->qty_old != $delivery_line->qty_dispatched) {
				Sales_Order::update_parent_line(ST_CUSTDELIVERY, $delivery_line->src_id,
													 $delivery_line->qty_dispatched - $delivery_line->qty_old);
			}
			if ($delivery_line->qty_dispatched != 0) {
				Inv_Movement::add_for_debtor(ST_CUSTDELIVERY, $delivery_line->stock_id, $delivery_no,
																$delivery->Location, $delivery->document_date, $delivery->reference,
																-$delivery_line->qty_dispatched, $delivery_line->standard_cost, 1,
																$line_price, $delivery_line->discount_percent);
				$stock_gl_code = Item::get_gl_code($delivery_line->stock_id);
				/* insert gl_trans to credit stock and debit cost of sales at standard cost*/
				if ($delivery_line->standard_cost != 0) {
					/*first the cost of sales entry*/
					// 2008-08-01. If there is a Customer Dimension, then override with this,
					// else take the Item Dimension (if any)
					$dim = ($delivery->dimension_id != $customer['dimension_id']
					 ? $delivery->dimension_id
					 :
					 ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
					$dim2 = ($delivery->dimension2_id != $customer['dimension2_id']
					 ? $delivery->dimension2_id
					 :
					 ($customer['dimension2_id'] != 0
						? $customer["dimension2_id"]
						:
						$stock_gl_code["dimension2_id"]));
					GL_Trans::add_std_cost(ST_CUSTDELIVERY, $delivery_no,
																$delivery->document_date, $stock_gl_code["cogs_account"], $dim, $dim2, "",
																$delivery_line->standard_cost * $delivery_line->qty_dispatched,
																PT_CUSTOMER, $delivery->customer_id,
																"The cost of sales GL posting could not be inserted");
					/*now the stock entry*/
					GL_Trans::add_std_cost(ST_CUSTDELIVERY, $delivery_no, $delivery->document_date,
																$stock_gl_code["inventory_account"], 0, 0, "",
						(-$delivery_line->standard_cost * $delivery_line->qty_dispatched),
																PT_CUSTOMER, $delivery->customer_id,
																"The stock side of the cost of sales GL posting could not be inserted");
				} /* end of if GL and stock integrated and standard cost !=0 */
			} /*quantity dispatched is more than 0 */
		} /*end of order_line loop */
		if ($bo_policy == 0) {
			// if cancelling any remaining quantities
			Sales_Order::close($delivery->order_no);
		}
		// taxes - this is for printing purposes
		foreach ($taxes as $taxitem) {
			if ($taxitem['Net'] != 0) {
				$ex_rate = Bank_Currency::exchange_rate_from_home(Bank_Currency::for_debtor($delivery->customer_id), $delivery->document_date);
				GL_Trans::add_tax_details(ST_CUSTDELIVERY, $delivery_no, $taxitem['tax_type_id'],
															$taxitem['rate'], $delivery->tax_included, $taxitem['Value'],
															$taxitem['Net'], $ex_rate, $delivery->document_date, $delivery->reference);
			}
		}
		DB_Comments::add(ST_CUSTDELIVERY, $delivery_no, $delivery->document_date, $delivery->Comments);
		if ($trans_no == 0) {
			Ref::save(ST_CUSTDELIVERY,  $delivery->reference);
		}
		DB::commit_transaction();
		return $delivery_no;
	}


	function void($type, $type_no)
	{
		DB::begin_transaction();
		GL_Trans::void($type, $type_no, true);
		// reverse all the changes in the sales order
		$items_result = Debtor_Trans::get($type, $type_no);
		$order = Sales_Trans::get_order($type, $type_no);
		if ($order) {
			$order_items = Sales_Order::get_details($order, ST_SALESORDER);
			while ($row = DB::fetch($items_result)) {
				$order_line = DB::fetch($order_items);
				Sales_Order::update_parent_line(ST_CUSTDELIVERY, $order_line['id'], -$row['quantity']);
			}
		}
		// clear details after they've been reversed in the sales order
		Debtor_Trans::void($type, $type_no);
		GL_Trans::void_tax_details($type, $type_no);
		Sales_Allocation::void($type, $type_no);
		// do this last because other voidings can depend on it
		// DO NOT MOVE THIS ABOVE VOIDING or we can end up with trans with alloc < 0
		Sales_Trans::void($type, $type_no);
		DB::commit_transaction();
	}

	}
