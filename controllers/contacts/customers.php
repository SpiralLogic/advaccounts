<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Session::i()->App->set_selected('Debtors');
  if (AJAX_REFERRER) {
    if (isset($_GET['term'])) {
      $data = Debtor::search($_GET['term']);
      JS::renderJSON($data);
    }
  }
  if (isset($_POST['name'])) {
    $data['company'] = $customer = new Debtor();
    $data['company']->save($_POST);
  }
  elseif (Input::request('id', Input::NUMERIC) > 0) {
    $data['company'] = $customer = new Debtor(Input::request('id', Input::NUMERIC));
    $data['contact_log'] = Contact_Log::read($customer->id, CT_CUSTOMER);
    $_SESSION['global_customer'] = $customer->id;
  }
  else {
    $data['company'] = $customer = new Debtor();
  }
  if (AJAX_REFERRER) {
    $data['status'] = $customer->getStatus();
    JS::renderJSON($data);
  }
  JS::footerFile("/js/company.js");
  Page::start(_($help_context = "Customers"), SA_CUSTOMER, Input::request('frame'));
  Validation::check(Validation::SALES_TYPES, _("There are no sales types defined. Please define at least one sales type before adding a customer."));
  Validation::check(Validation::SALESPERSONS, _("There are no sales people defined in the system. At least one sales person is required before proceeding."));
  Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
  Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
  Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
  JS::onload("Company.setValues(" . json_encode($data) . ");");
  $currentContact = $customer->contacts[$customer->defaultContact];
  $currentBranch = $customer->branches[$customer->defaultBranch];
  if (isset($_POST['delete'])) {
    $customer->delete();
    $status = $customer->getStatus();
    Event::notice($status['message']);
  }
  if (!Input::get('frame') && !Input::get('id')) {
    /** @noinspection PhpUndefinedMethodInspection */
    HTML::div('companysearch');
    HTML::table(array('class' => 'marginauto bold'));
    HTML::tr(TRUE)->td(TRUE);
    UI::search('customer', array('label' => 'Search Customer:', 'size' => 80, 'callback' => 'Company.fetch', 'focus' => TRUE));
    HTML::td()->tr->table->div;
  }
  start_form();
  $menu = new MenuUI();
  $menu->startTab('Details', 'Customer Details', '#', 'text-align:center');
  HTML::div('companyIDs');
  HTML::table(array("class" => "marginauto bold"))->tr(TRUE)->td(TRUE);
  HTML::label(array(
    'for' => 'name', 'content' => 'Customer name:'
  ), FALSE);
  HTML::input('name', array(
    'value' => $customer->name, 'name' => 'name', 'size' => 50
  ));
  HTML::td()->td(array(
    'content' => _("Customer ID: "), "style" => "width:90px"
  ), FALSE)->td(TRUE);
  HTML::input('id', array(
    'value' => $customer->id, 'name' => 'id', 'size' => 10, 'maxlength' => '7'
  ));
  HTML::td()->tr->table->div;
  start_outer_table('tablestyle2');
  table_section(1);
  table_section_title(_("Shipping Details"), 2);
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::tr(TRUE)->td('branchSelect', array(
    'colspan' => 2, 'class' => "center"
  ));
  UI::select('branchList', array_map(function($v) {
    return $v->br_name;
  }, $customer->branches), array('name' => 'branchList'));
  UI::button('addBranch', 'Add new address', array(
    'class' => 'invis', 'name' => 'addBranch'
  ));
  HTML::td()->tr;
  text_row(_("Contact:"), 'br_contact_name', $currentBranch->contact_name, 35, 40);
  //hidden('br_contact_name', $customer->contact_name);
  text_row(_("Phone Number:"), 'br_phone', $currentBranch->phone, 35, 30);
  text_row(_("2nd Phone Number:"), 'br_phone2', $currentBranch->phone2, 35, 30);
  text_row(_("Fax Number:"), 'br_fax', $currentBranch->fax, 35, 30);
  email_row(_("Email:"), 'br_email', $currentBranch->email, 35, 55);
  textarea_row(_("Street:"), 'br_br_address', $currentBranch->br_address, 35, 2);
  $branch_postcode = new Contact_Postcode(array(
      'city' => array('br_city', $currentBranch->city),
      'state' => array('br_state', $currentBranch->state),
      'postcode' => array('br_postcode', $currentBranch->postcode)
    )
  );
  $branch_postcode->render();
  table_section(2);
  table_section_title(_("Accounts Details"), 2);
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::tr(TRUE)->td(array(
    'class' => "center", 'colspan' => 2
  ));
  UI::button('useShipAddress', _("Use shipping details"), array('name' => 'useShipAddress'));
  HTML::td(FALSE)->_tr();
  text_row(_("Accounts Contact:"), 'acc_contact_name', $customer->accounts->contact_name, 35, 40);
  text_row(_("Phone Number:"), 'acc_phone', $customer->accounts->phone, 35, 30);
  text_row(_("Secondary Phone Number:"), 'acc_phone2', $customer->accounts->phone2, 35, 30);
  text_row(_("Fax Number:"), 'acc_fax', $customer->accounts->fax, 35, 30);
  email_row(_("E-mail:"), 'acc_email', $customer->accounts->email, 35, 55);
  textarea_row(_("Street:"), 'acc_br_address', $customer->accounts->br_address, 35, 2);
  $accounts_postcode = new Contact_Postcode(array(
      'city' => array('acc_city', $customer->accounts->city),
      'state' => array('acc_state', $customer->accounts->state),
      'postcode' => array('acc_postcode', $customer->accounts->postcode)
    )
  );
  $accounts_postcode->render();
  end_outer_table(1);
  $menu->endTab()->startTab('Accounts', 'Accounts');
  hidden('accounts_id', $customer->accounts->accounts_id);
  start_outer_table('tablestyle2');
  table_section(1);
  table_section_title(_("Accounts Details:"), 2);
  percent_row(_("Discount Percent:"), 'discount', $customer->discount, (User::i()->can_access(SA_CUSTOMER_CREDIT)) ? "" : " disabled");
  percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $customer->pymt_discount, (User::i()->can_access(SA_CUSTOMER_CREDIT)) ? "" :
    " disabled");
  amount_row(_("Credit Limit:"), 'credit_limit', $customer->credit_limit, NULL, NULL, 0, (User::i()->can_access(SA_CUSTOMER_CREDIT)) ? "" :
    " disabled");
  Sales_Type::row(_("Sales Type/Price List:"), 'sales_type', $customer->sales_type);
  record_status_list_row(_("Customer status:"), 'inactive');
  text_row(_("GSTNo:"), 'tax_id', $customer->tax_id, 35, 40);
  if (!$customer->id) {
    GL_Currency::row(_("Customer's Currency:"), 'curr_code', $customer->curr_code);
  }
  else {
    label_row(_("Customer's Currency:"), $customer->curr_code);
    hidden('curr_code', $customer->curr_code);
  }
  GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', $customer->payment_terms);
  Sales_CreditStatus::row(_("Credit Status:"), 'credit_status', $customer->credit_status);
  table_section(2);
  table_section_title(_("Contact log:"), 1);
  start_row();
  HTML::td(array(
    'class' => 'ui-widget-content center'
  ));
  UI::button('addLog', "Add log entry")->td->tr->tr(TRUE)->td(NULL)->textarea('messageLog', array('cols' => 50, 'rows' => 20));
  Contact_Log::read($customer->id, 'C');
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::textarea()->td->tr;
  end_outer_table(1);
  $menu->endTab()->startTab('Customer Contacts', 'Customer Contacts');
  HTML::div(array('style' => 'text-align:center'))->div('Contacts', array('style' => 'min-height:200px;'));
  HTML::script('contact', array('type' => 'text/x-jquery-tmpl'))->table('contact-${id}', array(
    'class' => '', 'style' => 'display:inline-block'
  ))->tr(TRUE)->td(array(
    'content' => '${name}',
    'class' => 'tablehead',
    'colspan' => 2
  ))->td->tr;
  text_row("Name:", 'con_name-${id}', '${name}', 35, 40);
  text_row("Phone:", 'con_phone1-${id}', '${phone1}', 35, 40);
  text_row("Phone2:", 'con_phone2-${id}', '${phone2}', 35, 40);
  text_row("Email:", 'con_email-${id}', '${email}', 35, 40);
  text_row("Dept:", 'con_department-${id}', '${department}', 35, 40);
  HTML::td()->tr->table->script->div->div;
  $menu->endTab()->startTab('Extra Shipping Info', 'Extra Shipping Info');
  start_outer_table('tablestyle2');
  hidden('branch_id', $currentBranch->branch_id);
  table_section(1);
  table_section_title(_("Sales"));
  Sales_UI::persons_row(_("Sales Person:"), 'br_salesman', $currentBranch->salesman);
  Sales_UI::areas_row(_("Sales Area:"), 'br_area', $currentBranch->area);
  Sales_UI::groups_row(_("Sales Group:"), 'br_group_no', $currentBranch->group_no);
  Inv_Location::row(_("Default Inventory Location:"), 'br_default_location', $currentBranch->default_location);
  Sales_UI::shippers_row(_("Default Shipping Company:"), 'br_default_ship_via', $currentBranch->default_ship_via);
  Tax_Groups::row(_("Tax Group:"), 'br_tax_group_id', $currentBranch->tax_group_id);
  yesno_list_row(_("Disable this Branch:"), 'br_disable_trans', $currentBranch->disable_trans);
  HTML::tr(true)->td(array(
    'content' => _("Website ID: "), "class" => "label"
  ), FALSE)->td(true);
  HTML::input('webid', array(
    'value' => $customer->webid, 'disabled' => TRUE, 'name' => 'webid', 'size' => 10, 'maxlength' => '7'
  ));
  HTML::td()->tr;
  table_section(2);
  table_section_title(_("GL Accounts"));
  GL_UI::all_row(_("Sales Account:"), 'br_sales_account', $currentBranch->sales_account, FALSE, FALSE, TRUE);
  GL_UI::all_row(_("Sales Discount Account:"), 'br_sales_discount_account', $currentBranch->sales_discount_account);
  GL_UI::all_row(_("Accounts Receivable Account:"), 'br_receivables_account', $currentBranch->receivables_account);
  GL_UI::all_row(_("Prompt Payment Discount Account:"), 'br_payment_discount_account', $currentBranch->payment_discount_account);
  table_section_title(_("Notes"));
  textarea_row(_("General Notes:"), 'br_notes', $currentBranch->notes, 35, 4);
  end_outer_table(1);

  $menu->endTab();
  $menu->startTab('Invoices', 'Invoices');
  echo "<div id='invoiceFrame' data-src='" . BASE_URL . "sales/inquiry/customer_allocation_inquiry.php?customer_id=" . $customer->id . "' ></div> ";
  $menu->endTab()->render();  hidden('frame', Input::request('frame'));
HTML::div();
  end_form();

  HTML::div('contactLog', array(
    'title' => 'New contact log entry', 'class' => 'ui-widget-overlay', 'style' => 'display:none;'
  ));
  hidden('type', CT_CUSTOMER);
  start_table();
  label_row('Date:', date('Y-m-d H:i:s'));
  text_row('Contact:', 'contact_name', $customer->accounts->contact_name, 35, 40);
  textarea_row('Entry:', 'message', '', 100, 10);
  end_table();
  HTML::_div()->div(array('class' => 'center width50'));
  UI::button('btnConfirm', ($customer->id) ? 'Update Customer' : 'New Customer', array(
    'name' => 'submit', 'type' => 'submit', 'class' => 'ui-helper-hidden', 'style' => 'margin:10px;'
  ));
  UI::button('btnCancel', 'Cancel', array(
    'name' => 'cancel', 'type' => 'submit', 'style' => 'margin:10px;'
  ));
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::_div();
  if (!Input::get('frame')) {
    HTML::div('shortcuts', array('class' => 'width50 center'));
    $shortcuts = new MenuUI(array('noajax' => TRUE));
    $shortcuts->addLink('Create Quote', 'Create Quote for this customer!', '/sales/sales_order_entry.php?type=32&add=' . ST_SALESQUOTE . '&customer_id=', 'id');
    $shortcuts->addLink('Create Order', 'Create Order for this customer!', '/sales/sales_order_entry.php?type=30&add=' . ST_SALESORDER . '&customer_id=', 'id');
    $shortcuts->addLink('Print Statement', 'Print Statement for this Customer!', '/reporting/prn_redirect.php?REP_ID=108&PARAM_2=0&PARAM_4=0&PARAM_5=0&PARAM_6=0&PARAM_0=', 'id', TRUE);
    $shortcuts->addJSLink('Email Statement', 'Email Statement for this Customer!', 'emailTab', <<<JS
			Adv.o.tabs.tabs1.bind('tabsselect',function(e,o) {if (o.index!=3)return; return false;});
JS
    );
    $shortcuts->addLink('Customer Payment', 'Make customer payment!', '/sales/customer_payments.php?customer_id=', 'id');
    $shortcuts->render();
    /** @noinspection PhpUndefinedMethodInspection */
    HTML::_div();
    UI::emailDialogue(CT_CUSTOMER);
  }
  HTML::_div();

  Page::end(TRUE);