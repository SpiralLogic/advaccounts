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
	$page_security = 'SA_PURCHASEORDER';
	$path_to_root = "..";
	$js = '';
	include_once($_SERVER['DOCUMENT_ROOT'] . "/purchasing/includes/po_class.inc");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
	include_once(APP_PATH . "/purchasing/includes/purchasing_ui.inc");
	include_once(APP_PATH . "/reporting/includes/reporting.inc");
	if (Config::get('ui.windows.popups')) {
		$js .= get_js_open_window(900, 500);
	}

	if (isset($_GET['ModifyOrderNumber'])) {
		page(_($help_context = "Modify Purchase Order #") . $_GET['ModifyOrderNumber'], false, false, "", $js);
	}
	else {
		page(_($help_context = "Purchase Order Entry"), false, false, "", $js);
	}
	//---------------------------------------------------------------------------------------------------
	check_db_has_suppliers(_("There are no suppliers defined in the system."));
	check_db_has_purchasable_items(_("There are no purchasable inventory items defined in the system."));
	//---------------------------------------------------------------------------------------------------------------
	if (isset($_GET['AddedID'])) {
		$order_no = $_GET['AddedID'];
		$trans_type = ST_PURCHORDER;
		$supplier = new Supplier($_SESSION['wa_global_supplier_id']);

		if (!isset($_GET['Updated'])) {
			display_notification_centered(_("Purchase Order: ".$_SESSION['history'][ST_PURCHORDER]." has been entered"));
		}
		else {
			display_notification_centered(_("Purchase Order: ".$_SESSION['history'][ST_PURCHORDER]." has been updated") . " #$order_no");
		}
	  unset($_SESSION['PO']);
		display_note(get_trans_view_str($trans_type, $order_no, _("&View this order"),false,'button'), 0, 1);
		display_note(print_document_link($order_no, _("&Print This Order"), true, $trans_type), 0, 1);
		submenu_button(_("&Edit This Order"), "/purchasing/po_entry_items.php?ModifyOrderNumber=$order_no");

		submenu_email(_("Email This Order"), $trans_type, $order_no, null, $supplier->getEmailAddresses(), 1);

		hyperlink_button("/purchasing/po_receive_items.php", _("&Receive Items on this PO"), "PONumber=$order_no");
		hyperlink_button($_SERVER['PHP_SELF'], _("&New Purchase Order"), "NewOrder=yes");
		hyperlink_no_params("/purchasing/inquiry/po_search.php", _("&Outstanding Purchase Orders"),true,true);
		display_footer_exit();
	}
	//--------------------------------------------------------------------------------------------------
	function copy_from_cart() {
		$_POST['supplier_id'] = $_SESSION['PO']->supplier_id;
		$_POST['OrderDate'] = $_SESSION['PO']->orig_order_date;
		$_POST['Requisition'] = $_SESSION['PO']->requisition_no;
		$_POST['ref'] = $_SESSION['PO']->reference;
		$_POST['Comments'] = $_SESSION['PO']->Comments;
		$_POST['StkLocation'] = $_SESSION['PO']->Location;
		$_POST['delivery_address'] = $_SESSION['PO']->delivery_address;
		$_POST['freight'] = $_SESSION['PO']->freight;
		$_POST['salesman'] = $_SESSION['PO']->salesman;
	}

	function copy_to_cart() {
		$_SESSION['PO']->supplier_id = $_POST['supplier_id'];
		$_SESSION['PO']->orig_order_date = $_POST['OrderDate'];
		$_SESSION['PO']->reference = $_POST['ref'];
		$_SESSION['PO']->requisition_no = $_POST['Requisition'];
		$_SESSION['PO']->Comments = $_POST['Comments'];
		$_SESSION['PO']->Location = $_POST['StkLocation'];
		$_SESSION['PO']->delivery_address = $_POST['delivery_address'];
		$_SESSION['PO']->freight = $_POST['freight'];
		$_SESSION['PO']->salesman = $_POST['salesman'];
	}

	//--------------------------------------------------------------------------------------------------
	function line_start_focus() {
		global $Ajax;
		$Ajax->activate('items_table');
		set_focus('_stock_id_edit');
	}

	//--------------------------------------------------------------------------------------------------
	function unset_form_variables() {
		unset($_POST['stock_id']);
		unset($_POST['qty']);
		unset($_POST['price']);
		unset($_POST['req_del_date']);
	}

	//---------------------------------------------------------------------------------------------------
	function handle_delete_item($line_no) {
		if ($_SESSION['PO']->some_already_received($line_no) == 0) {
			$_SESSION['PO']->remove_from_order($line_no);
			unset_form_variables();
		}
		else {
			display_error(_("This item cannot be deleted because some of it has already been received."));
		}
		line_start_focus();
	}

	//---------------------------------------------------------------------------------------------------
	function handle_cancel_po() {
		global $path_to_root;
		//need to check that not already dispatched or invoiced by the supplier
		if (($_SESSION['PO']->order_no != 0) && $_SESSION['PO']->any_already_received() == 1) {
			display_error(_("This order cannot be cancelled because some of it has already been received.") . "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
			return;
		}
		if ($_SESSION['PO']->order_no != 0) {
			delete_po($_SESSION['PO']->order_no);
		}
		else {
			unset($_SESSION['PO']);
			meta_forward($path_to_root . '/index.php', 'application=AP');
		}
		$_SESSION['PO']->clear_items();
		$_SESSION['PO'] = new purch_order;
		display_notification(_("This purchase order has been cancelled."));
		hyperlink_params("/purchasing/po_entry_items.php", _("Enter a new purchase order"), "NewOrder=Yes");

		echo "<br>";
		end_page();
		exit;
	}

	//---------------------------------------------------------------------------------------------------
	function check_data() {
		$dec = get_qty_dec($_POST['stock_id']);
		$min = 1 / pow(10, $dec);
		if (!check_num('qty', $min)) {
			$min = number_format2($min, $dec);
			display_error(_("The quantity of the order item must be numeric and not less than ") . $min);
			set_focus('qty');
			return false;
		}
		if (!check_num('price', 0)) {
			display_error(_("The price entered must be numeric and not less than zero."));
			set_focus('price');
			return false;
		}
		if (!check_num('discount', 0, 100)) {
			display_error(_("Discount percent can not be less than 0 or more than 100."));
			set_focus('discount');
			return false;
		}
		if (!is_date($_POST['req_del_date'])) {
			display_error(_("The date entered is in an invalid format."));
			set_focus('req_del_date');
			return false;
		}
		return true;
	}

	//---------------------------------------------------------------------------------------------------
	function handle_update_item() {
		$allow_update = check_data();
		if ($allow_update) {
			if ($_SESSION['PO']->line_items[$_POST['line_no']]->qty_inv > input_num('qty') || $_SESSION['PO']->line_items[$_POST['line_no']]->qty_received > input_num('qty')) {
				display_error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received.  This is prohibited.") . "<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
				set_focus('qty');
				return;
			}
			$_SESSION['PO']->update_order_item($_POST['line_no'], input_num('qty'), input_num('price'), $_POST['req_del_date'], $_POST['description'], $_POST['discount'] / 100);
			unset_form_variables();
		}
		line_start_focus();
	}

	//---------------------------------------------------------------------------------------------------
	//---------------------------------------------------------------------------------------------------
	function handle_add_new_item() {
		$allow_update = check_data();
		if ($allow_update == true) {
			if (count($_SESSION['PO']->line_items) > 0) {
				/*
													  foreach ($_SESSION['PO']->line_items as $order_item)
													  {

														 /* do a loop round the items on the order to see that the item
														 is not already on this order
														   if (($order_item->stock_id == $_POST['stock_id']) &&
															  ($order_item->Deleted == false))
														   {
															$allow_update = false;
															display_error(_("The selected item is already on this order."));
														 }
													  } /* end of the foreach loop to look for pre-existing items of the same code */
			}
			if ($allow_update == true) {
				$sql = "SELECT long_description as description , units, mb_flag
				FROM stock_master WHERE stock_id = " . db_escape($_POST['stock_id']);
				$result = db_query($sql, "The stock details for " . $_POST['stock_id'] . " could not be retrieved");
				if (db_num_rows($result) == 0) {
					$allow_update = false;
				}
				if ($allow_update) {
					$myrow = db_fetch($result);
					$_SESSION['PO']->add_to_order($_POST['line_no'], $_POST['stock_id'], input_num('qty'), $_POST['description'], input_num('price'), $myrow["units"], $_POST['req_del_date'], 0, 0,
					                              $_POST['discount'] / 100);
					unset_form_variables();
					$_POST['stock_id'] = "";
				}
				else {
					display_error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
				}

			} /* end of if not already on the order and allow input was true*/
		}
		line_start_focus();
	}

	//---------------------------------------------------------------------------------------------------
	function can_commit() {
		global $Refs;
		if (!get_post('supplier_id')) {
			display_error(_("There is no supplier selected."));
			set_focus('supplier_id');
			return false;
		}
		if (!is_date($_POST['OrderDate'])) {
			display_error(_("The entered order date is invalid."));
			set_focus('OrderDate');
			return false;
		}
		if (get_post('delivery_address') == '') {
			display_error(_("There is no delivery address specified."));
			set_focus('delivery_address');
			return false;
		}
		if (!check_num('freight', 0)) {
			display_error(_("The freight entered must be numeric and not less than zero."));
			set_focus('freight');
			return false;
		}
		if (get_post('StkLocation') == '') {
			display_error(_("There is no location specified to move any items into."));
			set_focus('StkLocation');
			return false;
		}
		if ($_SESSION['PO']->order_has_items() == false) {
			display_error(_("The order cannot be placed because there are no lines entered on this order."));
			return false;
		}
		if (!$_SESSION['PO']->order_no) {
			if (!$Refs->is_valid(get_post('ref'))) {
				display_error(_("There is no reference entered for this purchase order."));
				set_focus('ref');
				return false;
			}
			while (!is_new_reference($_POST['ref'], ST_PURCHORDER)) {
				//            if (!is_new_reference(get_post('ref'), ST_PURCHORDER)) {
				//display_error(_("The entered reference is already in use."));
				//set_focus('ref');
				//return false;
				$_POST['ref'] = $Refs->get_next(ST_PURCHORDER);
			}
		}
		return true;
	}

	//---------------------------------------------------------------------------------------------------
	function handle_commit_order() {
		if (can_commit()) {
			copy_to_cart();
			if ($_SESSION['PO']->order_no == 0) {
				/*its a new order to be inserted */
				$order_no = add_po($_SESSION['PO']);
				new_doc_date($_SESSION['PO']->orig_order_date);
				$_SESSION['history'][ST_PURCHORDER] = $_SESSION['PO']->reference;
				unset($_SESSION['PO']);
				meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no");
			}
			else {
				/*its an existing order need to update the old order info */
				$order_no = update_po($_SESSION['PO']);
				$_SESSION['history'][ST_PURCHORDER] = $_SESSION['PO']->reference;

				meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no&Updated=1");
			}
		}
	}

	//---------------------------------------------------------------------------------------------------
	$id = find_submit('Delete');
	if ($id != -1) {
		handle_delete_item($id);
	}
	if (isset($_POST['Commit'])) {
		handle_commit_order();
	}
	if (isset($_POST['UpdateLine'])) {
		handle_update_item();
	}
	if (isset($_POST['EnterLine'])) {
		handle_add_new_item();
	}
	if (isset($_POST['CancelOrder'])) {
		handle_cancel_po();
	}
	if (isset($_POST['CancelUpdate'])) {
		unset_form_variables();
	}
	if (isset($_GET['ModifyOrderNumber']) && $_GET['ModifyOrderNumber'] != "") {
		create_new_po();
		$_SESSION['PO']->order_no = $_GET['ModifyOrderNumber'];
		/*read in all the selected order into the Items cart  */
		read_po($_SESSION['PO']->order_no, $_SESSION['PO']);
		copy_from_cart();
	}
	if (isset($_POST['CancelUpdate']) || isset($_POST['UpdateLine'])) {
		line_start_focus();
	}
	if (isset($_GET['NewOrder'])) {
		create_new_po();
		if (isset($_GET['UseOrder']) && $_GET['UseOrder'] && isset($_SESSION['Items']->line_items)) {
			foreach ($_SESSION['Items']->line_items as $line_no => $line_item) {
				$sql = "SELECT purch_data.price,purch_data.supplier_id
		FROM purch_data INNER JOIN suppliers
		ON purch_data.supplier_id=suppliers.supplier_id
		WHERE stock_id = " . db_escape($line_item->stock_id) . ' ORDER BY price';
				$result = db_query($sql);
				$myrow = array();
				if (db_num_rows($result) > 0) {
					if (db_num_rows($result) == 1) {
						$myrow[] = db_fetch($result, 'pricing');
					}
					else {
						$myrow = db_fetch($result, 'pricing');
					}
					if (isset($po_lines[$myrow[0]['supplier_id']])) {
						$po_lines[$myrow[0]['supplier_id']]++;
					}
					else {
						$po_lines[$myrow[0]['supplier_id']] = 1;
					}
				}
				$_SESSION['PO']->add_to_order($line_no, $line_item->stock_id, $line_item->quantity, $line_item->description, price_decimal_format($myrow[0]['price'], $dec2), $line_item->units,
				                              add_days(Today(), 10), 0, 0, 0);

			}
			arsort($po_lines);
			$_SESSION['wa_global_supplier_id'] = key($po_lines);
			if ($_GET['DS']) {
				$item_info = get_item('DS');
				$_POST['StkLocation'] = 'DRP';
				$_SESSION['PO']->add_to_order(count($_SESSION['PO']->line_items), 'DS', 1, $item_info['long_description'], 0, '', add_days(Today(), 10), 0, 0, 0);
				$address = $_SESSION['Items']->customer_name . "\n";
				if (!empty($_SESSION['Items']->name) && $_SESSION['Items']->deliver_to == $_SESSION['Items']->customer_name) {
					$address .= $_SESSION['Items']->name . "\n";
				}
				elseif ($_SESSION['Items']->deliver_to != $_SESSION['Items']->customer_name) {
					$address .= $_SESSION['Items']->deliver_to . "\n";
				}
				if (!empty($_SESSION['Items']->phone)) {
					$address .= 'Ph:' . $_SESSION['Items']->phone . "\n";
				}
				$address .= $_SESSION['Items']->delivery_address;
				$_POST['delivery_address'] = $_SESSION['PO']->delivery_address = $address;
			}
		}

	}
	//---------------------------------------------------------------------------------------------------
	start_form();
	if ((isset($_GET['NewOrder']) && $_GET['NewOrder']) && (!isset($_GET['UseOrder']) || !$_GET['UseOrder'])) {
		echo "
<center>
    <iframe src='/purchasing/inquiry/po_search_completed.php?NFY=1&frame=1' width='90%' height='350' frameborder='0'></iframe>
</center>";
	}
	display_po_header($_SESSION['PO']);
	echo "<br>";
	display_po_items($_SESSION['PO']);
	start_table($table_style2);
	textarea_row(_("Memo:"), 'Comments', null, 70, 4);
	end_table(1);
	div_start('controls', 'items_table');
	if ($_SESSION['PO']->order_has_items()) {
		submit_center_first('CancelOrder', _("Delete This Order"));
		if ($_SESSION['PO']->order_no) {
			submit_center_last('Commit', _("Update Order"), '', 'default');
		}
		else {
			submit_center_last('Commit', _("Place Order"), '', 'default');
		}

	}
	else {
		submit_js_confirm('CancelOrder', _('You are about to void this Document.\nDo you want to continue?'));
		submit_center('CancelOrder', _("Delete This Order"), true, false, 'cancel');
	}
	div_end();
	//---------------------------------------------------------------------------------------------------
	end_form();
	JS::onUnload('Are you sure you want to leave without commiting changes?');

	Item::addEditDialog();

	if (isset($_SESSION['PO']->supplier_id)) {
		$supplier_details = $_SESSION['PO']->supplier_details;
		$content = '<div><span class="bold">Shipping Address:</span><br>' . $supplier_details['supp_address'] . '</br></br>
		<span class="bold">Mailing Address:</span><br>' . $supplier_details['address'] . '</br></br>
		<span class="bold">Phone: </span>' . $supplier_details['phone'] . '</br></br>
		<span class="bold">Phone2: </span>' . $supplier_details['phone2'] . '</br></br>
		<span class="bold">Fax: </span>' . $supplier_details['fax'] . '</br></br>
		<span class="bold">Contact: </span>' . $supplier_details['contact'] . '</br></br>
		<span class="bold">Email: </span><a href="mailto:' . $supplier_details['email'] . '">
		<span class="bold">Website: </span><a target="_new" href="http://' . $supplier_details['website'] . '">
		<span class="bold">Account #: </span>' . $supplier_details['supp_account_no'] . '</br></br></div>';
		$supp_details = new Dialog('Supplier Details:', 'supplier_details', $content);
		$supp_details->addOpenEvent("td[name=\"supplier_name\"]", 'click');
		$supp_details->addButton('Close', '$(this).dialog("close")');
		$supp_details->show();
	}

	end_page();

?>
