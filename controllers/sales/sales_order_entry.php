<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  use ADV\App\Controller\Base;
  use ADV\Core\View;
  use ADV\Core\Errors;
  use ADV\Core\Num;
  use ADV\Core\Dates;
  use ADV\Core\Event;
  use ADV\Core\Input;

  /**

   */
  class SalesOrder extends Base
  {

    protected $addTitles = array(
      ST_SALESQUOTE  => "New Sales Quotation Entry", //
      ST_SALESINVOICE=> "Direct Sales Invoice", //
      ST_CUSTDELIVERY=> "Direct Sales Delivery", //
      ST_SALESORDER  => "New Sales Order Entry"
    );
    protected $modifyTitles = array(
      ST_SALESQUOTE => "Modifying Sales Quotation # ", //
      ST_SALESORDER => "Modifying Sales Order # "
    );
    protected $typeSecurity = array(
      ST_SALESORDER          => SA_SALESORDER, //
      ST_SALESQUOTE          => SA_SALESQUOTE, ///
      ST_CUSTDELIVERY        => SA_SALESDELIVERY, //
      Orders::QUOTE_TO_ORDER => SA_SALESORDER, //
      Orders::CLONE_ORDER    => SA_SALESORDER, //
      ST_SALESINVOICE        => SA_SALESINVOICE
    );
    protected $security;
    public $type;
    /***
     * @var \Sales_Order;
     */
    public $order;
    protected function before() {
      $this->order = Orders::session_get() ? : null;
      $this->JS->_openWindow(900, 500);
      if (Input::get('customer_id', Input::NUMERIC)) {
        $this->action         = Orders::CANCEL_CHANGES;
        $_POST['customer_id'] = $_GET['customer_id'];
        $this->Ajax->activate('customer_id');
      }
      $this->type = Input::get('type');
      $this->setTitle("New Sales Order Entry");
      if (Input::get(Orders::ADD, Input::NUMERIC, false) !== false) {
        $this->setTitle($this->addTitles[$this->type]);
        $this->order = $this->createOrder($this->type, 0);
      } elseif (Input::get(Orders::UPDATE, Input::NUMERIC, -1) > 0) {
        $this->setTitle($this->modifyTitles[$this->type] . $_GET[Orders::UPDATE]);
        $this->order = $this->createOrder($this->type, Input::get(Orders::UPDATE));
      } elseif (Input::get(Orders::QUOTE_TO_ORDER)) {
        $this->type = Orders::QUOTE_TO_ORDER;
        $this->setTitle("New Order from Quote");
        $this->order = $this->createOrder(ST_SALESQUOTE, $_GET[Orders::QUOTE_TO_ORDER]);
      } elseif (Input::get(Orders::CLONE_ORDER)) {
        $this->type = Orders::CLONE_ORDER;
        $this->setTitle("New order from previous order");
        $this->order = $this->createOrder(ST_SALESORDER, Input::get(Orders::CLONE_ORDER));
      }
      $this->setSecurity();
      if (!isset($this->order)) {
        $this->order = $this->createOrder(ST_SALESORDER, 0);
      }
    }
    protected function index() {
      Page::start($this->title, $this->security);
      $this->checkBranch();
      if (isset($_GET[REMOVED])) {
        $this->removed();
      }
      $this->checkRowDelete();
      $this->runAction();
      $this->runValidation();
      switch ($this->order->trans_type) {
        case ST_SALESINVOICE:
          $idate       = _("Invoice Date:");
          $orderitems  = _("Sales Invoice Items");
          $deleteorder = _("Delete Invoice");
          $corder      = '';
          $porder      = _("Place Invoice");
          break;
        case ST_CUSTDELIVERY:
          $idate       = _("Delivery Date:");
          $orderitems  = _("Delivery Note Items");
          $deleteorder = _("Delete Delivery");
          $corder      = '';
          $porder      = _("Place Delivery");
          break;
        case ST_SALESQUOTE:
          $idate       = _("Quotation Date:");
          $orderitems  = _("Sales Quotation Items");
          $deleteorder = _("Delete Quotation");
          $porder      = _("Place Quotation");
          $corder      = _("Commit Quotations Changes");
          break;
        case ST_SALESORDER;
        default:
          $idate       = _("Order Date:");
          $orderitems  = _("Sales Order Items");
          $deleteorder = _("Delete Order");
          $porder      = _("Place Order");
          $corder      = _("Commit Order Changes");
          break;
      }
      Forms::start();
      $customer_error = $this->order->header($idate);
      if ($customer_error != "") {
        $this->exitError($customer_error);
      }
      Forms::hidden('order_id', $_POST['order_id']);
      Table::start('tablesstyle center width90 pad10');
      echo "<tr><td>";
      $edit_line = $this->getActionId(Orders::EDIT_LINE);
      $this->order->summary($orderitems, $edit_line);
      echo "</td></tr><tr><td>";
      $this->order->display_delivery_details();
      echo "</td></tr>";
      Table::end(1);
      Display::div_start('controls', 'items_table');
      $buttons = [];
      if ($this->order->trans_no > 0 && $this->User->hasAccess(SA_VOIDTRANSACTION) && !($this->order->trans_type == ST_SALESORDER && $this->order->has_deliveries())) {
        Form::submitConfirm('_action', Orders::DELETE_ORDER, _('You are about to void this Document.\nDo you want to continue?'));
        $buttons[] = Form::submit('_action', Orders::DELETE_ORDER, false, $deleteorder); //, _('Cancels document entry or removes sales order when editing an old document')
      }
      $buttons[] = Form::submit('_action', Orders::CANCEL_CHANGES, false, _("Cancel Changes")); //, _("Revert this document entry back to its former state.")
      if (count($this->order->line_items)) {
        $type      = ($this->order->trans_no > 0) ? $corder : $porder; //_('Check entered data and save document')
        $buttons[] = Form::submit('_action', Orders::PROCESS_ORDER, false, $type, 'default');
      }
      $view = new View('libraries/forms');
      $view->set('buttons', $buttons);
      $view->render();
      Display::div_end();
      Forms::end();
      Debtor::addEditDialog();
      Item::addEditDialog();
      Page::end(true);
    }
    protected function checkBranch() {
      if (Forms::isListUpdated('branch_id')) {
        // when branch is selected via external editor also customer can change
        $br                   = Sales_Branch::get(Input::post('branch_id'));
        $_POST['customer_id'] = $br['debtor_id'];
        $this->Ajax->activate('customer_id');
      }
    }
    protected function cancelItem() {
      Item_Line::start_focus('_stock_id_edit');
    }
    /**
     * @param $error
     */
    protected function exitError($error) {
      Event::warning($error);
      $this->Session->setGlobal('debtor', null);
      Page::footer_exit();
    }
    protected function Refresh() {
      $this->Ajax->activate('items_table');
    }
    protected function add() {
    }
    protected function after() {
      unset($_SESSION['order_no']);
    }
    /**
     * @param      $order_no
     * @param      $trans_type
     * @param bool $edit
     * @param bool $update
     *
     * @return void
     * @internal param string $trans_name
     */
    protected function pageComplete($order_no, $trans_type, $edit = false, $update = false) {
      switch ($trans_type) {
        case ST_SALESINVOICE:
          $trans_name = "Invoice";
          break;
        case ST_SALESQUOTE:
          $trans_name = "Quote";
          break;
        case ST_CUSTDELIVERY:
          $trans_name = "Delivery";
          break;
        case ST_SALESORDER:
        default:
          $trans_name = "Order";
      }
      $customer = new Debtor($this->Session->getGlobal('debtor', 0));
      $emails   = $customer->getEmailAddresses();
      Event::success(sprintf(_($trans_name . " # %d has been " . ($update ? "updated!" : "added!")), $order_no));
      Display::submenu_view(_("&View This " . $trans_name), $trans_type, $order_no);
      if ($edit) {
        Display::submenu_option(_("&Edit This " . $trans_name), "/sales/sales_order_entry.php?update=$order_no&type=" . $trans_type);
      }
      Display::submenu_print(_("&Print This " . $trans_name), $trans_type, $order_no, 'prtopt');
      Reporting::email_link($order_no, _("Email This $trans_name"), true, $trans_type, 'EmailLink', null, $emails, 1);
      if ($trans_type == ST_SALESORDER || $trans_type == ST_SALESQUOTE) {
        Display::submenu_print(_("Print Proforma Invoice"), ($trans_type == ST_SALESORDER ? ST_PROFORMA : ST_PROFORMAQ), $order_no, 'prtopt');
        Reporting::email_link($order_no, _("Email This Proforma Invoice"), true, ($trans_type == ST_SALESORDER ? ST_PROFORMA : ST_PROFORMAQ), 'EmailLink', null, $emails, 1);
      }
      if ($trans_type == ST_SALESORDER) {
        Display::submenu_option(_("Create PO from this order"), "/purchases/po_entry_items.php?NewOrder=Yes&UseOrder=" . $order_no . "'");
        Display::submenu_option(_("Dropship this order"), "/purchases/po_entry_items.php?NewOrder=Yes&UseOrder=" . $order_no . "&DRP=1' ");
        Display::submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber=$order_no");
        Display::submenu_option(_("Show outstanding &Orders"), "/sales/inquiry/sales_orders_view.php?OutstandingOnly=1");
        Display::submenu_option(_("Enter a New &Order"), "/sales/sales_order_entry.php?add=0&type=" . ST_SALESORDER);
        Display::submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESORDER);
      } elseif ($trans_type == ST_SALESQUOTE) {
        Display::submenu_option(_("Make &Sales Order Against This Quotation"), "/sales/sales_order_entry.php?" . Orders::QUOTE_TO_ORDER . "=$order_no");
        Display::submenu_option(_("Enter a New &Quotation"), "/sales/sales_order_entry.php?add=0&type=" . ST_SALESQUOTE);
        Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESQUOTE);
      } elseif ($trans_type == ST_CUSTDELIVERY) {
        Display::submenu_print(_("&Print Delivery Note"), ST_CUSTDELIVERY, $order_no, 'prtopt');
        Display::submenu_print(_("P&rint as Packing Slip"), ST_CUSTDELIVERY, $order_no, 'prtopt', null, 1);
        Display::note(GL_UI::view(ST_CUSTDELIVERY, $order_no, _("View the GL Journal Entries for this Dispatch")), 0, 1);
        Display::submenu_option(_("Make &Invoice Against This Delivery"), "/sales/customer_invoice.php?DeliveryNumber=$order_no");
        ((isset($_GET['Type']) && $_GET['Type'] == 1)) ? Display::submenu_option(_("Enter a New Template &Delivery"), "/sales/inquiry/sales_orders_view.php?DeliveryTemplates=Yes") : Display::submenu_option(_("Enter a &New Delivery"), "/sales/sales_order_entry.php?add=0&type=" . ST_CUSTDELIVERY);
      } elseif ($trans_type == ST_SALESINVOICE) {
        $sql    = "SELECT trans_type_from, trans_no_from FROM debtor_allocations WHERE trans_type_to=" . ST_SALESINVOICE . " AND trans_no_to=" . $this->DB->escape($order_no);
        $result = $this->DB->query($sql, "could not retrieve customer allocation");
        $row    = $this->DB->fetch($result);
        if ($row !== false) {
          Display::submenu_print(_("Print &Receipt"), $row['trans_type_from'], $row['trans_no_from'] . "-" . $row['trans_type_from'], 'prtopt');
        }
        Display::note(GL_UI::view(ST_SALESINVOICE, $order_no, _("View the GL &Journal Entries for this Invoice")), 0, 1);
        if ((isset($_GET['Type']) && $_GET['Type'] == 1)) {
          Display::submenu_option(_("Enter a &New Template Invoice"), "/sales/inquiry/sales_orders_view.php?InvoiceTemplates=Yes");
        } else {
          Display::submenu_option(_("Enter a &New Direct Invoice"), "/sales/sales_order_entry.php?add=0&type=10");
        }
        Display::link_params("/sales/customer_payments.php", _("Apply a customer payment"));
        if (isset($_GET[ADDED_DI]) && $this->Session->getGlobal('debtor') && $row == false) {
          echo "<div style='text-align:center;'><iframe style='margin:0 auto; border-width:0;' src='" . '/sales/customer_payments.php' . "?frame=1' width='80%' height='475' scrolling='auto' frameborder='0'></iframe> </div>";
        }
      }
      $this->JS->_setFocus('prtopt');
      //	UploadHandler::insert($order_no);
      Page::footer_exit();
    }
    /**
     * @internal param \Sales_Order $order
     * @return bool
     */
    protected function canProcess() {
      if (!Input::post('customer_id')) {
        Event::error(_("There is no customer selected."));
        $this->JS->_setFocus('customer_id');
        return false;
      }
      if (!Input::post('branch_id')) {
        Event::error(_("This customer has no branch defined."));
        $this->JS->_setFocus('branch_id');
        return false;
      }
      if (!Dates::isDate($_POST['OrderDate'])) {
        Event::error(_("The entered date is invalid."));
        $this->JS->_setFocus('OrderDate');
        return false;
      }
      if (!$this->order) {
        Event::error(_("You are not currently editing an order! (Using the browser back button after committing an order does not go back to editing)"));
        return false;
      }
      if ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE && !Dates::isDateInFiscalYear($_POST['OrderDate'])) {
        Event::error(_("The entered date is not in fiscal year"));
        $this->JS->_setFocus('OrderDate');
        return false;
      }
      if (count($this->order->line_items) == 0) {
        if (!empty($_POST['stock_id']) && $this->checkItemData()) {
          $this->order->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
          $_POST['_stock_id_edit'] = $_POST['stock_id'] = "";
        } else {
          Event::error(_("You must enter at least one non empty item line."));
          Item_Line::start_focus('_stock_id_edit');
          return false;
        }
      }
      if ($this->order->trans_type == ST_SALESORDER && $this->order->trans_no == 0 && !empty($_POST['cust_ref']) && $this->order->check_cust_ref($_POST['cust_ref'])
      ) {
        Event::error(_("This customer already has a purchase order with this number."));
        $this->JS->_setFocus('cust_ref');
        return false;
      }
      if (strlen($_POST['deliver_to']) <= 1) {
        Event::error(_("You must enter the person or company to whom delivery should be made to."));
        $this->JS->_setFocus('deliver_to');
        return false;
      }
      if (strlen($_POST['delivery_address']) <= 1) {
        Event::error(_("You should enter the street address in the box provided. Orders cannot be accepted without a valid street address."));
        $this->JS->_setFocus('delivery_address');
        return false;
      }
      if ($_POST['freight_cost'] == "") {
        $_POST['freight_cost'] = Num::priceFormat(0);
      }
      if (!Validation::post_num('freight_cost', 0)) {
        Event::error(_("The shipping cost entered is expected to be numeric."));
        $this->JS->_setFocus('freight_cost');
        return false;
      }
      if (!Dates::isDate($_POST['delivery_date'])) {
        if ($this->order->trans_type == ST_SALESQUOTE) {
          Event::error(_("The Valid date is invalid."));
        } else {
          Event::error(_("The delivery date is invalid."));
        }
        $this->JS->_setFocus('delivery_date');
        return false;
      }
      //if (Dates::isGreaterThan($this->order->document_date, $_POST['delivery_date'])) {
      if (Dates::isGreaterThan($_POST['OrderDate'], $_POST['delivery_date'])) {
        if ($this->order->trans_type == ST_SALESQUOTE) {
          Event::error(_("The requested valid date is before the date of the quotation."));
        } else {
          Event::error(_("The requested delivery date is before the date of the order."));
        }
        $this->JS->_setFocus('delivery_date');
        return false;
      }
      if ($this->order->trans_type == ST_SALESORDER && strlen($_POST['name']) < 1) {
        Event::error(_("You must enter a Person Ordering name."));
        $this->JS->_setFocus('name');
        return false;
      }
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        $this->JS->_setFocus('ref');
        return false;
      }
      if ($this->order->trans_no == 0 && !Ref::is_new($_POST['ref'], $this->order->trans_type)) {
        $_POST['ref'] = Ref::get_next($this->order->trans_type);
      }
      return true;
    }
    /**
     * @internal param $this ->order
     * @return bool
     */
    protected function checkItemData() {
      if (!$this->User->hasAccess(SA_SALESCREDIT) && (!Validation::post_num('qty', 0) || !Validation::post_num('Disc', 0, 100))) {
        Event::error(_("The item could not be updated because you are attempting to set the quantity ordered to less than 0, or the discount percent to more than 100."));
        $this->JS->_setFocus('qty');
        return false;
      } elseif (!Validation::post_num('price', 0)) {
        Event::error(_("Price for item must be entered and can not be less than 0"));
        $this->JS->_setFocus('price');
        return false;
      } elseif (!$this->User->hasAccess(SA_SALESCREDIT) && isset($_POST['LineNo']) && isset($this->order->line_items[$_POST['LineNo']]) && !Validation::post_num('qty', $this->order->line_items[$_POST['LineNo']]->qty_done)
      ) {
        $this->JS->_setFocus('qty');
        Event::error(_("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively."));
        return false;
      } // Joe Hunt added 2008-09-22 -------------------------
      elseif ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE && !DB_Company::get_pref('allow_negative_stock') && Item::is_inventory_item($_POST['stock_id'])) {
        $qoh = Item::get_qoh_on_date($_POST['stock_id'], $_POST['location'], $_POST['OrderDate']);
        if (Validation::input_num('qty') > $qoh) {
          $stock = Item::get($_POST['stock_id']);
          Event::error(_("The delivery cannot be processed because there is an insufficient quantity for item:") . " " . $stock['stock_id'] . " - " . $stock['description'] . " - " . _("Quantity On Hand") . " = " . Num::format($qoh, Item::qty_dec($_POST['stock_id'])));
          return false;
        }
        return true;
      }
      return true;
    }
    /**
     * @param $type
     * @param $trans_no
     *
     * @return \Purch_Order|\Sales_Order
     */
    protected function createOrder($type, $trans_no) {
      if (isset($_GET[Orders::QUOTE_TO_ORDER])) {
        $this->order    = new Sales_Order(ST_SALESQUOTE, array($trans_no));
        $doc            = clone($this->order);
        $doc->source_no = $trans_no;
        $this->order->finish();
        $doc->convertToOrder();
      } elseif (isset($_GET[Orders::CLONE_ORDER])) {
        $trans_no           = $_GET[Orders::CLONE_ORDER];
        $doc                = new Sales_Order(ST_SALESORDER, array($trans_no));
        $doc->trans_no      = 0;
        $doc->trans_type    = ST_SALESORDER;
        $doc->reference     = Ref::get_next($doc->trans_type);
        $doc->document_date = $doc->due_date = Dates::newDocDate();
        foreach ($doc->line_items as $line) {
          $line->qty_done = $line->qty_dispatched = 0;
        }
      } elseif ($type != ST_SALESORDER && $type != ST_SALESQUOTE && $trans_no != 0) { // this is template
        $doc                = new Sales_Order(ST_SALESORDER, array($trans_no));
        $doc->trans_type    = $type;
        $doc->trans_no      = 0;
        $doc->document_date = Dates::newDocDate();
        if ($type == ST_SALESINVOICE) {
          $doc->due_date = Sales_Order::get_invoice_duedate($doc->customer_id, $doc->document_date);
          $doc->pos      = $this->User->_pos();
          $pos           = Sales_Point::get($doc->pos);
          $doc->pos      = -1;
        } else {
          $doc->due_date = $doc->document_date;
        }
        $doc->reference = Ref::get_next($doc->trans_type);
        foreach ($doc->line_items as $line) {
          $doc->line_items[$line]->qty_done = 0;
        }
      } else {
        $doc = new Sales_Order($type, array($trans_no));
      }
      $this->type = $type;
      return Sales_Order::copyToPost($doc);
    }
    protected function removed() {
      if ($_GET['type'] == ST_SALESQUOTE) {
        Event::notice(_("This sales quotation has been deleted as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Quotation"), "/sales/sales_order_entry.php?add=0type=" . ST_SALESQUOTE);
        Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESQUOTE);
      } else {
        Event::notice(_("This sales order has been deleted as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Order"), "/sales/sales_order_entry.php?add=0&type=" . $_GET['type']);
        Display::submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.vphp?type=" . ST_SALESORDER);
      }
      Page::footer_exit();
    }
    /**
     * @return mixed
     */
    protected function processOrder() {
      if (!$this->canProcess($this->order)) {
        return;
      }
      Sales_Order::copyFromPost($this->order);
      $modified   = ($this->order->trans_no != 0);
      $so_type    = $this->order->so_type;
      $trans_type = $this->order->trans_type;
      Dates::newDocDate($this->order->document_date);
      $this->Session->setGlobal('debtor', $this->order->customer_id);
      $this->order->write(1);
      $jobsboard_order = clone ($this->order);
      $trans_no        = $jobsboard_order->trans_no = key($this->order->trans_no);
      if (Errors::getSeverity() == -1) { // abort on failure or error messages are lost
        $this->Ajax->activate('_page_body');
        Page::footer_exit();
      }
      $this->order->finish();
      if ($trans_type == ST_SALESORDER) {
        $jb = new \Modules\Jobsboard([]);
        $jb->addjob($jobsboard_order);
      }
      $this->pageComplete($trans_no, $trans_type, true, $modified);
    }
    protected function cancelChanges() {
      $type     = $this->order->trans_type;
      $order_no = (is_array($this->order->trans_no)) ? key($this->order->trans_no) : $this->order->trans_no;
      Orders::Session_delete($_POST['order_id']);
      $this->order = $this->createOrder($type, $order_no);
    }
    /**
     * @return mixed
     */
    protected function deleteOrder() {
      if (!$this->User->hasAccess(SS_SETUP)) {
        Event::error('You don\'t have access to delete orders');
        return;
      }
      if ($this->order->trans_type == ST_CUSTDELIVERY) {
        Event::notice(_("Direct delivery has been cancelled as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Delivery"), "/sales/sales_order_entry.php?NewDelivery=1");
      } elseif ($this->order->trans_type == ST_SALESINVOICE) {
        Event::notice(_("Direct invoice has been cancelled as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Invoice"), "/sales/sales_order_entry.php?NewInvoice=1");
      } else {
        if ($this->order->trans_no != 0) {
          if ($this->order->trans_type == ST_SALESORDER && $this->order->has_deliveries()) {
            Event::error(_("This order cannot be cancelled because some of it has already been invoiced or dispatched. However, the line item quantities may be modified."));
          } else {
            $trans_no   = key($this->order->trans_no);
            $trans_type = $this->order->trans_type;
            if (!isset($_GET[REMOVED_ID])) {
              $this->order->delete($trans_no, $trans_type);
              $jb = new \Modules\Jobsboard([]);
              $jb->removejob($trans_no);
              Event::notice(_("Sales order has been cancelled as requested."), 1);
            }
          }
        } else {
          Display::meta_forward('/index.php', 'application=sales');
        }
      }
      $this->Ajax->activate('_page_body');
      $this->order->finish();
      Display::submenu_option(_("Show outstanding &Orders"), "/sales/inquiry/sales_orders_view.php?OutstandingOnly=1");
      Display::submenu_option(_("Enter a New &Order"), "/sales/sales_order_entry.php?add=0&type=" . ST_SALESORDER);
      Display::submenu_option(_("Select A Different Order to edit"), "/sales/inquiry/sales_orders_view.php?type=" . ST_SALESORDER);
      Page::footer_exit();
    }
    protected function updateItem() {
      if ($this->checkItemData($this->order)) {
        $this->order->update_order_item($_POST['LineNo'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
      }
      Item_Line::start_focus('_stock_id_edit');
    }
    protected function discountAll() {
      if (!is_numeric($_POST['_discountAll'])) {
        Event::error(_("Discount must be a number"));
      } elseif ($_POST['_discountAll'] < 0 || $_POST['_discountAll'] > 100) {
        Event::error(_("Discount percentage must be between 0-100"));
      } else {
        $this->order->discount_all($_POST['_discountAll'] / 100);
      }
      $this->Ajax->activate('_page_body');
    }
    /**
     * @return mixed
     */
    protected function addLine() {
      if (!$this->checkItemData($this->order)) {
        return;
      }
      $this->order->add_line($_POST['stock_id'], Validation::input_num('qty'), Validation::input_num('price'), Validation::input_num('Disc') / 100, $_POST['description']);
      $_POST['_stock_id_edit'] = $_POST['stock_id'] = "";
      Item_Line::start_focus('_stock_id_edit');
    }
    /**
     * @return mixed
     */
    protected function checkRowDelete() {
      $line_id = $this->getActionID(Orders::DELETE_LINE);
      if ($line_id === -1) {
        return;
      }
      if ($this->order->some_already_delivered($line_id) == 0) {
        $this->order->remove_from_order($line_id);
      } else {
        Event::error(_("This item cannot be deleted because some of it has already been delivered."));
      }
      Item_Line::start_focus('_stock_id_edit');
    }
    /**
     * @return bool|mixed|void
     */
    protected function runValidation() {
      if (!is_object($this->order)) {
        $this->exitError('No current order to edit.');
      }
      Validation::check(Validation::STOCK_ITEMS, _("There are no inventory items defined in the system."));
      Validation::check(Validation::BRANCHES_ACTIVE, _("There are no customers, or there are no customers with branches. Please define customers and customer branches."));
    }
    protected function setLineOrder() {
      $line_map = Input::getPost('lineMap', array());
      $this->order->setLineOrder($line_map);
      $data = ['lineMap'=> $this->order, 'status'=> true];
      $this->JS->_renderJSON($data);
    }
    protected function setSecurity() {
      // first check is this is not start page call
      $this->security = $this->typeSecurity[$this->type];
      $value          = (!$this->order) ? : $this->order->trans_type;
      // then check Session value
      if (isset($this->typeSecurity[$value])) {
        $this->security = $this->typeSecurity[$value];
      }
    }
  }

  new SalesOrder();
