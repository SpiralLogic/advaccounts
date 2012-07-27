<?php
  use ADV\App\Creditor\Creditor;
  use ADV\App\UI\UI;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  ADVAccounting::i()->set_selected('Creditors');
  if (AJAX_REFERRER) {
    if (isset($_GET['term'])) {
      $data = Creditor::search($_GET['term']);
      JS::renderJSON($data);
    }
  }
  if (isset($_POST['name'])) {
    $data['company'] = $supplier = new Creditor();
    unset($_POST['supp_ref']);
    $data['company']->save($_POST);
  } elseif (Input::request('id', Input::NUMERIC) > 0) {
    $data['company']     = $supplier = new Creditor(Input::request('id', Input::NUMERIC));
    $data['contact_log'] = Contact_Log::read($supplier->id, CT_SUPPLIER);
    Session::setGlobal('creditor_id', $supplier->id);
  } else {
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
    HTML::tr(true)->td(true);
    UI::search('supplier', array('label' => 'Search Supplier:', 'size' => 80, 'callback' => 'Company.fetch', 'focus' => true));
    HTML::td()->tr->table->div;
  }
  Forms::start();
  $menu = new MenuUI();
  $menu->startTab('Details', 'Supplier Details', '#', 'text-align:center');
  HTML::div('companyIDs');
  HTML::table(array("class" => "marginauto width80 bold"))->tr(true)->td(array('class'=> 'right'));
  HTML::label(array('for' => 'name', 'content' => 'Supplier name:'), false);
  HTML::input('name', array('value' => $supplier->name, 'name' => 'name', 'class' => 'width60 '));
  HTML::td()->td(array('class'=> 'right'));
  HTML::label(array('content' => _("Supplier ID: "), "class" => 'width50'), false);
  HTML::input('id', array(
                         'value' => $supplier->id, 'name' => 'id', 'class'=> 'small', 'maxlength' => '7'
                    ));
  HTML::td()->tr->table->div;
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Shipping Details"), 2);
  /** @noinspection PhpUndefinedMethodInspection */
  Forms::textRow(_("Contact:"), 'contact', $supplier->contact, 35, 40);
  //Forms::hidden('br_contact_name', $supplier->contact_name);
  Forms::textRow(_("Phone Number:"), 'phone', $supplier->phone, 35, 30);
  Forms::textRow(_("Fax Number:"), 'fax', $supplier->fax, 35, 30);
  Forms::emailRow(_("Email:"), 'email', $supplier->email, 35, 55);
  Forms::textareaRow(_("Street:"), 'address', $supplier->address, 35, 2);
  $branch_postcode = new Contact_Postcode(array(
                                               'city'     => array('city', $supplier->city),
                                               'state'    => array('state', $supplier->state),
                                               'postcode' => array('postcode', $supplier->postcode)
                                          ));
  $branch_postcode->render();
  Table::section(2);
  Table::sectionTitle(_("Accounts Details"), 2);
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::tr(true)->td(array(
                          'class' => "center", 'colspan' => 2
                     ));
  UI::button('useShipAddress', _("Use shipping details"), array('name' => 'useShipAddress'));
  HTML::_td()->tr;
  Forms::textRow(_("Phone Number:"), 'supp_phone', $supplier->phone2, 35, 30);
  Forms::textareaRow(_("Address:"), 'supp_address', $supplier->address, 35, 2);

  $postcode = new Contact_Postcode(array(
                                        'city'     => array('supp_city', $supplier->city),
                                        'state'    => array('supp_state', $supplier->state),
                                        'postcode' => array('supp_postcode', $supplier->postcode)
                                   ));
  $postcode->render();
  Table::endOuter(1);
  $menu->endTab()->startTab('Accounts', 'Accounts');
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Accounts Details:"), 2);
  Forms::percentRow(_("Prompt Payment Discount Percent:"), 'discount', $supplier->discount, (User::i()
    ->hasAccess(SA_SUPPLIERCREDIT)) ? "" : " disabled");
  Forms::AmountRow(_("Credit Limit:"), 'credit_limit', $supplier->credit_limit, null, null, 0, (User::i()
    ->hasAccess(SA_SUPPLIERCREDIT)) ? "" : " disabled");
  Forms::textRow(_("GST No:"), 'tax_id', $supplier->tax_id, 'big', 40);
  Tax_Groups::row(_("Tax Group:"), 'tax_group_id', $supplier->tax_group_id);
  Forms::textareaRow(_("General Notes:"), 'notes', $supplier->notes, 'big', 4);

  Forms::recordStatusListRow(_("Supplier status:"), 'inactive');
  if (!$supplier->id) {
    GL_Currency::row(_("Supplier's Currency:"), 'curr_code', $supplier->curr_code);
  } else {
    Row::label(_("Supplier's Currency:"), $supplier->curr_code);
    Forms::hidden('curr_code', $supplier->curr_code);
  }
  GL_UI::payment_terms_row(_("Pament Terms:"), 'payment_terms', $supplier->payment_terms);
  Table::sectionTitle(_("GL Accounts"));
  GL_UI::all_row(_("Accounts Receivable Account:"), 'payable_account', $supplier->payable_account);
  GL_UI::all_row(_("Prompt Payment Discount Account:"), 'payment_discount_account', $supplier->payment_discount_account);

  Table::section(2);
  Table::sectionTitle(_("Contact log:"), 1);
  Row::start();
  HTML::td(array(
                'class' => 'ui-widget-content center'
           ));
  UI::button('addLog', "Add log entry")->td->tr->tr(true)->td(null)->textarea('messageLog', array('cols' => 50, 'rows' => 20));
  Contact_Log::read($supplier->id, CT_SUPPLIER);
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::textarea()->td->tr;
  Table::endOuter(1);
  $menu->endTab()->startTab('Supplier Contacts', 'Supplier Contacts');
  HTML::div(array('style' => 'text-align:center'))->div('Contacts', array('style' => 'min-height:200px;'));
  HTML::script('contact_tmpl', array('type' => 'text/x-jquery-tmpl'))->table('contact-${id}', array(
                                                                                                   'class' => '',
                                                                                                   'style' => 'display:inline-block'
                                                                                              ))->tr(true)->td(array(
                                                                                                                    'content' => '${name}',
                                                                                                                    'class'   => 'tablehead',
                                                                                                                    'colspan' => 2
                                                                                                               ))->td->tr;
  Forms::textRow("Name:", 'contact[name-${id}]', '${name}', 35, 40);
  Forms::textRow("Phone:", 'contact[phone1-${id}]', '${phone1}', 35, 40);
  Forms::textRow("Phone2:", 'contact[phone2-${id}]', '${phone2}', 35, 40);
  Forms::textRow("Email:", 'contact[email-${id}]', '${email}', 35, 40);
  Forms::textRow("Dept:", 'contact[department-${id}]', '${department}', 35, 40);
  HTML::td()->tr->table->script->div->div;

  $menu->endTab()->startTab('Invoices', 'Invoices');
  echo "<div id='invoiceFrame' data-src='" . BASE_URL . "purchases/inquiry/supplier_allocation_inquiry.php?creditor_id=" . $supplier->id . "' ></div> ";
  $menu->endTab()->render();
  Forms::hidden('frame', Input::request('frame'));
  HTML::div();

  Forms::end();
  HTML::div('contactLog', array(
                               'title' => 'New contact log entry', 'class' => 'ui-widget-overlay', 'style' => 'display:none;'
                          ));
  Forms::hidden('type', CT_SUPPLIER);
  Table::start();
  Row::label('Date:', date('Y-m-d H:i:s'));
  Forms::textRow('Contact:', 'contact_name', $supplier->contact_name, 35, 40);
  Forms::textareaRow('Entry:', 'message', '', 100, 10);
  Table::end();
  HTML::_div()->div(array('class' => 'center width50'));
  UI::button('btnConfirm', ($supplier->id) ? 'Update Supplier' : 'New Supplier', array(
                                                                                      'name'  => 'submit',
                                                                                      'type'  => 'submit',
                                                                                      'style' => 'margin:10px;'
                                                                                 ));
  UI::button('btnCancel', 'Cancel', array(
                                         'name'  => 'cancel',
                                         'type'  => 'submit',
                                         'class' => 'ui-helper-hidden',
                                         'style' => 'margin:10px;'
                                    ));
  /** @noinspection PhpUndefinedMethodInspection */
  HTML::_div();
  if (!Input::get('frame')) {
    HTML::div('shortcuts', array('class' => 'width50 center'));
    $shortcuts = new MenuUI(array('noajax' => true));
    $shortcuts->addLink('Supplier Payment', 'Make supplier payment!', '/purchases/supplier_payment.php?creditor_id=', 'id');
    $shortcuts->addLink('Supplier Invoice', 'Make supplier invoice!', '/purchases/supplier_invoice.php?New=1&creditor_id=', 'id');
    $shortcuts->render();
    /** @noinspection PhpUndefinedMethodInspection */
    HTML::_div();
    UI::emailDialogue(CT_SUPPLIER);
  }
  HTML::_div();

  Page::end(true);
