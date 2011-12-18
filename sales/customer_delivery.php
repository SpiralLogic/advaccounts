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
	//
	//	Entry/Modify Delivery Note against Sales Order
	//
	$page_security = 'SA_SALESDELIVERY';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	$page_title = _($help_context = "Deliver Items for a Sales Order");
	if (isset($_GET['ModifyDelivery'])) {
		$page_title = sprintf(_("Modifying Delivery Note # %d."), $_GET['ModifyDelivery']);
		$help_context = "Modifying Delivery Note";
	}
	Page::start($page_title);
	if (isset($_GET['AddedID'])) {
		$dispatch_no = $_GET['AddedID'];
		Errors::notice(sprintf(_("Delivery # %d has been entered."), $dispatch_no));
		Display::note(Debtor::trans_view(ST_CUSTDELIVERY, $dispatch_no, _("&View This Delivery")), 0, 1);
		Display::note(Reporting::print_doc_link($dispatch_no, _("&Print Delivery Note"), true, ST_CUSTDELIVERY));
		Display::note(Reporting::print_doc_link($dispatch_no, _("&Email Delivery Note"), true, ST_CUSTDELIVERY, false, "printlink",
			"", 1), 1, 1);
		Display::note(Reporting::print_doc_link($dispatch_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink",
			"", 0, 1));
		Display::note(Reporting::print_doc_link($dispatch_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink",
			"", 1, 1), 1);
		Display::note(GL_UI::view(13, $dispatch_no, _("View the GL Journal Entries for this Dispatch")), 1);
		Display::link_params("/sales/customer_invoice.php", _("Invoice This Delivery"), "DeliveryNumber=$dispatch_no");
		Display::link_params("/sales/inquiry/sales_orders_view.php", _("Select Another Order For Dispatch"), "OutstandingOnly=1");
		Page::footer_exit();
	} elseif (isset($_GET['UpdatedID'])) {
		$delivery_no = $_GET['UpdatedID'];
		Errors::notice(sprintf(_('Delivery Note # %d has been updated.'), $delivery_no));
		Display::note(GL_UI::trans_view(ST_CUSTDELIVERY, $delivery_no, _("View this delivery")), 0, 1);
		Display::note(Reporting::print_doc_link($delivery_no, _("&Print Delivery Note"), true, ST_CUSTDELIVERY));
		Display::note(Reporting::print_doc_link($delivery_no, _("&Email Delivery Note"), true, ST_CUSTDELIVERY, false, "printlink",
			"", 1), 1, 1);
		Display::note(Reporting::print_doc_link($delivery_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink",
			"", 0, 1));
		Display::note(Reporting::print_doc_link($delivery_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink",
			"", 1, 1), 1);
		Display::link_params("/sales/customer_invoice.php", _("Confirm Delivery and Invoice"), "DeliveryNumber=$delivery_no");
		Display::link_params("/sales/inquiry/sales_deliveries_view.php", _("Select A Different Delivery"), "OutstandingOnly=1");
		Page::footer_exit();
	}
	$order = Orders::session_get()?:null;
	if (isset($_GET['OrderNumber']) && $_GET['OrderNumber'] > 0) {
		$order = new Sales_Order(ST_SALESORDER, $_GET['OrderNumber'], true);
		/*read in all the selected order into the Items order */
		if ($order->count_items() == 0) {
			Display::link_params("/sales/inquiry/sales_orders_view.php", _("Select a different sales order to delivery"),
				"OutstandingOnly=1");
			die ("<br><span class='bold'>" . _("This order has no items. There is nothing to delivery.") . "</span>");
		}
		$order->trans_type = ST_CUSTDELIVERY;
		$order->src_docs = $order->trans_no;
		$order->order_no = key($order->trans_no);
		$order->trans_no = 0;
		$order->reference = Ref::get_next(ST_CUSTDELIVERY);
		$order->document_date = Dates::new_doc_date();
		copy_from_order($order);
	} elseif (isset($_GET['ModifyDelivery']) && $_GET['ModifyDelivery'] > 0) {
		$order = new Sales_Order(ST_CUSTDELIVERY, $_GET['ModifyDelivery']);
		copy_from_order($order);
		if ($order->count_items() == 0) {
			Display::link_params("/sales/inquiry/sales_orders_view.php", _("Select a different delivery"), "OutstandingOnly=1");
			echo "<br><div class='center'><span class='bold'>" . _("This delivery has all items invoiced. There is nothing to modify.") . "</div></span>";
			Page::footer_exit();
		}
	} elseif (!Orders::session_exists($order)) {
		/* This page can only be called with an order number for invoicing*/
		Errors::error(_("This page can only be opened if an order or delivery note has been selected. Please select it first."));
		Display::link_params("/sales/inquiry/sales_orders_view.php", _("Select a Sales Order to Delivery"), "OutstandingOnly=1");
		Renderer::end_page();
		exit;
	} else {
		if (!check_quantities($order)) {
			Errors::error(_("Selected quantity cannot be less than quantity invoiced nor more than quantity	not dispatched on sales order."));
		} elseif (!Validation::is_num('ChargeFreightCost', 0)) {
			Errors::error(_("Freight cost cannot be less than zero"));
			JS::set_focus('ChargeFreightCost');
		}
	}
	function check_data($order) {
		if (!isset($_POST['DispatchDate']) || !Dates::is_date($_POST['DispatchDate'])) {
			Errors::error(_("The entered date of delivery is invalid."));
			JS::set_focus('DispatchDate');
			return false;
		}
		if (!Dates::is_date_in_fiscalyear($_POST['DispatchDate'])) {
			Errors::error(_("The entered date of delivery is not in fiscal year."));
			JS::set_focus('DispatchDate');
			return false;
		}
		if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
			Errors::error(_("The entered dead-line for invoice is invalid."));
			JS::set_focus('due_date');
			return false;
		}
		if ($order->trans_no == 0) {
			if (!Ref::is_valid($_POST['ref'])) {
				Errors::error(_("You must enter a reference."));
				JS::set_focus('ref');
				return false;
			}
			if ($order->trans_no == 0 && !Ref::is_new($_POST['ref'], ST_CUSTDELIVERY)) {
				$_POST['ref'] = Ref::get_next(ST_CUSTDELIVERY);
			}
		}
		if ($_POST['ChargeFreightCost'] == "") {
			$_POST['ChargeFreightCost'] = Num::price_format(0);
		}
		if (!Validation::is_num('ChargeFreightCost', 0)) {
			Errors::error(_("The entered shipping value is not numeric."));
			JS::set_focus('ChargeFreightCost');
			return false;
		}
		if ($order->has_items_dispatch() == 0 && Validation::input_num('ChargeFreightCost') == 0) {
			Errors::error(_("There are no item quantities on this delivery note."));
			return false;
		}
		if (!check_quantities($order)) {
			return false;
		}
		return true;
	}

	function copy_to_order($order) {

		$order->ship_via = $_POST['ship_via'];
		$order->freight_cost = Validation::input_num('ChargeFreightCost');
		$order->document_date = $_POST['DispatchDate'];
		$order->due_date = $_POST['due_date'];
		$order->Location = $_POST['Location'];
		$order->Comments = $_POST['Comments'];
		if ($order->trans_no == 0) {
			$order->reference = $_POST['ref'];
		}
	}

	function copy_from_order($order) {
		$order = Sales_Order::check_edit_conflicts($order);
		$_POST['ship_via'] = $order->ship_via;
		$_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
		$_POST['DispatchDate'] = $order->document_date;
		$_POST['due_date'] = $order->due_date;
		$_POST['Location'] = $order->Location;
		$_POST['Comments'] = $order->Comments;
		$_POST['ref'] = $order->reference;
		$_POST['order_id'] = $order->order_id;
		Orders::session_set($order);
	}

	function check_quantities($order) {
		$ok = 1;
		// Update order delivery quantities/descriptions
		foreach ($order->line_items as $line => $itm) {
			if (isset($_POST['Line' . $line])) {
				if ($order->trans_no) {
					$min = $itm->qty_done;
					$max = $itm->quantity;
				} else {
					$min = 0;
					$max = $itm->quantity - $itm->qty_done;
				}
				if ($itm->quantity > 0 && Validation::is_num('Line' . $line, $min, $max)) {
					$order->line_items[$line]->qty_dispatched = Validation::input_num('Line' . $line);
				} elseif ($itm->quantity < 0 && Validation::is_num('Line' . $line, $max, $min)) {
					$order->line_items[$line]->qty_dispatched = Validation::input_num('Line' . $line);
				} else {
					JS::set_focus('Line' . $line);
					$ok = 0;
				}
			}
			if (isset($_POST['Line' . $line . 'Desc'])) {
				$line_desc = $_POST['Line' . $line . 'Desc'];
				if (strlen($line_desc) > 0) {
					$order->line_items[$line]->description = $line_desc;
				}
			}
		}
		// ...
		//	else
		//	 $order->freight_cost = Validation::input_num('ChargeFreightCost');
		return $ok;
	}

	function check_qoh($order) {
		if (!DB_Company::get_pref('allow_negative_stock')) {
			foreach ($order->line_items as $itm) {
				if ($itm->qty_dispatched && WO::has_stock_holding($itm->mb_flag)) {
					$qoh = Item::get_qoh_on_date($itm->stock_id, $_POST['Location'], $_POST['DispatchDate']);
					if ($itm->qty_dispatched > $qoh) {
						Errors::error(_("The delivery cannot be processed because there is an insufficient quantity for item:") . " " . $itm->stock_id . " - " . $itm->description);
						return false;
					}
				}
			}
		}
		return true;
	}

	if (isset($_POST['process_delivery']) && check_data($order) && check_qoh($order)) {
		$dn = $order;
		if ($_POST['bo_policy']) {
			$bo_policy = 0;
		} else {
			$bo_policy = 1;
		}
		$newdelivery = ($dn->trans_no == 0);
		copy_to_order($order);
		if ($newdelivery) {
			Dates::new_doc_date($dn->document_date);
		}
		$delivery_no = $dn->write($bo_policy);
		$dn->finish();
		if ($newdelivery) {
			Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$delivery_no");
		} else {
			Display::meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$delivery_no");
		}
	}
	if (isset($_POST['Update']) || isset($_POST['_Location_update'])) {
		Ajax::i()->activate('Items');
	}
	start_form();
	hidden('order_id');
	start_table('tablestyle2 width90 pad5');
	echo "<tr><td>"; // outer table
	start_table('tablestyle width100');
	start_row();
	label_cells(_("Customer"), $order->customer_name, "class='label'");
	label_cells(_("Branch"), Sales_Branch::get_name($order->Branch), "class='label'");
	label_cells(_("Currency"), $order->customer_currency, "class='label'");
	end_row();
	start_row();
	//if (!isset($_POST['ref']))
	//	$_POST['ref'] = Ref::get_next(ST_CUSTDELIVERY);
	if ($order->trans_no == 0) {
		ref_cells(_("Reference"), 'ref', '', null, "class='label'");
	} else {
		label_cells(_("Reference"), $order->reference, "class='label'");
	}
	label_cells(_("For Sales Order"), Debtor::trans_view(ST_SALESORDER, $order->order_no),
		"class='tableheader2'");
	label_cells(_("Sales Type"), $order->sales_type_name, "class='label'");
	end_row();
	start_row();
	if (!isset($_POST['Location'])) {
		$_POST['Location'] = $order->Location;
	}
	label_cell(_("Delivery From"), "class='label'");
	Inv_Location::cells(null, 'Location', null, false, true);
	if (!isset($_POST['ship_via'])) {
		$_POST['ship_via'] = $order->ship_via;
	}
	label_cell(_("Shipping Company"), "class='label'");
	Sales_UI::shippers_cells(null, 'ship_via', $_POST['ship_via']);
	// set this up here cuz it's used to calc qoh
	if (!isset($_POST['DispatchDate']) || !Dates::is_date($_POST['DispatchDate'])) {
		$_POST['DispatchDate'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['DispatchDate'])) {
			$_POST['DispatchDate'] = Dates::end_fiscalyear();
		}
	}
	date_cells(_("Date"), 'DispatchDate', '', $order->trans_no == 0, 0, 0, 0, "class='label'");
	end_row();
	end_table();
	echo "</td><td>"; // outer table
	start_table('tablestyle width90');
	if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
		$_POST['due_date'] = Sales_Order::get_invoice_duedate($order->customer_id, $_POST['DispatchDate']);
	}
	start_row();
	date_cells(_("Invoice Dead-line"), 'due_date', '', null, 0, 0, 0, "class='label'");
	end_row();
	end_table();
	echo "</td></tr>";
	end_table(1); // outer table
	$row = Sales_Order::get_customer($order->customer_id);
	if ($row['dissallow_invoices'] == 1) {
		Errors::error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
		end_form();
		Renderer::end_page();
		exit();
	}
	Display::heading(_("Delivery Items"));
	Display::div_start('Items');
	start_table('tablestyle width90');
	$new = $order->trans_no == 0;
	$th = array(
		_("Item Code"), _("Item Description"), $new ? _("Ordered") : _("Max. delivery"), _("Units"), $new ? _("Delivered") :
		 _("Invoiced"), _("This Delivery"), _("Price"), _("Tax Type"), _("Discount"), _("Total"));
	table_header($th);
	$k = 0;
	$has_marked = false;
	foreach ($order->line_items as $line => $ln_itm) {
		if ($ln_itm->quantity == $ln_itm->qty_done) {
			continue; //this line is fully delivered
		}
		// if it's a non-stock item (eg. service) don't show qoh
		$show_qoh = true;
		if (DB_Company::get_pref('allow_negative_stock') || !WO::has_stock_holding($ln_itm->mb_flag) || $ln_itm->qty_dispatched == 0
		) {
			$show_qoh = false;
		}
		if ($show_qoh) {
			$qoh = Item::get_qoh_on_date($ln_itm->stock_id, $_POST['Location'], $_POST['DispatchDate']);
		}
		if ($show_qoh && ($ln_itm->qty_dispatched > $qoh)) {
			// oops, we don't have enough of one of the component items
			start_row("class='stockmankobg'");
			$has_marked = true;
		} else {
			alt_table_row_color($k);
		}
		Item_UI::status_cell($ln_itm->stock_id);
		text_cells(null, 'Line' . $line . 'Desc', $ln_itm->description, 30, 50);
		$dec = Item::qty_dec($ln_itm->stock_id);
		qty_cell($ln_itm->quantity, false, $dec);
		label_cell($ln_itm->units);
		qty_cell($ln_itm->qty_done, false, $dec);
		small_qty_cells(null, 'Line' . $line, Item::qty_format($ln_itm->qty_dispatched, $ln_itm->stock_id, $dec), null, null, $dec);
		$display_discount_percent = Num::percent_format($ln_itm->discount_percent * 100) . "%";
		$line_total = ($ln_itm->qty_dispatched * $ln_itm->price * (1 - $ln_itm->discount_percent));
		amount_cell($ln_itm->price);
		label_cell($ln_itm->tax_type_name);
		label_cell($display_discount_percent, "nowrap class=right");
		amount_cell($line_total);
		end_row();
	}
	$_POST['ChargeFreightCost'] = get_post('ChargeFreightCost', Num::price_format($order->freight_cost));
	$colspan = 9;
	start_row();
	label_cell(_("Shipping Cost"), "colspan=$colspan class='right'");
	small_amount_cells(null, 'ChargeFreightCost', $order->freight_cost);
	end_row();
	$inv_items_total = $order->get_items_total_dispatch();
	$display_sub_total = Num::price_format($inv_items_total + Validation::input_num('ChargeFreightCost'));
	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan class='right'", "class=right");
	$taxes = $order->get_taxes(Validation::input_num('ChargeFreightCost'));
	$tax_total = Tax::edit_items($taxes, $colspan, $order->tax_included);
	$display_total = Num::price_format(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
	label_row(_("Amount Total"), $display_total, "colspan=$colspan class='right'", "class=right");
	end_table(1);
	if ($has_marked) {
		Errors::warning(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='red'");
	}
	start_table('tablestyle2');
	Sales_UI::policy_row(_("Action For Balance"), "bo_policy", null);
	textarea_row(_("Memo"), 'Comments', null, 50, 4);
	end_table(1);
	Display::div_end();
	submit_center_first('Update', _("Update"), _('Refresh document page'), true);
	submit_center_last('process_delivery', _("Process Dispatch"), _('Check entered data and save document'), 'default');
	end_form();
	Renderer::end_page();

?>
