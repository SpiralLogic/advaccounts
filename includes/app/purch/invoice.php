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
	class Purch_Invoice
	{
		//--------------------------------------------------------------------------------------------------
		public static function get_supplier_to_trans($supp_trans, $supplier_id)
			{
				$sql = "SELECT suppliers.supp_name, payment_terms.terms, "
				 . "payment_terms.days_before_due,
		payment_terms.day_in_following_month,
		suppliers.tax_group_id, tax_groups.name As tax_group_name
		From suppliers, payment_terms, tax_groups
		WHERE suppliers.tax_group_id = tax_groups.id
		AND suppliers.payment_terms=payment_terms.terms_indicator
		AND suppliers.supplier_id = " . DB::escape($supplier_id);
				$result = DB::query($sql, "The supplier record selected: " . $supplier_id . " cannot be retrieved");
				$myrow = DB::fetch($result);
				$supp_trans->supplier_id = $supplier_id;
				$supp_trans->supplier_name = $myrow['supp_name'];
				$supp_trans->terms_description = $myrow['terms'];
				if ($myrow['days_before_due'] == 0) {
					$supp_trans->terms = "1" . $myrow['day_in_following_month'];
				} else {
					$supp_trans->terms = "0" . $myrow['days_before_due'];
				}
				$supp_trans->tax_description = $myrow['tax_group_name'];
				$supp_trans->tax_group_id = $myrow['tax_group_id'];
				if ($supp_trans->tran_date == "") {
					$supp_trans->tran_date = Dates::Today();
					if (!Dates::is_date_in_fiscalyear($supp_trans->tran_date)) {
						$supp_trans->tran_date = Dates::end_fiscalyear();
					}
				}
				//if ($supp_trans->due_date=="") {
				//	Purch_Trans::get_duedate_from_terms($supp_trans);
				//}
				Purch_Trans::get_duedate_from_terms($supp_trans);
			}

		//--------------------------------------------------------------------------------------------------
		public static function update_supplier_received($id, $po_detail_item, $qty_invoiced, $chg_price = null)
			{
				if ($chg_price != null) {
					$sql
					 = "SELECT act_price, unit_price FROM purch_order_details WHERE
			po_detail_item = " . DB::escape($po_detail_item);
					$result = DB::query($sql, "The old actual price of the purchase order line could not be retrieved");
					$row = DB::fetch_row($result);
					$ret = $row[0];
					$unit_price = $row[1]; //Added by Rasmus
					$sql
					 = "SELECT delivery_date FROM grn_batch,grn_items WHERE
			grn_batch.id = grn_items.grn_batch_id AND "
					 . "grn_items.id=" . DB::escape($id);
					$result = DB::query($sql, "The old delivery date from the received record cout not be retrieved");
					$row = DB::fetch_row($result);
					$date = $row[0];
				} else {
					$ret = 0;
					$date = "";
					$unit_price = 0; // Added by Rasmus
				}
				$sql
				 = "UPDATE purch_order_details
		SET qty_invoiced = qty_invoiced + " . DB::escape($qty_invoiced);
				if ($chg_price != null) {
					$sql .= " , act_price = " . DB::escape($chg_price);
				}
				$sql .= " WHERE po_detail_item = " . DB::escape($po_detail_item);
				DB::query($sql, "The quantity invoiced of the purchase order line could not be updated");
				$sql
				 = "UPDATE grn_items
        SET quantity_inv = quantity_inv + " . DB::escape($qty_invoiced) . "
        WHERE id = " . DB::escape($id);
				DB::query($sql, "The quantity invoiced off the items received record could not be updated");
				return array($ret, $date, $unit_price);
			}

		public static function get_diff_in_home_currency($supplier, $old_date, $date, $amount1, $amount2)
			{
				$dec = User::price_dec();
				Num::price_decimal($amount2, $dec);
				$currency = Banking::get_supplier_currency($supplier);
				$ex_rate = Banking::get_exchange_rate_to_home_currency($currency, $old_date);
				$amount1 = $amount1 / $ex_rate;
				$ex_rate = Banking::get_exchange_rate_to_home_currency($currency, $date);
				$amount2 = $amount2 / $ex_rate;
				$diff = $amount2 - $amount1;
				return Num::round($diff, $dec);
			}

		//----------------------------------------------------------------------------------------
		public static function add($supp_trans, $invoice_no = 0) // do not receive as ref because we change locally
			{
				//$company_currency = Banking::get_company_currency();
				/*Start an sql transaction */
				DB::begin_transaction();
				$tax_total = 0;
				$taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
				;
				foreach (
					$taxes as $taxitem
				)
				{
					$taxitem['Value'] = Num::round($taxitem['Value'], User::price_dec());
					$tax_total += $taxitem['Value'];
				}
				$invoice_items_total = $supp_trans->get_total_charged($supp_trans->tax_group_id);
				$tax_total;
				if ($supp_trans->is_invoice) {
					$trans_type = ST_SUPPINVOICE;
				} else {
					$trans_type = ST_SUPPCREDIT;
					// let's negate everything because it's a credit note
					$invoice_items_total = -$invoice_items_total;
					$tax_total = -$tax_total;
					$supp_trans->ov_discount = -$supp_trans->ov_discount; // this isn't used at all...
				}
				$date_ = $supp_trans->tran_date;
				$ex_rate = Banking::get_exchange_rate_from_home_currency(
					Banking::get_supplier_currency($supp_trans->supplier_id),
					$date_
				);
				/*First insert the invoice into the supp_trans table*/
				$invoice_id = Purch_Trans::add(
					$trans_type, $supp_trans->supplier_id, $date_, $supp_trans->due_date,
					$supp_trans->reference, $supp_trans->supp_reference,
					$invoice_items_total, $tax_total, $supp_trans->ov_discount
				);
				$total = 0;
				/* Now the control account */
				$supplier_accounts = Purch_Creditor::get_accounts_name($supp_trans->supplier_id);
				$total += Purch_Trans::add_gl(
					$trans_type, $invoice_id, $date_, $supplier_accounts["payable_account"], 0, 0,
					-($invoice_items_total + $tax_total + $supp_trans->ov_discount),
					$supp_trans->supplier_id,
					"The general ledger transaction for the control total could not be added"
				);
				/*Loop through the GL Entries and create a debit posting for each of the accounts entered */
				/*the postings here are a little tricky, the logic goes like this:
															 if its a general ledger amount it goes straight to the account specified

															 if its a GRN amount invoiced then :

															 The cost as originally credited to GRN suspense on arrival of items is debited to GRN suspense. Any difference
															 between the std cost and the currency cost charged as converted at the ex rate of of the invoice is written off
															 to the purchase price variance account applicable to the item being invoiced.
															 */
				foreach (
					$supp_trans->gl_codes as $entered_gl_code
				)
				{
					/*GL Items are straight forward - just do the debit postings to the GL accounts specified -
																									 the credit is to creditors control act  done later for the total invoice value + tax*/
					if (!$supp_trans->is_invoice) {
						$entered_gl_code->amount = -$entered_gl_code->amount;
					}
					$memo_ = $entered_gl_code->memo_;
					$total += Purch_Trans::add_gl(
						$trans_type, $invoice_id, $date_, $entered_gl_code->gl_code,
						$entered_gl_code->gl_dim, $entered_gl_code->gl_dim2, $entered_gl_code->amount, $supp_trans->supplier_id, "", 0,
						$memo_
					);
					Purch_Line::add_gl_item(
						$trans_type, $invoice_id, $entered_gl_code->gl_code,
						$entered_gl_code->amount, $memo_
					);
					// store tax details if the gl account is a tax account
					if (!$supp_trans->is_invoice) {
						$entered_gl_code->amount = -$entered_gl_code->amount;
					}
					GL_Trans::add_gl_tax_details(
						$entered_gl_code->gl_code,
						$trans_type, $invoice_id, $entered_gl_code->amount,
						$ex_rate, $date_, $supp_trans->supp_reference
					);
				}
				foreach (
					$supp_trans->grn_items as $entered_grn
				)
				{
					if (!$supp_trans->is_invoice) {
						$entered_grn->this_quantity_inv = -$entered_grn->this_quantity_inv;
						Purch_GRN::set_item_credited($entered_grn, $supp_trans->supplier_id, $invoice_id, $date_);
					}
					$line_taxfree = $entered_grn->taxfree_charge_price($supp_trans->tax_group_id);
					$line_tax = $entered_grn->full_charge_price($supp_trans->tax_group_id) - $line_taxfree;
					$stock_gl_code = Item::get_gl_code($entered_grn->item_code);
					$iv_act = (Item::is_inventory_item($entered_grn->item_code)
					 ? $stock_gl_code["inventory_account"]
					 :
					 $stock_gl_code["cogs_account"]);
					$total += Purch_Trans::add_gl(
						$trans_type, $invoice_id, $date_, $iv_act,
						$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'],
					 $entered_grn->this_quantity_inv * $line_taxfree, $supp_trans->supplier_id
					);
					// -------------- if price changed since po received. 16 Aug 2008 Joe Hunt
					if ($supp_trans->is_invoice) {
						$old = static::update_supplier_received(
							$entered_grn->id, $entered_grn->po_detail_item,
							$entered_grn->this_quantity_inv, $entered_grn->chg_price
						);
						// Since the standard cost is always calculated on basis of the po unit_price,
						// this is also the price that should be the base of calculating the price diff.
						// In cases where there is two different po invoices on the same delivery with different unit prices this will not work either
						//$old_price = $old[0];
						$old_price = $old[2];
						/*
																									 If statement is removed. Should always check for deliveries nomatter if there has been a price change.
																									 */
						//if ($old_price != $entered_grn->chg_price) // price-change, so update
						//{
						//$diff = $entered_grn->chg_price - $old_price;
						$old_date = Dates::sql2date($old[1]);
						$diff = static::get_diff_in_home_currency(
							$supp_trans->supplier_id, $old_date, $date_, $old_price,
							$entered_grn->chg_price
						);
						// always return due to change in currency.
						$mat_cost = Purch_GRN::update_average_material_cost(
							null, $entered_grn->item_code,
							$diff, $entered_grn->this_quantity_inv, $old_date, true
						);
						// added 2008-12-08 Joe Hunt. Update the purchase data table
						$current_purchase_price = Item_Price::getPriceBySupplier(
							Item::getStockID($entered_grn->item_code),
							$supp_trans->supplier_id
						)->price;
						if ($current_purchase_price == 0 || $current_purchase_price > $entered_grn->chg_price) {
							Purch_Order::add_or_update_data($supp_trans->supplier_id, $entered_grn->item_code, $entered_grn->chg_price);
						}
						$deliveries = Item::get_deliveries_between(
							$entered_grn->item_code, $old_date,
							Dates::Today()
						); // extend the period, if invoice is before any deliveries.
						if ($deliveries[0] != 0) // have deliveries been done during the period?
						{
							$deliveries[1] /= $deliveries[0];
							$amt = ($mat_cost - $deliveries[1]) * $deliveries[0]; // $amt in home currency
							if ($amt != 0.0) {
								GL_Trans::add(
									$trans_type, $invoice_id, $date_, $stock_gl_code["cogs_account"],
									$stock_gl_code['dimension_id'], $stock_gl_code['dimension2_id'], _("Cost diff."),
									$amt, null, null, null,
									"The general ledger transaction could not be added for the price variance of the inventory item"
								);
								GL_Trans::add(
									$trans_type, $invoice_id, $date_, $iv_act,
									0, 0, _("Cost diff."), -$amt, null, null, null,
									"The general ledger transaction could not be added for the price variance of the inventory item"
								);
							}
							Inv_Transfer::update_pid(ST_CUSTDELIVERY, $entered_grn->item_code, $old_date, $date_, 0, $mat_cost);
						}
						Inv_Transfer::update_pid(
							ST_SUPPRECEIVE, $entered_grn->item_code, $old_date, $old_date, $supp_trans->supplier_id,
							$mat_cost
						);
						//}
					}
					// ----------------------------------------------------------------------
					Purch_Line::add_item(
						$trans_type, $invoice_id, $entered_grn->item_code,
						$entered_grn->description, 0, $line_taxfree, $line_tax,
						$entered_grn->this_quantity_inv, $entered_grn->id, $entered_grn->po_detail_item, "", "", $entered_grn->discount,
						$entered_grn->exp_price
					);
				} /* end of GRN postings */
				/* Now the TAX account */
				$taxes = $supp_trans->get_taxes(
					$supp_trans->tax_group_id, 0,
					false
				); // 2009.08-18 Joe Hunt. We have already got the gl lines
				foreach (
					$taxes as $taxitem
				)
				{
					if ($taxitem['Net'] != 0) {
						if (!$supp_trans->is_invoice) {
							$taxitem['Net'] = -$taxitem['Net'];
							$taxitem['Value'] = -$taxitem['Value'];
						}
						// here we suppose that tax is never included in price (we are company customer).
						GL_Trans::add_tax_details(
							$trans_type, $invoice_id,
							$taxitem['tax_type_id'], $taxitem['rate'], 0, $taxitem['Value'],
							$taxitem['Net'], $ex_rate, $date_, $supp_trans->supp_reference
						);
						if (!$supp_trans->is_invoice) {
							$taxitem['Value'] = -$taxitem['Value'];
						}
						$total += Purch_Trans::add_gl(
							$trans_type, $invoice_id, $date_,
							$taxitem['purchasing_gl_code'], 0, 0, $taxitem['Value'],
							$supp_trans->supplier_id,
							"A general ledger transaction for the tax amount could not be added"
						);
					}
				}
				/*Post a balance post if $total != 0 */
				GL_Trans::add_balance($trans_type, $invoice_id, $date_, -$total, PT_SUPPLIER, $supp_trans->supplier_id);
				DB_Comments::add($trans_type, $invoice_id, $date_, $supp_trans->Comments);
				Refs::save($trans_type, $invoice_id, $supp_trans->reference);
				if ($invoice_no != 0) {
					$invoice_alloc_balance = Purch_Allocation::get_balance(ST_SUPPINVOICE, $invoice_no);
					if ($invoice_alloc_balance > 0) { //the invoice is not already fully allocated
						$trans = Purch_Trans::get($invoice_no, ST_SUPPINVOICE);
						$total = $trans['Total'];
						$allocate_amount = ($invoice_alloc_balance > $total) ? $total : $invoice_alloc_balance;
						/*Now insert the allocation record if > 0 */
						if ($allocate_amount != 0) {
							Purch_Allocation::update(ST_SUPPINVOICE, $invoice_no, $allocate_amount);
							Purch_Allocation::update(ST_SUPPCREDIT, $invoice_id, $allocate_amount); // ***
							Purch_Allocation::add(
								$allocate_amount, ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no,
								$date_
							);
							// Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////
							Banking::exchange_variation(
								ST_SUPPCREDIT, $invoice_id, ST_SUPPINVOICE, $invoice_no, $date_,
								$allocate_amount, PT_SUPPLIER
							);
							///////////////////////////////////////////////////////////////////////////
						}
					}
				}
				DB::commit_transaction();
				return $invoice_id;
			}

		//----------------------------------------------------------------------------------------
		// get all the invoices/credits for a given PO - quite long route to get there !
		public static function get_po_credits($po_number)
			{
				$sql
				 = "SELECT DISTINCT supp_trans.trans_no, supp_trans.type,
		ov_amount+ov_discount+ov_gst AS Total,
		supp_trans.tran_date
		FROM supp_trans, supp_invoice_items, "
				 . "purch_order_details, purch_orders
		WHERE supp_invoice_items.supp_trans_no = supp_trans.trans_no
		AND supp_invoice_items.po_detail_item_id = purch_order_details.po_detail_item
		AND purch_orders.supplier_id = supp_trans.supplier_id
		AND purch_order_details.order_no = " . DB::escape($po_number);
				return DB::query($sql, "The invoices/credits for the po $po_number could not be retreived");
			}

		//----------------------------------------------------------------------------------------
		public static function get($trans_no, $trans_type, $supp_trans)
			{
				$sql
				 = "SELECT supp_trans.*, supp_name FROM supp_trans,suppliers
		WHERE trans_no = " . DB::escape($trans_no) . " AND type = " . DB::escape($trans_type) . "
		AND suppliers.supplier_id=supp_trans.supplier_id";
				$result = DB::query($sql, "Cannot retreive a supplier transaction");
				if (DB::num_rows($result) == 1) {
					$trans_row = DB::fetch($result);
					$supp_trans->supplier_id = $trans_row["supplier_id"];
					$supp_trans->supplier_name = $trans_row["supp_name"];
					$supp_trans->tran_date = Dates::sql2date($trans_row["tran_date"]);
					$supp_trans->due_date = Dates::sql2date($trans_row["due_date"]);
					//$supp_trans->Comments = $trans_row["TransText"];
					$supp_trans->Comments = "";
					$supp_trans->reference = $trans_row["reference"];
					$supp_trans->supp_reference = $trans_row["supp_reference"];
					$supp_trans->ov_amount = $trans_row["ov_amount"];
					$supp_trans->ov_discount = $trans_row["ov_discount"];
					$supp_trans->ov_gst = $trans_row["ov_gst"];
					$id = $trans_row["trans_no"];
					$result = Purch_Line::get_for_invoice($trans_type, $id);
					if (DB::num_rows($result) > 0) {
						while ($details_row = DB::fetch($result))
						{
							if ($details_row["gl_code"] == 0) {
								$supp_trans->add_grn_to_trans(
									$details_row["grn_item_id"], $details_row["po_detail_item_id"], $details_row["stock_id"],
									$details_row["description"], 0, 0, $details_row["quantity"], 0, $details_row["FullUnitPrice"],
									false, 0, 0, $details_row['discount'], $details_row['exp_price']
								);
							} else {
								$supp_trans->add_gl_codes_to_trans(
									$details_row["gl_code"], GL_Account::get_name($details_row["gl_code"]), 0, 0,
									$details_row["FullUnitPrice"], $details_row["memo_"]
								);
							}
						}
					} else {
						return Errors::show_db_error(
							"Invalid supptrans details for supptrans number : $trans_no and type : $trans_type",
							$sql, true
						);
					}
				} else {
					return Errors::show_db_error("Invalid supptrans number : $trans_no and type : $trans_type", $sql, true);
				}
			}

		//----------------------------------------------------------------------------------------
		public static function get_for_item($stock_id, $po_item_id)
			{
				$sql
				 = "SELECT *, tran_date FROM supp_invoice_items, supp_trans
		WHERE supp_trans_type = " . ST_SUPPINVOICE . " AND stock_id = "
				 . DB::escape($stock_id) . " AND po_detail_item_id = " . DB::escape($po_item_id) . "
		AND supp_trans_no = trans_no";
				$result = DB::query($sql, "Cannot retreive supplier transaction detail records");
				return DB::fetch($result);
			}

		public static function void($type, $type_no)
			{
				DB::begin_transaction();
				$trans = Purch_Trans::get($type_no, $type);
				Bank_Trans::void($type, $type_no, true);
				GL_Trans::void($type, $type_no, true);
				Purch_Allocation::void($type, $type_no);
				Purch_Trans::void($type, $type_no);
				$result = Purch_Line::get_for_invoice($type, $type_no);
				// now remove this invoice/credit from any GRNs/POs that it's related to
				if (DB::num_rows($result) > 0) {
					$date_ = Dates::Today();
					while ($details_row = DB::fetch($result))
					{
						if ((int)$details_row["grn_item_id"] > 0) // it can be empty for GL items
						{
							// Changed 2008-10-17 by Joe Hunt to get the avg. material cost updated
							$old = static::update_supplier_received(
								$details_row["grn_item_id"],
								$details_row["po_detail_item_id"], -
								$details_row["quantity"], $details_row["FullUnitPrice"]
							);
							//$diff = $details_row["FullUnitPrice"] - $old[2];
							$old_date = Dates::sql2date($old[1]);
							$batch = Purch_GRN::get_batch_for_item($details_row["grn_item_id"]);
							$grn = Purch_GRN::get_batch($batch);
							if ($type == ST_SUPPCREDIT) // credit note 2009-06-14 Joe Hunt Must restore the po and grn
							{ // We must get the corresponding invoice item to check for price chg.
								$match = static::get_for_item($details_row["stock_id"], $details_row["po_detail_item_id"]);
								if ($match !== false) {
									$mat_cost = Purch_GRN::update_average_material_cost(
										$grn["supplier_id"], $details_row["stock_id"],
										$match["unit_price"], -
										$details_row["quantity"], Dates::sql2date($match['tran_date']),
									 $match['tran_date'] !== $trans['tran_date']
									);
								} else {
									$mat_cost = Purch_GRN::update_average_material_cost(
										$grn["supplier_id"], $details_row["stock_id"],
										$details_row["FullUnitPrice"], -
										$details_row["quantity"], $old_date, $old[1] !== $trans['tran_date']
									);
								}
								$sql
								 = "UPDATE purch_order_details
        				SET quantity_ordered = quantity_ordered + " . -$details_row["quantity"] . ", ";
								if ($match !== false) {
									$sql .= "act_price=" . $match['unit_price'] . ", ";
								}
								$sql .= "quantity_received = quantity_received + " . -$details_row["quantity"] . "
    				    WHERE po_detail_item = " . $details_row["po_detail_item_id"];
								DB::query(
									$sql,
									"a purchase order details record could not be updated. This receipt of goods has not been processed "
								);
								$sql = "UPDATE grn_items SET qty_recd=qty_recd+" . -$details_row["quantity"] . "
						WHERE id=" . $details_row["grn_item_id"];
								DB::query($sql);
							} else {
								$diff = static::get_diff_in_home_currency(
									$grn["supplier_id"], $old_date, Dates::sql2date($trans['tran_date']), $old[2],
									$details_row["FullUnitPrice"]
								);
								// Only adjust the avg for the diff
								$mat_cost = Purch_GRN::update_average_material_cost(
									null, $details_row["stock_id"],
									$diff, -$details_row["quantity"], $old_date, true
								);
							}
							$deliveries = Item::get_deliveries_between($details_row["stock_id"], $old_date, $date_);
							if ($deliveries[0] != 0) // have deliveries been done during the period?
							{
								Inv_Transfer::update_pid(ST_CUSTDELIVERY, $details_row["stock_id"], $old_date, $date_, 0, $mat_cost);
							}
							Inv_Transfer::update_pid(
								ST_SUPPRECEIVE, $details_row["stock_id"], $old_date, $old_date,
								$grn['supplier_id'], $mat_cost
							);
						}
					}
				}
				if ($type == ST_SUPPCREDIT) // void the credits in stock moves
				{
					Inv_Movement::void($type, $type_no);
				}
				Purch_Line::void_for_invoice($type, $type_no);
				GL_Trans::void_tax_details($type, $type_no);
				DB::commit_transaction();
			}

//--------------------------------------------------------------------------------------------------
		public static function copy_from_trans($supp_trans)
			{
				$_POST['Comments'] = $supp_trans->Comments;
				$_POST['tran_date'] = $supp_trans->tran_date;
				$_POST['due_date'] = $supp_trans->due_date;
				$_POST['supp_reference'] = $supp_trans->supp_reference;
				$_POST['reference'] = $supp_trans->reference;
				$_POST['supplier_id'] = $supp_trans->supplier_id;
				$_POST['ChgTax'] = $supp_trans->tax_correction;
			}

		//--------------------------------------------------------------------------------------------------
		public static function copy_to_trans($supp_trans)
			{
				$supp_trans->Comments = Input::post('Comments');
				$supp_trans->tran_date = $_POST['tran_date'];
				$supp_trans->due_date = $_POST['due_date'];
				$supp_trans->supp_reference = $_POST['supp_reference'];
				$supp_trans->reference = $_POST['reference'];
				$supp_trans->ov_amount = 0; /* for starters */
				$supp_trans->tax_correction = Input::post('ChgTax'); /* for starters */
				if (count($supp_trans->grn_items) > 0) {
					foreach (
						$supp_trans->grn_items as $grn
					) {
						$supp_trans->ov_amount += Num::round(
							($grn->this_quantity_inv * $grn->chg_price * (1 - $grn->discount / 100)),
							User::price_dec()
						);
					}
				}
				if (count($supp_trans->gl_codes) > 0) {
					foreach (
						$supp_trans->gl_codes as $gl_line
					) {
						////////// 2009-08-18 Joe Hunt
						if (!Taxes::is_tax_account($gl_line->gl_code)) {
							$supp_trans->ov_amount += $gl_line->amount;
						}
					}
				}
			}

		//--------------------------------------------------------------------------------------------------
		public static function header($supp_trans)
			{
				$Ajax = Ajax::i();
				// if vars have been lost, recopy
				if (!isset($_POST['tran_date'])) {
					Purch_Invoice::copy_from_trans($supp_trans);
				}
				start_outer_table("width=40% " . Config::get('tables_style2'));
				div_start('summary');
				start_table();
				table_header(_("Supplier:"));
				start_row();
				if (isset($_POST['invoice_no'])) {
					$trans = Purch_Trans::get($_POST['invoice_no'], ST_SUPPINVOICE);
					$_POST['supplier_id'] = $trans['supplier_id'];
					$supp = $trans['supplier_name'] . " - " . $trans['SupplierCurrCode'];
					label_cell('', $supp . hidden('supplier_id', $_POST['supplier_id'], false));
				} else {
					if (!isset($_POST['supplier_id']) && Session::i()->supplier_id) {
						$_POST['supplier_id'] = Session::i()->supplier_id;
					}
					echo "<td >";
					supplier_list_cells(null, 'supplier_id', $_POST['supplier_id'], false, true);
				}
				if ($supp_trans->supplier_id != $_POST['supplier_id']) {
					// supplier has changed
					// delete all the order items - drastic but necessary because of
					// change of currency, etc
					$supp_trans->clear_items();
					Purch_Invoice::get_supplier_to_trans($supp_trans, $_POST['supplier_id']);
					Purch_Invoice::copy_from_trans($supp_trans);
				}
				end_row();
				start_row();
				table_header("PO#: ");
				if ($supp_trans->is_invoice) {
					ref_cells(null, 'reference', '', Refs::get_next(ST_SUPPINVOICE));
				} else {
					ref_cells(null, 'reference', '', Refs::get_next(ST_SUPPCREDIT));
				}
				end_row();
				start_row();
				table_header(_("Invoie #:"));
				if ($supp_trans->is_invoice && isset($_POST['invoice_no'])) {
					label_cells(null,
					 $_POST['invoice_no'] . hidden('invoice_no', $_POST['invoice_no'], false) . hidden(
						 'supp_reference',
						 $_POST['invoice_no'], false
					 )
					);
				} else {
					text_cells(null, 'supp_reference', $_POST['supp_reference'], 20, 20);
				}
				end_row();
				start_row();
				table_header(_("Date") . ":");
				date_cells(null, 'tran_date', '', true, 0, 0, 0, "", true);
				if (isset($_POST['_tran_date_changed'])) {
					$Ajax->activate('_ex_rate');
					$supp_trans->tran_date = $_POST['tran_date'];
					end_row();
					start_row();
					Purch_Trans::get_duedate_from_terms($supp_trans);
					$_POST['due_date'] = $supp_trans->due_date;
					$Ajax->activate('due_date');
				}
				end_row();
				start_row();
				table_header(_("Due Date") . ":");
				date_cells(null, 'due_date');
				end_row();
				end_table();
				div_end();
				start_table();
				start_row();
				label_cells(_("Terms:"), $supp_trans->terms_description, 'class="label"');
				$supplier_currency = Banking::get_supplier_currency($supp_trans->supplier_id);
				$company_currency = Banking::get_company_currency();
				GL_ExchangeRate::display($supplier_currency, $company_currency, $_POST['tran_date']);
				Session::i()->supplier_id = $_POST['supplier_id'];
				start_row();
				label_cells(_("Tax Group:"), $supp_trans->tax_description, 'class="label"');
				if ($supplier_currency != $company_currency) {
					label_cells(_("Supplier's Currency:"), "<b>" . $supplier_currency . "</b>", 'class="label"');
					end_row();
					end_row();
				}
				end_table();
				end_outer_table(1);
			}

		//--------------------------------------------------------------------------------------------------
		public static function totals($supp_trans)
			{
				Purch_Invoice::copy_to_trans($supp_trans);
				$dim = DB_Company::get_pref('use_dimension');
				$colspan = ($dim == 2 ? 7 : ($dim == 1 ? 6 : 5));
				start_table(Config::get('tables_style2') . " width=90%");
				label_row(_("Sub-total:"), Num::price_format($supp_trans->ov_amount), "colspan=$colspan align=right", "align=right");
				$taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
				$tax_total = Taxes::edit_items($taxes, $colspan, 0, null, true); // tax_included==0 (we are the company)
				label_cell(_("Total Correction"), "colspan=$colspan align=right width='90%'");
				small_amount_cells(null, 'ChgTotal', Num::price_format(get_post('ChgTotal'), 2));
				$total = $supp_trans->ov_amount + $tax_total + get_post('ChgTotal');
				if ($supp_trans->is_invoice) {
					label_row(
						_("Invoice Total:"), Num::price_format($total), "colspan=$colspan align=right style='font-weight:bold;'",
					 "align=right id='invoiceTotal' data-total=" . $total . " style='font-weight:bold;'"
					);
				} else {
					label_row(
						_("Credit Note Total"), Num::price_format($total),
						"colspan=$colspan align=right style='font-weight:bold;color:red;'",
					 "nowrap align=right id='invoiceTotal' data-total=" . $total . "  style='font-weight:bold;color:red;'"
					);
				}
				end_table(1);
				start_table(Config::get('tables_style2'));
				textarea_row(_("Memo:"), "Comments", null, 50, 3);
				end_table(1);
			}
		//----------------------------------------------------------------------------------------
	}