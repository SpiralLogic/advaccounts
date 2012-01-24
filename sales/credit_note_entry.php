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
	//	Entry/Modify free hand Credit Note
	//
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");


	JS::open_window(900, 500);
	if (isset($_GET[Orders::NEW_CREDIT])) {
		$_SESSION['page_title'] = _($help_context = "Customer Credit Note");
		$order = handle_new_credit(0);
	}
	elseif (isset($_GET[Orders::MODIFY_CREDIT])) {
		$_SESSION['page_title'] = sprintf(_("Modifying Customer Credit Note #%d"), $_GET[Orders::MODIFY_CREDIT]);
		$order = handle_new_credit($_GET[Orders::MODIFY_CREDIT]);
		$help_context = "Modifying Customer Credit Note";
	}
	else {
		$_SESSION['page_title'] = _($help_context = "Customer Credit Note");
	}
	Page::start($_SESSION['page_title'],SA_SALESCREDIT);
	Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
	Validation::check(Validation::BRANCHES_ACTIVE, _("There are no customers, or there are no customers with branches. Please define customers and customer branches."));
	if (list_updated('branch_id')) {
		// when branch is selected via external editor also customer can change
		$br = Sales_Branch::get(get_post('branch_id'));
		$_POST['customer_id'] = $br['debtor_no'];
		Ajax::i()->activate('customer_id');
	}
	if (isset($_GET['AddedID'])) {
		$credit_no = $_GET['AddedID'];
		$trans_type = ST_CUSTCREDIT;
		Event::notice(sprintf(_("Credit Note # %d has been processed"), $credit_no));
		Display::note(Debtor::trans_view($trans_type, $credit_no, _("&View this credit note")), 0, 1);
		Display::note(Reporting::print_doc_link($credit_no . "-" . $trans_type, _("&Print This Credit Invoice"), true, ST_CUSTCREDIT), 0, 1);
		Display::note(Reporting::print_doc_link($credit_no . "-" . $trans_type, _("&Email This Credit Invoice"), true, ST_CUSTCREDIT, false, "printlink", "", 1), 0, 1);
		Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")));
		Display::link_params($_SERVER['PHP_SELF'], _("Enter Another &Credit Note"), "NewCredit=yes");
		Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$credit_no");
		Page::footer_exit();
	}
	if (isset($_POST['CancelChanges'])) {
		$type = $order->trans_type;
		$order_no = key($order->trans_no);
		Orders::session_delete($_POST['order_id']);
		$order = create_order($type, $order_no);
	}
	$id = find_submit(MODE_DELETE);
	if ($id != -1) {
		handle_delete_item($order, $id);
	}
	if (isset($_POST['AddItem'])) {
		handle_new_item($order);
	}
	if (isset($_POST['UpdateItem'])) {
		handle_update_item($order);
	}
	if (isset($_POST['CancelItemChanges'])) {
		line_start_focus();
	}
	if (isset($_POST['ProcessCredit']) && can_process()) {
		if ($_POST['CreditType'] == "WriteOff" && (!isset($_POST['WriteOffGLCode']) || $_POST['WriteOffGLCode'] == '')) {
			Event::warning(_("For credit notes created to write off the stock, a general ledger account is required to be selected."), 1, 0);
			Event::warning(_("Please select an account to write the cost of the stock off to, then click on Process again."), 1, 0);
			exit;
		}
		if (!isset($_POST['WriteOffGLCode'])) {
			$_POST['WriteOffGLCode'] = 0;
		}
		$credit = copy_to_cn($order);
		$credit_no = $credit->write($_POST['WriteOffGLCode']);
		Dates::new_doc_date($credit->document_date);
		Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$credit_no");
	} /*end of process credit note */
	start_form();
	hidden('order_id', $_POST['order_id']);
	$customer_error = Sales_Credit::header($order);
	if ($customer_error == "") {
		start_table('tables_style2 width90 pad10');
		echo "<tr><td>";
		Sales_Credit::display_items(_("Credit Note Items"), $order);
		Sales_Credit::option_controls($order);
		echo "</td></tr>";
		end_table();
	}
	else {
		Event::error($customer_error);
	}
	submit_center_first('Update', _("Update"));
	submit_center_middle('CancelChanges', _("Cancel Changes"), _("Revert this document entry back to its former state."));
	submit_center_last('ProcessCredit', _("Process Credit Note"), '', false, 'default');
	echo "</tr></table></div>";
	end_form();
	Page::end();
	function line_start_focus() {
		Ajax::i()->activate('items_table');
		JS::set_focus('_stock_id_edit');
	}
/***
 * @param $order
 * @return Sales_Order
 */
	function copy_to_cn($order) {
		$order->Comments = $_POST['CreditText'];
		$order->document_date = $_POST['OrderDate'];
		$order->freight_cost = Validation::input_num('ChargeFreightCost');
		$order->Location = (isset($_POST["Location"]) ? $_POST["Location"] : "");
		$order->sales_type = $_POST['sales_type_id'];
		if ($order->trans_no == 0) {
			$order->reference = $_POST['ref'];
		}
		$order->ship_via = $_POST['ShipperID'];
		$order->dimension_id = $_POST['dimension_id'];
		$order->dimension2_id = $_POST['dimension2_id'];
		return $order;
	}

	function copy_from_cn($order) {
		$order = Sales_Order::check_edit_conflicts($order);
		$_POST['CreditText'] = $order->Comments;
		$_POST['customer_id'] = $order->customer_id;
		$_POST['branch_id'] = $order->Branch;
		$_POST['OrderDate'] = $order->document_date;
		$_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
		$_POST['Location'] = $order->Location;
		$_POST['sales_type_id'] = $order->sales_type;
		if ($order->trans_no == 0) {
			$_POST['ref'] = $order->reference;
		}
		$_POST['ShipperID'] = $order->ship_via;
		$_POST['dimension_id'] = $order->dimension_id;
		$_POST['dimension2_id'] = $order->dimension2_id;
		$_POST['order_id'] = $order->order_id;
		Orders::session_set($order);
	}

	function handle_new_credit($trans_no) {
		$order = new Sales_Order(ST_CUSTCREDIT, $trans_no);
		Orders::session_delete($order->order_id);
		$order->start();
		copy_from_cn($order);
		return $order;
	}

	function can_process($order) {
		$input_error = 0;
		if ($order->count_items() == 0 && (!Validation::is_num('ChargeFreightCost', 0))) {
			return false;
		}
		if ($order->trans_no == 0) {
			if (!Ref::is_valid($_POST['ref'])) {
				Event::error(_("You must enter a reference."));
				JS::set_focus('ref');
				$input_error = 1;
			}
			elseif (!Ref::is_new($_POST['ref'], ST_CUSTCREDIT)) {
				$_POST['ref'] = Ref::get_next(ST_CUSTCREDIT);
			}
		}
		if (!Dates::is_date($_POST['OrderDate'])) {
			Event::error(_("The entered date for the credit note is invalid."));
			JS::set_focus('OrderDate');
			$input_error = 1;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['OrderDate'])) {
			Event::error(_("The entered date is not in fiscal year."));
			JS::set_focus('OrderDate');
			$input_error = 1;
		}
		return ($input_error == 0);
	}

	function check_item_data() {
		if (!Validation::is_num('qty', 0)) {
			Event::error(_("The quantity must be greater than zero."));
			JS::set_focus('qty');
			return false;
		}
		if (!Validation::is_num('price', 0)) {
			Event::error(_("The entered price is negative or invalid."));
			JS::set_focus('price');
			return false;
		}
		if (!Validation::is_num('Disc', 0, 100)) {
			Event::error(_("The entered discount percent is negative, greater than 100 or invalid."));
			JS::set_focus('Disc');
			return false;
		}
		return true;
	}

	function handle_update_item($order) {
		if ($_POST['UpdateItem'] != "" && check_item_data()) {
			$order->update_order_item($_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100);
		}
		line_start_focus();
	}

	function handle_delete_item($order, $line_no) {
		$order->remove_from_order($line_no);
		line_start_focus();
	}

	function handle_new_item($order) {
		if (!check_item_data()) {
			return;
		}
		$order->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100);
		line_start_focus();
	}

?>
