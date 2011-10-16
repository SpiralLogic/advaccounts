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
	$page_security = 'SA_SUPPLIERPAYMNT';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	include_once(APP_PATH . "reporting/includes/reporting.php");

	$js = "";
	if (Config::get('ui.windows.popups'))
		$js .= ui_view::get_js_open_window(900, 500);

	JS::headerFile('/js/payalloc.js');

	Renderer::page(_($help_context = "Supplier Payment Entry"), false, false, "", $js);

	if (isset($_GET['supplier_id'])) {
		$_POST['supplier_id'] = $_GET['supplier_id'];
	}

	//----------------------------------------------------------------------------------------

	check_db_has_suppliers(_("There are no suppliers defined in the system."));

	check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

	//----------------------------------------------------------------------------------------

	if (!isset($_POST['supplier_id']))
		$_POST['supplier_id'] = ui_globals::get_global_supplier(false);

	if (!isset($_POST['DatePaid'])) {
		$_POST['DatePaid'] = Dates::new_doc_date();
		if (!Dates::is_date_in_fiscalyear($_POST['DatePaid']))
			$_POST['DatePaid'] = Dates::end_fiscalyear();
	}

	if (isset($_POST['_DatePaid_changed'])) {
		$Ajax->activate('_ex_rate');
	}

	if (list_updated('supplier_id') || list_updated('bank_account')) {
		$_SESSION['alloc']->read();
		$Ajax->activate('alloc_tbl');
	}
	//----------------------------------------------------------------------------------------

	if (isset($_GET['AddedID'])) {
		$payment_id = $_GET['AddedID'];

		ui_msgs::display_notification_centered(_("Payment has been sucessfully entered"));

		submenu_print(_("&Print This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, 'prtopt');
		submenu_print(_("&Email This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, null, 1);

		ui_msgs::display_note(ui_view::get_gl_view_str(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment")));

		//    hyperlink_params($path_to_root . "/purchasing/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");

		hyperlink_params(
			$_SERVER['PHP_SELF'], _("Enter another supplier &payment"), "supplier_id=" . $_POST['supplier_id']);

		ui_view::display_footer_exit();
	}

	//----------------------------------------------------------------------------------------

	function check_inputs() {

		if (!get_post('supplier_id')) {
			ui_msgs::display_error(_("There is no supplier selected."));
			ui_view::set_focus('supplier_id');
			return false;
		}

		if ($_POST['amount'] == "") {
			$_POST['amount'] = price_format(0);
		}

		if (!check_num('amount', 0)) {
			ui_msgs::display_error(_("The entered amount is invalid or less than zero."));
			ui_view::set_focus('amount');
			return false;
		}

		if (isset($_POST['charge']) && !check_num('charge', 0)) {
			ui_msgs::display_error(_("The entered amount is invalid or less than zero."));
			ui_view::set_focus('charge');
			return false;
		}

		if (isset($_POST['charge']) && input_num('charge') > 0) {
			$charge_acct = DB_Company::get_pref('bank_charge_act');
			if (get_gl_account($charge_acct) == false) {
				ui_msgs::display_error(_("The Bank Charge Account has not been set in System and General GL Setup."));
				ui_view::set_focus('charge');
				return false;
			}
		}

		if (isset($_POST['_ex_rate']) && !check_num('_ex_rate', 0.000001)) {
			ui_msgs::display_error(_("The exchange rate must be numeric and greater than zero."));
			ui_view::set_focus('_ex_rate');
			return false;
		}

		if ($_POST['discount'] == "") {
			$_POST['discount'] = 0;
		}

		if (!check_num('discount', 0)) {
			ui_msgs::display_error(_("The entered discount is invalid or less than zero."));
			ui_view::set_focus('amount');
			return false;
		}

		//if (input_num('amount') - input_num('discount') <= 0)
		if (input_num('amount') <= 0) {
			ui_msgs::display_error(_("The total of the amount and the discount is zero or negative. Please enter positive values."));
			ui_view::set_focus('amount');
			return false;
		}

		if (!Dates::is_date($_POST['DatePaid'])) {
			ui_msgs::display_error(_("The entered date is invalid."));
			ui_view::set_focus('DatePaid');
			return false;
		}
		elseif (!Dates::is_date_in_fiscalyear($_POST['DatePaid']))
		{
			ui_msgs::display_error(_("The entered date is not in fiscal year."));
			ui_view::set_focus('DatePaid');
			return false;
		}
		if (!Refs::is_valid($_POST['ref'])) {
			ui_msgs::display_error(_("You must enter a reference."));
			ui_view::set_focus('ref');
			return false;
		}

		if (!is_new_reference($_POST['ref'], ST_SUPPAYMENT)) {
			ui_msgs::display_error(_("The entered reference is already in use."));
			ui_view::set_focus('ref');
			return false;
		}

		$_SESSION['alloc']->amount = -input_num('amount');

		if (isset($_POST["TotalNumberOfAllocs"]))
			return Allocation::check_allocations();
		else
			return true;
	}

	//----------------------------------------------------------------------------------------

	function handle_add_payment() {
		$supp_currency = Banking::get_supplier_currency($_POST['supplier_id']);
		$bank_currency = Banking::get_bank_account_currency($_POST['bank_account']);
		$comp_currency = Banking::get_company_currency();
		if ($comp_currency != $bank_currency && $bank_currency != $supp_currency)
			$rate = 0;
		else
			$rate = input_num('_ex_rate');

		$payment_id = add_supp_payment($_POST['supplier_id'], $_POST['DatePaid'],
			$_POST['bank_account'], input_num('amount'), input_num('discount'),
			$_POST['ref'], $_POST['memo_'], $rate, input_num('charge'));
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

		meta_forward($_SERVER['PHP_SELF'], "AddedID=$payment_id&supplier_id=" . $_POST['supplier_id']);
	}

	//----------------------------------------------------------------------------------------

	if (isset($_POST['ProcessSuppPayment'])) {
		/*First off  check for valid inputs */
		if (check_inputs() == true) {
			handle_add_payment();
			Renderer::end_page();
			exit;
		}
	}

	//----------------------------------------------------------------------------------------

	start_form();

	start_outer_table(Config::get('tables.style2') . " width=60%", 5);

	table_section(1);

	supplier_list_row(_("Payment To:"), 'supplier_id', null, false, true);

	if (!isset($_POST['bank_account'])) // first page call
		$_SESSION['alloc'] = new allocation(ST_SUPPAYMENT, 0);

	ui_globals::set_global_supplier($_POST['supplier_id']);

	bank_accounts_list_row(_("From Bank Account:"), 'bank_account', null, true);

	table_section(2);

	ref_row(_("Reference:"), 'ref', '', Refs::get_next(ST_SUPPAYMENT));

	date_row(_("Date Paid") . ":", 'DatePaid', '', true, 0, 0, 0, null, true);

	table_section(3);

	$supplier_currency = Banking::get_supplier_currency($_POST['supplier_id']);
	$bank_currency = Banking::get_bank_account_currency($_POST['bank_account']);
	if ($bank_currency != $supplier_currency) {
		ui_view::exchange_rate_display($bank_currency, $supplier_currency, $_POST['DatePaid'], true);
	}

	amount_row(_("Bank Charge:"), 'charge');

	end_outer_table(1); // outer table

	if ($bank_currency == $supplier_currency) {
		div_start('alloc_tbl');
		Allocation::show_allocatable(false);
		div_end();
	}

	start_table(Config::get('tables.style') . "  width=60%");
	amount_row(_("Amount of Discount:"), 'discount');
	amount_row(_("Amount of Payment:"), 'amount');
	textarea_row(_("Memo:"), 'memo_', null, 22, 4);
	end_table(1);

	if ($bank_currency != $supplier_currency) {
		ui_msgs::display_note(_("The amount and discount are in the bank account's currency."), 0, 1);
	}

	submit_center('ProcessSuppPayment', _("Enter Payment"), true, '', 'default');

	end_form();

	Renderer::end_page();
?>
