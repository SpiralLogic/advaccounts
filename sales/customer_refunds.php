<?php

	/* * ********************************************************************
		 Copyright (C) FrontAccounting, LLC.
		 Released under the terms of the GNU General Public License, GPL,
		 as published by the Free Software Foundation, either version 3
		 of the License, or (at your option) any later version.
		 This program is distributed in the hope that it will be useful,
		 but WITHOUT ANY WARRANTY; without even the implied warranty of
		 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		* ********************************************************************* */
	$page_security = 'SA_SALESREFUND';

	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");

	include_once(APP_PATH . "contacts/includes/contacts.inc");

	include_once(APP_PATH . "reporting/includes/reporting.inc");

	$js = "";
	if (Config::get('ui.windows.popups')) {
		$js .= ui_view::get_js_open_window(900, 500);
	}
	$js_lib[] = <<< JS
function Customer() {

}
JS;

	JS::headerFile('/js/payalloc.js');
	page(_($help_context = "Customer Refund Entry"), @$_REQUEST['frame'], false, "", $js);
	//----------------------------------------------------------------------------------------------
	check_db_has_customers(_("There are no customers defined in the system."));
	check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));
	//----------------------------------------------------------------------------------------
	if (!isset($_POST['customer_id'])) {
		$customer = new Customer(ui_globals::get_global_customer(false));
	}
	if (!isset($_POST['DateBanked'])) {
		$_POST['DateBanked'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
			$_POST['DateBanked'] = Dates::end_fiscalyear();
		}
	}
	if (isset($_GET['AddedID'])) {
		$refund_id = $_GET['AddedID'];
		ui_msgs::display_notification_centered(_("The customer refund has been successfully entered."));
		submenu_print(_("&Print This Receipt"), ST_CUSTREFUND, $refund_id . "-" . ST_CUSTREFUND, 'prtopt');
		hyperlink_no_params("/sales/inquiry/customer_inquiry.php", _("Show Invoices"));
		ui_msgs::display_note(ui_view::get_gl_view_str(ST_CUSTREFUND, $refund_id, _("&View the GL Journal Entries for this Customer Refund")));
		ui_view::display_footer_exit();
	}

	//----------------------------------------------------------------------------------------------
	function can_process() {
		global $Refs;
		if (!get_post('customer_id')) {
			ui_msgs::display_error(_("There is no customer selected."));
			JS::setfocus('[name="customer_id"]');
			return false;
		}
		if (!get_post('BranchID')) {
			ui_msgs::display_error(_("This customer has no branch defined."));
			JS::setfocus('[name="BranchID"]');
			return false;
		}
		if (!isset($_POST['DateBanked']) || !Dates::is_date($_POST['DateBanked'])) {
			ui_msgs::display_error(_("The entered date is invalid. Please enter a valid date for the refund."));
			JS::setfocus('[name="DateBanked"]');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
			ui_msgs::display_error(_("The entered date is not in fiscal year."));
			JS::setfocus('[name="DateBanked"]');
			return false;
		}
		if (!$Refs->is_valid($_POST['ref'])) {
			ui_msgs::display_error(_("You must enter a reference."));
			JS::setfocus('[name="ref"]');
			return false;
		}
		if (!is_new_reference($_POST['ref'], ST_CUSTREFUND)) {
			ui_msgs::display_error(_("The entered reference is already in use."));
			JS::setfocus('[name="ref"]');
			return false;
		}
		if (!check_num('amount', 0, null)) {
			ui_msgs::display_error(_("The entered amount is invalid or positive and cannot be processed."));
			JS::setfocus('[name="amount"]');
			return false;
		}
		if (isset($_POST['charge']) && !check_num('charge', 0)) {
			ui_msgs::display_error(_("The entered amount is invalid or negative and cannot be processed."));
			JS::setfocus('[name="charge"]');
			return false;
		}
		if (isset($_POST['charge']) && input_num('charge') > 0) {
			$charge_acct = get_company_pref('bank_charge_act');
			if (get_gl_account($charge_acct) == false) {
				ui_msgs::display_error(_("The Bank Charge Account has not been set in System and General GL Setup."));
				JS::setfocus('[name="charge"]');
				return false;
			}
		}
		if (isset($_POST['_ex_rate']) && !check_num('_ex_rate', 0.000001)) {
			ui_msgs::display_error(_("The exchange rate must be numeric and greater than zero."));
			JS::setfocus('[name="ex_rate"]');
			return false;
		}
		if ($_POST['discount'] == "") {
			$_POST['discount'] = 0;
		}
		//if ((input_num('amount') - input_num('discount') <= 0)) {
		if (input_num('amount') >= 0) {
			ui_msgs::display_error(_("The balance of the amount and discount is zero or positive. Please enter valid amounts."));
			JS::setfocus('[name="amount"]');
			return false;
		}
		$_SESSION['alloc']->amount = -1 * input_num('amount');
		if (isset($_POST["TotalNumberOfAllocs"])) {
			return check_allocations();
		}
		return true;
	}

	//----------------------------------------------------------------------------------------------
	// validate inputs
	if (isset($_POST['AddRefundItem'])) {
		if (!can_process()) {
			unset($_POST['AddRefundItem']);
		}
	}
	if (isset($_POST['_DateBanked_changed'])) {
		JS::setfocus('_DataBanked_changed');
	}
	if (list_updated('customer_id') || list_updated('bank_account')) {
		$_SESSION['alloc']->read();
		$Ajax->activate('alloc_tbl');
	}
	//----------------------------------------------------------------------------------------------

	if (isset($_POST['AddRefundItem'])) {
		$cust_currency = Banking::get_customer_currency($_POST['customer_id']);
		$bank_currency = Banking::get_bank_account_currency($_POST['bank_account']);
		$comp_currency = Banking::get_company_currency();
		if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
			$rate = 0;
		}
		else
		{
			$rate = input_num('_ex_rate');
		}
		Dates::new_doc_date($_POST['DateBanked']);
		$refund_id = write_customer_refund(0, $_POST['customer_id'], $_POST['BranchID'],
			$_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'],
			input_num('amount'), input_num('discount'),
			$_POST['memo_'], $rate, input_num('charge'));
		$_SESSION['alloc']->trans_no = $refund_id;
		$_SESSION['alloc']->write();
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$refund_id");
	}

	//----------------------------------------------------------------------------------------------

	function read_customer_data() {
		global $Refs, $customer;
		$sql = "SELECT debtors_master.pymt_discount,
		credit_status.dissallow_invoices
		FROM debtors_master, credit_status
		WHERE debtors_master.credit_status = credit_status.id
			AND debtors_master.debtor_no = " . $customer->id;
		$result = db_query($sql, "could not query customers");
		$myrow = db_fetch($result);
		$_POST['HoldAccount'] = $myrow["dissallow_invoices"];
		$_POST['pymt_discount'] = 0;
		$_POST['ref'] = $Refs->get_next(12);
	}

	//----------------------------------------------------------------------------------------------

	start_form();

	start_outer_table(Config::get('tables.style2') . " width=60%", 5);
	table_section(1);
	UI::search('customer', array('label' => 'Search Customer:', 'size' => 20, 'url' => '/contacts/search.php'));
	if (!isset($_POST['bank_account'])) // first page call
	{
		$_SESSION['alloc'] = new allocation(ST_CUSTREFUND, 0);
	}
	if (count($customer->branches) == 0) {
		customer_branches_list_row(_("Branch:"), $_POST['customer_id'], 'BranchID', null, false, true, true);
	}
	else {
		hidden('BranchID', ANY_NUMERIC);
	}
	read_customer_data();
	ui_globals::set_global_customer($customer->id);
	if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0) {
		end_outer_table();
		ui_msgs::display_error(_("This customer account is on hold."));
	}
	else {
		$display_discount_percent = percent_format($_POST['pymt_discount'] * 100) . "%";
		table_section(2);
		bank_accounts_list_row(_("Into Bank Account:"), 'bank_account', null, true);
		text_row(_("Reference:"), 'ref', null, 20, 40);
		table_section(3);
		date_row(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
		$comp_currency = Banking::get_company_currency();
		$cust_currency = Banking::get_customer_currency($customer->id);
		$bank_currency = Banking::get_bank_account_currency($_POST['bank_account']);
		if ($cust_currency != $bank_currency) {
			ui_view::exchange_rate_display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
		}
		amount_row(_("Bank Charge:"), 'charge');
		end_outer_table(1);
		if ($cust_currency == $bank_currency) {
			div_start('alloc_tbl');
			show_allocatable(true);
			div_end();
		}
		start_table(Config::get('tables.style') . "  width=60%");
		amount_row(_("Amount:"), 'amount');
		textarea_row(_("Memo:"), 'memo_', null, 22, 4);
		end_table(1);
		if ($cust_currency != $bank_currency) {
			ui_msgs::display_note(_("Amount and discount are in customer's currency."));
		}
		br();
		submit_center('AddRefundItem', _("Add Refund"), true, '', 'default');
	}
	br();
	end_form();
	if (@$_REQUEST['frame']) {
		end_page(true, true, true);
	}
	else {
		end_page();
	}
