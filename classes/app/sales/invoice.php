<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Sales_Invoice {

    /**
     * @static
     *
     * @param $invoice
     *
     * @return int
     */
    static public function add($invoice) {
      $trans_no = $invoice->trans_no;
      if (is_array($trans_no)) {
        $trans_no = key($trans_no);
      }
      $date_           = $invoice->document_date;
      $charge_shipping = $invoice->freight_cost;
      DB::begin();
      $company_data = DB_Company::get_prefs();
      $branch_data  = Sales_Branch::get_accounts($invoice->Branch);
      $customer     = Debtor::get($invoice->customer_id);
      // offer price values without freight costs
      $items_total = $invoice->get_items_total_dispatch();
      $freight_tax = $invoice->get_shipping_tax();
      $delivery_no = $invoice->src_docs;
      if (is_array($delivery_no)) {
        $delivery_no = 0;
      }
      Debtor_Trans::update_version(Debtor_Trans::get_parent_type(ST_SALESINVOICE), $invoice->src_docs);
      $ov_gst = 0;
      $taxes  = $invoice->get_taxes(); // all taxes with freight_tax
      foreach ($taxes as $taxitem) {
        $ov_gst += $taxitem['Value'];
      }
      if ($invoice->tax_included == 0) {
        $items_added_tax   = $ov_gst - $freight_tax;
        $freight_added_tax = $freight_tax;
      }
      else {
        $items_added_tax   = 0;
        $freight_added_tax = 0;
      }
      // 2006-06-14. If the Customer Branch AR Account is set to a Bank Account,
      // the transaction will be settled at once.
      if (Bank_Account::is($branch_data['receivables_account'])) {
        $alloc = $items_total + $items_added_tax + $invoice->freight_cost + $freight_added_tax;
      }
      else {
        $alloc = 0;
      }
      /* Insert/update the debtor_trans */
      $sales_order = $invoice->order_no;
      if (is_array($sales_order)) {
        $sales_order = $sales_order[0];
      } // assume all crucial SO data are same for every delivery
      $invoice_no = Debtor_Trans::write(ST_SALESINVOICE, $trans_no, $invoice->customer_id, $invoice->Branch, $date_, $invoice->reference, $items_total, 0, $items_added_tax, $invoice->freight_cost, $freight_added_tax, $invoice->sales_type, $sales_order, $delivery_no, $invoice->ship_via,
        $invoice->due_date, $alloc, 0, $invoice->dimension_id, $invoice->dimension2_id);
      // 2008-06-14 extra $alloc, 2008-11-12 added dimension_id Joe Hunt
      if ($trans_no == 0) {
        $invoice->trans_no = array($invoice_no => 0);
        Debtor_Trans::set_parent($invoice);
      }
      else {
        DB_Comments::delete(ST_SALESINVOICE, $invoice_no);
        GL_Trans::void(ST_SALESINVOICE, $invoice_no, TRUE);
        Sales_Allocation::void(ST_SALESINVOICE, $invoice_no); // ?
        GL_Trans::void_tax_details(ST_SALESINVOICE, $invoice_no);
      }
      $total = 0;
      foreach ($invoice->line_items as $line_no => $invoice_line) {
        $line_taxfree_price = Tax::tax_free_price($invoice_line->stock_id, $invoice_line->price, 0, $invoice->tax_included, $invoice->tax_group_array);
        $line_tax           = Tax::full_price_for_item($invoice_line->stock_id, $invoice_line->price, 0, $invoice->tax_included, $invoice->tax_group_array) - $line_taxfree_price;
        Debtor_TransDetail::add(ST_SALESINVOICE, $invoice_no, $invoice_line->stock_id, $invoice_line->description, $invoice_line->qty_dispatched, $invoice_line->line_price(), $line_tax, $invoice_line->discount_percent, $invoice_line->standard_cost, $trans_no ?
          $invoice_line->id : 0);
        // Update delivery items for the quantity invoiced
        if ($invoice_line->qty_old != $invoice_line->qty_dispatched) {
          Sales_Order::update_parent_line(ST_SALESINVOICE, $invoice_line->src_id, ($invoice_line->qty_dispatched - $invoice_line->qty_old));
        }
        if ($invoice_line->qty_dispatched != 0) {
          $stock_gl_code = Item::get_gl_code($invoice_line->stock_id);
          if ($invoice_line->line_price() != 0) {
            //Post sales transaction to GL credit sales
            // 2008-06-14. If there is a Branch Sales Account, then override with this,
            // else take the Item Sales Account
            $sales_account = ($branch_data['sales_account'] != "" ? $branch_data['sales_account'] : $stock_gl_code['sales_account']);
            // 2008-08-01. If there is a Customer Dimension, then override with this,
            // else take the Item Dimension (if any)
            $dim  = ($invoice->dimension_id != $customer['dimension_id'] ? $invoice->dimension_id :
              ($customer['dimension_id'] != 0 ? $customer["dimension_id"] : $stock_gl_code["dimension_id"]));
            $dim2 = ($invoice->dimension2_id != $customer['dimension2_id'] ? $invoice->dimension2_id :
              ($customer['dimension2_id'] != 0 ? $customer["dimension2_id"] : $stock_gl_code["dimension2_id"]));
            $total += Debtor_TransDetail::add_gl_trans(ST_SALESINVOICE, $invoice_no, $date_, $sales_account, $dim, $dim2, (-$line_taxfree_price * $invoice_line->qty_dispatched), $invoice->customer_id, "The sales price GL posting could not be inserted");
            if ($invoice_line->discount_percent != 0) {
              $total += Debtor_TransDetail::add_gl_trans(ST_SALESINVOICE, $invoice_no, $date_, $branch_data["sales_discount_account"], $dim, $dim2, ($line_taxfree_price * $invoice_line->qty_dispatched * $invoice_line->discount_percent), $invoice->customer_id,
                "The sales discount GL posting could not be inserted");
            } /*end of if discount !=0 */
          }
        } /*quantity dispatched is more than 0 */
      } /*end of delivery_line loop */
      if (($items_total + $charge_shipping) != 0) {
        $total += Debtor_TransDetail::add_gl_trans(ST_SALESINVOICE, $invoice_no, $date_, $branch_data["receivables_account"], 0, 0, ($items_total + $charge_shipping + $items_added_tax + $freight_added_tax), $invoice->customer_id, "The total debtor GL posting could not be inserted");
      }
      if ($charge_shipping != 0) {
        $total += Debtor_TransDetail::add_gl_trans(ST_SALESINVOICE, $invoice_no, $date_, $company_data["freight_act"], 0, 0, -$invoice->get_tax_free_shipping(), $invoice->customer_id, "The freight GL posting could not be inserted");
      }
      // post all taxes
      foreach ($taxes as $taxitem) {
        if ($taxitem['Net'] != 0) {
          $ex_rate = Bank_Currency::exchange_rate_from_home(Bank_Currency::for_debtor($invoice->customer_id), $date_);
          GL_Trans::add_tax_details(ST_SALESINVOICE, $invoice_no, $taxitem['tax_type_id'], $taxitem['rate'], $invoice->tax_included, $taxitem['Value'], $taxitem['Net'], $ex_rate, $date_, $invoice->reference);
          $total += Debtor_TransDetail::add_gl_trans(ST_SALESINVOICE, $invoice_no, $date_, $taxitem['sales_gl_code'], 0, 0, (-$taxitem['Value']), $invoice->customer_id, "A tax GL posting could not be inserted");
        }
      }
      /*Post a balance post if $total != 0 */
      GL_Trans::add_balance(ST_SALESINVOICE, $invoice_no, $date_, -$total, PT_CUSTOMER, $invoice->customer_id);
      DB_Comments::add(10, $invoice_no, $date_, $invoice->Comments);
      if ($trans_no == 0) {
        Ref::save(ST_SALESINVOICE, $invoice->reference);
      }
      DB::commit();
      return $invoice_no;
    }
    /**
     * @static
     *
     * @param $type
     * @param $type_no
     */
    static public function void($type, $type_no) {
      DB::begin();
      Bank_Trans::void($type, $type_no, TRUE);
      GL_Trans::void($type, $type_no, TRUE);
      // reverse all the changes in parent document(s)
      $items_result = Debtor_TransDetail::get($type, $type_no);
      $deliveries   = Debtor_Trans::get_parent($type, $type_no);
      if ($deliveries !== 0) {
        $srcdetails = Debtor_TransDetail::get(Debtor_Trans::get_parent_type($type), $deliveries);
        while ($row = DB::fetch($items_result)) {
          $src_line = DB::fetch($srcdetails);
          Sales_Order::update_parent_line($type, $src_line['id'], -$row['quantity']);
        }
      }
      // clear details after they've been reversed in the sales order
      Debtor_TransDetail::void($type, $type_no);
      GL_Trans::void_tax_details($type, $type_no);
      Sales_Allocation::void($type, $type_no);
      // do this last because other voidings can depend on it - especially voiding
      // DO NOT MOVE THIS ABOVE VOIDING or we can end up with trans with alloc < 0
      Debtor_Trans::void($type, $type_no);
      DB::commit();
    }
    /**
     * @param $order
     *
     * @return int
     */
    static public function check_quantities($order) {
      $ok = 1;
      foreach ($order->line_items as $line_no => $itm) {
        if (isset($_POST['Line' . $line_no])) {
          if ($order->trans_no) {
            $min = $itm->qty_done;
            $max = $itm->quantity;
          }
          else {
            $min = 0;
            $max = $itm->quantity - $itm->qty_done;
          }
          if ($itm->quantity > 0 && Validation::post_num('Line' . $line_no, $min, $max)) {
            $order->line_items[$line_no]->qty_dispatched = Validation::input_num('Line' . $line_no);
          }
          elseif ($itm->quantity < 0 && Validation::post_num('Line' . $line_no, $max, $min)) {
            $order->line_items[$line_no]->qty_dispatched = Validation::input_num('Line' . $line_no);
          }
          else {
            $ok = 0;
          }
        }
        if (isset($_POST['Line' . $line_no . 'Desc'])) {
          $line_desc = $_POST['Line' . $line_no . 'Desc'];
          if (strlen($line_desc) > 0) {
            $order->line_items[$line_no]->description = $line_desc;
          }
        }
      }
      return $ok;
    }

    /**
     * @param $delivery_notes
     */
    static public function set_delivery_shipping_sum($delivery_notes) {
      $shipping = 0;
      foreach ($delivery_notes as $delivery_num) {
        $myrow = Debtor_Trans::get($delivery_num, 13);
        //$branch = Sales_Branch::get($myrow["branch_id"]);
        //$shipping += $sales_order['freight_cost'];
        $shipping += $myrow['ov_freight'];
      }
      $_POST['ChargeFreightCost'] = Num::price_format($shipping);
    }

    /**
     * @param $order
     */
    static public function copyFromPost($order) {
      $order->ship_via      = $_POST['ship_via'];
      $order->freight_cost  = Validation::input_num('ChargeFreightCost');
      $order->document_date = $_POST['InvoiceDate'];
      $order->due_date      = $_POST['due_date'];
      $order->Comments      = $_POST['Comments'];
      if ($order->trans_no == 0) {
        $order->reference = $_POST['ref'];
      }
    }

    /**
     * @param $order
     *
     * @return \Purch_Order|\Sales_Order
     */
    static public function copyToPost($order) {
      $order->view_only = isset($_GET[Orders::VIEW_INVOICE]) || isset($_POST['viewing']);

      $order = Sales_Order::check_edit_conflicts($order);
      if (!$order->view_only) {
        $_POST['ship_via']          = $order->ship_via;
        $_POST['ChargeFreightCost'] = Num::price_format($order->freight_cost);
        $_POST['InvoiceDate']       = $order->document_date;
        $_POST['due_date']          = $order->due_date;
        $_POST['ref']               = $order->reference;
      }
      $_POST['order_id'] = $order->order_id;
      $_POST['Comments'] = $order->Comments;
      return Orders::session_set($order);
    }

    /**
     * @param Sales_Order $order
     *
     * @return bool
     */
    static public function check_data($order) {
      if (!isset($_POST['InvoiceDate']) || !Dates::is_date($_POST['InvoiceDate'])) {
        Event::error(_("The entered invoice date is invalid."));
        JS::set_focus('InvoiceDate');
        return FALSE;
      }
      if (!Dates::is_date_in_fiscalyear($_POST['InvoiceDate'])) {
        Event::error(_("The entered invoice date is not in fiscal year."));
        JS::set_focus('InvoiceDate');
        return FALSE;
      }
      if (!isset($_POST['due_date']) || !Dates::is_date($_POST['due_date'])) {
        Event::error(_("The entered invoice due date is invalid."));
        JS::set_focus('due_date');
        return FALSE;
      }
      if ($order->trans_no == 0) {
        if (!Ref::is_valid($_POST['ref'])) {
          Event::error(_("You must enter a reference."));
          JS::set_focus('ref');
          return FALSE;
        }
        if (!Ref::is_new($_POST['ref'], ST_SALESINVOICE)) {
          $_POST['ref'] = Ref::get_next(ST_SALESINVOICE);
        }
      }
      if ($_POST['ChargeFreightCost'] == "") {
        $_POST['ChargeFreightCost'] = Num::price_format(0);
      }
      if (!Validation::post_num('ChargeFreightCost', 0)) {
        Event::error(_("The entered shipping value is not numeric."));
        JS::set_focus('ChargeFreightCost');
        return FALSE;
      }
      if ($order->has_items_dispatch() == 0 && Validation::input_num('ChargeFreightCost') == 0) {
        Event::error(_("There are no item quantities on this invoice."));
        return FALSE;
      }
      if (!Sales_Delivery::check_quantities($order)) {
        Event::error(_("Selected quantity cannot be less than quantity credited nor more than quantity not invoiced yet."));
        return FALSE;
      }
      return TRUE;
    }
    /**
     * @static
     *
     * @param $customer_id
     * @param $branch_id
     * @param $order_no
     * @param $tmpl_no
     *
     * @return int|void
     */
    static public function create_recurrent($customer_id, $branch_id, $order_no, $tmpl_no) {
      $doc = new Sales_Order(ST_SALESORDER, array($order_no));
      $doc->customer_to_order($customer_id, $branch_id);
      $doc->trans_type    = ST_SALESORDER;
      $doc->trans_no      = 0;
      $doc->document_date = Dates::today(); // 2006-06-15. Added so Invoices and Deliveries get current day
      $doc->due_date      = Sales_Order::get_invoice_duedate($doc->customer_id, $doc->document_date);
      $doc->reference     = Ref::get_next($doc->trans_type);
      //$doc->Comments='';
      foreach ($doc->line_items as $line_no => $item) {
        $line        = &$doc->line_items[$line_no];
        $line->price = Item_Price::get_calculated_price($line->stock_id, $doc->customer_currency, $doc->sales_type, $doc->price_factor, $doc->document_date);
      }
      $order             = $doc;
      $order->trans_type = ST_SALESINVOICE;
      $order->reference  = Ref::get_next($order->trans_type);
      $invno             = $order->write(1);
      $date              = Dates::date2sql($order->document_date);
      $sql               = "UPDATE recurrent_invoices SET last_sent='$date' WHERE id=" . DB::escape($tmpl_no);
      DB::query($sql, "The recurrent invoice could not be updated or added");
      return $invno;
    }
  }
