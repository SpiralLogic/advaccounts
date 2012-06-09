<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Chart of Accounts"), SA_GLACCOUNT);
  Validation::check(Validation::GL_ACCOUNT_GROUPS, _("There are no account groups defined. Please define at least one account group before entering accounts."));
  if (isset($_POST['_AccountList_update'])) {
    $_POST['selected_account'] = $_POST['AccountList'];
    unset($_POST['account_code']);
  }
  if (isset($_POST['selected_account'])) {
    $selected_account = $_POST['selected_account'];
  } elseif (isset($_GET['selected_account'])) {
    $selected_account = $_GET['selected_account'];
  } else {
    $selected_account = "";
  }
  if (isset($_POST['add']) || isset($_POST['update'])) {
    $input_error = 0;
    if (strlen($_POST['account_code']) == 0) {
      $input_error = 1;
      Event::error(_("The account code must be entered."));
      JS::set_focus('account_code');
    } elseif (strlen($_POST['account_name']) == 0) {
      $input_error = 1;
      Event::error(_("The account name cannot be empty."));
      JS::set_focus('account_name');
    } elseif (!Config::get('accounts.allowcharacters') && !is_numeric($_POST['account_code'])) {
      $input_error = 1;
      Event::error(_("The account code must be numeric."));
      JS::set_focus('account_code');
    }
    if ($input_error != 1) {
      if (Config::get('accounts.allowcharacters') == 2) {
        $_POST['account_code'] = strtoupper($_POST['account_code']);
      }
      if (!isset($_POST['account_tags'])) {
        $_POST['account_tags'] = array();
      }
      if ($selected_account) {
        if (GL_Account::update($_POST['account_code'], $_POST['account_name'], $_POST['account_type'], $_POST['account_code2'])
        ) {
          DB::update_record_status($_POST['account_code'], $_POST['inactive'], 'chart_master', 'account_code');
          Tags::update_associations(TAG_ACCOUNT, $_POST['account_code'], $_POST['account_tags']);
          Ajax::i()->activate('account_code'); // in case of status change
          Event::success(_("Account data has been updated."));
        }
      } else {
        if (GL_Account::add($_POST['account_code'], $_POST['account_name'], $_POST['account_type'], $_POST['account_code2'])
        ) {
          Tags::add_associations($_POST['account_code'], $_POST['account_tags']);
          Event::success(_("New account has been added."));
          $selected_account = $_POST['AccountList'] = $_POST['account_code'];
        }
      }
      Ajax::i()->activate('_page_body');
    }
  }
  if (isset($_POST['delete'])) {
    if (can_delete($selected_account)) {
      GL_Account::delete($selected_account);
      $selected_account = $_POST['AccountList'] = '';
      Tags::delete_associations(TAG_ACCOUNT, $selected_account, true);
      $selected_account = $_POST['AccountList'] = '';
      Event::notice(_("Selected account has been deleted"));
      unset($_POST['account_code']);
      Ajax::i()->activate('_page_body');
    }
  }
  Form::start();
  if (Validation::check(Validation::GL_ACCOUNTS)) {
    Table::start('tablestyle_noborder');
    Row::start();
    GL_UI::all_cells(null, 'AccountList', null, false, false, _('New account'), true, Form::hasPost('show_inactive'));
     Form::checkCells(_("Show inactive:"), 'show_inactive', null, true);
    Row::end();
    Table::end();
    if (Input::post('_show_inactive_update')) {
      Ajax::i()->activate('AccountList');
      JS::set_focus('AccountList');
    }
  }
  Display::br(1);
  Table::start('tablestyle2');
  if ($selected_account != "") {
    //editing an existing account
    $myrow                  = GL_Account::get($selected_account);
    $_POST['account_code']  = $myrow["account_code"];
    $_POST['account_code2'] = $myrow["account_code2"];
    $_POST['account_name']  = $myrow["account_name"];
    $_POST['account_type']  = $myrow["account_type"];
    $_POST['inactive']      = $myrow["inactive"];
    $tags_result            = Tags::get_all_associated_with_record(TAG_ACCOUNT, $selected_account);
    $tagids                 = array();
    while ($tag = DB::fetch($tags_result)) {
      $tagids[] = $tag['id'];
    }
    $_POST['account_tags'] = $tagids;
    Form::hidden('account_code', $_POST['account_code']);
    Form::hidden('selected_account', $selected_account);
    Row::label(_("Account Code:"), $_POST['account_code']);
  } else {
    if (!isset($_POST['account_code'])) {
      $_POST['account_tags'] = array();
      $_POST['account_code'] = $_POST['account_code2'] = '';
      $_POST['account_name'] = $_POST['account_type'] = '';
      $_POST['inactive']     = 0;
    }
     Form::textRowEx(_("Account Code:"), 'account_code', 11);
  }
   Form::textRowEx(_("Account Code 2:"), 'account_code2', 11);
   Form::textRowEx(_("Account Name:"), 'account_name', 60);
  GL_Type::row(_("Account Group:"), 'account_type', null);
  Tags::row(_("Account Tags:"), 'account_tags', 5, TAG_ACCOUNT, true);
   Form::recordStatusListRow(_("Account status:"), 'inactive');
  Table::end(1);
  if ($selected_account == "") {
    Form::submitCenter('add', _("Add Account"), true, '', 'default');
  } else {
    Form::submitCenterBegin('update', _("Update Account"), '', 'default');
    Form::submitCenterEnd('delete', _("Delete account"), '', true);
  }
  Form::end();
  Page::end();
  /**
   * @param $selected_account
   *
   * @return bool
   */
  function can_delete($selected_account)
  {
    if ($selected_account == "") {
      return false;
    }
    $acc    = DB::escape($selected_account);
    $sql    = "SELECT COUNT(*) FROM gl_trans WHERE account=$acc";
    $result = DB::query($sql, "Couldn't test for existing transactions");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because transactions have been created using this account."));

      return false;
    }
    $sql
            = "SELECT COUNT(*) FROM company WHERE debtors_act=$acc
            OR pyt_discount_act=$acc
            OR creditors_act=$acc
            OR bank_charge_act=$acc
            OR exchange_diff_act=$acc
            OR profit_loss_year_act=$acc
            OR retained_earnings_act=$acc
            OR freight_act=$acc
            OR default_sales_act=$acc
            OR default_sales_discount_act=$acc
            OR default_prompt_payment_act=$acc
            OR default_inventory_act=$acc
            OR default_cogs_act=$acc
            OR default_adj_act=$acc
            OR default_inv_sales_act=$acc
            OR default_assembly_act=$acc";
    $result = DB::query($sql, "Couldn't test for default company GL codes");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because it is used as one of the company default GL accounts."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM bank_accounts WHERE account_code=$acc";
    $result = DB::query($sql, "Couldn't test for bank accounts");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because it is used by a bank account."));

      return false;
    }
    $sql
            = "SELECT COUNT(*) FROM stock_master WHERE
            inventory_account=$acc
            OR cogs_account=$acc
            OR adjustment_account=$acc
            OR sales_account=$acc";
    $result = DB::query($sql, "Couldn't test for existing stock GL codes");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because it is used by one or more Items."));

      return false;
    }
    $sql    = "SELECT COUNT(*) FROM tax_types WHERE sales_gl_code=$acc OR purchasing_gl_code=$acc";
    $result = DB::query($sql, "Couldn't test for existing tax GL codes");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because it is used by one or more Taxes."));

      return false;
    }
    $sql
            = "SELECT COUNT(*) FROM branches WHERE
            sales_account=$acc
            OR sales_discount_account=$acc
            OR receivables_account=$acc
            OR payment_discount_account=$acc";
    $result = DB::query($sql, "Couldn't test for existing cust branch GL codes");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because it is used by one or more Customer Branches."));

      return false;
    }
    $sql
            = "SELECT COUNT(*) FROM suppliers WHERE
            purchase_account=$acc
            OR payment_discount_account=$acc
            OR payable_account=$acc";
    $result = DB::query($sql, "Couldn't test for existing suppliers GL codes");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because it is used by one or more suppliers."));

      return false;
    }
    $sql
            = "SELECT COUNT(*) FROM quick_entry_lines WHERE
            dest_id=$acc AND UPPER(LEFT(action, 1)) <> 'T'";
    $result = DB::query($sql, "Couldn't test for existing suppliers GL codes");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this account because it is used by one or more Quick Entry Lines."));

      return false;
    }

    return true;
  }
