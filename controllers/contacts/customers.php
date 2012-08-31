<?php
  use ADV\App\Debtor\Debtor;
  use ADV\Core\HTMLmin;
  use ADV\App\Validation;
  use ADV\App\User;
  use ADV\App\ADVAccounting;
  use ADV\App\Form\Form;
  use ADV\Core\Input\Input;
  use ADV\Core\Row;
  use ADV\App\UI\UI;
  use ADV\Core\Table;

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
    protected $debtor;
    protected $company_data;
    protected function before() {
      ADVAccounting::i()->set_selected('Debtors');
      if (AJAX_REFERRER) {
        if (isset($_GET['term'])) {
          $data = Debtor::search($_GET['term']);
          $this->JS->renderJSON($data);
        }
      }
      if (isset($_POST['name'])) {
        $data['company'] = $this->debtor = new Debtor();
        $data['company']->save($_POST);
      } elseif ($this->Input->request('id', Input::NUMERIC) > 0) {
        $data['company']     = $this->debtor = new Debtor($this->Input->request('id', Input::NUMERIC));
        $data['contact_log'] = Contact_Log::read($this->debtor->id, CT_CUSTOMER);
        $this->Session->setGlobal('debtor_id', $this->debtor->id);
      } else {
        $data['company'] = $this->debtor = new Debtor();
      }
      if (AJAX_REFERRER) {
        $data['status'] = $this->debtor->getStatus();
        $this->JS->renderJSON($data);
      }
      $this->company_data = $data;

      $this->JS->footerFile("/js/company.js");
    }
    protected function index() {
      Page::start(_($help_context = "Customers"), SA_CUSTOMER, $this->Input->request('frame'));
      if (isset($_POST['delete'])) {
        $this->delete();
      }
      echo $this->generateForm();
      $this->JS->onload("Company.setValues(" . json_encode($this->company_data) . ");")->setFocus($this->debtor->id ? 'name' : 'customer');

      Page::end(true);
    }
    /**
     * @return string
     */
    protected function generateForm() {

      $cache = null;Cache::_get('customer_form');
      if ($cache) {
        $this->JS->setState($cache[1]);

        return $form = igbinary_unserialize($cache[0]);
      }
      $this->JS->autocomplete('customer', 'Company.fetch');
      $currentBranch = $this->debtor->branches[$this->debtor->defaultBranch];
      $form          = new Form();
      $menu          = new MenuUI();
      $view          = new View('contacts/customers');
      $view['frame'] = $this->Input->get('frame') || $this->Input->get('id');
      $view->set('menu', $menu);
      $view->set(
        'branchlist',
        UI::select(
          'branchList',
          array_map(
            function ($v) {
              return $v->br_name;
            },
            $this->debtor->branches
          ),
          ['class'=> 'med', 'name' => 'branchList'],
          null,
          true
        )
      );
      $form->text('branch[contact_name]', $currentBranch->contact_name)->label('Contact:');
      $form->text('branch[phone]', $currentBranch->phone)->label('Phone Number:');
      $form->text('branch[phone2]', $currentBranch->phone2)->label("Alt Phone Number:");
      $form->text('branch[fax]', $currentBranch->fax)->label("Fax Number:");
      $form->text('branch[email]', $currentBranch->email)->label("Email:");
      $form->textarea('branch[br_address]', $currentBranch->br_address, ['cols'=> 37, 'rows'=> 4])->label('Street:');
      $branch_postcode = new Contact_Postcode([
                                              'city'     => ['branch[city]', $currentBranch->city], //
                                              'state'    => ['branch[state]', $currentBranch->state], //
                                              'postcode' => ['branch[postcode]', $currentBranch->postcode]
                                              ]);
      $view->set('branch_postcode', $branch_postcode);
      $form->text('accounts[contact_name]', $this->debtor->accounts->contact_name)->label('Accounts Contact:');
      $form->text('accounts[phone]', $this->debtor->accounts->phone)->label('Phone Number:');
      $form->text('accounts[phone2]', $this->debtor->accounts->phone2)->label('Alt Phone Number:');
      $form->text('accounts[fax]', $this->debtor->accounts->fax)->label('Fax Number:');
      $form->text('accounts[email]', $this->debtor->accounts->email)->label('E-mail:');
      $form->textarea('accounts[br_address]', $this->debtor->accounts->br_address, ['cols'=> 37, 'rows'=> 4])->label('Street:');
      $accounts_postcode = new Contact_Postcode([
                                                'city'     => ['accounts[city]', $this->debtor->accounts->city], //
                                                'state'    => ['accounts[state]', $this->debtor->accounts->state], //
                                                'postcode' => ['accounts[postcode]', $this->debtor->accounts->postcode] //
                                                ]);
      $view->set('accounts_postcode', $accounts_postcode);
      $form->hidden('accounts_id', $this->debtor->accounts->accounts_id);
      $form->group('accounts');

      $form->percent('discount', $this->debtor->discount, ["disabled"=> !User::i()->hasAccess(SA_CUSTOMER_CREDIT)])->label("Discount Percent:");
      $form->percent('payment_discount', $this->debtor->payment_discount, ["disabled"=> !User::i()->hasAccess(SA_CUSTOMER_CREDIT)])->label("Prompt Payment Discount:");
      $form->amount('credit_limit', $this->debtor->credit_limit, ["disabled"=> !User::i()->hasAccess(SA_CUSTOMER_CREDIT)])->label("Credit Limit:");
      $form->text('tax_id', $this->debtor->tax_id)->label("GSTNo:");
      $form->custom(Sales_Type::select('sales_type', $this->debtor->sales_type))->label('Sales Type:');
      $form->custom(UI::select('inactive', ['No', 'Yes'], ['name' => 'inactive'], $this->debtor->inactive, true))->label('Inactive:');
      if (!$this->debtor->id) {
        $form->custom(GL_Currency::select('curr_code', $this->debtor->curr_code))->label('Currency Code:');
      } else {
        $form->label('Currency Code:', 'curr_code', $this->debtor->curr_code);
        $form->hidden('curr_code', $this->debtor->curr_code);
      }
      $form->custom(GL_UI::payment_terms('payment_terms', $this->debtor->payment_terms))->label('Payment Terms:');
      $form->custom(Sales_CreditStatus::select('credit_status', $this->debtor->credit_status))->label('Credit Status:');
      $form->group();



      $form->textarea('messageLog', Contact_Log::read($this->debtor->id, CT_CUSTOMER), ['style'=> 'height:100px;width:95%;margin:0 auto;', 'cols'=> 100]);
      /** @noinspection PhpUndefinedMethodInspection */
      $contacts = new View('contacts/contact');
      $view->set('contacts', $contacts->render(true));
      $form->hidden('branch_id', $currentBranch->branch_id);
      $form->custom(Sales_UI::persons('branch[salesman]', $currentBranch->salesman))->label('Salesman:');
      $form->custom(Sales_UI::areas('branch[area]', $currentBranch->area))->label('Sales Area:');
      $form->custom(Sales_UI::groups('branch[group_no]', $currentBranch->group_no))->label('Sales Group:');
      $form->custom(Inv_Location::select('branch[default_location]', $currentBranch->default_location))->label('Dispatch Location:');
      $form->custom(Sales_UI::shippers('branch[default_ship_via]', $currentBranch->default_ship_via))->label('Default Shipper:');
      $form->custom(Tax_Groups::select('branch[tax_group_id]', $currentBranch->tax_group_id))->label('Tax Group:');
      $form->custom(UI::select('branch-disable_trans', ['Yes', 'No'], ['name' => 'branch[disable_trans]'], $currentBranch->disable_trans, true))->label('Disabled: ');
      $form->text('webid', $this->debtor->webid, ['disabled'=> true])->label("Websale ID");
      $form->custom(GL_UI::all('branch[sales_account]', $currentBranch->sales_account, true, false, true))->label('Sales Account:');
      $form->custom(GL_UI::all('branch[receivables_account]', $currentBranch->receivables_account, true, false, false))->label('Receivables Account:');
      $form->custom(GL_UI::all('branch[sales_discount_account]', $currentBranch->sales_discount_account, false, false, true))->label('Discount Account:');
      $form->custom(GL_UI::all('branch[payment_discount_account]', $currentBranch->payment_discount_account, false, false, true))->label('Prompt Payment Account:');
      $form->textarea('branch[notes]', $currentBranch->notes, ['cols'=> 100, 'rows'=> 10])->label('General Notes:');
      $view['debtor_id'] = $this->debtor->id;
      $form->hidden('frame', $this->Input->request('frame'));
      $view->set('form', $form);
      $form->hidden('type', CT_CUSTOMER);
      $contact_form = new Form();
      $view['date'] = date('Y-m-d H:i:s');
      $contact_form->text('contact_name', $this->debtor->accounts->contact_name)->label('Contact:');
      $contact_form->textarea('message', ['cols'=> 100, 'rows'=> 10])->label('Entry:');
      $view->set('contact_form', $contact_form);
      if (!$this->Input->get('frame')) {
        $shortcuts = new MenuUI(array('noajax' => true));
        $shortcuts->addLink(
          'Create Quote',
          'Create Quote for this customer!',
          '/sales/sales_order_entry.php?type=' . ST_SALESQUOTE . '&add=' . ST_SALESQUOTE . '&debtor_id=',
          'id'
        );
        $shortcuts->addLink('Create Order', 'Create Order for this customer!', '/sales/sales_order_entry.php?type=30&add=' . ST_SALESORDER . '&debtor_id=', 'id');
        $shortcuts->addLink(
          'Print Statement',
          'Print Statement for this Customer!',
          '/reporting/prn_redirect.php?REP_ID=108&PARAM_2=0&PARAM_4=0&PARAM_5=0&PARAM_6=0&PARAM_0=',
          'id',
          true
        );
        $shortcuts->addJSLink(
          'Email Statement',
          'Email Statement for this Customer!',
          'emailTab',
          "Adv.o.tabs[1].bind('tabsselect',function(e,o) {if (o.index!=3)return; return false;});"
        );
        $shortcuts->addLink('Customer Payment', 'Make customer payment!', '/sales/customer_payments.php?debtor_id=', 'id');
        $view->set('shortcuts', $shortcuts);
        UI::emailDialogue(CT_CUSTOMER);
      }
      $form = HTMLmin::minify($view->render(true));
      Cache::_set('customer_form', [igbinary_serialize($form), $this->JS->getState()]);

      return $form;
    }
    protected function delete() {
      $this->debtor->delete();
      $status = $this->debtor->getStatus();
      Event::notice($status['message']);
    }
    protected function after() {
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
