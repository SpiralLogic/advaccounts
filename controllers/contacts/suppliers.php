<?php
  use ADV\App\Creditor\Creditor;
  use ADV\App\Form\Form;
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
      } elseif ($this->Input->request('id', Input::NUMERIC) > 0) {
        $data['company']     = $this->creditor = new Creditor($this->Input->request('id', Input::NUMERIC));
        $data['contact_log'] = Contact_Log::read($this->creditor->id, CT_SUPPLIER);
        Session::_setGlobal('creditor_id', $this->creditor->id);
      } else {
        $data['company'] = $this->creditor = new Creditor();
      }
      if (AJAX_REFERRER) {
        /** @noinspection PhpUndefinedMethodInspection */
        $data['status'] = $this->creditor->getStatus();
        /** @noinspection PhpUndefinedMethodInspection */
        JS::_renderJSON($data);
      }
      JS::_footerFile("/js/company.js");
      JS::_onload("Company.setValues(" . json_encode($data) . ");");
    }
    protected function search() {
      if (isset($_GET['term'])) {
        $data = Creditor::search($_GET['term']);
        JS::_renderJSON($data);
      }
    }
    protected function index() {
      Page::start(_($help_context = "Suppliers"), SA_SUPPLIER, $this->Input->request('frame'));
      if (isset($_POST['delete'])) {
        $this->delete();
      }
      $this->JS->autocomplete('supplier', 'Company.fetch');
      $form          = new Form();
      $menu          = new MenuUI();
      $view          = new View('contacts/supplier');
      $view['frame'] = $this->Input->get('frame') || $this->Input->get('id');
      $view->set('menu', $menu);
      $form->text('name', $this->creditor->name, ['class' => 'width60'])->label('Supplier Name:');
      $form->text('id', $this->creditor->id, ['class' => 'small', 'maxlength' => 7])->label('Supplier ID:');
      $view->set('form', $form);
      $view->set('creditor_id', $this->creditor->id);
      if (!$this->Input->get('frame')) {
        $shortcuts = new MenuUI(array('noajax' => true));
        $shortcuts->addLink('Supplier Payment', 'Make supplier payment!', '/purchases/supplier_payment.php?creditor_id=', 'id');
        $shortcuts->addLink('Supplier Invoice', 'Make supplier invoice!', '/purchases/supplier_invoice.php?New=1&creditor_id=', 'id');
        $view->set('shortcuts', $shortcuts);
        UI::emailDialogue(CT_SUPPLIER);
      }
      $form->text('contact', $this->creditor->contact)->label('Contact:');
      $form->text('phone', $this->creditor->phone)->label('Phone Number:');
      $form->text('fax', $this->creditor->fax)->label('Fax Number:');
      $form->text('email', $this->creditor->email)->label('Email:');
      $form->textarea('address', $this->creditor->address, ['cols'=> 37, 'rows'=> 4])->label('Street:');
      $postcode = new Contact_Postcode(array(
                                            'city'     => array('city', $this->creditor->city),
                                            'state'    => array('state', $this->creditor->state),
                                            'postcode' => array('postcode', $this->creditor->postcode)
                                       ));
      $view->set('postcode', $postcode);
      $form->text('supp_phone', $this->creditor->phone2)->label('Phone Number:');
      $form->textarea('supp_address', $this->creditor->supp_address, ['cols'=> 37, 'rows'=> 4])->label('Address:');
      $supp_postcode = new Contact_Postcode(array(
                                                 'city'     => array('supp_city', $this->creditor->city),
                                                 'state'    => array('supp_state', $this->creditor->state),
                                                 'postcode' => array('supp_postcode', $this->creditor->postcode)
                                            ));
      $view->set('supp_postcode', $supp_postcode);
      $form->percent("Prompt Payment Discount:", 'payment_discount', $this->creditor->discount, ["disabled"=> User::i()->hasAccess(SA_SUPPLIERCREDIT)]);
      $form->number("Credit Limit:", 'credit_limit', $this->creditor->credit_limit, null, null, ["disabled"=> User::i()->hasAccess(SA_SUPPLIERCREDIT)]);
      $form->text('tax_id', $this->creditor->tax_id)->label("GSTNo:");
      $form->label('Tax Group:', 'tax_group_id', Tax_Groups::select('tax_group_id', $this->creditor->tax_group_id));
      $form->textarea('notes', $this->creditor->notes)->label('General Notes:');
      $form->label('Inactive:', 'inactive', UI::select('inactive', ['0'=> 'No', '1'=> 'Yes'], ['name' => 'inactive'], $this->creditor->inactive, true));
      if (!$this->creditor->id) {
        $form->label('Currency Code:', 'curr_code', GL_Currency::select('curr_code', $this->creditor->curr_code));
      } else {
        $form->label('Currency Code:', 'curr_code', $this->creditor->curr_code);
        $form->hidden('curr_code', $this->creditor->curr_code);
      }
      $form->label('Payment Terms:', 'payment_terms', GL_UI::payment_terms('payment_terms', $this->creditor->payment_terms));
      $form->label('Payable Account:', 'payable_account', GL_UI::all('payable_account', $this->creditor->payable_account, false, false, true));
      $form->label(
        'Prompt Payment Account:',
        'payment_discount_account',
        GL_UI::all('payment_discount_account', $this->creditor->payment_discount_account, false, false, true)
      );
      $form->hidden('type', CT_SUPPLIER);
      $view['date'] = date('Y-m-d H:i:s');
      $form->text('contact_name', $this->creditor->contact_name)->label('Contact:');
      $form->textarea('messageLog', '', ['class'=> 'big', 'cols'=> 40]);

      $form->textarea('Entry:', 'message', '', ['cols'=> 100, 'rows'=> 10]);
      $view->render();
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
