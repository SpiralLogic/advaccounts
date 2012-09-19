<?php
  use ADV\App\Dimensions;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Suppliers"), SA_SUPPLIER, Input::_request('frame'));
  Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
  if (isset($_GET['creditor_id'])) {
    $_POST['creditor_id'] = $_GET['creditor_id'];
  }
  $new_supplier = Input::_post('creditor_id') == '';
  if (isset($_POST['submit'])) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    /* actions to take once the user has clicked the submit button
                   ie the page has called itself with some user input */
    //first off validate inputs sensible
    if (strlen($_POST['name']) == 0 || $_POST['name'] == "") {
      $input_error = 1;
      Event::error(_("The supplier name must be entered."));
      JS::_setFocus('name');
    }
    if (strlen($_POST['ref']) == 0 || $_POST['ref'] == "") {
      $input_error = 1;
      Event::error(_("The supplier short name must be entered."));
      JS::_setFocus('ref');
    }
    if ($input_error != 1) {
      if (!$new_supplier) {
        $sql = "UPDATE suppliers SET name=" . DB::_escape($_POST['name']) . ",
				ref=" . DB::_escape($_POST['ref']) . ",
 address=" . DB::_escape($_POST['address']) . ",
 address=" . DB::_escape($_POST['address']) . ",
 phone=" . DB::_escape($_POST['phone']) . ",
 phone2=" . DB::_escape($_POST['phone2']) . ",
 fax=" . DB::_escape($_POST['fax']) . ",
 gst_no=" . DB::_escape($_POST['gst_no']) . ",
 email=" . DB::_escape($_POST['email']) . ",
 website=" . DB::_escape($_POST['website']) . ",
 contact=" . DB::_escape($_POST['contact']) . ",
 account_no=" . DB::_escape($_POST['account_no']) . ",
 bank_account=" . DB::_escape($_POST['bank_account']) . ",
 credit_limit=" . Validation::input_num('credit_limit', 0) . ",
 dimension_id=" . DB::_escape($_POST['dimension_id']) . ",
 dimension2_id=" . DB::_escape($_POST['dimension2_id']) . ",
 curr_code=" . DB::_escape($_POST['curr_code']) . ",
 payment_terms=" . DB::_escape($_POST['payment_terms']) . ",
				payable_account=" . DB::_escape($_POST['payable_account']) . ",
				purchase_account=" . DB::_escape($_POST['purchase_account']) . ",
				payment_discount_account=" . DB::_escape($_POST['payment_discount_account']) . ",
 notes=" . DB::_escape($_POST['notes']) . ",
				tax_group_id=" . DB::_escape($_POST['tax_group_id']) . " WHERE creditor_id = " . DB::_escape($_POST['creditor_id']);
        DB::_query($sql, "The supplier could not be updated");
        DB::_updateRecordStatus($_POST['creditor_id'], $_POST['inactive'], 'suppliers', 'creditor_id');
        Ajax::_activate('creditor_id'); // in case of status change
        Event::success(_("Supplier has been updated."));
      } else {
        $sql
          = "INSERT INTO suppliers (name, ref, address, address, phone, phone2, fax, gst_no, email, website,
				contact, account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
				payment_terms, payable_account, purchase_account, payment_discount_account, notes, tax_group_id)
				VALUES (" . DB::_escape($_POST['name']) . ", " . DB::_escape($_POST['ref']) . ", " . DB::_escape($_POST['address']) . ", " . DB::_escape(
          $_POST['address']
        ) . ", " . DB::_escape($_POST['phone']) . ", " . DB::_escape($_POST['phone2']) . ", " . DB::_escape($_POST['fax']) . ", " . DB::_escape(
          $_POST['gst_no']
        ) . ", " . DB::_escape($_POST['email']) . ", " . DB::_escape($_POST['website']) . ", " . DB::_escape($_POST['contact']) . ", " . DB::_escape(
          $_POST['account_no']
        ) . ", " . DB::_escape($_POST['bank_account']) . ", " . Validation::input_num('credit_limit', 0) . ", " . DB::_escape(
          $_POST['dimension_id']
        ) . ", " . DB::_escape($_POST['dimension2_id']) . ", " . DB::_escape($_POST['curr_code']) . ", " . DB::_escape($_POST['payment_terms']) . ", " . DB::_escape(
          $_POST['payable_account']
        ) . ", " . DB::_escape($_POST['purchase_account']) . ", " . DB::_escape(
          $_POST['payment_discount_account']
        ) . ", " . DB::_escape($_POST['notes']) . ", " . DB::_escape($_POST['tax_group_id']) . ")";
        DB::_query($sql, "The supplier could not be added");
        $_POST['creditor_id'] = DB::_insertId();
        $new_supplier         = false;
        Event::success(_("A new supplier has been added."));
        Ajax::_activate('_page_body');
      }
    }
  } elseif (isset($_POST['delete']) && $_POST['delete'] != "") {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete = 0;
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'creditor_trans' , purch_orders
    $sql    = "SELECT COUNT(*) FROM creditor_trans WHERE creditor_id=" . DB::_escape($_POST['creditor_id']);
    $result = DB::_query($sql, "check failed");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("Cannot delete this supplier because there are transactions that refer to this supplier."));
    } else {
      $sql    = "SELECT COUNT(*) FROM purch_orders WHERE creditor_id=" . DB::_escape($_POST['creditor_id']);
      $result = DB::_query($sql, "check failed");
      $myrow  = DB::_fetchRow($result);
      if ($myrow[0] > 0) {
        $cancel_delete = 1;
        Event::error(_("Cannot delete the supplier record because purchase orders have been created against this supplier."));
      }
    }
    if ($cancel_delete == 0) {
      $sql = "DELETE FROM suppliers WHERE creditor_id=" . DB::_escape($_POST['creditor_id']);
      DB::_query($sql, "check failed");
      unset($_SESSION['creditor_id']);
      $new_supplier = true;
      Ajax::_activate('_page_body');
    } //end if Delete supplier
  }
  Forms::start();
  if (Validation::check(Validation::SUPPLIERS)) {
    Table::start('noborder pad3');
    //	Table::start('noborder');
    echo '<tr>';
    Creditor::cells(_("Select a supplier: "), 'creditor_id', null, _('New supplier'), true, Input::_hasPost('show_inactive'));
    Forms::checkCells(_("Show inactive:"), 'show_inactive', null, true);
    echo '</tr>';
    Table::end();
    if (Input::_post('_show_inactive_update')) {
      Ajax::_activate('creditor_id');
      JS::_setFocus('creditor_id');
    }
  } else {
    Forms::hidden('creditor_id', Input::_post('creditor_id'));
  }
  Table::startOuter('standard');
  Table::section(1);
  if (!$new_supplier) {
    //SupplierID exists - either passed when calling the form or from the form itself
    $myrow                             = Creditor::get($_POST['creditor_id']);
    $_POST['name']                     = $myrow["name"];
    $_POST['ref']                      = $myrow["ref"];
    $_POST['address']                  = $myrow["address"];
    $_POST['address']                  = $myrow["address"];
    $_POST['phone']                    = $myrow["phone"];
    $_POST['phone2']                   = $myrow["phone2"];
    $_POST['fax']                      = $myrow["fax"];
    $_POST['gst_no']                   = $myrow["gst_no"];
    $_POST['email']                    = $myrow["email"];
    $_POST['website']                  = $myrow["website"];
    $_POST['contact']                  = $myrow["contact"];
    $_POST['account_no']               = $myrow["account_no"];
    $_POST['bank_account']             = $myrow["bank_account"];
    $_POST['dimension_id']             = $myrow["dimension_id"];
    $_POST['dimension2_id']            = $myrow["dimension2_id"];
    $_POST['curr_code']                = $myrow["curr_code"];
    $_POST['payment_terms']            = $myrow["payment_terms"];
    $_POST['credit_limit']             = Num::_priceFormat($myrow["credit_limit"]);
    $_POST['tax_group_id']             = $myrow["tax_group_id"];
    $_POST['payable_account']          = $myrow["payable_account"];
    $_POST['purchase_account']         = $myrow["purchase_account"];
    $_POST['payment_discount_account'] = $myrow["payment_discount_account"];
    $_POST['notes']                    = $myrow["notes"];
    $_POST['inactive']                 = $myrow["inactive"];
  } else {
    $_POST['name']                     = $_POST['ref'] = $_POST['address'] = $_POST['address'] = $_POST['tax_group_id'] = $_POST['website'] = $_POST['account_no'] = $_POST['notes'] = '';
    $_POST['dimension_id']             = 0;
    $_POST['dimension2_id']            = 0;
    $_POST['sales_type']               = -1;
    $_POST['email']                    = $_POST['phone'] = $_POST['phone2'] = $_POST['fax'] = $_POST['gst_no'] = $_POST['contact'] = $_POST['bank_account'] = '';
    $_POST['payment_terms']            = '';
    $_POST['credit_limit']             = Num::_priceFormat(0);
    $company_record                    = DB_Company::get_prefs();
    $_POST['curr_code']                = $company_record["curr_default"];
    $_POST['payable_account']          = $company_record["creditors_act"];
    $_POST['purchase_account']         = $company_record["default_cogs_act"];
    $_POST['payment_discount_account'] = $company_record['pyt_discount_act'];
    $_POST['inactive']                 = 0;
  }
  Table::sectionTitle(_("Name and Contact"));
  Forms::textRow(_("Supplier Name:"), 'name', null, 42, 40);
  Forms::textRow(_("Supplier Short Name:"), 'ref', null, 30, 30);
  Forms::textRow(_("Contact Person:"), 'contact', null, 42, 40);
  Forms::textRow(_("Phone Number:"), 'phone', null, 32, 30);
  Forms::textRow(_("Secondary Phone Number:"), 'phone2', null, 32, 30);
  Forms::textRow(_("Fax Number:"), 'fax', null, 32, 30);
  Forms::emailRow(_("Email:"), 'email', null, 35, 55);
  Forms::linkRow(_("Website:"), 'website', null, 35, 55);
  Forms::textRow(_("Our Customer No:"), 'account_no', null, 42, 40);
  Table::sectionTitle(_("Addresses"));
  Forms::textareaRow(_("Mailing Address:"), 'address', null, 35, 5);
  Forms::textareaRow(_("Physical Address:"), 'address', null, 35, 5);
  Table::section(2);
  Table::sectionTitle(_("Purchasing"));
  Forms::textRow(_("GSTNo:"), 'gst_no', null, 42, 40);
  Forms::textRow(_("Bank Name/Account:"), 'bank_account', null, 42, 40);
  Forms::AmountRow(_("Credit Limit:"), 'credit_limit', null);
  if (!$new_supplier) {
    Table::label(_("Supplier's Currency:"), $_POST['curr_code']);
    Forms::hidden('curr_code', $_POST['curr_code']);
  } else {
    GL_Currency::row(_("Supplier's Currency:"), 'curr_code', null);
  }
  Tax_Groups::row(_("Tax Group:"), 'tax_group_id', null);
  GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', null);
  Table::sectionTitle(_("Accounts"));
  GL_UI::all_row(_("Accounts Payable Account:"), 'payable_account', $_POST['payable_account']);
  GL_UI::all_row(_("Purchase Account:"), 'purchase_account', $_POST['purchase_account']);
  GL_UI::all_row(_("Purchase Discount Account:"), 'payment_discount_account', $_POST['payment_discount_account']);
  $dim = DB_Company::get_pref('use_dimension');
  if ($dim >= 1) {
    Table::sectionTitle(_("Dimension"));
    Dimensions::select_row(_("Dimension") . " 1:", 'dimension_id', null, true, " ", false, 1);
    if ($dim > 1) {
      Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', null, true, " ", false, 2);
    }
  }
  if ($dim < 1) {
    Forms::hidden('dimension_id', 0);
  }
  if ($dim < 2) {
    Forms::hidden('dimension2_id', 0);
  }
  Table::sectionTitle(_("General"));
  Forms::textareaRow(_("General Notes:"), 'notes', null, 35, 5);
  Forms::recordStatusListRow(_("Supplier status:"), 'inactive');
  Table::endOuter(1);
  Display::div_start('controls');
  if (!$new_supplier) {
    Forms::submitCenterBegin(
      'submit',
      _("Update Supplier"),
      _('Update supplier data'),
      Input::_request('frame') ? true : 'default'
    );
    Forms::submitReturn('select', Input::_post('creditor_id'), _("Select this supplier and return to document entry."));
    Forms::submitCenterEnd('delete', _("Delete Supplier"), _('Delete supplier data if have been never used'), true);
  } else {
    Forms::submitCenter('submit', _("Add New Supplier Details"), true, '', 'default');
  }
  Display::div_end();
  Forms::hidden('frame', Input::_request('frame'));
  Forms::end();
  Page::end();


