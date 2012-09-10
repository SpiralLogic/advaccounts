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
      Page::start([_($help_context = "Customers"), 'debtors'], SA_CUSTOMER, $this->Input->request('frame'));
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
      $cache = null; //Cache::_get('customer_form');
      if ($cache) {
        $this->JS->setState($cache[1]);

        return $form = $cache[0];
      }
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
            $this->debtor->branches
          ),
          ['class'=> 'med', 'name' => 'branchList'],
          null,
          true
        )
      );
      $form->group('shipping_details')->text('branch[contact_name]')->label('Contact:');
      $form->text('branch[phone]')->label('Phone Number:');
      $form->text('branch[phone2]')->label("Alt Phone Number:");
      $form->text('branch[fax]')->label("Fax Number:");
      $form->text('branch[email]')->label("Email:");
      $form->textarea('branch[br_address]', ['cols'=> 37, 'rows'=> 4])->label('Street:');
      $branch_postcode = new Contact_Postcode([
                                              'city'     => ['branch[city]'], //
                                              'state'    => ['branch[state]'], //
                                              'postcode' => ['branch[postcode]']
                                              ]);
      $view->set('branch_postcode', $branch_postcode->getForm());
      $form->group('accounts_details')->text('accounts[contact_name]')->label('Accounts Contact:');
      $form->text('accounts[phone]')->label('Phone Number:');
      $form->text('accounts[phone2]')->label('Alt Phone Number:');
      $form->text('accounts[fax]')->label('Fax Number:');
      $form->text('accounts[email]')->label('E-mail:');
      $form->textarea('accounts[br_address]', ['cols'=> 37, 'rows'=> 4])->label('Street:');
      $accounts_postcode = new Contact_Postcode([
                                                'city'     => ['accounts[city]'], //
                                                'state'    => ['accounts[state]'], //
                                                'postcode' => ['accounts[postcode]'] //
                                                ]);
      $view->set('accounts_postcode', $accounts_postcode->getForm());
      $form->hidden('accounts_id');
      $form->group('accounts');
      $has_access = !User::i()->hasAccess(SA_CUSTOMER_CREDIT);
      $form->percent('discount', ["disabled"=> $has_access])->label("Discount Percent:");
      $form->percent('payment_discount', ["disabled"=> $has_access])->label("Prompt Payment Discount:");
      $form->amount('credit_limit', ["disabled"=> $has_access])->label("Credit Limit:");
      $form->text('tax_id')->label("GSTNo:");
      $form->custom(Sales_Type::select('sales_type'))->label('Sales Type:');
      $form->arraySelect('inactive', ['No', 'Yes'])->label('Inactive:');
      if (!$this->debtor->id) {
        $form->custom(GL_Currency::select('curr_code'))->label('Currency Code:');
      } else {
        //$form->label('Currency Code:', 'curr_code', $this->debtor->curr_code);
        $form->hidden('curr_code');
      }
      $form->custom(GL_UI::payment_terms('payment_terms'))->label('Payment Terms:');
      $form->custom(Sales_CreditStatus::select('credit_status'))->label('Credit Status:');
      $form->group();
      $form->textarea('messageLog', ['style'=> 'height:100px;width:95%;margin:0 auto;', 'cols'=> 100])->setContent(Contact_Log::read($this->debtor->id, CT_CUSTOMER));
      /** @noinspection PhpUndefinedMethodInspection */
      $contacts = new View('contacts/contact');
      $view->set('contacts', $contacts->render(true));
      $form->hidden('branch_id');
      $form->custom(Sales_UI::persons('branch[salesman]'))->label('Salesman:');
      $form->custom(Sales_UI::areas('branch[area]'))->label('Sales Area:');
      $form->custom(Sales_UI::groups('branch[group_no]'))->label('Sales Group:');
      $form->custom(Inv_Location::select('branch[default_location]'))->label('Dispatch Location:');
      $form->custom(Sales_UI::shippers('branch[default_ship_via]'))->label('Default Shipper:');
      $form->custom(Tax_Groups::select('branch[tax_group_id]'))->label('Tax Group:');
      $form->arraySelect('branch[disable_trans]', ['Yes', 'No'])->label('Disabled: ');
      $form->text('webid', ['disabled'=> true])->label("Websale ID");
      $form->custom(GL_UI::all('branch[sales_account]', null, true, false, true))->label('Sales Account:');
      $form->custom(GL_UI::all('branch[receivables_account]', null, true, false, false))->label('Receivables Account:');
      $form->custom(GL_UI::all('branch[sales_discount_account]', null, false, false, true))->label('Discount Account:');
      $form->custom(GL_UI::all('branch[payment_discount_account]', null, false, false, true))->label('Prompt Payment Account:');
      $form->textarea('branch[notes]', ['cols'=> 100, 'rows'=> 10])->label('General Notes:');
      $view['debtor_id'] = $this->debtor->id;
      $form->hidden('frame', $this->Input->request('frame'));
      $view->set('form', $form);
      $form->hidden('type', CT_CUSTOMER);
      $contact_form = new Form();
      $view['date'] = date('Y-m-d H:i:s');
      $contact_form->text('contact_name')->label('Contact:');
      $contact_form->textarea('message', ['cols'=> 100, 'rows'=> 10])->label('Entry:');
      $view->set('contact_form', $contact_form);
      if (!$this->Input->get('frame')) {
        $shortcuts = [
          [
            'caption'=> 'Create Quote',
            'Create Quote for this customer!',
            'data'   => '/sales/sales_order_entry.php?type=' . ST_SALESQUOTE . '&add=' . ST_SALESQUOTE . '&debtor_id='
          ],
          ['caption'=> 'Create Order', 'Create Order for this customer!', 'data'=> '/sales/sales_order_entry.php?type=30&add=' . ST_SALESORDER . '&debtor_id='],
          [
            'caption'=> 'Print Statement',
            'Print Statement for this Customer!',
            'data'   => '/reporting/prn_redirect.php?REP_ID=108&PARAM_2=0&PARAM_4=0&PARAM_5=0&PARAM_6=0&PARAM_0='
          ],
          [
            'caption'=> 'Email Statement',
            'Email Statement for this Customer!',
            'data'   => 'emailTab'
          ],
          ['caption'=> 'Customer Payment', 'Make customer payment!', 'data'=> '/sales/customer_payments.php?debtor_id=']
        ];
        $view->set('shortcuts', $shortcuts);
        UI::emailDialogue(CT_CUSTOMER);
      }
      $form = HTMLmin::minify($view->render(true));
      Cache::_set('customer_form', [$form, $this->JS->getState()]);

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
