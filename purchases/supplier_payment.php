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
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$page_security = SA_SUPPLIERPAYMNT;

	JS::open_window(900, 500);
	JS::footerFile('/js/payalloc.js');
	Page::start(_($help_context = "Supplier Payment Entry"));
	if (isset($_GET['supplier_id'])) {
		$_POST['supplier_id'] = $_GET['supplier_id'];
	}
	Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	if (!isset($_POST['supplier_id'])) {
		$_POST['supplier_id'] = Session::i()->supplier_id;
	}
	if (!isset($_POST['DatePaid'])) {
		$_POST['DatePaid'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['DatePaid'])) {
			$_POST['DatePaid'] = Dates::end_fiscalyear();
		}
	}
	if (isset($_POST['_DatePaid_changed'])) {
		Ajax::i()->activate('_ex_rate');
	}
	if (list_updated('supplier_id') || list_updated('bank_account')) {
		$_SESSION['alloc']->read();
		Ajax::i()->activate('alloc_tbl');
	}
	if (isset($_GET['AddedID'])) {
		$payment_id = $_GET['AddedID'];
		Errors::notice(_("Payment has been sucessfully entered"));
		Display::submenu_print(_("&Print This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, 'prtopt');
		Display::submenu_print(_("&Email This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, null, 1);
		Display::note(GL_UI::view(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment")));
		// Display::link_params($path_to_root . "/purchases/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");
		Display::link_params($_SERVER['PHP_SELF'], _("Enter another supplier &payment"), "supplier_id=" . $_POST['supplier_id']);
		Page::footer_exit();
	}
	function check_inputs() {
		if (!get_post('supplier_id')) {
			Errors::error(_("There is no supplier selected."));
			JS::set_focus('supplier_id');
			return false;
		}
		if ($_POST['amount'] == "") {
			$_POST['amount'] = Num::price_format(0);
		}
		if (!Validation::is_num('amount', 0)) {
			Errors::error(_("The entered amount is invalid or less than zero."));
			JS::set_focus('amount');
			return false;
		}
		if (isset($_POST['charge']) && !Validation::is_num('charge', 0)) {
			Errors::error(_("The entered amount is invalid or less than zero."));
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
		if (!Validation::is_num('discount', 0)) {
			Errors::error(_("The entered discount is invalid or less than zero."));
			JS::set_focus('amount');
			return false;
		}
		//if (Validation::input_num('amount') - Validation::input_num('discount') <= 0)
		if (Validation::input_num('amount') <= 0) {
			Errors::error(_("The total of the amount and the discount is zero or negative. Please enter positive values."));
			JS::set_focus('amount');
			return false;
		}
		if (!Dates::is_date($_POST['DatePaid'])) {
			Errors::error(_("The entered date is invalid."));
			JS::set_focus('DatePaid');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['DatePaid'])) {
			Errors::error(_("The entered date is not in fiscal year."));
			JS::set_focus('DatePaid');
			return false;
		}
		if (!Ref::is_valid($_POST['ref'])) {
			Errors::error(_("You must enter a reference."));
			JS::set_focus('ref');
			return false;
		}
		if (!Ref::is_new($_POST['ref'], ST_SUPPAYMENT)) {
			$_POST['ref'] = Ref::get_next(ST_SUPPAYMENT);
		}
		$_SESSION['alloc']->amount = -Validation::input_num('amount');
		if (isset($_POST["TotalNumberOfAllocs"])) {
			return Gl_Allocation::check();
		}
		else {
			return true;
		}
	}

	function handle_add_payment() {
		$supp_currency = Bank_Currency::for_creditor($_POST['supplier_id']);
		$bank_currency = Bank_Currency::for_company($_POST['bank_account']);
		$comp_currency = Bank_Currency::for_company();
		if ($comp_currency != $bank_currency && $bank_currency != $supp_currency) {
			$rate = 0;
		}
		else {
			$rate = Validation::input_num('_ex_rate');
		}
		$payment_id = Creditor_Payment::add($_POST['supplier_id'], $_POST['DatePaid'], $_POST['bank_account'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['ref'], $_POST['memo_'], $rate, Validation::input_num('charge'));
		Dates::new_doc_date($_POST['DatePaid']);
		$_SESSION['alloc']->trans_no = $payment_id;
		$_SESSION['alloc']->write();
		//unset($_POST['supplier_id']);
		unset($_POST['bank_account']);
		unset($_POST['DatePaid']);
		unset($_POST['currency']);
		unset($_POST['memo_']);
		unset($_POST['amount']);
		unset($_POST['discount']);
		unset($_POST['ProcessSuppPayment']);
		Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_id&supplier_id=" . $_POST['supplier_id']);
	}

	if (isset($_POST['ProcessSuppPayment'])) {
		/*First off check for valid inputs */
		if (check_inputs() == true) {
			handle_add_payment();
			Page::end();
			exit;
		}
	}
	start_form();
	start_outer_table('tablestyle2 width60 pad5');
	table_section(1);
	Creditor::row(_("Payment To:"), 'supplier_id', null, false, true);
	if (!isset($_POST['bank_account'])) // first page call
	{
		$_SESSION['alloc'] = new Gl_Allocation(ST_SUPPAYMENT, 0);
	}
	Session::i()->supplier_id = $_POST['supplier_id'];
	Bank_Account::row(_("From Bank Account:"), 'bank_account', null, true);
	table_section(2);
	ref_row(_("Reference:"), 'ref', '', Ref::get_next(ST_SUPPAYMENT));
	date_row(_("Date Paid") . ":", 'DatePaid', '', true, 0, 0, 0, null, true);
	table_section(3);
	$supplier_currency = Bank_Currency::for_creditor($_POST['supplier_id']);
	$bank_currency = Bank_Currency::for_company($_POST['bank_account']);
	if ($bank_currency != $supplier_currency) {
		GL_ExchangeRate::display($bank_currency, $supplier_currency, $_POST['DatePaid'], true);
	}
	amount_row(_("Bank Charge:"), 'charge');
	end_outer_table(1); // outer table
	if ($bank_currency == $supplier_currency) {
		Display::div_start('alloc_tbl');
		Gl_Allocation::show_allocatable(false);
		Display::div_end();
	}
	start_table('tablestyle width60');
	amount_row(_("Amount of Discount:"), 'discount');
	amount_row(_("Amount of Payment:"), 'amount');
	textarea_row(_("Memo:"), 'memo_', null, 22, 4);
	end_table(1);
	if ($bank_currency != $supplier_currency) {
		Errors::warning(_("The amount and discount are in the bank account's currency."), 0, 1);
	}
	submit_center('ProcessSuppPayment', _("Enter Payment"), true, '', 'default');
	end_form();
	Page::end();
?>
