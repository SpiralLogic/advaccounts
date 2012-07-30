<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::openWindow(950, 600);
  Page::start(_($help_context = "View Sales Invoice"), SA_SALESTRANSVIEW, true);
  if (isset($_GET["trans_no"])) {
    $trans_id = $_GET["trans_no"];
  } elseif (isset($_POST["trans_no"])) {
    $trans_id = $_POST["trans_no"];
  }
  // 3 different queries to get the information - what a JOKE !!!!
  $myrow       = Debtor_Trans::get($trans_id, ST_SALESINVOICE);
  $branch      = Sales_Branch::get($myrow["branch_id"]);
  $sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER);
  Table::start('tablestyle2 width90');
  echo "<tr class='tablerowhead top'><th colspan=6>";
  Display::heading(sprintf(_("SALES INVOICE #%d"), $trans_id));
  echo "</td></tr>";
  echo "<tr class='top'><td colspan=3>";
  Table::start('tablestyle width100');
  Row::label(_("Charge To"), $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "class='label' nowrap", "colspan=5");
  Row::start();
  Cell::labels(_("Charge Branch"), $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "class='label' nowrap", "colspan=2");
  Cell::labels(_("Delivered To"), $sales_order["deliver_to"] . "<br>" . nl2br($sales_order["delivery_address"]), "class='label' nowrap", "colspan=2");
  Row::end();
  Row::start();
  Cell::labels(_("Reference"), $myrow["reference"], "class='label'");
  Cell::labels(_("Currency"), $sales_order["curr_code"], "class='label'");
  Cell::labels(_("Our Order No"), Debtor::viewTrans(ST_SALESORDER, $sales_order["order_no"]), "class='label'");
  Row::end();
  Row::start();
  Cell::labels(_("PO #"), $sales_order["customer_ref"], "class='label'");
  Cell::labels(_("Shipping Company"), $myrow["shipper_name"], "class='label'");
  Cell::labels(_("Sales Type"), $myrow["sales_type"], "class='label'");
  Row::end();
  Row::start();
  Cell::labels(_("Invoice Date"), Dates::sqlToDate($myrow["tran_date"]), "class='label'", ' class="nowrap"');
  Cell::labels(_("Due Date"), Dates::sqlToDate($myrow["due_date"]), "class='label'", ' class="nowrap"');
  Cell::labels(_("Deliveries"), Debtor::viewTrans(ST_CUSTDELIVERY, Debtor_Trans::get_parent(ST_SALESINVOICE, $trans_id)), "class='label'");
  Row::end();
  DB_Comments::display_row(ST_SALESINVOICE, $trans_id);
  Table::end();
  echo "</td></tr>";
  Table::end(1); // outer table
  $result = Debtor_TransDetail::get(ST_SALESINVOICE, $trans_id);
  Table::start('tablestyle grid width95');
  if (DB::numRows() > 0) {
    $th = array(
      _("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount %"), _("Total")
    );
    Table::header($th);
    $k         = 0; //row colour counter
    $sub_total = 0;
    while ($myrow2 = $result->fetch()) {
      if ($myrow2["quantity"] == 0) {
        continue;
      }
      $value = Num::round(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::price_dec());
      $sub_total += $value;
      if ($myrow2["discount_percent"] == 0) {
        $display_discount = "";
      } else {
        $display_discount = Num::percentFormat($myrow2["discount_percent"] * 100) . "%";
      }
      Cell::label($myrow2["stock_id"]);
      Cell::label($myrow2["StockDescription"]);
      Cell::qty($myrow2["quantity"], false, Item::qty_dec($myrow2["stock_id"]));
      Cell::label($myrow2["units"], "class='right'");
      Cell::amount($myrow2["unit_price"]);
      Cell::label($display_discount, ' class="right nowrap"');
      Cell::amount($value);
      Row::end();
    } //end while there are line items to print out
  } else {
    Event::warning(_("There are no line items on this invoice."), 1, 2);
  }
  $display_sub_tot = Num::priceFormat($sub_total);
  $display_freight = Num::priceFormat($myrow["ov_freight"]);
  /*Print out the invoice text entered */
  Row::label(_("Sub-total"), $display_sub_tot, "colspan=6 class='right'", " class='right nowrap width15'");
  Row::label(_("Shipping"), $display_freight, "colspan=6 class='right'", ' class="right nowrap"');
  $tax_items = GL_Trans::get_tax_details(ST_SALESINVOICE, $trans_id);
  Debtor_Trans::display_tax_details($tax_items, 6);
  $display_total = Num::priceFormat($myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"]);
  Row::label(_("TOTAL INVOICE"), $display_total, "colspan=6 class='right'", ' class="right nowrap"');
  Table::end(1);
  Display::is_voided(ST_SALESINVOICE, $trans_id, _("This invoice has been voided."));
  if (Input::get('frame')) {
    return;
  }
  $customer = new Debtor($myrow['debtor_id']);
  $emails   = $customer->getEmailAddresses();
  Display::submenu_print(_("&Print This Invoice"), ST_SALESINVOICE, $_GET['trans_no'], 'prtopt');
  Reporting::email_link($_GET['trans_no'], _("Email This Invoice"), true, ST_SALESINVOICE, 'EmailLink', null, $emails, 1);
  Page::end();

