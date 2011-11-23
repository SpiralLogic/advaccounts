<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	//------------------- update average material cost ------------------------------------------ Joe Hunt Mar-03-2008
	class Purch_GRN {
		public static function update_average_material_cost($supplier, $stock_id, $price, $qty, $date, $adj_only = false)
	
	{
		if ($supplier != null) {
			$currency = Banking::get_supplier_currency($supplier);
		} else {
			$currency = null;
		}
		$dec = User::price_dec();
		Num::price_decimal($price, $dec);
		$price = Num::round($price, $dec);
		if ($currency != null) {
			$ex_rate = Banking::get_exchange_rate_to_home_currency($currency, $date);
			$price_in_home_currency = $price / $ex_rate;
		} else
		{
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
		} else {
			$exclude = 0;
		}
		$cost_adjust = false;
		$qoh = Item::get_qoh_on_date($stock_id, null, $date, $exclude);
		if ($adj_only) {
			if ($qoh > 0) {
				$material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency) / $qoh;
			}
		} else {
			if ($qoh < 0) {
				if ($qoh + $qty > 0) {
					$cost_adjust = true;
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

	//-------------------------------------------------------------------------------------------------------------
	public static function add(&$po, $date_, $reference, $location)
	{
		DB::begin_transaction();
		$grn = static::add_batch($po->order_no, $po->supplier_id, $reference, $location, $date_);
		foreach ($po->line_items as $order_line) {
			if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty)) {
				/*Update sales_order_details for the new quantity received and the standard cost used for postings to GL and recorded in the stock movements for FIFO/LIFO stocks valuations*/
				//------------------- update average material cost ------------------------------------------ Joe Hunt Mar-03-2008
				static::update_average_material_cost($po->supplier_id, $order_line->stock_id, $order_line->price, $order_line->receive_qty, $date_);
				//----------------------------------------------------------------------------------------------------------------
				if ($order_line->qty_received == 0) {
					/*This must be the first receipt of goods against this line */
					/*Need to get the standard cost as it is now so we can process GL jorunals later*/
					$order_line->standard_cost = Item_Price::get_standard_cost($order_line->stock_id);
				}
				if ($order_line->price <= $order_line->standard_cost) {
					Purch_Order::add_or_update_data($po->supplier_id, $order_line->stock_id, $order_line->price);
				}
				/*Need to insert a grn item */
				$grn_item = static::add_item($grn, $order_line->po_detail_rec, $order_line->stock_id, $order_line->description, $order_line->standard_cost, $order_line->receive_qty, $order_line->price,
																				$order_line->discount);
				/* Update location stock records - NB  a po cannot be entered for a service/kit parts */
				Inv_Movement::add(ST_SUPPRECEIVE, $order_line->stock_id, $grn, $location, $date_, "", $order_line->receive_qty, $order_line->standard_cost, $po->supplier_id, 1, $order_line->price);
			} /*quantity received is != 0 */
		} /*end of order_line loop */
		$grn_item = static::add_item($grn, Purch_Order::add_freight($po, $date_), 'Freight', 'Freight Charges', 0, 1, $po->freight, 0);
		Refs::save(ST_SUPPRECEIVE, $grn, $reference);
		DB_AuditTrail::add(ST_SUPPRECEIVE, $grn, $date_);
		DB::commit_transaction();
		return $grn;
	}


	public static function add_batch($po_number, $supplier_id, $reference, $location, $date_)
	{
		$date = Dates::date2sql($date_);
		$sql
		 = "INSERT INTO grn_batch (purch_order_no, delivery_date, supplier_id, reference, loc_code)
			VALUES (" . DB::escape($po_number) . ", " . DB::escape($date) . ", " . DB::escape($supplier_id) . ", " . DB::escape($reference) . ", " . DB::escape($location) . ")";
		DB::query($sql, "A grn batch record could not be inserted.");
		return DB::insert_id();
	}

	//-------------------------------------------------------------------------------------------------------------
	public static function add_item($grn_batch_id, $po_detail_item, $item_code, $description, $standard_unit_cost, $quantity_received, $price, $discount)
	{
		$sql
		 = "UPDATE purch_order_details
        SET quantity_received = quantity_received + " . DB::escape($quantity_received) . ",
        std_cost_unit=" . DB::escape($standard_unit_cost) . ",
        discount=" . DB::escape($discount) . ",
        act_price=" . DB::escape($price) . "
        WHERE po_detail_item = " . DB::escape($po_detail_item);
		DB::query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
		$sql
		 = "INSERT INTO grn_items (grn_batch_id, po_detail_item, item_code, description, qty_recd, discount)
		VALUES (" . DB::escape($grn_batch_id) . ", " . DB::escape($po_detail_item) . ", " . DB::escape($item_code) . ", " . DB::escape($description) . ", " . DB::escape($quantity_received) . ", " . DB::escape($discount) . ")";
		DB::query($sql, "A GRN detail item could not be inserted.");
		return DB::insert_id();
	}

	//----------------------------------------------------------------------------------------
	public static function get_batch_for_item($item)
	{
		$sql = "SELECT grn_batch_id FROM grn_items WHERE id=" . DB::escape($item);
		$result = DB::query($sql, "Could not retreive GRN batch id");
		$row = DB::fetch_row($result);
		return $row[0];
	}

	public static function get_batch($grn)
	{
		$sql = "SELECT * FROM grn_batch WHERE id=" . DB::escape($grn);
		$result = DB::query($sql, "Could not retreive GRN batch id");
		return DB::fetch($result);
	}

	public static function set_item_credited(&$entered_grn, $supplier, $transno, $date)
	{
		$mcost = static::update_average_material_cost($supplier, $entered_grn->item_code, $entered_grn->chg_price, $entered_grn->this_quantity_inv, $date);
		$sql
		 = "SELECT grn_batch.*, grn_items.*
    	FROM grn_batch, grn_items
    	WHERE grn_items.grn_batch_id=grn_batch.id
		AND grn_items.id=" . DB::escape($entered_grn->id) . "
    	AND grn_items.item_code=" . DB::escape($entered_grn->item_code);
		$result = DB::query($sql, "Could not retreive GRNS");
		$myrow = DB::fetch($result);
		$sql
		 = "UPDATE purch_order_details
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

	public static function get_items($grn_batch_id = 0, $supplier_id = "", $outstanding_only = false, $is_invoiced_only = false, $invoice_no = 0, $begin = "", $end = "")
	{
		$sql = "SELECT "
					 . "grn_batch.*, "
					 . "grn_items.*, "
					 . "purch_order_details.unit_price, "
					 . "purch_order_details.std_cost_unit, units
    	    FROM "
					 . "grn_batch, "
					 . "grn_items, "
					 . "purch_order_details, "
					 . "stock_master";
		if ($invoice_no != 0) {
			$sql .= ", supp_invoice_items";
		}
		$sql .= " WHERE "
						. "grn_items.grn_batch_id="
						. "grn_batch.id AND "
						. "grn_items.po_detail_item="
						. "purch_order_details.po_detail_item";
		if ($invoice_no != 0) {
			$sql .= " AND "
							. "supp_invoice_items.supp_trans_type="
							. ST_SUPPINVOICE . " AND "
							. "supp_invoice_items.supp_trans_no=$invoice_no AND "
							. "grn_items.id="
							. "supp_invoice_items.grn_item_id";
		}
		$sql .= " AND "
						. "stock_master.stock_id="
						. "grn_items.item_code ";
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

	//----------------------------------------------------------------------------------------
	// get the details for a given grn item
	public static function get_item($grn_item_no)
	{
		$sql
		 = "SELECT grn_items.*, purch_order_details.unit_price,
    	grn_items.qty_recd - grn_items.quantity_inv AS QtyOstdg,
    	purch_order_details.std_cost_unit
		FROM grn_items, purch_order_details, stock_master
		WHERE grn_items.po_detail_item=purch_order_details.po_detail_item
 			AND stock_master.stock_id=grn_items.item_code
			AND grn_items.id=" . DB::escape($grn_item_no);
		$result = DB::query($sql, "could not retreive grn item details");
		return DB::fetch($result);
	}

	//----------------------------------------------------------------------------------------
	public static function get_items_to_order($grn_batch, &$order)
	{
		$result = static::get_items($grn_batch);
		if (DB::num_rows($result) > 0) {
			while ($myrow = DB::fetch($result)) {
				if (is_null($myrow["units"])) {
					$units = "";
				} else {
					$units = $myrow["units"];
				}
				$order->add_to_order($order->lines_on_order + 1, $myrow["item_code"], 1, $myrow["description"],
														 $myrow["unit_price"], $units, Dates::sql2date($myrow["delivery_date"]),
														 $myrow["quantity_inv"], $myrow["qty_recd"], $myrow['discount']);
				$order->line_items[$order->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
			} /* line po from purchase order details */
		} //end of checks on returned data set
	}

	//----------------------------------------------------------------------------------------
	// read a grn into an order class
	public static function get($grn_batch, &$order)
	{
		$sql = "SELECT *	FROM grn_batch WHERE id=" . DB::escape($grn_batch);
		$result = DB::query($sql, "The grn sent is not valid");
		$row = DB::fetch($result);
		$po_number = $row["purch_order_no"];
		$result = Purch_Order::get_header($po_number, $order);
		if ($result) {
			$order->orig_order_date = Dates::sql2date($row["delivery_date"]);
			$order->location = $row["loc_code"];
			$order->reference = $row["reference"];
			static::get_items_to_order($grn_batch, $order);
		}
	}

	//----------------------------------------------------------------------------------------------------------
	// get the GRNs (batch info not details) for a given po number
	public static function get_for_po($po_number)
	{
		$sql = "SELECT * FROM grn_batch WHERE purch_order_no=" . DB::escape($po_number);
		return DB::query($sql, "The grns for the po $po_number could not be retreived");
	}

	//----------------------------------------------------------------------------------------------------------
	public static function exists($grn_batch)
	{
		$sql = "SELECT id FROM grn_batch WHERE id=" . DB::escape($grn_batch);
		$result = DB::query($sql, "Cannot retreive a grn");
		return (DB::num_rows($result) > 0);
	}

	//----------------------------------------------------------------------------------------------------------
	public static function exists_on_invoices($grn_batch)
	{
		$sql
		 = "SELECT supp_invoice_items.id FROM supp_invoice_items,grn_items
		WHERE supp_invoice_items.grn_item_id=grn_items.id
		AND quantity != 0
		AND grn_batch_id=" . DB::escape($grn_batch);
		$result = DB::query($sql, "Cannot query GRNs");
		return (DB::num_rows($result) > 0);
	}

	//----------------------------------------------------------------------------------------------------------
	public static function void($grn_batch)
	{
		// if this grn is references on any invoices/credit notes, then it
		// can't be voided
		if (static::exists_on_invoices($grn_batch)) {
			return false;
		}
		DB::begin_transaction();
		Bank_Trans::void(ST_SUPPRECEIVE, $grn_batch, true);
		GL_Trans::void(ST_SUPPRECEIVE, $grn_batch, true);
		// clear the quantities of the grn items in the POs and invoices
		$result = static::get_items($grn_batch);
		if (DB::num_rows($result) > 0) {
			while ($myrow = DB::fetch($result)) {
				$sql
				 = "UPDATE purch_order_details
                SET quantity_received = quantity_received - " . $myrow["qty_recd"] . "
                WHERE po_detail_item = " . $myrow["po_detail_item"];
				DB::query($sql, "a purchase order details record could not be voided.");
			}
		}
		// clear the quantities in the grn items
		$sql
		 = "UPDATE grn_items SET qty_recd=0, quantity_inv=0
		WHERE grn_batch_id=" . DB::escape($grn_batch);
		DB::query($sql, "A grn detail item could not be voided.");
		// clear the stock move items
		Inv_Movement::void(ST_SUPPRECEIVE, $grn_batch);
		DB::commit_transaction();
		return true;
	}
	}