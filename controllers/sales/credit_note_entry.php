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
  //
  //	Entry/Modify free hand Credit Note
  //
  class CreditNote extends \ADV\App\Controller\Base
  {

    public $credit;
    protected function before() {
      JS::openWindow(950, 500);
      $this->credit = Orders::session_get() ? : null;
      if ($this->Input->get(Orders::NEW_CREDIT)) {
        $this->setTitle("Customer Credit Note");
        $this->handle_new_credit(0);
      } elseif ($this->Input->get(Orders::MODIFY_CREDIT)) {
        $this->setTitle("Modifying Customer Credit Note " . $this->Input->get(Orders::MODIFY_CREDIT));
        $this->handle_new_credit($this->Input->get(Orders::MODIFY_CREDIT));
      } else {
        $this->setTitle("Customer Credit Note");
      }
      if (Forms::isListUpdated('branch_id')) {
        // when branch is selected via external editor also customer can change
        $br                 = Sales_Branch::get(Input::post('branch_id'));
        $_POST['debtor_id'] = $br['debtor_id'];
        Ajax::activate('debtor_id');
      }
      if (isset($_GET[ADDED_ID])) {
        $this->pageComplete();
      }
      if (isset($_POST[Orders::CANCEL_CHANGES])) {
        $this->cancelCredit();
      }
      $id = Forms::findPostPrefix(MODE_DELETE);
      if ($id != -1) {
        $this->credit->remove_from_order($id);
        Item_Line::start_focus('_stock_id_edit');
      }
      if (isset($_POST[Orders::ADD_LINE]) && check_item_data()) {
        $this->credit->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
        Item_Line::start_focus('_stock_id_edit');
      }
      if (isset($_POST[Orders::UPDATE_ITEM])) {
        if ($_POST[Orders::UPDATE_ITEM] != "" && check_item_data()) {
          $this->credit->update_order_item($_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
        }
        Item_Line::start_focus('_stock_id_edit');
      }
      if (isset($_POST['cancelItem'])) {
        Item_Line::start_focus('_stock_id_edit');
      }
      if (isset($_POST['ProcessCredit'])) {
        $this->processCredit();
      }
    }
    protected function processCredit() {
      if (!$this->can_process()) {
        return false;
      }
      if ($_POST['CreditType'] == "WriteOff" && (!isset($_POST['WriteOffGLCode']) || $_POST['WriteOffGLCode'] == '')) {
        Event::warning(_("For credit notes created to write off the stock, a general ledger account is required to be selected."), 1, 0);
        Event::warning(_("Please select an account to write the cost of the stock off to, then click on Process again."), 1, 0);
        exit;
      }
      if (!isset($_POST['WriteOffGLCode'])) {
        $_POST['WriteOffGLCode'] = 0;
      }
      $this->credit = $this->copy_to_cn($this->credit);
      $credit_no    = $this->credit->write($_POST['WriteOffGLCode']);
      Dates::newDocDate($this->credit->document_date);
      $this->pageComplete($credit_no);
    }
    protected function cancelCredit() {
      $type     = $this->credit->trans_type;
      $order_no = (is_array($this->credit->trans_no)) ? key($this->credit->trans_no) : $this->credit->trans_no;
      Orders::session_delete($_POST['order_id']);
      $this->credit = $this->handle_new_credit($order_no);
    }
    protected function pageComplete($credit_no) {
      $trans_type = ST_CUSTCREDIT;
      Event::success(sprintf(_("Credit Note # %d has been processed"), $credit_no));
      Display::note(Debtor::viewTrans($trans_type, $credit_no, _("&View this credit note")), 0, 1);
      Display::note(Reporting::print_doc_link($credit_no . "-" . $trans_type, _("&Print This Credit Invoice"), true, ST_CUSTCREDIT), 0, 1);
      Display::note(Reporting::print_doc_link($credit_no . "-" . $trans_type, _("&Email This Credit Invoice"), true, ST_CUSTCREDIT, false, "printlink", "", 1), 0, 1);
      Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")));
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another &Credit Note"), "NewCredit=yes");
      Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$credit_no");
      $this->Ajax->_activate('_page_body', "/sales/view/view_credit?trans_no=$credit_no&trans_type=$trans_type", '/sales/credit_note_entry?NewCredit=Yes');
    }
    protected function index() {
      Page::start($this->title, SA_SALESCREDIT);
      Forms::start();
      Forms::hidden('order_id', $_POST['order_id']);
      $customer_error = Sales_Credit::header($this->credit);
      if ($customer_error == "") {
        Table::start('tables_style2 width90 pad10');
        echo "<tr><td>";
        Sales_Credit::display_items(_("Credit Note Items"), $this->credit);
        Sales_Credit::option_controls($this->credit);
        echo "</td></tr>";
        Table::end();
      } else {
        Event::error($customer_error);
      }
      Forms::submitCenterBegin(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
      Forms::submitCenterEnd('ProcessCredit', _("Process Credit Note"), '', false);
      echo "</tr></table></div>";
      Forms::end();
      Page::end();
    }
    protected function runValidation() {
      Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
      Validation::check(Validation::BRANCHES_ACTIVE, _("There are no customers, or there are no customers with branches. Please define customers and customer branches."));
    }
    /***
     * @param $this->credit
     *
     * @return Sales_Order
     */
    protected function copy_to_cn() {
      $this->credit->Comments      = $_POST['CreditText'];
      $this->credit->document_date = $_POST['OrderDate'];
      $this->credit->freight_cost  = Validation::input_num('ChargeFreightCost');
      $this->credit->location      = (isset($_POST['location']) ? $_POST['location'] : "");
      $this->credit->sales_type    = $_POST['sales_type_id'];
      if ($this->credit->trans_no == 0) {
        $this->credit->reference = $_POST['ref'];
      }
      $this->credit->ship_via      = $_POST['ShipperID'];
      $this->credit->dimension_id  = $_POST['dimension_id'];
      $this->credit->dimension2_id = $_POST['dimension2_id'];
    }
    /**
     * @param $this->credit
     */
    protected function copy_from_cn() {
      $this->credit               = Sales_Order::check_edit_conflicts($this->credit);
      $_POST['CreditText']        = $this->credit->Comments;
      $_POST['debtor_id']         = $this->credit->debtor_id;
      $_POST['branch_id']         = $this->credit->Branch;
      $_POST['OrderDate']         = $this->credit->document_date;
      $_POST['ChargeFreightCost'] = Num::priceFormat($this->credit->freight_cost);
      $_POST['location']          = $this->credit->location;
      $_POST['sales_type_id']     = $this->credit->sales_type;
      if ($this->credit->trans_no == 0) {
        $_POST['ref'] = $this->credit->reference;
      }
      $_POST['ShipperID']     = $this->credit->ship_via;
      $_POST['dimension_id']  = $this->credit->dimension_id;
      $_POST['dimension2_id'] = $this->credit->dimension2_id;
      $_POST['order_id']      = $this->credit->order_id;
      Orders::session_set($this->credit);
    }
    /**
     * @param $trans_no
     *
     * @return Sales_Order
     */
    protected function handle_new_credit($trans_no) {
      $this->credit = new Sales_Order(ST_CUSTCREDIT, $trans_no);
      Orders::session_delete($this->credit->order_id);
      $this->credit->start();
      $this->copy_from_cn();
    }
    /**
     * @param Sales_Order $this->credit
     *
     * @return bool
     */
    protected function can_process() {
      $input_error = 0;
      if ($this->credit->count_items() == 0 && (!Validation::post_num('ChargeFreightCost', 0))) {
        return false;
      }
      if ($this->credit->trans_no == 0) {
        if (!Ref::is_valid($_POST['ref'])) {
          Event::error(_("You must enter a reference."));
          JS::setFocus('ref');
          $input_error = 1;
        } elseif (!Ref::is_new($_POST['ref'], ST_CUSTCREDIT)) {
          $_POST['ref'] = Ref::get_next(ST_CUSTCREDIT);
        }
      }
      if (!Dates::isDate($_POST['OrderDate'])) {
        Event::error(_("The entered date for the credit note is invalid."));
        JS::setFocus('OrderDate');
        $input_error = 1;
      } elseif (!Dates::isDateInFiscalYear($_POST['OrderDate'])) {
        Event::error(_("The entered date is not in fiscal year."));
        JS::setFocus('OrderDate');
        $input_error = 1;
      }
      return ($input_error == 0);
    }
    /**
     * @return bool
     */
    protected function check_item_data() {
      if (!Validation::post_num('qty', 0)) {
        Event::error(_("The quantity must be greater than zero."));
        JS::setFocus('qty');
        return false;
      }
      if (!Validation::post_num('price', 0)) {
        Event::error(_("The entered price is negative or invalid."));
        JS::setFocus('price');
        return false;
      }
      if (!Validation::post_num('Disc', 0, 100)) {
        Event::error(_("The entered discount percent is negative, greater than 100 or invalid."));
        JS::setFocus('Disc');
        return false;
      }
      return true;
    }
  }
