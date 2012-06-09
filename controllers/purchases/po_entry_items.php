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
  JS::open_window(900, 500);
  if (isset($_GET[Orders::MODIFY_ORDER])) {
    Page::start(_($help_context = "Modify Purchase Order #") . $_GET[Orders::MODIFY_ORDER], SA_PURCHASEORDER);
  } else {
    Page::start(_($help_context = "Purchase Order Entry"), SA_PURCHASEORDER);
  }
  Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
  Validation::check(Validation::PURCHASE_ITEMS, _("There are no purchasable inventory items defined in the system."), STOCK_PURCHASED);
  if (isset($_GET[ADDED_ID])) {
    $order_no   = $_GET[ADDED_ID];
    $trans_type = ST_PURCHORDER;
    $supplier   = new Creditor(Session::i()->getGlobal('creditor'));
    if (!isset($_GET['Updated'])) {
      Event::success(_("Purchase Order: " . Session::i()->history[ST_PURCHORDER] . " has been entered"));
    } else {
      Event::success(_("Purchase Order: " . Session::i()->history[ST_PURCHORDER] . " has been updated"));
    }
    Display::note(GL_UI::trans_view($trans_type, $order_no, _("&View this order"), FALSE, 'button'), 0, 1);
    Display::note(Reporting::print_doc_link($order_no, _("&Print This Order"), TRUE, $trans_type), 0, 1);
    Display::submenu_button(_("&Edit This Order"), "/purchases/po_entry_items.php?ModifyOrder=$order_no");
    Reporting::email_link($order_no, _("Email This Order"), TRUE, $trans_type, 'EmailLink', NULL, $supplier->getEmailAddresses(), 1);
    Display::link_button("/purchases/po_receive_items.php", _("&Receive Items on this PO"), "PONumber=$order_no");
    Display::link_button($_SERVER['DOCUMENT_URI'], _("&New Purchase Order"), "NewOrder=yes");
    Display::link_no_params("/purchases/inquiry/po_search.php", _("&Outstanding Purchase Orders"), TRUE, TRUE);
    Page::footer_exit();
  }
  $order = Orders::session_get() ? : NULL;
  if (isset($_POST[Orders::CANCEL_CHANGES])) {
    $order_no = $order->order_no;
    Orders::session_delete($_POST['order_id']);
    $order = create_order($order_no);
  }
  $id = Form::findPostPrefix(MODE_DELETE);
  if ($id != -1 && $order) {
    if ($order->some_already_received($id) == 0) {
      $order->remove_from_order($id);
      unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
    } else {
      Event::error(_("This item cannot be deleted because some of it has already been received."));
    }
    Item_Line::start_focus('_stock_id_edit');
  }
  if (isset($_POST[COMMIT])) {
    Purch_Order::copyFromPost($order);
    if (can_commit($order)) {
      if ($order->order_no == 0) {
        /*its a new order to be inserted */
        $order_no                           = $order->add();
        $_SESSION['history'][ST_PURCHORDER] = $order->reference;
        Dates::new_doc_date($order->orig_order_date);
        Orders::session_delete($_POST['order_id']);
        Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$order_no");
      } else {
        /*its an existing order need to update the old order info */
        $order_no                           = $order->update();
        $_SESSION['history'][ST_PURCHORDER] = $order->reference;
        Orders::session_delete($_POST['order_id']);
        Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$order_no&Updated=1");
      }
    }
  }
  if (isset($_POST[UPDATE_ITEM]) && check_data()) {
    if ($order->line_items[$_POST['line_no']]->qty_inv > Validation::input_num('qty') || $order->line_items[$_POST['line_no']]->qty_received > Validation::input_num('qty')) {
      Event::error(_("You are attempting to make the quantity ordered a quantity less than has already been invoiced or received. This is prohibited.") . "<br>" . _("The quantity received can only be modified by entering a negative receipt and the quantity invoiced can only be reduced by entering a credit note against this item."));
      JS::set_focus('qty');
    } else {
      $order->update_order_item($_POST['line_no'], Validation::input_num('qty'), Validation::input_num('price'), $_POST['req_del_date'], $_POST['description'], $_POST['discount'] / 100);
      unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
      Item_Line::start_focus('_stock_id_edit');
    }
  }
  if (isset($_POST[ADD_ITEM])) {
    $allow_update = check_data();
    if ($allow_update == TRUE) {
      if ($allow_update == TRUE) {
        $sql
                = "SELECT long_description as description , units, mb_flag
				FROM stock_master WHERE stock_id = " . DB::escape($_POST['stock_id']);
        $result = DB::query($sql, "The stock details for " . $_POST['stock_id'] . " could not be retrieved");
        if (DB::num_rows($result) == 0) {
          $allow_update = FALSE;
        }
        if ($allow_update) {
          $myrow = DB::fetch($result);
          $order->add_to_order($_POST['line_no'], $_POST['stock_id'], Validation::input_num('qty'), $_POST['description'], Validation::input_num('price'), $myrow["units"], $_POST['req_del_date'], 0, 0, $_POST['discount'] / 100);
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
    if (!$order) {
      Display::meta_forward('/index.php', 'application=Purchases');
    }
    //need to check that not already dispatched or invoiced by the supplier
    if (($order->order_no != 0) && $order->any_already_received() == 1) {
      Event::error(_("This order cannot be cancelled because some of it has already been received.") . "<br>" . _("The line item quantities may be modified to quantities more than already received. prices cannot be altered for lines that have already been received and quantities cannot be reduced below the quantity already received."));
    } else {
      Orders::session_delete($order->order_id);
      if ($order->order_no != 0) {
        $order->delete();
      } else {
        Display::meta_forward('/index.php', 'application=Purchases');
      }
      Orders::session_delete($order->order_id);
      Event::notice(_("This purchase order has been cancelled."));
      Display::link_params("/purchases/po_entry_items.php", _("Enter a new purchase order"), "NewOrder=Yes");
      Page::footer_exit();
    }
  }
  if (isset($_POST[CANCEL])) {
    unset($_POST['stock_id'], $_POST['qty'], $_POST['price'], $_POST['req_del_date']);
  }
  if (isset($_GET[Orders::MODIFY_ORDER]) && $_GET[Orders::MODIFY_ORDER] != "") {
    $order = create_order($_GET[Orders::MODIFY_ORDER]);
  } elseif (isset($_POST[CANCEL]) || isset($_POST[UPDATE_ITEM])) {
    Item_Line::start_focus('_stock_id_edit');
  } elseif (isset($_GET[Orders::NEW_ORDER]) || !isset($order)) {
    $order = create_order();
    if ((!isset($_GET['UseOrder']) || !$_GET['UseOrder']) && count($order->line_items) == 0) {
      echo "<div class='center'><iframe src='" . e('/purchases/inquiry/po_search_completed.php?' . LOC_NOT_FAXED_YET . '=1&frame=1') . "' class='width70' style='height:300px' ></iframe></div>";
    }
  }
  Form::start();
  echo "<br>";
  Form::hidden('order_id');
  $order->header();
  $order->display_items();
  Table::start('tablestyle2');
  Form::textareaRow(_("Memo:"), 'Comments', NULL, 70, 4);
  Table::end(1);
  Display::div_start('controls', 'items_table');
  if ($order->order_has_items()) {
    Form::submitCenterBegin(Orders::CANCEL, _("Delete This Order"));
    Form::submitCenterInsert(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
    if ($order->order_no) {
      Form::submitCenterEnd(COMMIT, _("Update Order"), '', 'default');
    } else {
      Form::submitCenterEnd(COMMIT, _("Place Order"), '', 'default');
    }
  } else {
    Form::submitConfirm(Orders::CANCEL, _('You are about to void this Document.\nDo you want to continue?'));
    Form::submitCenterBegin(Orders::CANCEL, _("Delete This Order"), TRUE, FALSE, ICON_DELETE);
    Form::submitCenterInsert(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
  }
  Display::div_end();
  Form::end();
  Item::addEditDialog();
  if (isset($order->supplier_id)) {
    Creditor::addInfoDialog("td[name=\"supplier_name\"]", $order->supplier_details['supplier_id']);
  }
  Page::end(TRUE);

  /**
   * @param int $order_no
   *
   * @return \Purch_Order|\Sales_Order
   */
  function create_order($order_no = 0)
  {
    if (isset($_GET['UseOrder']) && $_GET['UseOrder'] && isset(Orders::session_get($_GET['UseOrder'])->line_items)) {
      $sales_order = Orders::session_get($_GET['UseOrder']);
      $order       = new Purch_Order($order_no);
      $stock       = $myrow = array();
      foreach ($sales_order->line_items as $line_item) {
        $stock[] = ' stock_id = ' . DB::escape($line_item->stock_id);
      }
      $sql    = "SELECT AVG(price),supplier_id,COUNT(supplier_id) FROM purch_data WHERE " . implode(' OR ', $stock) . ' GROUP BY supplier_id ORDER BY AVG(price)';
      $result = DB::query($sql);
      $row    = DB::fetch($result);
      $order->supplier_to_order($row['supplier_id']);
      foreach ($sales_order->line_items as $line_no => $line_item) {
        $order->add_to_order($line_no, $line_item->stock_id, $line_item->quantity, $line_item->description, 0, $line_item->units, Dates::add_days(Dates::today(), 10), 0, 0, 0);
      }
      if (isset($_GET[LOC_DROP_SHIP])) {
        $item_info         = Item::get('DS');
        $_POST['location'] = $order->location = LOC_DROP_SHIP;
        $order->add_to_order(count($sales_order->line_items), 'DS', 1, $item_info['long_description'], 0, '', Dates::add_days(Dates::today(), 10), 0, 0, 0);
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
        $order->delivery_address = $address;
      }
      unset($_POST['order_id']);
    } else {
      $order = new Purch_Order($order_no);
    }
    $order = Purch_Order::copyToPost($order);
    return $order;
  }

  /**
   * @return bool
   */
  function check_data()
  {
    $dec = Item::qty_dec($_POST['stock_id']);
    $min = 1 / pow(10, $dec);
    if (!Validation::post_num('qty', $min)) {
      $min = Num::format($min, $dec);
      Event::error(_("The quantity of the order item must be numeric and not less than ") . $min);
      JS::set_focus('qty');
      return FALSE;
    }
    if (!Validation::post_num('price', 0)) {
      Event::error(_("The price entered must be numeric and not less than zero."));
      JS::set_focus('price');
      return FALSE;
    }
    if (!Validation::post_num('discount', 0, 100)) {
      Event::error(_("Discount percent can not be less than 0 or more than 100."));
      JS::set_focus('discount');
      return FALSE;
    }
    if (!Dates::is_date($_POST['req_del_date'])) {
      Event::error(_("The date entered is in an invalid format."));
      JS::set_focus('req_del_date');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param Purch_Order $order
   *
   * @return bool
   */
  function can_commit($order)
  {
    if (!$order) {
      Event::error(_("You are not currently editing an order."));
      Page::footer_exit();
    }
    if (!Form::getPost('supplier_id')) {
      Event::error(_("There is no supplier selected."));
      JS::set_focus('supplier_id');
      return FALSE;
    }
    if (!Dates::is_date($_POST['OrderDate'])) {
      Event::error(_("The entered order date is invalid."));
      JS::set_focus('OrderDate');
      return FALSE;
    }
    if (Form::getPost('delivery_address') == '') {
      Event::error(_("There is no delivery address specified."));
      JS::set_focus('delivery_address');
      return FALSE;
    }
    if (!Validation::post_num('freight', 0)) {
      Event::error(_("The freight entered must be numeric and not less than zero."));
      JS::set_focus('freight');
      return FALSE;
    }
    if (Form::getPost('location') == '') {
      Event::error(_("There is no location specified to move any items into."));
      JS::set_focus('location');
      return FALSE;
    }
    if ($order->order_has_items() == FALSE) {
      Event::error(_("The order cannot be placed because there are no lines entered on this order."));
      return FALSE;
    }
    if (!$order->order_no) {
      if (!Ref::is_valid(Form::getPost('ref'))) {
        Event::error(_("There is no reference entered for this purchase order."));
        JS::set_focus('ref');
        return FALSE;
      }
      if (!Ref::is_new($_POST['ref'], ST_PURCHORDER)) {
        $_POST['ref'] = Ref::get_next(ST_PURCHORDER);
      }
    }
    return TRUE;
  }


