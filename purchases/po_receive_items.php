<?php
  /**
   * PHP version 5.4
   *
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(900, 500);
  Page::start(_($help_context = "Receive Purchase Order Items"), SA_GRN);
  if (isset($_GET[ADDED_ID])) {
    $grn = $_GET[ADDED_ID];
    $trans_type = ST_SUPPRECEIVE;
    Event::success(_("Purchase Order Delivery has been processed"));
    Display::note(GL_UI::trans_view($trans_type, $grn, _("&View this Delivery")));
    Display::link_params("/purchases/supplier_invoice.php", _("Entry purchase &invoice for this receival"), "New=1");
    Display::link_no_params("/purchases/inquiry/po_search.php", _("Select a different &purchase order for receiving items against"));
    Page::footer_exit();
  }
  $order = Orders::session_get() ? : NULL;
  if (isset($_GET['PONumber']) && $_GET['PONumber'] > 0 && !isset($_POST['Update'])) {
    $order = new Purch_Order($_GET['PONumber']);
  }
  elseif ((!isset($_GET['PONumber']) || $_GET['PONumber'] == 0) && !isset($_POST['order_id'])) {
    Event::error(_("This page can only be opened if a purchase order has been selected. Please select a purchase order first."));
    Page::footer_exit();
  }
  $order = Purch_Order::check_edit_conflicts($order);
  $_POST['order_id'] = $order->order_id;
  Orders::session_set($order);
  /*read in all the selected order into the Items order */
  if (isset($_POST['Update']) || isset($_POST['ProcessGoodsReceived'])) {
    /* if update quantities button is hit page has been called and ${$line->line_no} would have be
                    set from the post to the quantity to be received in this receival*/
    foreach ($order->line_items as $line) {
      if (($line->quantity - $line->qty_received) > 0) {
        $_POST[$line->line_no] = max($_POST[$line->line_no], 0);
        if (!Validation::post_num($line->line_no)) {
          $_POST[$line->line_no] = Num::format(0, Item::qty_dec($line->stock_id));
        }
        if (!isset($_POST['DefaultReceivedDate']) || $_POST['DefaultReceivedDate'] == "") {
          $_POST['DefaultReceivedDate'] = Dates::new_doc_date();
        }
        $order->line_items[$line->line_no]->receive_qty = Validation::input_num($line->line_no);
        if (isset($_POST[$line->stock_id . "Desc"]) && strlen($_POST[$line->stock_id . "Desc"]) > 0) {
          $order->line_items[$line->line_no]->description = $_POST[$line->stock_id . "Desc"];
        }
      }
    }
    Ajax::i()->activate('grn_items');
  }
  if (isset($_POST['ProcessGoodsReceived']) && $order->can_receive()) {
    if ($order->has_changed()) {
      Event::error(_("This order has been changed or invoiced since this delivery was started to be actioned. Processing halted. To enter a delivery against this purchase order, it must be re-selected and re-read again to update the changes made by the other user."));
      Display::link_no_params("/purchases/inquiry/po_search.php", _("Select a different purchase order for receiving goods against"));
      Display::link_params("/purchases/po_receive_items.php", _("Re-Read the updated purchase order for receiving goods against"), "PONumber=" . $order->order_no);
      unset($order->line_items, $order, $_POST['ProcessGoodsReceived']);
      Ajax::i()->activate('_page_body');
      Page::footer_exit();
    }
    $_SESSION['supplier_id'] = $order->supplier_id;
    $grn = Purch_GRN::add($order, $_POST['DefaultReceivedDate'], $_POST['ref'], $_POST['location']);
    $_SESSION['delivery_po'] = $order->order_no;
    Dates::new_doc_date($_POST['DefaultReceivedDate']);
    unset($order->line_items);
    $order->finish($_POST['order_id']);
    unset($order);
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$grn");
  }
  start_form();
  hidden('order_id');
  Purch_GRN::display($order, TRUE);
  Display::heading(_("Items to Receive"));
  Display::div_start('grn_items');
  start_table('tablestyle width90');
  $th = array(
    _("Item Code"),
    _("Description"),
    _("Ordered"),
    _("Units"),
    _("Received"),
    _("Outstanding"),
    _("This Delivery"),
    _("Price"),
    _('Discount %'),
    _("Total")
  );
  table_header($th);
  /*show the line items on the order with the quantity being received for modification */
  $total = 0;
  $k = 0; //row colour counter
  if (count($order->line_items) > 0) {
    foreach ($order->line_items as $line) {
      alt_table_row_color($k);
      $qty_outstanding = $line->quantity - $line->qty_received;
      if (!isset($_POST['Update']) && !isset($_POST['ProcessGoodsReceived']) && $line->receive_qty == 0) { //If no quantites yet input default the balance to be received
        $line->receive_qty = $qty_outstanding;
      }
      $line_total = ($line->receive_qty * $line->price * (1 - $line->discount));
      $total += $line_total;
      label_cell($line->stock_id);
      if ($qty_outstanding > 0) {
        text_cells(NULL, $line->stock_id . "Desc", $line->description, 30, 50);
      }
      else {
        label_cell($line->description);
      }
      $dec = Item::qty_dec($line->stock_id);
      qty_cell($line->quantity, FALSE, $dec);
      label_cell($line->units);
      qty_cell($line->qty_received, FALSE, $dec);
      qty_cell($qty_outstanding, FALSE, $dec);
      if ($qty_outstanding > 0) {
        qty_cells(NULL, $line->line_no, Num::format($line->receive_qty, $dec), "class='right'", NULL, $dec);
      }
      else {
        label_cell(Num::format($line->receive_qty, $dec), "class='right'");
      }
      amount_decimal_cell($line->price);
      percent_cell($line->discount * 100);
      amount_cell($line_total);
      end_row();
    }
  }
  label_cell(_("Freight"), "colspan=9 class='right'");
  small_amount_cells(NULL, 'freight', Num::price_format($order->freight));
  $display_total = Num::format($total + $_POST['freight'], User::price_dec());
  label_row(_("Total value of items received"), $display_total, "colspan=9 class='right'", ' class="right nowrap"');
  end_table();
  Display::div_end();
  Display::link_params("/purchases/po_entry_items.php", _("Edit This Purchase Order"), "ModifyOrder=" . $order->order_no);
  echo '<br>';
  submit_center_first('Update', _("Update Totals"), '', TRUE);
  submit_center_last('ProcessGoodsReceived', _("Process Receive Items"), _("Clear all GL entry fields"), 'default');
  end_form();
  Page::end();
