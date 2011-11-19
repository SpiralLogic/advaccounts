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
	$page_security = 'SA_BANKTRANSFER';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(800, 500);
	Page::start(_($help_context = "Transfer between Bank Accounts"));
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	//----------------------------------------------------------------------------------------
	if (isset($_GET['AddedID'])) {
		$trans_no = $_GET['AddedID'];
		$trans_type = ST_BANKTRANSFER;
		Errors::notice(_("Transfer has been entered"));
		Display::note(ui_view::get_gl_view_str($trans_type, $trans_no, _("&View the GL Journal Entries for this Transfer")));
		hyperlink_no_params($_SERVER['PHP_SELF'], _("Enter & Another Transfer"));
		Page::footer_exit();
	}
	if (isset($_POST['_DatePaid_changed'])) {
		$Ajax->activate('_ex_rate');
	}
	//----------------------------------------------------------------------------------------
	function gl_payment_controls()
	{
		$home_currency = Banking::get_company_currency();
		start_form();
		start_outer_table(Config::get('tables_style2'), 5);
		table_section(1);
		bank_accounts_list_row(_("From Account:"), 'FromBankAccount', null, true);
		bank_accounts_list_row(_("To Account:"), 'ToBankAccount', null, true);
		date_row(_("Transfer Date:"), 'DatePaid', '', null, 0, 0, 0, null, true);
		$from_currency = Banking::get_bank_account_currency($_POST['FromBankAccount']);
		$to_currency = Banking::get_bank_account_currency($_POST['ToBankAccount']);
		if ($from_currency != "" && $to_currency != "" && $from_currency != $to_currency) {
			amount_row(_("Amount:"), 'amount', null, null, $from_currency);
			amount_row(_("Bank Charge:"), 'charge', null, null, $from_currency);
			Display::exchange_rate($from_currency, $to_currency, $_POST['DatePaid']);
		} else {
			amount_row(_("Amount:"), 'amount');
			amount_row(_("Bank Charge:"), 'charge');
		}
		table_section(2);
		ref_row(_("Reference:"), 'ref', '', Refs::get_next(ST_BANKTRANSFER));
		textarea_row(_("Memo:"), 'memo_', null, 40, 4);
		end_outer_table(1); // outer table
		submit_center('AddPayment', _("Enter Transfer"), true, '', 'default');
		end_form();
	}

	//----------------------------------------------------------------------------------------
	function check_valid_entries()
	{
		if (!Dates::is_date($_POST['DatePaid'])) {
			Errors::error(_("The entered date is invalid ."));
			JS::set_focus('DatePaid');
			return false;
		}
		if (!Dates::is_date_in_fiscalyear($_POST['DatePaid'])) {
			Errors::error(_("The entered date is not in fiscal year . "));
			JS::set_focus('DatePaid');
			return false;
		}
		if (!Validation::is_num('amount', 0)) {
			Errors::error(_("The entered amount is invalid or less than zero ."));
			JS::set_focus('amount');
			return false;
		}
		if (isset($_POST['charge']) && !Validation::is_num('charge', 0)) {
			Errors::error(_("The entered amount is invalid or less than zero ."));
			JS::set_focus('charge');
			return false;
		}
		if (isset($_POST['charge']) && input_num('charge') > 0 && DB_Company::get_pref('bank_charge_act') == '') {
			Errors::error(_("The Bank Charge Account has not been set in System and General GL Setup ."));
			JS::set_focus('charge');
			return false;
		}
		if (!Refs::is_valid($_POST['ref'])) {
			Errors::error(_("You must enter a reference ."));
			JS::set_focus('ref');
			return false;
		}
		if (!is_new_reference($_POST['ref'], ST_BANKTRANSFER)) {
			Errors::error(_("The entered reference is already in use."));
			JS::set_focus('ref');
			return false;
		}
		if ($_POST['FromBankAccount'] == $_POST['ToBankAccount']) {
			Errors::error(_("The source and destination bank accouts cannot be the same ."));
			JS::set_focus('ToBankAccount');
			return false;
		}
		return true;
	}

	//----------------------------------------------------------------------------------------
	function handle_add_deposit()
	{
		$trans_no = GL_Bank::add_bank_transfer($_POST['FromBankAccount'], $_POST['ToBankAccount'],
			$_POST['DatePaid'], input_num('amount'), $_POST['ref'],
			$_POST['memo_'], input_num('charge'));
		meta_forward($_SERVER['PHP_SELF'], "AddedID = $trans_no");
	}

	//----------------------------------------------------------------------------------------
	if (isset($_POST['AddPayment'])) {
		if (check_valid_entries() == true) {
			handle_add_deposit();
		}
	}
	gl_payment_controls();
	end_page();
?>
