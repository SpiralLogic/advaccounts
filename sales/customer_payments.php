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
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_SALESPAYMNT;

	JS::open_window(900, 500);
	JS::footerFile('/js/payalloc.js');
	Page::start(_($help_context = "Customer Payment Entry"), Input::request('frame'));
	Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	if (list_updated('BranchID')) {
		// when branch is selected via external editor also customer can change
		$br = Sales_Branch::get(get_post('BranchID'));
		$_POST['customer_id'] = $br['debtor_no'];
		Ajax::i()->activate('customer_id');
	}
	if (!isset($_POST['customer_id'])) {
		$_POST['customer_id'] = Session::i()->global_customer;
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
		Display::submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no . "-" . ST_CUSTPAYMENT, 'prtopt');
		Display::link_no_params("/sales/inquiry/customer_inquiry.php", _("Show Invoices"));
		Display::note(GL_UI::view(ST_CUSTPAYMENT, $payment_no, _("&View the GL Journal Entries for this Customer Payment")));
		//	Display::link_params( "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");
		Display::link_no_params("/sales/customer_payments.php", _("Enter Another &Customer Payment"));
		Page::footer_exit();
	}
	// validate inputs
	if (isset($_POST['AddPaymentItem'])) {
		if (!can_process()) {
			unset($_POST['AddPaymentItem']);
		}
	}
	if (isset($_POST['_customer_id_button'])) {
		//	unset($_POST['branch_id']);
		Ajax::i()->activate('BranchID');
	}
	if (isset($_POST['_DateBanked_changed'])) {
		Ajax::i()->activate('_ex_rate');
	}
	if (list_updated('customer_id') || list_updated('bank_account')) {
		Ajax::i()->activate('alloc_tbl');
	}
	if (isset($_POST['AddPaymentItem'])) {
		$cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
		$bank_currency = Bank_Currency::for_company($_POST['bank_account']);
		$comp_currency = Bank_Currency::for_company();
		if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
			$rate = 0;
		}
		else {
			$rate = Validation::input_num('_ex_rate');
		}
		if (check_value('createinvoice')) {
			Gl_Allocation::create_miscorder(new Debtor($_POST['customer_id']), $_POST['BranchID'], $_POST['DateBanked'], $_POST['memo_'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'));
		}
		$payment_no = Debtor_Payment::add(0, $_POST['customer_id'], $_POST['BranchID'], $_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['memo_'], $rate, Validation::input_num('charge'));
		$_SESSION['alloc']->trans_no = $payment_no;
		$_SESSION['alloc']->write();
		Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_no");
	}
	start_form();
	start_outer_table('tablestyle2 width90 pad2');
	table_section(1);
	Debtor::row(_("From Customer:"), 'customer_id', null, false, true);
	if (!isset($_POST['bank_account'])) // first page call
	{
		$_SESSION['alloc'] = new Gl_Allocation(ST_CUSTPAYMENT, 0);
	}
	if (Validation::check(Validation::BRANCHES, _("No Branches for Customer") . $_POST["customer_id"], $_POST['customer_id'])) {
		Debtor_Branch::row(_("Branch:"), $_POST['customer_id'], 'BranchID', null, false, true, true);
	}
	else {
		hidden('BranchID', ANY_NUMERIC);
	}
	read_customer_data();
	Session::i()->global_customer = $_POST['customer_id'];
	if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0) {
		end_outer_table();
		Errors::error(_("This customer account is on hold."));
	}
	else {
		$display_discount_percent = Num::percent_format($_POST['pymt_discount'] * 100) . "%";
		table_section(2);
		if (!list_updated('bank_account')) {
			$_POST['bank_account'] = Bank_Account::get_customer_default($_POST['customer_id']);
		}
		Bank_Account::row(_("Into Bank Account:"), 'bank_account', null, true);
		text_row(_("Reference:"), 'ref', null, 20, 40);
		table_section(3);
		date_row(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
		$comp_currency = Bank_Currency::for_company();
		$cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
		$bank_currency = Bank_Currency::for_company($_POST['bank_account']);
		if ($cust_currency != $bank_currency) {
			GL_ExchangeRate::display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
		}
		amount_row(_("Bank Charge:"), 'charge', 0);
		end_outer_table(1);
		if ($cust_currency == $bank_currency) {
			Display::div_start('alloc_tbl');
			$_SESSION['alloc']->read();
			Gl_Allocation::show_allocatable(false);
			Display::div_end();
		}
		start_table('tablestyle width70');
		label_row(_("Customer prompt payment discount :"), $display_discount_percent);
		amount_row(_("Amount of Discount:"), 'discount', 0);
		check_row(_("Create invoice and apply for this payment: "), 'createinvoice');
		amount_row(_("Amount:"), 'amount');
		textarea_row(_("Memo:"), 'memo_', null, 22, 4);
		end_table(1);
		if ($cust_currency != $bank_currency) {
			Display::note(_("Amount and discount are in customer's currency."));
		}
		Display::br();
		submit_center('AddPaymentItem', _("Add Payment"), true, '', 'default');
	}
	Display::br();
	end_form();
	$js = <<<JS
var ci = $("#createinvoice"), ci_row = ci.closest('tr'),alloc_tbl = $('#alloc_tbl'),hasallocated = false;
 alloc_tbl.find('.amount').each(function() { if (this.value != 0) hasallocated = true});
 if (hasallocated && !ci.prop('checked')) ci_row.hide(); else ci_row.show();
JS;
	JS::addLiveEvent('a, :input', 'click change', $js, 'wrapper', true);
	(Input::request('frame')) ? Page::end() : Page::end(true, true, true);
	function read_customer_data() {
		$myrow = Debtor::get_habit($_POST['customer_id']);
		$_POST['HoldAccount'] = $myrow["dissallow_invoices"];
		$_POST['pymt_discount'] = $myrow["pymt_discount"];
		$_POST['ref'] = Ref::get_next(ST_CUSTPAYMENT);
	}

	function can_process() {
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
		if (!Ref::is_valid($_POST['ref'])) {
			Errors::error(_("You must enter a reference."));
			JS::set_focus('ref');
			return false;
		}
		if (!Ref::is_new($_POST['ref'], ST_CUSTPAYMENT)) {
			$_POST['ref'] = Ref::get_next(ST_CUSTPAYMENT);
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
		if (isset($_POST['charge']) && Validation::input_num('charge') > 0) {
			$charge_acct = DB_Company::get_pref('bank_charge_act');
			if (GL_Account::get($charge_acct) == false) {
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
		//if ((Validation::input_num('amount') - Validation::input_num('discount') <= 0)) {
		if (Validation::input_num('amount', 0, 0) <= 0) {
			Errors::error(_("The balance of the amount and discount is zero or negative. Please enter valid amounts."));
			JS::set_focus('discount');
			return false;
		}
		$_SESSION['alloc']->amount = Validation::input_num('amount');
		if (isset($_POST["TotalNumberOfAllocs"])) {
			return Gl_Allocation::check();
		}
		else {
			return true;
		}
	}

?>
