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
  Page::start(_($help_context = "View Purchase Order"), SA_SUPPTRANSVIEW, true);
  if (!isset($_GET['trans_no'])) {
    die ("<br>" . _("This page must be called with a purchase order number to review."));
  }
  $order = new Purch_Order($_GET['trans_no']);
  echo "<br>";
  $order->summary(true);
  Table::start('tablestyle width90 pad6');
  Display::heading(_("Line Details"));
  Table::start('tablestyle grid width100');
  $th = array(
    _("Code"), _("Item"), _("Qty"), _("Unit"), _("Price"), _("Disc"), _("Total"), _("Needed By"), _("Received"), _("Invoiced")
  );
  Table::header($th);
  $total = $k = 0;
  $overdue_items = false;
  foreach ($order->line_items as $stock_item) {
    $line_total = $stock_item->quantity * $stock_item->price * (1 - $stock_item->discount);
    // if overdue and outstanding quantities, then highlight as so
    if (($stock_item->quantity - $stock_item->qty_received > 0) && Dates::date1_greater_date2(Dates::today(), $stock_item->req_del_date)
    ) {
      Row::start("class='overduebg'");
      $overdue_items = true;
    }
    else {

    }
    Cell::label($stock_item->stock_id);
    Cell::label($stock_item->description);
    $dec = Item::qty_dec($stock_item->stock_id);
    Cell::qty($stock_item->quantity, false, $dec);
    Cell::label($stock_item->units);
    Cell::amountDecimal($stock_item->price);
    Cell::percent($stock_item->discount * 100);
    Cell::amount($line_total);
    Cell::label($stock_item->req_del_date);
    Cell::qty($stock_item->qty_received, false, $dec);
    Cell::qty($stock_item->qty_inv, false, $dec);
    Row::end();
    $total += $line_total;
  }
  $display_total = Num::format($total, User::price_dec());
  Row::label(_("Total Excluding Tax/Shipping"), $display_total, "class=right colspan=6", ' class="right nowrap"', 3);
  Table::end();
  if ($overdue_items) {
    Event::warning(_("Marked items are overdue."), 0, 0, "class='overduefg'");
  }
  $k = 0;
  $grns_result = Purch_GRN::get_for_po($_GET['trans_no']);
  if (DB::num_rows($grns_result) > 0) {
    echo "</td><td class='top'>"; // outer table
    Display::heading(_("Deliveries"));
    Table::start('tablestyle grid');
    $th = array(_("#"), _("Reference"), _("Delivered On"));
    Table::header($th);
    while ($myrow = DB::fetch($grns_result)) {

      Cell::label(GL_UI::trans_view(ST_SUPPRECEIVE, $myrow["id"]));
      Cell::label($myrow["reference"]);
      Cell::label(Dates::sql2date($myrow["delivery_date"]));
      Row::end();
    }
    Table::end();
  }
  $invoice_result = Purch_Invoice::get_po_credits($_GET['trans_no']);
  $k = 0;
  if (DB::num_rows($invoice_result) > 0) {
    echo "</td><td class='top'>"; // outer table
    Display::heading(_("Invoices/Credits"));
    Table::start('tablestyle grid');
    $th = array(_("#"), _("Date"), _("Total"));
    Table::header($th);
    while ($myrow = DB::fetch($invoice_result)) {

      Cell::label(GL_UI::trans_view($myrow["type"], $myrow["trans_no"]));
      Cell::label(Dates::sql2date($myrow["tran_date"]));
      Cell::amount($myrow["Total"]);
      Row::end();
    }
    Table::end();
  }
  echo "</td></tr>";
  Table::end(1); // outer table
  if (Input::get('frame')) {
    return;
  }
  Display::submenu_print(_("Print This Order"), ST_PURCHORDER, $_GET['trans_no'], 'prtopt');
  Display::submenu_option(_("&Edit This Order"), "/purchases/po_entry_items.php?ModifyOrder=" . $_GET['trans_no']);
  Page::end(true);


