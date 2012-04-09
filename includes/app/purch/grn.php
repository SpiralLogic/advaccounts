<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //------------------- update average material cost ------------------------------------------ Joe Hunt Mar-03-2008
  class Purch_GRN {
    /**
     * @static
     *
     * @param      $supplier
     * @param      $stock_id
     * @param      $price
     * @param      $qty
     * @param      $date
     * @param bool $adj_only
     *
     * @return float
     */
    static public function update_average_material_cost($supplier, $stock_id, $price, $qty, $date, $adj_only = FALSE) {
      if ($supplier != NULL) {
        $currency = Bank_Currency::for_creditor($supplier);
      }
      else {
        $currency = NULL;
      }
      $dec = User::price_dec();
      Num::price_decimal($price, $dec);
      $price = Num::round($price, $dec);
      if ($currency != NULL) {
        $ex_rate = Bank_Currency::exchange_rate_to_home($currency, $date);
        $price_in_home_currency = $price / $ex_rate;
      }
      else {
        $price_in_home_currency = $price;
      }
      $sql = "SELECT material_cost FROM stock_master WHERE stock_id=" . DB::escape($stock_id);
      $result = DB::query($sql);
      $myrow = DB::fetch($result);
      $material_cost = $myrow['material_cost'];
      if ($price > -0.0001 && $price < 0.0001) {
        return $material_cost;
      }
      if ($adj_only) {
        $exclude = ST_CUSTDELIVERY;
      }
      else {
        $exclude = 0;
      }
      $cost_adjust = FALSE;
      $qoh = Item::get_qoh_on_date($stock_id, NULL, $date, $exclude);
      if ($adj_only) {
        if ($qoh > 0) {
          $material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency) / $qoh;
        }
      }
      else {
        if ($qoh < 0) {
          if ($qoh + $qty > 0) {
            $cost_adjust = TRUE;
          }
          $qoh = 0;
        }
        if ($qoh + $qty != 0) {
          $material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency) / ($qoh + $qty);
        }
      }
      $material_cost = Num::round($material_cost, $dec);
      if ($cost_adjust) // new 2010-02-10
      {
        Item::adjust_deliveries($stock_id, $material_cost, $date);
      }
      $sql = "UPDATE stock_master SET material_cost=" . DB::escape($material_cost) . "
		WHERE stock_id=" . DB::escape($stock_id);
      DB::query($sql, "The cost details for the inventory item could not be updated");
      return $material_cost;
    }
    /**
     * @static
     *
     * @param $order
     * @param $date_
     * @param $reference
     * @param $location
     *
     * @return string
     */
    static public function add(&$order, $date_, $reference, $location) {
      DB::begin();
      $grn = static::add_batch($order->order_no, $order->supplier_id, $reference, $location, $date_);
      foreach ($order->line_items as $order_line) {
        if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty)) {
          /*Update sales_order_details for the new quantity received and the standard cost used for postings to GL and recorded in the stock movements for FIFO/LIFO stocks valuations*/
          //------------------- update average material cost ------------------------------------------ Joe Hunt Mar-03-2008
          static::update_average_material_cost($order->supplier_id, $order_line->stock_id, $order_line->price, $order_line->receive_qty, $date_);
          if ($order_line->qty_received == 0) {
            /*This must be the first receipt of goods against this line */
            /*Need to get the standard cost as it is now so we can process GL jorunals later*/
            $order_line->standard_cost = Item_Price::get_standard_cost($order_line->stock_id);
          }
          if ($order_line->price <= $order_line->standard_cost) {
            Purch_Order::add_or_update_data($order->supplier_id, $order_line->stock_id, $order_line->price);
          }
          /*Need to insert a grn item */
          $grn_item = static::add_item($grn, $order_line->po_detail_rec, $order_line->stock_id, $order_line->description, $order_line->standard_cost, $order_line->receive_qty, $order_line->price, $order_line->discount);
          /* Update location stock records - NB a po cannot be entered for a service/kit parts */
          Inv_Movement::add(ST_SUPPRECEIVE, $order_line->stock_id, $grn, $location, $date_, "", $order_line->receive_qty, $order_line->standard_cost, $order->supplier_id, 1, $order_line->price);
        } /*quantity received is != 0 */
      } /*end of order_line loop */
      $grn_item = static::add_item($grn, $order->add_freight($date_), 'Freight', 'Freight Charges', 0, 1, $order->freight, 0);
      Ref::save(ST_SUPPRECEIVE, $reference);
      DB_AuditTrail::add(ST_SUPPRECEIVE, $grn, $date_);
      DB::commit();
      return $grn;
    }
    /**
     * @static
     *
     * @param $po_number
     * @param $supplier_id
     * @param $reference
     * @param $location
     * @param $date_
     *
     * @return string
     */
    static public function add_batch($po_number, $supplier_id, $reference, $location, $date_) {
      $date = Dates::date2sql($date_);
      $sql = "INSERT INTO grn_batch (purch_order_no, delivery_date, supplier_id, reference, loc_code)
			VALUES (" . DB::escape($po_number) . ", " . DB::escape($date) . ", " . DB::escape($supplier_id) . ", " . DB::escape($reference) . ", " . DB::escape($location) . ")";
      DB::query($sql, "A grn batch record could not be inserted.");
      return DB::insert_id();
    }
    /**
     * @static
     *
     * @param $grn_batch_id
     * @param $po_detail_item
     * @param $item_code
     * @param $description
     * @param $standard_unit_cost
     * @param $quantity_received
     * @param $price
     * @param $discount
     *
     * @return string
     */
    static public function add_item($grn_batch_id, $po_detail_item, $item_code, $description, $standard_unit_cost, $quantity_received, $price, $discount) {
      $sql = "UPDATE purch_order_details
 SET quantity_received = quantity_received + " . DB::escape($quantity_received) . ",
 std_cost_unit=" . DB::escape($standard_unit_cost) . ",
 discount=" . DB::escape($discount) . ",
 act_price=" . DB::escape($price) . "
 WHERE po_detail_item = " . DB::escape($po_detail_item);
      DB::query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
      $sql = "INSERT INTO grn_items (grn_batch_id, po_detail_item, item_code, description, qty_recd, discount)
		VALUES (" . DB::escape($grn_batch_id) . ", " . DB::escape($po_detail_item) . ", " . DB::escape($item_code) . ", " . DB::escape($description) . ", " . DB::escape($quantity_received) . ", " . DB::escape($discount) . ")";
      DB::query($sql, "A GRN detail item could not be inserted.");
      return DB::insert_id();
    }
    /**
     * @static
     *
     * @param $item
     *
     * @return mixed
     */
    static public function get_batch_for_item($item) {
      $sql = "SELECT grn_batch_id FROM grn_items WHERE id=" . DB::escape($item);
      $result = DB::query($sql, "Could not retreive GRN batch id");
      $row = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $grn
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_batch($grn) {
      $sql = "SELECT * FROM grn_batch WHERE id=" . DB::escape($grn);
      $result = DB::query($sql, "Could not retreive GRN batch id");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $entered_grn
     * @param $supplier
     * @param $transno
     * @param $date
     */
    static public function set_item_credited(&$entered_grn, $supplier, $transno, $date) {
      $mcost = static::update_average_material_cost($supplier, $entered_grn->item_code, $entered_grn->chg_price, $entered_grn->this_quantity_inv, $date);
      $sql = "SELECT grn_batch.*, grn_items.*
 	FROM grn_batch, grn_items
 	WHERE grn_items.grn_batch_id=grn_batch.id
		AND grn_items.id=" . DB::escape($entered_grn->id) . "
 	AND grn_items.item_code=" . DB::escape($entered_grn->item_code);
      $result = DB::query($sql, "Could not retreive GRNS");
      $myrow = DB::fetch($result);
      $sql = "UPDATE purch_order_details
 SET quantity_received = quantity_received + " . DB::escape($entered_grn->this_quantity_inv) . ",
 quantity_ordered = quantity_ordered + " . DB::escape($entered_grn->this_quantity_inv) . ",
 qty_invoiced = qty_invoiced + " . DB::escape($entered_grn->this_quantity_inv) . ",
 std_cost_unit=" . DB::escape($mcost) . ",
 act_price=" . DB::escape($entered_grn->chg_price) . "
 WHERE po_detail_item = " . $myrow["po_detail_item"];
      DB::query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
      //$sql = "UPDATE ".''."grn_items SET qty_recd=0, quantity_inv=0 WHERE id=$entered_grn->id";
      $sql = "UPDATE grn_items SET qty_recd=qty_recd+" . DB::escape($entered_grn->this_quantity_inv) . ",quantity_inv=quantity_inv+" . DB::escape($entered_grn->this_quantity_inv) . " WHERE id=" . DB::escape($entered_grn->id);
      DB::query($sql);
      Inv_Movement::add(ST_SUPPCREDIT, $entered_grn->item_code, $transno, $myrow['loc_code'], $date, "", $entered_grn->this_quantity_inv, $mcost, $supplier, 1, $entered_grn->chg_price);
    }
    /**
     * @static
     *
     * @param int    $grn_batch_id
     * @param string $supplier_id
     * @param bool   $outstanding_only
     * @param bool   $is_invoiced_only
     * @param int    $invoice_no
     * @param string $begin
     * @param string $end
     *
     * @return null|PDOStatement
     */
    static public function get_items($grn_batch_id = 0, $supplier_id = "", $outstanding_only = FALSE, $is_invoiced_only = FALSE, $invoice_no = 0, $begin = "", $end = "") {
      $sql = "SELECT " . "grn_batch.*, " . "grn_items.*, " . "purch_order_details.unit_price, " . "purch_order_details.std_cost_unit, units
 	 FROM " . "grn_batch, " . "grn_items, " . "purch_order_details, " . "stock_master";
      if ($invoice_no != 0) {
        $sql .= ", creditor_trans_details";
      }
      $sql .= " WHERE " . "grn_items.grn_batch_id=" . "grn_batch.id AND " . "grn_items.po_detail_item=" . "purch_order_details.po_detail_item";
      if ($invoice_no != 0) {
        $sql .= " AND " . "creditor_trans_details.creditor_trans_type=" . ST_SUPPINVOICE . " AND " . "creditor_trans_details.creditor_trans_no=$invoice_no AND " . "grn_items.id=" . "creditor_trans_details.grn_item_id";
      }
      $sql .= " AND " . "stock_master.stock_id=" . "grn_items.item_code ";
      if ($begin != "") {
        $sql .= " AND grn_batch.delivery_date>='" . Dates::date2sql($begin) . "'";
      }
      if ($end != "") {
        $sql .= " AND grn_batch.delivery_date<='" . Dates::date2sql($end) . "'";
      }
      if ($grn_batch_id != 0) {
        $sql .= " AND grn_batch.id=" . DB::escape($grn_batch_id) . " AND grn_items.grn_batch_id=" . DB::escape($grn_batch_id);
      }
      if ($is_invoiced_only) {
        $sql .= " AND grn_items.quantity_inv > 0";
      }
      if ($outstanding_only) {
        $sql .= " AND grn_items.qty_recd - grn_items.quantity_inv > 0";
      }
      if ($supplier_id != "") {
        $sql .= " AND grn_batch.supplier_id =" . DB::escape($supplier_id);
      }
      $sql .= " ORDER BY grn_batch.delivery_date, grn_batch.id, grn_items.id";
      return DB::query($sql, "Could not retreive GRNS");
    }
    // get the details for a given grn item
    /**
     * @static
     *
     * @param $grn_item_no
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_item($grn_item_no) {
      $sql = "SELECT grn_items.*, purch_order_details.unit_price,
 	grn_items.qty_recd - grn_items.quantity_inv AS QtyOstdg,
 	purch_order_details.std_cost_unit
		FROM grn_items, purch_order_details, stock_master
		WHERE grn_items.po_detail_item=purch_order_details.po_detail_item
 			AND stock_master.stock_id=grn_items.item_code
			AND grn_items.id=" . DB::escape($grn_item_no);
      $result = DB::query($sql, "could not retreive grn item details");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $grn_batch
     * @param $order
     */
    static public function get_items_to_order($grn_batch, &$order) {
      $result = static::get_items($grn_batch);
      if (DB::num_rows($result) > 0) {
        while ($myrow = DB::fetch($result)) {
          if (is_null($myrow["units"])) {
            $units = "";
          }
          else {
            $units = $myrow["units"];
          }
          $order->add_to_order($order->lines_on_order + 1, $myrow["item_code"], 1, $myrow["description"], $myrow["unit_price"], $units, Dates::sql2date($myrow["delivery_date"]), $myrow["quantity_inv"], $myrow["qty_recd"], $myrow['discount']);
          $order->line_items[$order->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
        } /* line po from purchase order details */
      } //end of checks on returned data set
    }
    // read a grn into an order class
    /**
     * @static
     *
     * @param $grn_batch
     * @param $order
     */
    static public function get($grn_batch, &$order) {
      $sql = "SELECT *	FROM grn_batch WHERE id=" . DB::escape($grn_batch);
      $result = DB::query($sql, "The grn sent is not valid");
      $row = DB::fetch($result);
      $po_number = $row["purch_order_no"];
      $result = $order->get_header($po_number);
      if ($result) {
        $order->orig_order_date = Dates::sql2date($row["delivery_date"]);
        $order->location = $row["loc_code"];
        $order->reference = $row["reference"];
        static::get_items_to_order($grn_batch, $order);
      }
    }
    // get the GRNs (batch info not details) for a given po number
    /**
     * @static
     *
     * @param $po_number
     *
     * @return null|PDOStatement
     */
    static public function get_for_po($po_number) {
      $sql = "SELECT * FROM grn_batch WHERE purch_order_no=" . DB::escape($po_number);
      return DB::query($sql, "The grns for the po $po_number could not be retreived");
    }
    /**
     * @static
     *
     * @param $grn_batch
     *
     * @return bool
     */
    static public function exists($grn_batch) {
      $sql = "SELECT id FROM grn_batch WHERE id=" . DB::escape($grn_batch);
      $result = DB::query($sql, "Cannot retreive a grn");
      return (DB::num_rows($result) > 0);
    }
    /**
     * @static
     *
     * @param $grn_batch
     *
     * @return bool
     */
    static public function exists_on_invoices($grn_batch) {
      $sql = "SELECT creditor_trans_details.id FROM creditor_trans_details,grn_items
		WHERE creditor_trans_details.grn_item_id=grn_items.id
		AND quantity != 0
		AND grn_batch_id=" . DB::escape($grn_batch);
      $result = DB::query($sql, "Cannot query GRNs");
      return (DB::num_rows($result) > 0);
    }
    /**
     * @static
     *
     * @param $type
     * @param $grn_batch
     *
     * @return bool
     */
    static public function void($type, $grn_batch) {
      if ($type != ST_SUPPRECEIVE) {
        $type = ST_SUPPRECEIVE;
      }
      if (static::exists_on_invoices($grn_batch)) {
        return FALSE;
      }
      DB::begin();
      Bank_Trans::void($type, $grn_batch, TRUE);
      GL_Trans::void($type, $grn_batch, TRUE);
      // clear the quantities of the grn items in the POs and invoices
      $result = static::get_items($grn_batch);
      if (DB::num_rows($result) > 0) {
        while ($myrow = DB::fetch($result)) {
          $sql = "UPDATE purch_order_details
 SET quantity_received = quantity_received - " . $myrow["qty_recd"] . "
 WHERE po_detail_item = " . $myrow["po_detail_item"];
          DB::query($sql, "a purchase order details record could not be voided.");
        }
      }
      // clear the quantities in the grn items
      $sql = "UPDATE grn_items SET qty_recd=0, quantity_inv=0
		WHERE grn_batch_id=" . DB::escape($grn_batch);
      DB::query($sql, "A grn detail item could not be voided.");
      // clear the stock move items
      Inv_Movement::void($type, $grn_batch);
      DB::commit();
      return TRUE;
    }
    /**
     * @static
     *
     * @param      $po
     * @param bool $editable
     */
    static public function display(&$po, $editable = FALSE) {
      start_table('tablestyle2 width90');
      start_row();
      label_cells(_("Supplier"), $po->supplier_name, "class='label'");
      if (!Bank_Currency::is_company($po->curr_code)) {
        label_cells(_("Order Currency"), $po->curr_code, "class='label'");
      }
      label_cells(_("For Purchase Order"), GL_UI::trans_view(ST_PURCHORDER, $po->order_no), "class='label'");
      label_cells(_("Ordered On"), $po->orig_order_date, "class='label'");
      label_cells(_("Supplier's Reference"), $po->requisition_no, "class='label'");
      end_row();
      start_row();
      if ($editable) {
        if (!isset($_POST['ref'])) {
          $_POST['ref'] = Ref::get_next(ST_SUPPRECEIVE);
        }
        ref_cells(_("Reference"), 'ref', '', NULL, "class='label'");
        if (!isset($_POST['location'])) {
          $_POST['location'] = $po->location;
        }
        label_cell(_("Deliver Into Location"), "class='label'");
        Inv_Location::cells(NULL, 'location', $_POST['location']);
        if (!isset($_POST['DefaultReceivedDate'])) {
          $_POST['DefaultReceivedDate'] = Dates::new_doc_date();
        }
        date_cells(_("Date Items Received"), 'DefaultReceivedDate', '', TRUE, 0, 0, 0, "class='label'");
      }
      else {
        label_cells(_("Reference"), $po->reference, "class='label'");
        label_cells(_("Deliver Into Location"), Inv_Location::get_name($po->location), "class='label'");
      }
      end_row();
      if (!$editable) {
        label_row(_("Delivery Address"), $po->delivery_address, "class='label'", "colspan=9");
      }
      if ($po->Comments != "") {
        label_row(_("Order Comments"), $po->Comments, "class='label'", "colspan=9");
      }
      end_table(1);
    }
    //--------------
    /**
     * @static
     *
     * @param $creditor_trans
     * @param $k
     *
     * @return bool
     */
    static public function display_for_selection($creditor_trans, $k) {
      if ($creditor_trans->is_invoice) {
        $result = Purch_GRN::get_items(0, $creditor_trans->supplier_id, TRUE);
      }
      else {
        if (isset($_POST['receive_begin']) && isset($_POST['receive_end'])) {
          $result = Purch_GRN::get_items(0, $creditor_trans->supplier_id, FALSE, TRUE, 0, $_POST['receive_begin'], $_POST['receive_end']);
        }
        else {
          if (isset($_POST['invoice_no'])) {
            $result = Purch_GRN::get_items(0, $creditor_trans->supplier_id, FALSE, TRUE, $_POST['invoice_no']);
          }
          else {
            $result = Purch_GRN::get_items(0, $creditor_trans->supplier_id, FALSE, TRUE);
          }
        }
      }
      if (DB::num_rows($result) == 0) {
        return FALSE;
      }
      /*Set up a table to show the outstanding GRN items for selection */
      while ($myrow = DB::fetch($result)) {
        $grn_already_on_invoice = FALSE;
        foreach ($creditor_trans->grn_items as $entered_grn) {
          if ($entered_grn->id == $myrow["id"]) {
            $grn_already_on_invoice = TRUE;
          }
        }
        if ($grn_already_on_invoice == FALSE) {
          if (!isset($_SESSION['delivery_po']) || $myrow["purch_order_no"] == $_SESSION['delivery_po']) {
            alt_table_row_color($k);
            $n = $myrow["id"];
            label_cell(GL_UI::trans_view(25, $myrow["grn_batch_id"]));
            label_cell($myrow["id"] . hidden('qty_recd' . $n, $myrow["qty_recd"], FALSE) . hidden('item_code' . $n, $myrow["item_code"], FALSE) . hidden('description' . $n, $myrow["description"], FALSE) . hidden('prev_quantity_inv' . $n, $myrow['quantity_inv'], FALSE) . hidden('order_price' . $n,
              $myrow['unit_price'], FALSE) . hidden('std_cost_unit' . $n, $myrow['std_cost_unit'], FALSE) . hidden('po_detail_item' . $n, $myrow['po_detail_item'], FALSE));
            label_cell(GL_UI::trans_view(ST_PURCHORDER, $myrow["purch_order_no"]));
            label_cell($myrow["item_code"], "class='stock' data-stock_id='" . $myrow['item_code'] . "'");
            label_cell($myrow["description"]);
            label_cell(Dates::sql2date($myrow["delivery_date"]));
            $dec = Item::qty_dec($myrow["item_code"]);
            qty_cell($myrow["qty_recd"], FALSE, $dec);
            qty_cell($myrow["quantity_inv"], FALSE, $dec);
            if ($creditor_trans->is_invoice) {
              qty_cells(NULL, 'this_quantity_inv' . $n, Num::format($myrow["qty_recd"] - $myrow["quantity_inv"], $dec), NULL, NULL, $dec);
            }
            else {
              qty_cells(NULL, 'This_QuantityCredited' . $n, Num::format(max($myrow["quantity_inv"], 0), $dec), NULL, NULL, $dec);
            }
            $dec2 = 0;
            amount_cells(NULL, 'ChgPrice' . $n, Num::price_decimal($myrow["unit_price"], $dec2), NULL, NULL, $dec2, 'ChgPriceCalc' . $n);
            amount_cells(NULL, 'ExpPrice' . $n, Num::price_decimal($myrow["unit_price"], $dec2), NULL, NULL, $dec2, 'ExpPriceCalc' . $n);
            small_amount_cells(NULL, 'ChgDiscount' . $n, Num::percent_format($myrow['discount'] * 100), NULL, NULL, User::percent_dec());
            amount_cell(Num::price_decimal(($myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]) * (1 - $myrow['discount'])) / $myrow["qty_recd"], $dec2), FALSE, $dec2, 'Ea' . $n);
            if ($creditor_trans->is_invoice) {
              amount_cells(NULL, 'ChgTotal' . $n, Num::price_decimal($myrow["unit_price"] * ($myrow["qty_recd"] - $myrow["quantity_inv"]) * (1 - $myrow['discount']), $dec2), NULL, NULL, $dec2, 'ChgTotalCalc' . $n);
            }
            else {
              amount_cell(Num::round($myrow["unit_price"] * max($myrow['quantity_inv'], 0) * (1 - $myrow['discount']), User::price_dec()));
            }
            submit_cells('grn_item_id' . $n, _("Add"), '', ($creditor_trans->is_invoice ? _("Add to Invoice") : _("Add to Credit Note")), TRUE);
            if ($creditor_trans->is_invoice && User::i()->can_access(SA_GRNDELETE)
            ) { // Added 2008-10-18 by Joe Hunt. Special access rights needed.
              submit_cells('void_item_id' . $n, _("Remove"), '', _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!"), TRUE);
              submit_js_confirm('void_item_id' . $n, sprintf(_('You are about to remove all yet non-invoiced items from delivery line #%d. This operation also irreversibly changes related order line. Do you want to continue ?'), $n));
            }
            Display::link_params_td("/purchases/po_entry_items.php", _("Modify"), "ModifyOrder=" . $myrow["purch_order_no"], ' class="button"') . end_row();
          }
        }
      }
      if (isset($_SESSION['delivery_grn'])) {
        unset($_SESSION['delivery_grn']);
      }
      return TRUE;
    }
    // $mode = 0 none at the moment
    //		 = 1 display on invoice/credit page
    //		 = 2 display on view invoice
    //		 = 3 display on view credit
    /**
     * @static
     *
     * @param     $creditor_trans
     * @param int $mode
     *
     * @return float|int
     */
    static public function display_items($creditor_trans, $mode = 0) {
      $ret = TRUE;
      // if displaying in form, and no items, exit
      if (($mode == 2 || $mode == 3) && count($creditor_trans->grn_items) == 0) {
        return 0;
      }
      start_outer_table('tablestyle_noborder');
      $heading2 = "";
      if ($mode == 1) {
        if ($creditor_trans->is_invoice) {
          $heading = _("Items Received Yet to be Invoiced");
          if (User::i()->can_access(SA_GRNDELETE)) // Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
          {
            $heading2 = _("WARNING! Be careful with removal. The operation is executed immediately and cannot be undone !!!");
          }
        }
        else {
          $heading = _("Delivery Item Selected For Adding To A Supplier Credit Note");
        }
      }
      else {
        if ($creditor_trans->is_invoice) {
          $heading = _("Received Items Charged on this Invoice");
        }
        else {
          $heading = _("Received Items Credited on this Note");
        }
      }
      Display::heading($heading);
      if ($mode == 1) {
        /*	if (!$creditor_trans->is_invoice && !isset($_POST['invoice_no'])) {
                   echo "</td>";
                   date_cells(_("Received between"), 'receive_begin', "", null, -30, 0, 0, "class='vmiddle'");
                   date_cells(_("and"), 'receive_end', '', null, 1, 0, 0, "class='vmiddle'");
                   submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), true);
                   echo "<td>";
                 }*/
        if ($heading2 != "") {
          Event::warning($heading2, 0, 0, "class='overduefg'");
        }
        echo "</td><td width=10% class='right'>";
        submit('InvGRNAll', _("Add All Items"), TRUE, FALSE, 'button-large');
        end_outer_table(0, FALSE);
        start_outer_table('center');
        start_row();
        date_cells(_("Received between"), 'receive_begin', "", NULL, -30, 0, 0, "class='vmiddle'");
        date_cells(_("and"), 'receive_end', '', NULL, 1, 0, 0, "class='vmiddle'");
        submit_cells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), TRUE);
        end_row();
      }
      end_outer_table(0, FALSE);
      Display::div_start('grn_items');
      start_table('tablestyle2 width90');
      if ($mode == 1) {
        $th = array(
          _("Delivery"),
          _("Seq #"),
          _("P.O."),
          _("Item"),
          _("Description"),
          _("Date"),
          _("Received"),
          _("Invoiced"),
          _("Qty"),
          _("Price"),
          _("ExpPrice"),
          _('Discount %'),
          _('Ea Price'),
          _("Total"),
          "",
          "",
          ""
        );
        // if ($creditor_trans->is_invoice && CurrentUser::get()->can_access(SA_GRNDELETE)) // Added 2008-10-18 by Joe Hunt. Only admins can remove GRNs
        // $th[] = "";
        if (!$creditor_trans->is_invoice) {
          unset($th[14]);
          $th[8] = _("Qty Yet To Credit");
        }
      }
      else {
        $th = array(
          _("Delivery"), _("Item"), _("Description"), _("Quantity"), _("Price"), _("Expected Price"), _("Discount %"), _("Each Price"), _("Line Value")
        );
      }
      table_header($th);
      $total_grn_value = 0;
      $i = $k = 0;
      if (count($creditor_trans->grn_items) > 0) {
        foreach ($creditor_trans->grn_items as $entered_grn) {
          alt_table_row_color($k);
          $grn_batch = Purch_GRN::get_batch_for_item($entered_grn->id);
          label_cell(GL_UI::trans_view(ST_SUPPRECEIVE, $grn_batch));
          if ($mode == 1) {
            label_cell($entered_grn->id);
            label_cell(""); // PO
          }
          label_cell($entered_grn->item_code, "class='stock' data-stock_id='{$entered_grn->item_code}'");
          label_cell($entered_grn->description);
          $dec = Item::qty_dec($entered_grn->item_code);
          if ($mode == 1) {
            label_cell("");
            qty_cell($entered_grn->qty_recd, FALSE, $dec);
            qty_cell($entered_grn->prev_quantity_inv, FALSE, $dec);
          }
          qty_cell(abs($entered_grn->this_quantity_inv), TRUE, $dec);
          amount_decimal_cell($entered_grn->chg_price);
          amount_decimal_cell($entered_grn->exp_price);
          percent_cell($entered_grn->discount);
          amount_decimal_cell(Num::round(($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100)) / abs($entered_grn->this_quantity_inv)), User::price_dec());
          amount_cell(Num::round($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100), User::price_dec()));
          if ($mode == 1) {
            if ($creditor_trans->is_invoice && User::i()->can_access(SA_GRNDELETE)) {
              label_cell("");
            }
            label_cell(""); // PO
            delete_button_cell("Delete" . $entered_grn->id, _("Edit"), _('Edit document line'));
          }
          end_row();
          $total_grn_value += Num::round($entered_grn->chg_price * abs($entered_grn->this_quantity_inv) * (1 - $entered_grn->discount / 100), User::price_dec());
          $i++;
          if ($i > 15) {
            $i = 0;
            table_header($th);
          }
        }
      }
      if ($mode == 1) {
        $ret = Purch_GRN::display_for_selection($creditor_trans, $k);
        $colspan = 13;
      }
      else {
        $colspan = 8;
      }
      label_row(_("Total"), Num::price_format($total_grn_value), "colspan=$colspan class='right bold'", "nowrap class='right bold'");
      if (!$ret) {
        start_row();
        echo "<td colspan=" . ($colspan + 1) . ">";
        if ($creditor_trans->is_invoice) {
          Event::warning(_("There are no outstanding items received from this supplier that have not been invoiced by them."), 0, 0);
        }
        else {
          Event::warning(_("There are no received items for the selected supplier that have been invoiced.<br>Credits can only be applied to invoiced items."));
        }
        echo "</td>";
        end_row();
      }
      end_table(1);
      Display::div_end();
      return $total_grn_value;
    }
  }
