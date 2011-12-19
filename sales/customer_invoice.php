<?php

	/* * ********************************************************************
						Copyright (C) Advanced Group PTY LTD
						Released under the terms of the GNU General Public License, GPL,
						as published by the Free Software Foundation, either version 3
						of the License, or (at your option) any later version.
						This program is distributed in the hope that it will be useful,
						but WITHOUT ANY WARRANTY; without even the implied warranty of
						MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
						See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
					* ********************************************************************* */
	//
	//	Entry/Modify Sales Invoice against single delivery
	//	Entry/Modify Batch Sales Invoice against batch of deliveries
	//
	$page_security = 'SA_SALESINVOICE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	$page_title = 'Sales Invoice Complete';
	if (isset($_GET['ModifyInvoice'])) {
		$page_title = sprintf(_("Modifying Sales Invoice # %d."), $_GET['ModifyInvoice']);
		$help_context = "Modifying Sales Invoice";
	}
	elseif (isset($_GET['DeliveryNumber'])) {
		$page_title = _($help_context = "Issue an Invoice for Delivery Note");
	}
	elseif (isset($_GET['BatchInvoice'])) {
		$page_title = _($help_context = "Issue Batch Invoice for Delivery Notes");
	}
	elseif (isset($_GET['ViewInvoice'])) {
		$page_title = sprintf(_("View Sales Invoice # %d."), $_GET['ViewInvoice']);
	}
	Page::start($page_title);
	$order = Orders::session_get() ? : null;
	if (isset($_GET['AddedID'])) {
		$order = new Sales_Order(ST_SALESINVOICE, $_GET['AddedID']);
		$customer = new Debtor($order->customer_id);
		$emails = $customer->getEmailAddresses();
		$invoice_no = $_GET['AddedID'];
		$reference = $order->reference;
		Errors::notice(_("Invoice $reference has been entered."));
		$trans_type = ST_SALESINVOICE;
		Errors::notice(_("Selected deliveries has been processed"), true);
		Display::note(Debtor::trans_view($trans_type, $invoice_no, _("&View This Invoice")), 0, 1);
		Display::note(Reporting::print_doc_link($invoice_no, _("&Print This Invoice"), true, ST_SALESINVOICE));
		Reporting::email_link($invoice_no, _("Email This Invoice"), true, ST_SALESINVOICE, 'EmailLink', null, $emails, 1);
		Display::link_params("/sales/customer_payments.php", _("Apply a customer payment"));
		Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL &Journal Entries for this Invoice")), 1);
		Display::link_params("/sales/inquiry/sales_deliveries_view.php", _("Select Another &Delivery For Invoicing"), "OutstandingOnly=1");
		Page::footer_exit();
	}
	elseif (isset($_GET['UpdatedID'])) {
		$order = new Sales_Order(ST_SALESINVOICE, $_GET['UpdatedID']);
		$customer = new Debtor($order->customer_id);
		$emails = $customer->getEmailAddresses();
		$invoice_no = $_GET['UpdatedID'];
		Errors::notice(sprintf(_('Sales Invoice # %d has been updated.'), $invoice_no));
		Display::note(GL_UI::trans_view(ST_SALESINVOICE, $invoice_no, _("&View This Invoice")));
		echo '<br>';
		Display::note(Reporting::print_doc_link($invoice_no, _("&Print This Invoice"), true, ST_SALESINVOICE));
		Reporting::email_link($invoice_no, _("Email This Invoice"), true, ST_SALESINVOICE, 'EmailLink', null, $emails, 1);
		Display::link_no_params("/sales/inquiry/customer_inquiry.php", _("Select A Different &Invoice to Modify"));
		Page::footer_exit();
	}
	elseif (isset($_GET['RemoveDN'])) {
		for ($line_no = 0; $line_no < count($order->line_items); $line_no++) {
			$line = $order->line_items[$line_no];
			if ($line->src_no == $_GET['RemoveDN']) {
				$line->quantity = $line->qty_done;
				$line->qty_dispatched = 0;
			}
		}
		unset($line);
		// Remove also src_doc delivery note
		$sources = $order->src_docs;
		unset($sources[$_GET['RemoveDN']]);
	}
	if ((isset($_GET['DeliveryNumber']) && ($_GET['DeliveryNumber'] > 0)) || isset($_GET['BatchInvoice'])) {
		if (isset($_GET['BatchInvoice'])) {
			$src = $_SESSION['DeliveryBatch'];
			unset($_SESSION['DeliveryBatch']);
		}
		else {
			$src = array($_GET['DeliveryNumber']);
		}
		/* read in all the selected deliveries into the Items order */
		$order = new Sales_Order(ST_CUSTDELIVERY, $src, true);

		if ($order->count_items() == 0) {
			Display::link_params("/sales/inquiry/sales_deliveries_view.php", _("Select a different delivery to invoice"), "OutstandingOnly=1");
			die("<br><span class='bold'>" . _("There are no delivered items with a quantity left to invoice. There is nothing left to invoice.") . "</span>");
		}
		$order->trans_type = ST_SALESINVOICE;
		$order->src_docs = $order->trans_no;
		$order->trans_no = 0;
		$order->reference = Ref::get_next(ST_SALESINVOICE);
		$order->due_date = Sales_Order::get_invoice_duedate($order->customer_id, $order->document_date);
		copy_from_order($order);
	}
	elseif (isset($_GET['ModifyInvoice']) && $_GET['ModifyInvoice'] > 0) {
		if (Sales_Trans::get_parent(ST_SALESINVOICE, $_GET['ModifyInvoice']) == 0) { // 1.xx compatibility hack
			echo"<div class='center'><br><span class='bold'>" . _("There are no delivery notes for this invoice.<br>
		Most likely this invoice was created in ADV Accounts version prior to 2.0
		and therefore can not be modified.") . "</span></div>";
			Page::footer_exit();
		}
		$order = new Sales_Order(ST_SALESINVOICE, $_GET['ModifyInvoice']);
		$order->start();
		copy_from_order($order);
		if ($order->count_items() == 0) {
			echo "<div class='center'><br><span class='bold'>" . _("All quantities on this invoice has been credited. There is nothing to modify on this invoice") . "</span></div>";
		}
	}
	elseif (isset($_GET['ViewInvoice']) && $_GET['ViewInvoice'] > 0) {
		$order = new Sales_Order(ST_SALESINVOICE, $_GET['ViewInvoice']);
		$order->start();
		copy_from_order($order);
	}
	elseif (!$order && !isset($_GET['order_id'])) {
		/* This page can only be called with a delivery for invoicing or invoice no for edit */
		Errors::error(_("This page can only be opened after delivery selection. Please select delivery to invoicing first."));
		Display::link_no_params("/sales/inquiry/sales_deliveries_view.php", _("Select Delivery to Invoice"));
		Renderer::end_page();
		exit;
	}
	elseif ($order && !check_quantities($order)) {
		Errors::error(_("Selected quantity cannot be less than quantity credited nor more than quantity not invoiced yet."));
	}
	if (isset($_POST['Update'])) {
		Ajax::i()->activate('Items');
	}
	if (isset($_POST['_InvoiceDate_changed'])) {
		$_POST['due_date'] = Sales_Order::get_invoice_duedate($order->customer_id, $_POST['InvoiceDate']);
		Ajax::i()->activate('due_date');
	}
	if (isset($_POST['process_invoice']) && check_data($order)) {
		$newinvoice = $order->trans_no == 0;
		copy_to_order($order);
		if ($newinvoice) {
			Dates::new_doc_date($order->document_date);
		}
		$invoice_no = $order->write();
		$order->finish();
		if ($newinvoice) {
		Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
		}
		else {
		//	Display::meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$invoice_no");
		}
	}
	// find delivery spans for batch invoice display
	$dspans = array();
	$lastdn = '';
	$spanlen = 1;
	for ($line_no = 0; $line_no < count($order->line_items); $line_no++) {
		$line = $order->line_items[$line_no];
		if ($line->quantity == $line->qty_done) {
			continue;
		}
		if ($line->src_no == $lastdn) {
			$spanlen++;
		}
		else {
			if ($lastdn != '') {
				$dspans[] = $spanlen;
				$spanlen = 1;
			}
		}
		$lastdn = $line->src_no;
	}
	$dspans[] = $spanlen;
	$viewing = isset($_GET['ViewInvoice']);
	$is_batch_invoice = count($order->src_docs) > 1;
	$is_edition = $order->trans_type == ST_SALESINVOICE && $order->trans_no != 0;
	start_form();
	hidden('order_id');
	start_table('tablestyle2 width90 pad5');
	start_row();
	label_cells(_("Customer"), $order->customer_name, "class='tableheader2'");
	label_cells(_("Branch"), Sales_Branch::get_name($order->Branch), "class='tableheader2'");
	label_cells(_("Currency"), $order->customer_currency, "class='tableheader2'");
	end_row();
	start_row();
	if ($order->trans_no == 0) {
		ref_cells(_("Reference"), 'ref', '', null, "class='tableheader2'");
	}
	else {
		label_cells(_("Reference"), $order->reference, "class='tableheader2'");
	}
	label_cells(_("Delivery Notes:"), Debtor::trans_view(ST_CUSTDELIVERY, array_keys($order->src_docs)), "class='tableheader2'");
	label_cells(_("Sales Type"), $order->sales_type_name, "class='tableheader2'");
	end_row();
	start_row();
	if (!isset($_POST['ship_via'])) {
		$_POST['ship_via'] = $order->ship_via;
	}
	label_cell(_("Shipping Company"), "class='tableheader2'");
	if (!$viewing || !isset($order->ship_via)) {
		Sales_UI::shippers_cells(null, 'ship_via', $_POST['ship_via']);
	}
	else {
		label_cell($order->ship_via);
	}
	if (!isset($_POST['InvoiceDate']) || !Dates::is_date($_POST['InvoiceDate'])) {
		$_POST['InvoiceDate'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['InvoiceDate'])) {
			$_POST['InvoiceDate'] = Dates::end_fiscalyear();
		}
	}
	if (!$viewing) {
		date_cells(_("Date"), 'InvoiceDate', '', $order->trans_no == 0, 0, 0, 0, "class='tableheader2'", true);
	}
	else {
		label_cell($_POST['InvoiceDate']);
	}
	if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
		$_POST['due_date'] = Sales_Order::get_invoice_duedate($order->customer_id, $_POST['InvoiceDate']);
	}
	if (!$viewing) {
		date_cells(_("Due Date"), 'due_date', '', null, 0, 0, 0, "class='tableheader2'");
	}
	else {
		label_cell($_POST['due_date']);
	}
	end_row();
	end_table();
	$row = Sales_Order::get_customer($order->customer_id);
	if ($row['dissallow_invoices'] == 1) {
		Errors::error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
		end_form();
		Renderer::end_page();
		exit();
	}
	Display::heading(_("Invoice Items"));
	Display::div_start('Items');
	start_table('tablestyle width90');
	$th = array(
		_("Item Code"), _("Item Description"), _("Delivered"), _("Units"), _("Invoiced"), _("This Invoice"), _("Price"),
		_("Tax Type"), _("Discount"), _("Total"));
	if ($is_batch_invoice) {
		$th[] = _("DN");
		$th[] = "";
	}
	if ($is_edition) {
		$th[4] = _("Credited");
	}
	table_header($th);
	$k = 0;
	$has_marked = false;
	$show_qoh = true;
	$dn_line_cnt = 0;
	foreach ($order->line_items as $line_no => $line) {
		if (!$viewing && $line->quantity == $line->qty_done) {
			continue; // this line was fully invoiced
		}
		alt_table_row_color($k);
		Item_UI::status_cell($line->stock_id);
		if (!$viewing) {
			textarea_cells(null, 'Line' . $line_no . 'Desc', $line->description, 30, 3);
		}
		else {
			label_cell($line->description);
		}
		$dec = Item::qty_dec($line->stock_id);
		qty_cell($line->quantity, false, $dec);
		label_cell($line->units);
		qty_cell($line->qty_done, false, $dec);
		if ($is_batch_invoice) {
			// for batch invoices we can only remove whole deliveries
			echo '<td class="right no wrap">';
			hidden('Line' . $line_no, $line->qty_dispatched);
			echo Num::format($line->qty_dispatched, $dec) . '</td>';
		}
		elseif ($viewing) {
			hidden('viewing');
			qty_cell($line->quantity, false, $dec);
		}
		else {
			small_qty_cells(null, 'Line' . $line_no, Item::qty_format($line->qty_dispatched, $line->stock_id, $dec), null, null, $dec);
		}
		$display_discount_percent = Num::percent_format($line->discount_percent * 100) . " %";
		$line_total = ($line->qty_dispatched * $line->price * (1 - $line->discount_percent));
		amount_cell($line->price);
		label_cell($line->tax_type_name);
		label_cell($display_discount_percent, "nowrap class=right");
		amount_cell($line_total);
		if ($is_batch_invoice) {
			if ($dn_line_cnt == 0) {
				$dn_line_cnt = $dspans[0];
				$dspans = array_slice($dspans, 1);
				label_cell($line->src_no, "rowspan=$dn_line_cnt class=oddrow");
				label_cell("<a href='" . $_SERVER['PHP_SELF'] . "?RemoveDN=" . $line->src_no . "'>" . _("Remove") . "</a>", "rowspan=$dn_line_cnt class=oddrow");
			}
			$dn_line_cnt--;
		}
		end_row();
	}
	/* Don't re-calculate freight if some of the order has already been delivered -
					depending on the business logic required this condition may not be required.
					It seems unfair to charge the customer twice for freight if the order
					was not fully delivered the first time ?? */
	if (!isset($_POST['ChargeFreightCost']) || $_POST['ChargeFreightCost'] == "") {
		if ($order->any_already_delivered() == 1) {
			$_POST['ChargeFreightCost'] = Num::price_format(0);
		}
		else {
			$_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
		}
		if (!Validation::is_num('ChargeFreightCost')) {
			$_POST['ChargeFreightCost'] = Num::price_format(0);
		}
	}
	$accumulate_shipping = DB_Company::get_pref('accumulate_shipping');
	if ($is_batch_invoice && $accumulate_shipping) {
		set_delivery_shipping_sum(array_keys($order->src_docs));
	}
	$colspan = 9;
	start_row();
	label_cell(_("Shipping Cost"), "colspan=$colspan class='right'");
	if (!$viewing) {
		small_amount_cells(null, 'ChargeFreightCost', null);
	}
	else {
		amount_cell($order->freight_cost);
	}
	if ($is_batch_invoice) {
		label_cell('', 'colspan=2');
	}
	end_row();
	$inv_items_total = $order->get_items_total_dispatch();
	$display_sub_total = Num::price_format($inv_items_total + Validation::input_num('ChargeFreightCost'));
	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan class='right'", "class=right", $is_batch_invoice ? 2 : 0);
	$taxes = $order->get_taxes(Validation::input_num('ChargeFreightCost'));
	$tax_total = Tax::edit_items($taxes, $colspan, $order->tax_included, $is_batch_invoice ? 2 : 0);
	$display_total = Num::price_format(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
	label_row(_("Invoice Total"), $display_total, "colspan=$colspan class='right'", "class=right", $is_batch_invoice ? 2 : 0);
	end_table(1);
	Display::div_end();
	start_table('tablestyle2');
	textarea_row(_("Memo"), 'Comments', null, 50, 4);
	end_table(1);
	start_table('center red bold');
	label_cell(_("DON'T PRESS THE PROCESS TAX INVOICE BUTTON UNLESS YOU ARE 100% CERTAIN THAT YOU WON'T NEED TO EDIT ANYTHING IN THE FUTURE ON THIS INVOICE"));
	end_table();
	submit_center_first('Update', _("Update"), _('Refresh document page'), true);
	submit_center_last('process_invoice', _("Process Invoice"), _('Check entered data and save document'), 'default');
	start_table('center red bold');
	label_cell(_("DON'T FUCK THIS UP, YOU WON'T BE ABLE TO EDIT ANYTHING AFTER THIS. DON'T MAKE YOURSELF FEEL AND LOOK LIKE A DICK!"), 'center');
	end_table();
	end_form();
	Renderer::end_page();
	/**
	 * @param $order
	 *
	 * @return int
	 */
	function check_quantities($order) {
		$ok = 1;
		foreach ($order->line_items as $line_no => $itm) {
			if (isset($_POST['Line' . $line_no])) {
				if ($order->trans_no) {
					$min = $itm->qty_done;
					$max = $itm->quantity;
				}
				else {
					$min = 0;
					$max = $itm->quantity - $itm->qty_done;
				}
				if ($itm->quantity > 0 && Validation::is_num('Line' . $line_no, $min, $max)) {
					$order->line_items[$line_no]->qty_dispatched = Validation::input_num('Line' . $line_no);
				}
				elseif ($itm->quantity < 0 && Validation::is_num('Line' . $line_no, $max, $min)) {
					$order->line_items[$line_no]->qty_dispatched = Validation::input_num('Line' . $line_no);
				}
				else {
					$ok = 0;
				}
			}
			if (isset($_POST['Line' . $line_no . 'Desc'])) {
				$line_desc = $_POST['Line' . $line_no . 'Desc'];
				if (strlen($line_desc) > 0) {
					$order->line_items[$line_no]->description = $line_desc;
				}
			}
		}
		return $ok;
	}

	/**
	 * @param $delivery_notes
	 */
	function set_delivery_shipping_sum($delivery_notes) {
		$shipping = 0;
		foreach ($delivery_notes as $delivery_num) {
			$myrow = Sales_Trans::get($delivery_num, 13);
			//$branch = Sales_Branch::get($myrow["branch_code"]);
			//$sales_order = Sales_Order::get_header($myrow["order_"]);
			//$shipping += $sales_order['freight_cost'];
			$shipping += $myrow['ov_freight'];
		}
		$_POST['ChargeFreightCost'] = Num::price_format($shipping);
	}

	/**
	 * @param $order
	 */
	function copy_to_order($order) {
		$order->ship_via = $_POST['ship_via'];
		$order->freight_cost = Validation::input_num('ChargeFreightCost');
		$order->document_date = $_POST['InvoiceDate'];
		$order->due_date = $_POST['due_date'];
		$order->Comments = $_POST['Comments'];
		if ($order->trans_no == 0) {
			$order->reference = $_POST['ref'];
		}
	}

	/**
	 * @param $order
	 */
	function copy_from_order($order) {
		$order = Sales_Order::check_edit_conflicts($order);
		if (!isset($_POST['viewing'])) {
			$_POST['ship_via'] = $order->ship_via;
			$_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
			$_POST['InvoiceDate'] = $order->document_date;
			$_POST['due_date'] = $order->due_date;
			$_POST['ref'] = $order->reference;
		}
		$_POST['order_id'] = $order->order_id;
		$_POST['Comments'] = $order->Comments;
		return Orders::session_set($order);
	}

	/**
	 * @param Sales_Order $order
	 *
	 * @return bool
	 */
	function check_data($order) {
		if (!isset($_POST['InvoiceDate']) || !Dates::is_date($_POST['InvoiceDate'])) {
			Errors::error(_("The entered invoice date is invalid."));
			JS::set_focus('InvoiceDate');
			return false;
		}
		if (!Dates::is_date_in_fiscalyear($_POST['InvoiceDate'])) {
			Errors::error(_("The entered invoice date is not in fiscal year."));
			JS::set_focus('InvoiceDate');
			return false;
		}
		if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
			Errors::error(_("The entered invoice due date is invalid."));
			JS::set_focus('due_date');
			return false;
		}
		if ($order->trans_no == 0) {
			if (!Ref::is_valid($_POST['ref'])) {
				Errors::error(_("You must enter a reference."));
				JS::set_focus('ref');
				return false;
			}
			if (!Ref::is_new($_POST['ref'], ST_SALESINVOICE)) {
				$_POST['ref'] = Ref::get_next(ST_SALESINVOICE);
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
			Errors::error(_("There are no item quantities on this invoice."));
			return false;
		}
		if (!check_quantities($order)) {
			Errors::error(_("Selected quantity cannot be less than quantity credited nor more than quantity not invoiced yet."));
			return false;
		}
		return true;
	}

?>
