<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Suppliers"), SA_SUPPLIER, Input::request('frame'));
  Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
  if (isset($_GET['supplier_id'])) {
    $_POST['supplier_id'] = $_GET['supplier_id'];
  }
  $new_supplier = Form::getPost('supplier_id') == '';
  if (isset($_POST['submit'])) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    /* actions to take once the user has clicked the submit button
                   ie the page has called itself with some user input */
    //first off validate inputs sensible
    if (strlen($_POST['name']) == 0 || $_POST['name'] == "") {
      $input_error = 1;
      Event::error(_("The supplier name must be entered."));
      JS::set_focus('name');
    }
    if (strlen($_POST['ref']) == 0 || $_POST['ref'] == "") {
      $input_error = 1;
      Event::error(_("The supplier short name must be entered."));
      JS::set_focus('ref');
    }
    if ($input_error != 1) {
      if (!$new_supplier) {
        $sql = "UPDATE suppliers SET name=" . DB::escape($_POST['name']) . ",
				ref=" . DB::escape($_POST['ref']) . ",
 address=" . DB::escape($_POST['address']) . ",
 address=" . DB::escape($_POST['address']) . ",
 phone=" . DB::escape($_POST['phone']) . ",
 phone2=" . DB::escape($_POST['phone2']) . ",
 fax=" . DB::escape($_POST['fax']) . ",
 gst_no=" . DB::escape($_POST['gst_no']) . ",
 email=" . DB::escape($_POST['email']) . ",
 website=" . DB::escape($_POST['website']) . ",
 contact=" . DB::escape($_POST['contact']) . ",
 account_no=" . DB::escape($_POST['account_no']) . ",
 bank_account=" . DB::escape($_POST['bank_account']) . ",
 credit_limit=" . Validation::input_num('credit_limit', 0) . ",
 dimension_id=" . DB::escape($_POST['dimension_id']) . ",
 dimension2_id=" . DB::escape($_POST['dimension2_id']) . ",
 curr_code=" . DB::escape($_POST['curr_code']) . ",
 payment_terms=" . DB::escape($_POST['payment_terms']) . ",
				payable_account=" . DB::escape($_POST['payable_account']) . ",
				purchase_account=" . DB::escape($_POST['purchase_account']) . ",
				payment_discount_account=" . DB::escape($_POST['payment_discount_account']) . ",
 notes=" . DB::escape($_POST['notes']) . ",
				tax_group_id=" . DB::escape($_POST['tax_group_id']) . " WHERE supplier_id = " . DB::escape($_POST['supplier_id']);
        DB::query($sql, "The supplier could not be updated");
        DB::update_record_status($_POST['supplier_id'], $_POST['inactive'], 'suppliers', 'supplier_id');
        Ajax::i()->activate('supplier_id'); // in case of status change
        Event::success(_("Supplier has been updated."));
      }
      else {
        $sql = "INSERT INTO suppliers (name, ref, address, address, phone, phone2, fax, gst_no, email, website,
				contact, account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
				payment_terms, payable_account, purchase_account, payment_discount_account, notes, tax_group_id)
				VALUES (" . DB::escape($_POST['name']) . ", " . DB::escape($_POST['ref']) . ", " . DB::escape($_POST['address']) . ", " . DB::escape($_POST['address']) . ", " . DB::escape($_POST['phone']) . ", " . DB::escape($_POST['phone2']) . ", " . DB::escape($_POST['fax']) . ", " . DB::escape($_POST['gst_no']) . ", " . DB::escape($_POST['email']) . ", " . DB::escape($_POST['website']) . ", " . DB::escape($_POST['contact']) . ", " . DB::escape($_POST['account_no']) . ", " . DB::escape($_POST['bank_account']) . ", " . Validation::input_num('credit_limit',
          0) . ", " . DB::escape($_POST['dimension_id']) . ", " . DB::escape($_POST['dimension2_id']) . ", " . DB::escape($_POST['curr_code']) . ", " . DB::escape($_POST['payment_terms']) . ", " . DB::escape($_POST['payable_account']) . ", " . DB::escape($_POST['purchase_account']) . ", " . DB::escape($_POST['payment_discount_account']) . ", " . DB::escape($_POST['notes']) . ", " . DB::escape($_POST['tax_group_id']) . ")";
        DB::query($sql, "The supplier could not be added");
        $_POST['supplier_id'] = DB::insert_id();
        $new_supplier = FALSE;
        Event::success(_("A new supplier has been added."));
        Ajax::i()->activate('_page_body');
      }
    }
  }
  elseif (isset($_POST['delete']) && $_POST['delete'] != "") {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete = 0;
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'creditor_trans' , purch_orders
    $sql = "SELECT COUNT(*) FROM creditor_trans WHERE supplier_id=" . DB::escape($_POST['supplier_id']);
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this supplier because there are transactions that refer to this supplier."));
    }
    else {
      $sql = "SELECT COUNT(*) FROM purch_orders WHERE supplier_id=" . DB::escape($_POST['supplier_id']);
      $result = DB::query($sql, "check failed");
      $myrow = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        $cancel_delete = 1;
        Event::error(_("Cannot delete the supplier record because purchase orders have been created against this supplier."));
      }
    }
    if ($cancel_delete == 0) {
      $sql = "DELETE FROM suppliers WHERE supplier_id=" . DB::escape($_POST['supplier_id']);
      DB::query($sql, "check failed");
      unset($_SESSION['supplier_id']);
      $new_supplier = TRUE;
      Ajax::i()->activate('_page_body');
    } //end if Delete supplier
  }
  Form::start();
  if (Validation::check(Validation::SUPPLIERS)) {
    Table::start('tablestyle_noborder pad3');
    //	Table::start('tablestyle_noborder');
    Row::start();
    Creditor::cells(_("Select a supplier: "), 'supplier_id', NULL, _('New supplier'), TRUE, Form::hasPost('show_inactive'));
     Form::checkCells(_("Show inactive:"), 'show_inactive', NULL, TRUE);
    Row::end();
    Table::end();
    if (Form::getPost('_show_inactive_update')) {
      Ajax::i()->activate('supplier_id');
      JS::set_focus('supplier_id');
    }
  }
  else {
    Form::hidden('supplier_id', Form::getPost('supplier_id'));
  }
  Table::startOuter('tablestyle2');
  Table::section(1);
  if (!$new_supplier) {
    //SupplierID exists - either passed when calling the form or from the form itself
    $myrow = Creditor::get($_POST['supplier_id']);
    $_POST['name'] = $myrow["name"];
    $_POST['ref'] = $myrow["ref"];
    $_POST['address'] = $myrow["address"];
    $_POST['address'] = $myrow["address"];
    $_POST['phone'] = $myrow["phone"];
    $_POST['phone2'] = $myrow["phone2"];
    $_POST['fax'] = $myrow["fax"];
    $_POST['gst_no'] = $myrow["gst_no"];
    $_POST['email'] = $myrow["email"];
    $_POST['website'] = $myrow["website"];
    $_POST['contact'] = $myrow["contact"];
    $_POST['account_no'] = $myrow["account_no"];
    $_POST['bank_account'] = $myrow["bank_account"];
    $_POST['dimension_id'] = $myrow["dimension_id"];
    $_POST['dimension2_id'] = $myrow["dimension2_id"];
    $_POST['curr_code'] = $myrow["curr_code"];
    $_POST['payment_terms'] = $myrow["payment_terms"];
    $_POST['credit_limit'] = Num::price_format($myrow["credit_limit"]);
    $_POST['tax_group_id'] = $myrow["tax_group_id"];
    $_POST['payable_account'] = $myrow["payable_account"];
    $_POST['purchase_account'] = $myrow["purchase_account"];
    $_POST['payment_discount_account'] = $myrow["payment_discount_account"];
    $_POST['notes'] = $myrow["notes"];
    $_POST['inactive'] = $myrow["inactive"];
  }
  else {
    $_POST['name'] = $_POST['ref'] = $_POST['address'] = $_POST['address'] = $_POST['tax_group_id'] = $_POST['website'] = $_POST['account_no'] = $_POST['notes'] = '';
    $_POST['dimension_id'] = 0;
    $_POST['dimension2_id'] = 0;
    $_POST['sales_type'] = -1;
    $_POST['email'] = $_POST['phone'] = $_POST['phone2'] = $_POST['fax'] = $_POST['gst_no'] = $_POST['contact'] = $_POST['bank_account'] = '';
    $_POST['payment_terms'] = '';
    $_POST['credit_limit'] = Num::price_format(0);
    $company_record = DB_Company::get_prefs();
    $_POST['curr_code'] = $company_record["curr_default"];
    $_POST['payable_account'] = $company_record["creditors_act"];
    $_POST['purchase_account'] = $company_record["default_cogs_act"];
    $_POST['payment_discount_account'] = $company_record['pyt_discount_act'];
    $_POST['inactive'] = 0;
  }
  Table::sectionTitle(_("Name and Contact"));
   Form::textRow(_("Supplier Name:"), 'name', NULL, 42, 40);
   Form::textRow(_("Supplier Short Name:"), 'ref', NULL, 30, 30);
   Form::textRow(_("Contact Person:"), 'contact', NULL, 42, 40);
   Form::textRow(_("Phone Number:"), 'phone', NULL, 32, 30);
   Form::textRow(_("Secondary Phone Number:"), 'phone2', NULL, 32, 30);
   Form::textRow(_("Fax Number:"), 'fax', NULL, 32, 30);
   Form::emailRow(_("E-mail:"), 'email', NULL, 35, 55);
   Form::linkRow(_("Website:"), 'website', NULL, 35, 55);
   Form::textRow(_("Our Customer No:"), 'account_no', NULL, 42, 40);
  Table::sectionTitle(_("Addresses"));
   Form::textareaRow(_("Mailing Address:"), 'address', NULL, 35, 5);
   Form::textareaRow(_("Physical Address:"), 'address', NULL, 35, 5);
  Table::section(2);
  Table::sectionTitle(_("Purchasing"));
   Form::textRow(_("GSTNo:"), 'gst_no', NULL, 42, 40);
   Form::textRow(_("Bank Name/Account:"), 'bank_account', NULL, 42, 40);
   Form::AmountRow(_("Credit Limit:"), 'credit_limit', NULL);
  if (!$new_supplier) {
    Row::label(_("Supplier's Currency:"), $_POST['curr_code']);
    Form::hidden('curr_code', $_POST['curr_code']);
  }
  else {
    GL_Currency::row(_("Supplier's Currency:"), 'curr_code', NULL);
  }
  Tax_Groups::row(_("Tax Group:"), 'tax_group_id', NULL);
  GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', NULL);
  Table::sectionTitle(_("Accounts"));
  GL_UI::all_row(_("Accounts Payable Account:"), 'payable_account', $_POST['payable_account']);
  GL_UI::all_row(_("Purchase Account:"), 'purchase_account', $_POST['purchase_account']);
  GL_UI::all_row(_("Purchase Discount Account:"), 'payment_discount_account', $_POST['payment_discount_account']);
  $dim = DB_Company::get_pref('use_dimension');
  if ($dim >= 1) {
    Table::sectionTitle(_("Dimension"));
    Dimensions::select_row(_("Dimension") . " 1:", 'dimension_id', NULL, TRUE, " ", FALSE, 1);
    if ($dim > 1) {
      Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', NULL, TRUE, " ", FALSE, 2);
    }
  }
  if ($dim < 1) {
    Form::hidden('dimension_id', 0);
  }
  if ($dim < 2) {
    Form::hidden('dimension2_id', 0);
  }
  Table::sectionTitle(_("General"));
   Form::textareaRow(_("General Notes:"), 'notes', NULL, 35, 5);
   Form::recordStatusListRow(_("Supplier status:"), 'inactive');
  Table::endOuter(1);
  Display::div_start('controls');
  if (!$new_supplier) {
    Form::submitCenterBegin('submit', _("Update Supplier"), _('Update supplier data'), Input::request('frame') ? TRUE : 'default');
    Form::submitReturn('select', Form::getPost('supplier_id'), _("Select this supplier and return to document entry."));
    Form::submitCenterEnd('delete', _("Delete Supplier"), _('Delete supplier data if have been never used'), TRUE);
  }
  else {
    Form::submitCenter('submit', _("Add New Supplier Details"), TRUE, '', 'default');
  }
  Display::div_end();
  Form::hidden('frame', Input::request('frame'));
  Form::end();
  Page::end();


