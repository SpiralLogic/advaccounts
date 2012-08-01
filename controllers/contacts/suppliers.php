<?php
  use ADV\App\Creditor\Creditor;
  use ADV\Core\Row;
  use ADV\Core\Table;
  use ADV\App\UI\UI;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Creditors extends \ADV\App\Controller\Base
  {

    /** @var Creditor */
    protected $creditor;
    protected function before() {
      /** @noinspection PhpUndefinedMethodInspection */
      ADVAccounting::i()->set_selected('Creditors');
      if (AJAX_REFERRER) {
        $this->search();
      }
      if (isset($_POST['name'])) {
        $data['company'] = $this->creditor = new Creditor();
        unset($_POST['supp_ref']);
        $data['company']->save($_POST);
      } elseif (Input::request('id', Input::NUMERIC) > 0) {
        $data['company']     = $this->creditor = new Creditor(Input::request('id', Input::NUMERIC));
        $data['contact_log'] = Contact_Log::read($this->creditor->id, CT_SUPPLIER);
        Session::setGlobal('creditor_id', $this->creditor->id);
      } else {
        $data['company'] = $this->creditor = new Creditor();
      }
      if (AJAX_REFERRER) {
        /** @noinspection PhpUndefinedMethodInspection */
        $data['status'] = $this->creditor->getStatus();
        /** @noinspection PhpUndefinedMethodInspection */
        JS::renderJSON($data);
      }
      JS::footerFile("/js/company.js");
      JS::onload("Company.setValues(" . json_encode($data) . ");");
    }
    protected function search() {
      if (isset($_GET['term'])) {
        $data = Creditor::search($_GET['term']);
        JS::renderJSON($data);
      }
    }
    protected function index() {
      Page::start(_($help_context = "Suppliers"), SA_SUPPLIER, Input::request('frame'));
      $currentContact = $this->creditor->contacts[$this->creditor->defaultContact];
      if (isset($_POST['delete'])) {
        $this->delete();
      }
      JS::autocomplete('supplier', 'Company.fetch');
      $form          = new Form();
      $menu          = new MenuUI();
      $view          = new View('contacts/supplier');
      $view['frame'] = $this->Input->_get('frame') || $this->Input->_get('id');
      $view->set('menu', $menu);
      /** @noinspection PhpUndefinedMethodInspection */
      $form->text('Supplier Name:', 'name', $this->creditor->name, ['class' => 'width60']);
      $form->text('Supplier ID:', 'id', $this->creditor->id, ['class' => 'small', 'maxlength' => 7]);
      $view->set('form', $form);
      $view->set('creditor_id', $this->creditor->id);
      if (!$this->Input->_get('frame')) {
        $shortcuts = new MenuUI(array('noajax' => true));
        $shortcuts->addLink('Create Quote', 'Create Quote for this customer!', '/sales/sales_order_entry.php?type=' . ST_SALESQUOTE . '&add=' . ST_SALESQUOTE . '&debtor_id=', 'id');
        $shortcuts->addLink('Create Order', 'Create Order for this customer!', '/sales/sales_order_entry.php?type=30&add=' . ST_SALESORDER . '&debtor_id=', 'id');
        $shortcuts->addLink('Print Statement', 'Print Statement for this Customer!', '/reporting/prn_redirect.php?REP_ID=108&PARAM_2=0&PARAM_4=0&PARAM_5=0&PARAM_6=0&PARAM_0=', 'id', true);
        $shortcuts->addJSLink('Email Statement', 'Email Statement for this Customer!', 'emailTab', "Adv.o.tabs[1].bind('tabsselect',function(e,o) {if (o.index!=3)return; return false;});");
        $shortcuts->addLink('Customer Payment', 'Make customer payment!', '/sales/customer_payments.php?debtor_id=', 'id');
        $view->set('shortcuts', $shortcuts);
        UI::emailDialogue(CT_SUPPLIER);
      }
      $view->render();
      Table::startOuter('tablestyle2');
      Table::section(1);
      Table::sectionTitle(_("Shipping Details"), 2);
      /** @noinspection PhpUndefinedMethodInspection */
      Forms::textRow(_("Contact:"), 'contact', $this->creditor->contact, 35, 40);
      //Forms::hidden('br_contact_name', $this->creditor->contact_name);
      Forms::textRow(_("Phone Number:"), 'phone', $this->creditor->phone, 35, 30);
      Forms::textRow(_("Fax Number:"), 'fax', $this->creditor->fax, 35, 30);
      Forms::emailRow(_("Email:"), 'email', $this->creditor->email, 35, 55);
      Forms::textareaRow(_("Street:"), 'address', $this->creditor->address, 35, 2);
      $postcode = new Contact_Postcode(array(
        'city'     => array('supp_city', $this->creditor->city),
        'state'    => array('supp_state', $this->creditor->state),
        'postcode' => array('supp_postcode', $this->creditor->postcode)
      ));
      $view->set('branch_postcode', $postcode);
      Table::section(2);
      Table::sectionTitle(_("Accounts Details"), 2);
      /** @noinspection PhpUndefinedMethodInspection */
      HTML::tr(true)->td(array(
        'class' => "center", 'colspan' => 2
      ));
      UI::button('useShipAddress', _("Use shipping details"), array('name' => 'useShipAddress'));
      HTML::_td()->tr;
      Forms::textRow(_("Phone Number:"), 'supp_phone', $this->creditor->phone2, 35, 30);
      Forms::textareaRow(_("Address:"), 'supp_address', $this->creditor->address, 35, 2);
      $postcode->render();
      Table::endOuter(1);
      Table::startOuter('tablestyle2');
      Table::section(1);
      Table::sectionTitle(_("Accounts Details:"), 2);
      Forms::percentRow(_("Prompt Payment Discount Percent:"), 'discount', $this->creditor->discount, (User::i()
        ->hasAccess(SA_SUPPLIERCREDIT)) ? "" : " disabled");
      Forms::AmountRow(_("Credit Limit:"), 'credit_limit', $this->creditor->credit_limit, null, null, 0, (User::i()
        ->hasAccess(SA_SUPPLIERCREDIT)) ? "" : " disabled");
      Forms::textRow(_("GST No:"), 'tax_id', $this->creditor->tax_id, 'big', 40);
      Tax_Groups::row(_("Tax Group:"), 'tax_group_id', $this->creditor->tax_group_id);
      Forms::textareaRow(_("General Notes:"), 'notes', $this->creditor->notes, 'big', 4);
      Forms::recordStatusListRow(_("Supplier status:"), 'inactive');
      if (!$this->creditor->id) {
        GL_Currency::row(_("Supplier's Currency:"), 'curr_code', $this->creditor->curr_code);
      } else {
        Row::label(_("Supplier's Currency:"), $this->creditor->curr_code);
        Forms::hidden('curr_code', $this->creditor->curr_code);
      }
      GL_UI::payment_terms_row(_("Pament Terms:"), 'payment_terms', $this->creditor->payment_terms);
      Table::sectionTitle(_("GL Accounts"));
      GL_UI::all_row(_("Accounts Receivable Account:"), 'payable_account', $this->creditor->payable_account);
      GL_UI::all_row(_("Prompt Payment Discount Account:"), 'payment_discount_account', $this->creditor->payment_discount_account);
      Table::section(2);
      Table::sectionTitle(_("Contact log:"), 1);
      Row::start();
      HTML::td(array(
        'class' => 'ui-widget-content center'
      ));
      UI::button('addLog', "Add log entry")->td->tr->tr(true)->td(null)->textarea('messageLog', array('cols' => 50, 'rows' => 20));
      Contact_Log::read($this->creditor->id, CT_SUPPLIER);
      /** @noinspection PhpUndefinedMethodInspection */
      HTML::textarea()->td->tr;
      Table::endOuter(1);
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
      echo "<div id='invoiceFrame' data-src='" . BASE_URL . "purchases/inquiry/supplier_allocation_inquiry.php?creditor_id=" . $this->creditor->id . "' ></div> ";
      Forms::hidden('frame', Input::request('frame'));
      HTML::div();
      Forms::end();
      HTML::div('contactLog', array(
        'title' => 'New contact log entry', 'class' => 'ui-widget-overlay', 'style' => 'display:none;'
      ));
      Forms::hidden('type', CT_SUPPLIER);
      Table::start();
      Row::label('Date:', date('Y-m-d H:i:s'));
      Forms::textRow('Contact:', 'contact_name', $this->creditor->contact_name, 35, 40);
      Forms::textareaRow('Entry:', 'message', '', 100, 10);
      Table::end();
      HTML::_div()->div(array('class' => 'center width50'));
      UI::button('btnConfirm', ($this->creditor->id) ? 'Update Supplier' : 'New Supplier', array(
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
    }
    protected function runValidation() {
      Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
      Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
      Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
    }
    private function delete() {
    }
  }

  new Creditors();
