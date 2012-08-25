<?php
  use ADV\App\Debtor\Debtor;
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
    protected $customer;
    protected function before()
    {
      ADVAccounting::i()->set_selected('Debtors');
      if (AJAX_REFERRER) {
        if (isset($_GET['term'])) {
          $data = Debtor::search($_GET['term']);
          $this->JS->renderJSON($data);
        }
      }
      if (isset($_POST['name'])) {
        $data['company'] = $this->customer = new Debtor();
        $data['company']->save($_POST);
      } elseif ($this->Input->request('id', Input::NUMERIC) > 0) {
        $data['company']     = $this->customer = new Debtor($this->Input->request('id', Input::NUMERIC));
        $data['contact_log'] = Contact_Log::read($this->customer->id, CT_CUSTOMER);
        $this->Session->setGlobal('debtor_id', $this->customer->id);
      } else {
        $data['company'] = $this->customer = new Debtor();
      }
      if (AJAX_REFERRER) {
        $data['status'] = $this->customer->getStatus();
        $this->JS->renderJSON($data);
      }
      $this->JS->footerFile("/js/company.js");
      $this->JS->onload("Company.setValues(" . json_encode($data) . ");");
    }
    protected function index()
    {
      Page::start(_($help_context = "Customers"), SA_CUSTOMER, $this->Input->request('frame'));
      $currentBranch = $this->customer->branches[$this->customer->defaultBranch];
      if (isset($_POST['delete'])) {
        $this->delete();
      }
      /** @noinspection PhpUndefinedMethodInspection */
      $this->JS->autocomplete('customer', 'Company.fetch');
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
            $this->customer->branches
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
      $form->text('accounts[contact_name]', $this->customer->accounts->contact_name)->label('Accounts Contact:');
      $form->text('accounts[phone]', $this->customer->accounts->phone)->label('Phone Number:');
      $form->text('accounts[phone2]', $this->customer->accounts->phone2)->label('Alt Phone Number:');
      $form->text('accounts[fax]', $this->customer->accounts->fax)->label('Fax Number:');
      $form->text('accounts[email]', $this->customer->accounts->email)->label('E-mail:');
      $form->textarea('accounts[br_address]', $this->customer->accounts->br_address, ['cols'=> 37, 'rows'=> 4])->label('Street:');
      $accounts_postcode = new Contact_Postcode([
                                                'city'     => ['accounts[city]', $this->customer->accounts->city], //
                                                'state'    => ['accounts[state]', $this->customer->accounts->state], //
                                                'postcode' => ['accounts[postcode]', $this->customer->accounts->postcode] //
                                                ]);
      $view->set('accounts_postcode', $accounts_postcode);
      $form->hidden('accounts_id', $this->customer->accounts->accounts_id);
      $form->percent("Discount Percent:", 'discount', $this->customer->discount, ["disabled"=> User::i()->hasAccess(SA_CUSTOMER_CREDIT)]);
      $form->percent("Prompt Payment Discount:", 'payment_discount', $this->customer->payment_discount, ["disabled"=> User::i()->hasAccess(SA_CUSTOMER_CREDIT)]);
      $form->number("Credit Limit:", 'credit_limit', $this->customer->credit_limit, null, ['$'], ["disabled"=> User::i()->hasAccess(SA_CUSTOMER_CREDIT)]);
      $form->text('tax_id', $this->customer->tax_id)->label("GSTNo:");
      $form->label('Sales Type:', 'sales_type', Sales_Type::select('sales_type', $this->customer->sales_type));
      $form->label('Inactive:', 'inactive', UI::select('inactive', ['No', 'Yes'], ['name' => 'inactive'], $this->customer->inactive, true));
      if (!$this->customer->id) {
        $form->label('Currency Code:', 'curr_code', GL_Currency::select('curr_code', $this->customer->curr_code));
      } else {
        $form->label('Currency Code:', 'curr_code', $this->customer->curr_code);
        $form->hidden('curr_code', $this->customer->curr_code);
      }
      $form->label('Payment Terms:', 'payment_terms', GL_UI::payment_terms('payment_terms', $this->customer->payment_terms));
      $form->label('Credit Status:', 'credit_status', Sales_CreditStatus::select('credit_status', $this->customer->credit_status));
      $form->textarea('messageLog', Contact_Log::read($this->customer->id, CT_CUSTOMER), ['style'=> 'height:100px;width:95%;margin:0 auto;', 'cols'=> 100]);
      /** @noinspection PhpUndefinedMethodInspection */
      $contacts = new View('contacts/contact');
      $view->set('contacts', $contacts->render(true));
      $form->hidden('branch_id', $currentBranch->branch_id);
      $form->label('Salesman:', 'branch[salesman]', Sales_UI::persons('branch[salesman]', $currentBranch->salesman));
      $form->label('Sales Area:', 'branch[area]', Sales_UI::areas('branch[area]', $currentBranch->area));
      $form->label('Sales Group:', 'branch[group_no]', Sales_UI::groups('branch[group_no]', $currentBranch->group_no));
      $form->label('Dispatch Location:', 'branch[default_location]', Inv_Location::select('branch[default_location]', $currentBranch->default_location));
      $form->label('Default Shipper:', 'branch[default_ship_via]', Sales_UI::shippers('branch[default_ship_via]', $currentBranch->default_ship_via));
      $form->label('Tax Group:', 'branch[tax_group_id]', Tax_Groups::select('branch[tax_group_id]', $currentBranch->tax_group_id));
      $form->label(
        'Disabled:',
        'branch[disable_trans]',
        UI::select('branch.disable_trans', ['Yes', 'No'], ['name' => 'branch[disable_trans]'], $currentBranch->disable_trans, true)
      );
      $form->text('webid', $this->customer->webid, ['disbaled'=> true])->label("Websale ID");
      $form->label('Sales Account:', 'branch[sales_account]', GL_UI::all('branch[sales_account]', $currentBranch->sales_account, true, false, true));
      $form->label('Receivables Account:', 'branch[receivables_account]', GL_UI::all('branch[receivables_account]', $currentBranch->receivables_account, true, false, false));
      $form->label(
        'Discount Account:',
        'branch[sales_discount_account]',
        GL_UI::all('branch[sales_discount_account]', $currentBranch->sales_discount_account, false, false, true)
      );
      $form->label(
        'Prompt Payment Account:',
        'branch[payment_discount_account]',
        GL_UI::all('branch[payment_discount_account]', $currentBranch->payment_discount_account, false, false, true)
      );
      $form->textarea('branch[notes]', $currentBranch->notes, ['cols'=> 100, 'rows'=> 10])->label('General Notes:');
      $view['debtor_id'] = $this->customer->id;
      $form->hidden('frame', $this->Input->request('frame'));
      $view->set('form', $form);
      $form->hidden('type', CT_CUSTOMER);
      $view['date'] = date('Y-m-d H:i:s');
      $form->text('contact_name', $this->customer->accounts->contact_name)->label('Contact:');
      $form->textarea('message', ['cols'=> 100, 'rows'=> 10])->label('Entry:');
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
      $view->render();
      Page::end(true);
    }
    protected function delete()
    {
      $this->customer->delete();
      $status = $this->customer->getStatus();
      Event::notice($status['message']);
    }
    protected function after()
    {
      // TODO: Implement after() method.
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation()
    {
      Validation::check(Validation::SALES_TYPES, _("There are no sales types defined. Please define at least one sales type before adding a customer."));
      Validation::check(Validation::SALESPERSONS, _("There are no sales people defined in the system. At least one sales person is required before proceeding."));
      Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
      Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
      Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
    }
  }

  new Debtors();
