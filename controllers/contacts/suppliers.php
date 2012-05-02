<?php

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Session::i()->App->set_selected('Creditors');
  if (AJAX_REFERRER) {
    if (isset($_GET['term'])) {
      $data = Creditor::search($_GET['term']);
      JS::renderJSON($data);
    }
  }
  if (isset($_POST['supp_name'])) {
    $data['company'] = $supplier = new Creditor();
    $data['company']->save($_POST);
  }
  elseif (Input::request('id', Input::NUMERIC) > 0) {
    $data['company'] = $supplier = new Creditor(Input::request('id', Input::NUMERIC));
    $data['contact_log'] = Contact_Log::read($supplier->id, CT_SUPPLIER);
    Session::i()->setGlobal('creditor', $supplier->id);
  }
  else {
    $data['company'] = $supplier = new Creditor();
  }
  if (AJAX_REFERRER) {
    $data['status'] = $supplier->getStatus();
    JS::renderJSON($data);
  }
  JS::footerFile("/js/company.js");
  Page::start(_($help_context = "Suppliers"), SA_SUPPLIER, Input::request('frame'));
  Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
  Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
  Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
  JS::onload("Company.setValues(" . json_encode($data) . ");");
  $currentContact = $supplier->contacts[$supplier->defaultContact];
  if (isset($_POST['delete'])) {
    $supplier->delete();
    $status = $supplier->getStatus();
    Event::notice($status['message']);
  }
  if (!Input::get('frame') && !Input::get('id')) {
    /** @noinspection PhpUndefinedMethodInspection */
    HTML::div('companysearch');
    HTML::table(array('class' => 'marginauto bold'));
    HTML::tr(TRUE)->td(TRUE);
    UI::search('supplier', array('label' => 'Search Supplier:', 'size' => 80, 'callback' => 'Company.fetch', 'focus' => TRUE));
    HTML::td()->tr->table->div;
  }
  start_form();
  $menu = new MenuUI();
  $menu->startTab('Details', 'Supplier Details', '#', 'text-align:center');
  HTML::div('companyIDs');
  HTML::table(array("class" => "marginauto bold"))->tr(TRUE)->td(TRUE);
  HTML::label(array(
    'for' => 'supp_name', 'content' => 'Supplier name:'
  ), FALSE);
  HTML::input('supp_name', array(
    'value' => $supplier->supp_name, 'name' => 'supp_name', 'size' => 50
  ));
  HTML::td()->td(array(
    'content' => _("Supplier ID: "), "style" => "width:90px"
  ), FALSE)->td(TRUE);
  HTML::input('id', array(
    'value' => $supplier->id, 'name' => 'id', 'size' => 10, 'maxlength' => '7'
  ));
  HTML::td()->tr->table->div;
  start_outer_table('tablestyle2');
  table_section(1);
  table_section_title(_("Shipping Details"), 2);
  /** @noinspection PhpUndefinedMethodInspection */
  text_row(_("Contact:"), 'contact', $supplier->contact, 35, 40);
  //hidden('br_contact_name', $supplier->contact_name);
  text_row(_("Phone Number:"), 'phone', $supplier->phone, 35, 30);
  text_row(_("Fax Number:"), 'fax', $supplier->fax, 35, 30);
  email_row(_("Email:"), 'email', $supplier->email, 35, 55);

  textarea_row(_("Street:"), 'address', $supplier->address, 35, 2);
  $branch_postcode = new Contact_Postcode(array(
      'city' => array('city', $supplier->city),
      'state' => array('state', $supplier->state),
      'postcode' => array('postcode', $supplier->postcode)
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
  HTML::_td()->tr;
  text_row(_("Phone Number:"), 'supp_phone', $supplier->phone2, 35, 30);
  textarea_row(_("Address:"), 'supp_address', $supplier->supp_address, 35, 2);

  $supp_postcode = new Contact_Postcode(array(
      'city' => array('supp_city', $supplier->supp_city),
      'state' => array('supp_state', $supplier->supp_state),
      'postcode' => array('supp_postcode', $supplier->supp_postcode)
    )
  );
  $supp_postcode->render();
  end_outer_table(1);
  $menu->endTab()->startTab('Accounts', 'Accounts');
  start_outer_table('tablestyle2');
  table_section(1);
  table_section_title(_("Accounts Details:"), 2);
  percent_row(_("Prompt Payment Discount Percent:"), 'discount', $supplier->discount, (User::i()->can_access(SA_SUPPLIERCREDIT)) ? "" :
    " disabled");
  amount_row(_("Credit Limit:"), 'credit_limit', $supplier->credit_limit, NULL, NULL, 0, (User::i()->can_access(SA_SUPPLIERCREDIT)) ? "" :
    " disabled");
  record_status_list_row(_("Supplier status:"), 'inactive');
  text_row(_("GSTNo:"), 'tax_id', $supplier->tax_id, 35, 40);
  if (!$supplier->id) {
    GL_Currency::row(_("Supplier's Currency:"), 'curr_code', $supplier->curr_code);
  }
  else {
    label_row(_("Supplier's Currency:"), $supplier->curr_code);
    hidden('curr_code', $supplier->curr_code);
  }
  GL_UI::payment_terms_row(_("Pament Terms:"), 'payment_terms', $supplier->payment_terms);
  table_section_title(_("GL Accounts"));
  GL_UI::all_row(_("Accounts Receivable Account:"), 'payable_account', $supplier->payable_account);
  GL_UI::all_row(_("Prompt Payment Discount Account:"), 'payment_discount_account', $supplier->payment_discount_account);
  table_section_title(_("Notes"));
  textarea_row(_("General Notes:"), 'notes', $supplier->notes, 35, 4);
  table_section(2);
  table_section_title(_("Contact log:"), 1);
  start_row();
  HTML::td(array(
    'class' => 'ui-widget-content center'
  ));
  UI::button('addLog', "Add log entry")->td->tr->tr(TRUE)->td(NULL)->textarea('messageLog', array('cols' => 50, 'rows' => 20));
  Contact_Log::read($supplier->id, CT_SUPPLIER);
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::textarea()->td->tr;
  end_outer_table(1);
  $menu->endTab()->startTab('Supplier Contacts', 'Supplier Contacts');
  HTML::div(array('style' => 'text-align:center'))->div('Contacts', array('style' => 'min-height:200px;'));
  HTML::script('contact_tmpl', array('type' => 'text/x-jquery-tmpl'))->table('contact-${id}', array(
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

  $menu->endTab()->startTab('Invoices', 'Invoices');
  echo "<div id='invoiceFrame' data-src='" . BASE_URL . "purchases/inquiry/supplier_allocation_inquiry.php?supplier_id=" . $supplier->id . "' ></div> ";
  $menu->endTab()->render();
  hidden('frame', Input::request('frame'));
  HTML::div();

  end_form();
  HTML::div('contactLog', array(
    'title' => 'New contact log entry', 'class' => 'ui-widget-overlay', 'style' => 'display:none;'
  ));
  hidden('type', CT_SUPPLIER);
  start_table();
  label_row('Date:', date('Y-m-d H:i:s'));
  text_row('Contact:', 'contact_name', $supplier->contact_name, 35, 40);
  textarea_row('Entry:', 'message', '', 100, 10);
  end_table();
  HTML::_div()->div(array('class' => 'center width50'));
  UI::button('btnConfirm', ($supplier->id) ? 'Update Supplier' : 'New Supplier', array(
    'name' => 'submit', 'type' => 'submit', 'style' => 'margin:10px;'
  ));
  UI::button('btnCancel', 'Cancel', array(
    'name' => 'cancel', 'type' => 'submit', 'class' => 'ui-helper-hidden', 'style' => 'margin:10px;'
  ));
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::_div();
  if (!Input::get('frame')) {
    HTML::div('shortcuts', array('class' => 'width50 center'));
    $shortcuts = new MenuUI(array('noajax' => TRUE));
    $shortcuts->addLink('Supplier Payment', 'Make supplier payment!', '/purchases/supplier_payment.php?supplier_id=', 'id');
    $shortcuts->addLink('Supplier Invoice', 'Make supplier invoice!', '/purchases/supplier_invoice.php?New=1&supplier_id=', 'id');
    $shortcuts->render();
    /** @noinspection PhpUndefinedMethodInspection */
    HTML::_div();
    UI::emailDialogue(CT_SUPPLIER);
  }
  HTML::_div();

  Page::end(TRUE);
