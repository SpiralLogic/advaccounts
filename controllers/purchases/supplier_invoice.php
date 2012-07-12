<?php
  use ADV\App\Page;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class SupplierInvoice extends \ADV\App\Controller\Base
  {

    /** @var Creditor_Trans */
    protected $trans;
    protected $supplier_id;
    protected function before() {
      $this->JS->_openWindow(900, 500);
      $this->trans             = Creditor_Trans::i();
      $this->trans->is_invoice = true;
      if (isset($_POST['ClearFields'])) {
        $this->clearFields();
      }
      if (isset($_POST['Cancel'])) {
        $this->cancelInvoice();
      }
      $this->supplier_id = $this->trans->supplier_id ? : Input::getPost('supplier_id', Input::NUMERIC, null);
      if (isset($_POST['AddGLCodeToTrans'])) {
        $this->addGlCodesToTrans();
      }
      if (isset($_POST['PostInvoice'])) {
        $this->postInvoice();
      }
      $id = Forms::findPostPrefix('grn_item_id');
      if ($id != -1) {
        $this->commitItemData($id);
      }
      if (isset($_POST['InvGRNAll'])) {
        $this->invGrnAll();
      }
      if (Input::post('PONumber')) {
        $this->Ajax->_activate('grn_items');
        $this->Ajax->_activate('inv_tot');
      }
      $this->checkDelete();
      if (isset($_POST['go'])) {
        $this->go();
      }
    }
    protected function index() {
      Page::start(_($help_context = "Enter Supplier Invoice"), SA_SUPPLIERINVOICE);
      if (isset($_GET[ADDED_ID])) {
        $this->pageComplete();
      }
      Forms::start();
      Purch_Invoice::header($this->trans);
      if ($this->trans) {
        $this->Session->_removeGlobal('creditor', 'delivery_po');
      }
      if (!$this->supplier_id) {
        Event::warning(_("There is no supplier selected."));
      } else {
        Purch_GRN::display_items($this->trans, 1);
        Purch_GLItem::display_items($this->trans, 1);
        Display::div_start('inv_tot');
        Purch_Invoice::totals($this->trans);
        Display::div_end();
      }
      if (Input::post('AddGLCodeToTrans')) {
        $this->Ajax->_activate('inv_tot');
      }
      Display::br();
      Forms::submitCenterBegin('Cancel', _("Cancel Invoice"));
      Forms::submitCenterEnd('PostInvoice', _("Enter Invoice"), '', 'default');
      Display::br();
      Forms::end();
      $this->addJS();
      Page::end(true);
    }
    protected function pageComplete() {
      $invoice_no = $_GET[ADDED_ID];
      $trans_type = ST_SUPPINVOICE;
      echo "<div class='center'>";
      Event::success(_("Supplier " . $_SESSION['history'][ST_SUPPINVOICE] . "invoice has been processed."));
      Display::note(GL_UI::viewTrans($trans_type, $invoice_no, _("View this Invoice")));
      Display::link_no_params("/purchases/inquiry/po_search.php", _("Purchase Order Maintainants"));
      Display::link_params($_SERVER['DOCUMENT_URI'], _("Enter Another Invoice"), "New=1");
      Display::link_no_params("/purchases/supplier_payment.php", _("Entry supplier &payment for this invoice"));
      Display::link_no_params("/purchases/allocations/supplier_allocation_main.php", _("Allocate a payment to this invoice."));
      Display::note(GL_UI::view($trans_type, $invoice_no, _("View the GL Journal Entries for this Invoice")), 1);
      Display::link_params("/system/attachments.php", _("Add an Attachment"), "filterType=$trans_type&trans_no=$invoice_no");
      Page::footer_exit();
    }
    protected function addGlCodesToTrans() {
      $this->Ajax->_activate('gl_items');
      $input_error = false;
      $sql         = "SELECT account_code, account_name FROM chart_master WHERE account_code=" . DB::escape($_POST['gl_code']);
      $result      = DB::query($sql, "get account information");
      if (DB::numRows($result) == 0) {
        Event::error(_("The account code entered is not a valid code, this line cannot be added to the transaction."));
        $this->JS->_setFocus('gl_code');
        $input_error = true;
      } else {
        $myrow       = DB::fetchRow($result);
        $gl_act_name = $myrow[1];
        if (!Validation::post_num('amount')) {
          Event::error(_("The amount entered is not numeric. This line cannot be added to the transaction."));
          $this->JS->_setFocus('amount');
          $input_error = true;
        }
      }
      if (!Tax_Types::is_tax_gl_unique(Input::post('gl_code'))) {
        Event::error(_("Cannot post to GL account used by more than one tax type."));
        $this->JS->_setFocus('gl_code');
        $input_error = true;
      }
      if ($input_error == false) {
        $this->trans->add_gl_codes_to_trans($_POST['gl_code'], $gl_act_name, null, null, Validation::input_num('amount'), $_POST['memo_']);
        $taxexists = false;
        foreach ($this->trans->gl_codes as &$gl_item) {
          if ($gl_item->gl_code == 2430) {
            $taxexists = true;
            $gl_item->amount += Validation::input_num('amount') * .1;
            break;
          }
        }
        if (!$taxexists) {
          $this->trans->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, Validation::input_num('amount') * .1, 'GST TAX Paid');
        }
        $this->JS->_setFocus('gl_code');
      }
    }
    protected function checkDelete() {
      $id3 = Forms::findPostPrefix(MODE_DELETE);
      if ($id3 != -1) {
        $this->trans->remove_grn_from_trans($id3);
        $this->Ajax->_activate('grn_items');
        $this->Ajax->_activate('inv_tot');
      }
      $id4 = Forms::findPostPrefix('Delete2');
      if ($id4 != -1) {
        if (!isset($taxtotal)) {
          $taxtotal = 0;
        }
        $this->trans->remove_gl_codes_from_trans($id4);
        $taxrecord = null;
        foreach ($this->trans->gl_codes as $key => $gl_item) {
          if ($gl_item->gl_code == 2430) {
            $taxrecord = $key;
            continue;
          }
          $taxtotal += $gl_item->amount;
        }
        if (!is_null($taxrecord)) {
          $this->trans->gl_codes[$taxrecord]->amount = $taxtotal * .1;
        }
        unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
        $this->Ajax->_activate('gl_items');
        $this->JS->_setFocus('gl_code');
        $this->Ajax->_activate('gl_items');
        $this->Ajax->_activate('inv_tot');
      }
      $id2 = -1;
      if (User::i()->hasAccess(SA_GRNDELETE)) {
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
          Inv_Movement::add(ST_SUPPRECEIVE, $myrow["item_code"], $myrow['grn_batch_id'], $grn['loc_code'], Dates::sqlToDate($grn["delivery_date"]), "", -$myrow["QtyOstdg"], $myrow['std_cost_unit'], $grn["supplier_id"], 1, $myrow['unit_price']);
          DB::commit();
          Event::notice(sprintf(_('All yet non-invoiced items on delivery line # %d has been removed.'), $id2));
          $this->Ajax->_activate('grn_items');
          $this->Ajax->_activate('inv_tot');
        }
      }
    }
    protected function postInvoice() {
      Purch_Invoice::copy_to_trans($this->trans);
      if (!$this->checkData()) {
        return;
      }
      if (Input::post('ChgTax', null, 0) != 0) {
        $taxexists = false;
        foreach ($this->trans->gl_codes as &$gl_item) {
          if ($gl_item->gl_code == 2430) {
            $taxexists = true;
            $gl_item->amount += Input::post('ChgTax');
            break;
          }
        }
        if (!$taxexists) {
          $this->trans->add_gl_codes_to_trans(2430, 'GST Paid', 0, 0, Input::post('ChgTax'), 'GST Correction');
        }
      }
      if (Input::post('ChgTotal', null, 0) != 0) {
        $this->trans->add_gl_codes_to_trans(DB_Company::get_pref('default_cogs_act'), 'Cost of Goods Sold', 0, 0, Input::post('ChgTotal'), 'Rounding Correction');
      }
      $invoice_no                          = Purch_Invoice::add($this->trans);
      $_SESSION['history'][ST_SUPPINVOICE] = $this->trans->reference;
      $this->Session->_setGlobal('creditor', $this->supplier_id, '');
      $this->trans->clear_items();
      Creditor_Trans::killInstance();
      Display::meta_forward($_SERVER['DOCUMENT_URI'], "AddedID=$invoice_no");
    }
    //	GL postings are often entered in the same form to two accounts
    // so fileds are cleared only on user demand.
    //
    function clearFields() {
      unset($_POST['gl_code'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['amount'], $_POST['memo_'], $_POST['AddGLCodeToTrans']);
      $this->Ajax->_activate('gl_items');
      $this->JS->_setFocus('gl_code');
    }
    protected function after() {
      $this->Session->_set('Creditor_Trans', $this->trans);
    }
    /**
     * @internal param $prefix
     * @return bool|mixed
     */
    protected function runValidation() {
      Validation::check(Validation::SUPPLIERS, _("There are no suppliers defined in the system."));
    }
    /**
     * @return bool
     */
    protected function checkData() {
      if (!$this->trans->is_valid_trans_to_post()) {
        Event::error(_("The invoice cannot be processed because the there are no items or values on the invoice. Invoices are expected to have a charge."));
        return false;
      }
      if (!Ref::is_valid($this->trans->reference)) {
        Event::error(_("You must enter an invoice reference."));
        $this->JS->_setFocus('reference');
        return false;
      }
      if (!Ref::is_new($this->trans->reference, ST_SUPPINVOICE)) {
        $this->trans->reference = Ref::get_next(ST_SUPPINVOICE);
      }
      if (!Ref::is_valid($this->trans->supplier_reference)) {
        Event::error(_("You must enter a supplier's invoice reference."));
        $this->JS->_setFocus('supplier_reference');
        return false;
      }
      if (!Dates::isDate($this->trans->tran_date)) {
        Event::error(_("The invoice as entered cannot be processed because the invoice date is in an incorrect format."));
        $this->JS->_setFocus('trans_date');
        return false;
      } elseif (!Dates::isDateInFiscalYear($this->trans->tran_date)) {
        Event::error(_("The entered date is not in fiscal year."));
        $this->JS->_setFocus('trans_date');
        return false;
      }
      if (!Dates::isDate($this->trans->due_date)) {
        Event::error(_("The invoice as entered cannot be processed because the due date is in an incorrect format."));
        $this->JS->_setFocus('due_date');
        return false;
      }
      $sql    = "SELECT Count(*) FROM creditor_trans WHERE supplier_id=" . DB::escape($this->trans->supplier_id) . " AND supplier_reference=" . DB::escape($_POST['supplier_reference']) . " AND ov_amount!=0"; // ignore voided invoice references
      $result = DB::query($sql, "The sql to check for the previous entry of the same invoice failed");
      $myrow  = DB::fetchRow($result);
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
    protected function checkItemData($n) {
      if (!Validation::post_num('this_quantity_inv' . $n, 0) || Validation::input_num('this_quantity_inv' . $n) == 0) {
        Event::error(_("The quantity to invoice must be numeric and greater than zero."));
        $this->JS->_setFocus('this_quantity_inv' . $n);
        return false;
      }
      if (!Validation::post_num('ChgPrice' . $n)) {
        Event::error(_("The price is not numeric."));
        $this->JS->_setFocus('ChgPrice' . $n);
        return false;
      }
      if (!Validation::post_num('ExpPrice' . $n)) {
        Event::error(_("The price is not numeric."));
        $this->JS->_setFocus('ExpPrice' . $n);
        return false;
      }
      $margin = DB_Company::get_pref('po_over_charge');
      if (Config::get('purchases.valid_charged_to_delivered_price') == true && $margin != 0) {
        if ($_POST['order_price' . $n] != Validation::input_num('ChgPrice' . $n)) {
          if (Input::post('order_price' . $n, Input::NUMERIC, 0) != 0 && Validation::input_num('ChgPrice' . $n) / $_POST['order_price' . $n] > (1 + ($margin / 100))) {
            if (!$this->Session->_get('err_over_charge')) {
              Event::warning(_("The price being invoiced is more than the purchase order price by more than the allowed over-charge percentage. The system is set up to prohibit this. See the system administrator to modify the set up parameters if necessary.") . _("The over-charge percentage
              allowance is :") . $margin . "%");
              $this->JS->_setFocus('ChgPrice' . $n);
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
          $this->JS->_setFocus('this_quantity_inv' . $n);
          return false;
        }
      }
      return true;
    }
    /**
     * @param $n
     */
    protected function commitItemData($n) {
      if ($this->checkItemData($n)) {
        if (Validation::input_num('this_quantity_inv' . $n) >= ($_POST['qty_recd' . $n] - $_POST['prev_quantity_inv' . $n])) {
          $complete = true;
        } else {
          $complete = false;
        }
        $_SESSION['err_over_charge'] = false;
        $this->trans
          ->add_grn_to_trans($n, $_POST['po_detail_item' . $n], $_POST['item_code' . $n], $_POST['description' . $n], $_POST['qty_recd' . $n], $_POST['prev_quantity_inv' . $n], Validation::input_num('this_quantity_inv' . $n), $_POST['order_price' . $n], Validation::input_num('ChgPrice' . $n),
          $complete, $_POST['std_cost_unit' . $n], "", Validation::input_num('ChgDiscount' . $n), Validation::input_num('ExpPrice' . $n));
      }
      $this->Ajax->_activate('grn_items');
      $this->Ajax->_activate('inv_tot');
    }
    protected function cancelInvoice() {
      $this->trans->clear_items();
      unset($_SESSION['delivery_po']);
      unset($_POST['PONumber']);
      unset($_POST['supplier_id']);
      unset($_POST['supplier']);
      Creditor_Trans::killInstance();
      $this->trans = Creditor_Trans::i(true);
      $this->Ajax->_activate('_page_body');
    }
    protected function go() {
      $this->Ajax->_activate('gl_items');
      GL_QuickEntry::show_menu($this->trans, $_POST['qid'], Validation::input_num('total_amount'), QE_SUPPINV);
      $_POST['total_amount'] = Num::priceFormat(0);
      $this->Ajax->_activate('total_amount');
      $this->Ajax->_activate('inv_tot');
    }
    protected function invGrnAll() {
      foreach ($_POST as $postkey => $postval) {
        if (strpos($postkey, "qty_recd") === 0) {
          $id = substr($postkey, strlen("qty_recd"));
          $id = (int) $id;
          $this->commitItemData($id);
        }
      }
      $this->Ajax->_activate('_page_body');
    }
    protected function addJS() {
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
               } else if (fv.qty > 0 && fv.price > 0) {
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
      $this->JS->_onload($js);
    }
  }

  new SupplierInvoice();
