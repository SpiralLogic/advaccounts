<?php
  use ADV\App\Creditor\Creditor;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class SupplierPayment extends \ADV\App\Controller\Base
  {
    protected $supplier_currency;
    protected $bank_currency;
    protected $company_currency;
    protected $creditor_id;
    public $bank_account;
    protected function before() {
      JS::openWindow(900, 500);
      JS::footerFile('/js/payalloc.js');
      $_POST['creditor_id'] = Input::postGetGlobal('creditor_id', null, -1);
      $this->creditor_id    = &$_POST['creditor_id'];
      $this->Session->_setGlobal('creditor_id', $this->creditor_id);
      if (!$this->bank_account) // first page call
      {
        $_SESSION['alloc'] = new GL_Allocation(ST_SUPPAYMENT, 0);
      }
      $_POST['bank_account'] = Input::postGetGlobal('bank_account', null, -1);
      $this->bank_account    = &$_POST['bank_account'];
      if (!isset($_POST['DatePaid'])) {
        $_POST['DatePaid'] = Dates::newDocDate();
        if (!Dates::isDateInFiscalYear($_POST['DatePaid'])) {
          $_POST['DatePaid'] = Dates::endFiscalYear();
        }
      }
      if (isset($_POST['_DatePaid_changed'])) {
        Ajax::activate('_ex_rate');
      }
      if (Input::post('_control') == 'creditor' || Forms::isListUpdated('bank_account')) {
        $_SESSION['alloc']->read();
        Ajax::activate('alloc_tbl');
      }
      $this->company_currency  = Bank_Currency::for_company();
      $this->supplier_currency = Bank_Currency::for_creditor($this->creditor_id);
      $this->bank_currency     = Bank_Currency::for_company($_POST['bank_account']);
    }
    protected function index() {
      Page::start(_($help_context = "Supplier Payment Entry"), SA_SUPPLIERPAYMNT);
      if (isset($_POST['ProcessSuppPayment']) && Creditor_Payment::can_process()) {
        $this->processSupplierPayment();
      }
      Forms::start();
      Table::startOuter('tablestyle2 width80 pad5');
      Table::section(1);
      Creditor::newselect();
      Bank_Account::row(_("Bank Account:"), 'bank_account', null, true);
      Table::section(2);
      Forms::refRow(_("Reference:"), 'ref', '', Ref::get_next(ST_SUPPAYMENT));
      Forms::dateRow(_("Date Paid") . ":", 'DatePaid', '', true, 0, 0, 0, null, true);
      Table::section(3);
      if ($this->bank_currency != $this->supplier_currency) {
        GL_ExchangeRate::display($this->bank_currency, $this->supplier_currency, $_POST['DatePaid'], true);
      }
      Forms::AmountRow(_("Bank Charge:"), 'charge');
      Table::endOuter(1); // outer table
      Display::div_start('alloc_tbl');
      if ($this->bank_currency == $this->supplier_currency) {
        $_SESSION['alloc']->read();
        GL_Allocation::show_allocatable(false);
      }
      Display::div_end();
      Table::start('tablestyle width60');
      Forms::AmountRow(_("Amount of Discount:"), 'discount');
      Forms::AmountRow(_("Amount of Payment:"), 'amount');
      Forms::textareaRow(_("Memo:"), 'memo_', null, 22, 4);
      Table::end(1);
      if ($this->bank_currency != $this->supplier_currency) {
        Event::warning("The amount and discount are in the bank account's currency.");
      }
      Forms::submitCenter('ProcessSuppPayment', _("Enter Payment"), true, '', 'default');
      Forms::end();
      Page::end();
    }
    protected function processSupplierPayment() {
      if ($this->company_currency != $this->bank_currency && $this->bank_currency != $this->supplier_currency) {
        $rate = 0;
      } else {
        $rate = Validation::input_num('_ex_rate');
      }
      $payment_id = Creditor_Payment::add($this->creditor_id, $_POST['DatePaid'], $_POST['bank_account'], Validation::input_num('amount'), Validation::input_num('discount'), $_POST['ref'], $_POST['memo_'], $rate, Validation::input_num('charge'));
      Dates::newDocDate($_POST['DatePaid']);
      $_SESSION['alloc']->trans_no = $payment_id;
      $_SESSION['alloc']->write();
      //unset($this->creditor_id);
      unset($_POST['bank_account'], $_POST['DatePaid'], $_POST['currency'], $_POST['memo_'], $_POST['amount'], $_POST['discount'], $_POST['ProcessSuppPayment']);
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$payment_id&creditor_id=" . $this->creditor_id);
      Event::success(_("Payment has been sucessfully entered"));
      Display::submenu_print(_("&Print This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, 'prtopt');
      Display::submenu_print(_("&Email This Remittance"), ST_SUPPAYMENT, $payment_id . "-" . ST_SUPPAYMENT, null, 1);
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Invoice"), "New=1", true, 'class="button"');
      HTML::br();
      Display::note(GL_UI::view(ST_SUPPAYMENT, $payment_id, _("View the GL &Journal Entries for this Payment"), false, 'button'));
      // Display::link_params($path_to_root . "/purchases/allocations/supplier_allocate.php", _("&Allocate this Payment"), "trans_no=$payment_id&trans_type=22");
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter another supplier &payment"), "creditor_id=" . $this->creditor_id, true, 'class="button"');
      $this->Ajax->_activate('_page_body');
      Page::footer_exit();
    }
    protected function runValidation() {
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
    }
  }

  new SupplierPayment();
