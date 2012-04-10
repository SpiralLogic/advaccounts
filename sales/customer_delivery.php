<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //
  //	Entry/Modify Delivery Note against Sales Order
  //
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(900, 500);
  $page_title = _($help_context = "Deliver Items for a Sales Order");
  if (isset($_GET[Orders::MODIFY_DELIVERY])) {
    $page_title = sprintf(_("Modifying Delivery Note # %d."), $_GET[Orders::MODIFY_DELIVERY]);
    $help_context = "Modifying Delivery Note";
  }
  Page::start($page_title, SA_SALESDELIVERY);
  if (isset($_GET[ADDED_ID])) {
    $dispatch_no = $_GET[ADDED_ID];
    Event::success(sprintf(_("Delivery # %d has been entered."), $dispatch_no));
    Display::note(Debtor::trans_view(ST_CUSTDELIVERY, $dispatch_no, _("&View This Delivery"), 0, 'button button-large'), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("&Print Delivery Note"), TRUE, ST_CUSTDELIVERY), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("&Email Delivery Note"), TRUE, ST_CUSTDELIVERY, FALSE,
      "printlink button", "", 1), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("P&rint as Packing Slip"), TRUE, ST_CUSTDELIVERY, FALSE,
      "printlink button"), 1, 0);
    Display::note(Reporting::print_doc_link($dispatch_no, _("E&mail as Packing Slip"), TRUE, ST_CUSTDELIVERY, FALSE,
      "printlink button", "", 1, 1), 1, 0);
    Display::note(GL_UI::view(13, $dispatch_no, _("View the GL Journal Entries"), 0, 'button button-large'), 1, 0);
    Display::submenu_option(_("Invoice This Delivery"), "/sales/customer_invoice.php?DeliveryNumber=$dispatch_no");
    Display::submenu_option(_("Select Another Order For Dispatch"), "/sales/inquiry/sales_orders_view.php?OutstandingOnly=1");
    Page::footer_exit();
  }
  elseif (isset($_GET[UPDATED_ID])) {
    $delivery_no = $_GET[UPDATED_ID];
    Event::success(sprintf(_('Delivery Note # %d has been updated.'), $delivery_no));
    Display::note(GL_UI::trans_view(ST_CUSTDELIVERY, $delivery_no, _("View this delivery"), 0, 'button  button-large'), 1, 0);
    Display::note(Reporting::print_doc_link($delivery_no, _("&Print Delivery Note"), TRUE, ST_CUSTDELIVERY));
    Display::note(Reporting::print_doc_link($delivery_no, _("&Email Delivery Note"), TRUE, ST_CUSTDELIVERY, FALSE,
      "printlink button", "", 1), 1, 1);
    Display::note(Reporting::print_doc_link($delivery_no, _("P&rint as Packing Slip"), TRUE, ST_CUSTDELIVERY, FALSE,
      "printlink button", "", 0, 1));
    Display::note(Reporting::print_doc_link($delivery_no, _("E&mail as Packing Slip"), TRUE, ST_CUSTDELIVERY, FALSE,
      "printlink button", "", 1, 1), 1);
    Display::link_params("/sales/customer_invoice.php", _("Confirm Delivery and Invoice"), "DeliveryNumber=$delivery_no");
    Display::link_params("/sales/inquiry/sales_deliveries_view.php", _("Select A Different Delivery"), "OutstandingOnly=1");
    Page::footer_exit();
  }
  $order = Orders::session_get() ? : NULL;
  if (isset($_GET['OrderNumber']) && $_GET['OrderNumber'] > 0) {
    $order = new Sales_Order(ST_SALESORDER, $_GET['OrderNumber'], TRUE);
    /*read in all the selected order into the Items order */
    if ($order->count_items() == 0) {
      Display::link_params("/sales/inquiry/sales_orders_view.php", _("Select a different sales order to delivery"), "OutstandingOnly=1");
      die ("<br><span class='bold'>" . _("This order has no items. There is nothing to delivery.") . "</span>");
    }
    $order->trans_type = ST_CUSTDELIVERY;
    $order->src_docs = $order->trans_no;
    $order->order_no = key($order->trans_no);
    $order->trans_no = 0;
    $order->reference = Ref::get_next(ST_CUSTDELIVERY);
    $order->document_date = Dates::new_doc_date();
    Sales_Delivery::copy_from_order($order);
  }
  elseif (isset($_GET[Orders::MODIFY_DELIVERY]) && $_GET[Orders::MODIFY_DELIVERY] > 0) {
    $order = new Sales_Order(ST_CUSTDELIVERY, $_GET['ModifyDelivery']);
    Sales_Delivery::copy_from_order($order);
    if ($order->count_items() == 0) {
      Display::link_params("/sales/inquiry/sales_orders_view.php", _("Select a different delivery"), "OutstandingOnly=1");
      echo "<br><div class='center'><span class='bold'>" . _("This delivery has all items invoiced. There is nothing to modify.") . "</div></span>";
      Page::footer_exit();
    }
  }
  elseif (!Orders::session_exists($order)) {
    /* This page can only be called with an order number for invoicing*/
    Event::error(_("This page can only be opened if an order or delivery note has been selected. Please select it first."));
    Display::link_params("/sales/inquiry/sales_orders_view.php", _("Select a Sales Order to Delivery"), "OutstandingOnly=1");
    Page::end();
    exit;
  }
  else {
    if (!Sales_Delivery::check_quantities($order)) {
      Event::error(_("Selected quantity cannot be less than quantity invoiced nor more than quantity	not dispatched on sales order."));
    }
    elseif (!Validation::is_num('ChargeFreightCost', 0)) {
      Event::error(_("Freight cost cannot be less than zero"));
      JS::set_focus('ChargeFreightCost');
    }
  }
  if (isset($_POST['process_delivery']) && Sales_Delivery::check_data($order) && Sales_Delivery::check_qoh($order)) {
    $dn = $order;
    if ($_POST['bo_policy']) {
      $bo_policy = 0;
    }
    else {
      $bo_policy = 1;
    }
    $newdelivery = ($dn->trans_no == 0);
    Sales_Delivery::copy_to_order($order);
    if ($newdelivery) {
      Dates::new_doc_date($dn->document_date);
    }
    $delivery_no = $dn->write($bo_policy);
    $dn->finish();
    if ($newdelivery) {
      Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$delivery_no");
    }
    else {
      Display::meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$delivery_no");
    }
  }
  if (isset($_POST['Update']) || isset($_POST['_location_update'])) {
    Ajax::i()->activate('Items');
  }
  start_form();
  hidden('order_id');
  start_table('tablestyle2 width90 pad5');
  echo "<tr><td>"; // outer table
  start_table('tablestyle width100');
  start_row();
  label_cells(_("Customer"), $order->customer_name, "class='label'");
  label_cells(_("Branch"), Sales_Branch::get_name($order->Branch), "class='label'");
  label_cells(_("Currency"), $order->customer_currency, "class='label'");
  end_row();
  start_row();
  //if (!isset($_POST['ref']))
  //	$_POST['ref'] = Ref::get_next(ST_CUSTDELIVERY);
  if ($order->trans_no == 0) {
    ref_cells(_("Reference"), 'ref', '', NULL, "class='label'");
  }
  else {
    label_cells(_("Reference"), $order->reference, "class='label'");
    hidden('ref', $order->reference);
  }
  label_cells(_("For Sales Order"), Debtor::trans_view(ST_SALESORDER, $order->order_no), "class='tablerowhead'");
  label_cells(_("Sales Type"), $order->sales_type_name, "class='label'");
  end_row();
  start_row();
  if (!isset($_POST['location'])) {
    $_POST['location'] = $order->location;
  }
  label_cell(_("Delivery From"), "class='label'");
  Inv_Location::cells(NULL, 'location', NULL, FALSE, TRUE);
  if (!isset($_POST['ship_via'])) {
    $_POST['ship_via'] = $order->ship_via;
  }
  label_cell(_("Shipping Company"), "class='label'");
  Sales_UI::shippers_cells(NULL, 'ship_via', $_POST['ship_via']);
  // set this up here cuz it's used to calc qoh
  if (!isset($_POST['DispatchDate']) || !Dates::is_date($_POST['DispatchDate'])) {
    $_POST['DispatchDate'] = Dates::new_doc_date();
    if (!Dates::is_date_in_fiscalyear($_POST['DispatchDate'])) {
      $_POST['DispatchDate'] = Dates::end_fiscalyear();
    }
  }
  date_cells(_("Date"), 'DispatchDate', '', $order->trans_no == 0, 0, 0, 0, "class='label'");
  end_row();
  end_table();
  echo "</td><td>"; // outer table
  start_table('tablestyle width90');
  if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
    $_POST['due_date'] = $order->get_invoice_duedate($order->customer_id, $_POST['DispatchDate']);
  }
  start_row();
  date_cells(_("Invoice Dead-line"), 'due_date', '', NULL, 0, 0, 0, "class='label'");
  end_row();
  end_table();
  echo "</td></tr>";
  end_table(1); // outer table
  $row = Sales_Order::get_customer($order->customer_id);
  if ($row['dissallow_invoices'] == 1) {
    Event::error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
    end_form();
    Page::end();
    exit();
  }
  Display::heading(_("Delivery Items"));
  Display::div_start('Items');
  start_table('tablestyle width90');
  $new = $order->trans_no == 0;
  $th = array(
    _("Item Code"), _("Item Description"), $new ? _("Ordered") : _("Max. delivery"), _("Units"),
    $new ? _("Delivered") : _("Invoiced"), _("This Delivery"), _("Price"), _("Tax Type"), _("Discount"), _("Total")
  );
  table_header($th);
  $k = 0;
  $has_marked = FALSE;
  foreach ($order->line_items as $line_no => $line) {
    if ($line->quantity == $line->qty_done) {
      continue; //this line is fully delivered
    }
    // if it's a non-stock item (eg. service) don't show qoh
    $show_qoh = TRUE;
    if (DB_Company::get_pref('allow_negative_stock') || !WO::has_stock_holding($line->mb_flag) || $line->qty_dispatched == 0
    ) {
      $show_qoh = FALSE;
    }
    if ($show_qoh) {
      $qoh = Item::get_qoh_on_date($line->stock_id, $_POST['location'], $_POST['DispatchDate']);
    }
    if ($show_qoh && ($line->qty_dispatched > $qoh)) {
      // oops, we don't have enough of one of the component items
      start_row("class='stockmankobg'");
      $has_marked = TRUE;
    }
    else {
      alt_table_row_color($k);
    }
    Item_UI::status_cell($line->stock_id);
    text_cells(NULL, 'Line' . $line_no . 'Desc', $line->description, 30, 50);
    $dec = Item::qty_dec($line->stock_id);
    qty_cell($line->quantity, FALSE, $dec);
    label_cell($line->units);
    qty_cell($line->qty_done, FALSE, $dec);
    small_qty_cells(NULL, 'Line' . $line_no, Item::qty_format($line->qty_dispatched, $line->stock_id, $dec), NULL, NULL, $dec);
    $display_discount_percent = Num::percent_format($line->discount_percent * 100) . "%";
    $line_total = ($line->qty_dispatched * $line->price * (1 - $line->discount_percent));
    amount_cell($line->price);
    label_cell($line->tax_type_name);
    label_cell($display_discount_percent, ' class="right nowrap"');
    amount_cell($line_total);
    end_row();
  }
  $_POST['ChargeFreightCost'] = get_post('ChargeFreightCost', Num::price_format($order->freight_cost));
  $colspan = 9;
  start_row();
  label_cell(_("Shipping Cost"), "colspan=$colspan class='right'");
  small_amount_cells(NULL, 'ChargeFreightCost', $order->freight_cost);
  end_row();
  $inv_items_total = $order->get_items_total_dispatch();
  $display_sub_total = Num::price_format($inv_items_total + Validation::input_num('ChargeFreightCost'));
  label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan class='right'", "class='right'");
  $taxes = $order->get_taxes(Validation::input_num('ChargeFreightCost'));
  $tax_total = Tax::edit_items($taxes, $colspan, $order->tax_included);
  $display_total = Num::price_format(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
  label_row(_("Amount Total"), $display_total, "colspan=$colspan class='right'", "class='right'");
  end_table(1);
  if ($has_marked) {
    Event::warning(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='red'");
  }
  start_table('tablestyle2');
  Sales_UI::policy_row(_("Action For Balance"), "bo_policy", NULL);
  textarea_row(_("Memo"), 'Comments', NULL, 50, 4);
  end_table(1);
  Display::div_end();
  submit_center_first('Update', _("Update"), _('Refresh document page'), TRUE);
  submit_center_last('process_delivery', _("Process Dispatch"), _('Check entered data and save document'), 'default');
  end_form();
  Page::end();
