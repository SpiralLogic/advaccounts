<?php
  use ADV\App\Dimensions;

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
  Page::start(_($help_context = "Customers"), SA_CUSTOMER, Input::_request('frame'));
  if (isset($_GET['debtor_id'])) {
    $_POST['debtor_id'] = $_GET['debtor_id'];
  }
  $new_customer = (!isset($_POST['debtor_id']) || $_POST['debtor_id'] == "");
  if (isset($_POST['submit'])) {
    if (strlen($_POST['CustName']) == 0) {
      Event::error(_("The customer name cannot be empty."));
      JS::_setFocus('CustName');
      return false;
    }
    if (strlen($_POST['cust_ref']) == 0) {
      Event::error(_("The customer short name cannot be empty."));
      JS::_setFocus('cust_ref');
      return false;
    }
    if (!Validation::is_num('credit_limit', 0)) {
      Event::error(_("The credit limit must be numeric and not less than zero."));
      JS::_setFocus('credit_limit');
      return false;
    }
    if (!Validation::is_num('payment_discount', 0, 100)) {
      Event::error(_("The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0."));
      JS::_setFocus('payment_discount');
      return false;
    }
    if (!Validation::is_num('discount', 0, 100)) {
      Event::error(_("The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0."));
      JS::_setFocus('discount');
      return false;
    }
    if ($new_customer == false) {
      $sql = "UPDATE debtors SET name=" . DB::_escape($_POST['CustName']) . ",
    				debtor_ref=" . DB::_escape($_POST['cust_ref']) . ",
    				address=" . DB::_escape($_POST['address']) . ",
    				tax_id=" . DB::_escape($_POST['tax_id']) . ",
    				curr_code=" . DB::_escape($_POST['curr_code']) . ",
    				email=" . DB::_escape($_POST['email']) . ",
    				dimension_id=" . DB::_escape($_POST['dimension_id']) . ",
    				dimension2_id=" . DB::_escape($_POST['dimension2_id']) . ",
    	 credit_status=" . DB::_escape($_POST['credit_status']) . ",
    	 payment_terms=" . DB::_escape($_POST['payment_terms']) . ",
    	 discount=" . Validation::input_num('discount') / 100 . ",
    	 payment_discount=" . Validation::input_num('payment_discount') / 100 . ",
    	 credit_limit=" . Validation::input_num('credit_limit') . ",
    	 sales_type = " . DB::_escape($_POST['sales_type']) . ",
    	 notes=" . DB::_escape($_POST['notes']) . "
    	 WHERE debtor_id = " . DB::_escape($_POST['debtor_id']);
      DB::_query($sql, "The customer could not be updated");
      DB::_updateRecordStatus($_POST['debtor_id'], $_POST['inactive'], 'debtors', 'debtor_id');
      Ajax::_activate('debtor_id'); // in case of status change
      Event::success(_("Customer has been updated."));
    } else { //it is a new customer
      DB::_begin();
      $sql
        = "INSERT INTO debtors (name, debtor_ref, address, tax_id, email, dimension_id, dimension2_id,
    				curr_code, credit_status, payment_terms, discount, payment_discount,credit_limit,
    				sales_type, notes) VALUES (" . DB::_escape($_POST['CustName']) . ", " . DB::_escape($_POST['cust_ref']) . ", " . DB::_escape($_POST['address']) . ", " . DB::_escape(
        $_POST['tax_id']
      ) . "," . DB::_escape($_POST['email']) . ", " . DB::_escape($_POST['dimension_id']) . ", " . DB::_escape(
        $_POST['dimension2_id']
      ) . ", " . DB::_escape($_POST['curr_code']) . ",
    				" . DB::_escape($_POST['credit_status']) . ", " . DB::_escape($_POST['payment_terms']) . ", " . Validation::input_num('discount') / 100 . ",
    				" . Validation::input_num('payment_discount') / 100 . ", " . Validation::input_num('credit_limit') . ", " . DB::_escape($_POST['sales_type']) . ", " . DB::_escape(
        $_POST['notes']
      ) . ")";
      DB::_query($sql, "The customer could not be added");
      $_POST['debtor_id'] = DB::_insertId();
      $new_customer       = false;
      DB::_commit();
      Event::success(_("A new customer has been added."));
      Ajax::_activate('_page_body');
    }
  }
  if (isset($_POST['delete'])) {
    //the link to delete a selected record was clicked instead of the submit button
    $cancel_delete = 0;
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'debtor_trans'
    $sel_id = DB::_escape($_POST['debtor_id']);
    $sql    = "SELECT COUNT(*) FROM debtor_trans WHERE debtor_id=$sel_id";
    $result = DB::_query($sql, "check failed");
    $myrow  = DB::_fetchRow($result);
    if ($myrow[0] > 0) {
      $cancel_delete = 1;
      Event::error(_("This customer cannot be deleted because there are transactions that refer to it."));
    } else {
      $sql    = "SELECT COUNT(*) FROM sales_orders WHERE debtor_id=$sel_id";
      $result = DB::_query($sql, "check failed");
      $myrow  = DB::_fetchRow($result);
      if ($myrow[0] > 0) {
        $cancel_delete = 1;
        Event::error(_("Cannot delete the customer record because orders have been created against it."));
      } else {
        $sql    = "SELECT COUNT(*) FROM branches WHERE debtor_id=$sel_id";
        $result = DB::_query($sql, "check failed");
        $myrow  = DB::_fetchRow($result);
        if ($myrow[0] > 0) {
          $cancel_delete = 1;
          Event::error(_("Cannot delete this customer because there are branch records set up against it."));
          //echo "<br> There are " . $myrow[0] . " branch records relating to this customer";
        }
      }
    }
    if ($cancel_delete == 0) { //ie not cancelled the delete as a result of above tests
      $sql = "DELETE FROM debtors WHERE debtor_id=$sel_id";
      DB::_query($sql, "cannot delete customer");
      Event::notice(_("Selected customer has been deleted."));
      unset($_POST['debtor_id']);
      $new_customer = true;
      Ajax::_activate('_page_body');
    } //end if Delete Customer
  }
  Validation::check(Validation::SALES_TYPES, _("There are no sales types defined. Please define at least one sales type before adding a customer."));
  Forms::start();
  if (Validation::check(Validation::CUSTOMERS, _('There are no customers.'))) {
    Table::start('noborder');
    echo '<tr>';
    Debtor::cells(_("Select a customer: "), 'debtor_id', null, _('New customer'), true, Input::_hasPost('show_inactive'));
    Forms::checkCells(_("Show inactive:"), 'show_inactive', null, true);
    echo '</tr>';
    Table::end();
    if (Input::_post('_show_inactive_update')) {
      Ajax::_activate('debtor_id');
      JS::_setFocus('debtor_id');
    }
  } else {
    Forms::hidden('debtor_id');
  }
  if ($new_customer) {
    $_POST['CustName']      = $_POST['cust_ref'] = $_POST['address'] = $_POST['tax_id'] = '';
    $_POST['dimension_id']  = 0;
    $_POST['dimension2_id'] = 0;
    $_POST['sales_type']    = -1;
    $_POST['email']         = '';
    $_POST['curr_code']     = Bank_Currency::for_company();
    $_POST['credit_status'] = -1;
    $_POST['payment_terms'] = $_POST['notes'] = '';
    $_POST['discount']      = $_POST['payment_discount'] = Num::_percentFormat(0);
    $_POST['credit_limit']  = Num::_priceFormat(DB_Company::get_pref('default_credit_limit'));
    $_POST['inactive']      = 0;
  } else {
    $sql                       = "SELECT * FROM debtors WHERE debtor_id = " . DB::_escape($_POST['debtor_id']);
    $result                    = DB::_query($sql, "check failed");
    $myrow                     = DB::_fetch($result);
    $_POST['CustName']         = $myrow["name"];
    $_POST['cust_ref']         = $myrow["debtor_ref"];
    $_POST['address']          = $myrow["address"];
    $_POST['tax_id']           = $myrow["tax_id"];
    $_POST['email']            = $myrow["email"];
    $_POST['dimension_id']     = $myrow["dimension_id"];
    $_POST['dimension2_id']    = $myrow["dimension2_id"];
    $_POST['sales_type']       = $myrow["sales_type"];
    $_POST['curr_code']        = $myrow["curr_code"];
    $_POST['credit_status']    = $myrow["credit_status"];
    $_POST['payment_terms']    = $myrow["payment_terms"];
    $_POST['discount']         = Num::_percentFormat($myrow["discount"] * 100);
    $_POST['payment_discount'] = Num::_percentFormat($myrow["payment_discount"] * 100);
    $_POST['credit_limit']     = Num::_priceFormat($myrow["credit_limit"]);
    $_POST['notes']            = $myrow["notes"];
    $_POST['inactive']         = $myrow["inactive"];
  }
  Table::startOuter('standard');
  Table::section(1);
  Table::sectionTitle(_("Name and Address"));
  Forms::textRow(_("Customer Name:"), 'CustName', $_POST['CustName'], 40, 80);
  Forms::textRow(_("Customer Short Name:"), 'cust_ref', null, 30, 30);
  Forms::textareaRow(_("Address:"), 'address', $_POST['address'], 35, 5);
  Forms::emailRow(_("Email:"), 'email', null, 40, 40);
  Forms::textRow(_("GSTNo:"), 'tax_id', null, 40, 40);
  if ($new_customer) {
    GL_Currency::row(_("Customer's Currency:"), 'curr_code', $_POST['curr_code']);
  } else {
    Table::label(_("Customer's Currency:"), $_POST['curr_code']);
    Forms::hidden('curr_code', $_POST['curr_code']);
  }
  Sales_Type::row(_("Sales Type/Price List:"), 'sales_type', $_POST['sales_type']);
  Table::section(2);
  Table::sectionTitle(_("Sales"));
  Forms::percentRow(_("Discount Percent:"), 'discount', $_POST['discount']);
  Forms::percentRow(_("Prompt Payment Discount Percent:"), 'payment_discount', $_POST['payment_discount']);
  Forms::AmountRow(_("Credit Limit:"), 'credit_limit', $_POST['credit_limit']);
  GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', $_POST['payment_terms']);
  Sales_CreditStatus::row(_("Credit Status:"), 'credit_status', $_POST['credit_status']);
  $dim = DB_Company::get_pref('use_dimension');
  if ($dim >= 1) {
    Dimensions::select_row(_("Dimension") . " 1:", 'dimension_id', $_POST['dimension_id'], true, " ", false, 1);
  }
  if ($dim > 1) {
    Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', $_POST['dimension2_id'], true, " ", false, 2);
  }
  if ($dim < 1) {
    Forms::hidden('dimension_id', 0);
  }
  if ($dim < 2) {
    Forms::hidden('dimension2_id', 0);
  }
  if (!$new_customer) {
    echo '<tr>';
    echo '<td>' . _('Customer branches') . ':</td>';
    Display::link_params_td(
      "/sales/manage/customer_branches.php",
      "<span class='bold'>" . (Input::_request('frame') ? _("Select or &Add") : _("&Add or Edit ")) . '</span>',
      "debtor_id=" . $_POST['debtor_id'] . (Input::_request('frame') ? '&frame=1' : '')
    );
    echo '</tr>';
  }
  Forms::textareaRow(_("General Notes:"), 'notes', null, 35, 5);
  Forms::recordStatusListRow(_("Customer status:"), 'inactive');
  Table::endOuter(1);
  Display::div_start('controls');
  if ($new_customer) {
    Forms::submitCenter('submit', _("Add New Customer"), true, '', 'default');
  } else {
    Forms::submitCenterBegin(
      'submit',
      _("Update Customer"),
      _('Update customer data'),
      Input::_request('frame') ? true : 'default'
    );
    Forms::submitReturn('select', Input::_post('debtor_id'), _("Select this customer and return to document entry."));
    Forms::submitCenterEnd('delete', _("Delete Customer"), _('Delete customer data if have been never used'), true);
  }
  Display::div_end();
  Forms::hidden('frame', Input::_request('frame'));
  Forms::end();
  Page::end();


