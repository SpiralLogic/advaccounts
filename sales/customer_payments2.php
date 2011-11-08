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
	$page_security = 'SA_SALESPAYMNT';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(900, 500);
	JS::headerFile('/js/payalloc.js');
	Page::start(_($help_context = "Customer Payment Entry"));
	//----------------------------------------------------------------------------------------------
	Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	//----------------------------------------------------------------------------------------
	if (list_updated('BranchID')) {
		// when branch is selected via external editor also customer can change
		$br = get_branch(get_post('BranchID'));
		$_POST['customer_id'] = $br['debtor_no'];
		$Ajax->activate('customer_id');
	}
	if (!isset($_POST['customer_id'])) {
		$_POST['customer_id'] = Session::get()->global_customer;
	}
	if (!isset($_POST['DateBanked'])) {
		$_POST['DateBanked'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
			$_POST['DateBanked'] = Dates::end_fiscalyear();
		}
	}
	if (isset($_GET['AddedID'])) {
		$payment_no = $_GET['AddedID'];
		Errors::notice(_("The customer payment has been successfully entered."));
		submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no . "-" . ST_CUSTPAYMENT, 'prtopt');
		Display::note(ui_view::get_gl_view_str(ST_CUSTPAYMENT, $payment_no, _("&View the GL Journal Entries for this Customer Payment")));
		//	hyperlink_params( "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");
		hyperlink_no_params("/sales/customer_payments.php", _("Enter Another &Customer Payment"));
		Page::footer_exit();
	}
	//----------------------------------------------------------------------------------------------
	function can_process()
	{
		if (!get_post('customer_id')) {
			Errors::error(_("There is no customer selected."));
			JS::set_focus('customer_id');
			return false;
		}
		if (!get_post('BranchID')) {
			Errors::error(_("This customer has no branch defined."));
			JS::set_focus('BranchID');
			return false;
		}
		if (!isset($_POST['DateBanked']) || !Dates::is_date($_POST['DateBanked'])) {
			Errors::error(_("The entered date is invalid. Please enter a valid date for the payment."));
			JS::set_focus('DateBanked');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
			Errors::error(_("The entered date is not in fiscal year."));
			JS::set_focus('DateBanked');
			return false;
		}
		if (!Refs::is_valid($_POST['ref'])) {
			Errors::error(_("You must enter a reference."));
			JS::set_focus('ref');
			return false;
		}
		if (!is_new_reference($_POST['ref'], ST_CUSTPAYMENT)) {
			Errors::error(_("The entered reference is already in use."));
			JS::set_focus('ref');
			return false;
		}
		if (!Validation::is_num('amount', 0)) {
			Errors::error(_("The entered amount is invalid or negative and cannot be processed."));
			JS::set_focus('amount');
			return false;
		}
		if (isset($_POST['charge']) && !Validation::is_num('charge', 0)) {
			Errors::error(_("The entered amount is invalid or negative and cannot be processed."));
			JS::set_focus('charge');
			return false;
		}
		if (isset($_POST['charge']) && input_num('charge') > 0) {
			$charge_acct = DB_Company::get_pref('bank_charge_act');
			if (get_gl_account($charge_acct) == false) {
				Errors::error(_("The Bank Charge Account has not been set in System and General GL Setup."));
				JS::set_focus('charge');
				return false;
			}
		}
		if (isset($_POST['_ex_rate']) && !Validation::is_num('_ex_rate', 0.000001)) {
			Errors::error(_("The exchange rate must be numeric and greater than zero."));
			JS::set_focus('_ex_rate');
			return false;
		}
		if ($_POST['discount'] == "") {
			$_POST['discount'] = 0;
		}
		if (!Validation::is_num('discount')) {
			Errors::error(_("The entered discount is not a valid number."));
			JS::set_focus('discount');
			return false;
		}
		//if ((input_num('amount') - input_num('discount') <= 0)) {
		if (input_num('amount') <= 0) {
			Errors::error(_("The balance of the amount and discount is zero or negative. Please enter valid amounts."));
			JS::set_focus('discount');
			return false;
		}
		$_SESSION['alloc']->amount = input_num('amount');
		if (isset($_POST["TotalNumberOfAllocs"])) {
			return Gl_Allocation::check_allocations();
		}
		else
		{
			return true;
		}
	}

	//----------------------------------------------------------------------------------------------
	// validate inputs
	if (isset($_POST['AddPaymentItem'])) {
		if (!can_process()) {
			unset($_POST['AddPaymentItem']);
		}
	}
	if (isset($_POST['_customer_id_button'])) {
		//	unset($_POST['branch_id']);
		$Ajax->activate('BranchID');
	}
	if (isset($_POST['_DateBanked_changed'])) {
		$Ajax->activate('_ex_rate');
	}
	if (list_updated('customer_id') || list_updated('bank_account')) {
		$_SESSION['alloc']->read();
		$Ajax->activate('alloc_tbl');
	}
	//----------------------------------------------------------------------------------------------
	if (isset($_POST['AddPaymentItem'])) {
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
		$payment_no = write_customer_payment2(0, $_POST['customer_id'], $_POST['BranchID'],
			$_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'],
			input_num('amount'), input_num('discount'),
			$_POST['memo_'], $rate, input_num('charge'));
		$_SESSION['alloc']->trans_no = $payment_no;
		$_SESSION['alloc']->write();
		meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_no");
	}
	//----------------------------------------------------------------------------------------------
	function read_customer_data()
	{
		$sql
		 = "SELECT debtors_master.pymt_discount,
		credit_status.dissallow_invoices
		FROM debtors_master, credit_status
		WHERE debtors_master.credit_status = credit_status.id
			AND debtors_master.debtor_no = " . DB::escape($_POST['customer_id']);
		$result = DB::query($sql, "could not query customers");
		$myrow = DB::fetch($result);
		$_POST['HoldAccount'] = $myrow["dissallow_invoices"];
		$_POST['pymt_discount'] = $myrow["pymt_discount"];
		$_POST['ref'] = Refs::get_next(12);
	}

	//----------------------------------------------------------------------------------------------
	start_form();
	start_outer_table(Config::get('tables_style2') . " width=60%", 5);
	table_section(1);
	customer_list_row(_("From Customer:"), 'customer_id', null, false, true);
	if (!isset($_POST['bank_account'])) // first page call
	{
		$_SESSION['alloc'] = new Gl_Allocation(ST_CUSTPAYMENT, 0);
	}
	if (Validation::check(Validation::BRANCHES, _("No Branches for Customer"), $_POST['customer_id'])) {
		customer_branches_list_row(_("Branch:"), $_POST['customer_id'], 'BranchID', null, false, true, true);
	}
	else {
		hidden('BranchID', ANY_NUMERIC);
	}
	read_customer_data();
	Session::get()->global_customer = $_POST['customer_id'];
	if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0) {
		end_outer_table();
		Errors::error(_("This customer account is on hold."));
	}
	else {
		$display_discount_percent = Num::percent_format($_POST['pymt_discount'] * 100) . "%";
		table_section(2);
		bank_accounts_list_row(_("Into Bank Account:"), 'bank_account', null, true);
		text_row(_("Reference:"), 'ref', null, 20, 40);
		table_section(3);
		date_row(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
		$comp_currency = Banking::get_company_currency();
		$cust_currency = Banking::get_customer_currency($_POST['customer_id']);
		$bank_currency = Banking::get_bank_account_currency($_POST['bank_account']);
		if ($cust_currency != $bank_currency) {
			Display::exchange_rate($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
		}
		amount_row(_("Bank Charge:"), 'charge');
		end_outer_table(1);
		if ($cust_currency == $bank_currency) {
			div_start('alloc_tbl');
			Gl_Allocation::show_allocatable(false);
			div_end();
		}
		start_table(Config::get('tables_style') . "  width=60%");
		label_row(_("Customer prompt payment discount :"), $display_discount_percent);
		amount_row(_("Amount of Discount:"), 'discount');
		amount_row(_("Amount:"), 'amount');
		textarea_row(_("Memo:"), 'memo_', null, 22, 4);
		end_table(1);
		if ($cust_currency != $bank_currency) {
			Display::note(_("Amount and discount are in customer's currency."));
		}
		br();
		submit_center('AddPaymentItem', _("Add Payment"), true, '', 'default');
	}
	br();
	end_form();
	end_page();
?>
