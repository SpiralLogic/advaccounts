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
  //	Entry/Modify Credit Note for selected Sales Invoice
  //
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  JS::open_window(900, 500);
  if (isset($_GET[Orders::MODIFY_CREDIT])) {
    $_SESSION['page_title'] = sprintf(_("Modifying Credit Invoice # %d."), $_GET[Orders::MODIFY_CREDIT]);
    $help_context = "Modifying Credit Invoice";
  }
  elseif (isset($_GET['InvoiceNumber'])) {
    $page_title = _($help_context = "Credit all or part of an Invoice");
  }
  else {
    $page_title = "Credit Invoice";
  }

  Page::start($page_title, SA_SALESCREDITINV);
  if (isset($_GET[ADDED_ID])) {
    $credit_no = $_GET[ADDED_ID];
    $trans_type = ST_CUSTCREDIT;
    Event::success(_("Credit Note has been processed"));
    Display::note(Debtor::trans_view($trans_type, $credit_no, _("&View This Credit Note")), 0, 0);
    Display::note(Reporting::print_doc_link($credit_no, _("&Print This Credit Note"), TRUE, $trans_type), 1);
    Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")), 1);
    Page::footer_exit();
  }
  elseif (isset($_GET[UPDATED_ID])) {
    $credit_no = $_GET[UPDATED_ID];
    $trans_type = ST_CUSTCREDIT;
    Event::success(_("Credit Note has been updated"));
    Display::note(Debtor::trans_view($trans_type, $credit_no, _("&View This Credit Note")), 0, 0);
    Display::note(Reporting::print_doc_link($credit_no, _("&Print This Credit Note"), TRUE, $trans_type), 1);
    Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")), 1);
    Page::footer_exit();
  }
  if (isset($_GET['InvoiceNumber']) && $_GET['InvoiceNumber'] > 0) {
    $ci = new Sales_Order(ST_SALESINVOICE, $_GET['InvoiceNumber'], TRUE);
    $ci->trans_type = ST_CUSTCREDIT;
    $ci->src_docs = $ci->trans_no;
    $ci->src_date = $ci->document_date;
    $ci->trans_no = 0;
    $ci->document_date = Dates::new_doc_date();
    $ci->reference = Ref::get_next(ST_CUSTCREDIT);
    for ($line_no = 0; $line_no < count($ci->line_items); $line_no++) {
      $ci->line_items[$line_no]->qty_dispatched = '0';
    }
    copy_from_order($ci);
  }
  elseif (isset($_GET[Orders::MODIFY_CREDIT]) && $_GET[Orders::MODIFY_CREDIT] > 0) {
    $ci = new Sales_Order(ST_CUSTCREDIT, $_GET[Orders::MODIFY_CREDIT]);
    copy_from_order($ci);
  }
  elseif (!Sales_Order::active()) {
    /* This page can only be called with an invoice number for crediting*/
    Event::error(_("This page can only be opened if an invoice has been selected for crediting."));
    Page::footer_exit();
  }
  elseif (!check_quantities()) {
    Event::error(_("Selected quantity cannot be less than zero nor more than quantity not credited yet."));
  }
  if (isset($_POST['ProcessCredit']) && can_process()) {
    $new_credit = (Orders::session_get($_POST['order_id'])->trans_no == 0);
    if (!isset($_POST['WriteOffGLCode'])) {
      $_POST['WriteOffGLCode'] = 0;
    }
    copy_to_order();
    if ($new_credit) {
      Dates::new_doc_date(Orders::session_get($_POST['order_id'])->document_date);
    }
    $credit = Orders::session_get($_POST['order_id']);
    $credit_no = $credit->write($_POST['WriteOffGLCode']);
    Orders::session_delete($credit);
    if ($new_credit) {
      Display::meta_forward($_SERVER['PHP_SELF'], "AddedID=$credit_no");
    }
    else {
      Display::meta_forward($_SERVER['PHP_SELF'], "UpdatedID=$credit_no");
    }
  }
  if (isset($_POST['location'])) {
    Orders::session_get($_POST['order_id'])->location = $_POST['location'];
  }
  if (isset($_POST[Orders::CANCEL_CHANGES])) {
    $order = Orders::session_get($_POST['order_id']);
    $type = $order->trans_type;
    $order_no = key($order->trans_no);
    Orders::session_delete($_POST['order_id']);
    create_order($type, $order_no);
  }
  if (get_post('Update')) {
    Ajax::i()->activate('credit_items');
  }
  display_credit_items();
  display_credit_options();
  submit_center_first('Update', _("Update"), _('Update credit value for quantities entered'), FALSE, ICON_UPDATE);
  submit_center_middle(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
  submit_center_last('ProcessCredit', _("Process Credit Note"), TRUE, '', 'default');
  Page::end();
  /**
   * @return int
   */
  function check_quantities() {
    $ok = 1;
    foreach (Orders::session_get($_POST['order_id'])->line_items as $line_no => $itm) {
      if ($itm->quantity == $itm->qty_done) {
        continue; // this line was fully credited/removed
      }
      if (isset($_POST['Line' . $line_no])) {
        if (Validation::is_num('Line' . $line_no, 0, $itm->quantity)) {
          Orders::session_get($_POST['order_id'])->line_items[$line_no]->qty_dispatched = Validation::input_num('Line' . $line_no);
        }
      }
      else {
        $ok = 0;
      }
      if (isset($_POST['Line' . $line_no . 'Desc'])) {
        $line_desc = $_POST['Line' . $line_no . 'Desc'];
        if (strlen($line_desc) > 0) {
          Orders::session_get($_POST['order_id'])->line_items[$line_no]->description = $line_desc;
        }
      }
    }
    return $ok;
  }

  /**
   * @return bool
   */
  function can_process() {
    if (!Dates::is_date($_POST['CreditDate'])) {
      Event::error(_("The entered date is invalid."));
      JS::set_focus('CreditDate');
      return FALSE;
    }
    elseif (!Dates::is_date_in_fiscalyear($_POST['CreditDate'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('CreditDate');
      return FALSE;
    }
    if (Orders::session_get($_POST['order_id'])->trans_no == 0) {
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        JS::set_focus('ref');
        return FALSE;
      }
      if (!Ref::is_new($_POST['ref'], ST_CUSTCREDIT)) {
        $_POST['ref'] = Ref::get_next(ST_CUSTCREDIT);
      }
    }
    if (!Validation::is_num('ChargeFreightCost', 0)) {
      Event::error(_("The entered shipping cost is invalid or less than zero."));
      JS::set_focus('ChargeFreightCost');
      return FALSE;
    }
    if (!check_quantities()) {
      Event::error(_("Selected quantity cannot be less than zero nor more than quantity not credited yet."));
      return FALSE;
    }
    return TRUE;
  }

  function copy_to_order() {
    $order = Orders::session_get($_POST['order_id']);
    $order->ship_via = $_POST['ShipperID'];
    $order->freight_cost = Validation::input_num('ChargeFreightCost');
    $order->document_date = $_POST['CreditDate'];
    $order->location = $_POST['location'];
    $order->Comments = $_POST['CreditText'];
    if ($order->trans_no == 0) {
      $order->reference = $_POST['ref'];
    }
  }

  /**
   * @param $order
   */
  function copy_from_order($order) {
    $order = Sales_Order::check_edit_conflicts($order);
    $_POST['ShipperID'] = $order->ship_via;
    $_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
    $_POST['CreditDate'] = $order->document_date;
    $_POST['location'] = $order->location;
    $_POST['CreditText'] = $order->Comments;
    $_POST['order_id'] = $order->order_id;
    $_POST['ref'] = $order->reference;
    Orders::session_set($order);
  }

  function display_credit_items() {
    start_form();
    hidden('order_id');
    start_table('tablestyle2 width90 pad5');
    echo "<tr><td>"; // outer table
    start_table('tablestyle width100');
    start_row();
    label_cells(_("Customer"), Orders::session_get($_POST['order_id'])->customer_name, "class='tablerowhead'");
    label_cells(_("Branch"), Sales_Branch::get_name(Orders::session_get($_POST['order_id'])->Branch), "class='tablerowhead'");
    label_cells(_("Currency"), Orders::session_get($_POST['order_id'])->customer_currency, "class='tablerowhead'");
    end_row();
    start_row();
    if (Orders::session_get($_POST['order_id'])->trans_no == 0) {
      ref_cells(_("Reference"), 'ref', '', NULL, "class='tablerowhead'");
    }
    else {
      label_cells(_("Reference"), Orders::session_get($_POST['order_id'])->reference, "class='tablerowhead'");
    }
    label_cells(_("Crediting Invoice"), Debtor::trans_view(ST_SALESINVOICE, array_keys(Orders::session_get($_POST['order_id'])->src_docs)), "class='tablerowhead'");
    if (!isset($_POST['ShipperID'])) {
      $_POST['ShipperID'] = Orders::session_get($_POST['order_id'])->ship_via;
    }
    label_cell(_("Shipping Company"), "class='tablerowhead'");
    Sales_UI::shippers_cells(NULL, 'ShipperID', $_POST['ShipperID']);
    //	if (!isset($_POST['sales_type_id']))
    //	 $_POST['sales_type_id'] = Orders::session_get($_POST['order_id'])->sales_type;
    //	label_cell(_("Sales Type"), "class='tablerowhead'");
    //	Sales_Type::cells(null, 'sales_type_id', $_POST['sales_type_id']);
    end_row();
    end_table();
    echo "</td><td>"; // outer table
    start_table('tablestyle width100');
    label_row(_("Invoice Date"), Orders::session_get($_POST['order_id'])->src_date, "class='tablerowhead'");
    date_row(_("Credit Note Date"), 'CreditDate', '', Orders::session_get($_POST['order_id'])->trans_no == 0, 0, 0, 0, "class='tablerowhead'");
    end_table();
    echo "</td></tr>";
    end_table(1); // outer table
    Display::div_start('credit_items');
    start_table('tablestyle width90');
    $th = array(
      _("Item Code"), _("Item Description"), _("Invoiced Quantity"), _("Units"), _("Credit Quantity"), _("Price"),
      _("Discount %"), _("Total")
    );
    table_header($th);
    $k = 0; //row colour counter
    foreach (Orders::session_get($_POST['order_id'])->line_items as $line_no => $line) {
      if ($line->quantity == $line->qty_done) {
        continue; // this line was fully credited/removed
      }
      alt_table_row_color($k);
      //	Item_UI::status_cell($line->stock_id); alternative view
      label_cell($line->stock_id);
      text_cells(NULL, 'Line' . $line_no . 'Desc', $line->description, 30, 50);
      $dec = Item::qty_dec($line->stock_id);
      qty_cell($line->quantity, FALSE, $dec);
      label_cell($line->units);
      amount_cells(NULL, 'Line' . $line_no, Num::format($line->qty_dispatched, $dec), NULL, NULL, $dec);
      $line_total = ($line->qty_dispatched * $line->price * (1 - $line->discount_percent));
      amount_cell($line->price);
      percent_cell($line->discount_percent * 100);
      amount_cell($line_total);
      end_row();
    }
    if (!Validation::is_num('ChargeFreightCost')) {
      $_POST['ChargeFreightCost'] = Num::price_format(Orders::session_get($_POST['order_id'])->freight_cost);
    }
    $colspan = 7;
    start_row();
    label_cell(_("Credit Shipping Cost"), "colspan=$colspan class='right'");
    small_amount_cells(NULL, "ChargeFreightCost", Num::price_format(get_post('ChargeFreightCost', 0)));
    end_row();
    $inv_items_total = Orders::session_get($_POST['order_id'])->get_items_total_dispatch();
    $display_sub_total = Num::price_format($inv_items_total + Validation::input_num('ChargeFreightCost'));
    label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan class='right'", "class='right'");
    $taxes = Orders::session_get($_POST['order_id'])->get_taxes(Validation::input_num('ChargeFreightCost'));
    $tax_total = Tax::edit_items($taxes, $colspan, Orders::session_get($_POST['order_id'])->tax_included);
    $display_total = Num::price_format(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
    label_row(_("Credit Note Total"), $display_total, "colspan=$colspan class='right'", "class='right'");
    end_table();
    Display::div_end();
  }

  function display_credit_options() {
    echo "<br>";
    if (isset($_POST['_CreditType_update'])) {
      Ajax::i()->activate('options');
    }
    Display::div_start('options');
    start_table('tablestyle2');
    Sales_Credit::row(_("Credit Note Type"), 'CreditType', NULL, TRUE);
    if ($_POST['CreditType'] == "Return") {
      /*if the credit note is a return of goods then need to know which location to receive them into */
      if (!isset($_POST['location'])) {
        $_POST['location'] = Orders::session_get($_POST['order_id'])->location;
      }
      Inv_Location::row(_("Items Returned to Location"), 'location', $_POST['location']);
    }
    else {
      /* the goods are to be written off to somewhere */
      GL_UI::all_row(_("Write off the cost of the items to"), 'WriteOffGLCode', NULL);
    }
    textarea_row(_("Memo"), "CreditText", NULL, 51, 3);
    echo "</table>";
    Display::div_end();
  }


