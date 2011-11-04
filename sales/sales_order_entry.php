<?php

	/*     * ********************************************************************
				Copyright (C) FrontAccounting, LLC.
				Released under the terms of the GNU General Public License, GPL,
				as published by the Free Software Foundation, either version 3
				of the License, or (at your option) any later version.
				This program is distributed in the hope that it will be useful,
				but WITHOUT ANY WARRANTY; without even the implied warranty of
				MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
				See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
				* ********************************************************************* */
	//-----------------------------------------------------------------------------
	//
	//	Entry/Modify Sales Quotations
	//	Entry/Modify Sales Order
	//	Entry Direct Delivery
	//	Entry Direct Invoice
	//
	$page_security = 'SA_SALESORDER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	include_once(APP_PATH . "sales/includes/sales_ui.php");
	include_once(APP_PATH . "sales/includes/ui/sales_order_ui.php");
	include_once(APP_PATH . "sales/includes/db/sales_types_db.php");
	Security::set_page(
		(!Input::session('Items') ? : $_SESSION['Items']->trans_type),
		array(
				 ST_SALESORDER	 => 'SA_SALESORDER',
				 ST_SALESQUOTE	 => 'SA_SALESQUOTE',
				 ST_CUSTDELIVERY => 'SA_SALESDELIVERY',
				 ST_SALESINVOICE => 'SA_SALESINVOICE'
		),
		array(
				 'NewOrder'							=> 'SA_SALESORDER',
				 'ModifySalesOrder'			=> 'SA_SALESORDER',
				 'NewQuotation'					=> 'SA_SALESQUOTE',
				 'ModifyQuotationNumber' => 'SA_SALESQUOTE',
				 'NewDelivery'					 => 'SA_SALESDELIVERY',
				 'NewInvoice'						=> 'SA_SALESINVOICE'
		)
	);
	JS::get_js_open_window(900, 500);
	$page_title = _($help_context = "Sales Order Entry");
	if (Input::post('saveorder')) {
		$_SESSION['Items']->store();
		echo $_POST['saveorder'];
		exit();
	}
	if (Input::get('customer_id', Input::NUMERIC)) {
		$_POST['customer_id'] = $_GET['customer_id'];
		$Ajax->activate('customer_id');
	}
	if (Input::get('NewDelivery', Input::NUMERIC)) {
		$page_title = _($help_context = "Direct Sales Delivery");
		create_cart(ST_CUSTDELIVERY, $_GET['NewDelivery']);
	}
	if (Input::get('NewInvoice', Input::NUMERIC)) {
		$page_title = _($help_context = "Direct Sales Invoice");
		create_cart(ST_SALESINVOICE, $_GET['NewInvoice']);
	}
	elseif (Input::get('ModifyOrderNumber', Input::NUMERIC)) {
		$help_context = 'Modifying Sales Order';
		$page_title   = sprintf(_("Modifying Sales Order # %d"), $_GET['ModifyOrderNumber']);
		create_cart(ST_SALESORDER, $_GET['ModifyOrderNumber']);
	}
	elseif (Input::get('ModifyQuotationNumber', Input::NUMERIC)) {
		$help_context = 'Modifying Sales Quotation';
		$page_title   = sprintf(_("Modifying Sales Quotation # %d"), $_GET['ModifyQuotationNumber']);
		create_cart(ST_SALESQUOTE, $_GET['ModifyQuotationNumber']);
	}
	elseif (Input::get('NewOrder')) {
		create_cart(ST_SALESORDER, 0);
	}
	elseif (Input::get('NewQuotation')) {
		$page_title = _($help_context = "New Sales Quotation Entry");
		create_cart(ST_SALESQUOTE, 0);
	}
	elseif (Input::get('NewQuoteToSalesOrder')) {
		create_cart(ST_SALESQUOTE, $_GET['NewQuoteToSalesOrder']);
	}
	elseif (Input::get('CloneOrder')) {
		create_cart(ST_SALESORDER, Input::get('CloneOrder'));
	}
	elseif (Input::get('remotecombine')) {
		if (isset($_SESSION['Items'])) {
			foreach (
				$_SESSION['remote_order']->line_items as $item
			) {
				add_to_order($_SESSION['Items'], $item->stock_id, $item->quantity, $item->price, $item->discount_percent, $item->description);
			}
			unset($_SESSION['remote_order']);
		}
	} elseif (Input::get('NewRemoteToSalesOrder')) {
		create_cart(ST_SALESORDER, $_GET['NewRemoteToSalesOrder']);
	} elseif (isset($_GET['restoreorder'])) {
		$serial = Sales_Order::restore();
		create_cart($serial, 0);
	}
	Page::start($page_title);
	//-----------------------------------------------------------------------------
	if (list_updated('branch_id')) {
		// when branch is selected via external editor also customer can change
		$br                   = get_branch(get_post('branch_id'));
		$_POST['customer_id'] = $br['debtor_no'];
		$Ajax->activate('customer_id');
	}
	if (isset($_GET['AddedID'])) {
		page_complete($_GET['AddedID'], ST_SALESORDER, "Order", true);
	}
	elseif (isset($_GET['UpdatedID'])) {
		page_complete($_GET['UpdatedID'], ST_SALESORDER, "Order", true, true);
	}
	elseif (isset($_GET['AddedQU'])) {
		page_complete($_GET['AddedQU'], ST_SALESQUOTE, "Quotation", true);
	}
	elseif (isset($_GET['UpdatedQU'])) {
		page_complete($_GET['UpdatedQU'], ST_SALESQUOTE, "Quotation", true, true);
	}
	elseif (isset($_GET['AddedDN'])) {
		page_complete($_GET['AddedDN'], ST_CUSTDELIVERY, "Delivery");
	}
	elseif (isset($_GET['AddedDI'])) {
		page_complete($_GET['AddedDI'], ST_SALESINVOICE, "Invoice");
	}
	elseif (isset($_GET['RemovedID'])) {
		submenu_view(_("&View This Order"), ST_SALESORDER, $_GET['RemovedID']);
		if ($_GET['Type'] == ST_SALESQUOTE) {
			ui_msgs::display_notification(_("This sales quotation has been cancelled as requested."), 1);
			submenu_option(_("Enter a New Sales Quotation"), "/sales/sales_order_entry.php?NewQuotation=Yes");
			submenu_option(_("Select A Different &Quotation to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESQUOTE);
		}
		else {
			ui_msgs::display_notification(_("This sales order has been cancelled as requested."), 1);
			submenu_option(_("Enter a New Sales Order"), "/sales/sales_order_entry.php?NewOrder=Yes");
			submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESORDER);
		}
		ui_view::display_footer_exit();
	}
	else {
		check_edit_conflicts();
	}
	function page_complete($order_no, $trans_type, $trans_name = 'Transaction', $edit = false, $update = false)
	{
		$customer = new Contacts_Customer($_SESSION['Jobsboard']->customer_id);
		$emails   = $customer->getEmailAddresses();
		ui_msgs::display_notification(
			sprintf(
				_(
					$trans_name . " # %d has been " . ($update ? "updated!"
					 : "added!")
				), $order_no
			)
		);
		submenu_view(_("&View This " . $trans_name), $trans_type, $order_no);
		if ($edit) {
			submenu_option(
				_("&Edit This " . $trans_name), "/sales/sales_order_entry.php?" . ($trans_type == ST_SALESORDER
			 ? "ModifyOrderNumber"
			 : "ModifyQuotationNumber") . "=$order_no"
			);
		}
		submenu_print(_("&Print This " . $trans_name), $trans_type, $order_no, 'prtopt');
		submenu_email(_("Email This $trans_name"), $trans_type, $order_no, null, $emails, 1);
		if ($trans_type == ST_SALESORDER || $trans_type == ST_SALESQUOTE) {
			submenu_print(
				_("Print Proforma Invoice"), ($trans_type == ST_SALESORDER ? ST_PROFORMA
				 : ST_PROFORMAQ), $order_no, 'prtopt'
			);
			submenu_email(
				_("Email This Proforma Invoice"), ($trans_type == ST_SALESORDER ? ST_PROFORMA
				 : ST_PROFORMAQ), $order_no, null, $emails, 1
			);
		}
		if ($trans_type == ST_SALESORDER) {
			submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber=$order_no");
			submenu_option(_("Show outstanding &Orders"), "/sales/inquiry/sales_orders_view.php?OutstandingOnly=1");
			submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESORDER);
		}
		elseif ($trans_type == ST_SALESQUOTE) {
			submenu_option(_("Make &Sales Order Against This Quotation"), "/sales/sales_order_entry.php?NewQuoteToSalesOrder=$order_no");
			submenu_option(_("Enter a New &Quotation"), "/sales/sales_order_entry.php?NewQuotation=1");
			submenu_option(_("Select A Different &Quotation to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESQUOTE);
		}
		elseif ($trans_type == ST_CUSTDELIVERY) {
			submenu_print(_("&Print Delivery Note"), ST_CUSTDELIVERY, $order_no, 'prtopt');
			submenu_print(_("P&rint as Packing Slip"), ST_CUSTDELIVERY, $order_no, 'prtopt', null, 1);
			ui_msgs::display_note(ui_view::get_gl_view_str(ST_CUSTDELIVERY, $order_no, _("View the GL Journal Entries for this Dispatch")), 0, 1);
			submenu_option(_("Make &Invoice Against This Delivery"), "/sales/customer_invoice.php?DeliveryNumber=$order_no");
			((isset($_GET['Type']) && $_GET['Type'] == 1))
			 ? submenu_option(_("Enter a New Template &Delivery"), "/sales/inquiry/sales_orders_view.php?DeliveryTemplates=Yes")
			 : submenu_option(_("Enter a &New Delivery"), "/sales/sales_order_entry.php?NewDelivery=0");
		}
		elseif ($trans_type == ST_SALESINVOICE) {
			$sql    = "SELECT trans_type_from, trans_no_from FROM cust_allocations WHERE trans_type_to=" . ST_SALESINVOICE . " AND trans_no_to=" . DBOld::escape($order_no);
			$result = DBOld::query($sql, "could not retrieve customer allocation");
			$row    = DBOld::fetch($result);
			if ($row !== false) {
				submenu_print(_("Print &Receipt"), $row['trans_type_from'], $row['trans_no_from'] . "-" . $row['trans_type_from'], 'prtopt');
			}
			ui_msgs::display_note(ui_view::get_gl_view_str(ST_SALESINVOICE, $order_no, _("View the GL &Journal Entries for this Invoice")), 0, 1);
			if ((isset($_GET['Type']) && $_GET['Type'] == 1)) {
				submenu_option(_("Enter a &New Template Invoice"), "/sales/inquiry/sales_orders_view.php?InvoiceTemplates=Yes");
			}
			else {
				submenu_option(_("Enter a &New Direct Invoice"), "/sales/sales_order_entry.php?NewInvoice=0");
			}
			hyperlink_params("/sales/customer_payments.php", _("Apply a customer payment"));
			if ($_GET['AddedDI'] && isset($_SESSION['wa_global_customer_id']) && $row == false) {
				echo "<div style='text-align:center;'><iframe  style='margin:0 auto; border-width:0;' src='/sales/customer_payments.php?frame=1' width='80%' height='475' scrolling='auto' frameborder='0'></iframe> </div>";
			}
		}
		JS::set_focus('prtopt');
		//	UploadHandler::insert($order_no);
		ui_view::display_footer_exit();
	}

	//-----------------------------------------------------------------------------
	function copy_to_cart()
	{
		$cart                   = &$_SESSION['Items'];
		$cart->reference        = $_POST['ref'];
		$cart->Comments         = $_POST['Comments'];
		$cart->document_date    = $_POST['OrderDate'];
		$cart->due_date         = $_POST['delivery_date'];
		$cart->cust_ref         = $_POST['cust_ref'];
		$cart->freight_cost     = input_num('freight_cost');
		$cart->deliver_to       = $_POST['deliver_to'];
		$cart->delivery_address = $_POST['delivery_address'];
		$cart->name             = $_POST['name'];
		$cart->phone            = $_POST['phone'];
		$cart->Location         = $_POST['Location'];
		$cart->ship_via         = $_POST['ship_via'];
		if (isset($_POST['email'])) {
			$cart->email = $_POST['email'];
		}
		else {
			$cart->email = '';
		}
		if (isset($_POST['salesman'])) {
			$cart->salesman = $_POST['salesman'];
		}
		$cart->customer_id = $_POST['customer_id'];
		$cart->Branch      = $_POST['branch_id'];
		$cart->sales_type  = $_POST['sales_type'];
		// POS
		if ($cart->trans_type != ST_SALESORDER && $cart->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
			$cart->dimension_id  = $_POST['dimension_id'];
			$cart->dimension2_id = $_POST['dimension2_id'];
		}
	}

	//-----------------------------------------------------------------------------
	function copy_from_cart()
	{
		$cart                      = &$_SESSION['Items'];
		$_POST['ref']              = $cart->reference;
		$_POST['Comments']         = $cart->Comments;
		$_POST['OrderDate']        = $cart->document_date;
		$_POST['delivery_date']    = $cart->due_date;
		$_POST['cust_ref']         = $cart->cust_ref;
		$_POST['freight_cost']     = price_format($cart->freight_cost);
		$_POST['deliver_to']       = $cart->deliver_to;
		$_POST['delivery_address'] = $cart->delivery_address;
		$_POST['name']             = $cart->name;
		$_POST['phone']            = $cart->phone;
		$_POST['Location']         = $cart->Location;
		$_POST['ship_via']         = $cart->ship_via;
		$_POST['customer_id']      = $cart->customer_id;
		$_POST['branch_id']        = $cart->Branch;
		$_POST['sales_type']       = $cart->sales_type;
		$_POST['salesman']         = $cart->salesman;
		if ($cart->trans_type != ST_SALESORDER && $cart->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt
			$_POST['dimension_id']  = $cart->dimension_id;
			$_POST['dimension2_id'] = $cart->dimension2_id;
		}
		$_POST['cart_id'] = $cart->cart_id;
	}

	//--------------------------------------------------------------------------------
	function line_start_focus()
	{
		$Ajax = Ajax::instance();
		$Ajax->activate('items_table');
		JS::set_focus('_stock_id_edit');
	}

	//--------------------------------------------------------------------------------
	function can_process()
	{
		if (!get_post('customer_id')) {
			ui_msgs::display_error(_("There is no customer selected."));
			JS::set_focus('customer_id');
			return false;
		}
		if (!get_post('branch_id')) {
			ui_msgs::display_error(_("This customer has no branch defined."));
			JS::set_focus('branch_id');
			return false;
		}
		if (!Dates::is_date($_POST['OrderDate'])) {
			ui_msgs::display_error(_("The entered date is invalid."));
			JS::set_focus('OrderDate');
			return false;
		}
		if ($_SESSION['Items']->trans_type != ST_SALESORDER && $_SESSION['Items']->trans_type != ST_SALESQUOTE && !Dates::is_date_in_fiscalyear($_POST['OrderDate'])) {
			ui_msgs::display_error(_("The entered date is not in fiscal year"));
			JS::set_focus('OrderDate');
			return false;
		}
		if (count($_SESSION['Items']->line_items) == 0) {
			ui_msgs::display_error(_("You must enter at least one non empty item line."));
			JS::set_focus('AddItem');
			return false;
		}
		if ($_SESSION['Items']->trans_no == 0 && !empty($_POST['cust_ref']) && !$_SESSION['Items']->check_cust_ref($_POST['cust_ref'])) {
			ui_msgs::display_error(_("This customer already has a purchase order with this number."));
			JS::set_focus('cust_ref');
			return false;
		}
		if (strlen($_POST['deliver_to']) <= 1) {
			ui_msgs::display_error(_("You must enter the person or company to whom delivery should be made to."));
			JS::set_focus('deliver_to');
			return false;
		}
		if (strlen($_POST['delivery_address']) <= 1) {
			ui_msgs::display_error(_("You should enter the street address in the box provided. Orders cannot be accepted without a valid street address."));
			JS::set_focus('delivery_address');
			return false;
		}
		if ($_POST['freight_cost'] == "") {
			$_POST['freight_cost'] = price_format(0);
		}
		if (!Validation::is_num('freight_cost', 0)) {
			ui_msgs::display_error(_("The shipping cost entered is expected to be numeric."));
			JS::set_focus('freight_cost');
			return false;
		}
		if (!Dates::is_date($_POST['delivery_date'])) {
			if ($_SESSION['Items']->trans_type == ST_SALESQUOTE) {
				ui_msgs::display_error(_("The Valid date is invalid."));
			}
			else {
				ui_msgs::display_error(_("The delivery date is invalid."));
			}
			JS::set_focus('delivery_date');
			return false;
		}
		//if (Dates::date1_greater_date2($_SESSION['Items']->document_date, $_POST['delivery_date'])) {
		if (Dates::date1_greater_date2($_POST['OrderDate'], $_POST['delivery_date'])) {
			if ($_SESSION['Items']->trans_type == ST_SALESQUOTE) {
				ui_msgs::display_error(_("The requested valid date is before the date of the quotation."));
			}
			else {
				ui_msgs::display_error(_("The requested delivery date is before the date of the order."));
			}
			JS::set_focus('delivery_date');
			return false;
		}
		if ($_SESSION['Items']->trans_type == ST_SALESORDER && strlen($_POST['name']) < 1) {
			ui_msgs::display_error(_("You must enter a Person Ordering name."));
			JS::set_focus('name');
			return false;
		}
		if (!Refs::is_valid($_POST['ref'])) {
			ui_msgs::display_error(_("You must enter a reference."));
			JS::set_focus('ref');
			return false;
		}
		while ($_SESSION['Items']->trans_no == 0 && !is_new_reference($_POST['ref'], $_SESSION['Items']->trans_type)) {
			//ui_msgs::display_error(_("The entered reference is already in use."));
			//JS::set_focus('ref');
			//return false;
			$_POST['ref'] = Refs::get_next($_SESSION['Items']->trans_type);
		}
		return true;
	}

	//--------------- --------------------------------------------------------------
	if (isset($_POST['ProcessOrder']) && can_process()) {
		copy_to_cart();
		$modified = ($_SESSION['Items']->trans_no != 0);
		$so_type  = $_SESSION['Items']->so_type;
		$_SESSION['Items']->write(1);
		if (count(Errors::$messages)) { // abort on failure or error messages are lost
			$Ajax->activate('_page_body');
			ui_view::display_footer_exit();
		}
		$_SESSION['order_no'] = $trans_no = key($_SESSION['Items']->trans_no);
		$trans_type           = $_SESSION['Items']->trans_type;
		Dates::new_doc_date($_SESSION['Items']->document_date);
		$_SESSION['wa_global_customer_id'] = $_SESSION['Items']->customer_id;
		processing_end();
		$_SESSION['Jobsboard'] = new Sales_Order($trans_type, $_SESSION['order_no']);
		if ($modified) {
			if ($trans_type == ST_SALESQUOTE) {
				meta_forward($_SERVER['PHP_SELF'], "UpdatedQU=$trans_no");
			}
			else {
				meta_forward("/jobsboard/jobsboard/addjob/UpdatedID/$trans_no/$so_type", "");
			}
		}
		elseif ($trans_type == ST_SALESORDER) {
			meta_forward("/jobsboard/jobsboard/addjob/AddedID/$trans_no/$so_type", "");
		}
		elseif ($trans_type == ST_SALESQUOTE) {
			meta_forward($_SERVER['PHP_SELF'], "AddedQU=$trans_no");
		}
		elseif ($trans_type == ST_SALESINVOICE) {
			meta_forward($_SERVER['PHP_SELF'], "AddedDI=$trans_no&Type=" . ST_SALESINVOICE);
		}
		else {
			meta_forward($_SERVER['PHP_SELF'], "AddedDN=$trans_no&Type=$so_type");
		}
	}
	if (isset($_POST['update'])) {
		$Ajax->activate('items_table');
	}
	//--------------------------------------------------------------------------------
	function check_item_data()
	{
		if (!CurrentUser::instance()->can_access('SA_SALESCREDIT') && (!Validation::is_num('qty', 0) || !Validation::is_num('Disc', 0, 100))) {
			ui_msgs::display_error(_("The item could not be updated because you are attempting to set the quantity ordered to less than 0, or the discount percent to more than 100."));
			JS::set_focus('qty');
			return false;
		}
		elseif (!Validation::is_num('price', 0)) {
			ui_msgs::display_error(_("Price for item must be entered and can not be less than 0"));
			JS::set_focus('price');
			return false;
		}
		elseif (!CurrentUser::instance()->can_access('SA_SALESCREDIT') && isset($_POST['LineNo']) && isset($_SESSION['Items']->line_items[$_POST['LineNo']])
		 && !Validation::is_num(
			 'qty',
			 $_SESSION['Items']->line_items[$_POST['LineNo']]->qty_done
		 )
		) {
			JS::set_focus('qty');
			ui_msgs::display_error(_("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively."));
			return false;
		} // Joe Hunt added 2008-09-22 -------------------------
		elseif ($_SESSION['Items']->trans_type != ST_SALESORDER && $_SESSION['Items']->trans_type != ST_SALESQUOTE && !SysPrefs::allow_negative_stock() && is_inventory_item($_POST['stock_id'])) {
			$qoh = get_qoh_on_date($_POST['stock_id'], $_POST['Location'], $_POST['OrderDate']);
			if (input_num('qty') > $qoh) {
				$stock = get_item($_POST['stock_id']);
				ui_msgs::display_error(
					_("The delivery cannot be processed because there is an insufficient quantity for item:") . " " . $stock['stock_id'] . " - " . $stock['description'] . " - " . _("Quantity On Hand") . " = " . number_format2(
						$qoh,
						get_qty_dec($_POST['stock_id'])
					)
				);
				return false;
			}
			return true;
		}
		return true;
	}

	//--------------------------------------------------------------------------------
	function handle_update_item()
	{
		if ($_POST['UpdateItem'] != '' && check_item_data()) {
			$_SESSION['Items']->update_cart_item($_POST['LineNo'], input_num('qty'), input_num('price'), input_num('Disc') / 100, $_POST['description']);
		}
		line_start_focus();
	}

	//--------------------------------------------------------------------------------
	function handle_delete_item($line_no)
	{
		if ($_SESSION['Items']->some_already_delivered($line_no) == 0) {
			$_SESSION['Items']->remove_from_cart($line_no);
		}
		else {
			ui_msgs::display_error(_("This item cannot be deleted because some of it has already been delivered."));
		}
		line_start_focus();
	}

	//--------------------------------------------------------------------------------
	function handle_new_item()
	{
		if (!check_item_data()) {
			return;
		}
		add_to_order($_SESSION['Items'], $_POST['stock_id'], input_num('qty'), input_num('price'), input_num('Disc') / 100, $_POST['description']);
		$_POST['_stock_id_edit'] = $_POST['stock_id'] = "";
		line_start_focus();
	}

	//--------------------------------------------------------------------------------
	function handle_cancel_order()
	{
		$Ajax = Ajax::instance();
		if ($_SESSION['Items']->trans_type == ST_CUSTDELIVERY) {
			ui_msgs::display_notification(_("Direct delivery entry has been cancelled as requested."), 1);
			submenu_option(_("Enter a New Sales Delivery"), "/sales/sales_order_entry.php?NewDelivery=1");
		}
		elseif ($_SESSION['Items']->trans_type == ST_SALESINVOICE) {
			ui_msgs::display_notification(_("Direct invoice entry has been cancelled as requested."), 1);
			submenu_option(_("Enter a New Sales Invoice"), "/sales/sales_order_entry.php?NewInvoice=1");
		}
		else {
			if ($_SESSION['Items']->trans_no != 0) {
				if ($_SESSION['Items']->trans_type == ST_SALESORDER && sales_order_has_deliveries(key($_SESSION['Items']->trans_no))) {
					ui_msgs::display_error(_("This order cannot be cancelled because some of it has already been invoiced or dispatched. However, the line item quantities may be modified."));
				}
				else {
					$trans_no   = key($_SESSION['Items']->trans_no);
					$trans_type = $_SESSION['Items']->trans_type;
					if (!isset($_GET['RemovedID'])) {
						delete_sales_order($trans_no, $trans_type);
						meta_forward("/jobsboard/jobsboard/removejob/RemovedID/$trans_no/$trans_type", "");
					}
				}
			}
			else {
				processing_end();
				meta_forward('/index.php', 'application=orders');
			}
		}
		$Ajax->activate('_page_body');
		processing_end();
		ui_view::display_footer_exit();
	}

	//------------------------------------------------------- -------------------------
	function create_cart($type, $trans_no)
	{
		processing_start();
		$doc_type = $type;
		if (isset($_GET['NewQuoteToSalesOrder'])) {
			$trans_no           = $_GET['NewQuoteToSalesOrder'];
			$doc                = new Sales_Order(ST_SALESQUOTE, $trans_no);
			$doc->trans_no      = 0;
			$doc->trans_type    = ST_SALESORDER;
			$doc->reference     = Refs::get_next($doc->trans_type);
			$doc->document_date = $doc->due_date = Dates::new_doc_date();
			$doc->Comments      = $doc->Comments . "\n\n" . _("Sales Quotation") . " # " . $trans_no;
			$_SESSION['Items']  = $doc;
		} elseif (isset($_Get['CloneOrder'])) {
			$trans_no           = $_GET['CloneOrder'];
			$doc                = new Sales_Order(ST_SALESORDER, $trans_no);
			$doc->trans_no      = 0;
			$doc->trans_type    = ST_SALESORDER;
			$doc->reference     = Refs::get_next($doc->trans_type);
			$doc->document_date = $doc->due_date = Dates::new_doc_date();
			foreach (
				$doc->line_items as $line_no => $line
			) {
				$line->qty_done = $line->qty_dispatched = 0;
			}
			$_SESSION['Items'] = $doc;
		}
		elseif (isset($_GET['NewRemoteToSalesOrder'])) {
			$_SESSION['Items'] = $_SESSION['remote_order'];
			unset($_SESSION['remote_order']);
		}
		elseif ($type != ST_SALESORDER && $type != ST_SALESQUOTE && $trans_no != 0) { // this is template
			$doc_type           = ST_SALESORDER;
			$doc                = new Sales_Order(ST_SALESORDER, array($trans_no));
			$doc->trans_type    = $type;
			$doc->trans_no      = 0;
			$doc->document_date = Dates::new_doc_date();
			if ($type == ST_SALESINVOICE) {
				$doc->due_date = get_invoice_duedate($doc->customer_id, $doc->document_date);
				$doc->pos      = user_pos();
				$pos           = get_sales_point($doc->pos);
				$doc->pos      = -1;
			}
			else {
				$doc->due_date = $doc->document_date;
			}
			$doc->reference = Refs::get_next($doc->trans_type);
			//$doc->Comments='';
			foreach (
				$doc->line_items as $line_no => $line
			) {
				$doc->line_items[$line]->qty_done = 0;
			}
			$_SESSION['Items'] = $doc;
		}
		else {
			$_SESSION['Items'] = new Sales_Order($type, array($trans_no));
		}
		copy_from_cart();
	}

	//--------------------------------------------------------------------------------
	if (isset($_POST['CancelOrder'])) {
		handle_cancel_order();
	}
	$id = find_submit('Delete');
	if ($id != -1) {
		handle_delete_item($id);
	}
	if (isset($_POST['UpdateItem'])) {
		handle_update_item();
	}
	if (isset($_POST['discountall'])) {
		if (!is_numeric($_POST['_discountall'])) {
			ui_msgs::display_error(_("Discount must be a number"));
		} elseif ($_POST['_discountall'] < 0 || $_POST['_discountall'] > 100) {
			ui_msgs::display_error(_("Discount percentage must be between 0-100"));
		} else {
			$_SESSION['Items']->discount_all($_POST['_discountall'] / 100);
		}
		$Ajax->activate('_page_body');
	}
	if (isset($_POST['AddItem'])) {
		handle_new_item();
	}
	if (isset($_POST['CancelItemChanges'])) {
		line_start_focus();
	}
	//--------------------------------------------------------------------------------
	Validation::check(Validation::STOCK_ITEMS, _("There are no inventory items defined in the system."));
	Validation::check(Validation::BRANCHES_ACTIVE, _("There are no customers, or there are no customers with branches. Please define customers and customer branches."));
	if (Input::session('Items', Input::OBJECT) && Input::session('Items')->trans_type == ST_SALESINVOICE) {
		$idate           = _("Invoice Date:");
		$orderitems      = _("Sales Invoice Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Invoice");
		$cancelorder     = _("Cancel Invoice");
		$porder          = _("Place Invoice");
	} elseif (Input::session('Items', Input::OBJECT) && $_SESSION['Items']->trans_type == ST_CUSTDELIVERY) {
		$idate           = _("Delivery Date:");
		$orderitems      = _("Delivery Note Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Dispatch");
		$cancelorder     = _("Cancel Delivery");
		$porder          = _("Place Delivery");
	}
	elseif (Input::session('Items', Input::OBJECT) && $_SESSION['Items']->trans_type == ST_SALESQUOTE) {
		$idate           = _("Quotation Date:");
		$orderitems      = _("Sales Quotation Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Quotation");
		$cancelorder     = _("Cancel Quotation");
		$porder          = _("Place Quotation");
		$corder          = _("Commit Quotations Changes");
	}
	else {
		$idate           = _("Order Date:");
		$orderitems      = _("Sales Order Items");
		$deliverydetails = _("Enter Delivery Details and Confirm Order");
		$cancelorder     = _("Cancel Order");
		$porder          = _("Place Order");
		$corder          = _("Commit Order Changes");
	}
	start_form();
	hidden('cart_id');
	$customer_error = display_order_header($_SESSION['Items'], ($_SESSION['Items']->any_already_delivered() == 0), $idate);
	if ($customer_error == "") {
		start_table(Config::get('tables_style'), 10);
		echo "
<tr>
    <td>";
		display_order_summary($orderitems, $_SESSION['Items'], true);
		echo "
    </td>
</tr>";
		echo "
<tr>
    <td>";
		display_delivery_details($_SESSION['Items']);
		echo "
    </td>
</tr>";
		end_table(1);
		if ($_SESSION['Items']->trans_no == 0) {
			submit_center_first('ProcessOrder', $porder, _('Check entered data and save document'), 'default');
		}
		else {
			submit_center_first('ProcessOrder', $corder, _('Validate changes and update document'), 'default');
		}
		submit_js_confirm('CancelOrder', _('You are about to void this Document.\nDo you want to continue?'));
		submit_center_last('CancelOrder', $cancelorder, _('Cancels document entry or removes sales order when editing an old document'));
		if (isset($_GET['ModifyOrderNumber']) && is_numeric($_GET['ModifyOrderNumber'])) {
			//UploadHandler::insert($_GET['ModifyOrderNumber']);
		}
	}
	else {
		ui_msgs::display_error($customer_error);
	}
	end_form();
	JS::onUnload('Are you sure you want to leave without commiting changes?');
	Item::addEditDialog();
	end_page();
	unset($_SESSION['order_no']);
