<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  /** @noinspection PhpIncludeInspection */
  //
  class PurchaseOrder extends \ADV\App\Controller\Base
  {

    protected $iframe='';
    /** @var Purch_Order */
    protected $order = null;
    protected function before() {
      JS::openWindow(900, 500);
      $this->order = Orders::session_get() ? : null;
      if (isset($_POST[Orders::CANCEL_CHANGES])) {
        $order_no = $this->order->order_no;
        Orders::session_delete($_POST['order_id']);
        $this->order = $this->createOrder($order_no);
      }
      $id = Forms::findPostPrefix(MODE_DELETE);
      if ($id != -1 && $this->order) {
        if ($this->order->some_already_received($id) == 0) {
          $this->order->remove_from_order($id);
          unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
        } else {
          Event::error(_("This item cannot be deleted because some of it has already been received."));
        }
        Item_Line::start_focus('_stock_id_edit');
      }
      if (isset($_POST[COMMIT])) {
        Purch_Order::copyFromPost($this->order);
        if ($this->canCommit($this->order)) {
          $order_no = ($this->order->order_no == 0) ? $this->order->add() : $this->order->update();
          if ($order_no) {
            Dates::newDocDate($this->order->orig_order_date);
            Orders::session_delete($_POST['order_id']);
            $this->pageComplete($order_no);
          }
        }
      }
      if (isset($_POST[UPDATE_ITEM]) && $this->checkData()) {
        if ($this->order->line_items[$_POST['line_no']]->qty_inv > Validation::input_num('qty') || $this->order->line_items[$_POST['line_no']]->qty_received > Validation::input_num('qty')) {
          Event::error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received. This is prohibited.") . "<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
          JS::setFocus('qty');
        } else {
          $this->order->update_order_item($_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), $_POST['req_del_date'], $_POST['description'], $_POST['discount'] / 100);
          unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
          Item_Line::start_focus('_stock_id_edit');
        }
      }
      if (isset($_POST[ADD_ITEM])) {
        $allow_update = $this->checkData();
        if ($allow_update == true) {
          if ($allow_update == true) {
            $sql
                    = "SELECT long_description as description , units, mb_flag
  				FROM stock_master WHERE stock_id = " . DB::escape($_POST['stock_id']);
            $result = DB::query($sql, "The stock details for " . $_POST['stock_id'] . " could not be retrieved");
            if (DB::numRows($result) == 0) {
              $allow_update = false;
            }
            if ($allow_update) {
              $myrow = DB::fetch($result);
              $this->order->add_to_order($_POST['line_no'], $_POST['stock_id'], Validation::input_num('qty'), $_POST['description'], Validation::input_num('price'), $myrow["units"], $_POST['req_del_date'], 0, 0, $_POST['discount'] / 100);
              unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
              $_POST['stock_id'] = "";
            } else {
              Event::error(_("The selected item does not exist or it is a kit part and therefore cannot be purchased."));
            }
          } /* end of if not already on the order and allow input was true*/
        }
        Item_Line::start_focus('_stock_id_edit');
      }
      if (isset($_POST[Orders::CANCEL])) {
        if (!$this->order) {
          Display::meta_forward('/index.php', 'application=Purchases');
        }
        //need to check that not already dispatched or invoiced by the supplier
        if (($this->order->order_no != 0) && $this->order->any_already_received() == 1) {
          Event::error(_("This order cannot be cancelled because some of it has already been received.") . "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
        } else {
          Orders::session_delete($this->order->order_id);
          if ($this->order->order_no != 0) {
            $this->order->delete();
          } else {
            Display::meta_forward('/index.php', 'application=Purchases');
          }
          Orders::session_delete($this->order->order_id);
          Event::notice(_("This purchase order has been cancelled."));
          Display::link_params("/purchases/po_entry_items.php", _("Enter a new purchase order"), "NewOrder=Yes");
          Page::footer_exit();
        }
      }
      if (isset($_POST[CANCEL])) {
        unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
      }
      $this->iframe = "<div class='center'><iframe src='" . e('/purchases/inquiry/po_search_completed.php?' . LOC_NOT_FAXED_YET . '=1&frame=1') . "' class='width70' style='height:300px' ></iframe></div>";
      if (!Input::get(Orders::MODIFY_ORDER)) {
        $this->order = $this->createOrder($_GET[Orders::MODIFY_ORDER]);
      } elseif (isset($_POST[CANCEL]) || isset($_POST[UPDATE_ITEM])) {
        Item_Line::start_focus('_stock_id_edit');
      } elseif (isset($_GET[Orders::NEW_ORDER]) || !isset($this->order)) {
        $this->order = $this->createOrder();
        if (Input::get('UseOrder') && !count($this->order->line_items)) {
        }
      }
    }
    protected function index() {
      if (isset($_GET[Orders::MODIFY_ORDER])) {
        Page::start(_($help_context = "Modify Purchase Order #") . $_GET[Orders::MODIFY_ORDER], SA_PURCHASEORDER);
      } else {
        Page::start(_($help_context = "Purchase Order Entry"), SA_PURCHASEORDER);
      }
      Forms::start();
      echo "<br>";
      Forms::hidden('order_id');          echo $this->iframe;

      $this->order->header();

      $this->order->display_items();
      Table::start('tablestyle2');
      Forms::textareaRow(_("Memo:"), 'Comments', null, 70, 4);
      Table::end(1);
      Display::div_start('controls', 'items_table');
      if ($this->order->order_has_items()) {
        Forms::submitCenterBegin(Orders::CANCEL, _("Delete This Order"));
        Forms::submitCenterInsert(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
        if ($this->order->order_no) {
          Forms::submitCenterEnd(COMMIT, _("Update Order"), '', 'default');
        } else {
          Forms::submitCenterEnd(COMMIT, _("Place Order"), '', 'default');
        }
      } else {
        Forms::submitConfirm(Orders::CANCEL, _('You are about to void this Document.\nDo you want to continue?'));
        Forms::submitCenterBegin(Orders::CANCEL, _("Delete This Order"), true, false, ICON_DELETE);
        Forms::submitCenterInsert(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
      }
      Display::div_end();
      Forms::end();
      Item::addEditDialog();
      UI::emailDialogue(CT_SUPPLIER);
      if (isset($this->order->creditor_id)) {
        Creditor::addInfoDialog("td[name=\"supplier_name\"]", $this->order->supplier_details['creditor_id']);
      }
      Page::end(true);
    }
    protected function runValidation() {
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
      Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
    }
    /**
     * @param $order_no
     */
    function pageComplete($order_no) {
      Page::start(_($help_context = "Purchase Order Entry"), SA_PURCHASEORDER);

      $trans_type = ST_PURCHORDER;
      $supplier   = new Creditor(Session::getGlobal('creditor_id'));
      if (!isset($_GET['Updated'])) {
        Event::success(_("Purchase Order: " . Session::i()['history'][ST_PURCHORDER] . " has been entered"));
      } else {
        Event::success(_("Purchase Order: " . Session::i()['history'][ST_PURCHORDER] . " has been updated"));
      }
      $view                 = new View('purchases/purchase_order');
      $view['viewtrans']    = GL_UI::viewTrans($trans_type, $order_no, _("&View this order"), false, 'button');
      $view['printtrans']   = Reporting::print_doc_link($order_no, _("&Print This Order"), true, $trans_type);
      $view['modifytrans']  = Display::menu_button(BASE_URL . "purchases/po_entry_items.php?ModifyOrder=$order_no", _("&Edit This Order"));
      $view['emailtrans']   = Reporting::emailDialogue($supplier->id, ST_PURCHORDER, $order_no);
      $view['recievetrans'] = "/purchases/po_receive_items.php?PONumber=$order_no";
      $view['neworder']     = "/purchases/po_receive_items.php?NewOrder=yes";
      $view['neworder']     = "/purchases/inquiry/po_search.php";
      $view->render();
      Ajax::activate('_page_body');
      Page::footer_exit();
    }
    /**
     * @param int $order_no
     *
     * @return \Purch_Order|\Sales_Order
     */
    function createOrder($order_no = 0) {
      $getUuseOrder = Input::get('UseOrder');
      if ($getUuseOrder) {
        if (isset(Orders::session_get($getUuseOrder)->line_items)) {
          $sales_order = Orders::session_get($_GET['UseOrder']);
        } else {
          $sales_order = new Sales_Order(ST_SALESORDER, array($_GET['UseOrder']));
        }
        $this->order = new Purch_Order($order_no);
        $stock       = $myrow = array();
        foreach ($sales_order->line_items as $line_item) {
          $stock[] = ' stock_id = ' . DB::escape($line_item->stock_id);
        }
        $sql    = "SELECT AVG(price),creditor_id,COUNT(creditor_id) FROM purch_data WHERE " . implode(' OR ', $stock) . ' GROUP BY creditor_id ORDER BY AVG(price)';
        $result = DB::query($sql);
        $row    = DB::fetch($result);
        $this->order->supplier_to_order($row['creditor_id']);
        foreach ($sales_order->line_items as $line_no => $line_item) {
          $this->order->add_to_order($line_no, $line_item->stock_id, $line_item->quantity, $line_item->description, 0, $line_item->units, Dates::addDays(Dates::today(), 10), 0, 0, 0);
        }
        if (isset($_GET[LOC_DROP_SHIP])) {
          $item_info         = Item::get('DS');
          $_POST['location'] = $this->order->location = LOC_DROP_SHIP;
          $this->order->add_to_order(count($sales_order->line_items), 'DS', 1, $item_info['long_description'], 0, '', Dates::addDays(Dates::today(), 10), 0, 0, 0);
          $address = $sales_order->customer_name . "\n";
          if (!empty($sales_order->name) && $sales_order->deliver_to == $sales_order->customer_name) {
            $address .= $sales_order->name . "\n";
          } elseif ($sales_order->deliver_to != $sales_order->customer_name) {
            $address .= $sales_order->deliver_to . "\n";
          }
          if (!empty($sales_order->phone)) {
            $address .= 'Ph:' . $sales_order->phone . "\n";
          }
          $address .= $sales_order->delivery_address;
          $this->order->delivery_address = $address;
        }
        unset($_POST['order_id']);
      } else {
        $this->order = new Purch_Order($order_no);
      }
      $this->order = Purch_Order::copyToPost($this->order);
      return $this->order;
    }
    /**
     * @return bool
     */
    function checkData() {
      $dec = Item::qty_dec($_POST['stock_id']);
      $min = 1 / pow(10, $dec);
      if (!Validation::post_num('qty', $min)) {
        $min = Num::format($min, $dec);
        Event::error(_("The quantity of the order item must be numeric and not less than ") . $min);
        JS::setFocus('qty');
        return false;
      }
      if (!Validation::post_num('price', 0)) {
        Event::error(_("The price entered must be numeric and not less than zero."));
        JS::setFocus('price');
        return false;
      }
      if (!Validation::post_num('discount', 0, 100)) {
        Event::error(_("Discount percent can not be less than 0 or more than 100."));
        JS::setFocus('discount');
        return false;
      }
      if (!Dates::isDate($_POST['req_del_date'])) {
        Event::error(_("The date entered is in an invalid format."));
        JS::setFocus('req_del_date');
        return false;
      }
      return true;
    }
    /**
     * @internal param \Purch_Order $this ->order
     * @return bool
     */
    function canCommit() {
      if (!$this->order) {
        Event::error(_("You are not currently editing an order."));
        Page::footer_exit();
      }
      if (!Input::post('creditor_id')) {
        Event::error(_("There is no supplier selected."));
        JS::setFocus('creditor_id');
        return false;
      }
      if (!Dates::isDate($_POST['OrderDate'])) {
        Event::error(_("The entered order date is invalid."));
        JS::setFocus('OrderDate');
        return false;
      }
      if (Input::post('delivery_address') == '') {
        Event::error(_("There is no delivery address specified."));
        JS::setFocus('delivery_address');
        return false;
      }
      if (!Validation::post_num('freight', 0)) {
        Event::error(_("The freight entered must be numeric and not less than zero."));
        JS::setFocus('freight');
        return false;
      }
      if (Input::post('location') == '') {
        Event::error(_("There is no location specified to move any items into."));
        JS::setFocus('location');
        return false;
      }
      if ($this->order->order_has_items() == false) {
        Event::error(_("The order cannot be placed because there are no lines entered on this order."));
        return false;
      }
      if (!$this->order->order_no) {
        if (!Ref::is_valid(Input::post('ref'))) {
          Event::error(_("There is no reference entered for this purchase order."));
          JS::setFocus('ref');
          return false;
        }
        if (!Ref::is_new($_POST['ref'], ST_PURCHORDER)) {
          $_POST['ref'] = Ref::get_next(ST_PURCHORDER);
        }
      }
      return true;
    }
  }

  new PurchaseOrder();
