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
	//--------------------------------------------------------------------------------------------------
	function copy_from_trans($supp_trans)
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
	function copy_to_trans($supp_trans)
	{
		$supp_trans->Comments = $_POST['Comments'];
		$supp_trans->tran_date = $_POST['tran_date'];
		$supp_trans->due_date = $_POST['due_date'];
		$supp_trans->supp_reference = $_POST['supp_reference'];
		$supp_trans->reference = $_POST['reference'];
		$supp_trans->ov_amount = 0; /* for starters */
		$supp_trans->tax_correction = $_POST['ChgTax']; /* for starters */
		if (count($supp_trans->grn_items) > 0) {
			foreach ($supp_trans->grn_items as $grn) {
				$supp_trans->ov_amount += Num::round(($grn->this_quantity_inv * $grn->chg_price * (1 - $grn->discount / 100)), User::price_dec());
			}
		}
		if (count($supp_trans->gl_codes) > 0) {
			foreach ($supp_trans->gl_codes as $gl_line) {
				////////// 2009-08-18 Joe Hunt
				if (!Taxes::is_tax_account($gl_line->gl_code)) {
					$supp_trans->ov_amount += $gl_line->amount;
				}
			}
		}
	}

	//--------------------------------------------------------------------------------------------------
	function invoice_header($supp_trans)
	{
		$Ajax = Ajax::instance();
		// if vars have been lost, recopy
		if (!isset($_POST['tran_date'])) {
			copy_from_trans($supp_trans);
		}
		start_outer_table("width=95% " . Config::get('tables_style2'));
		table_section(1);
		if (isset($_POST['invoice_no'])) {
			$trans = Purch_Trans::get($_POST['invoice_no'], ST_SUPPINVOICE);
			$_POST['supplier_id'] = $trans['supplier_id'];
			$supp = $trans['supplier_name'] . " - " . $trans['SupplierCurrCode'];
			label_row(_("Supplier:"), $supp . hidden('supplier_id', $_POST['supplier_id'], false));
		} else {
			if (!isset($_POST['supplier_id']) && Session::get()->supplier_id) {
				$_POST['supplier_id'] = Session::get()->supplier_id;
			}
			supplier_list_row(_("Supplier:"), 'supplier_id', $_POST['supplier_id'], false, true);
		}
		if ($supp_trans->supplier_id != $_POST['supplier_id']) {
			// supplier has changed
			// delete all the order items - drastic but necessary because of
			// change of currency, etc
			$supp_trans->clear_items();
			Purch_Invoice::get_supplier_to_trans($supp_trans, $_POST['supplier_id']);
			copy_from_trans($supp_trans);
		}
		if ($supp_trans->is_invoice) {
			ref_row(_("Reference:"), 'reference', '', Refs::get_next(ST_SUPPINVOICE));
		} else {
			ref_row(_("Reference:"), 'reference', '', Refs::get_next(ST_SUPPCREDIT));
		}
		if ($supp_trans->is_invoice && isset($_POST['invoice_no'])) {
			label_row(_("Supplier's Ref.:"), $_POST['invoice_no'] . hidden('invoice_no', $_POST['invoice_no'], false) . hidden('supp_reference', $_POST['invoice_no'], false));
		} else {
			text_row(_("Supplier's Ref.:"), 'supp_reference', $_POST['supp_reference'], 20, 20);
		}
		table_section(2, "33%");
		date_row(_("Date") . ":", 'tran_date', '', true, 0, 0, 0, "", true);
		if (isset($_POST['_tran_date_changed'])) {
			$Ajax->activate('_ex_rate');
			$supp_trans->tran_date = $_POST['tran_date'];
			get_duedate_from_terms($supp_trans);
			$_POST['due_date'] = $supp_trans->due_date;
			$Ajax->activate('due_date');
		}
		date_row(_("Due Date") . ":", 'due_date');
		label_row(_("Terms:"), $supp_trans->terms_description);
		table_section(3, "33%");
		Session::get()->supplier_id = $_POST['supplier_id'];
		$supplier_currency = Banking::get_supplier_currency($supp_trans->supplier_id);
		$company_currency = Banking::get_company_currency();
		if ($supplier_currency != $company_currency) {
			label_row(_("Supplier's Currency:"), "<b>" . $supplier_currency . "</b>");
			Display::exchange_rate($supplier_currency, $company_currency, $_POST['tran_date']);
		}
		label_row(_("Tax Group:"), $supp_trans->tax_description);
		end_outer_table(1);
	}

	//--------------------------------------------------------------------------------------------------
	function invoice_totals($supp_trans)
	{
		copy_to_trans($supp_trans);
		$dim = DB_Company::get_pref('use_dimension');
		$colspan = ($dim == 2 ? 7 : ($dim == 1 ? 6 : 5));
		start_table(Config::get('tables_style2') . " width=90%");
		label_row(_("Sub-total:"), Num::price_format($supp_trans->ov_amount), "colspan=$colspan align=right", "align=right");
		$taxes = $supp_trans->get_taxes($supp_trans->tax_group_id);
		$tax_total = Display::edit_tax_items($taxes, $colspan, 0, null, true); // tax_included==0 (we are the company)
		label_cell(_("Total Correction"), "colspan=$colspan align=right width='90%'");
		small_amount_cells(null, 'ChgTotal', Num::price_format(get_post('ChgTotal'), 2));
		$total = $supp_trans->ov_amount + $tax_total + get_post('ChgTotal');
		if ($supp_trans->is_invoice) {
			label_row(_("Invoice Total:"), Num::price_format($total), "colspan=$colspan align=right style='font-weight:bold;'", "align=right id='invoiceTotal' data-total=" . $total . " style='font-weight:bold;'");
		} else {
			label_row(_("Credit Note Total"), Num::price_format($total), "colspan=$colspan align=right style='font-weight:bold;color:red;'", "nowrap align=right id='invoiceTotal' data-total=" . $total . "  style='font-weight:bold;color:red;'");
		}
		end_table(1);
		start_table(Config::get('tables_style2'));
		textarea_row(_("Memo:"), "Comments", null, 50, 3);
		end_table(1);
	}

	//--------------------------------------------------------------------------------------------------
	function display_gl_controls($supp_trans, $k)
	{
		$accs = Purch_Creditor::get_accounts_name($supp_trans->supplier_id);
		$_POST['gl_code'] = $accs['purchase_account'];
		alt_table_row_color($k);
		echo gl_all_accounts_list('gl_code', null, true, true);
		$dim = DB_Company::get_pref('use_dimension');
		if ($dim >= 1) {
			dimensions_list_cells(null, 'dimension_id', null, true, " ", false, 1);
			hidden('dimension_id', 0);
		}
		if ($dim > 1) {
			dimensions_list_cells(null, 'dimension2_id', null, true, " ", false, 2);
			hidden('dimension2_id', 0);
		}
		textarea_cells(null, 'memo_', null, 50, 1);
		amount_cells(null, 'amount');
		submit_cells('AddGLCodeToTrans', _("Add"), "", _('Add GL Line'), true);
		submit_cells('ClearFields', _("Reset"), "", _("Clear all GL entry fields"), true);
		end_row();
	}

	// $mode = 0 none at the moment
	//		 = 1 display on invoice/credit page
	//		 = 2 display on view invoice
	//		 = 3 display on view credit
	function display_gl_items($supp_trans, $mode = 0)
	{
		$Ajax = Ajax::instance();
		// if displaying in form, and no items, exit
		if (($mode == 2 || $mode == 3) && count($supp_trans->gl_codes) == 0) {
			return 0;
		}
		if ($supp_trans->is_invoice) {
			$heading = _("GL Items for this Invoice");
		} else {
			$heading = _("GL Items for this Credit Note");
		}
		start_outer_table(Config::get('tables_style') . "  width=90%");
		if ($mode == 1) {
			$qes = GL_QuickEntry::has(QE_SUPPINV);
			if ($qes !== false) {
				echo "<div style='float:right;'>";
				echo _("Quick Entry:") . "&nbsp;";
				echo quick_entries_list('qid', null, QE_SUPPINV, true);
				$qid = GL_QuickEntry::get(get_post('qid'));
				if (list_updated('qid')) {
					unset($_POST['totamount']); // enable default
					$Ajax->activate('totamount');
				}
				echo "&nbsp;" . $qid['base_desc'] . ":&nbsp;";
				$amount = input_num('totamount', $qid['base_amount']);
				$dec = User::price_dec();
				echo "<input class='amount' type='text' name='totamount' size='7' maxlength='12' dec='$dec' value='$amount'>&nbsp;";
				submit('go', _("Go"), true, false, true);
				echo "</div>";
			}
		}
		Display::heading($heading);
		end_outer_table(0, false);
		div_start('gl_items');
		start_table(Config::get('tables_style') . "  width=90%");
		$dim = DB_Company::get_pref('use_dimension');
		if ($dim == 2) {
			$th = array(_("Account"), _("Name"), _("Dimension") . " 1", _("Dimension") . " 2", _("Memo"), _("Amount"));
		} else {
			if ($dim == 1) {
				$th = array(_("Account"), _("Name"), _("Dimension"), _("Memo"), _("Amount"));
			} else {
				$th = array(_("Account"), _("Name"), _("Memo"), _("Amount"));
			}
		}
		if ($mode == 1) {
			$th[] = "";
			$th[] = "";
		}
		table_header($th);
		$total_gl_value = 0;
		$i = $k = 0;
		if (count($supp_trans->gl_codes) > 0) {
			foreach ($supp_trans->gl_codes as $entered_gl_code) {
				alt_table_row_color($k);
				if ($mode == 3) {
					$entered_gl_code->amount = -$entered_gl_code->amount;
				}
				label_cell($entered_gl_code->gl_code);
				label_cell($entered_gl_code->gl_act_name);
				if ($dim >= 1) {
					label_cell(Dimensions::get_string($entered_gl_code->gl_dim, true));
				}
				if ($dim > 1) {
					label_cell(Dimensions::get_string($entered_gl_code->gl_dim2, true));
				}
				label_cell($entered_gl_code->memo_);
				amount_cell($entered_gl_code->amount, true);
				if ($mode == 1) {
					delete_button_cell("Delete2" . $entered_gl_code->Counter, _("Delete"), _('Remove line from document'));
					label_cell("");
				}
				end_row();
				/////////// 2009-08-18 Joe Hunt
				if ($mode > 1 && !Taxes::is_tax_account($entered_gl_code->gl_code)) {
					$total_gl_value += $entered_gl_code->amount;
				} else {
					$total_gl_value += $entered_gl_code->amount;
				}
				$i++;
				if ($i > 15) {
					$i = 0;
					table_header($th);
				}
			}
		}
		if ($mode == 1) {
			display_gl_controls($supp_trans, $k);
		}
		$colspan = ($dim == 2 ? 5 : ($dim == 1 ? 4 : 3));
		label_row(_("Total"), Num::price_format($total_gl_value), "colspan=" . $colspan . " align=right", "nowrap align=right", ($mode == 1 ? 3 : 0));
		end_table(1);
		div_end();
		return $total_gl_value;
	}

	//--------------//-----------------------------------------------------------------------------------------
	function display_grn_items_for_selection($supp_trans, $k)
	{
		if ($supp_trans->is_invoice) {
			$result = Purch_GRN::get_items(0, $supp_trans->supplier_id, true);
		} else {
			if (isset($_POST['receive_begin']) && isset($_POST['receive_end'])) {
				$result = Purch_GRN::get_items(0, $supp_trans->supplier_id, false, true, 0, $_POST['receive_begin'], $_POST['receive_end']);
			} else {
				if (isset($_POST['invoice_no'])) {
					$result = Purch_GRN::get_items(0, $supp_trans->supplier_id, false, true, $_POST['invoice_no']);
				} else {
					$result = Purch_GRN::get_items(0, $supp_trans->supplier_id, false, true);
				}
			}
		}
		if (DB::num_rows($result) == 0) {
			return false;
		}
		/*Set up a table to show the outstanding GRN items for selection */
		while ($myrow = DB::fetch($result)) {
			$grn_already_on_invoice = false;
			foreach ($supp_trans->grn_items as $entered_grn) {
				if ($entered_grn->id == $myrow["id"]) {
					$grn_already_on_invoice = true;
				}
			}
			if ($grn_already_on_invoice == false) {
				if (!isset($_SESSION['delivery_po']) || $myrow["purch_order_no"] == $_SESSION['delivery_po']) {
					alt_table_row_color($k);
					$n = $myrow["id"];
					label_cell(ui_view::get_trans_view_str(25, $myrow["grn_batch_id"]));
					label_cell($myrow["id"] . hidden('qty_recd' . $n, $myrow["qty_recd"], false) . hidden('item_code' . $n, $myrow["item_code"], false) . hidden('description' . $n, $myrow["description"], false) . hidden('prev_quantity_inv' . $n, $myrow['quantity_inv'], false) . hidden('order_price' . $n,
																																																																																																																																										$myrow['unit_price'],
																																																																																																																																										false) . hidden('std_cost_unit' . $n,
																																																																																																																																																		$myrow['std_cost_unit'],
																																																																																																																																																		false) . hidden('po_detail_item' . $n,
																																																																																																																																																										$myrow['po_detail_item'],
																																																																																																																																																										false));
					label_cell(ui_view::get_trans_view_str(ST_PURCHORDER, $myrow["purch_order_no"]));
					label_cell($myrow["item_code"], "class='stock' data-stock_id='" . $myrow['item_code'] . "'");
					label_cell($myrow["description"]);
					label_cell(Dates::sql2date($myrow["delivery_date"]));
					$dec = Num::qty_dec($myrow["item_code"]);
					qty_cell($myrow["qty_recd"], false, $dec);
					qty_cell($myrow["quantity_inv"], false, $dec);
					if ($supp_trans->is_invoice) {
						qty_cells(null, 'this_quantity_inv' . $n, Num::format($myrow["qty_recd"] - $myrow["quantity_inv"], $dec), null, null, $dec);
					} else {
						qty_cells(null, 'This_QuantityCredited' . $n, Num::format(max($myrow["quantity_inv"], 0), $dec), null, null, $dec);
					}
					$dec2 = 0;
					amount_cells(null, 'ChgPrice' . $n, Num::price_decimal($myrow["unit_price"], $dec2), null, null, $dec2, 'ChgPriceCalc' . $n);
					amount_cells(null, 'ExpPrice' . $n, Num::price_decimal($myrow["unit_price"], $dec2), null, null, $dec2, 'ExpPriceCalc' . $n);
					small_amount_cells(null, 'ChgDiscount' . $n, Num::percent_format($myrow['discount'] * 100), null, null, User::percent_dec());
					amount_cell(Num::price_decimal(($myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]) * (1 - $myrow['discount'])) / $myrow["qty_recd"], $dec2), false, $dec2, 'Ea' . $n);
					if ($supp_trans->is_invoice) {
						amount_cells(null, 'ChgTotal' . $n, Num::price_decimal($myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]) * (1 - $myrow['discount']), $dec2), null, null, $dec2, 'ChgTotalCalc' . $n);
					} else {
						amount_cell(Num::round($myrow["unit_price"] * max($myrow['quantity_inv'], 0) * (1 - $myrow['discount']), User::price_dec()));
					}
					submit_cells('grn_item_id' . $n, _("Add"), '', ($supp_trans->is_invoice ? _("Add to Invoice") : _("Add to Credit Note")), true);
					if ($supp_trans->is_invoice && User::get()->can_access('SA_GRNDELETE')) { // Added 2008-10-18 by Joe Hunt. Special access rights needed.
						submit_cells('void_item_id' . $n, _("Remove"), '', _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!"), true);
						submit_js_confirm('void_item_id' . $n, sprintf(_('You are about to remove all yet non-invoiced items from delivery line #%d. This operation also irreversibly changes related order line. Do you want to continue ?'), $n));
					}
					hyperlink_params_td("/purchases/po_entry_items.php", _("Modify"), "ModifyOrderNumber=" . $myrow["purch_order_no"], ' class="button"') . end_row();
				}
			}
		}
		if (isset($_SESSION['delivery_grn'])) {
			unset($_SESSION['delivery_grn']);
		}
		return true;
	}

	//------------------------------------------------------------------------------------
	// $mode = 0 none at the moment
	//		 = 1 display on invoice/credit page
	//		 = 2 display on view invoice
	//		 = 3 display on view credit
	function display_grn_items($supp_trans, $mode = 0)
	{
		$ret = true;
		// if displaying in form, and no items, exit
		if (($mode == 2 || $mode == 3) && count($supp_trans->grn_items) == 0) {
			return 0;
		}
		start_outer_table("style='border:1px solid #cccccc;' width=90%");
		$heading2 = "";
		if ($mode == 1) {
			if ($supp_trans->is_invoice) {
				$heading = _("Items Received Yet to be Invoiced");
				if (User::get()->can_access('SA_GRNDELETE')) // Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
				{
					$heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
				}
			} else {
				$heading = _("Delivery Item Selected For Adding To A Supplier Credit Note");
			}
		} else {
			if ($supp_trans->is_invoice) {
				$heading = _("Received Items Charged on this Invoice");
			} else {
				$heading = _("Received Items Credited on this Note");
			}
		}
		Display::heading($heading);
		if ($mode == 1) {
			/*	if (!$supp_trans->is_invoice && !isset($_POST['invoice_no'])) {
				echo "</td>";
				date_cells(_("Received between"), 'receive_begin', "", null, -30, 0, 0, "valign=middle");
				date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "valign=middle");
				submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), true);
				echo "<td>";
			}*/
			if ($heading2 != "") {
				Errors::warning($heading2, 0, 0, "class='overduefg'");
			}
			echo "</td><td width=10% align='right'>";
			submit('InvGRNAll', _("Add All Items"), true, false, 'button-large');
			end_outer_table(0, false);
			start_outer_table();
			start_row();
			date_cells(_("Received between"), 'receive_begin', "", null, -30, 0, 0, "valign=middle");
			date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "valign=middle");
			submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), true);
			end_row();
		}
		end_outer_table(0, false);
		div_start('grn_items');
		start_table(Config::get('tables_style') . "  width=90%");
		if ($mode == 1) {
			$th = array(
				_("Delivery"), _("Seq #"), _("P.O."), _("Item"), _("Description"), _("Date"), _("Received"), _("Invoiced"), _("Qty"), _("Price"), _("ExpPrice"), _('Discount %'), _('Ea Price'), _("Total"), "", "", "");
			//      if ($supp_trans->is_invoice && CurrentUser::get()->can_access('SA_GRNDELETE')) // Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
			//         $th[] = "";
			if (!$supp_trans->is_invoice) {
				unset($th[14]);
				$th[8] = _("Qty Yet To Credit");
			}
		} else {
			$th = array(
				_("Delivery"), _("Item"), _("Description"), _("Quantity"), _("Price"), _("Expected Price"), _("Discount %"), _("Line Value"));
		}
		table_header($th);
		$total_grn_value = 0;
		$i = $k = 0;
		if (count($supp_trans->grn_items) > 0) {
			foreach ($supp_trans->grn_items as $entered_grn) {
				alt_table_row_color($k);
				$grn_batch = Purch_GRN::get_batch_for_item($entered_grn->id);
				label_cell(ui_view::get_trans_view_str(ST_SUPPRECEIVE, $grn_batch));
				if ($mode == 1) {
					label_cell($entered_grn->id);
					label_cell(""); // PO
				}
				label_cell($entered_grn->item_code, "class='stock' data-stock_id='{$entered_grn->item_code}'");
				label_cell($entered_grn->description);
				$dec = Num::qty_dec($entered_grn->item_code);
				if ($mode == 1) {
					label_cell("");
					qty_cell($entered_grn->qty_recd, false, $dec);
					qty_cell($entered_grn->prev_quantity_inv, false, $dec);
				}
				qty_cell(abs($entered_grn->this_quantity_inv), true, $dec);
				amount_decimal_cell($entered_grn->chg_price);
				amount_decimal_cell($entered_grn->exp_price);
				percent_cell($entered_grn->discount);
				amount_decimal_cell(Num::round(($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100)) / abs($entered_grn->this_quantity_inv)), User::price_dec());
				amount_cell(Num::round($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100), User::price_dec()));
				if ($mode == 1) {
					if ($supp_trans->is_invoice && User::get()->can_access('SA_GRNDELETE')) {
						label_cell("");
					}
					label_cell(""); // PO
					delete_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
				}
				end_row();
				$total_grn_value += Num::round($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100), User::price_dec());
				$i++;
				if ($i > 15) {
					$i = 0;
					table_header($th);
				}
			}
		}
		if ($mode == 1) {
			$ret = display_grn_items_for_selection($supp_trans, $k);
			$colspan = 13;
		} else {
			$colspan = 7;
		}
		label_row(_("Total"), Num::price_format($total_grn_value), "colspan=$colspan align=right", "nowrap align=right");
		if (!$ret) {
			start_row();
			echo "<td colspan=" . ($colspan + 1) . ">";
			if ($supp_trans->is_invoice) {
				Errors::warning(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
			} else {
				Errors::warning(_("There are no received items for the selected supplier that have been invoiced."));
				Errors::notice(_("Credits can only be applied to invoiced items."), 0, 0);
			}
			echo "</td>";
			end_row();
		}
		end_table(1);
		div_end();
		return $total_grn_value;
	}

	//--------------------------------------------------------------------------------------------------
	function get_duedate_from_terms($supp_trans)
	{
		if (!Dates::is_date($supp_trans->tran_date)) {
			$supp_trans->tran_date = Dates::Today();
		}
		if (substr($supp_trans->terms, 0, 1) == "1") { /*Its a day in the following month when due */
			$supp_trans->due_date = Dates::add_days(Dates::end_month($supp_trans->tran_date), (int)substr($supp_trans->terms, 1));
		} else { /*Use the Days Before Due to add to the invoice date */
			$supp_trans->due_date = Dates::add_days($supp_trans->tran_date, (int)substr($supp_trans->terms, 1));
		}
	}
//--------------------------------------------------------------------------------------------------
