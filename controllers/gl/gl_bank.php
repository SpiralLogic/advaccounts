<?php
  use ADV\Core\Row;
  use ADV\App\Form\Button;
  use ADV\App\Form\Form;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class GlBank extends \ADV\App\Controller\Base
  {
    /** @var Item_Order */
    protected $order;
    protected $security;
    protected $trans_type;
    protected $trans_no;
    protected $type;
    protected function before() {
      if ($this->Input->hasSession('pay_items')) {
        $this->order = $this->Input->session('pay_items');
      }
      if (!$this->order) {
        if ($this->Input->get('NewPayment')) {
          $this->newOrder(ST_BANKPAYMENT);
        } elseif ($this->Input->get('NewDeposit')) {
          $this->newOrder(ST_BANKDEPOSIT);
        }
      }
      $this->security = $this->order->trans_type == ST_BANKPAYMENT ? SA_PAYMENT : SA_DEPOSIT;

      $this->JS->openWindow(950, 500);
      $this->type = $this->order->trans_type == ST_BANKPAYMENT ? 'Payment' : 'Deposit';
      $this->setTitle('Bank Account ' . $this->type . ' Entry');
      if ($_SERVER['REQUEST_METHOD'] == "GET") {
        if ($this->Input->hasGet('account', 'amount', 'memo', 'date')) {
          $_POST['bank_account'] = $this->Input->get('account');
          $_POST['total_amount'] = $_POST['amount'] = abs($this->Input->get('amount'));
          $_POST['memo_']        = $this->Input->get('memo');
          $_POST['date_']        = $this->Input->get('date');
        }
      }
      if (Forms::isListUpdated('PersonDetailID')) {
        $br                 = Sales_Branch::get($this->Input->post('PersonDetailID'));
        $_POST['person_id'] = $br['debtor_id'];
        $this->Ajax->activate('person_id');
      }
      if (isset($_POST['_date__changed'])) {
        $this->Ajax->activate('_ex_rate');
      }
      $id = Forms::findPostPrefix(MODE_DELETE);
      if ($id != -1) {
        $this->deleteItem($id);
      }
      if (isset($_POST['addLine']) && $this->checkItemData()) {
        $this->newItem();
      }
      if (isset($_POST['updateItem'])) {
        $this->updateItem();
      }
      if (isset($_POST['cancelItem'])) {
        Item_Line::start_focus('_code_id_edit');
      }
      if (isset($_POST['go'])) {
        $this->quickEntries();
      }
    }
    protected function index() {
      Page::start($this->title, $this->security);
      $this->runAction();
      Forms::start();
      Bank_UI::header($this->order);
      Table::start('tablesstyle2 width90 pad10');
      Row::start();
      echo "<td>";
      Bank_UI::items(_($this->type . " Items"), $this->order);
      Bank_UI::option_controls();
      echo "</td>";
      Row::end();
      Table::end(1);
echo '<div class="center">';
      echo (new Button('_action',COMMIT,COMMIT))->type('success')->preIcon(ICON_SUBMIT);
      echo "</div>";
      Forms::end();
      Page::end();
    }
    protected function quickEntries() {
      GL_QuickEntry::addEntry(
        $this->order,
        $_POST['person_id'],
        Validation::input_num('total_amount'),
        $this->order->trans_type == ST_BANKPAYMENT ? QE_PAYMENT : QE_DEPOSIT
      );
      $_POST['total_amount'] = Num::_priceFormat(0);
      $this->Ajax->activate('total_amount');
      Item_Line::start_focus('_code_id_edit');
    }
    /**
     * @return bool
     */
    protected function canProcess() {
      if ($this->order->count_gl_items() < 1) {
        Event::error(_("You must enter at least one payment line."));
        $this->JS->setFocus('code_id');

        return false;
      }
      if ($this->order->gl_items_total() == 0.0) {
        Event::error(_("The total bank amount cannot be 0."));
        $this->JS->setFocus('code_id');

        return false;
      }
      if (!Ref::is_new($_POST['ref'], $this->order->trans_type)) {
        $_POST['ref'] = Ref::get_next($this->order->trans_type);
      }
      if (!Dates::_isDate($_POST['date_'])) {
        Event::error(_("The entered date is invalid."));
        $this->JS->setFocus('date_');

        return false;
      } elseif (!Dates::_isDateInFiscalYear($_POST['date_'])) {
        Event::error(_("The entered date is not in fiscal year."));
        $this->JS->setFocus('date_');

        return false;
      }

      return true;
    }
    protected function commit() {
      if (!$this->canProcess()) return ;
      $trans            = GL_Bank::add_bank_transaction(
        $this->order->trans_type,
        $_POST['bank_account'],
        $this->order,
        $_POST['date_'],
        $_POST['PayType'],
        $_POST['person_id'],
        $this->Input->post('PersonDetailID'),
        $_POST['ref'],
        $_POST['memo_']
      );
      $this->trans_type = $trans[0];
      $this->trans_no   = $trans[1];
      Dates::_newDocDate($_POST['date_']);
      $this->order->clear_items();
      unset($_SESSION['pay_items']);
      $this->pageComplete();
    }
    protected function pageComplete() {
      Event::success(_($this->type . " " . $this->trans_no . " has been entered"));
      Display::note(GL_UI::view($this->trans_type, $this->trans_no, _("&View the GL Postings for this " . $this->type)));
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter A &Payment"), "NewPayment=yes");
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter A &Deposit"), "NewDeposit=yes");
      $this->Ajax->activate('_page_body');
      Page::footer_exit();
    }
    /**
     * @return bool
     */
    protected function checkItemData() {
      if ($this->Input->post('PayType') == PT_QUICKENTRY && $this->order->count_gl_items() < 1) {
        Event::error('You must select and add quick entry before adding extra lines!');
        $this->JS->setFocus('total_amount');

        return false;
      }
      //if (!Validation::post_num('amount', 0))
      //{
      //	Event::error( _("The amount entered is not a valid number or is less than zero."));
      //	$this->JS->setFocus('amount');
      //	return false;
      //}

      if ($_POST['code_id'] == $_POST['bank_account']) {
        Event::error(_("The source and destination accouts cannot be the same."));
        $this->JS->setFocus('code_id');

        return false;
      }
      if (Bank_Account::is($_POST['code_id'])) {
        Event::error(_("You cannot make a " . $this->type . " from a bank account. Please use the transfer funds facility for this."));
        $this->JS->setFocus('code_id');

        return false;
      }

      return true;
    }
    protected function updateItem() {
      $amount = ($this->order->trans_type == ST_BANKPAYMENT ? 1 : -1) * Validation::input_num('amount');
      if ($_POST['updateItem'] != "" && $this->checkItemData()) {
        $this->order->update_gl_item($_POST['Index'], $_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
      }
      Item_Line::start_focus('_code_id_edit');
    }
    /**
     * @param $id
     */
    protected function deleteItem($id) {
      $this->order->remove_gl_item($id);
      Item_Line::start_focus('_code_id_edit');
    }
    protected function newItem() {
      $amount = ($this->order->trans_type == ST_BANKPAYMENT ? 1 : -1) * Validation::input_num('amount');
      $this->order->add_gl_item($_POST['code_id'], $_POST['dimension_id'], $_POST['dimension2_id'], $amount, $_POST['LineMemo']);
      Item_Line::start_focus('_code_id_edit');
    }
    /**
     * @param $type
     */
    protected function newOrder($type) {
      if (isset($this->order)) {
        unset($_SESSION['pay_items']);
      }
      $this->order    = $_SESSION['pay_items'] = new Item_Order($type);
      $_POST['date_'] = Dates::_newDocDate();
      if (!Dates::_isDateInFiscalYear($_POST['date_'])) {
        $_POST['date_'] = Dates::_endFiscalYear();
      }
      $this->order->tran_date = $_POST['date_'];
    }
    protected function runValidation() {
      Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
    }
  }

  new GlBank();
