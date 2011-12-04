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
	$page_security = 'SA_PURCHASEORDER';
	$js = '';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	if (isset($_GET['ModifyOrderNumber'])) {
		Page::start(_($help_context = "Modify Purchase Order #") . $_GET['ModifyOrderNumber']);
	} else {
		Page::start(_($help_context = "Purchase Order Entry"));
	}

	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
	Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."),
		STOCK_PURCHASED);

	if (isset($_GET['AddedID'])) {
		$order_no = $_GET['AddedID'];
		$trans_type = ST_PURCHORDER;
		$supplier = new Contacts_Supplier(Session::i()->supplier_id);
		if (!isset($_GET['Updated'])) {
			Errors::notice(_("Purchase Order: " . Session::i()->history[ST_PURCHORDER] . " has been entered"));
		} else {
			Errors::notice(_("Purchase Order: " . Session::i()->history[ST_PURCHORDER] . " has been updated") . " #$order_no");
		}
		unset($_SESSION['PO']);
		Display::note(GL_UI::trans_view($trans_type, $order_no, _("&View this order"), false, 'button'), 0, 1);
		Display::note(Reporting::print_doc_link($order_no, _("&Print This Order"), true, $trans_type), 0, 1);
		Display::submenu_button(_("&Edit This Order"), "/purchases/po_entry_items.php?ModifyOrderNumber=$order_no");
		Reporting::email_link($order_no, _("Email This Order"), true, $trans_type, 'EmailLink', null, $supplier->getEmailAddresses(),
			1);
		Display::link_button("/purchases/po_receive_items.php", _("&Receive Items on this PO"), "PONumber=$order_no");
		Display::link_button($_SERVER['PHP_SELF'], _("&New Purchase Order"), "NewOrder=yes");
		Display::link_no_params("/purchases/inquiry/po_search.php", _("&Outstanding Purchase Orders"), true, true);
		Page::footer_exit();
	}

	function copy_from_cart()
		{
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

	function copy_to_cart()
		{
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


	function line_start_focus()
		{
			$Ajax = Ajax::i();
			$Ajax->activate('items_table');
			JS::set_focus('_stock_id_edit');
		}


	function unset_form_variables()
		{
			unset($_POST['stock_id']);
			unset($_POST['qty']);
			unset($_POST['price']);
			unset($_POST['req_del_date']);
		}


	function handle_delete_item($line_no)
		{
			if ($_SESSION['PO']->some_already_received($line_no) == 0) {
				$_SESSION['PO']->remove_from_order($line_no);
				unset_form_variables();
			} else {
				Errors::error(_("This item cannot be deleted because some of it has already been received."));
			}
			line_start_focus();
		}


	function handle_cancel_po()
		{
			//need to check that not already dispatched or invoiced by the supplier
			if (($_SESSION['PO']->order_no != 0) && $_SESSION['PO']->any_already_received() == 1) {
				Errors::error(_("This order cannot be cancelled because some of it has already been received.") . "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
				return;
			}
			if ($_SESSION['PO']->order_no != 0) {
				Purch_Order::delete($_SESSION['PO']->order_no);
			} else {
				unset($_SESSION['PO']);
				Display::meta_forward('/index.php', 'application=Purchases');
			}
			$_SESSION['PO']->clear_items();
			$_SESSION['PO'] = new Purch_Order();
			Errors::notice(_("This purchase order has been cancelled."));
			Display::link_params("/purchases/po_entry_items.php", _("Enter a new purchase order"), "NewOrder=Yes");
			echo "<br>";
			end_page();
			exit;
		}


	function check_data()
		{
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


	function handle_update_item()
		{
			$allow_update = check_data();
			if ($allow_update) {
				if ($_SESSION['PO']->line_items[$_POST['line_no']]->qty_inv > Validation::input_num('qty') || $_SESSION['PO']->line_items[$_POST['line_no']]->qty_received > Validation::input_num('qty')) {
					Errors::error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received.  This is prohibited.") . "<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
					JS::set_focus('qty');
					return;
				}
				$_SESSION['PO']->update_order_item($_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), $_POST['req_del_date'],
					$_POST['description'], $_POST['discount'] / 100);
				unset_form_variables();
			}
			line_start_focus();
		}



	function handle_add_new_item()
		{
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
						$_SESSION['PO']->add_to_order($_POST['line_no'], $_POST['stock_id'], Validation::input_num('qty'), $_POST['description'],
							Validation::input_num('price'), $myrow["units"], $_POST['req_del_date'], 0, 0, $_POST['discount'] / 100);
						unset_form_variables();
						$_POST['stock_id'] = "";
					} else {
						Errors::error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
					}
				} /* end of if not already on the order and allow input was true*/
			}
			line_start_focus();
		}


	function can_commit()
		{
			if (!Display::get_post('supplier_id')) {
				Errors::error(_("There is no supplier selected."));
				JS::set_focus('supplier_id');
				return false;
			}
			if (!Dates::is_date($_POST['OrderDate'])) {
				Errors::error(_("The entered order date is invalid."));
				JS::set_focus('OrderDate');
				return false;
			}
			if (Display::get_post('delivery_address') == '') {
				Errors::error(_("There is no delivery address specified."));
				JS::set_focus('delivery_address');
				return false;
			}
			if (!Validation::is_num('freight', 0)) {
				Errors::error(_("The freight entered must be numeric and not less than zero."));
				JS::set_focus('freight');
				return false;
			}
			if (Display::get_post('StkLocation') == '') {
				Errors::error(_("There is no location specified to move any items into."));
				JS::set_focus('StkLocation');
				return false;
			}
			if ($_SESSION['PO']->order_has_items() == false) {
				Errors::error(_("The order cannot be placed because there are no lines entered on this order."));
				return false;
			}
			if (!$_SESSION['PO']->order_no) {
				if (!Ref::is_valid(Display::get_post('ref'))) {
					Errors::error(_("There is no reference entered for this purchase order."));
					JS::set_focus('ref');
					return false;
				}
				while (!Ref::is_new($_POST['ref'], ST_PURCHORDER)) {
					//            if (!Ref::is_new(Display::get_post('ref'), ST_PURCHORDER)) {
					//Errors::error(_("The entered reference is already in use."));
					//JS::set_focus('ref');
					//return false;
					$_POST['ref'] = Ref::get_next(ST_PURCHORDER);
				}
			}
			return true;
		}


	function handle_commit_order()
		{
			if (can_commit()) {
				copy_to_cart();
				if ($_SESSION['PO']->order_no == 0) {
					/*its a new order to be inserted */
					$order_no = Purch_Order::add($_SESSION['PO']);
					Dates::new_doc_date($_SESSION['PO']->orig_order_date);
					$_SESSION['history'][ST_PURCHORDER] = $_SESSION['PO']->reference;
					unset($_SESSION['PO']);
					Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no");
				} else {
					/*its an existing order need to update the old order info */
					$order_no = Purch_Order::update($_SESSION['PO']);
					$_SESSION['history'][ST_PURCHORDER] = $_SESSION['PO']->reference;
					Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$order_no&Updated=1");
				}
			}
		}


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
		Purch_Order::create();
		$_SESSION['PO']->order_no = $_GET['ModifyOrderNumber'];
		/*read in all the selected order into the Items cart  */
		Purch_Order::get($_SESSION['PO']->order_no, $_SESSION['PO']);
		copy_from_cart();
	}
	if (isset($_POST['CancelUpdate']) || isset($_POST['UpdateLine'])) {
		line_start_focus();
	}
	if (isset($_GET['NewOrder'])) {
		Purch_Order::create();
		if (isset($_GET['UseOrder']) && $_GET['UseOrder'] && isset($_SESSION['Items']->line_items)) {
			foreach ($_SESSION['Items']->line_items as $line_no => $line_item) {
				$sql = "SELECT purch_data.price,purch_data.supplier_id
		FROM purch_data INNER JOIN suppliers
		ON purch_data.supplier_id=suppliers.supplier_id
		WHERE stock_id = " . DB::escape($line_item->stock_id) . ' ORDER BY price';
				$result = DB::query($sql);
				$myrow = array();
				if (DB::num_rows($result) > 0) {
					if (DB::num_rows($result) == 1) {
						$myrow[] = DB::fetch($result, 'pricing');
					} else {
						$myrow = DB::fetch($result, 'pricing');
					}
					if (isset($po_lines[$myrow[0]['supplier_id']])) {
						$po_lines[$myrow[0]['supplier_id']]++;
					} else {
						$po_lines[$myrow[0]['supplier_id']] = 1;
					}
				}
				$_SESSION['PO']->add_to_order($line_no, $line_item->stock_id, $line_item->quantity, $line_item->description,
					Num::price_decimal($myrow[0]['price'], $dec2), $line_item->units, Dates::add_days(Dates::Today(), 10), 0, 0, 0);
			}
			arsort($po_lines);
			$_SESSION['supplier_id'] = key($po_lines);
			if ($_GET['DS']) {
				$item_info = Item::get('DS');
				$_POST['StkLocation'] = 'DRP';
				$_SESSION['PO']->add_to_order(count($_SESSION['PO']->line_items), 'DS', 1, $item_info['long_description'], 0, '',
					Dates::add_days(Dates::Today(), 10), 0, 0, 0);
				$address = $_SESSION['Items']->customer_name . "\n";
				if (!empty($_SESSION['Items']->name) && $_SESSION['Items']->deliver_to == $_SESSION['Items']->customer_name) {
					$address .= $_SESSION['Items']->name . "\n";
				} elseif ($_SESSION['Items']->deliver_to != $_SESSION['Items']->customer_name) {
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

	Display::start_form();
	if ((isset($_GET['NewOrder']) && $_GET['NewOrder']) && (!isset($_GET['UseOrder']) || !$_GET['UseOrder'])) {
		echo "
<div class='center'>
    <iframe src='/purchases/inquiry/po_search_completed.php?NFY=1&frame=1' style='width:90%' height='350' frameborder='0'></iframe>
</div>";
	}
	Purch_Order::header($_SESSION['PO']);
	echo "<br>";
	Purch_Order::display_items($_SESSION['PO']);
	Display::start_table('tablestyle2');
	textarea_row(_("Memo:"), 'Comments', null, 70, 4);
	Display::end_table(1);
	Display::div_start('controls', 'items_table');
	if ($_SESSION['PO']->order_has_items()) {
		submit_center_first('CancelOrder', _("Delete This Order"));
		if ($_SESSION['PO']->order_no) {
			submit_center_last('Commit', _("Update Order"), '', 'default');
		} else {
			submit_center_last('Commit', _("Place Order"), '', 'default');
		}
	} else {
		submit_js_confirm('CancelOrder', _('You are about to void this Document.\nDo you want to continue?'));
		submit_center('CancelOrder', _("Delete This Order"), true, false, 'cancel');
	}
	Display::div_end();

	Display::end_form();
	JS::onUnload('Are you sure you want to leave without commiting changes?');
	Item::addEditDialog();
	if (isset($_SESSION['PO']->supplier_id)) {
		Contacts_Supplier::addInfoDialog("td[name=\"supplier_name\"]", $_SESSION['PO']->supplier_details['supplier_id']);
	}
	end_page();

?>
