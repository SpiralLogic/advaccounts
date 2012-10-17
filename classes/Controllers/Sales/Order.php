<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Sales;

  use ADV\App\Controller\Action;
  use Sales_Point;
  use Sales_Order;
  use DB_Company;
  use ADV\Core\Session;
  use GL_UI;
  use Item_Line;
  use Sales_Branch;
  use ADV\App\Ref;
  use ADV\App\Validation;
  use ADV\App\Reporting;
  use ADV\App\Display;
  use ADV\App\Forms;
  use ADV\App\SysTypes;
  use ADV\App\Orders;
  use ADV\App\Form\Form;
  use ADV\Core\Table;
  use ADV\App\Item\Item;
  use ADV\App\UI;
  use ADV\App\Debtor\Debtor;
  use ADV\Core\View;
  use ADV\Core\Errors;
  use ADV\Core\Num;
  use ADV\App\Dates;
  use ADV\Core\Event;
  use ADV\Core\Input\Input;

  /**

   */
  class Order extends Action {
    protected $addTitles
      = array(
        ST_SALESQUOTE  => "New Sales Quotation Entry", //
        ST_SALESINVOICE=> "Direct Sales Invoice", //
        ST_CUSTDELIVERY=> "Direct Sales Delivery", //
        ST_SALESORDER  => "New Sales Order Entry"
      );
    protected $modifyTitles
      = array(
        ST_SALESQUOTE => "Modifying Sales Quotation # ", //
        ST_SALESORDER => "Modifying Sales Order # "
      );
    protected $typeSecurity
      = array(
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
      $this->JS->openWindow(900, 500);
      if (REQUEST_GET) {
        if ($this->Input->get('debtor_id', Input::NUMERIC)) {
          $this->action       = Orders::CANCEL_CHANGES;
          $_POST['debtor_id'] = $_GET['debtor_id'];
          $this->Ajax->activate('debtor_id');
        }
        $this->type = $this->Input->get('type', Input::NUMERIC, ST_SALESORDER);
        $type_id    = 0;
        $this->setTitle("New Sales Order Entry");
        if ($this->Input->get(Orders::ADD, Input::NUMERIC, false) !== false) {
          $this->setTitle($this->addTitles[$this->type]);
        } elseif ($this->Input->get(Orders::UPDATE, Input::NUMERIC, -1) > 0) {
          $this->setTitle($this->modifyTitles[$this->type] . $_GET[Orders::UPDATE]);
          $type_id = $this->Input->get(Orders::UPDATE);
        } elseif ($this->Input->get(Orders::QUOTE_TO_ORDER)) {
          $this->type = Orders::QUOTE_TO_ORDER;
          $this->setTitle("New Order from Quote");
          $type_id = $_GET[Orders::QUOTE_TO_ORDER];
        } elseif ($this->Input->get(Orders::CLONE_ORDER)) {
          $this->type = Orders::CLONE_ORDER;
          $this->setTitle("New order from previous order");
          $type_id = $this->Input->get(Orders::CLONE_ORDER);
        }
        $this->order = $this->createOrder($this->type, $type_id);
      }
      $this->setSecurity();
    }
    protected function index() {
      $this->Page->init($this->title, $this->security);
      $this->checkBranch();
      if (isset($_GET[REMOVED])) {
        $this->removed();
      }
      $this->checkRowDelete();
      $this->runAction();
      $this->runValidation();
      $type_name       = SysTypes::$names[$this->order->trans_type];
      $type_name_short = SysTypes::$short_names[$this->order->trans_type];
      $idate           = _("$type_name_short Date:");
      $orderitems      = _("$type_name Items");
      $deleteorder     = _("Delete $type_name_short");
      $corder          = _("Commit $type_name_short Changes");
      $porder          = _("Place $type_name_short");
      Forms::start();
      $this->order->header($idate);
      Forms::hidden('order_id');
      Table::start('tablesstyle center width90 pad10');
      echo "<tr><td>";
      $edit_line = $this->getActionId(Orders::EDIT_LINE);
      $this->order->summary($orderitems, $edit_line);
      echo "</td></tr><tr><td>";
      $this->order->display_delivery_details();
      echo "</td></tr>";
      Table::end(1);
      $this->Ajax->start_div('controls', 'items_table');
      $buttons = new Form();
      if ($this->order->trans_no > 0 && $this->User->hasAccess(SA_VOIDTRANSACTION) && !($this->order->trans_type == ST_SALESORDER && $this->order->has_deliveries())) {
        $buttons->submit(Orders::DELETE_ORDER, $deleteorder)->preIcon(ICON_DELETE)->type('danger')->setWarning('You are about to void this Document.\nDo you want to continue?');
      }
      $buttons->submit(Orders::CANCEL_CHANGES, _("Cancel Changes"))->preIcon(ICON_CANCEL)->type('warning');
      if (count($this->order->line_items)) {
        $type = ($this->order->trans_no > 0) ? $corder : $porder; //_('Check entered data and save document')
        $buttons->submit(Orders::PROCESS_ORDER, $type)->type('success')->preIcon(ICON_SUBMIT);
      }
      $view = new View('libraries/forms');
      $view->set('buttons', $buttons);
      $view->render();
      $this->Ajax->end_div();
      Forms::end();
      Debtor::addEditDialog();
      Item::addEditDialog();
      UI::emailDialogue(CT_CUSTOMER);
      $this->Page->end_page(true);
    }
    protected function checkBranch() {
      if (Forms::isListUpdated('branch_id')) {
        // when branch is selected via external editor also customer can change
        $br                 = Sales_Branch::get($this->Input->post('branch_id'));
        $_POST['debtor_id'] = $br['debtor_id'];
        $this->Ajax->activate('debtor_id');
      }
    }
    protected function cancelItem() {
      Item_Line::start_focus('stock_id');
    }
    /**
     * @param $error
     */
    protected function exitError($error) {
      Event::warning($error);
      $this->Session->setGlobal('debtor_id', null);
      $this->Page->endExit();
    }
    protected function Refresh() {
      $this->Ajax->activate('items_table');
    }
    protected function add() {
    }
    protected function after() {
      unset($this->Session['order_no']);
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
      $edit_trans = '';
      switch ($trans_type) {
        case ST_SALESINVOICE:
          $trans_name = "Invoice";
          break;
        case ST_SALESQUOTE:
          $trans_name = "Quote";
          $edit_trans = "/sales/order?update=$order_no&type=" . $trans_type;
          break;
        case ST_CUSTDELIVERY:
          $trans_name = "Delivery";
          break;
        case ST_SALESORDER:
        default:
          $trans_name = "Order";
          $edit_trans = "/sales/order?update=$order_no&type=" . $trans_type;
      }
      $new_trans = "/sales/order?add=0&type=" . $trans_type;
      $customer  = new Debtor($this->Session->getGlobal('debtor_id', 0));
      Event::success(sprintf(_($trans_name . " # %d has been " . ($update ? "updated!" : "added!")), $order_no));
      GL_UI::viewTrans($trans_type, $order_no, _("&View This " . $trans_name), false, 'menu_option button');
      if ($edit) {
        Display::submenu_option(_("&Edit This " . $trans_name), $edit_trans);
      }
      echo "<br><div class='center'>" . Display::submenu_print(_("&Print This " . $trans_name), $trans_type, $order_no, 'prtopt') . '<br><br>';
      echo  Reporting::emailDialogue($customer->id, $trans_type, $order_no) . '<br><br>';
      if ($trans_type == ST_SALESORDER || $trans_type == ST_SALESQUOTE) {
        echo    Display::submenu_print(
          _("Print Proforma Invoice"),
          ($trans_type == ST_SALESORDER ? ST_PROFORMA : ST_PROFORMAQ),
          $order_no,
          'prtopt'
        ) . '<br><br>';
      }
      echo "</div>";
      if ($trans_type == ST_SALESORDER) {
        Display::submenu_option(_("Create PO from this order"), "/purchases/order?NewOrder=Yes&UseOrder=" . $order_no . "'");
        Display::submenu_option(_("Dropship this order"), "/purchases/order?NewOrder=Yes&UseOrder=" . $order_no . "&DRP=1' ");
        Display::submenu_option(_("Make &Delivery Against This Order"), "/sales/customer_delivery.php?OrderNumber=$order_no");
        Display::submenu_option(_("Show outstanding &Orders"), "/sales/search/orders?OutstandingOnly=1");
        Display::submenu_option(_("Enter a New &Order"), $new_trans);
        Display::submenu_option(_("Select A Different Order to edit"), "/sales/search/orders?type=" . ST_SALESORDER);
      } elseif ($trans_type == ST_SALESQUOTE) {
        Display::submenu_option(_("Make &Sales Order Against This Quotation"), "/sales/order?" . Orders::QUOTE_TO_ORDER . "=$order_no");
        Display::submenu_option(_("Enter a New &Quotation"), $new_trans);
        Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/search/orders?type=" . ST_SALESQUOTE);
      } elseif ($trans_type == ST_CUSTDELIVERY) {
        Display::submenu_print(_("&Print Delivery Note"), ST_CUSTDELIVERY, $order_no, 'prtopt');
        Display::submenu_print(_("P&rint as Packing Slip"), ST_CUSTDELIVERY, $order_no, 'prtopt', null, 1);
        GL_UI::view(ST_CUSTDELIVERY, $order_no, _("View the GL Journal Entries for this Dispatch"));
        Display::submenu_option(_("Make &Invoice Against This Delivery"), "/sales/customer_invoice.php?DeliveryNumber=$order_no");
        ((isset($_GET['Type']) && $_GET['Type'] == 1)) ? Display::submenu_option(_("Enter a New Template &Delivery"), "/sales/search/orders?DeliveryTemplates=Yes") :
          Display::submenu_option(_("Enter a &New Delivery"), $new_trans);
      } elseif ($trans_type == ST_SALESINVOICE) {
        $sql    = "SELECT trans_type_from, trans_no_from FROM debtor_allocations WHERE trans_type_to=" . ST_SALESINVOICE . " AND trans_no_to=" . $this->DB->escape(
          $order_no
        );
        $result = $this->DB->query($sql, "could not retrieve customer allocation");
        $row    = $this->DB->fetch($result);
        if ($row !== false) {
          Display::submenu_print(_("Print &Receipt"), $row['trans_type_from'], $row['trans_no_from'] . "-" . $row['trans_type_from'], 'prtopt');
        }
        GL_UI::view(ST_SALESINVOICE, $order_no, _("View the GL &Journal Entries for this Invoice"));
        if ((isset($_GET['Type']) && $_GET['Type'] == 1)) {
          Display::submenu_option(_("Enter a &New Template Invoice"), "/sales/search/orders?InvoiceTemplates=Yes");
        } else {
          Display::submenu_option(_("Enter a &New Direct Invoice"), $new_trans);
        }
        Display::link_params("/sales/payment", _("Apply a customer payment"));
        if (isset($_GET[ADDED_DI]) && $this->Session->getGlobal('debtor_id') && $row == false) {
          echo "<div style='text-align:center;'><iframe style='margin:0 auto; border-width:0;' src='" . '/sales/payment' . "?frame=1' width='80%' height='475' scrolling='auto' frameborder='0'></iframe> </div>";
        }
      }
      $this->JS->setFocus('prtopt');
      $this->Ajax->activate('_page_body', $new_trans, $edit_trans, $this->addTitles[$trans_type]);
      //	UploadHandler::insert($order_no);
      $this->Page->endExit();
    }
    /**
     * @internal param \Sales_Order $order
     * @return bool
     */
    protected function canProcess() {
      if (!$this->Input->post('debtor_id')) {
        Event::error(_("There is no customer selected."));
        $this->JS->setFocus('debtor_id');
        return false;
      }
      if (!$this->Input->post('branch_id')) {
        Event::error(_("This customer has no branch defined."));
        $this->JS->setFocus('branch_id');
        return false;
      }
      if (!Dates::_isDate($_POST['OrderDate'])) {
        Event::error(_("The entered date is invalid."));
        $this->JS->setFocus('OrderDate');
        return false;
      }
      if (!$this->order) {
        Event::error(_("You are not currently editing an order! (Using the browser back button after committing an order does not go back to editing)"));
        return false;
      }
      if ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE && !Dates::_isDateInFiscalYear($_POST['OrderDate'])) {
        Event::error(_("The entered date is not in fiscal year"));
        $this->JS->setFocus('OrderDate');
        return false;
      }
      if (count($this->order->line_items) == 0) {
        if (!empty($_POST['stock_id']) && $this->checkItemData()) {
          $this->order->add_line(
            $_POST['stock_id'],
            Validation::input_num('qty'),
            Validation::input_num('price'),
            Validation::input_num('Disc') / 100,
            $_POST['description']
          );
          $_POST['_stock_id_edit'] = $_POST['stock_id'] = "";
        } else {
          Event::error(_("You must enter at least one non empty item line."));
          Item_Line::start_focus('stock_id');
          return false;
        }
      }
      if ($this->order->trans_type == ST_SALESORDER && $this->order->trans_no == 0 && !empty($_POST['cust_ref']) && $this->order->check_cust_ref($_POST['cust_ref'])
      ) {
        Event::error(_("This customer already has a purchase order with this number."));
        $this->JS->setFocus('cust_ref');
        return false;
      }
      if (strlen($_POST['deliver_to']) <= 1) {
        Event::error(_("You must enter the person or company to whom delivery should be made to."));
        $this->JS->setFocus('deliver_to');
        return false;
      }
      if (strlen($_POST['delivery_address']) <= 1) {
        Event::error(_("You should enter the street address in the box provided. Orders cannot be accepted without a valid street address."));
        $this->JS->setFocus('delivery_address');
        return false;
      }
      if ($_POST['freight_cost'] == "") {
        $_POST['freight_cost'] = Num::_priceFormat(0);
      }
      if (!Validation::post_num('freight_cost', 0)) {
        Event::error(_("The shipping cost entered is expected to be numeric."));
        $this->JS->setFocus('freight_cost');
        return false;
      }
      if (!Dates::_isDate($_POST['delivery_date'])) {
        if ($this->order->trans_type == ST_SALESQUOTE) {
          Event::error(_("The Valid date is invalid."));
        } else {
          Event::error(_("The delivery date is invalid."));
        }
        $this->JS->setFocus('delivery_date');
        return false;
      }
      //if (Dates::_isGreaterThan($this->order->document_date, $_POST['delivery_date'])) {
      if (Dates::_differenceBetween($_POST['delivery_date'], $_POST['OrderDate']) < 0) {
        if ($this->order->trans_type == ST_SALESQUOTE) {
          Event::error(_("The requested valid date is before the date of the quotation."));
        } else {
          Event::error(_("The requested delivery date is before the date of the order."));
        }
        $this->JS->setFocus('delivery_date');
        return false;
      }
      if ($this->order->trans_type == ST_SALESORDER && strlen($_POST['name']) < 1) {
        Event::error(_("You must enter a Person Ordering name."));
        $this->JS->setFocus('name');
        return false;
      }
      $result = $this->order->trans_type == ST_SALESORDER && strlen($_POST['cust_ref']) < 1;
      if ($result && $this->order->order_id !== $this->Session->getFlash('SalesOrder')) {
        $this->Session->setFlash('SalesOrder', $this->order->order_id);
        Event::warning('Are you sure you want to commit this order without a purchase order number?');
        $this->JS->setFocus('cust_ref');
        return false;
      }
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        $this->JS->setFocus('ref');
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
        $this->JS->setFocus('qty');
        return false;
      } elseif (!Validation::post_num('price', 0)) {
        Event::error(_("Price for item must be entered and can not be less than 0"));
        $this->JS->setFocus('price');
        return false;
      } elseif (!$this->User->hasAccess(SA_SALESCREDIT) && isset($_POST['LineNo']) && isset($this->order->line_items[$_POST['LineNo']]) && !Validation::post_num(
        'qty',
        $this->order->line_items[$_POST['LineNo']]->qty_done
      )
      ) {
        $this->JS->setFocus('qty');
        Event::error(
          _("You attempting to make the quantity ordered a quantity less than has already been delivered. The quantity delivered cannot be modified retrospectively.")
        );
        return false;
      } // Joe Hunt added 2008-09-22 -------------------------
      elseif ($this->order->trans_type != ST_SALESORDER && $this->order->trans_type != ST_SALESQUOTE && !DB_Company::get_pref(
        'allow_negative_stock'
      ) && Item::is_inventory_item($_POST['stock_id'])
      ) {
        $qoh = Item::get_qoh_on_date($_POST['stock_id'], $_POST['location'], $_POST['OrderDate']);
        if (Validation::input_num('qty') > $qoh) {
          $stock = Item::get($_POST['stock_id']);
          Event::error(
            _(
              "The delivery cannot be processed because there is an insufficient quantity for item:"
            ) . " " . $stock['stock_id'] . " - " . $stock['description'] . " - " . _("Quantity On Hand") . " = " . Num::_format($qoh, Item::qty_dec($_POST['stock_id']))
          );
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
        $doc->document_date = $doc->due_date = Dates::_newDocDate();
        foreach ($doc->line_items as $line) {
          $line->qty_done = $line->qty_dispatched = 0;
        }
      } elseif ($type != ST_SALESORDER && $type != ST_SALESQUOTE && $trans_no != 0) { // this is template
        $doc                = new Sales_Order(ST_SALESORDER, array($trans_no));
        $doc->trans_type    = $type;
        $doc->trans_no      = 0;
        $doc->document_date = Dates::_newDocDate();
        if ($type == ST_SALESINVOICE) {
          $doc->due_date = Sales_Order::get_invoice_duedate($doc->debtor_id, $doc->document_date);
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
        Display::submenu_option(_("Enter a New Sales Quotation"), "/sales/order?add=0type=" . ST_SALESQUOTE);
        Display::submenu_option(_("Select A Different &Quotation to edit"), "/sales/search/orders?type=" . ST_SALESQUOTE);
      } else {
        Event::notice(_("This sales order has been deleted as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Order"), "/sales/order?add=0&type=" . $_GET['type']);
        Display::submenu_option(_("Select A Different Order to edit"), "/sales/search/orders.vphp?type=" . ST_SALESORDER);
      }
      $this->Page->endExit();
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
      $trans_type = $this->order->trans_type;
      Dates::_newDocDate($this->order->document_date);
      $this->Session->setGlobal('debtor_id', $this->order->debtor_id);
      $this->order->write(1);
      $jobsboard_order = clone ($this->order);
      $trans_no        = $jobsboard_order->trans_no = key($this->order->trans_no);
      if (Errors::getSeverity() == -1) { // abort on failure or error messages are lost
        $this->Ajax->activate('_page_body');
        $this->Page->endExit();
      }
      $this->order->finish();
      if ($trans_type == ST_SALESORDER) {
        $jb = new \Modules\Jobsboard\Jobsboard([]);
        $jb->addjob($jobsboard_order);
      }
      $this->pageComplete($trans_no, $trans_type, true, $modified);
    }
    protected function cancelChanges() {
      $type     = $this->order->trans_type;
      $order_no = (is_array($this->order->trans_no)) ? key($this->order->trans_no) : $this->order->trans_no;
      Orders::Session_delete($_POST['order_id']);
      $this->order = $this->createOrder($type, $order_no);
      $this->JS->setfocus('customer');
    }
    /**
     * @return mixed
     */
    protected function deleteOrder() {
      if (!$this->User->hasAccess(SA_VOIDTRANSACTION)) {
        Event::error('You don\'t have access to delete orders');
        return;
      }
      if ($this->order->trans_type == ST_CUSTDELIVERY) {
        Event::notice(_("Direct delivery has been cancelled as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Delivery"), "/sales/order?NewDelivery=1");
      } elseif ($this->order->trans_type == ST_SALESINVOICE) {
        Event::notice(_("Direct invoice has been cancelled as requested."), 1);
        Display::submenu_option(_("Enter a New Sales Invoice"), "/sales/order?NewInvoice=1");
      } else {
        if ($this->order->trans_no != 0) {
          if ($this->order->trans_type == ST_SALESORDER && $this->order->has_deliveries()) {
            Event::error(
              _("This order cannot be cancelled because some of it has already been invoiced or dispatched. However, the line item quantities may be modified.")
            );
          } else {
            $trans_no   = key($this->order->trans_no);
            $trans_type = $this->order->trans_type;
            $this->order->delete($trans_no, $trans_type);
            if ($trans_type == ST_SALESORDER) {
              $jb = new \Modules\Jobsboard\Jobsboard([]);
              $jb->removejob($trans_no);
              Event::notice(_("Sales order has been cancelled."), 1);
            } else {
              Event::notice(_("Sales quote has been cancelled."), 1);
            }
          }
        } else {
          return;
        }
      }
      $this->Ajax->activate('_page_body');
      $this->order->finish();
      Display::submenu_option(_("Show outstanding &Orders"), "/sales/search/orders?OutstandingOnly=1");
      Display::submenu_option(_("Enter a New &Order"), "/sales/order?add=0&type=" . ST_SALESORDER);
      Display::submenu_option(_("Select A Different Order to edit"), "/sales/search/orders?type=" . ST_SALESORDER);
      $this->Page->endExit();
    }
    protected function updateItem() {
      if ($this->checkItemData($this->order)) {
        $this->order->update_order_item(
          $_POST['LineNo'],
          Validation::input_num('qty'),
          Validation::input_num('price'),
          Validation::input_num('Disc') / 100,
          $_POST['description']
        );
      }
      Item_Line::start_focus('stock_id');
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
      Item_Line::start_focus('stock_id');
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
      Item_Line::start_focus('stock_id');
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
      $line_map = $this->Input->getPost('lineMap', []);
      $this->order->setLineOrder($line_map);
      $data = ['lineMap'=> $this->order, 'status'=> true];
      $this->JS->renderJSON($data);
    }
    protected function setSecurity() {
      if ($this->order->trans_type) {
        $this->type = $this->order->trans_type;
      }
      // first check is this is not start page call
      $this->security = $this->typeSecurity[$this->type];
      $value          = (!$this->order) ? : $this->order->trans_type;
      // then check Session value
      if (isset($this->typeSecurity[$value])) {
        $this->security = $this->typeSecurity[$value];
      }
    }
  }

