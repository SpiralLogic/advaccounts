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
	//-----------------------------------------------------------------------------
	//	Add or update Sales Invoice
	//
	function write_sales_invoice(&$invoice) {

		$trans_no = $invoice->trans_no;
		if (is_array($trans_no))
			$trans_no = key($trans_no);

		$date_ = $invoice->document_date;
		$charge_shipping = $invoice->freight_cost;

		DBOld::begin_transaction();

		$company_data = DB_Company::get_prefs();

		$branch_data = get_branch_accounts($invoice->Branch);

		$customer = get_customer($invoice->customer_id);

		// offer price values without freight costs
		$items_total = $invoice->get_items_total_dispatch();
		$freight_tax = $invoice->get_shipping_tax();

		$delivery_no = $invoice->src_docs;
		if (is_array($delivery_no))
			$delivery_no = 0;

		update_customer_trans_version(get_parent_type(ST_SALESINVOICE), $invoice->src_docs);

		$ov_gst = 0;
		$taxes = $invoice->get_taxes(); // all taxes with freight_tax

		foreach ($taxes as $taxitem) {
			$taxitem['Value'] = round2($taxitem['Value'], user_price_dec());
			$ov_gst += $taxitem['Value'];
		}

		if ($invoice->tax_included == 0) {
			$items_added_tax = $ov_gst - $freight_tax;
			$freight_added_tax = $freight_tax;
		}
		else {
			$items_added_tax = 0;
			$freight_added_tax = 0;
		}
		// 2006-06-14. If the Customer Branch AR Account is set to a Bank Account,
		// the transaction will be settled at once.
		if (Banking::is_bank_account($branch_data['receivables_account']))
			$alloc = $items_total + $items_added_tax + $invoice->freight_cost + $freight_added_tax;
		else
			$alloc = 0;

		/* Insert/update the debtor_trans */
		$sales_order = $invoice->order_no;
		if (is_array($sales_order))
			$sales_order = $sales_order[0]; // assume all crucial SO data are same for every delivery

		$invoice_no = write_customer_trans(ST_SALESINVOICE, $trans_no, $invoice->customer_id,
			$invoice->Branch, $date_, $invoice->reference, $items_total, 0,
			$items_added_tax, $invoice->freight_cost, $freight_added_tax,
			$invoice->sales_type, $sales_order, $delivery_no,
			$invoice->ship_via, $invoice->due_date, $alloc, 0, $invoice->dimension_id, $invoice->dimension2_id);
		// 2008-06-14 extra $alloc, 2008-11-12 added dimension_id Joe Hunt

		if ($trans_no == 0) {
			$invoice->trans_no = array($invoice_no => 0);
			set_document_parent($invoice);
		}
		else {
			DB_Comments::delete(ST_SALESINVOICE, $invoice_no);
			void_gl_trans(ST_SALESINVOICE, $invoice_no, true);
			void_cust_allocations(ST_SALESINVOICE, $invoice_no); // ?
			void_trans_tax_details(ST_SALESINVOICE, $invoice_no);
		}
		$total = 0;
		foreach ($invoice->line_items as $line_no => $invoice_line) {

			$line_taxfree_price = get_tax_free_price_for_item($invoice_line->stock_id,
				$invoice_line->price, 0, $invoice->tax_included,
				$invoice->tax_group_array);

			$line_tax = get_full_price_for_item($invoice_line->stock_id,
				$invoice_line->price, 0, $invoice->tax_included,
				$invoice->tax_group_array) - $line_taxfree_price;

			write_customer_trans_detail_item(ST_SALESINVOICE, $invoice_no, $invoice_line->stock_id,
				$invoice_line->description, $invoice_line->qty_dispatched,
				$invoice_line->line_price(), $line_tax, $invoice_line->discount_percent,
				$invoice_line->standard_cost,
				$trans_no ? $invoice_line->id : 0);

			// Update delivery items for the quantity invoiced
			if ($invoice_line->qty_old != $invoice_line->qty_dispatched)
				update_parent_line(ST_SALESINVOICE, $invoice_line->src_id, ($invoice_line->qty_dispatched - $invoice_line->qty_old));

			if ($invoice_line->qty_dispatched != 0) {
				$stock_gl_code = get_stock_gl_code($invoice_line->stock_id);

				if ($invoice_line->line_price() != 0) {
					//Post sales transaction to GL credit sales

					// 2008-06-14. If there is a Branch Sales Account, then override with this,
					// else take the Item Sales Account
					$sales_account = (
					$branch_data['sales_account'] != "" ? $branch_data['sales_account'] : $stock_gl_code['sales_account']);
					// 2008-08-01. If there is a Customer Dimension, then override with this,
					// else take the Item Dimension (if any)
					$dim = ($invoice->dimension_id != $customer['dimension_id'] ? $invoice->dimension_id :
					 ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
					$dim2 = ($invoice->dimension2_id != $customer['dimension2_id'] ? $invoice->dimension2_id :
					 ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] :
						$stock_gl_code["dimension2_id"]));
					$total += add_gl_trans_customer(ST_SALESINVOICE, $invoice_no, $date_, $sales_account, $dim, $dim2,
						(-$line_taxfree_price * $invoice_line->qty_dispatched),
						$invoice->customer_id, "The sales price GL posting could not be inserted");

					if ($invoice_line->discount_percent != 0) {

						$total += add_gl_trans_customer(ST_SALESINVOICE, $invoice_no, $date_,
							$branch_data["sales_discount_account"], $dim, $dim2,
							($line_taxfree_price * $invoice_line->qty_dispatched * $invoice_line->discount_percent),
							$invoice->customer_id, "The sales discount GL posting could not be inserted");
					} /*end of if discount !=0 */
				}
			} /*quantity dispatched is more than 0 */
		} /*end of delivery_line loop */

		if (($items_total + $charge_shipping) != 0) {
			$total += add_gl_trans_customer(ST_SALESINVOICE, $invoice_no, $date_, $branch_data["receivables_account"], 0, 0,
				($items_total + $charge_shipping + $items_added_tax + $freight_added_tax),
				$invoice->customer_id, "The total debtor GL posting could not be inserted");
		}

		if ($charge_shipping != 0) {
			$total += add_gl_trans_customer(ST_SALESINVOICE, $invoice_no, $date_, $company_data["freight_act"], 0, 0,
				-$invoice->get_tax_free_shipping(), $invoice->customer_id,
				"The freight GL posting could not be inserted");
		}
		// post all taxes
		foreach ($taxes as $taxitem) {

			if ($taxitem['Net'] != 0) {
				$ex_rate = Banking::get_exchange_rate_from_home_currency(Banking::get_customer_currency($invoice->customer_id), $date_);
				add_trans_tax_details(ST_SALESINVOICE, $invoice_no, $taxitem['tax_type_id'],
					$taxitem['rate'], $invoice->tax_included, $taxitem['Value'],
					$taxitem['Net'], $ex_rate, $date_, $invoice->reference);

				$total += add_gl_trans_customer(ST_SALESINVOICE, $invoice_no, $date_, $taxitem['sales_gl_code'], 0, 0,
					(-$taxitem['Value']), $invoice->customer_id,
					"A tax GL posting could not be inserted");
			}
		}

		/*Post a balance post if $total != 0 */
		add_gl_balance(ST_SALESINVOICE, $invoice_no, $date_, -$total, PT_CUSTOMER, $invoice->customer_id);

		DB_Comments::add(10, $invoice_no, $date_, $invoice->Comments);

		if ($trans_no == 0) {
			Refs::save(ST_SALESINVOICE, $invoice_no, $invoice->reference);
		}
		DBOld::commit_transaction();

		return $invoice_no;
	}

	//--------------------------------------------------------------------------------------------------

	function void_sales_invoice($type, $type_no) {
		DBOld::begin_transaction();

		void_bank_trans($type, $type_no, true);
		void_gl_trans($type, $type_no, true);

		// reverse all the changes in parent document(s)
		$items_result = get_customer_trans_details($type, $type_no);

		$deliveries = get_parent_trans($type, $type_no);

		if ($deliveries !== 0) {
			$srcdetails = get_customer_trans_details(get_parent_type($type), $deliveries);
			while ($row = DBOld::fetch($items_result)) {
				$src_line = DBOld::fetch($srcdetails);
				update_parent_line($type, $src_line['id'], -$row['quantity']);
			}
		}
		// clear details after they've been reversed in the sales order
		void_customer_trans_details($type, $type_no);

		void_trans_tax_details($type, $type_no);

		void_cust_allocations($type, $type_no);

		// do this last because other voidings can depend on it - especially voiding
		// DO NOT MOVE THIS ABOVE VOIDING or we can end up with trans with alloc < 0
		void_customer_trans($type, $type_no);

		DBOld::commit_transaction();
	}

?>