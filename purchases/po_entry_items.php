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
	/** @noinspection PhpIncludeInspection */
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_PURCHASEORDER;
	JS::open_window(900, 500);
	if (isset($_GET['ModifyOrder'])) {
		Page::start(_($help_context = "Modify Purchase Order #") . $_GET['ModifyOrder']);
	}
	else {
		Page::start(_($help_context = "Purchase Order Entry"));
	}
	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
	Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
	if (isset($_GET['AddedID'])) {
		$order_no = $_GET['AddedID'];
		$trans_type = ST_PURCHORDER;
		$supplier = new Creditor(Session::i()->supplier_id);
		if (!isset($_GET['Updated'])) {
			Errors::notice(_("Purchase Order: " . Session::i()->history[ST_PURCHORDER] . " has been entered"));
		}
		else {
			Errors::notice(_("Purchase Order: " . Session::i()->history[ST_PURCHORDER] . " has been updated"));
		}
		Display::note(GL_UI::trans_view($trans_type, $order_no, _("&View this order"), false, 'button'), 0, 1);
		Display::note(Reporting::print_doc_link($order_no, _("&Print This Order"), true, $trans_type), 0, 1);
		Display::submenu_button(_("&Edit This Order"), "/purchases/po_entry_items.php?ModifyOrder=$order_no");
		Reporting::email_link($order_no, _("Email This Order"), true, $trans_type, 'EmailLink', null, $supplier->getEmailAddresses(), 1);
		Display::link_button("/purchases/po_receive_items.php", _("&Receive Items on this PO"), "PONumber=$order_no");
		Display::link_button($_SERVER['PHP_SELF'], _("&New Purchase Order"), "NewOrder=yes");
		Display::link_no_params("/purchases/inquiry/po_search.php", _("&Outstanding Purchase Orders"), true, true);
		Page::footer_exit();
	}
	$order = Orders::session_get() ? : null;
	if (isset($_POST['CancelChanges'])) {
		$order_no = $order->trans_no;
		Orders::session_delete($_POST['order_id']);
		$order = create_order($order_no);
	}
	$id = find_submit(MODE_DELETE);
	if ($id != -1) {
		handle_delete_item($order, $id);
	}
	if (isset($_POST['Commit'])) {
		handle_commit_order($order);
	}
	if (isset($_POST['UpdateLine'])) {
		handle_update_item($order);
	}
	if (isset($_POST['EnterLine'])) {
		handle_add_new_item($order);
	}
	if (isset($_POST['CancelOrder'])) {
		handle_cancel_po($order);
	}
	if (isset($_POST['CancelUpdate'])) {
		unset_form_variables();
	}
	if (isset($_GET['ModifyOrder']) && $_GET['ModifyOrder'] != "") {
		$order = create_order($_GET['ModifyOrder']);
	}
	elseif (isset($_POST['CancelUpdate']) || isset($_POST['UpdateLine'])) {
		line_start_focus();
	}
	elseif (isset($_GET['NewOrder'])) {
		$order = create_order();
		if ((!isset($_GET['UseOrder']) || !$_GET['UseOrder']) && count($order->line_items) == 0) {
			echo "<div class='center'><iframe src='/purchases/inquiry/po_search_completed.php?" . LOC_NOT_FAXED_YET . "=1&frame=1' style='width:90%' height='350' frameborder='0'></iframe></div>";
		}
	}
	start_form();
	echo "<br>";
	hidden('order_id');
	$order->header();
	$order->display_items();
	start_table('tablestyle2');
	textarea_row(_("Memo:"), 'Comments', null, 70, 4);
	end_table(1);
	Display::div_start('controls', 'items_table');
	if ($order->order_has_items()) {
		submit_center_first('CancelOrder', _("Delete This Order"));
		submit_center_middle('CancelChanges', _("Cancel Changes"), _("Revert this document entry back to its former state."));
		if ($order->order_no) {
			submit_center_last('Commit', _("Update Order"), '', 'default');
		}
		else {
			submit_center_last('Commit', _("Place Order"), '', 'default');
		}
	}
	else {
		submit_js_confirm('CancelOrder', _('You are about to void this Document.\nDo you want to continue?'));
		submit_center_first('CancelOrder', _("Delete This Order"), true, false, ICON_DELETE);
		submit_center_middle('CancelChanges', _("Cancel Changes"), _("Revert this document entry back to its former state."));
	}
	Display::div_end();
	end_form();
	JS::onUnload('Are you sure you want to leave without commiting changes?');
	Item::addEditDialog();
	if (isset($order->supplier_id)) {
		Creditor::addInfoDialog("td[name=\"supplier_name\"]", $order->supplier_details['supplier_id']);
	}
	Page::end();
	/**
	 * @param $order
	 *
	 * @return \Purch_Order|\Sales_Order
	 */
	function copy_from_order($order) {
		if (!Input::get('UseOrder')) {
			$order = Purch_Order::check_edit_conflicts($order);
		}
		$_POST['supplier_id'] = $order->supplier_id;
		$_POST['OrderDate'] = $order->orig_order_date;
		$_POST['Requisition'] = $order->requisition_no;
		$_POST['ref'] = $order->reference;
		$_POST['Comments'] = $order->Comments;
		$_POST['StkLocation'] = $order->Location;
		$_POST['delivery_address'] = $order->delivery_address;
		$_POST['freight'] = $order->freight;
		$_POST['salesman'] = $order->salesman;
		$_POST['order_id'] = $order->order_id;
		return Orders::session_set($order);
	}

	function copy_to_order($order) {
		$order->supplier_id = $_POST['supplier_id'];
		$order->orig_order_date = $_POST['OrderDate'];
		$order->reference = $_POST['ref'];
		$order->requisition_no = $_POST['Requisition'];
		$order->Comments = $_POST['Comments'];
		$order->Location = $_POST['StkLocation'];
		$order->delivery_address = $_POST['delivery_address'];
		$order->freight = $_POST['freight'];
		$order->salesman = $_POST['salesman'];
	}

	function line_start_focus() {
		Ajax::i()->activate('items_table');
		JS::set_focus('_stock_id_edit');
	}

	function unset_form_variables() {
		unset($_POST['stock_id']);
		unset($_POST['qty']);
		unset($_POST['price']);
		unset($_POST['req_del_date']);
	}

	/**
	 * @param Purch_Order $order
	 * @param						 $line_no
	 */
	function handle_delete_item($order, $line_no) {
		if ($order->some_already_received($line_no) == 0) {
			$order->remove_from_order($line_no);
			unset_form_variables();
		}
		else {
			Errors::error(_("This item cannot be deleted because some of it has already been received."));
		}
		line_start_focus();
	}

	/**
	 * @param Purch_Order $order
	 *
	 * @return mixed
	 */
	function handle_cancel_po($order) {
		//need to check that not already dispatched or invoiced by the supplier
		if (($order->order_no != 0) && $order->any_already_received() == 1) {
			Errors::error(_("This order cannot be cancelled because some of it has already been received.") . "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
			return;
		}
		Orders::session_delete($order);
		if ($order->order_no != 0) {
			$order->delete();
		}
		else {
			Display::meta_forward('/index.php', 'application=Purchases');
		}
		Orders::session_delete($order);
		Errors::notice(_("This purchase order has been cancelled."));
		Display::link_params("/purchases/po_entry_items.php", _("Enter a new purchase order"), "NewOrder=Yes");
		echo "<br>";
		Page::end();
		exit;
	}

	/**
	 * @param int $order_no
	 *
	 * @return \Purch_Order|\Sales_Order
	 */
	function create_order($order_no = 0) {
		if (isset($_GET['UseOrder']) && $_GET['UseOrder'] && isset(Orders::session_get($_GET['UseOrder'])->line_items)) {
			$sales_order = Orders::session_get($_GET['UseOrder']);
			$order = new Purch_Order($order_no);
			$stock = $myrow = array();
			foreach ($sales_order->line_items as $line_item) {
				$stock[] = ' stock_id = ' . DB::escape($line_item->stock_id);
			}
			$sql = "SELECT AVG(price),supplier_id,COUNT(supplier_id) FROM purch_data WHERE " . implode(' OR ', $stock) . ' GROUP BY supplier_id ORDER BY AVG(price)';
			$result = DB::query($sql);
			$row = DB::fetch($result);
			$order->supplier_to_order($row['supplier_id']);
			foreach ($sales_order->line_items as $line_no => $line_item) {
				$order->add_to_order($line_no, $line_item->stock_id, $line_item->quantity, $line_item->description, 0, $line_item->units, Dates::add_days(Dates::Today(), 10), 0, 0, 0);
			}
			if (isset($_GET[LOC_DROP_SHIP])) {
				$item_info = Item::get('DS');
				$_POST['StkLocation'] = LOC_DROP_SHIP;
				$order->add_to_order(count($sales_order->line_items), 'DS', 1, $item_info['long_description'], 0, '',
														 Dates::add_days(Dates::Today(), 10), 0, 0, 0);
				$address = $sales_order->customer_name . "\n";
				if (!empty($sales_order->name) && $sales_order->deliver_to == $sales_order->customer_name) {
					$address .= $sales_order->name . "\n";
				}
				elseif ($sales_order->deliver_to != $sales_order->customer_name) {
					$address .= $sales_order->deliver_to . "\n";
				}
				if (!empty($sales_order->phone)) {
					$address .= 'Ph:' . $sales_order->phone . "\n";
				}
				$address .= $sales_order->delivery_address;
				$order->delivery_address = $address;
			}
			unset($_POST['order_id']);
		}
		else {
			$order = new Purch_Order($order_no);
		}
		$order = copy_from_order($order);
		return $order;
	}

	function check_data() {
		$dec = Item::qty_dec($_POST['stock_id']);
		$min = 1 / pow(10, $dec);
		if (!Validation::is_num('qty', $min)) {
			$min = Num::format($min, $dec);
			Errors::error(_("The quantity of the order item must be numeric and not less than ") . $min);
			JS::set_focus('qty');
			return false;
		}
		if (!Validation::is_num('price', 0)) {
			Errors::error(_("The price entered must be numeric and not less than zero."));
			JS::set_focus('price');
			return false;
		}
		if (!Validation::is_num('discount', 0, 100)) {
			Errors::error(_("Discount percent can not be less than 0 or more than 100."));
			JS::set_focus('discount');
			return false;
		}
		if (!Dates::is_date($_POST['req_del_date'])) {
			Errors::error(_("The date entered is in an invalid format."));
			JS::set_focus('req_del_date');
			return false;
		}
		return true;
	}

	/**
	 * @param Purch_Order $order
	 *
	 * @return mixed
	 */
	function handle_update_item($order) {
		$allow_update = check_data();
		if ($allow_update) {
			if ($order->line_items[$_POST['line_no']]->qty_inv > Validation::input_num('qty') || $order->line_items[$_POST['line_no']]->qty_received > Validation::input_num('qty')) {
				Errors::error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received. This is prohibited.") . "<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
				JS::set_focus('qty');
				return;
			}
			$order->update_order_item($_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), $_POST['req_del_date'], $_POST['description'], $_POST['discount'] / 100);
			unset_form_variables();
		}
		line_start_focus();
		return;
	}

	/**
	 * @param Purch_Order $order
	 */
	function handle_add_new_item($order) {
		$allow_update = check_data();
		if ($allow_update == true) {
			if ($allow_update == true) {
				$sql = "SELECT long_description as description , units, mb_flag
				FROM stock_master WHERE stock_id = " . DB::escape($_POST['stock_id']);
				$result = DB::query($sql, "The stock details for " . $_POST['stock_id'] . " could not be retrieved");
				if (DB::num_rows($result) == 0) {
					$allow_update = false;
				}
				if ($allow_update) {
					$myrow = DB::fetch($result);
					$order->add_to_order($_POST['line_no'], $_POST['stock_id'], Validation::input_num('qty'), $_POST['description'], Validation::input_num('price'), $myrow["units"], $_POST['req_del_date'], 0, 0, $_POST['discount'] / 100);
					unset_form_variables();
					$_POST['stock_id'] = "";
				}
				else {
					Errors::error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
				}
			} /* end of if not already on the order and allow input was true*/
		}
		line_start_focus();
	}

	/**
	 * @param Purch_Order $order
	 *
	 * @return bool
	 */
	function can_commit($order) {
		if (!$order) {
			Errors::error(_("You are not currently editing an order."));
			Page::footer_exit();
		}
		if (!get_post('supplier_id')) {
			Errors::error(_("There is no supplier selected."));
			JS::set_focus('supplier_id');
			return false;
		}
		if (!Dates::is_date($_POST['OrderDate'])) {
			Errors::error(_("The entered order date is invalid."));
			JS::set_focus('OrderDate');
			return false;
		}
		if (get_post('delivery_address') == '') {
			Errors::error(_("There is no delivery address specified."));
			JS::set_focus('delivery_address');
			return false;
		}
		if (!Validation::is_num('freight', 0)) {
			Errors::error(_("The freight entered must be numeric and not less than zero."));
			JS::set_focus('freight');
			return false;
		}
		if (get_post('StkLocation') == '') {
			Errors::error(_("There is no location specified to move any items into."));
			JS::set_focus('StkLocation');
			return false;
		}
		if ($order->order_has_items() == false) {
			Errors::error(_("The order cannot be placed because there are no lines entered on this order."));
			return false;
		}
		if (!$order->order_no) {
			if (!Ref::is_valid(get_post('ref'))) {
				Errors::error(_("There is no reference entered for this purchase order."));
				JS::set_focus('ref');
				return false;
			}
			if (!Ref::is_new($_POST['ref'], ST_PURCHORDER)) {
				$_POST['ref'] = Ref::get_next(ST_PURCHORDER);
			}
		}
		return true;
	}

	/**
	 * @param Purch_Order $order
	 */
	function handle_commit_order($order) {
		copy_to_order($order);
		if (can_commit($order)) {
			if ($order->order_no == 0) {
				/*its a new order to be inserted */
				$_SESSION['history'][ST_PURCHORDER] = $order->reference;
				$order_no = $order->add();
				Dates::new_doc_date($order->orig_order_date);
				Orders::session_delete($_POST['order_id']);
				Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no");
			}
			else {
				/*its an existing order need to update the old order info */
				$_SESSION['history'][ST_PURCHORDER] = $order->reference;
				$order_no = $order->update();
				Orders::session_delete($_POST['order_id']);
				Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no&Updated=1");
			}
		}
	}

?>
