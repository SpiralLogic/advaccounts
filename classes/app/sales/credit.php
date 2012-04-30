<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  /**
   * if ($writeoff_acc==0) return goods into $order->location
   * if src_docs!=0 => credit invoice else credit note
   */
  class Sales_Credit {

    /**
     * @static
     *
     * @param Sales_Order $credit_note
     * @param             $write_off_acc
     *
     * @return int
     */
    static public function add($credit_note, $write_off_acc) {
      $credit_invoice = is_array($credit_note->src_docs) ? reset(array_keys($credit_note->src_docs)) : $credit_note->src_docs;
      $credit_date = $credit_note->document_date;
      $tax_group_id = $credit_note->tax_group_id;
      $trans_no = $credit_note->trans_no;
      if (is_array($trans_no)) {
        $trans_no = key($trans_no);
      }
      $credit_type = $write_off_acc == 0 ? 'Return' : 'WriteOff';
      DB::begin();
      $company_data = DB_Company::get_prefs();
      $branch_data = Sales_Branch::get_accounts($credit_note->Branch);
      $credit_note_total = $credit_note->get_items_total_dispatch();
      $freight_tax = $credit_note->get_shipping_tax();
      $taxes = $credit_note->get_taxes();
      $tax_total = 0;
      foreach ($taxes as $taxitem) {
        $taxitem['Value'] = Num::round($taxitem['Value'], User::price_dec());
        $tax_total += $taxitem['Value'];
      }
      if ($credit_note->tax_included == 0) {
        $items_added_tax = $tax_total - $freight_tax;
        $freight_added_tax = $freight_tax;
      }
      else {
        $items_added_tax = 0;
        $freight_added_tax = 0;
      }
      // 2006-06-14. If the Customer Branch AR Account is set to a Bank Account,
      // the transaction will be settled at once.
      if (Bank_Account::is($branch_data['receivables_account'])) {
        $alloc = $credit_note_total + $items_added_tax + $credit_note->freight_cost + $freight_added_tax;
      }
      else {
        $alloc = 0;
      }
      //	$sales_order=$invoice->order_no;	//?
      // if (is_array($sales_order)) $sales_order = $sales_order[0]; //?
      if (!isset($credit_note->order_no)) {
        $credit_note->order_no = 0;
      }
      /*Now insert the Credit Note into the debtor_trans table with the allocations as calculated above*/
      // all amounts in debtor's currency
      $credit_no = Debtor_Trans::write(ST_CUSTCREDIT, $trans_no, $credit_note->customer_id, $credit_note->Branch, $credit_date, $credit_note->reference, $credit_note_total, 0, $items_added_tax, $credit_note->freight_cost, $freight_added_tax, $credit_note->sales_type, $credit_note->order_no,
        $credit_invoice, $credit_note->ship_via, NULL, $alloc, 0, $credit_note->dimension_id, $credit_note->dimension2_id);
      // 2008-06-14 extra $alloc, 2008-11-12 dimension_id Joe Hunt
      if ($trans_no == 0) {
        $credit_note->trans_no = array($credit_no => 0);
        Debtor_Trans::set_parent($credit_note);
      }
      else {
        DB_Comments::delete(ST_CUSTCREDIT, $credit_no);
        Sales_Allocation::void(ST_CUSTCREDIT, $credit_no, $credit_date);
        GL_Trans::void(ST_CUSTCREDIT, $credit_no, TRUE);
        Inv_Movement::void(ST_CUSTCREDIT, $credit_no);
        GL_Trans::void_tax_details(ST_CUSTCREDIT, $credit_no);
      }
      if ($credit_invoice) {
        $invoice_alloc_balance = Sales_Allocation::get_balance(ST_SALESINVOICE, $credit_invoice);
        Debtor_Trans::update_version(Debtor_Trans::get_parent_type(ST_CUSTCREDIT), $credit_note->src_docs);
        if ($invoice_alloc_balance > 0) { //the invoice is not already fully allocated
          $total = $credit_note_total + $credit_note->freight_cost + $items_added_tax + $freight_added_tax;
          $allocate_amount = ($invoice_alloc_balance > $total) ? $total : $invoice_alloc_balance;
          /*Now insert the allocation record if > 0 */
          if ($allocate_amount != 0) {
            Sales_Allocation::update(ST_SALESINVOICE, $credit_invoice, $allocate_amount);
            Sales_Allocation::update(ST_CUSTCREDIT, $credit_no, $allocate_amount); // ***
            Sales_Allocation::add($allocate_amount, ST_CUSTCREDIT, $credit_no, ST_SALESINVOICE, $credit_invoice);
            // Exchange Variations Joe Hunt 2008-09-20 ////////////////////////////////////////
            Bank::exchange_variation(ST_CUSTCREDIT, $credit_no, ST_SALESINVOICE, $credit_invoice, $credit_date, $allocate_amount, PT_CUSTOMER);
            ///////////////////////////////////////////////////////////////////////////
          }
        }
      }
      $total = 0;
      foreach ($credit_note->line_items as $credit_line) {
        if ($credit_invoice && $credit_line->qty_dispatched != $credit_line->qty_old) {
          Sales_Order::update_parent_line(11, $credit_line->src_id, ($credit_line->qty_dispatched - $credit_line->qty_old));
        }
        $line_taxfree_price = Tax::tax_free_price($credit_line->stock_id, $credit_line->price, 0, $credit_note->tax_included, $credit_note->tax_group_array);
        $line_tax = Tax::full_price_for_item($credit_line->stock_id, $credit_line->price, 0, $credit_note->tax_included, $credit_note->tax_group_array) - $line_taxfree_price;
        Debtor_TransDetail::add(ST_CUSTCREDIT, $credit_no, $credit_line->stock_id, $credit_line->description, $credit_line->qty_dispatched, $credit_line->line_price(), $line_tax, $credit_line->discount_percent, $credit_line->standard_cost, $trans_no == 0 ?
          0 : $credit_line->id);
        Sales_Credit::add_movements($credit_note, $credit_line, $credit_type, $line_taxfree_price + $line_tax, $credit_invoice);
        $total += Sales_Credit::add_gl_costs($credit_note, $credit_line, $credit_no, $credit_date, $credit_type, $write_off_acc, $branch_data);
      } /*end of credit_line loop */
      /*Post credit note transaction to GL credit debtors,
                                         debit freight re-charged and debit sales */
      if (($credit_note_total + $credit_note->freight_cost) != 0) {
        $total += Debtor_TransDetail::add_gl_trans(ST_CUSTCREDIT, $credit_no, $credit_date, $branch_data["receivables_account"], 0, 0, -($credit_note_total + $credit_note->freight_cost + $items_added_tax + $freight_added_tax), $credit_note->customer_id,
          "The total debtor GL posting for the credit note could not be inserted");
      }
      if ($credit_note->freight_cost != 0) {
        $total += Debtor_TransDetail::add_gl_trans(ST_CUSTCREDIT, $credit_no, $credit_date, $company_data["freight_act"], 0, 0, $credit_note->get_tax_free_shipping(), $credit_note->customer_id, "The freight GL posting for this credit note could not be inserted");
      }
      foreach ($taxes as $taxitem) {
        if ($taxitem['Net'] != 0) {
          $ex_rate = Bank_Currency::exchange_rate_from_home(Bank_Currency::for_debtor($credit_note->customer_id), $credit_note->document_date);
          GL_Trans::add_tax_details(ST_CUSTCREDIT, $credit_no, $taxitem['tax_type_id'], $taxitem['rate'], $credit_note->tax_included, $taxitem['Value'], $taxitem['Net'], $ex_rate, $credit_note->document_date, $credit_note->reference);
          $total += Debtor_TransDetail::add_gl_trans(ST_CUSTCREDIT, $credit_no, $credit_date, $taxitem['sales_gl_code'], 0, 0, $taxitem['Value'], $credit_note->customer_id, "A tax GL posting for this credit note could not be inserted");
        }
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance(ST_CUSTCREDIT, $credit_no, $credit_date, -$total, PT_CUSTOMER, $credit_note->customer_id);
      DB_Comments::add(ST_CUSTCREDIT, $credit_no, $credit_date, $credit_note->Comments);
      if ($trans_no == 0) {
        Ref::save(ST_CUSTCREDIT, $credit_note->reference);
      }
      DB::commit();
      return $credit_no;
    }
    /**
     * @static
     *
     * @param     $credit_note
     * @param     $credit_line
     * @param     $credit_type
     * @param     $price
     * @param int $credited_invoice
     * Insert a stock movement coming back in to show the credit note and
     * a reversing stock movement to show the write off

     */
    static public function add_movements($credit_note, $credit_line, $credit_type, $price, $credited_invoice = 0) {
      if ($credit_type == "Return") {
        $reference = "Return ";
        if ($credited_invoice) {
          $reference .= "Ex Inv: " . $credited_invoice;
        }
      }
      elseif ($credit_type == "WriteOff") {
        $reference = "WriteOff ";
        if ($credited_invoice) {
          $reference .= "Ex Inv: " . $credited_invoice;
        }
        Inv_Movement::add_for_debtor(ST_CUSTCREDIT, $credit_line->stock_id, key($credit_note->trans_no), $credit_note->location, $credit_note->document_date, $reference, -$credit_line->qty_dispatched, $credit_line->standard_cost, 0, $price, $credit_line->discount_percent);
      }
      Inv_Movement::add_for_debtor(ST_CUSTCREDIT, $credit_line->stock_id, key($credit_note->trans_no), $credit_note->location, $credit_note->document_date, $reference, $credit_line->qty_dispatched, $credit_line->standard_cost, 0, $price, $credit_line->discount_percent);
    }
    /**
     * @static
     *
     * @param            $order
     * @param Sales_Line $order_line
     * @param            $credit_no
     * @param            $date_
     * @param            $credit_type
     * @param            $write_off_gl_code
     * @param            $branch_data
     *
     * @return float|int
     */
    static public function add_gl_costs($order, $order_line, $credit_no, $date_, $credit_type, $write_off_gl_code, &$branch_data) {
      $stock_gl_codes = Item::get_gl_code($order_line->stock_id);
      $customer = Debtor::get($order->customer_id);
      // 2008-08-01. If there is a Customer Dimension, then override with this,
      // else take the Item Dimension (if any)
      $dim = ($order->dimension_id != $customer['dimension_id'] ? $order->dimension_id :
        ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_codes["dimension_id"]));
      $dim2 = ($order->dimension2_id != $customer['dimension2_id'] ? $order->dimension2_id :
        ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_codes["dimension2_id"]));
      $total = 0;
      /* insert gl_trans to credit stock and debit cost of sales at standard cost*/
      $standard_cost = Item_Price::get_standard_cost($order_line->stock_id);
      if ($standard_cost != 0) {
        /*first the cost of sales entry*/
        $total += GL_Trans::add_std_cost(ST_CUSTCREDIT, $credit_no, $date_, $stock_gl_codes["cogs_account"], $dim, $dim2, "", -($standard_cost * $order_line->qty_dispatched), PT_CUSTOMER, $order->customer_id, "The cost of sales GL posting could not be inserted");
        /*now the stock entry*/
        if ($credit_type == "WriteOff") {
          $stock_entry_account = $write_off_gl_code;
        }
        else {
          $stock_gl_code = Item::get_gl_code($order_line->stock_id);
          $stock_entry_account = $stock_gl_code["inventory_account"];
        }
        $total += GL_Trans::add_std_cost(ST_CUSTCREDIT, $credit_no, $date_, $stock_entry_account, 0, 0, "", ($standard_cost * $order_line->qty_dispatched), PT_CUSTOMER, $order->customer_id, "The stock side (or write off) of the cost of sales GL posting could not be inserted");
      } /* end of if GL and stock integrated and standard cost !=0 */
      if ($order_line->line_price() != 0) {
        $line_taxfree_price = Tax::tax_free_price($order_line->stock_id, $order_line->price, 0, $order->tax_included, $order->tax_group_array);
        $line_tax = Tax::full_price_for_item($order_line->stock_id, $order_line->price, 0, $order->tax_included, $order->tax_group_array) - $line_taxfree_price;
        //Post sales transaction to GL credit sales
        // 2008-06-14. If there is a Branch Sales Account, then override with this,
        // else take the Item Sales Account
        if ($branch_data['sales_account'] != "") {
          $sales_account = $branch_data['sales_account'];
        }
        else {
          $sales_account = $stock_gl_codes['sales_account'];
        }
        $total += Debtor_TransDetail::add_gl_trans(ST_CUSTCREDIT, $credit_no, $date_, $sales_account, $dim, $dim2, ($line_taxfree_price * $order_line->qty_dispatched), $order->customer_id, "The credit note GL posting could not be inserted");
        if ($order_line->discount_percent != 0) {
          $total += Debtor_TransDetail::add_gl_trans(ST_CUSTCREDIT, $credit_no, $date_, $branch_data["sales_discount_account"], $dim, $dim2, -($line_taxfree_price * $order_line->qty_dispatched * $order_line->discount_percent), $order->customer_id,
            "The credit note discount GL posting could not be inserted");
        } /*end of if discount !=0 */
      } /*if line_price!=0 */
      return $total;
    }
    /**
     * @static
     *
     * @param $order
     *
     * @return mixed|string
     */
    static public function header($order) {
      start_outer_table('tablestyle width90');
      table_section(1);
      $customer_error = "";
      $change_prices = 0;
      if (!isset($_POST['customer_id']) && Session::i()->getGlobal('debtor')) {
        $_POST['customer_id'] = Session::i()->getGlobal('debtor');
      }
      Debtor::newselect();
      if ($order->customer_id != $_POST['customer_id'] /*|| $order->sales_type != $_POST['sales_type_id']*/) {
        // customer has changed
        Ajax::i()->activate('branch_id');
      }
      Debtor_Branch::row(_("Branch:"), $_POST['customer_id'], 'branch_id', NULL, FALSE, TRUE, TRUE, TRUE);
      //if (($_SESSION['credit_items']->order_no == 0) ||
      //	($order->customer_id != $_POST['customer_id']) ||
      //	($order->Branch != $_POST['branch_id']))
      //	$customer_error = $order->customer_to_order($_POST['customer_id'], $_POST['branch_id']);
      if (($order->customer_id != $_POST['customer_id']) || ($order->Branch != $_POST['branch_id'])
      ) {
        $old_order = (PHP_VERSION < 5) ? $order : clone($order);
        $customer_error = $order->customer_to_order($_POST['customer_id'], $_POST['branch_id']);
        $_POST['location'] = $order->location;
        $_POST['deliver_to'] = $order->deliver_to;
        $_POST['delivery_address'] = $order->delivery_address;
        $_POST['name'] = $order->name;
        $_POST['phone'] = $order->phone;
        Ajax::i()->activate('location');
        Ajax::i()->activate('deliver_to');
        Ajax::i()->activate('name');
        Ajax::i()->activate('phone');
        Ajax::i()->activate('delivery_address');
        if ($old_order->customer_currency != $order->customer_currency) {
          $change_prices = 1;
        }
        if ($old_order->sales_type != $order->sales_type) {
          // || $old_order->default_discount!=$order->default_discount
          $_POST['sales_type_id'] = $order->sales_type;
          Ajax::i()->activate('sales_type_id');
          $change_prices = 1;
        }
        if ($old_order->dimension_id != $order->dimension_id) {
          $_POST['dimension_id'] = $order->dimension_id;
          Ajax::i()->activate('dimension_id');
        }
        if ($old_order->dimension2_id != $order->dimension2_id) {
          $_POST['dimension2_id'] = $order->dimension2_id;
          Ajax::i()->activate('dimension2_id');
        }
        unset($old_order);
      }
      Session::i()->setGlobal('debtor',$_POST['customer_id']);
      if (!isset($_POST['ref'])) {
        $_POST['ref'] = Ref::get_next(ST_CUSTCREDIT);
      }
      if ($order->trans_no == 0) {
        ref_row(_("Reference") . ':', 'ref');
      }
      else {
        label_row(_("Reference") . ':', $order->reference);
      }
      if (!Bank_Currency::is_company($order->customer_currency)) {
        table_section(2);
        label_row(_("Customer Currency:"), $order->customer_currency);
        GL_ExchangeRate::display($order->customer_currency, Bank_Currency::for_company(), $_POST['OrderDate']);
      }
      table_section(3);
      if (!isset($_POST['sales_type_id'])) {
        $_POST['sales_type_id'] = $order->sales_type;
      }
      Sales_Type::row(_("Sales Type"), 'sales_type_id', $_POST['sales_type_id'], TRUE);
      if ($order->sales_type != $_POST['sales_type_id']) {
        $myrow = Sales_Type::get($_POST['sales_type_id']);
        $order->set_sales_type($myrow['id'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
        Ajax::i()->activate('sales_type_id');
        $change_prices = 1;
      }
      Sales_UI::shippers_row(_("Shipping Company:"), 'ShipperID', $order->ship_via);
      label_row(_("Customer Discount:"), ($order->default_discount * 100) . "%");
      table_section(4);
      if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "") {
        $_POST['OrderDate'] = $order->document_date;
      }
      date_row(_("Date:"), 'OrderDate', '', $order->trans_no == 0, 0, 0, 0, NULL, TRUE);
      if (isset($_POST['_OrderDate_changed'])) {
        if (!Bank_Currency::is_company($order->customer_currency) && (DB_Company::get_base_sales_type() > 0)
        ) {
          $change_prices = 1;
        }
        Ajax::i()->activate('_ex_rate');
      }
      // 2008-11-12 Joe Hunt added dimensions
      $dim = DB_Company::get_pref('use_dimension');
      if ($dim > 0) {
        Dimensions::select_row(_("Dimension") . ":", 'dimension_id', NULL, TRUE, ' ', FALSE, 1, FALSE);
      }
      else {
        hidden('dimension_id', 0);
      }
      if ($dim > 1) {
        Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', NULL, TRUE, ' ', FALSE, 2, FALSE);
      }
      else {
        hidden('dimension2_id', 0);
      }
      end_outer_table(1); // outer table
      if ($change_prices != 0) {
        foreach ($order->line_items as $line) {
          $line->price = Item_Price::get_calculated_price($line->stock_id, $order->customer_currency, $order->sales_type, $order->price_factor, get_post('OrderDate'));
          //		$line->discount_percent = $order->default_discount;
        }
        Ajax::i()->activate('items_table');
      }
      return $customer_error;
    }
    /**
     * @static
     *
     * @param             $title
     * @param Sales_Order $order
     */
    static public function display_items($title, $order) {
      Display::heading($title);
      Display::div_start('items_table');
      start_table('tablestyle width90');
      $th = array(
        _("Item Code"), _("Item Description"), _("Quantity"), _("Unit"), _("Price"), _("Discount %"), _("Total"), ''
      );
      if (count($order->line_items)) {
        $th[] = '';
      }
      table_header($th);
      $subtotal = 0;
      $k = 0; //row colour counter
      $id = find_submit(MODE_EDIT);
      foreach ($order->line_items as $line_no => $line) {
        $line_total = round($line->qty_dispatched * $line->price * (1 - $line->discount_percent), User::price_dec());
        if ($id != $line_no) {
          alt_table_row_color($k);
          label_cell("<a target='_blank' href='" . BASE_URL . "inventory/inquiry/stock_status.php?stock_id=" . $line->stock_id . "'>$line->stock_id</a>");
          label_cell($line->description, ' class="nowrap"');
          qty_cell($line->qty_dispatched, FALSE, Item::qty_dec($line->stock_id));
          label_cell($line->units);
          amount_cell($line->price);
          percent_cell($line->discount_percent * 100);
          amount_cell($line_total);
          edit_button_cell("Edit$line_no", _(MODE_EDIT), _('Edit document line'));
          delete_button_cell("Delete$line_no", _('Delete'), _('Remove line from document'));
          end_row();
        }
        else {
          Sales_Credit::item_controls($order, $k, $line_no);
        }
        $subtotal += $line_total;
      }
      if ($id == -1) {
        Sales_Credit::item_controls($order, $k);
      }
      $colspan = 6;
      $display_sub_total = Num::price_format($subtotal);
      label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan class='right bold'", "class='right'", 2);
      if (!isset($_POST['ChargeFreightCost']) OR ($_POST['ChargeFreightCost'] == "")) {
        $_POST['ChargeFreightCost'] = 0;
      }
      start_row();
      label_cell(_("Shipping"), "colspan=$colspan class='right bold'");
      small_amount_cells(NULL, 'ChargeFreightCost', Num::price_format(get_post('ChargeFreightCost', 0)));
      label_cell('', 'colspan=2');
      end_row();
      $taxes = $order->get_taxes($_POST['ChargeFreightCost']);
      $tax_total = Tax::edit_items($taxes, $colspan, $order->tax_included, 2);
      $display_total = Num::price_format(($subtotal + $_POST['ChargeFreightCost'] + $tax_total));
      label_row(_("Credit Note Total"), $display_total, "colspan=$colspan class='right bold'", "class='amount'", 2);
      end_table();
      Display::div_end();
    }
    /**
     * @static
     *
     * @param $order
     * @param $rowcounter
     * @param $line_no
     */
    static public function item_controls($order, $rowcounter, $line_no = -1) {
      alt_table_row_color($rowcounter);
      $id = find_submit(MODE_EDIT);
      if ($line_no != -1 && $line_no == $id) {
        $_POST['stock_id'] = $order->line_items[$id]->stock_id;
        $_POST['qty'] = Item::qty_format($order->line_items[$id]->qty_dispatched, $_POST['stock_id'], $dec);
        $_POST['price'] = Num::price_format($order->line_items[$id]->price);
        $_POST['Disc'] = Num::percent_format(($order->line_items[$id]->discount_percent) * 100);
        $_POST['units'] = $order->line_items[$id]->units;
        hidden('stock_id', $_POST['stock_id']);
        label_cell($_POST['stock_id']);
        label_cell($order->line_items[$id]->description, ' class="nowrap"');
        Ajax::i()->activate('items_table');
      }
      else {
        Sales_UI::items_cells(NULL, 'stock_id', NULL, FALSE, FALSE, array('description' => ''));
        if (list_updated('stock_id')) {
          Ajax::i()->activate('price');
          Ajax::i()->activate('qty');
          Ajax::i()->activate('units');
          Ajax::i()->activate('line_total');
        }
        $item_info = Item::get_edit_info(Input::post('stock_id'));
        $dec = $item_info['decimals'];
        $_POST['qty'] = Num::format(0, $dec);
        $_POST['units'] = $item_info["units"];
        $_POST['price'] = Num::price_format(Item_Price::get_calculated_price(Input::post('stock_id'), $order->customer_currency, $order->sales_type, $order->price_factor, $order->document_date));
        // default to the customer's discount %
        $_POST['Disc'] = Num::percent_format($order->default_discount * 100);
      }
      qty_cells(NULL, 'qty', $_POST['qty'], NULL, NULL, $dec);
      label_cell($_POST['units']);
      amount_cells(NULL, 'price', NULL);
      small_amount_cells(NULL, 'Disc', Num::percent_format(0), NULL, NULL, User::percent_dec());
      amount_cell(Validation::input_num('qty') * Validation::input_num('price') * (1 - Validation::input_num('Disc') / 100));
      if ($id != -1) {
        button_cell(Orders::UPDATE_ITEM, _("Update"), _('Confirm changes'), ICON_UPDATE);
        button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
        hidden('line_no', $line_no);
        JS::set_focus('qty');
      }
      else {
        submit_cells(Orders::ADD_ITEM, _("Add Item"), "colspan=2", _('Add new item to document'), TRUE);
      }
      end_row();
    }
    /**
     * @static
     *
     * @param $credit
     */
    static public function option_controls($credit) {
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
          $_POST['location'] = $credit->location;
        }
        Inv_Location::row(_("Items Returned to Location"), 'location');
      }
      else {
        /* the goods are to be written off to somewhere */
        GL_UI::all_row(_("Write off the cost of the items to"), 'WriteOffGLCode', NULL);
      }
      textarea_row(_("Memo"), "CreditText", NULL, 51, 3);
      echo "</table>";
      Display::div_end();
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     * @param bool $submit_on_change
     */
    static public function cells($label, $name, $selected = NULL, $submit_on_change = FALSE) {
      if ($label != NULL) {
        label_cell($label);
      }
      echo "<td>\n";
      echo array_selector($name, $selected, array(
        'Return' => _("Items Returned to Inventory Location"), 'WriteOff' => _("Items Written Off")
      ), array('select_submit' => $submit_on_change));
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected
     * @param bool $submit_on_change
     */
    static public function row($label, $name, $selected = NULL, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Sales_Credit::cells(NULL, $name, $selected, $submit_on_change);
      echo "</tr>\n";
    }
  }
