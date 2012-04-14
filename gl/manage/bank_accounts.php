<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  Page::start(_($help_context = "Bank Accounts"), SA_BANKACCOUNT);
  list($Mode, $selected_id) = Page::simple_mode();
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    //first off validate inputs sensible
    if (strlen($_POST['bank_account_name']) == 0) {
      $input_error = 1;
      Event::error(_("The bank account name cannot be empty."));
      JS::set_focus('bank_account_name');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        Bank_Account::update($selected_id, $_POST['account_code'], $_POST['account_type'], $_POST['bank_account_name'], $_POST['bank_name'], $_POST['bank_account_number'], $_POST['bank_address'], $_POST['BankAccountCurrency'], $_POST['dflt_curr_act']);
        Event::success(_('Bank account has been updated'));
      }
      else {
        Bank_Account::add($_POST['account_code'], $_POST['account_type'], $_POST['bank_account_name'], $_POST['bank_name'], $_POST['bank_account_number'], $_POST['bank_address'], $_POST['BankAccountCurrency'], $_POST['dflt_curr_act']);
        Event::success(_('New bank account has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  elseif ($Mode == MODE_DELETE) {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete = 0;
    $acc = DB::escape($selected_id);
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'bank_trans'
    $sql = "SELECT COUNT(*) FROM bank_trans WHERE bank_act=$acc";
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this bank account because transactions have been created using this account."));
    }
    $sql = "SELECT COUNT(*) FROM sales_pos WHERE pos_account=$acc";
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this bank account because POS definitions have been created using this account."));
    }
    if (!$cancel_delete) {
      Bank_Account::delete($selected_id);
      Event::notice(_('Selected bank account has been deleted'));
    } //end if Delete bank account
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $_POST['bank_name'] = $_POST['bank_account_name'] = '';
    $_POST['bank_account_number'] = $_POST['bank_address'] = '';
  }
  /* Always show the list of accounts */
  $sql = "SELECT account.*, gl_account.account_name
	FROM bank_accounts account, chart_master gl_account
	WHERE account.account_code = gl_account.account_code";
  if (!check_value('show_inactive')) {
    $sql .= " AND !account.inactive";
  }
  $sql .= " ORDER BY account_code, bank_curr_code";
  $result = DB::query($sql, "could not get bank accounts");
  start_form();
  start_table('tablestyle width80');
  $th = array(
    _("Account Name"), _("Type"), _("Currency"), _("GL Account"), _("Bank"), _("Number"), _("Bank Address"), _("Dflt"), '', ''
  );
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  global $bank_account_types;
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    label_cell($myrow["bank_account_name"], ' class="nowrap"');
    label_cell($bank_account_types[$myrow["account_type"]], ' class="nowrap"');
    label_cell($myrow["bank_curr_code"], ' class="nowrap"');
    label_cell($myrow["account_code"] . " " . $myrow["account_name"], ' class="nowrap"');
    label_cell($myrow["bank_name"], ' class="nowrap"');
    label_cell($myrow["bank_account_number"], ' class="nowrap"');
    label_cell($myrow["bank_address"]);
    if ($myrow["dflt_curr_act"]) {
      label_cell(_("Yes"));
    }
    else {
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
  start_table('tablestyle2');
  if ($is_editing) {
    if ($Mode == MODE_EDIT) {
      $myrow = Bank_Account::get($selected_id);
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
  text_row(_("Bank Account Name:"), 'bank_account_name', NULL, 50, 100);
  if ($is_editing) {
    global $bank_account_types;
    label_row(_("Account Type:"), $bank_account_types[$_POST['account_type']]);
  }
  else {
    Bank_Account::type_row(_("Account Type:"), 'account_type', NULL);
  }
  if ($is_editing) {
    label_row(_("Bank Account Currency:"), $_POST['BankAccountCurrency']);
  }
  else {
    GL_Currency::row(_("Bank Account Currency:"), 'BankAccountCurrency', NULL);
  }
  yesno_list_row(_("Default currency account:"), 'dflt_curr_act');
  if ($is_editing) {
    label_row(_("Bank Account GL Code:"), $_POST['account_code']);
  }
  else {
    GL_UI::all_row(_("Bank Account GL Code:"), 'account_code', NULL);
  }
  text_row(_("Bank Name:"), 'bank_name', NULL, 50, 60);
  text_row(_("Bank Account Number:"), 'bank_account_number', NULL, 30, 60);
  textarea_row(_("Bank Address:"), 'bank_address', NULL, 40, 5);
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
