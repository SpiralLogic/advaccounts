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
		JS::open_window(900, 500);
	JS::headerFile('/js/payalloc.js');
Page::start(_($help_context = "Customer Refund Entry"), SA_SALESREFUND, Input::request('frame'));
	Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	if (!isset($_POST['customer_id'])) {
		$customer = new Debtor(Session::i()->global_customer);
	}
	if (!isset($_POST['DateBanked'])) {
		$_POST['DateBanked'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
			$_POST['DateBanked'] = Dates::end_fiscalyear();
		}
	}
	if (isset($_GET[ADDED_ID])) {
		$refund_id = $_GET[ADDED_ID];
		Event::notice(_("The customer refund has been successfully entered."));
		Display::submenu_print(_("&Print This Receipt"), ST_CUSTREFUND, $refund_id . "-" . ST_CUSTREFUND, 'prtopt');
		Display::link_no_params("/sales/inquiry/customer_inquiry.php", _("Show Invoices"));
		Display::note(GL_UI::view(ST_CUSTREFUND, $refund_id, _("&View the GL Journal Entries for this Customer Refund")));
		Page::footer_exit();
	}

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
		Ajax::i()->activate('alloc_tbl');
	}
	if (isset($_POST['AddRefundItem'])) {
		$cust_currency = Bank_Currency::for_debtor($_POST['customer_id']);
		$bank_currency = Bank_Currency::for_company($_POST['bank_account']);
		$comp_currency = Bank_Currency::for_company();
		if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
			$rate = 0;
		}
		else {
			$rate = Validation::input_num('_ex_rate');
		}
		Dates::new_doc_date($_POST['DateBanked']);
		$refund_id = Debtor_Refund::add(0, $_POST['customer_id'], $_POST['BranchID'], $_POST['bank_account'], $_POST['DateBanked'], $_POST['ref'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['memo_'], $rate, Validation::input_num('charge'));
		$_SESSION['alloc']->trans_no = $refund_id;
		$_SESSION['alloc']->write();
		Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$refund_id");
	}
	start_form();
	start_outer_table('tablestyle2 width60 pad5');
	table_section(1);
	UI::search('customer', array(
															'label' => 'Search Customer:', 'size' => 20, 'url' => '/contacts/search.php'));
	if (!isset($_POST['bank_account'])) // first page call
	{
		$_SESSION['alloc'] = new Gl_Allocation(ST_CUSTREFUND, 0);
	}
	if (count($customer->branches) == 0) {
		Debtor_Branch::row(_("Branch:"), $_POST['customer_id'], 'BranchID', null, false, true, true);
	}
	else {
		hidden('BranchID', ANY_NUMERIC);
	}
	read_customer_data();
	Session::i()->global_customer = $customer->id;
	if (isset($_POST['HoldAccount']) && $_POST['HoldAccount'] != 0) {
		end_outer_table();
		Event::error(_("This customer account is on hold."));
	}
	else {
		$display_discount_percent = Num::percent_format($_POST['pymt_discount'] * 100) . "%";
		table_section(2);
		Bank_Account::row(_("Into Bank Account:"), 'bank_account', null, true);
		text_row(_("Reference:"), 'ref', null, 20, 40);
		table_section(3);
		date_row(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
		$comp_currency = Bank_Currency::for_company();
		$cust_currency = Bank_Currency::for_debtor($customer->id);
		$bank_currency = Bank_Currency::for_company($_POST['bank_account']);
		if ($cust_currency != $bank_currency) {
			GL_ExchangeRate::display($bank_currency, $cust_currency, $_POST['DateBanked'], ($bank_currency == $comp_currency));
		}
		amount_row(_("Bank Charge:"), 'charge');
		end_outer_table(1);
		if ($cust_currency == $bank_currency) {
			Display::div_start('alloc_tbl');
			Gl_Allocation::show_allocatable(true);
			Display::div_end();
		}
		start_table('tablestyle width60');
		amount_row(_("Amount:"), 'amount');
		textarea_row(_("Memo:"), 'memo_', null, 22, 4);
		end_table(1);
		if ($cust_currency != $bank_currency) {
			Event::warning(_("Amount and discount are in customer's currency."));
		}
		Display::br();
		submit_center('AddRefundItem', _("Add Refund"), true, '', 'default');
	}
	Display::br();
	end_form();
	if (Input::request('frame')) {
		Page::end(true);
	}
	else {
		Page::end();
	}
	function read_customer_data() {
		global $customer;
		$sql
		 = "SELECT debtors.pymt_discount,
			credit_status.dissallow_invoices
			FROM debtors, credit_status
			WHERE debtors.credit_status = credit_status.id
				AND debtors.debtor_no = " . $customer->id;
		$result = DB::query($sql, "could not query customers");
		$myrow = DB::fetch($result);
		$_POST['HoldAccount'] = $myrow["dissallow_invoices"];
		$_POST['pymt_discount'] = 0;
		$_POST['ref'] = Ref::get_next(ST_CUSTREFUND);
	}
	function can_process() {
		if (!get_post('customer_id')) {
			Event::error(_("There is no customer selected."));
			JS::setfocus('[name="customer_id"]');
			return false;
		}
		if (!get_post('BranchID')) {
			Event::error(_("This customer has no branch defined."));
			JS::setfocus('[name="BranchID"]');
			return false;
		}
		if (!isset($_POST['DateBanked']) || !Dates::is_date($_POST['DateBanked'])) {
			Event::error(_("The entered date is invalid. Please enter a valid date for the refund."));
			JS::setfocus('[name="DateBanked"]');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['DateBanked'])) {
			Event::error(_("The entered date is not in fiscal year."));
			JS::setfocus('[name="DateBanked"]');
			return false;
		}
		if (!Ref::is_valid($_POST['ref'])) {
			Event::error(_("You must enter a reference."));
			JS::setfocus('[name="ref"]');
			return false;
		}
		if (!Ref::is_new($_POST['ref'], ST_CUSTREFUND)) {
			$_POST['ref'] = Ref::get_next(ST_CUSTREFUND);
		}
		if (!Validation::is_num('amount', 0, null)) {
			Event::error(_("The entered amount is invalid or positive and cannot be processed."));
			JS::setfocus('[name="amount"]');
			return false;
		}
		if (isset($_POST['charge']) && !Validation::is_num('charge', 0)) {
			Event::error(_("The entered amount is invalid or negative and cannot be processed."));
			JS::setfocus('[name="charge"]');
			return false;
		}
		if (isset($_POST['charge']) && Validation::input_num('charge') > 0) {
			$charge_acct = DB_Company::get_pref('bank_charge_act');
			if (GL_Account::get($charge_acct) == false) {
				Event::error(_("The Bank Charge Account has not been set in System and General GL Setup."));
				JS::setfocus('[name="charge"]');
				return false;
			}
		}
		if (isset($_POST['_ex_rate']) && !Validation::is_num('_ex_rate', 0.000001)) {
			Event::error(_("The exchange rate must be numeric and greater than zero."));
			JS::setfocus('[name="ex_rate"]');
			return false;
		}
		if ($_POST['discount'] == "") {
			$_POST['discount'] = 0;
		}
		//if ((Validation::input_num('amount') - Validation::input_num('discount') <= 0)) {
		if (Validation::input_num('amount') >= 0) {
			Event::error(_("The balance of the amount and discount is zero or positive. Please enter valid amounts."));
			JS::setfocus('[name="amount"]');
			return false;
		}
		$_SESSION['alloc']->amount = -1 * Validation::input_num('amount');
		if (isset($_POST["TotalNumberOfAllocs"])) {
			return Gl_Allocation::check();
		}
		return true;
	}
