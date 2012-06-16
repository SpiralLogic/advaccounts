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
  Page::start(_($help_context = "Enter Supplier Invoice"), SA_SUPPLIERINVOICE);
  Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
  if (isset($_GET[ADDED_ID])) {
    $invoice_no = $_GET[ADDED_ID];
    $trans_type = ST_SUPPINVOICE;
    echo "<div class='center'>";
    Event::success(_("Supplier " . $_SESSION['history'][ST_SUPPINVOICE] . "invoice has been processed."));
    Display::note(GL_UI::trans_view($trans_type, $invoice_no, _("View this Invoice")));
    Display::link_no_params("/purchases/inquiry/po_search.php", _("Purchase Order Maintainants"));
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Invoice"), "New=1");
    Display::link_no_params("/purchases/supplier_payment.php", _("Entry supplier &payment for this invoice"));
    Display::link_no_params("/purchases/allocations/supplier_allocation_main.php", _("Allocate a payment to this invoice."));
    Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL Journal Entries for this Invoice")), 1);
    Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
    Page::footer_exit();
  }
  if (isset($_GET['New'])) {
    Creditor_Trans::i(true);
    Creditor_Trans::i()->is_invoice = true;
    $supplier_id                    = Input::get('supplier_id', Input::NUMERIC);
    if ($supplier_id) {
      Session::i()->setGlobal('creditor', $supplier_id);
    }
  }
  //	GL postings are often entered in the same form to two accounts
  // so fileds are cleared only on user demand.
  //
  if (isset($_POST['ClearFields'])) {
    unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
    Ajax::i()->activate('gl_items');
    JS::set_focus('gl_code');
  }
  if (isset($_POST['AddGLCodeToTrans'])) {
    Ajax::i()->activate('gl_items');
    $input_error = false;
    $sql         = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DB::escape($_POST['gl_code']);
    $result      = DB::query($sql, "get account information");
    if (DB::num_rows($result) == 0) {
      Event::error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
      JS::set_focus('gl_code');
      $input_error = true;
    } else {
      $myrow       = DB::fetch_row($result);
      $gl_act_name = $myrow[1];
      if (!Validation::post_num('amount')) {
        Event::error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
        JS::set_focus('amount');
        $input_error = true;
      }
    }
    if (!Tax_Types::is_tax_gl_unique(Input::post('gl_code'))) {
      Event::error(_("Cannot post to GL account used by more than one tax type."));
      JS::set_focus('gl_code');
      $input_error = true;
    }
    if ($input_error == false) {
      Creditor_Trans::i()
        ->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name, null, null, Validation::input_num('amount'), $_POST['memo_']);
      $taxexists = false;
      foreach (Creditor_Trans::i()->gl_codes as &$gl_item) {
        if ($gl_item->gl_code == 2430) {
          $taxexists = true;
          $gl_item->amount += Validation::input_num('amount') * .1;
          break;
        }
      }
      if (!$taxexists) {
        Creditor_Trans::i()->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, Validation::input_num('amount') * .1, 'GST TAX Paid');
      }
      JS::set_focus('gl_code');
    }
  }
  if (isset($_POST['PostInvoice'])) {
    Purch_Invoice::copy_to_trans(Creditor_Trans::i());
    if (!check_data()) {
      return;
    }
    if (Input::post('ChgTax',null,0) != 0) {
      $taxexists = false;
      foreach (Creditor_Trans::i()->gl_codes as &$gl_item) {
        if ($gl_item->gl_code == 2430) {
          $taxexists = true;
          $gl_item->amount += Input::post('ChgTax');
          break;
        }
      }
      if (!$taxexists) {
        Creditor_Trans::i()->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, Input::post('ChgTax'), 'GST Correction');
      }
    }
    if (Input::post('ChgTotal',null,0) != 0) {
      Creditor_Trans::i()
        ->add_gl_codes_to_trans(DB_Company::get_pref('default_cogs_act'), 'Cost of Goods Sold', 0, 0, Input::post('ChgTotal'), 'Rounding Correction');
    }
    $invoice_no                          = Purch_Invoice::add(Creditor_Trans::i());
    $_SESSION['history'][ST_SUPPINVOICE] = Creditor_Trans::i()->reference;
    Session::i()->setGlobal('creditor', $_POST['supplier_id'], '');
    Creditor_Trans::i()->clear_items();
    Creditor_Trans::killInstance();
    Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$invoice_no");
  }
  $id = Forms::findPostPrefix('grn_item_id');
  if ($id != -1) {
    commit_item_data($id);
  }
  if (isset($_POST['InvGRNAll'])) {
    foreach ($_POST as $postkey => $postval) {
      if (strpos($postkey, "qty_recd") === 0) {
        $id = substr($postkey, strlen("qty_recd"));
        $id = (int) $id;
        commit_item_data($id);
      }
    }
  }
  $id3 = Forms::findPostPrefix(MODE_DELETE);
  if ($id3 != -1) {
    Creditor_Trans::i()->remove_grn_from_trans($id3);
    Ajax::i()->activate('grn_items');
    Ajax::i()->activate('inv_tot');
  }
  $id4 = Forms::findPostPrefix('Delete2');
  if ($id4 != -1) {
    if (!isset($taxtotal)) {
      $taxtotal = 0;
    }
    Creditor_Trans::i()->remove_gl_codes_from_trans($id4);
    foreach (Creditor_Trans::i()->gl_codes as $key => $gl_item) {
      if ($gl_item->gl_code == 2430) {
        $taxrecord = $key;
        continue;
      }
      $taxtotal += $gl_item->amount;
    }
    if (!is_null($taxrecord)) {
      Creditor_Trans::i()->gl_codes[$taxrecord]->amount = $taxtotal * .1;
    }
    unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
    Ajax::i()->activate('gl_items');
    JS::set_focus('gl_code');
    Ajax::i()->activate('gl_items');
    Ajax::i()->activate('inv_tot');
  }
  $id2 = -1;
  if (User::i()->can_access(SA_GRNDELETE)) {
    $id2 = Forms::findPostPrefix('void_item_id');
    if ($id2 != -1) {
      DB::begin();
      $myrow = Purch_GRN::get_item($id2);
      $grn   = Purch_GRN::get_batch($myrow['grn_batch_id']);
      $sql
             = "UPDATE purch_order_details
            SET quantity_received = qty_invoiced, quantity_ordered = qty_invoiced WHERE po_detail_item = " . $myrow["po_detail_item"];
      DB::query($sql, "The quantity invoiced of the purchase order line could not be updated");
      $sql
        = "UPDATE grn_items
         SET qty_recd = quantity_inv WHERE id = " . $myrow["id"];
      DB::query($sql, "The quantity invoiced off the items received record could not be updated");
      Purch_GRN::update_average_material_cost($grn["supplier_id"], $myrow["item_code"], $myrow["unit_price"], -$myrow["QtyOstdg"], Dates::today());
      Inv_Movement::add(ST_SUPPRECEIVE, $myrow["item_code"], $myrow['grn_batch_id'], $grn['loc_code'], Dates::sql2date($grn["delivery_date"]), "", -$myrow["QtyOstdg"], $myrow['std_cost_unit'], $grn["supplier_id"], 1, $myrow['unit_price']);
      DB::commit();
      Event::notice(sprintf(_('All yet non-invoiced items on delivery line # %d has been removed.'), $id2));
    }
  }
  if (isset($_POST['go'])) {
    Ajax::i()->activate('gl_items');
    GL_QuickEntry::show_menu(Creditor_Trans::i(), $_POST['qid'], Validation::input_num('total_amount'), QE_SUPPINV);
    $_POST['total_amount'] = Num::price_format(0);
    Ajax::i()->activate('total_amount');
    Ajax::i()->activate('inv_tot');
  }
  Forms::start();
  Purch_Invoice::header(Creditor_Trans::i());
  $_POST['supplier_id'] = Session::i()->getGlobal('creditor', '');
  if (Creditor_Trans::i()) {
    Session::i()->removeGlobal('creditor', 'delivery_po');
  }
  if ($_POST['supplier_id'] == ALL_TEXT) {
    Event::warning(_("There is no supplier selected."));
  } else {
    Purch_GRN::display_items(Creditor_Trans::i(), 1);
    Purch_GLItem::display_items(Creditor_Trans::i(), 1);
    Display::div_start('inv_tot');
    Purch_Invoice::totals(Creditor_Trans::i());
    Display::div_end();
  }
  if ($id != -1 || $id2 != -1) {
    Ajax::i()->activate('grn_items');
    Ajax::i()->activate('inv_tot');
  }
  if (Input::post('AddGLCodeToTrans')) {
    Ajax::i()->activate('inv_tot');
  }
  Display::br();
  Forms::submitCenter('PostInvoice', _("Enter Invoice"), true, '', 'default');
  Display::br();
  Forms::end();
  Item::addEditDialog();
  $js
    = <<<JS
         $("#wrapper").delegate('.amount','change',function() {
     var feild = $(this), ChgTax=$('[name="ChgTax"]'),ChgTotal=$('[name="ChgTotal"]'),invTotal=$('#invoiceTotal'), fields = $(this).parent().parent(), fv = {}, nodes = {
     qty: $('[name^="this_quantity"]',fields),
     price: $('[name^="ChgPrice"]',fields),
     discount: $('[name^="ChgDiscount"]',fields),
     total: $('[id^="ChgTotal"]',fields),
                        eachprice: $('[id^="Ea"]',fields)
     };
     if (fields.hasClass('grid')) {
     $.each(nodes,function(k,v) {
     if (v && v.val()) fv[k] = Number(v.val().replace(',',''));
     });
     if (feild.attr('id') == nodes.total.attr('id')) {
     if (fv.price == 0 && fv.discount==0) {
     fv.price = fv.total / fv.qty;
     } else {
     fv.discount = 100*(1-(fv.total)/(fv.price*fv.qty));
             fv.discount = Math.round(fv.discount*1)/1;
     }
     nodes.price.val(fv.price);
     nodes.discount.val(fv.discount);
     } elseif (fv.qty > 0 && fv.price > 0) {
     fv.total = fv.qty*fv.price*((100-fv.discount)/100);
     nodes.total.val(Math.round(fv.total*100)/100 );
     };
     Adv.Forms.priceFormat(nodes.eachprice.attr('id'),(fv.total/fv.qty),2,true);
     } else {
        if (feild.attr('name')=='ChgTotal' || feild.attr('name')=='ChgTax') {
        var total = Number(invTotal.data('total'));
        var ChgTax = Number(ChgTax.val().replace(',',''));
        var ChgTotal = Number(ChgTotal.val().replace(',',''));
        Adv.Forms.priceFormat(invTotal.attr('id'),total+ChgTax+ChgTotal,2,true); }
    }});
JS;
  JS::onload($js);
  Page::end();
  /**
   * @return bool
   */
  function check_data()
  {
    if (!Creditor_Trans::i()->is_valid_trans_to_post()) {
      Event::error(_("The invoice cannot be processed because the there are no items or values on the invoice. Invoices are expected to have a charge."));

      return false;
    }
    if (!Ref::is_valid(Creditor_Trans::i()->reference)) {
      Event::error(_("You must enter an invoice reference."));
      JS::set_focus('reference');

      return false;
    }
    if (!Ref::is_new(Creditor_Trans::i()->reference, ST_SUPPINVOICE)) {
      Creditor_Trans::i()->reference = Ref::get_next(ST_SUPPINVOICE);
    }
    if (!Ref::is_valid(Creditor_Trans::i()->supplier_reference)) {
      Event::error(_("You must enter a supplier's invoice reference."));
      JS::set_focus('supplier_reference');

      return false;
    }
    if (!Dates::is_date(Creditor_Trans::i()->tran_date)) {
      Event::error(_("The invoice as entered cannot be processed because the invoice date is in an incorrect format."));
      JS::set_focus('trans_date');

      return false;
    } elseif (!Dates::is_date_in_fiscalyear(Creditor_Trans::i()->tran_date)) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::set_focus('trans_date');

      return false;
    }
    if (!Dates::is_date(Creditor_Trans::i()->due_date)) {
      Event::error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
      JS::set_focus('due_date');

      return false;
    }
    $sql    = "SELECT Count(*) FROM creditor_trans WHERE supplier_id=" . DB::escape(Creditor_Trans::i()->supplier_id) . " AND supplier_reference=" . DB::escape($_POST['supplier_reference']) . " AND ov_amount!=0"; // ignore voided invoice references
    $result = DB::query($sql, "The sql to check for the previous entry of the same invoice failed");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] == 1) { /*Transaction reference already entered */
      Event::error(_("This invoice number has already been entered. It cannot be entered again. (" . $_POST['supplier_reference'] . ")"));

      return false;
    }

    return true;
  }

  /**
   * @param $n
   *
   * @return bool
   */
  function check_item_data($n)
  {
    if (!Validation::post_num('this_quantity_inv' . $n, 0) || Validation::input_num('this_quantity_inv' . $n) == 0) {
      Event::error(_("The quantity to invoice must be numeric and greater than zero."));
      JS::set_focus('this_quantity_inv' . $n);

      return false;
    }
    if (!Validation::post_num('ChgPrice' . $n)) {
      Event::error(_("The price is not numeric."));
      JS::set_focus('ChgPrice' . $n);

      return false;
    }
    if (!Validation::post_num('ExpPrice' . $n)) {
      Event::error(_("The price is not numeric."));
      JS::set_focus('ExpPrice' . $n);

      return false;
    }
    $margin = DB_Company::get_pref('po_over_charge');
    if (Config::get('purchases.valid_charged_to_delivered_price') == true && $margin != 0) {
      if ($_POST['order_price' . $n] != Validation::input_num('ChgPrice' . $n)) {
        if (Input::post('order_price' . $n, Input::NUMERIC, 0) != 0 && Validation::input_num('ChgPrice' . $n) / $_POST['order_price' . $n] > (1 + ($margin / 100))
        ) {
          if (Session::i()->err_over_charge != true) {
            Event::warning(_("The price being invoiced is more than the purchase order price by more than the allowed over-charge
                        percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") . _("The over-charge percentage allowance is :") . $margin . "%");
            JS::set_focus('ChgPrice' . $n);
            $_SESSION['err_over_charge'] = true;

            return false;
          } else {
            $_SESSION['err_over_charge'] = false;
          }
        }
      }
    }
    if (Config::get('purchases.valid_charged_to_delivered_qty') == true) {
      if (Validation::input_num('this_quantity_inv' . $n) / ($_POST['qty_recd' . $n] - $_POST['prev_quantity_inv' . $n]) > (1 + ($margin / 100))) {
        Event::error(_("The quantity being invoiced is more than the outstanding quantity by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") . _("The over-charge percentage allowance is :") . $margin . "%");
        JS::set_focus('this_quantity_inv' . $n);

        return false;
      }
    }

    return true;
  }

  /**
   * @param $n
   */
  function commit_item_data($n)
  {
    if (check_item_data($n)) {
      if (Validation::input_num('this_quantity_inv' . $n) >= ($_POST['qty_recd' . $n] - $_POST['prev_quantity_inv' . $n])) {
        $complete = true;
      } else {
        $complete = false;
      }
      $_SESSION['err_over_charge'] = false;
      Creditor_Trans::i()
        ->add_grn_to_trans($n, $_POST['po_detail_item' . $n], $_POST['item_code' . $n], $_POST['description' . $n], $_POST['qty_recd' . $n], $_POST['prev_quantity_inv' . $n], Validation::input_num('this_quantity_inv' . $n), $_POST['order_price' . $n], Validation::input_num('ChgPrice' . $n), $complete, $_POST['std_cost_unit' . $n], "", Validation::input_num('ChgDiscount' . $n), Validation::input_num('ExpPrice' . $n));
    }
  }

