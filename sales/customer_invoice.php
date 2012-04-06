<?php

  /* * ********************************************************************
              Copyright (C) Advanced Group PTY LTD
              Released under the terms of the GNU General Public License, GPL,
              as published by the Free Software Foundation, either version 3
              of the License, or (at your option) any later version.
              This program is distributed in the hope that it will be useful,
              but WITHOUT ANY WARRANTY; without even the implied warranty of
              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
              See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
            * ********************************************************************* */
  //
  //	Entry/Modify Sales Invoice against single delivery
  //	Entry/Modify Batch Sales Invoice against batch of deliveries
  //
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(900, 500);

  $page_title = 'Sales Invoice Complete';
  if (isset($_GET[Orders::MODIFY_INVOICE])) {
    $page_title = sprintf(_("Modifying Sales Invoice # %d."), $_GET[Orders::MODIFY_INVOICE]);
    $help_context = "Modifying Sales Invoice";
  }
  elseif (isset($_GET['DeliveryNumber'])) {
    $page_title = _($help_context = "Issue an Invoice for Delivery Note");
  }
  elseif (isset($_GET[Orders::BATCH_INVOICE])) {
    $page_title = _($help_context = "Issue Batch Invoice for Delivery Notes");
  }
  elseif (isset($_GET[Orders::VIEW_INVOICE])) {
    $page_title = sprintf(_("View Sales Invoice # %d."), $_GET[Orders::VIEW_INVOICE]);
  }
  Page::start($page_title, SA_SALESINVOICE);
  $order = Orders::session_get() ? : NULL;
  if (isset($_GET[ADDED_ID])) {
    $order = new Sales_Order(ST_SALESINVOICE, $_GET[ADDED_ID]);
    $customer = new Debtor($order->customer_id);
    $emails = $customer->getEmailAddresses();
    $invoice_no = $_GET[ADDED_ID];
    $reference = $order->reference;
    Event::success(_("Invoice $reference has been entered."));
    $trans_type = ST_SALESINVOICE;
    Event::success(_("Selected deliveries has been processed"), TRUE);
    Display::note(Debtor::trans_view($trans_type, $invoice_no, _("&View This Invoice"), FALSE, 'button'), 0, 1);
    Display::note(Reporting::print_doc_link($invoice_no, _("&Print This Invoice"), TRUE, ST_SALESINVOICE));
    Reporting::email_link($invoice_no, _("Email This Invoice"), TRUE, ST_SALESINVOICE, 'EmailLink', NULL, $emails, 1);
    Display::link_params("/sales/customer_payments.php", _("Apply a customer payment"), '', TRUE, 'class="button"');
    Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL &Journal Entries for this Invoice"), FALSE, 'button'), 1);
    Display::link_params("/sales/inquiry/sales_deliveries_view.php", _("Select Another &Delivery For Invoicing"), "OutstandingOnly=1", TRUE, 'class="button"');
    Page::footer_exit();
  }
  elseif (isset($_GET[UPDATED_ID])) {
    $order = new Sales_Order(ST_SALESINVOICE, $_GET[UPDATED_ID]);
    $customer = new Debtor($order->customer_id);
    $emails = $customer->getEmailAddresses();
    $invoice_no = $_GET[UPDATED_ID];
    Event::success(sprintf(_('Sales Invoice # %d has been updated.'), $invoice_no));
    Display::note(GL_UI::trans_view(ST_SALESINVOICE, $invoice_no, _("&View This Invoice")));
    echo '<br>';
    Display::note(Reporting::print_doc_link($invoice_no, _("&Print This Invoice"), TRUE, ST_SALESINVOICE));
    Reporting::email_link($invoice_no, _("Email This Invoice"), TRUE, ST_SALESINVOICE, 'EmailLink', NULL, $emails, 1);
    Display::link_no_params("/sales/inquiry/customer_inquiry.php", _("Select A Different &Invoice to Modify"));
    Page::footer_exit();
  }
  elseif (isset($_GET['RemoveDN'])) {
    for ($line_no = 0; $line_no < count($order->line_items); $line_no++) {
      $line = $order->line_items[$line_no];
      if ($line->src_no == $_GET['RemoveDN']) {
        $line->quantity = $line->qty_done;
        $line->qty_dispatched = 0;
      }
    }
    unset($line);
    // Remove also src_doc delivery note
    $sources = $order->src_docs;
    unset($sources[$_GET['RemoveDN']]);
  }
  if ((isset($_GET['DeliveryNumber']) && ($_GET['DeliveryNumber'] > 0)) || isset($_GET[Orders::BATCH_INVOICE])) {
    if (isset($_GET[Orders::BATCH_INVOICE])) {
      $src = $_SESSION['DeliveryBatch'];
      unset($_SESSION['DeliveryBatch']);
    }
    else {
      $src = array($_GET['DeliveryNumber']);
    }
    /* read in all the selected deliveries into the Items order */
    $order = new Sales_Order(ST_CUSTDELIVERY, $src, TRUE);
    if ($order->count_items() == 0) {
      Display::link_params("/sales/inquiry/sales_deliveries_view.php", _("Select a different delivery to invoice"), "OutstandingOnly=1");
      die("<br><span class='bold'>" . _("There are no delivered items with a quantity left to invoice. There is nothing left to invoice.") . "</span>");
    }
    $order->trans_type = ST_SALESINVOICE;
    $order->src_docs = $order->trans_no;
    $order->trans_no = 0;
    $order->reference = Ref::get_next(ST_SALESINVOICE);
    $order->due_date = Sales_Order::get_invoice_duedate($order->customer_id, $order->document_date);
    Sales_Invoice::copy_from_order($order);
  }
  elseif (isset($_GET[Orders::MODIFY_INVOICE]) && $_GET[Orders::MODIFY_INVOICE] > 0) {
    if (Debtor_Trans::get_parent(ST_SALESINVOICE, $_GET[Orders::MODIFY_INVOICE]) == 0) { // 1.xx compatibility hack
      echo"<div class='center'><br><span class='bold'>" . _("There are no delivery notes for this invoice.<br>
		Most likely this invoice was created in ADV Accounts version prior to 2.0
		and therefore can not be modified.") . "</span></div>";
      Page::footer_exit();
    }
    $order = new Sales_Order(ST_SALESINVOICE, $_GET[Orders::MODIFY_INVOICE]);
    $order->start();
    Sales_Invoice::copy_from_order($order);
    if ($order->count_items() == 0) {
      echo "<div class='center'><br><span class='bold'>" . _("All quantities on this invoice have been credited. There is
			nothing to modify on this invoice") . "</span></div>";
    }
  }
  elseif (isset($_GET[Orders::VIEW_INVOICE]) && $_GET[Orders::VIEW_INVOICE] > 0) {
    $order = new Sales_Order(ST_SALESINVOICE, $_GET[Orders::VIEW_INVOICE]);
    $order->start();
    Sales_Invoice::copy_from_order($order);
  }
  elseif (!$order && !isset($_GET['order_id'])) {
    /* This page can only be called with a delivery for invoicing or invoice no for edit */
    Event::error(_("This page can only be opened after delivery selection. Please select delivery to invoicing first."));
    Display::link_no_params("/sales/inquiry/sales_deliveries_view.php", _("Select Delivery to Invoice"));
    Page::end();
    exit;
  }
  elseif ($order && !Sales_Invoice::check_qty($order)) {
    Event::error(_("Selected quantity cannot be less than quantity credited nor more than quantity not invoiced yet."));
  }
  if (isset($_POST['Update'])) {
    Ajax::i()->activate('Items');
  }
  if (isset($_POST['_InvoiceDate_changed'])) {
    $_POST['due_date'] = Sales_Order::get_invoice_duedate($order->customer_id, $_POST['InvoiceDate']);
    Ajax::i()->activate('due_date');
  }
  if (isset($_POST['process_invoice']) && Sales_Invoice::check_data($order)) {
    $newinvoice = $order->trans_no == 0;
    Sales_Invoice::copy_to_order($order);
    if ($newinvoice) {
      Dates::new_doc_date($order->document_date);
    }
    $invoice_no = $order->write();
    $order->finish();
    if ($newinvoice) {
      Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$invoice_no");
    }
    else {
      //	Display::meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$invoice_no");
    }
  }
  // find delivery spans for batch invoice display
  $dspans = array();
  $lastdn = '';
  $spanlen = 1;
  for ($line_no = 0; $line_no < count($order->line_items); $line_no++) {
    $line = $order->line_items[$line_no];
    if ($line->quantity == $line->qty_done) {
      continue;
    }
    if ($line->src_no == $lastdn) {
      $spanlen++;
    }
    else {
      if ($lastdn != '') {
        $dspans[] = $spanlen;
        $spanlen = 1;
      }
    }
    $lastdn = $line->src_no;
  }
  $dspans[] = $spanlen;
  $is_batch_invoice = count($order->src_docs) > 1;
  $is_edition = $order->trans_type == ST_SALESINVOICE && $order->trans_no != 0;
  start_form();
  hidden('order_id');
  start_table('tablestyle2 width90 pad5');
  start_row();
  label_cells(_("Customer"), $order->customer_name, "class='tablerowhead'");
  label_cells(_("Branch"), Sales_Branch::get_name($order->Branch), "class='tablerowhead'");
  label_cells(_("Currency"), $order->customer_currency, "class='tablerowhead'");
  end_row();
  start_row();
  if ($order->trans_no == 0) {
    ref_cells(_("Reference"), 'ref', '', NULL, "class='tablerowhead'");
  }
  else {
    label_cells(_("Reference"), $order->reference, "class='tablerowhead'");
  }
  label_cells(_("Delivery Notes:"), Debtor::trans_view(ST_CUSTDELIVERY, array_keys($order->src_docs)), "class='tablerowhead'");
  label_cells(_("Sales Type"), $order->sales_type_name, "class='tablerowhead'");
  end_row();
  start_row();
  if (!isset($_POST['ship_via'])) {
    $_POST['ship_via'] = $order->ship_via;
  }
  label_cell(_("Shipping Company"), "class='label'");
  if (!$order->view_only || !isset($order->ship_via)) {
    Sales_UI::shippers_cells(NULL, 'ship_via', $_POST['ship_via']);
  }
  else {
    label_cell($order->ship_via);
  }
  if (!isset($_POST['InvoiceDate']) || !Dates::is_date($_POST['InvoiceDate'])) {
    $_POST['InvoiceDate'] = Dates::new_doc_date();
    if (!Dates::is_date_in_fiscalyear($_POST['InvoiceDate'])) {
      $_POST['InvoiceDate'] = Dates::end_fiscalyear();
    }
  }
  if (!$order->view_only) {
    date_cells(_("Date"), 'InvoiceDate', '', $order->trans_no == 0, 0, 0, 0, "class='tablerowhead'", TRUE);
  }
  else {
    label_cells(_('Invoice Date:'), $_POST['InvoiceDate']);
  }
  if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
    $_POST['due_date'] = Sales_Order::get_invoice_duedate($order->customer_id, $_POST['InvoiceDate']);
  }
  if (!$order->view_only) {
    date_cells(_("Due Date"), 'due_date', '', NULL, 0, 0, 0, "class='tablerowhead'");
  }
  else {
    label_cells(_('Due Date'), $_POST['due_date']);
  }
  end_row();
  end_table();
  $row = Sales_Order::get_customer($order->customer_id);
  if ($row['dissallow_invoices'] == 1) {
    Event::error(_("The selected customer account is currently on hold. Please contact the credit control personnel to discuss."));
    end_form();
    Page::end();
    exit();
  }
  Display::heading(_("Invoice Items"));
  Display::div_start('Items');
  start_table('tablestyle width90');
  $th = array(
    _("Item Code"), _("Item Description"), _("Delivered"), _("Units"), _("Invoiced"), _("This Invoice"), _("Price"),
    _("Tax Type"), _("Discount"), _("Total")
  );
  if ($is_batch_invoice) {
    $th[] = _("DN");
    $th[] = "";
  }
  if ($is_edition) {
    $th[4] = _("Credited");
  }
  table_header($th);
  $k = 0;
  $has_marked = FALSE;
  $show_qoh = TRUE;
  $dn_line_cnt = 0;
  foreach ($order->line_items as $line_no => $line) {
    if (!$order->view_only && $line->quantity == $line->qty_done) {
      continue; // this line was fully invoiced
    }
    alt_table_row_color($k);
    Item_UI::status_cell($line->stock_id);
    if (!$order->view_only) {
      textarea_cells(NULL, 'Line' . $line_no . 'Desc', $line->description, 30, 3);
    }
    else {
      label_cell($line->description);
    }
    $dec = Item::qty_dec($line->stock_id);
    qty_cell($line->quantity, FALSE, $dec);
    label_cell($line->units);
    qty_cell($line->qty_done, FALSE, $dec);
    if ($is_batch_invoice) {
      // for batch invoices we can only remove whole deliveries
      echo '<td class="right nowrap">';
      hidden('Line' . $line_no, $line->qty_dispatched);
      echo Num::format($line->qty_dispatched, $dec) . '</td>';
    }
    elseif ($order->view_only) {
      hidden('viewing');
      qty_cell($line->quantity, FALSE, $dec);
    }
    else {
      small_qty_cells(NULL, 'Line' . $line_no, Item::qty_format($line->qty_dispatched, $line->stock_id, $dec), NULL, NULL, $dec);
    }
    $display_discount_percent = Num::percent_format($line->discount_percent * 100) . " %";
    $line_total = ($line->qty_dispatched * $line->price * (1 - $line->discount_percent));
    amount_cell($line->price);
    label_cell($line->tax_type_name);
    label_cell($display_discount_percent, ' class="right nowrap"');
    amount_cell($line_total);
    if ($is_batch_invoice) {
      if ($dn_line_cnt == 0) {
        $dn_line_cnt = $dspans[0];
        $dspans = array_slice($dspans, 1);
        label_cell($line->src_no, "rowspan=$dn_line_cnt class=oddrow");
        label_cell("<a href='" . $_SERVER['PHP_SELF'] . "?RemoveDN=" . $line->src_no . "'>" . _("Remove") . "</a>", "rowspan=$dn_line_cnt class=oddrow");
      }
      $dn_line_cnt--;
    }
    end_row();
  }
  /* Don't re-calculate freight if some of the order has already been delivered -
            depending on the business logic required this condition may not be required.
            It seems unfair to charge the customer twice for freight if the order
            was not fully delivered the first time ?? */
  if (!isset($_POST['ChargeFreightCost']) || $_POST['ChargeFreightCost'] == "") {
    if ($order->any_already_delivered() == 1) {
      $_POST['ChargeFreightCost'] = Num::price_format(0);
    }
    else {
      $_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
    }
    if (!Validation::is_num('ChargeFreightCost')) {
      $_POST['ChargeFreightCost'] = Num::price_format(0);
    }
  }
  $accumulate_shipping = DB_Company::get_pref('accumulate_shipping');
  if ($is_batch_invoice && $accumulate_shipping) {
    Sales_Invoice::set_delivery_shipping_sum(array_keys($order->src_docs));
  }
  $colspan = 9;
  start_row();
  label_cell(_("Shipping Cost"), "colspan=$colspan class='right bold'");
  if (!$order->view_only) {
    small_amount_cells(NULL, 'ChargeFreightCost', NULL);
  }
  else {
    amount_cell($order->freight_cost);
  }
  if ($is_batch_invoice) {
    label_cell('', 'colspan=2');
  }
  end_row();
  $inv_items_total = $order->get_items_total_dispatch();
  $display_sub_total = Num::price_format($inv_items_total + Validation::input_num('ChargeFreightCost'));
  label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan class='right bold'", "class='right'", $is_batch_invoice ? 2 : 0);
  $taxes = $order->get_taxes(Validation::input_num('ChargeFreightCost'));
  $tax_total = Tax::edit_items($taxes, $colspan, $order->tax_included, $is_batch_invoice ? 2 : 0);
  $display_total = Num::price_format(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
  label_row(_("Invoice Total"), $display_total, "colspan=$colspan class='right bold'", "class='right'", $is_batch_invoice ? 2 : 0);
  end_table(1);
  Display::div_end();
  start_table('tablestyle2');
  textarea_row(_("Memo"), 'Comments', NULL, 50, 4);
  end_table(1);
  start_table('center red bold');
  if (!$order->view_only) {
    label_cell(_("DON'T PRESS THE PROCESS TAX INVOICE BUTTON UNLESS YOU ARE 100% CERTAIN THAT YOU WON'T NEED TO EDIT ANYTHING IN THE
	FUTURE ON THIS
	INVOICE"));
  }
  end_table();

  if (!$order->view_only) {
    submit_center_first('Update', _("Update"), _('Refresh document page'), TRUE);
    submit_center_last('process_invoice', _("Process Invoice"), _('Check entered data and save document'), 'default');
    start_table('center red bold');
    label_cell(_("DON'T FUCK THIS UP, YOU WON'T BE ABLE TO EDIT ANYTHING AFTER THIS. DON'T MAKE YOURSELF FEEL AND LOOK LIKE A DICK!"), 'center');
  }
  end_table();
  end_form();
  Page::end(FALSE);
