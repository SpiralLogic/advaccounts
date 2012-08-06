<?php
    use ADV\App\Debtor\Debtor;

    /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
    class DebtorPayment extends \ADV\App\Controller\Base
    {
        public $date_banked;
        public $debtor_id;
        protected function before()
        {
            if ($_SERVER['REQUEST_METHOD'] == "GET") {
                if (Input::get('account')) {
                    $_POST['bank_acount'] = Input::get('account');
                }
                if (Input::get('amount')) {
                    $_POST['amount'] = Input::get('amount');
                }
                if (Input::get('memo')) {
                    $_POST['memo_'] = Input::get('memo');
                }
                if (Input::get('date')) {
                    $_POST['DateBanked'] = Input::get('date');
                }
            }
            $this->JS->_openWindow(900, 500);
            $this->JS->_footerFile('/js/payalloc.js');
            $this->debtor_id    = Input::postGetGlobal('debtor_id');
            $_POST['debtor_id'] =& $this->debtor_id;
            if (Forms::isListUpdated('branch_id') || !$_POST['debtor_id']) {
                $br              = Sales_Branch::get(Input::post('branch_id'));
                $this->debtor_id = $br['debtor_id'];
                Ajax::activate('debtor_id');
            }
            $this->Session->_setGlobal('debtor_id', $this->debtor_id);
            $this->date_banked = Input::post('DateBanked', null, Dates::newDocDate());
            if (!Dates::isDateInFiscalYear($this->date_banked)) {
                $this->date_banked = Dates::endFiscalYear();
            }
            $_POST['DateBanked'] = &$this->date_banked;
            // validate inputs
            if (isset($_POST['_debtor_id_button'])) {
                Ajax::activate('branch_id');
            }
            if (isset($_POST['_DateBanked_changed'])) {
                Ajax::activate('_ex_rate');
            }
            if (Input::hasPost('debtor_id') || Forms::isListUpdated('bank_account')) {
                Ajax::activate('_page_body');
            }
            if (!isset($_POST['bank_account'])) // first page call
            {
                $_SESSION['alloc'] = new GL_Allocation(ST_CUSTPAYMENT, 0);
            }
            if (!Forms::isListUpdated('bank_account')) {
                $_POST['bank_account'] = Bank_Account::get_customer_default($this->debtor_id);
            }
        }
        protected function addPaymentItem()
        {
            $cust_currency = Bank_Currency::for_debtor($this->debtor_id);
            $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
            $comp_currency = Bank_Currency::for_company();
            if ($comp_currency != $bank_currency && $bank_currency != $cust_currency) {
                $rate = 0;
            } else {
                $rate = Validation::input_num('_ex_rate');
            }
            if (Input::hasPost('createinvoice')) {
                GL_Allocation::create_miscorder(
                    new Debtor($this->debtor_id),
                    $_POST['branch_id'],
                    $this->date_banked,
                    $_POST['memo_'],
                    $_POST['ref'],
                    Validation::input_num('amount'),
                    Validation::input_num('discount')
                );
            }
            $payment_no                  = Debtor_Payment::add(
                0,
                $this->debtor_id,
                $_POST['branch_id'],
                $_POST['bank_account'],
                $this->date_banked,
                $_POST['ref'],
                Validation::input_num('amount'),
                Validation::input_num('discount'),
                $_POST['memo_'],
                $rate,
                Validation::input_num('charge')
            );
            $_SESSION['alloc']->trans_no = $payment_no;
            $_SESSION['alloc']->write();
            Event::success(_("The customer payment has been successfully entered."));
            Display::submenu_print(_("&Print This Receipt"), ST_CUSTPAYMENT, $payment_no . "-" . ST_CUSTPAYMENT, 'prtopt');
            Display::link_no_params("/sales/inquiry/customer_inquiry.php", _("Show Invoices"));
            Display::note(GL_UI::view(ST_CUSTPAYMENT, $payment_no, _("&View the GL Journal Entries for this Customer Payment")));
            //	Display::link_params( "/sales/allocations/customer_allocate.php", _("&Allocate this Customer Payment"), "trans_no=$payment_no&trans_type=12");
            Display::link_no_params("/sales/customer_payments.php", _("Enter Another &Customer Payment"));
            $this->Ajax->_activate('_page_body');
            Page::footer_exit();
        }
        protected function index()
        {
            Page::start(_($help_context = "Customer Payment Entry"), SA_SALESPAYMNT, Input::request('frame'));
            $this->runAction();
            Forms::start();
            Table::startOuter('tablestyle2 width90 pad2');
            Table::section(1);
            Debtor::newselect();
            Forms::refRow(_("Reference:"), 'ref', null, Ref::get_next(ST_CUSTPAYMENT));
            Debtor_Payment::read_customer_data($this->debtor_id);
            $display_discount_percent = Num::percentFormat($_POST['payment_discount'] * 100) . "%";
            Table::section(2);
            Debtor_Branch::row(_("Branch:"), $this->debtor_id, 'branch_id', null, false, true, true);
            Bank_Account::row(_("Into Bank Account:"), 'bank_account', null, true);
            Table::section(3);
            Forms::dateRow(_("Date of Deposit:"), 'DateBanked', '', true, 0, 0, 0, null, true);
            $comp_currency = Bank_Currency::for_company();
            $cust_currency = Bank_Currency::for_debtor($this->debtor_id);
            $bank_currency = Bank_Currency::for_company($_POST['bank_account']);
            if ($cust_currency != $bank_currency) {
                GL_ExchangeRate::display($bank_currency, $cust_currency, $this->date_banked, ($bank_currency == $comp_currency));
            }
            Forms::AmountRow(_("Bank Charge:"), 'charge', 0);
            Table::endOuter(1);
            Display::div_start('alloc_tbl');
            if ($cust_currency == $bank_currency) {
                $_SESSION['alloc']->read();
                GL_Allocation::show_allocatable(false);
            }
            Display::div_end();
            Table::start('tablestyle width70');
            Row::label(_("Customer prompt payment discount :"), $display_discount_percent);
            Forms::AmountRow(_("Amount of Discount:"), 'discount', 0);
            //  if (User::i()->hasAccess(SS_SALES) && !Input::post('TotalNumberOfAllocs')) {
            Forms::checkRow(_("Create invoice and apply for this payment: "), 'createinvoice');
            //  }
            Forms::AmountRow(_("Amount:"), 'amount');
            Forms::textareaRow(_("Memo:"), 'memo_', null, 22, 4);
            Table::end(1);
            if ($cust_currency != $bank_currency) {
                Event::warning(_("Amount and discount are in customer's currency."));
            }

            Forms::submitCenter('_action', 'addPaymentItem', true, 'Add Payment', 'default');

            Forms::end();
            $this->addJS();
            Page::end(!Input::request('frame'));
        }
        protected function addJS()
        {
            $js
              = <<<JS
var ci = $("#createinvoice"), ci_row = ci.closest('tr'),alloc_tbl = $('#alloc_tbl'),hasallocated = false;
 alloc_tbl.find('.amount').each(function() { if (this.value != 0) hasallocated = true});
 if (hasallocated && !ci.prop('checked')) ci_row.hide(); else ci_row.show();
JS;
            $this->JS->_addLiveEvent('a, :input', 'click change', $js, 'wrapper', true);
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
            Validation::check(Validation::CUSTOMERS, _("There are no customers defined in the system."));
            Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
            if ($this->debtor_id) {
                Validation::check(Validation::BRANCHES, _("No Branches for Customer") . $this->debtor_id, $this->debtor_id);
            }
        }
    }

    new DebtorPayment();
