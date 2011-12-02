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
	$page_security = 'SA_BANKACCOUNT';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Bank Accounts"));
	Page::simple_mode();

	if ($Mode == 'ADD_ITEM' || $Mode == 'UPDATE_ITEM') {
		//initialise no input errors assumed initially before we test
		$input_error = 0;
		//first off validate inputs sensible
		if (strlen($_POST['bank_account_name']) == 0) {
			$input_error = 1;
			Errors::error(_("The bank account name cannot be empty."));
			JS::set_focus('bank_account_name');
		}
		if ($input_error != 1) {
			if ($selected_id != -1) {
				GL_BankAccount::update(
					$selected_id, $_POST['account_code'],
					$_POST['account_type'], $_POST['bank_account_name'],
					$_POST['bank_name'], $_POST['bank_account_number'],
					$_POST['bank_address'], $_POST['BankAccountCurrency'],
					$_POST['dflt_curr_act']
				);
				Errors::notice(_('Bank account has been updated'));
			} else {
				GL_BankAccount::add(
					$_POST['account_code'], $_POST['account_type'],
					$_POST['bank_account_name'], $_POST['bank_name'],
					$_POST['bank_account_number'], $_POST['bank_address'],
					$_POST['BankAccountCurrency'], $_POST['dflt_curr_act']
				);
				Errors::notice(_('New bank account has been added'));
			}
			$Mode = 'RESET';
		}
	}
	elseif ($Mode == 'Delete')
	{
		//the link to delete a selected record was clicked instead of the submit button
		$cancel_delete = 0;
		$acc = DB::escape($selected_id);
		// PREVENT DELETES IF DEPENDENT RECORDS IN 'bank_trans'
		$sql = "SELECT COUNT(*) FROM bank_trans WHERE bank_act=$acc";
		$result = DB::query($sql, "check failed");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			$cancel_delete = 1;
			Errors::error(_("Cannot delete this bank account because transactions have been created using this account."));
		}
		$sql = "SELECT COUNT(*) FROM sales_pos WHERE pos_account=$acc";
		$result = DB::query($sql, "check failed");
		$myrow = DB::fetch_row($result);
		if ($myrow[0] > 0) {
			$cancel_delete = 1;
			Errors::error(_("Cannot delete this bank account because POS definitions have been created using this account."));
		}
		if (!$cancel_delete) {
			GL_BankAccount::delete($selected_id);
			Errors::notice(_('Selected bank account has been deleted'));
		} //end if Delete bank account
		$Mode = 'RESET';
	}
	if ($Mode == 'RESET') {
		$selected_id = -1;
		$_POST['bank_name'] = $_POST['bank_account_name'] = '';
		$_POST['bank_account_number'] = $_POST['bank_address'] = '';
	}
	/* Always show the list of accounts */
	$sql
	 = "SELECT account.*, gl_account.account_name
	FROM bank_accounts account, chart_master gl_account
	WHERE account.account_code = gl_account.account_code";
	if (!check_value('show_inactive')) {
		$sql .= " AND !account.inactive";
	}
	$sql .= " ORDER BY account_code, bank_curr_code";
	$result = DB::query($sql, "could not get bank accounts");
	Errors::check_db_error("The bank accounts set up could not be retreived", $sql);
	start_form();
	start_table(Config::get('tables_style') . "  style='width:80%'");
	$th = array(
		_("Account Name"), _("Type"), _("Currency"), _("GL Account"),
		_("Bank"), _("Number"), _("Bank Address"), _("Dflt"), '', ''
	);
	inactive_control_column($th);
	table_header($th);
	$k = 0;
	$bank_account_types = unserialize(TYPE_BANK_ACCOUNTS);
	while ($myrow = DB::fetch($result))
	{
		alt_table_row_color($k);
		label_cell($myrow["bank_account_name"], "nowrap");
		label_cell($bank_account_types[$myrow["account_type"]], "nowrap");
		label_cell($myrow["bank_curr_code"], "nowrap");
		label_cell($myrow["account_code"] . " " . $myrow["account_name"], "nowrap");
		label_cell($myrow["bank_name"], "nowrap");
		label_cell($myrow["bank_account_number"], "nowrap");
		label_cell($myrow["bank_address"]);
		if ($myrow["dflt_curr_act"]) {
			label_cell(_("Yes"));
		} else {
			label_cell(_("No"));
		}
		inactive_control_cell($myrow["id"], $myrow["inactive"], 'bank_accounts', 'id');
		edit_button_cell("Edit" . $myrow["id"], _("Edit"));
		delete_button_cell("Delete" . $myrow["id"], _("Delete"));
		end_row();
	}
	inactive_control_row($th);
	end_table(1);
	$is_editing = $selected_id != -1;
	start_table(Config::get('tables_style2'));
	if ($is_editing) {
		if ($Mode == 'Edit') {
			$myrow = GL_BankAccount::get($selected_id);
			$_POST['account_code'] = $myrow["account_code"];
			$_POST['account_type'] = $myrow["account_type"];
			$_POST['bank_name'] = $myrow["bank_name"];
			$_POST['bank_account_name'] = $myrow["bank_account_name"];
			$_POST['bank_account_number'] = $myrow["bank_account_number"];
			$_POST['bank_address'] = $myrow["bank_address"];
			$_POST['BankAccountCurrency'] = $myrow["bank_curr_code"];
			$_POST['dflt_curr_act'] = $myrow["dflt_curr_act"];
		}
		hidden('selected_id', $selected_id);
		hidden('account_code');
		hidden('account_type');
		hidden('BankAccountCurrency', $_POST['BankAccountCurrency']);
		JS::set_focus('bank_account_name');
	}
	text_row(_("Bank Account Name:"), 'bank_account_name', null, 50, 100);
	if ($is_editing) {
		$bank_account_types = unserialize(TYPE_BANK_ACCOUNTS);

		label_row(_("Account Type:"), $bank_account_types[$_POST['account_type']]);
	} else {
		bank_account_types_list_row(_("Account Type:"), 'account_type', null);
	}
	if ($is_editing) {
		label_row(_("Bank Account Currency:"), $_POST['BankAccountCurrency']);
	} else {
		currencies_list_row(_("Bank Account Currency:"), 'BankAccountCurrency', null);
	}
	yesno_list_row(_("Default currency account:"), 'dflt_curr_act');
	if ($is_editing) {
		label_row(_("Bank Account GL Code:"), $_POST['account_code']);
	} else {
		gl_all_accounts_list_row(_("Bank Account GL Code:"), 'account_code', null);
	}
	text_row(_("Bank Name:"), 'bank_name', null, 50, 60);
	text_row(_("Bank Account Number:"), 'bank_account_number', null, 30, 60);
	textarea_row(_("Bank Address:"), 'bank_address', null, 40, 5);
	end_table(1);
	submit_add_or_update_center($selected_id == -1, '', 'both');
	end_form();
	end_page();
?>
