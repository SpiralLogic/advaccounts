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
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	$order = Orders::session_get() ? : null;
	Security::set_page((!$order) ? : $order->trans_type, array(
		ST_SALESORDER => SA_SALESORDER,
		ST_SALESQUOTE => SA_SALESQUOTE,
		ST_CUSTDELIVERY => SA_SALESDELIVERY,
		ST_SALESINVOICE => SA_SALESINVOICE
	), array(
		Orders::NEW_ORDER => SA_SALESORDER,
		Orders::MODIFY_ORDER => SA_SALESORDER,
		Orders::NEW_QUOTE => SA_SALESQUOTE,
		Orders::MODIFY_QUOTE => SA_SALESQUOTE,
		Orders::NEW_DELIVERY => SA_SALESDELIVERY,
		Orders::NEW_INVOICE => SA_SALESINVOICE
	));
	JS::open_window(900, 500);
	if (Input::get('customer_id', Input::NUMERIC)) {
		$_POST['customer_id'] = $_GET['customer_id'];
		Ajax::i()->activate('customer_id');
	}
	$page_title = _($help_context = "New Sales Order Entry");
	if (Input::get(Orders::ADD, Input::NUMERIC, -1) > -1) {
		switch (Input::get('type')) {
			case ST_SALESQUOTE:
				$page_title = _($help_context = "New Sales Quotation Entry");
				break;
			case ST_SALESINVOICE:
				$page_title = _($help_context = "Direct Sales Invoice");
				break;
			case ST_CUSTDELIVERY:
				$page_title = _($help_context = "Direct Sales Delivery");
				break;
			case ST_SALESORDER;
			default:
				$page_title = _($help_context = "New Sales Order Entry");
				break;
		}
		$order = create_order(Input::get('type'), 0);
	} elseif (Input::get(Orders::UPDATE, Input::NUMERIC, 0) > 0) {
		switch (Input::get('type')) {
			case ST_SALESORDER:
				$help_context = 'Modifying Sales Order';
				$page_title = sprintf(_("Modifying Sales Order # %d"), $_GET[Orders::UPDATE]);
				break;
			case ST_SALESQUOTE:
				$help_context = 'Modifying Sales Quotation';
				$page_title = sprintf(_("Modifying Sales Quotation # %d"), $_GET[Orders::UPDATE]);
				break;
		}
		$order = create_order(Input::get('type'), Input::get(Orders::UPDATE));
	}
	elseif (Input::get(Orders::QUOTE_TO_ORDER)) {
		$page_title = _($help_context = "New Order from Quote");
		$order = create_order(ST_SALESQUOTE, $_GET[Orders::QUOTE_TO_ORDER]);
	}
	elseif (Input::get(Orders::CLONE_ORDER)) {
		$page_title = _($help_context = "New order from previous order");
		$order = create_order(ST_SALESORDER, Input::get(Orders::CLONE_ORDER));
	}
	if (!isset($order)) {
		$order = create_order(ST_SALESORDER, 0);
	}
	Page::start($page_title);
	if (list_updated('branch_id')) {
		// when branch is selected via external editor also customer can change
		$br = Sales_Branch::get(get_post('branch_id'));
		$_POST['customer_id'] = $br['debtor_no'];
		Ajax::i()->activate('customer_id');
	}

	if (isset($_GET[REMOVED])) {
		if ($_GET['type'] == ST_SALESQUOTE) {
			Event::notice(_("This sales quotation has been deleted as requested."), 1);
			Display::submenu_option(_("Enter a New Sales Quotation"), "/sales/sales_order_entry.php?add=0type=" . ST_SALESQUOTE);
			Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESQUOTE);
		}
		else {
			Event::notice(_("This sales order has been deleted as requested."), 1);
			Display::submenu_option(_("Enter a New Sales Order"), "/sales/sales_order_entry.php?add=0&type=" . $_GET['type']);
			Display::submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.vphp?type=" . ST_SALESORDER);
		}
		Page::footer_exit();
	}
	//--------------- --------------------------------------------------------------
	if (isset($_POST[Orders::PROCESS_ORDER]) && can_process($order)) {
		copy_to_order($order);
		$modified = ($order->trans_no != 0);
		$so_type = $order->so_type;
		$trans_type = $order->trans_type;
		Dates::new_doc_date($order->document_date);
		$_SESSION['global_customer_id'] = $order->customer_id;
		$order->write(1);
		$jobsboard_order = clone ($order);
		$trans_no = $jobsboard_order->trans_no = key($order->trans_no);
		if (Errors::getSeverity() == -1) { // abort on failure or error messages are lost
			Ajax::i()->activate('_page_body');
			Page::footer_exit();
		}
		$order->finish();
		if ($trans_type == ST_SALESORDER) {
			$jb = new      \Modules\Jobsboard();
			$jb->addjob($jobsboard_order);
		}

		page_complete($trans_no, $trans_type, ($trans_type==ST_SALESORDER? "Order":"Quote"), true, $modified);
	}
	if (isset($_POST['update'])) {
		Ajax::i()->activate('items_table');
	}
	if (isset($_POST[Orders::CANCEL_CHANGES])) {
		$type = $order->trans_type;
		$order_no = (is_array($order->trans_no)) ? key($order->trans_no) : $order->trans_no;
		Orders::session_delete($_POST['order_id']);
		$order = create_order($type, $order_no);
	}
	if (isset($_POST[Orders::DELETE_ORDER])) {
		handle_cancel_order($order);
	}
	$id = find_submit(MODE_DELETE);
	if ($id != -1) {
		handle_delete_item($order, $id);
	}
	if (isset($_POST[Orders::UPDATE_ITEM])) {
		handle_update_item($order);
	}
	if (isset($_POST['discountall'])) {
		if (!is_numeric($_POST['_discountall'])) {
			Event::error(_("Discount must be a number"));
		}
		elseif ($_POST['_discountall'] < 0 || $_POST['_discountall'] > 100) {
			Event::error(_("Discount percentage must be between 0-100"));
		}
		else {
			$order->discount_all($_POST['_discountall'] / 100);
		}
		Ajax::i()->activate('_page_body');
	}
	if (isset($_POST[Orders::ADD_ITEM])) {
		handle_new_item($order);
	}
	if (isset($_POST['CancelItemChanges'])) {
		line_start_focus();
	}
	Validation::check(Validation::STOCK_ITEMS, _("There are no inventory items defined in the system."));
	Validation::check(Validation::BRANCHES_ACTIVE, _("There are no customers, or there are no customers with branches. Please define customers and customer branches."));
	if ($order && $order->trans_type == ST_SALESINVOICE) {
		$idate = _("Invoice Date:");
		$orderitems = _("Sales Invoice Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Invoice");
		$deleteorder = _("Delete Invoice");
		$corder = '';
		$porder = _("Place Invoice");
	}
	elseif ($order && $order->trans_type == ST_CUSTDELIVERY) {
		$idate = _("Delivery Date:");
		$orderitems = _("Delivery Note Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Dispatch");
		$deleteorder = _("Delete Delivery");
		$corder = '';
		$porder = _("Place Delivery");
	}
	elseif ($order && $order->trans_type == ST_SALESQUOTE) {
		$idate = _("Quotation Date:");
		$orderitems = _("Sales Quotation Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Quotation");
		$deleteorder = _("Delete Quotation");
		$porder = _("Place Quotation");
		$corder = _("Commit Quotations Changes");
	}
	else {
		$idate = _("Order Date:");
		$orderitems = _("Sales Order Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Order");
		$deleteorder = _("Delete Order");
		$porder = _("Place Order");
		$corder = _("Commit Order Changes");
	}
	start_form();
	$customer_error = $order->header($idate);
	hidden('order_id', $_POST['order_id']);
	if ($customer_error == "") {
		start_table('tablesstyle center width90 pad10');
		echo "<tr><td>";
		$order->summary($orderitems, true);
		echo "</td></tr><tr><td>";
		$order->display_delivery_details();
		echo "</td></tr>";
		end_table(1);
		if ($order->trans_no > 0 && User::i()->can_access(SA_VOIDTRANSACTION)) {
			submit_js_confirm(Orders::DELETE_ORDER, _('You are about to void this Document.\nDo you want to continue?'));
			submit_center_first(Orders::DELETE_ORDER, $deleteorder, _('Cancels document entry or removes sales order when editing an old document'));
			submit_center_middle(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
		}
		else {
			submit_center_first(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
		}
		if ($order->trans_no == 0) {
			submit_center_last(Orders::PROCESS_ORDER, $porder, _('Check entered data and save document'), 'default');
		}
		else {
			submit_center_last(Orders::PROCESS_ORDER, $corder, _('Validate changes and update document'), 'default');
		}
		if (isset($_GET[Orders::MODIFY_ORDER]) && is_numeric($_GET[Orders::MODIFY_ORDER])) {
			//UploadHandler::insert($_GET[Orders::MODIFY_ORDER]);
		}
	}
	else {
		Event::warning($customer_error);
		Session::i()->global_customer = null;
		Page::footer_exit();
	}
	end_form();
	JS::onUnload('Are you sure you want to leave without commiting changes?');
	Debtor::addEditDialog();
	Item::addEditDialog();
	Page::end(true);
	unset($_SESSION['order_no']);
	/**
	 * @param        $order_no
	 * @param        $trans_type
	 * @param string $trans_name
	 * @param bool   $edit
	 * @param bool   $update
	 */
	function page_complete($order_no, $trans_type, $trans_name = 'Transaction', $edit = false, $update = false) {
		$customer = new Debtor($_SESSION['global_customer_id']);
		$emails = $customer->getEmailAddresses();
		Event::success(sprintf(_($trans_name . " # %d has been " . ($update ? "updated!" : "added!")), $order_no));
		Display::submenu_view(_("&View This " . $trans_name), $trans_type, $order_no);
		if ($edit) {
			Display::submenu_option(_("&Edit This " . $trans_name), "/sales/sales_order_entry.php?update=$order_no&type=" . $trans_type);
		}
		Display::submenu_print(_("&Print This " . $trans_name), $trans_type, $order_no, 'prtopt');
		Reporting::email_link($order_no, _("Email This $trans_name"), true, $trans_type, 'EmailLink', null, $emails, 1);
		if ($trans_type == ST_SALESORDER || $trans_type == ST_SALESQUOTE) {
			Display::submenu_print(_("Print Proforma Invoice"), ($trans_type == ST_SALESORDER ? ST_PROFORMA : ST_PROFORMAQ), $order_no, 'prtopt');
			Reporting::email_link($order_no, _("Email This Proforma Invoice"), true, ($trans_type == ST_SALESORDER ? ST_PROFORMA :
			 ST_PROFORMAQ), 'EmailLink', null, $emails, 1);
		}
		if ($trans_type == ST_SALESORDER) {
			Display::submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber=$order_no");
			Display::submenu_option(_("Show outstanding &Orders"), "/sales/inquiry/sales_orders_view.php?OutstandingOnly=1");
			Display::submenu_option(_("Enter a New &Order"), "/sales/sales_order_entry.php?add=0&type=" . ST_SALESORDER);
			Display::submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESORDER);
		}
		elseif ($trans_type == ST_SALESQUOTE) {
			Display::submenu_option(_("Make &Sales Order Against This Quotation"), "/sales/sales_order_entry.php?" . Orders::QUOTE_TO_ORDER . "=$order_no");
			Display::submenu_option(_("Enter a New &Quotation"), "/sales/sales_order_entry.php?add=0&type=" . ST_SALESQUOTE);
			Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESQUOTE);
		}
		elseif ($trans_type == ST_CUSTDELIVERY) {
			Display::submenu_print(_("&Print Delivery Note"), ST_CUSTDELIVERY, $order_no, 'prtopt');
			Display::submenu_print(_("P&rint as Packing Slip"), ST_CUSTDELIVERY, $order_no, 'prtopt', null, 1);
			Display::note(GL_UI::view(ST_CUSTDELIVERY, $order_no, _("View the GL Journal Entries for this Dispatch")), 0, 1);
			Display::submenu_option(_("Make &Invoice Against This Delivery"), "/sales/customer_invoice.php?DeliveryNumber=$order_no");
			((isset($_GET['Type']) && $_GET['Type'] == 1)) ?
			 Display::submenu_option(_("Enter a New Template &Delivery"), "/sales/inquiry/sales_orders_view.php?DeliveryTemplates=Yes") :
			 Display::submenu_option(_("Enter a &New Delivery"), "/sales/sales_order_entry.php?add=0&type=" . ST_CUSTDELIVERY);
		}
		elseif ($trans_type == ST_SALESINVOICE) {
			$sql = "SELECT trans_type_from, trans_no_from FROM debtor_allocations WHERE trans_type_to=" . ST_SALESINVOICE . " AND trans_no_to=" . DB::escape($order_no);
			$result = DB::query($sql, "could not retrieve customer allocation");
			$row = DB::fetch($result);
			if ($row !== false) {
				Display::submenu_print(_("Print &Receipt"), $row['trans_type_from'], $row['trans_no_from'] . "-" . $row['trans_type_from'], 'prtopt');
			}
			Display::note(GL_UI::view(ST_SALESINVOICE, $order_no, _("View the GL &Journal Entries for this Invoice")), 0, 1);
			if ((isset($_GET['Type']) && $_GET['Type'] == 1)) {
				Display::submenu_option(_("Enter a &New Template Invoice"), "/sales/inquiry/sales_orders_view.php?InvoiceTemplates=Yes");
			}
			else {
				Display::submenu_option(_("Enter a &New Direct Invoice"), "/sales/sales_order_entry.php?add=0&type=10");
			}
			Display::link_params("/sales/customer_payments.php", _("Apply a customer payment"));
			if (isset($_GET[ADDED_DI]) && isset($_SESSION['global_customer_id']) && $row == false) {
				echo "<div style='text-align:center;'><iframe style='margin:0 auto; border-width:0;' src='/sales/customer_payments.php?frame=1' width='80%' height='475' scrolling='auto' frameborder='0'></iframe> </div>";
			}
		}
		JS::set_focus('prtopt');
		//	UploadHandler::insert($order_no);
		Page::footer_exit();
	}

	/**
	 * @param $order
	 */
	function copy_to_order($order) {
		$order->reference = $_POST['ref'];
		$order->Comments = $_POST['Comments'];
		$order->document_date = $_POST['OrderDate'];
		$order->due_date = $_POST['delivery_date'];
		$order->cust_ref = $_POST['cust_ref'];
		$order->freight_cost = Validation::input_num('freight_cost');
		$order->deliver_to = $_POST['deliver_to'];
		$order->delivery_address = $_POST['delivery_address'];
		$order->name = $_POST['name'];
		$order->customer_name = Input::post('customer', Input::STRING);
		$order->phone = $_POST['phone'];
		$order->location = $_POST['location'];
		$order->ship_via = $_POST['ship_via'];
		if (isset($_POST['email'])) {
			$order->email = $_POST['email'];
		}
		else {
			$order->email = '';
		}
		if (isset($_POST['salesman'])) {
			$order->salesman = $_POST['salesman'];
		}
		$order->customer_id = $_POST['customer_id'];
		$order->Branch = $_POST['branch_id'];
		$order->sales_type = $_POST['sales_type'];
		// POS
		if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
			$order->dimension_id = $_POST['dimension_id'];
			$order->dimension2_id = $_POST['dimension2_id'];
		}
	}

	/**
	 * @param $order
	 *
	 * @return \Purch_Order|\Sales_Order
	 */
	function copy_from_order($order) {
		$order = Sales_Order::check_edit_conflicts($order);
		$_POST['ref'] = $order->reference;
		$_POST['Comments'] = $order->Comments;
		$_POST['OrderDate'] = $order->document_date;
		$_POST['delivery_date'] = $order->due_date;
		$_POST['cust_ref'] = $order->cust_ref;
		$_POST['freight_cost'] = Num::price_format($order->freight_cost);
		$_POST['deliver_to'] = $order->deliver_to;
		$_POST['delivery_address'] = $order->delivery_address;
		$_POST['name'] = $order->name;
		$_POST['customer'] = $order->customer_name;
		$_POST['phone'] = $order->phone;
		$_POST['location'] = $order->location;
		$_POST['ship_via'] = $order->ship_via;
		$_POST['customer_id'] = $order->customer_id;
		$_POST['branch_id'] = $order->Branch;
		$_POST['sales_type'] = $order->sales_type;
		$_POST['salesman'] = $order->salesman;
		if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
			$_POST['dimension_id'] = $order->dimension_id;
			$_POST['dimension2_id'] = $order->dimension2_id;
		}
		$_POST['order_id'] = $order->order_id;
		return Orders::session_set($order);
	}

	/**

	 */
	function line_start_focus() {
		Ajax::i()->activate('items_table');
		JS::set_focus('_stock_id_edit');
	}

	/**
	 * @param Sales_Order $order
	 *
	 * @return bool
	 */
	function can_process($order) {
		if (!get_post('customer_id')) {
			Event::error(_("There is no customer selected."));
			JS::set_focus('customer_id');
			return false;
		}
		if (!get_post('branch_id')) {
			Event::error(_("This customer has no branch defined."));
			JS::set_focus('branch_id');
			return false;
		}
		if (!Dates::is_date($_POST['OrderDate'])) {
			Event::error(_("The entered date is invalid."));
			JS::set_focus('OrderDate');
			return false;
		}
		if (!$order) {
			Event::error(_("You are not currently editing an order! (Using the browser back button after committing an order does not go back to editing)"));
			return false;
		}
		if ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE && !Dates::is_date_in_fiscalyear($_POST['OrderDate'])) {
			Event::error(_("The entered date is not in fiscal year"));
			JS::set_focus('OrderDate');
			return false;
		}
		if (count($order->line_items) == 0) {
			if (!empty($_POST['stock_id'])) {
				handle_new_item($order);
			}
			else {
				Event::error(_("You must enter at least one non empty item line."));
				JS::set_focus(Orders::ADD_ITEM);
				return false;
			}
		}
		if ($order->trans_type == ST_SALESORDER && $order->trans_no == 0 && !empty($_POST['cust_ref']) && $order->check_cust_ref($_POST['cust_ref'])
		) {
			Event::error(_("This customer already has a purchase order with this number."));
			JS::set_focus('cust_ref');
			return false;
		}
		if (strlen($_POST['deliver_to']) <= 1) {
			Event::error(_("You must enter the person or company to whom delivery should be made to."));
			JS::set_focus('deliver_to');
			return false;
		}
		if (strlen($_POST['delivery_address']) <= 1) {
			Event::error(_("You should enter the street address in the box provided. Orders cannot be accepted without a valid street address."));
			JS::set_focus('delivery_address');
			return false;
		}
		if ($_POST['freight_cost'] == "") {
			$_POST['freight_cost'] = Num::price_format(0);
		}
		if (!Validation::is_num('freight_cost', 0)) {
			Event::error(_("The shipping cost entered is expected to be numeric."));
			JS::set_focus('freight_cost');
			return false;
		}
		if (!Dates::is_date($_POST['delivery_date'])) {
			if ($order->trans_type == ST_SALESQUOTE) {
				Event::error(_("The Valid date is invalid."));
			}
			else {
				Event::error(_("The delivery date is invalid."));
			}
			JS::set_focus('delivery_date');
			return false;
		}
		//if (Dates::date1_greater_date2($order->document_date, $_POST['delivery_date'])) {
		if (Dates::date1_greater_date2($_POST['OrderDate'], $_POST['delivery_date'])) {
			if ($order->trans_type == ST_SALESQUOTE) {
				Event::error(_("The requested valid date is before the date of the quotation."));
			}
			else {
				Event::error(_("The requested delivery date is before the date of the order."));
			}
			JS::set_focus('delivery_date');
			return false;
		}
		if ($order->trans_type == ST_SALESORDER && strlen($_POST['name']) < 1) {
			Event::error(_("You must enter a Person Ordering name."));
			JS::set_focus('name');
			return false;
		}
		if (!Ref::is_valid($_POST['ref'])) {
			Event::error(_("You must enter a reference."));
			JS::set_focus('ref');
			return false;
		}
		if ($order->trans_no == 0 && !Ref::is_new($_POST['ref'], $order->trans_type)) {
			$_POST['ref'] = Ref::get_next($order->trans_type);
		}
		return true;
	}

	/**
	 * @param $order
	 *
	 * @return bool
	 */
	function check_item_data($order) {
		if (!User::i()->can_access(SA_SALESCREDIT) && (!Validation::is_num('qty', 0) || !Validation::is_num('Disc', 0, 100))) {
			Event::error(_("The item could not be updated because you are attempting to set the quantity ordered to less than 0, or the discount percent to more than 100."));
			JS::set_focus('qty');
			return false;
		}
		elseif (!Validation::is_num('price', 0)) {
			Event::error(_("Price for item must be entered and can not be less than 0"));
			JS::set_focus('price');
			return false;
		}
		elseif (!User::i()
		 ->can_access(SA_SALESCREDIT) && isset($_POST['LineNo']) && isset($order->line_items[$_POST['LineNo']]) && !Validation::is_num('qty', $order->line_items[$_POST[LineNo]]->qty_done)
		) {
			JS::set_focus('qty');
			Event::error(_("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively."));
			return false;
		} // Joe Hunt added 2008-09-22 -------------------------
		elseif ($order->trans_type != ST_SALESORDER && $order->trans_type != ST_SALESQUOTE && !DB_Company::get_pref('allow_negative_stock') && Item::is_inventory_item($_POST['stock_id'])) {
			$qoh = Item::get_qoh_on_date($_POST['stock_id'], $_POST['location'], $_POST['OrderDate']);
			if (Validation::input_num('qty') > $qoh) {
				$stock = Item::get($_POST['stock_id']);
				Event::error(_("The delivery cannot be processed because there is an insufficient quantity for item:") . " " . $stock['stock_id'] . " - " . $stock['description'] . " - " . _("Quantity On Hand") . " = " . Num::format($qoh, Item::qty_dec($_POST['stock_id'])));
				return false;
			}
			return true;
		}
		return true;
	}

	/**
	 * @param Sales_Order $order
	 */
	function handle_update_item($order) {
		if ($_POST[Orders::UPDATE_ITEM] != '' && check_item_data($order)) {
			$order->update_order_item($_POST['LineNo'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
		}
		line_start_focus();
	}

	/**
	 * @param Sales_Order $order
	 * @param             $line_no
	 */
	function handle_delete_item($order, $line_no) {
		if ($order->some_already_delivered($line_no) == 0) {
			$order->remove_from_order($line_no);
		}
		else {
			Event::error(_("This item cannot be deleted because some of it has already been delivered."));
		}
		line_start_focus();
	}

	/**
	 * @param Sales_Order $order
	 *
	 * @return mixed
	 */
	function handle_new_item($order) {
		if (!check_item_data($order)) {
			return;
		}
		$order->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
		$_POST['_stock_id_edit'] = $_POST['stock_id'] = "";
		line_start_focus();
	}

	/**
	 ** @param Sales_Order $order
	 */
	function handle_cancel_order($order) {
		if (!User::i()->can_access(SS_SETUP)) {
			Event::error('You don\'t have access to delete orders');
			return;
		}
		if ($order->trans_type == ST_CUSTDELIVERY) {
			Event::notice(_("Direct delivery has been cancelled as requested."), 1);
			Display::submenu_option(_("Enter a New Sales Delivery"), "/sales/sales_order_entry.php?NewDelivery=1");
		}
		elseif ($order->trans_type == ST_SALESINVOICE) {
			Event::notice(_("Direct invoice has been cancelled as requested."), 1);
			Display::submenu_option(_("Enter a New Sales Invoice"), "/sales/sales_order_entry.php?NewInvoice=1");
		}
		else {
			if ($order->trans_no != 0) {
				if ($order->trans_type == ST_SALESORDER && $order->has_deliveries(key($order->trans_no))) {
					Event::error(_("This order cannot be cancelled because some of it has already been invoiced or dispatched. However, the line item quantities may be modified."));
				}
				else {
					$trans_no = key($order->trans_no);
					$trans_type = $order->trans_type;
					if (!isset($_GET[REMOVED_ID])) {
						$order->($trans_no, $trans_type);
						$jb = new \Modules\Jobsboard();
						$jb->removejob($trans_no);
						Event::notice(_("Sales order has been cancelled as requested."), 1);
					}
				}
			}
			else {
				Display::meta_forward('/index.php', 'application=sales');
			}
		}
		Ajax::i()->activate('_page_body');
		$order->finish();
		Display::submenu_option(_("Show outstanding &Orders"), "/sales/inquiry/sales_orders_view.php?OutstandingOnly=1");
		Display::submenu_option(_("Enter a New &Order"), "/sales/sales_order_entry.php?add=0&type=" . ST_SALESORDER);
		Display::submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESORDER);
		Page::footer_exit();
	}

	/**
	 * @param $type
	 * @param $trans_no
	 *
	 * @return \Purch_Order|\Sales_Order
	 */
	function create_order($type, $trans_no) {
		if (isset($_GET[Orders::QUOTE_TO_ORDER])) {
			$doc = new Sales_Order(ST_SALESQUOTE, $trans_no);
			$doc->convertToOrder();
		}
		elseif (isset($_GET[Orders::CLONE_ORDER])) {
			$trans_no = $_GET[Orders::CLONE_ORDER];
			$doc = new Sales_Order(ST_SALESORDER, array($trans_no));
			$doc->trans_no = 0;
			$doc->trans_type = ST_SALESORDER;
			$doc->reference = Ref::get_next($doc->trans_type);
			$doc->document_date = $doc->due_date = Dates::new_doc_date();
			foreach ($doc->line_items as $line) {
				$line->qty_done = $line->qty_dispatched = 0;
			}
		}
		elseif ($type != ST_SALESORDER && $type != ST_SALESQUOTE && $trans_no != 0) { // this is template
			$doc = new Sales_Order(ST_SALESORDER, array($trans_no));
			$doc->trans_type = $type;
			$doc->trans_no = 0;
			$doc->document_date = Dates::new_doc_date();
			if ($type == ST_SALESINVOICE) {
				$doc->due_date = Sales_Order::get_invoice_duedate($doc->customer_id, $doc->document_date);
				$doc->pos = User::pos();
				$pos = Sales_Point::get($doc->pos);
				$doc->pos = -1;
			}
			else {
				$doc->due_date = $doc->document_date;
			}
			$doc->reference = Ref::get_next($doc->trans_type);
			foreach ($doc->line_items as $line) {
				$doc->line_items[$line]->qty_done = 0;
			}
		}
		else {
			$doc = new Sales_Order($type, array($trans_no));
		}
		return copy_from_order($doc);
	}
