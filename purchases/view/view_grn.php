<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  JS::open_window(900, 500);
  Page::start(_($help_context = "View Purchase Order Delivery"), SA_SUPPTRANSVIEW, TRUE);
  if (!isset($_GET['trans_no'])) {
    die ("<BR>" . _("This page must be called with a Purchase Order Delivery number to review."));
  }
  $purchase_order = new Purch_Order;
  Purch_GRN::get($_GET["trans_no"], $purchase_order);
  Display::heading(_("Purchase Order Delivery") . " #" . $_GET['trans_no']);
  echo "<br>";
  Purch_GRN::display($purchase_order);
  Display::heading(_("Line Details"));
  start_table('tablestyle width90');
  $th = array(
    _("Item Code"), _("Item Description"), _("Delivery Date"), _("Quantity"), _("Unit"), _("Price"), _("Line Total"), _("Quantity Invoiced")
  );
  table_header($th);
  $total = 0;
  $k = 0; //row colour counter
  foreach ($purchase_order->line_items as $stock_item) {
    $line_total = $stock_item->qty_received * $stock_item->price;
    alt_table_row_color($k);
    label_cell($stock_item->stock_id);
    label_cell($stock_item->description);
    label_cell($stock_item->req_del_date, ' class="right nowrap"');
    $dec = Item::qty_dec($stock_item->stock_id);
    qty_cell($stock_item->qty_received, FALSE, $dec);
    label_cell($stock_item->units);
    amount_decimal_cell($stock_item->price);
    amount_cell($line_total);
    qty_cell($stock_item->qty_inv, FALSE, $dec);
    end_row();
    $total += $line_total;
  }
  $display_total = Num::format($total, User::price_dec());
  label_row(_("Total Excluding Tax/Shipping"), $display_total, "colspan=6", ' class="right nowrap"');
  end_table(1);
  Display::is_voided(ST_SUPPRECEIVE, $_GET['trans_no'], _("This delivery has been voided."));
  Page::end(TRUE);


