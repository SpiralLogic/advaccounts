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

  Page::start(_($help_context = "Customers"), SA_CUSTOMER, Input::request('frame'));
  if (isset($_GET['debtor_id'])) {
    $_POST['customer_id'] = $_GET['debtor_id'];
  }
  $new_customer = (!isset($_POST['customer_id']) || $_POST['customer_id'] == "");
  if (isset($_POST['submit'])) {
    if (strlen($_POST['CustName']) == 0) {
      Event::error(_("The customer name cannot be empty."));
      JS::set_focus('CustName');
      return FALSE;
    }
    if (strlen($_POST['cust_ref']) == 0) {
      Event::error(_("The customer short name cannot be empty."));
      JS::set_focus('cust_ref');
      return FALSE;
    }
    if (!Validation::is_num('credit_limit', 0)) {
      Event::error(_("The credit limit must be numeric and not less than zero."));
      JS::set_focus('credit_limit');
      return FALSE;
    }
    if (!Validation::is_num('payment_discount', 0, 100)) {
      Event::error(_("The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0."));
      JS::set_focus('payment_discount');
      return FALSE;
    }
    if (!Validation::is_num('discount', 0, 100)) {
      Event::error(_("The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0."));
      JS::set_focus('discount');
      return FALSE;
    }
    if ($new_customer == FALSE) {
      $sql = "UPDATE debtors SET name=" . DB::escape($_POST['CustName']) . ",
    				debtor_ref=" . DB::escape($_POST['cust_ref']) . ",
    				address=" . DB::escape($_POST['address']) . ",
    				tax_id=" . DB::escape($_POST['tax_id']) . ",
    				curr_code=" . DB::escape($_POST['curr_code']) . ",
    				email=" . DB::escape($_POST['email']) . ",
    				dimension_id=" . DB::escape($_POST['dimension_id']) . ",
    				dimension2_id=" . DB::escape($_POST['dimension2_id']) . ",
    	 credit_status=" . DB::escape($_POST['credit_status']) . ",
    	 payment_terms=" . DB::escape($_POST['payment_terms']) . ",
    	 discount=" . Validation::input_num('discount') / 100 . ",
    	 payment_discount=" . Validation::input_num('payment_discount') / 100 . ",
    	 credit_limit=" . Validation::input_num('credit_limit') . ",
    	 sales_type = " . DB::escape($_POST['sales_type']) . ",
    	 notes=" . DB::escape($_POST['notes']) . "
    	 WHERE debtor_id = " . DB::escape($_POST['customer_id']);
      DB::query($sql, "The customer could not be updated");
      DB::update_record_status($_POST['customer_id'], $_POST['inactive'], 'debtors', 'debtor_id');
      Ajax::i()->activate('customer_id'); // in case of status change
      Event::success(_("Customer has been updated."));
    }
    else { //it is a new customer
      DB::begin();
      $sql
        = "INSERT INTO debtors (name, debtor_ref, address, tax_id, email, dimension_id, dimension2_id,
    				curr_code, credit_status, payment_terms, discount, payment_discount,credit_limit,
    				sales_type, notes) VALUES (" . DB::escape($_POST['CustName']) . ", " . DB::escape($_POST['cust_ref']) . ", " . DB::escape($_POST['address']) . ", " . DB::escape($_POST['tax_id']) . "," . DB::escape($_POST['email']) . ", " . DB::escape($_POST['dimension_id']) . ", " . DB::escape($_POST['dimension2_id']) . ", " . DB::escape($_POST['curr_code']) . ",
    				" . DB::escape($_POST['credit_status']) . ", " . DB::escape($_POST['payment_terms']) . ", " . Validation::input_num('discount') / 100 . ",
    				" . Validation::input_num('payment_discount') / 100 . ", " . Validation::input_num('credit_limit') . ", " . DB::escape($_POST['sales_type']) . ", " . DB::escape($_POST['notes']) . ")";
      DB::query($sql, "The customer could not be added");
      $_POST['customer_id'] = DB::insert_id();
      $new_customer = FALSE;
      DB::commit();
      Event::success(_("A new customer has been added."));
      Ajax::i()->activate('_page_body');
    }
  }
  if (isset($_POST['delete'])) {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete = 0;
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'
    $sel_id = DB::escape($_POST['customer_id']);
    $sql = "SELECT COUNT(*) FROM debtor_trans WHERE debtor_id=$sel_id";
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("This customer cannot be deleted because there are transactions that refer to it."));
    }
    else {
      $sql = "SELECT COUNT(*) FROM sales_orders WHERE debtor_id=$sel_id";
      $result = DB::query($sql, "check failed");
      $myrow = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        $cancel_delete = 1;
        Event::error(_("Cannot delete the customer record because orders have been created against it."));
      }
      else {
        $sql = "SELECT COUNT(*) FROM branches WHERE debtor_id=$sel_id";
        $result = DB::query($sql, "check failed");
        $myrow = DB::fetch_row($result);
        if ($myrow[0] > 0) {
          $cancel_delete = 1;
          Event::error(_("Cannot delete this customer because there are branch records set up against it."));
          //echo "<br> There are " . $myrow[0] . " branch records relating to this customer";
        }
      }
    }
    if ($cancel_delete == 0) { //ie not cancelled the delete as a result of above tests
      $sql = "DELETE FROM debtors WHERE debtor_id=$sel_id";
      DB::query($sql, "cannot delete customer");
      Event::notice(_("Selected customer has been deleted."));
      unset($_POST['customer_id']);
      $new_customer = TRUE;
      Ajax::i()->activate('_page_body');
    } //end if Delete Customer
  }
  Validation::check(Validation::SALES_TYPES, _("There are no sales types defined. Please define at least one sales type before adding a customer."));
  start_form();
  if (Validation::check(Validation::CUSTOMERS, _('There are no customers.'))) {
    Table::start('tablestyle_noborder');
    Row::start();
    Debtor::cells(_("Select a customer: "), 'customer_id', NULL, _('New customer'), TRUE, check_value('show_inactive'));
    check_cells(_("Show inactive:"), 'show_inactive', NULL, TRUE);
    Row::end();
    Table::end();
    if (get_post('_show_inactive_update')) {
      Ajax::i()->activate('customer_id');
      JS::set_focus('customer_id');
    }
  }
  else {
    hidden('customer_id');
  }
  if ($new_customer) {
    $_POST['CustName'] = $_POST['cust_ref'] = $_POST['address'] = $_POST['tax_id'] = '';
    $_POST['dimension_id'] = 0;
    $_POST['dimension2_id'] = 0;
    $_POST['sales_type'] = -1;
    $_POST['email'] = '';
    $_POST['curr_code'] = Bank_Currency::for_company();
    $_POST['credit_status'] = -1;
    $_POST['payment_terms'] = $_POST['notes'] = '';
    $_POST['discount'] = $_POST['payment_discount'] = Num::percent_format(0);
    $_POST['credit_limit'] = Num::price_format(DB_Company::get_pref('default_credit_limit'));
    $_POST['inactive'] = 0;
  }
  else {
    $sql = "SELECT * FROM debtors WHERE debtor_id = " . DB::escape($_POST['customer_id']);
    $result = DB::query($sql, "check failed");
    $myrow = DB::fetch($result);
    $_POST['CustName'] = $myrow["name"];
    $_POST['cust_ref'] = $myrow["debtor_ref"];
    $_POST['address'] = $myrow["address"];
    $_POST['tax_id'] = $myrow["tax_id"];
    $_POST['email'] = $myrow["email"];
    $_POST['dimension_id'] = $myrow["dimension_id"];
    $_POST['dimension2_id'] = $myrow["dimension2_id"];
    $_POST['sales_type'] = $myrow["sales_type"];
    $_POST['curr_code'] = $myrow["curr_code"];
    $_POST['credit_status'] = $myrow["credit_status"];
    $_POST['payment_terms'] = $myrow["payment_terms"];
    $_POST['discount'] = Num::percent_format($myrow["discount"] * 100);
    $_POST['payment_discount'] = Num::percent_format($myrow["payment_discount"] * 100);
    $_POST['credit_limit'] = Num::price_format($myrow["credit_limit"]);
    $_POST['notes'] = $myrow["notes"];
    $_POST['inactive'] = $myrow["inactive"];
  }
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Name and Address"));
  text_row(_("Customer Name:"), 'CustName', $_POST['CustName'], 40, 80);
  text_row(_("Customer Short Name:"), 'cust_ref', NULL, 30, 30);
  textarea_row(_("Address:"), 'address', $_POST['address'], 35, 5);
  email_row(_("E-mail:"), 'email', NULL, 40, 40);
  text_row(_("GSTNo:"), 'tax_id', NULL, 40, 40);
  if ($new_customer) {
    GL_Currency::row(_("Customer's Currency:"), 'curr_code', $_POST['curr_code']);
  }
  else {
    Row::label(_("Customer's Currency:"), $_POST['curr_code']);
    hidden('curr_code', $_POST['curr_code']);
  }
  Sales_Type::row(_("Sales Type/Price List:"), 'sales_type', $_POST['sales_type']);
  Table::section(2);
  Table::sectionTitle(_("Sales"));
  percent_row(_("Discount Percent:"), 'discount', $_POST['discount']);
  percent_row(_("Prompt Payment Discount Percent:"), 'payment_discount', $_POST['payment_discount']);
  amount_row(_("Credit Limit:"), 'credit_limit', $_POST['credit_limit']);
  GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', $_POST['payment_terms']);
  Sales_CreditStatus::row(_("Credit Status:"), 'credit_status', $_POST['credit_status']);
  $dim = DB_Company::get_pref('use_dimension');
  if ($dim >= 1) {
    Dimensions::select_row(_("Dimension") . " 1:", 'dimension_id', $_POST['dimension_id'], TRUE, " ", FALSE, 1);
  }
  if ($dim > 1) {
    Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', $_POST['dimension2_id'], TRUE, " ", FALSE, 2);
  }
  if ($dim < 1) {
    hidden('dimension_id', 0);
  }
  if ($dim < 2) {
    hidden('dimension2_id', 0);
  }
  if (!$new_customer) {
    Row::start();
    echo '<td>' . _('Customer branches') . ':</td>';
    Display::link_params_td("/sales/manage/customer_branches.php", "<span class='bold'>" . (Input::request('frame') ?
      _("Select or &Add") : _("&Add or Edit ")) . '</span>', "debtor_id=" . $_POST['customer_id'] . (Input::request('frame') ?
      '&frame=1' : ''));
    Row::end();
  }
  textarea_row(_("General Notes:"), 'notes', NULL, 35, 5);
  record_status_list_row(_("Customer status:"), 'inactive');
  Table::endOuter(1);
  Display::div_start('controls');
  if ($new_customer) {
    submit_center('submit', _("Add New Customer"), TRUE, '', 'default');
  }
  else {
    submit_center_first('submit', _("Update Customer"), _('Update customer data'), Input::request('frame') ? TRUE : 'default');
    submit_return('select', get_post('customer_id'), _("Select this customer and return to document entry."));
    submit_center_last('delete', _("Delete Customer"), _('Delete customer data if have been never used'), TRUE);
  }
  Display::div_end();
  hidden('frame', Input::request('frame'));
  end_form();
  Page::end();


