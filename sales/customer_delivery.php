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
	//
	//	Entry/Modify Delivery Note against Sales Order
	//
	$page_security = 'SA_SALESDELIVERY';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "sales/includes/sales_ui.php");

	JS::get_js_open_window(900, 500);
	$page_title = _($help_context = "Deliver Items for a Sales Order");
	if (isset($_GET['ModifyDelivery'])) {
		$page_title   = sprintf(_("Modifying Delivery Note # %d."), $_GET['ModifyDelivery']);
		$help_context = "Modifying Delivery Note";
		processing_start();
	}
	elseif (isset($_GET['OrderNumber'])) {
		processing_start();
	}

	Page::start($page_title);

	if (isset($_GET['AddedID'])) {
		$dispatch_no = $_GET['AddedID'];

		ui_msgs::display_notification(sprintf(_("Delivery # %d has been entered."), $dispatch_no));

		ui_msgs::display_note(ui_view::get_customer_trans_view_str(ST_CUSTDELIVERY, $dispatch_no, _("&View This Delivery")), 0, 1);

		ui_msgs::display_note(Reporting::print_doc_link($dispatch_no, _("&Print Delivery Note"), true, ST_CUSTDELIVERY));
		ui_msgs::display_note(Reporting::print_doc_link($dispatch_no, _("&Email Delivery Note"), true, ST_CUSTDELIVERY, false, "printlink", "", 1), 1, 1);
		ui_msgs::display_note(Reporting::print_doc_link($dispatch_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 0, 1));
		ui_msgs::display_note(Reporting::print_doc_link($dispatch_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 1, 1), 1);

		ui_msgs::display_note(ui_view::get_gl_view_str(13, $dispatch_no, _("View the GL Journal Entries for this Dispatch")), 1);

		hyperlink_params("/sales/customer_invoice.php", _("Invoice This Delivery"), "DeliveryNumber=$dispatch_no");

		hyperlink_params("/sales/inquiry/sales_orders_view.php", _("Select Another Order For Dispatch"), "OutstandingOnly=1");

		ui_view::display_footer_exit();
	}
	elseif (isset($_GET['UpdatedID'])) {

		$delivery_no = $_GET['UpdatedID'];

		ui_msgs::display_notification(sprintf(_('Delivery Note # %d has been updated.'), $delivery_no));

		ui_msgs::display_note(ui_view::get_trans_view_str(ST_CUSTDELIVERY, $delivery_no, _("View this delivery")), 0, 1);

		ui_msgs::display_note(Reporting::print_doc_link($delivery_no, _("&Print Delivery Note"), true, ST_CUSTDELIVERY));
		ui_msgs::display_note(Reporting::print_doc_link($delivery_no, _("&Email Delivery Note"), true, ST_CUSTDELIVERY, false, "printlink", "", 1), 1, 1);
		ui_msgs::display_note(Reporting::print_doc_link($delivery_no, _("P&rint as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 0, 1));
		ui_msgs::display_note(Reporting::print_doc_link($delivery_no, _("E&mail as Packing Slip"), true, ST_CUSTDELIVERY, false, "printlink", "", 1, 1), 1);

		hyperlink_params("/sales/customer_invoice.php", _("Confirm Delivery and Invoice"), "DeliveryNumber=$delivery_no");

		hyperlink_params("/sales/inquiry/sales_deliveries_view.php", _("Select A Different Delivery"), "OutstandingOnly=1");

		ui_view::display_footer_exit();
	}
	//-----------------------------------------------------------------------------

	if (isset($_GET['OrderNumber']) && $_GET['OrderNumber'] > 0) {

		$ord = new Sales_Order(ST_SALESORDER, $_GET['OrderNumber'], true);

		/*read in all the selected order into the Items cart  */

		if ($ord->count_items() == 0) {
			hyperlink_params(
				"/sales/inquiry/sales_orders_view.php",
				_("Select a different sales order to delivery"), "OutstandingOnly=1"
			);
			die ("<br><b>" . _("This order has no items. There is nothing to delivery.") . "</b>");
		}

		$ord->trans_type    = ST_CUSTDELIVERY;
		$ord->src_docs      = $ord->trans_no;
		$ord->order_no      = key($ord->trans_no);
		$ord->trans_no      = 0;
		$ord->reference     = Refs::get_next(ST_CUSTDELIVERY);
		$ord->document_date = Dates::new_doc_date();
		$_SESSION['Items']  = $ord;
		copy_from_cart();
	}
	elseif (isset($_GET['ModifyDelivery']) && $_GET['ModifyDelivery'] > 0) {

		$_SESSION['Items'] = new Sales_Order(ST_CUSTDELIVERY, $_GET['ModifyDelivery']);

		if ($_SESSION['Items']->count_items() == 0) {
			hyperlink_params(
				"/sales/inquiry/sales_orders_view.php",
				_("Select a different delivery"), "OutstandingOnly=1"
			);
			echo "<br><center><b>" . _("This delivery has all items invoiced. There is nothing to modify.") .
			 "</center></b>";
			ui_view::display_footer_exit();
		}

		copy_from_cart();
	}
	elseif (!processing_active()) {
		/* This page can only be called with an order number for invoicing*/

		ui_msgs::display_error(_("This page can only be opened if an order or delivery note has been selected. Please select it first."));

		hyperlink_params("/sales/inquiry/sales_orders_view.php", _("Select a Sales Order to Delivery"), "OutstandingOnly=1");

		end_page();
		exit;
	}
	else {
		check_edit_conflicts();

		if (!check_quantities()) {
			ui_msgs::display_error(_("Selected quantity cannot be less than quantity invoiced nor more than quantity	not dispatched on sales order."));
		}
		elseif (!Validation::is_num('ChargeFreightCost', 0)) {
			ui_msgs::display_error(_("Freight cost cannot be less than zero"));
			ui_view::set_focus('ChargeFreightCost');
		}
	}

	//-----------------------------------------------------------------------------

	function check_data()
	{

		if (!isset($_POST['DispatchDate']) || !Dates::is_date($_POST['DispatchDate'])) {
			ui_msgs::display_error(_("The entered date of delivery is invalid."));
			ui_view::set_focus('DispatchDate');
			return false;
		}

		if (!Dates::is_date_in_fiscalyear($_POST['DispatchDate'])) {
			ui_msgs::display_error(_("The entered date of delivery is not in fiscal year."));
			ui_view::set_focus('DispatchDate');
			return false;
		}

		if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
			ui_msgs::display_error(_("The entered dead-line for invoice is invalid."));
			ui_view::set_focus('due_date');
			return false;
		}

		if ($_SESSION['Items']->trans_no == 0) {
			if (!Refs::is_valid($_POST['ref'])) {
				ui_msgs::display_error(_("You must enter a reference."));
				ui_view::set_focus('ref');
				return false;
			}

			if ($_SESSION['Items']->trans_no == 0 && !is_new_reference($_POST['ref'], ST_CUSTDELIVERY)) {
				ui_msgs::display_error(_("The entered reference is already in use."));
				ui_view::set_focus('ref');
				return false;
			}
		}
		if ($_POST['ChargeFreightCost'] == "") {
			$_POST['ChargeFreightCost'] = price_format(0);
		}

		if (!Validation::is_num('ChargeFreightCost', 0)) {
			ui_msgs::display_error(_("The entered shipping value is not numeric."));
			ui_view::set_focus('ChargeFreightCost');
			return false;
		}

		if ($_SESSION['Items']->has_items_dispatch() == 0 && input_num('ChargeFreightCost') == 0) {
			ui_msgs::display_error(_("There are no item quantities on this delivery note."));
			return false;
		}

		if (!check_quantities()) {
			return false;
		}

		return true;
	}

	//------------------------------------------------------------------------------
	function copy_to_cart()
	{
		$cart                = &$_SESSION['Items'];
		$cart->ship_via      = $_POST['ship_via'];
		$cart->freight_cost  = input_num('ChargeFreightCost');
		$cart->document_date = $_POST['DispatchDate'];
		$cart->due_date      = $_POST['due_date'];
		$cart->Location      = $_POST['Location'];
		$cart->Comments      = $_POST['Comments'];
		if ($cart->trans_no == 0) {
			$cart->reference = $_POST['ref'];
		}
	}

	//------------------------------------------------------------------------------

	function copy_from_cart()
	{
		$cart                       = &$_SESSION['Items'];
		$_POST['ship_via']          = $cart->ship_via;
		$_POST['ChargeFreightCost'] = price_format($cart->freight_cost);
		$_POST['DispatchDate']      = $cart->document_date;
		$_POST['due_date']          = $cart->due_date;
		$_POST['Location']          = $cart->Location;
		$_POST['Comments']          = $cart->Comments;
		$_POST['cart_id']           = $cart->cart_id;
		$_POST['ref']               = $cart->reference;
	}

	//------------------------------------------------------------------------------

	function check_quantities()
	{
		$ok = 1;
		// Update cart delivery quantities/descriptions
		foreach (
			$_SESSION['Items']->line_items as $line => $itm
		) {
			if (isset($_POST['Line' . $line])) {
				if ($_SESSION['Items']->trans_no) {
					$min = $itm->qty_done;
					$max = $itm->quantity;
				}
				else {
					$min = 0;
					$max = $itm->quantity - $itm->qty_done;
				}

				if ($itm->quantity > 0 && Validation::is_num('Line' . $line, $min, $max)) {
					$_SESSION['Items']->line_items[$line]->qty_dispatched = input_num('Line' . $line);
				}
				elseif ($itm->quantity < 0 && Validation::is_num('Line' . $line, $max, $min)) {
					$_SESSION['Items']->line_items[$line]->qty_dispatched = input_num('Line' . $line);
				}
				else {
					ui_view::set_focus('Line' . $line);
					$ok = 0;
				}
			}

			if (isset($_POST['Line' . $line . 'Desc'])) {
				$line_desc = $_POST['Line' . $line . 'Desc'];
				if (strlen($line_desc) > 0) {
					$_SESSION['Items']->line_items[$line]->description = $line_desc;
				}
			}
		}
		// ...
		//	else
		//	  $_SESSION['Items']->freight_cost = input_num('ChargeFreightCost');
		return $ok;
	}

	//------------------------------------------------------------------------------

	function check_qoh()
	{

		if (!SysPrefs::allow_negative_stock()) {
			foreach (
				$_SESSION['Items']->line_items as $itm
			) {

				if ($itm->qty_dispatched && Manufacturing::has_stock_holding($itm->mb_flag)) {
					$qoh = get_qoh_on_date($itm->stock_id, $_POST['Location'], $_POST['DispatchDate']);

					if ($itm->qty_dispatched > $qoh) {
						ui_msgs::display_error(
							_("The delivery cannot be processed because there is an insufficient quantity for item:") .
							 " " . $itm->stock_id . " - " . $itm->description
						);
						return false;
					}
				}
			}
		}
		return true;
	}

	//------------------------------------------------------------------------------

	if (isset($_POST['process_delivery']) && check_data() && check_qoh()) {

		$dn = &$_SESSION['Items'];

		if ($_POST['bo_policy']) {
			$bo_policy = 0;
		}
		else {
			$bo_policy = 1;
		}
		$newdelivery = ($dn->trans_no == 0);

		copy_to_cart();
		if ($newdelivery) {
			Dates::new_doc_date($dn->document_date);
		}
		$delivery_no = $dn->write($bo_policy);

		processing_end();
		if ($newdelivery) {
			meta_forward($_SERVER['PHP_SELF'], "AddedID=$delivery_no");
		}
		else {
			meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$delivery_no");
		}
	}
	if (isset($_POST['Update']) || isset($_POST['_Location_update'])) {
		$Ajax->activate('Items');
	}
	//------------------------------------------------------------------------------
	start_form();
	hidden('cart_id');

	start_table(Config::get('tables_style2') . " width=90%", 5);
	echo "<tr><td>"; // outer table

	start_table(Config::get('tables_style') . "  width=100%");
	start_row();
	label_cells(_("Customer"), $_SESSION['Items']->customer_name, "class='label'");
	label_cells(_("Branch"), get_branch_name($_SESSION['Items']->Branch), "class='label'");
	label_cells(_("Currency"), $_SESSION['Items']->customer_currency, "class='label'");
	end_row();
	start_row();

	//if (!isset($_POST['ref']))
	//	$_POST['ref'] = Refs::get_next(ST_CUSTDELIVERY);

	if ($_SESSION['Items']->trans_no == 0) {
		ref_cells(_("Reference"), 'ref', '', null, "class='label'");
	}
	else {
		label_cells(_("Reference"), $_SESSION['Items']->reference, "class='label'");
	}

	label_cells(
		_("For Sales Order"), ui_view::get_customer_trans_view_str(
			ST_SALESORDER,
			$_SESSION['Items']->order_no
		), "class='tableheader2'"
	);

	label_cells(_("Sales Type"), $_SESSION['Items']->sales_type_name, "class='label'");
	end_row();
	start_row();

	if (!isset($_POST['Location'])) {
		$_POST['Location'] = $_SESSION['Items']->Location;
	}
	label_cell(_("Delivery From"), "class='label'");
	locations_list_cells(null, 'Location', null, false, true);

	if (!isset($_POST['ship_via'])) {
		$_POST['ship_via'] = $_SESSION['Items']->ship_via;
	}
	label_cell(_("Shipping Company"), "class='label'");
	shippers_list_cells(null, 'ship_via', $_POST['ship_via']);

	// set this up here cuz it's used to calc qoh
	if (!isset($_POST['DispatchDate']) || !Dates::is_date($_POST['DispatchDate'])) {
		$_POST['DispatchDate'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['DispatchDate'])) {
			$_POST['DispatchDate'] = Dates::end_fiscalyear();
		}
	}
	date_cells(_("Date"), 'DispatchDate', '', $_SESSION['Items']->trans_no == 0, 0, 0, 0, "class='label'");
	end_row();

	end_table();

	echo "</td><td>"; // outer table

	start_table(Config::get('tables_style') . "  width=90%");

	if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
		$_POST['due_date'] = get_invoice_duedate($_SESSION['Items']->customer_id, $_POST['DispatchDate']);
	}
	start_row();
	date_cells(_("Invoice Dead-line"), 'due_date', '', null, 0, 0, 0, "class='label'");
	end_row();
	end_table();

	echo "</td></tr>";
	end_table(1); // outer table

	$row = get_customer_to_order($_SESSION['Items']->customer_id);
	if ($row['dissallow_invoices'] == 1) {
		ui_msgs::display_error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
		end_form();
		end_page();
		exit();
	}
	ui_msgs::display_heading(_("Delivery Items"));
	div_start('Items');
	start_table(Config::get('tables_style') . "  width=90%");

	$new = $_SESSION['Items']->trans_no == 0;
	$th  = array(
		_("Item Code"), _("Item Description"),
		$new ? _("Ordered") : _("Max. delivery"), _("Units"), $new ? _("Delivered") : _("Invoiced"),
		_("This Delivery"), _("Price"), _("Tax Type"), _("Discount"), _("Total")
	);

	table_header($th);
	$k          = 0;
	$has_marked = false;

	foreach (
		$_SESSION['Items']->line_items as $line => $ln_itm
	) {
		if ($ln_itm->quantity == $ln_itm->qty_done) {
			continue; //this line is fully delivered
		}
		// if it's a non-stock item (eg. service) don't show qoh
		$show_qoh = true;
		if (SysPrefs::allow_negative_stock() || !Manufacturing::has_stock_holding($ln_itm->mb_flag)
		 || $ln_itm->qty_dispatched == 0
		) {
			$show_qoh = false;
		}

		if ($show_qoh) {
			$qoh = get_qoh_on_date($ln_itm->stock_id, $_POST['Location'], $_POST['DispatchDate']);
		}

		if ($show_qoh && ($ln_itm->qty_dispatched > $qoh)) {
			// oops, we don't have enough of one of the component items
			start_row("class='stockmankobg'");
			$has_marked = true;
		}
		else {
			alt_table_row_color($k);
		}
		ui_view::view_stock_status_cell($ln_itm->stock_id);

		text_cells(null, 'Line' . $line . 'Desc', $ln_itm->description, 30, 50);
		$dec = get_qty_dec($ln_itm->stock_id);
		qty_cell($ln_itm->quantity, false, $dec);
		label_cell($ln_itm->units);
		qty_cell($ln_itm->qty_done, false, $dec);

		small_qty_cells(null, 'Line' . $line, qty_format($ln_itm->qty_dispatched, $ln_itm->stock_id, $dec), null, null, $dec);

		$display_discount_percent = percent_format($ln_itm->discount_percent * 100) . "%";

		$line_total = ($ln_itm->qty_dispatched * $ln_itm->price * (1 - $ln_itm->discount_percent));

		amount_cell($ln_itm->price);
		label_cell($ln_itm->tax_type_name);
		label_cell($display_discount_percent, "nowrap align=right");
		amount_cell($line_total);

		end_row();
	}

	$_POST['ChargeFreightCost'] = get_post(
		'ChargeFreightCost',
		price_format($_SESSION['Items']->freight_cost)
	);

	$colspan = 9;

	start_row();
	label_cell(_("Shipping Cost"), "colspan=$colspan align=right");
	small_amount_cells(null, 'ChargeFreightCost', $_SESSION['Items']->freight_cost);
	end_row();

	$inv_items_total = $_SESSION['Items']->get_items_total_dispatch();

	$display_sub_total = price_format($inv_items_total + input_num('ChargeFreightCost'));

	label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan align=right", "align=right");

	$taxes     = $_SESSION['Items']->get_taxes(input_num('ChargeFreightCost'));
	$tax_total = ui_view::display_edit_tax_items($taxes, $colspan, $_SESSION['Items']->tax_included);

	$display_total = price_format(($inv_items_total + input_num('ChargeFreightCost') + $tax_total));

	label_row(_("Amount Total"), $display_total, "colspan=$colspan align=right", "align=right");

	end_table(1);

	if ($has_marked) {
		ui_msgs::display_warning(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='red'");
	}
	start_table(Config::get('tables_style2'));

	policy_list_row(_("Action For Balance"), "bo_policy", null);

	textarea_row(_("Memo"), 'Comments', null, 50, 4);

	end_table(1);
	div_end();
	submit_center_first(
		'Update', _("Update"),
		_('Refresh document page'), true
	);
	submit_center_last(
		'process_delivery', _("Process Dispatch"),
		_('Check entered data and save document'), 'default'
	);

	end_form();

	end_page();

?>
