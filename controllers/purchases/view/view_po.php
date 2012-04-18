<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  JS::open_window(900, 500);
  Page::start(_($help_context = "View Purchase Order"), SA_SUPPTRANSVIEW, TRUE);
  if (!isset($_GET['trans_no'])) {
    die ("<br>" . _("This page must be called with a purchase order number to review."));
  }
  $order = new Purch_Order($_GET['trans_no']);
  echo "<br>";
  $order->summary(TRUE);
  start_table('tablestyle width90 pad6');
  Display::heading(_("Line Details"));
  start_table('tablestyle width100');
  $th = array(
    _("Code"), _("Item"), _("Qty"), _("Unit"), _("Price"), _("Disc"), _("Total"), _("Needed By"), _("Received"), _("Invoiced")
  );
  table_header($th);
  $total = $k = 0;
  $overdue_items = FALSE;
  foreach ($order->line_items as $stock_item) {
    $line_total = $stock_item->quantity * $stock_item->price * (1 - $stock_item->discount);
    // if overdue and outstanding quantities, then highlight as so
    if (($stock_item->quantity - $stock_item->qty_received > 0) && Dates::date1_greater_date2(Dates::today(), $stock_item->req_del_date)
    ) {
      start_row("class='overduebg'");
      $overdue_items = TRUE;
    }
    else {
      alt_table_row_color($k);
    }
    label_cell($stock_item->stock_id);
    label_cell($stock_item->description);
    $dec = Item::qty_dec($stock_item->stock_id);
    qty_cell($stock_item->quantity, FALSE, $dec);
    label_cell($stock_item->units);
    amount_decimal_cell($stock_item->price);
    percent_cell($stock_item->discount * 100);
    amount_cell($line_total);
    label_cell($stock_item->req_del_date);
    qty_cell($stock_item->qty_received, FALSE, $dec);
    qty_cell($stock_item->qty_inv, FALSE, $dec);
    end_row();
    $total += $line_total;
  }
  $display_total = Num::format($total, User::price_dec());
  label_row(_("Total Excluding Tax/Shipping"), $display_total, "class=right colspan=6", ' class="right nowrap"', 3);
  end_table();
  if ($overdue_items) {
    Event::warning(_("Marked items are overdue."), 0, 0, "class='overduefg'");
  }
  $k = 0;
  $grns_result = Purch_GRN::get_for_po($_GET['trans_no']);
  if (DB::num_rows($grns_result) > 0) {
    echo "</td><td class='top'>"; // outer table
    Display::heading(_("Deliveries"));
    start_table('tablestyle');
    $th = array(_("#"), _("Reference"), _("Delivered On"));
    table_header($th);
    while ($myrow = DB::fetch($grns_result)) {
      alt_table_row_color($k);
      label_cell(GL_UI::trans_view(ST_SUPPRECEIVE, $myrow["id"]));
      label_cell($myrow["reference"]);
      label_cell(Dates::sql2date($myrow["delivery_date"]));
      end_row();
    }
    end_table();
  }
  $invoice_result = Purch_Invoice::get_po_credits($_GET['trans_no']);
  $k = 0;
  if (DB::num_rows($invoice_result) > 0) {
    echo "</td><td class='top'>"; // outer table
    Display::heading(_("Invoices/Credits"));
    start_table('tablestyle');
    $th = array(_("#"), _("Date"), _("Total"));
    table_header($th);
    while ($myrow = DB::fetch($invoice_result)) {
      alt_table_row_color($k);
      label_cell(GL_UI::trans_view($myrow["type"], $myrow["trans_no"]));
      label_cell(Dates::sql2date($myrow["tran_date"]));
      amount_cell($myrow["Total"]);
      end_row();
    }
    end_table();
  }
  echo "</td></tr>";
  end_table(1); // outer table
  if (Input::get('frame')) {
    return;
  }
  Display::submenu_print(_("Print This Order"), ST_PURCHORDER, $_GET['trans_no'], 'prtopt');
  Display::submenu_option(_("&Edit This Order"), "/purchases/po_entry_items.php?ModifyOrder=" . $_GET['trans_no']);
  Page::end(TRUE);


