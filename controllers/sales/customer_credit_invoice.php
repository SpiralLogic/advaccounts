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
  JS::open_window(900, 500);
  if (isset($_GET[Orders::MODIFY_CREDIT])) {
    $_SESSION['page_title'] = sprintf(_("Modifying Credit Invoice # %d."), $_GET[Orders::MODIFY_CREDIT]);
    $help_context           = "Modifying Credit Invoice";
  } elseif (isset($_GET['InvoiceNumber'])) {
    $page_title = _($help_context = "Credit all or part of an Invoice");
  } else {
    $page_title = "Credit Invoice";
  }
  Page::start($page_title, SA_SALESCREDITINV);
  if (isset($_GET[ADDED_ID])) {
    $credit_no  = $_GET[ADDED_ID];
    $trans_type = ST_CUSTCREDIT;
    Event::success(_("Credit Note has been processed"));
    Display::note(Debtor::trans_view($trans_type, $credit_no, _("&View This Credit Note")), 0, 0);
    Display::note(Reporting::print_doc_link($credit_no, _("&Print This Credit Note"), true, $trans_type), 1);
    Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")), 1);
    Page::footer_exit();
  } elseif (isset($_GET[UPDATED_ID])) {
    $credit_no  = $_GET[UPDATED_ID];
    $trans_type = ST_CUSTCREDIT;
    Event::success(_("Credit Note has been updated"));
    Display::note(Debtor::trans_view($trans_type, $credit_no, _("&View This Credit Note")), 0, 0);
    Display::note(Reporting::print_doc_link($credit_no, _("&Print This Credit Note"), true, $trans_type), 1);
    Display::note(GL_UI::view($trans_type, $credit_no, _("View the GL &Journal Entries for this Credit Note")), 1);
    Page::footer_exit();
  }
  if (isset($_GET['InvoiceNumber']) && $_GET['InvoiceNumber'] > 0) {
    $ci                = new Sales_Order(ST_SALESINVOICE, $_GET['InvoiceNumber'], true);
    $ci->trans_type    = ST_CUSTCREDIT;
    $ci->src_docs      = $ci->trans_no;
    $ci->src_date      = $ci->document_date;
    $ci->trans_no      = 0;
    $ci->document_date = Dates::new_doc_date();
    $ci->reference     = Ref::get_next(ST_CUSTCREDIT);
    for ($line_no = 0; $line_no < count($ci->line_items); $line_no++) {
      $ci->line_items[$line_no]->qty_dispatched = '0';
    }
    copy_from_credit($ci);
  } elseif (isset($_GET[Orders::MODIFY_CREDIT]) && $_GET[Orders::MODIFY_CREDIT] > 0) {
    $ci = new Sales_Order(ST_CUSTCREDIT, $_GET[Orders::MODIFY_CREDIT]);
    copy_from_credit($ci);
  } elseif (!Sales_Order::active()) {
    /* This page can only be called with an invoice number for crediting*/
    Event::error(_("This page can only be opened if an invoice has been selected for crediting."));
    Page::footer_exit();
  } elseif (!check_quantities()) {
    Event::error(_("Selected quantity cannot be less than zero nor more than quantity not credited yet."));
  }
  if (isset($_POST['ProcessCredit']) && can_process()) {
    $new_credit = (Orders::session_get($_POST['order_id'])->trans_no == 0);
    if (!isset($_POST['WriteOffGLCode'])) {
      $_POST['WriteOffGLCode'] = 0;
    }
    copy_to_credit();
    if ($new_credit) {
      Dates::new_doc_date(Orders::session_get($_POST['order_id'])->document_date);
    }
    $credit    = Orders::session_get($_POST['order_id']);
    $credit_no = $credit->write($_POST['WriteOffGLCode']);
    Orders::session_delete($credit);
    if ($new_credit) {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$credit_no");
    } else {
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "UpdatedID=$credit_no");
    }
  }
  if (isset($_POST['location'])) {
    Orders::session_get($_POST['order_id'])->location = $_POST['location'];
  }
  if (isset($_POST[Orders::CANCEL_CHANGES])) {
    $order    = Orders::session_get($_POST['order_id']);
    $type     = $order->trans_type;
    $order_no = key($order->trans_no);
    Orders::session_delete($_POST['order_id']);
    create_order($type, $order_no);
  }
  if (Input::post('Update')) {
    Ajax::i()->activate('credit_items');
  }
  display_credit_items();
  display_credit_options();
  Form::submitCenterBegin('Update', _("Update"), _('Update credit value for quantities entered'), false, ICON_UPDATE);
  Form::submitCenterInsert(Orders::CANCEL_CHANGES, _("Cancel Changes"), _("Revert this document entry back to its former state."));
  Form::submitCenterEnd('ProcessCredit', _("Process Credit Note"), true, '', 'default');
  Page::end();
  /**
   * @return int
   */
  function check_quantities()
  {
    $ok = 1;
    foreach (Orders::session_get($_POST['order_id'])->line_items as $line_no => $itm) {
      if ($itm->quantity == $itm->qty_done) {
        continue; // this line was fully credited/removed
      }
      if (isset($_POST['Line' . $line_no])) {
        if (Validation::post_num('Line' . $line_no, 0, $itm->quantity)) {
          Orders::session_get($_POST['order_id'])->line_items[$line_no]->qty_dispatched = Validation::input_num('Line' . $line_no);
        }
      } else {
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
  function can_process()
  {
    if (!Dates::is_date($_POST['CreditDate'])) {
      Event::error(_("The entered date is invalid."));
      JS::set_focus('CreditDate');

      return false;
    } elseif (!Dates::is_date_in_fiscalyear($_POST['CreditDate'])) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('CreditDate');

      return false;
    }
    if (Orders::session_get($_POST['order_id'])->trans_no == 0) {
      if (!Ref::is_valid($_POST['ref'])) {
        Event::error(_("You must enter a reference."));
        JS::set_focus('ref');

        return false;
      }
      if (!Ref::is_new($_POST['ref'], ST_CUSTCREDIT)) {
        $_POST['ref'] = Ref::get_next(ST_CUSTCREDIT);
      }
    }
    if (!Validation::post_num('ChargeFreightCost', 0)) {
      Event::error(_("The entered shipping cost is invalid or less than zero."));
      JS::set_focus('ChargeFreightCost');

      return false;
    }
    if (!check_quantities()) {
      Event::error(_("Selected quantity cannot be less than zero nor more than quantity not credited yet."));

      return false;
    }

    return true;
  }

  function copy_to_credit()
  {
    $order                = Orders::session_get($_POST['order_id']);
    $order->ship_via      = $_POST['ShipperID'];
    $order->freight_cost  = Validation::input_num('ChargeFreightCost');
    $order->document_date = $_POST['CreditDate'];
    $order->location      = $_POST['location'];
    $order->Comments      = $_POST['CreditText'];
    if ($order->trans_no == 0) {
      $order->reference = $_POST['ref'];
    }
  }

  /**
   * @param $order
   */
  function copy_from_credit($order)
  {
    $order                      = Sales_Order::check_edit_conflicts($order);
    $_POST['ShipperID']         = $order->ship_via;
    $_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
    $_POST['CreditDate']        = $order->document_date;
    $_POST['location']          = $order->location;
    $_POST['CreditText']        = $order->Comments;
    $_POST['order_id']          = $order->order_id;
    $_POST['ref']               = $order->reference;
    Orders::session_set($order);
  }

  function display_credit_items()
  {
    Form::start();
    Form::hidden('order_id');
    Table::start('tablestyle2 width90 pad5');
    echo "<tr><td>"; // outer table
    Table::start('tablestyle width100');
    Row::start();
    Cell::labels(_("Customer"), Orders::session_get($_POST['order_id'])->customer_name, "class='tablerowhead'");
    Cell::labels(_("Branch"), Sales_Branch::get_name(Orders::session_get($_POST['order_id'])->Branch), "class='tablerowhead'");
    Cell::labels(_("Currency"), Orders::session_get($_POST['order_id'])->customer_currency, "class='tablerowhead'");
    Row::end();
    Row::start();
    if (Orders::session_get($_POST['order_id'])->trans_no == 0) {
       Form::refCells(_("Reference"), 'ref', '', null, "class='tablerowhead'");
    } else {
      Cell::labels(_("Reference"), Orders::session_get($_POST['order_id'])->reference, "class='tablerowhead'");
    }
    Cell::labels(_("Crediting Invoice"), Debtor::trans_view(ST_SALESINVOICE, array_keys(Orders::session_get($_POST['order_id'])->src_docs)), "class='tablerowhead'");
    if (!isset($_POST['ShipperID'])) {
      $_POST['ShipperID'] = Orders::session_get($_POST['order_id'])->ship_via;
    }
    Cell::label(_("Shipping Company"), "class='tablerowhead'");
    Sales_UI::shippers_cells(null, 'ShipperID', $_POST['ShipperID']);
    //	if (!isset($_POST['sales_type_id']))
    //	 $_POST['sales_type_id'] = Orders::session_get($_POST['order_id'])->sales_type;
    //	Cell::label(_("Sales Type"), "class='tablerowhead'");
    //	Sales_Type::cells(null, 'sales_type_id', $_POST['sales_type_id']);
    Row::end();
    Table::end();
    echo "</td><td>"; // outer table
    Table::start('tablestyle width100');
    Row::label(_("Invoice Date"), Orders::session_get($_POST['order_id'])->src_date, "class='tablerowhead'");
     Form::dateRow(_("Credit Note Date"), 'CreditDate', '', Orders::session_get($_POST['order_id'])->trans_no == 0, 0, 0, 0, "class='tablerowhead'");
    Table::end();
    echo "</td></tr>";
    Table::end(1); // outer table
    Display::div_start('credit_items');
    Table::start('tablestyle grid width90');
    $th = array(
      _("Item Code"),
      _("Item Description"),
      _("Invoiced Quantity"),
      _("Units"),
      _("Credit Quantity"),
      _("Price"),
      _("Discount %"),
      _("Total")
    );
    Table::header($th);
    $k = 0; //row colour counter
    foreach (Orders::session_get($_POST['order_id'])->line_items as $line_no => $line) {
      if ($line->quantity == $line->qty_done) {
        continue; // this line was fully credited/removed
      }
      //	Item_UI::status_cell($line->stock_id); alternative view
      Cell::label($line->stock_id);
       Form::textCells(null, 'Line' . $line_no . 'Desc', $line->description, 30, 50);
      $dec = Item::qty_dec($line->stock_id);
      Cell::qty($line->quantity, false, $dec);
      Cell::label($line->units);
       Form::amountCells(null, 'Line' . $line_no, Num::format($line->qty_dispatched, $dec), null, null, $dec);
      $line_total = ($line->qty_dispatched * $line->price * (1 - $line->discount_percent));
      Cell::amount($line->price);
      Cell::percent($line->discount_percent * 100);
      Cell::amount($line_total);
      Row::end();
    }
    if (!Validation::post_num('ChargeFreightCost')) {
      $_POST['ChargeFreightCost'] = Num::price_format(Orders::session_get($_POST['order_id'])->freight_cost);
    }
    $colspan = 7;
    Row::start();
    Cell::label(_("Credit Shipping Cost"), "colspan=$colspan class='right'");
     Form::amountCellsSmall(null, "ChargeFreightCost", Num::price_format(Input::post('ChargeFreightCost',null,0)));
    Row::end();
    $inv_items_total   = Orders::session_get($_POST['order_id'])->get_items_total_dispatch();
    $display_sub_total = Num::price_format($inv_items_total + Validation::input_num('ChargeFreightCost'));
    Row::label(_("Sub-total"), $display_sub_total, "colspan=$colspan class='right'", "class='right'");
    $taxes         = Orders::session_get($_POST['order_id'])->get_taxes(Validation::input_num('ChargeFreightCost'));
    $tax_total     = Tax::edit_items($taxes, $colspan, Orders::session_get($_POST['order_id'])->tax_included);
    $display_total = Num::price_format(($inv_items_total + Validation::input_num('ChargeFreightCost') + $tax_total));
    Row::label(_("Credit Note Total"), $display_total, "colspan=$colspan class='right'", "class='right'");
    Table::end();
    Display::div_end();
  }

  function display_credit_options()
  {
    echo "<br>";
    if (isset($_POST['_CreditType_update'])) {
      Ajax::i()->activate('options');
    }
    Display::div_start('options');
    Table::start('tablestyle2');
    Sales_Credit::row(_("Credit Note Type"), 'CreditType', null, true);
    if ($_POST['CreditType'] == "Return") {
      /*if the credit note is a return of goods then need to know which location to receive them into */
      if (!isset($_POST['location'])) {
        $_POST['location'] = Orders::session_get($_POST['order_id'])->location;
      }
      Inv_Location::row(_("Items Returned to Location"), 'location', $_POST['location']);
    } else {
      /* the goods are to be written off to somewhere */
      GL_UI::all_row(_("Write off the cost of the items to"), 'WriteOffGLCode', null);
    }
     Form::textareaRow(_("Memo"), "CreditText", null, 51, 3);
    echo "</table>";
    Display::div_end();
  }

