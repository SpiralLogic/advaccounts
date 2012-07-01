<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  JS::openWindow(900, 500);
  Page::start(_($help_context = "Supplier Credit Note"), SA_SUPPLIERCREDIT);
  Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
  if (isset($_GET[ADDED_ID])) {
    $invoice_no = $_GET[ADDED_ID];
    $trans_type = ST_SUPPCREDIT;
    echo "<div class='center'>";
    Event::success(_("Supplier credit note has been processed."));
    Display::note(GL_UI::trans_view($trans_type, $invoice_no, _("View this Credit Note")));
    Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL Journal Entries for this Credit Note")), 1);
    Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Credit Note"), "New=1");
    Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
    Page::footer_exit();
  }
  if (isset($_GET['New'])) {
    Creditor_Trans::i(true)->is_invoice = false;
    if (isset($_GET['invoice_no'])) {
      Creditor_Trans::i()->supplier_reference = $_POST['invoice_no'] = $_GET['invoice_no'];
    }
  }
  //	GL postings are often entered in the same form to two accounts
  // so fileds are cleared only on user demand.
  //
  if (isset($_POST['ClearFields'])) {
    unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
    Ajax::activate('gl_items');
    JS::setFocus('gl_code');
  }
  if (isset($_POST['AddGLCodeToTrans'])) {
    Ajax::activate('gl_items');
    $input_error = false;
    $sql         = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DB::escape($_POST['gl_code']);
    $result      = DB::query($sql, "get account information");
    if (DB::numRows($result) == 0) {
      Event::error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
      JS::setFocus('gl_code');
      $input_error = true;
    } else {
      $myrow       = DB::fetchRow($result);
      $gl_act_name = $myrow[1];
      if (!Validation::post_num('amount')) {
        Event::error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
        JS::setFocus('amount');
        $input_error = true;
      }
    }
    if (!Tax_Types::is_tax_gl_unique(Input::post('gl_code'))) {
      Event::error(_("Cannot post to GL account used by more than one tax type."));
      JS::setFocus('gl_code');
      $input_error = true;
    }
    if ($input_error == false) {
      Creditor_Trans::i()
        ->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name, $_POST['dimension_id'], $_POST['dimension2_id'], Validation::input_num('amount'), $_POST['memo_']);
      JS::setFocus('gl_code');
    }
  }
  if (isset($_POST['PostCreditNote'])) {
    Purch_Invoice::copy_to_trans(Creditor_Trans::i());
    if (!check_data()) {
      return;
    }
    if (isset($_POST['invoice_no'])) {
      $invoice_no = Purch_Invoice::add(Creditor_Trans::i(), $_POST['invoice_no']);
    } else {
      $invoice_no = Purch_Invoice::add(Creditor_Trans::i());
    }
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
    Ajax::activate('grn_items');
    Ajax::activate('inv_tot');
  }
  $id4 = Forms::findPostPrefix('Delete2');
  if ($id4 != -1) {
    Creditor_Trans::i()->remove_gl_codes_from_trans($id4);
    unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
    JS::setFocus('gl_code');
    Ajax::activate('gl_items');
    Ajax::activate('inv_tot');
  }
  if (isset($_POST['RefreshInquiry'])) {
    Ajax::activate('grn_items');
    Ajax::activate('inv_tot');
  }
  if (isset($_POST['go'])) {
    Ajax::activate('gl_items');
    GL_QuickEntry::show_menu(Creditor_Trans::i(), $_POST['qid'], Validation::input_num('total_amount'), QE_SUPPINV);
    $_POST['total_amount'] = Num::priceFormat(0);
    Ajax::activate('total_amount');
    Ajax::activate('inv_tot');
  }
  Forms::start();
  Purch_Invoice::header(Creditor_Trans::i());
  if ($_POST['supplier_id'] != '') {
    $total_grn_value = Purch_GRN::display_items(Creditor_Trans::i(), 1);
    $total_gl_value  = Purch_GLItem::display_items(Creditor_Trans::i(), 1);
    Display::div_start('inv_tot');
    Purch_Invoice::totals(Creditor_Trans::i());
    Display::div_end();
  }
  if ($id != -1) {
    Ajax::activate('grn_items');
    Ajax::activate('inv_tot');
  }
  if (Input::post('AddGLCodeToTrans')) {
    Ajax::activate('inv_tot');
  }
  Display::br();
  Forms::submitCenter('PostCreditNote', _("Enter Credit Note"), true, '', 'default');
  Display::br();
  Forms::end();
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
    global $total_grn_value, $total_gl_value;
    if (!Creditor_Trans::i()->is_valid_trans_to_post()) {
      Event::error(_("The credit note cannot be processed because the there are no items or values on the invoice. Credit notes are expected to have a charge."));
      JS::setFocus('');

      return false;
    }
    if (!Ref::is_valid(Creditor_Trans::i()->reference)) {
      Event::error(_("You must enter an credit note reference."));
      JS::setFocus('reference');

      return false;
    }
    if (!Ref::is_new(Creditor_Trans::i()->reference, ST_SUPPCREDIT)) {
      Creditor_Trans::i()->reference = Ref::get_next(ST_SUPPCREDIT);
    }
    if (!Ref::is_valid(Creditor_Trans::i()->supplier_reference)) {
      Event::error(_("You must enter a supplier's credit note reference."));
      JS::setFocus('supplier_reference');

      return false;
    }
    if (!Dates::isDate(Creditor_Trans::i()->tran_date)) {
      Event::error(_("The credit note as entered cannot be processed because the date entered is not valid."));
      JS::setFocus('tran_date');

      return false;
    } elseif (!Dates::isDateInFiscalYear(Creditor_Trans::i()->tran_date)) {
      Event::error(_("The entered date is not in fiscal year."));
      JS::setFocus('tran_date');

      return false;
    }
    if (!Dates::isDate(Creditor_Trans::i()->due_date)) {
      Event::error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
      JS::setFocus('due_date');

      return false;
    }
    if (Creditor_Trans::i()->ov_amount < ($total_gl_value + $total_grn_value)) {
      Event::error(_("The credit note total as entered is less than the sum of the the general ledger entires (if any) and the charges for goods received. There must be a mistake somewhere, the credit note as entered will not be processed."));

      return false;
    }

    return true;
  }

  /**S
   *
   * @param $n
   *
   * @return bool
   */
  function check_item_data($n)
  {
    if (!Validation::post_num('this_quantityCredited' . $n, 0)) {
      Event::error(_("The quantity to credit must be numeric and greater than zero."));
      JS::setFocus('this_quantityCredited' . $n);

      return false;
    }
    if (!Validation::post_num('ChgPrice' . $n, 0)) {
      Event::error(_("The price is either not numeric or negative."));
      JS::setFocus('ChgPrice' . $n);

      return false;
    }

    return true;
  }

  /**
   * @param $n
   */
  function commit_item_data($n)
  {
    if (check_item_data($n)) {
      $complete = false;
      Creditor_Trans::i()
        ->add_grn_to_trans($n, $_POST['po_detail_item' . $n], $_POST['item_code' . $n], $_POST['description' . $n], $_POST['qty_recd' . $n], $_POST['prev_quantity_inv' . $n], Validation::input_num('this_quantityCredited' . $n), $_POST['order_price' . $n], Validation::input_num('ChgPrice' . $n), $complete, $_POST['std_cost_unit' . $n], "");
    }
  }

