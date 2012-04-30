<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  JS::open_window(900, 600);
  Page::start(_($help_context = "View Sales Invoice"), SA_SALESTRANSVIEW, TRUE);
  if (isset($_GET["trans_no"])) {
    $trans_id = $_GET["trans_no"];
  }
  elseif (isset($_POST["trans_no"])) {
    $trans_id = $_POST["trans_no"];
  }
  // 3 different queries to get the information - what a JOKE !!!!
  $myrow = Debtor_Trans::get($trans_id, ST_SALESINVOICE);
  $branch = Sales_Branch::get($myrow["branch_id"]);
  $sales_order = Sales_Order::get_header($myrow["order_"], ST_SALESORDER);
  start_table('tablestyle2 width90');
  echo "<tr class='tablerowhead top'><th colspan=6>";
  Display::heading(sprintf(_("SALES INVOICE #%d"), $trans_id));
  echo "</td></tr>";
  echo "<tr class='top'><td colspan=3>";
  start_table('tablestyle width100');
  label_row(_("Charge To"), $myrow["DebtorName"] . "<br>" . nl2br($myrow["address"]), "class='label' nowrap", "colspan=5");
  start_row();
  label_cells(_("Charge Branch"), $branch["br_name"] . "<br>" . nl2br($branch["br_address"]), "class='label' nowrap", "colspan=2");
  label_cells(_("Delivered To"), $sales_order["deliver_to"] . "<br>" . nl2br($sales_order["delivery_address"]), "class='label' nowrap", "colspan=2");
  end_row();
  start_row();
  label_cells(_("Reference"), $myrow["reference"], "class='label'");
  label_cells(_("Currency"), $sales_order["curr_code"], "class='label'");
  label_cells(_("Our Order No"), Debtor::trans_view(ST_SALESORDER, $sales_order["order_no"]), "class='label'");
  end_row();
  start_row();
  label_cells(_("PO #"), $sales_order["customer_ref"], "class='label'");
  label_cells(_("Shipping Company"), $myrow["shipper_name"], "class='label'");
  label_cells(_("Sales Type"), $myrow["sales_type"], "class='label'");
  end_row();
  start_row();
  label_cells(_("Invoice Date"), Dates::sql2date($myrow["tran_date"]), "class='label'", ' class="nowrap"');
  label_cells(_("Due Date"), Dates::sql2date($myrow["due_date"]), "class='label'", ' class="nowrap"');
  label_cells(_("Deliveries"), Debtor::trans_view(ST_CUSTDELIVERY, Debtor_Trans::get_parent(ST_SALESINVOICE, $trans_id)), "class='label'");
  end_row();
  DB_Comments::display_row(ST_SALESINVOICE, $trans_id);
  end_table();
  echo "</td></tr>";
  end_table(1); // outer table
  $result = Debtor_TransDetail::get(ST_SALESINVOICE, $trans_id);
  start_table('tablestyle width95');
  if (DB::num_rows() > 0) {
    $th = array(
      _("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount %"), _("Total")
    );
    table_header($th);
    $k = 0; //row colour counter
    $sub_total = 0;
    while ($myrow2 = $result->fetch()) {
      if ($myrow2["quantity"] == 0) {
        continue;
      }
      alt_table_row_color($k);
      $value = Num::round(((1 - $myrow2["discount_percent"]) * $myrow2["unit_price"] * $myrow2["quantity"]), User::price_dec());
      $sub_total += $value;
      if ($myrow2["discount_percent"] == 0) {
        $display_discount = "";
      }
      else {
        $display_discount = Num::percent_format($myrow2["discount_percent"] * 100) . "%";
      }
      label_cell($myrow2["stock_id"]);
      label_cell($myrow2["StockDescription"]);
      qty_cell($myrow2["quantity"], FALSE, Item::qty_dec($myrow2["stock_id"]));
      label_cell($myrow2["units"], "class='right'");
      amount_cell($myrow2["unit_price"]);
      label_cell($display_discount, ' class="right nowrap"');
      amount_cell($value);
      end_row();
    } //end while there are line items to print out
  }
  else {
    Event::warning(_("There are no line items on this invoice."), 1, 2);
  }
  $display_sub_tot = Num::price_format($sub_total);
  $display_freight = Num::price_format($myrow["ov_freight"]);
  /*Print out the invoice text entered */
  label_row(_("Sub-total"), $display_sub_tot, "colspan=6 class='right'", " class='right nowrap width15'");
  label_row(_("Shipping"), $display_freight, "colspan=6 class='right'", ' class="right nowrap"');
  $tax_items = GL_Trans::get_tax_details(ST_SALESINVOICE, $trans_id);
  Debtor_Trans::display_tax_details($tax_items, 6);
  $display_total = Num::price_format($myrow["ov_freight"] + $myrow["ov_gst"] + $myrow["ov_amount"] + $myrow["ov_freight_tax"]);
  label_row(_("TOTAL INVOICE"), $display_total, "colspan=6 class='right'", ' class="right nowrap"');
  end_table(1);
  Display::is_voided(ST_SALESINVOICE, $trans_id, _("This invoice has been voided."));
  if (Input::get('frame')) {
    return;
  }
  $customer = new Debtor($myrow['debtor_no']);
  $emails = $customer->getEmailAddresses();
  Display::submenu_print(_("&Print This Invoice"), ST_SALESINVOICE, $_GET['trans_no'], 'prtopt');
  Reporting::email_link($_GET['trans_no'], _("Email This Invoice"), TRUE, ST_SALESINVOICE, 'EmailLink', NULL, $emails, 1);
  Page::end();

