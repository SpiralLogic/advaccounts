<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Debtors extends \ADV\App\Controller\Base
  {

    /** @var Debtor */
    protected $customer;
    protected function before() {
      ADVAccounting::i()->set_selected('Debtors');
      if (AJAX_REFERRER) {
        if (isset($_GET['term'])) {
          $data = Debtor::search($_GET['term']);
          $this->JS->_renderJSON($data);
        }
      }
      if (isset($_POST['name'])) {
        $data['company'] = $this->customer = new Debtor();
        $data['company']->save($_POST);
      } elseif ($this->Input->_request('id', Input::NUMERIC) > 0) {
        $data['company']     = $this->customer = new Debtor($this->Input->_request('id', Input::NUMERIC));
        $data['contact_log'] = Contact_Log::read($this->customer->id, CT_CUSTOMER);
        $this->Session->_setGlobal('debtor_id', $this->customer->id);
      } else {
        $data['company'] = $this->customer = new Debtor();
      }
      if (AJAX_REFERRER) {
        $data['status'] = $this->customer->getStatus();
        $this->JS->_renderJSON($data);
      }
      $this->JS->_footerFile("/js/company.js");
      $this->JS->_onload("Company.setValues(" . json_encode($data) . ");");
    }
    protected function index() {
      Page::start(_($help_context = "Customers"), SA_CUSTOMER, $this->Input->_request('frame'));
      $currentContact = $this->customer->contacts[$this->customer->defaultContact];
      $currentBranch  = $this->customer->branches[$this->customer->defaultBranch];
      if (isset($_POST['delete'])) {
        $this->customer->delete();
        $status = $this->customer->getStatus();
        Event::notice($status['message']);
      }
      /** @noinspection PhpUndefinedMethodInspection */
      JS::autocomplete('customer', 'Company.fetch');
      $view          = new View('contacts/customers');
      $view['frame'] = $this->Input->_get('frame') || $this->Input->_get('id');
      $view->render();
      Forms::start();
      $menu = new MenuUI();
      $menu->startTab('Details', 'Customer Details', '#', 'text-align:center');
      HTML::div('companyIDs');
      HTML::table(array("class" => "marginauto width80 bold"))->tr(true)->td(true);
      HTML::label(array(
        'for' => 'name', 'content' => 'Customer name:'
      ), false);
      HTML::input('name', array(
        'value' => $this->customer->name, 'name' => 'name', 'class'=> 'med'
      ));
      HTML::td()->td(array(
        'content' => _("Customer ID: "),
      ), false)->td(true);
      HTML::input('id', array(
        'value' => $this->customer->id, 'name' => 'id', 'class'=> 'small', 'maxlength' => '7'
      ));
      HTML::td()->tr->table->div;
      Table::startOuter('tablestyle2');
      Table::section(1);
      Table::sectionTitle(_("Shipping Details"), 2);
      /** @noinspection PhpUndefinedMethodInspection */
      HTML::tr(true)->td('branchSelect', array(
        'colspan' => 2, 'class' => "center"
      ));
      UI::select('branchList', array_map(function($v) {
        return $v->br_name;
      }, $this->customer->branches), array('class'=> 'med', 'name' => 'branchList'));
      UI::button('addBranch', 'Add new address', array(
        'class' => 'invis', 'name' => 'addBranch'
      ));
      HTML::td()->tr;
      Forms::textRow(_("Contact:"), 'branch[contact_name]', $currentBranch->contact_name, null, 40);
      //Forms::hidden('br_contact_name', $this->customer->contact_name);
      Forms::textRow(_("Phone Number:"), 'branch[phone]', $currentBranch->phone, 35, 30);
      Forms::textRow(_("2nd Phone Number:"), 'branch[phone2]', $currentBranch->phone2, 35, 30);
      Forms::textRow(_("Fax Number:"), 'branch[fax]', $currentBranch->fax, 35, 30);
      Forms::emailRow(_("Email:"), 'branch[email]', $currentBranch->email, 35, 55);
      Forms::textareaRow(_("Street:"), 'branch[br_address]', $currentBranch->br_address, 35, 2);
      $branch_postcode = new Contact_Postcode(array(
        'city'     => array('branch[city]', $currentBranch->city), 'state'    => array('branch[state]', $currentBranch->state), 'postcode' => array('branch[postcode]', $currentBranch->postcode)
      ));
      $branch_postcode->render();
      Table::section(2);
      Table::sectionTitle(_("Accounts Details"), 2);
      /** @noinspection PhpUndefinedMethodInspection */
      HTML::tr(true)->td(array(
        'class' => "center", 'colspan' => 2
      ));
      UI::button('useShipAddress', _("Use shipping details"), array('name' => 'useShipAddress'));
      HTML::td(false)->_tr();
      Forms::textRow(_("Accounts Contact:"), 'accounts[contact_name]', $this->customer->accounts->contact_name, 35, 40);
      Forms::textRow(_("Phone Number:"), 'accounts[phone]', $this->customer->accounts->phone, 35, 30);
      Forms::textRow(_("Secondary Phone Number:"), 'accounts[phone2]', $this->customer->accounts->phone2, 35, 30);
      Forms::textRow(_("Fax Number:"), 'accounts[fax]', $this->customer->accounts->fax, 35, 30);
      Forms::emailRow(_("E-mail:"), 'accounts[email]', $this->customer->accounts->email, 35, 55);
      Forms::textareaRow(_("Street:"), 'accounts[br_address]', $this->customer->accounts->br_address, 35, 2);
      $accounts_postcode = new Contact_Postcode(array(
        'city'     => array('accounts[city]', $this->customer->accounts->city), 'state'    => array('accounts[state]', $this->customer->accounts->state), 'postcode' => array('accounts[postcode]', $this->customer->accounts->postcode)
      ));
      $accounts_postcode->render();
      Table::endOuter(1);
      $menu->endTab()->startTab('Accounts', 'Accounts');
      Forms::hidden('accounts_id', $this->customer->accounts->accounts_id);
      Table::startOuter('tablestyle2');
      Table::section(1);
      Table::sectionTitle(_("Accounts Details:"), 2);
      Forms::percentRow(_("Discount Percent:"), 'discount', $this->customer->discount, (User::i()->hasAccess(SA_CUSTOMER_CREDIT)) ? "" : " disabled");
      Forms::percentRow(_("Prompt Payment Discount Percent:"), 'payment_discount', $this->customer->payment_discount, (User::i()->hasAccess(SA_CUSTOMER_CREDIT)) ? "" : " disabled");
      Forms::AmountRow(_("Credit Limit:"), 'credit_limit', $this->customer->credit_limit, null, null, 0, (User::i()->hasAccess(SA_CUSTOMER_CREDIT)) ? "" : " disabled");
      Forms::textRow(_("GSTNo:"), 'tax_id', $this->customer->tax_id, null, 40);
      Sales_Type::row(_("Sales Type/Price List:"), 'sales_type', $this->customer->sales_type);
      Forms::recordStatusListRow(_("Customer status:"), 'inactive');
      if (!$this->customer->id) {
        GL_Currency::row(_("Customer's Currency:"), 'curr_code', $this->customer->curr_code);
      } else {
        Row::label(_("Customer's Currency:"), $this->customer->curr_code);
        Forms::hidden('curr_code', $this->customer->curr_code);
      }
      GL_UI::payment_terms_row(_("Payment Terms:"), 'payment_terms', $this->customer->payment_terms);
      Sales_CreditStatus::row(_("Credit Status:"), 'credit_status', $this->customer->credit_status);
      Table::section(2);
      Table::sectionTitle(_("Contact log:"), 1);
      Row::start();
      HTML::td(array(
        'class' => 'ui-widget-content center'
      ));
      UI::button('addLog', "Add log entry")->td->tr->tr(true)->td(null)->textarea('messageLog', array('cols' => 50, 'rows' => 20));
      Contact_Log::read($this->customer->id, CT_CUSTOMER);
      /** @noinspection PhpUndefinedMethodInspection */
      HTML::textarea()->td->tr;
      Table::endOuter(1);
      $menu->endTab()->startTab('Customer Contacts', 'Customer Contacts');
      $contacts = new View('contacts/contact');
      $contacts->render();
      $menu->endTab()->startTab('Extra Shipping Info', 'Extra Shipping Info');
      Table::startOuter('tablestyle2');
      Forms::hidden('branch_id', $currentBranch->branch_id);
      Table::section(1);
      Table::sectionTitle(_("Sales"));
      Sales_UI::persons_row(_("Sales Person:"), 'branch[salesman]', $currentBranch->salesman);
      Sales_UI::areas_row(_("Sales Area:"), 'branch[area]', $currentBranch->area);
      Sales_UI::groups_row(_("Sales Group:"), 'branch[group_no]', $currentBranch->group_no);
      Inv_Location::row(_("Default Inventory Location:"), 'branch[default_location]', $currentBranch->default_location);
      Sales_UI::shippers_row(_("Default Shipping Company:"), 'branch[default_ship_via]', $currentBranch->default_ship_via);
      Tax_Groups::row(_("Tax Group:"), 'branch[tax_group_id]', $currentBranch->tax_group_id);
      Forms::yesnoListRow(_("Disable this Branch:"), 'branch[disable_trans]', $currentBranch->disable_trans);
      HTML::tr(true)->td(array(
        'content' => _("Website ID: "), "class" => "label"
      ), false)->td(true);
      HTML::input('webid', array(
        'value' => $this->customer->webid, 'disabled' => true, 'name' => 'webid', 'maxlength' => '7'
      ));
      HTML::td()->tr;
      Table::section(2);
      Table::sectionTitle(_("GL Accounts"));
      GL_UI::all_row(_("Sales Account:"), 'branch[sales_account]', $currentBranch->sales_account, false, false, true);
      GL_UI::all_row(_("Sales Discount Account:"), 'branch[sales_discount_account]', $currentBranch->sales_discount_account);
      GL_UI::all_row(_("Accounts Receivable Account:"), 'branch[receivables_account]', $currentBranch->receivables_account);
      GL_UI::all_row(_("Prompt Payment Discount Account:"), 'branch[payment_discount_account]', $currentBranch->payment_discount_account);
      Table::sectionTitle(_("Notes"));
      Forms::textareaRow(_("General Notes:"), 'branch[notes]', $currentBranch->notes, 35, 4);
      Table::endOuter(1);
      $menu->endTab();
      $menu->startTab('Invoices', 'Invoices');
      echo "<div id='invoiceFrame' data-src='" . BASE_URL . "sales/inquiry/customer_allocation_inquiry.php?debtor_id=" . $this->customer->id . "' ></div> ";
      $menu->endTab()->render();
      Forms::hidden('frame', $this->Input->_request('frame'));
      HTML::div();
      Forms::end();
      HTML::div('contactLog', array(
        'title' => 'New contact log entry', 'class' => 'ui-widget-overlay', 'style' => 'display:none;'
      ));
      Forms::hidden('type', CT_CUSTOMER);
      Table::start();
      Row::label('Date:', date('Y-m-d H:i:s'));
      Forms::textRow('Contact:', 'contact_name', $this->customer->accounts->contact_name, 35, 40);
      Forms::textareaRow('Entry:', 'message', '', 100, 10);
      Table::end();
      HTML::_div()->div(array('class' => 'center width50'));
      UI::button('btnConfirm', ($this->customer->id) ? 'Update Customer' : 'New Customer', array(
        'name'  => 'submit', 'type'  => 'submit', 'class' => 'ui-helper-hidden', 'style' => 'margin:10px;'
      ));
      UI::button('btnCancel', 'Cancel', array(
        'name' => 'cancel', 'type' => 'submit', 'style' => 'margin:10px;'
      ));
      /** @noinspection PhpUndefinedMethodInspection */
      HTML::_div();
      if (!$this->Input->_get('frame')) {
        HTML::div('shortcuts', array('class' => 'width50 center'));
        $shortcuts = new MenuUI(array('noajax' => true));
        $shortcuts->addLink('Create Quote', 'Create Quote for this customer!', '/sales/sales_order_entry.php?type=' . ST_SALESQUOTE . '&add=' . ST_SALESQUOTE . '&debtor_id=', 'id');
        $shortcuts->addLink('Create Order', 'Create Order for this customer!', '/sales/sales_order_entry.php?type=30&add=' . ST_SALESORDER . '&debtor_id=', 'id');
        $shortcuts->addLink('Print Statement', 'Print Statement for this Customer!', '/reporting/prn_redirect.php?REP_ID=108&PARAM_2=0&PARAM_4=0&PARAM_5=0&PARAM_6=0&PARAM_0=', 'id', true);
        $shortcuts->addJSLink('Email Statement', 'Email Statement for this Customer!', 'emailTab', "Adv.o.tabs.tabs1.bind('tabsselect',function(e,o) {if (o.index!=3)return; return false;});");
        $shortcuts->addLink('Customer Payment', 'Make customer payment!', '/sales/customer_payments.php?debtor_id=', 'id');
        $shortcuts->render();
        /** @noinspection PhpUndefinedMethodInspection */
        HTML::_div();
        UI::emailDialogue(CT_CUSTOMER);
      }
      HTML::_div();
      Page::end(true);
    }
    protected function after() {
      // TODO: Implement after() method.
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
      Validation::check(Validation::SALES_TYPES, _("There are no sales types defined. Please define at least one sales type before adding a customer."));
      Validation::check(Validation::SALESPERSONS, _("There are no sales people defined in the system. At least one sales person is required before proceeding."));
      Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
      Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
      Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
    }
  }

  new Debtors();
