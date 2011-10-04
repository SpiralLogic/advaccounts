<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	//------------------- update average material cost ------------------------------------------ Joe Hunt Mar-03-2008

	function update_average_material_cost($supplier, $stock_id, $price, $qty, $date, $adj_only = false) {
		if ($supplier != null) {
			$currency = Banking::get_supplier_currency($supplier);
		} else {
			$currency = null;
		}
		$dec = user_price_dec();
		price_decimal_format($price, $dec);
		$price = round2($price, $dec);
		if ($currency != null) {
			$ex_rate = Banking::get_exchange_rate_to_home_currency($currency, $date);
			$price_in_home_currency = $price / $ex_rate;
		} else
			$price_in_home_currency = $price;
		$sql = "SELECT material_cost FROM stock_master WHERE stock_id=" . db_escape($stock_id);
		$result = db_query($sql);
		$myrow = db_fetch($result);
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
		$qoh = get_qoh_on_date($stock_id, null, $date, $exclude);
		if ($adj_only) {
			if ($qoh > 0)
				$material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency) / $qoh;
		} else {
			if ($qoh < 0) {
				if ($qoh + $qty > 0)
					$cost_adjust = true;
				$qoh = 0;
			}
			if ($qoh + $qty != 0)
				$material_cost = ($qoh * $material_cost + $qty * $price_in_home_currency) / ($qoh + $qty);
		}
		$material_cost = round2($material_cost, $dec);
		if ($cost_adjust) // new 2010-02-10
		{
			adjust_deliveries($stock_id, $material_cost, $date);
		}
		$sql = "UPDATE stock_master SET material_cost=" . db_escape($material_cost) . "
		WHERE stock_id=" . db_escape($stock_id);
		db_query($sql, "The cost details for the inventory item could not be updated");
		return $material_cost;
	}

	//-------------------------------------------------------------------------------------------------------------

	function add_grn(&$po, $date_, $reference, $location) {
		global $Refs;

		begin_transaction();

		$grn = add_grn_batch($po->order_no, $po->supplier_id, $reference, $location, $date_);

		foreach ($po->line_items as $order_line) {

			if ($order_line->receive_qty != 0 && $order_line->receive_qty != "" && isset($order_line->receive_qty)) {

				/*Update sales_order_details for the new quantity received and the standard cost used for postings to GL and recorded in the stock movements for FIFO/LIFO stocks valuations*/
				//------------------- update average material cost ------------------------------------------ Joe Hunt Mar-03-2008
				update_average_material_cost($po->supplier_id, $order_line->stock_id, $order_line->price, $order_line->receive_qty, $date_);
				//----------------------------------------------------------------------------------------------------------------
				if ($order_line->qty_received == 0) {
					/*This must be the first receipt of goods against this line */
					/*Need to get the standard cost as it is now so we can process GL jorunals later*/
					$order_line->standard_cost = get_standard_cost($order_line->stock_id);
				}
				if ($order_line->price <= $order_line->standard_cost) {
					add_or_update_purchase_data($po->supplier_id, $order_line->stock_id, $order_line->price);
				}
				/*Need to insert a grn item */
				$grn_item = add_grn_detail_item($grn, $order_line->po_detail_rec, $order_line->stock_id, $order_line->description, $order_line->standard_cost, $order_line->receive_qty, $order_line->price,
					$order_line->discount);
				/* Update location stock records - NB  a po cannot be entered for a service/kit parts */
				add_stock_move(ST_SUPPRECEIVE, $order_line->stock_id, $grn, $location, $date_, "", $order_line->receive_qty, $order_line->standard_cost, $po->supplier_id, 1, $order_line->price);
			} /*quantity received is != 0 */
		} /*end of order_line loop */

		$grn_item = add_grn_detail_item($grn, add_freight_to_po($po, $date_), 'Freight', 'Freight Charges', 0, 1, $po->freight, 0);
		$Refs->save(ST_SUPPRECEIVE, $grn, $reference);

		add_audit_trail(ST_SUPPRECEIVE, $grn, $date_);

		commit_transaction();

		return $grn;
	}

	//----------------------------------------------------------------------------------------
	function	 add_freight_to_po(&$po, $date_) {
		$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";

		$sql .= $po->order_no . "," .
		 db_escape('freight') . "," .
		 db_escape('Freight Charges') . ",'" .
		 date2sql($date_) . "'," .
		 db_escape($po->freight) . ", " .
		 db_escape(1) . ", " .
		 db_escape(0) .
		 ")";
		db_query($sql, "One of the purchase order detail records could not be updated");
		return db_insert_id();
	}

	function add_grn_batch($po_number, $supplier_id, $reference, $location, $date_) {
		$date = date2sql($date_);

		$sql = "INSERT INTO grn_batch (purch_order_no, delivery_date, supplier_id, reference, loc_code)
			VALUES (" . db_escape($po_number) . ", " . db_escape($date) . ", " . db_escape($supplier_id) . ", " . db_escape($reference) . ", " . db_escape($location) . ")";

		db_query($sql, "A grn batch record could not be inserted.");

		return db_insert_id();
	}

	//-------------------------------------------------------------------------------------------------------------

	function add_grn_detail_item($grn_batch_id, $po_detail_item, $item_code, $description, $standard_unit_cost, $quantity_received, $price, $discount) {
		$sql = "UPDATE purch_order_details
        SET quantity_received = quantity_received + " . db_escape($quantity_received) . ",
        std_cost_unit=" . db_escape($standard_unit_cost) . ",
        discount=" . db_escape($discount) . ",
        act_price=" . db_escape($price) . "
        WHERE po_detail_item = " . db_escape($po_detail_item);
		db_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");
		$sql = "INSERT INTO grn_items (grn_batch_id, po_detail_item, item_code, description, qty_recd, discount)
		VALUES (" . db_escape($grn_batch_id) . ", " . db_escape($po_detail_item) . ", " . db_escape($item_code) . ", " . db_escape($description) . ", " . db_escape($quantity_received) . ", " . db_escape($discount) . ")";
		db_query($sql, "A GRN detail item could not be inserted.");
		return db_insert_id();
	}

	//----------------------------------------------------------------------------------------
	function get_grn_batch_from_item($item) {
		$sql = "SELECT grn_batch_id FROM grn_items WHERE id=" . db_escape($item);
		$result = db_query($sql, "Could not retreive GRN batch id");
		$row = db_fetch_row($result);
		return $row[0];
	}

	function get_grn_batch($grn) {
		$sql = "SELECT * FROM grn_batch WHERE id=" . db_escape($grn);
		$result = db_query($sql, "Could not retreive GRN batch id");
		return db_fetch($result);
	}

	function set_grn_item_credited(&$entered_grn, $supplier, $transno, $date) {
		$mcost = update_average_material_cost($supplier, $entered_grn->item_code, $entered_grn->chg_price, $entered_grn->this_quantity_inv, $date);

		$sql = "SELECT grn_batch.*, grn_items.*
    	FROM grn_batch, grn_items
    	WHERE grn_items.grn_batch_id=grn_batch.id
		AND grn_items.id=" . db_escape($entered_grn->id) . "
    	AND grn_items.item_code=" . db_escape($entered_grn->item_code);
		$result = db_query($sql, "Could not retreive GRNS");
		$myrow = db_fetch($result);

		$sql = "UPDATE purch_order_details
        SET quantity_received = quantity_received + " . db_escape($entered_grn->this_quantity_inv) . ",
        quantity_ordered = quantity_ordered + " . db_escape($entered_grn->this_quantity_inv) . ",
        qty_invoiced = qty_invoiced + " . db_escape($entered_grn->this_quantity_inv) . ",
        std_cost_unit=" . db_escape($mcost) . ",
        act_price=" . db_escape($entered_grn->chg_price) . "
        WHERE po_detail_item = " . $myrow["po_detail_item"];
		db_query($sql, "a purchase order details record could not be updated. This receipt of goods has not been processed ");

		//$sql = "UPDATE ".''."grn_items SET qty_recd=0, quantity_inv=0 WHERE id=$entered_grn->id";
		$sql = "UPDATE grn_items SET qty_recd=qty_recd+" . db_escape($entered_grn->this_quantity_inv) . ",quantity_inv=quantity_inv+" . db_escape($entered_grn->this_quantity_inv) . " WHERE id=" . db_escape($entered_grn->id);
		db_query($sql);

		add_stock_move(ST_SUPPCREDIT, $entered_grn->item_code, $transno, $myrow['loc_code'], $date, "", $entered_grn->this_quantity_inv, $mcost, $supplier, 1, $entered_grn->chg_price);
	}

	function get_grn_items($grn_batch_id = 0, $supplier_id = "", $outstanding_only = false, $is_invoiced_only = false, $invoice_no = 0, $begin = "", $end = "") {
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
			$sql .= " AND grn_batch.delivery_date>='" . date2sql($begin) . "'";
		}
		if ($end != "") {
			$sql .= " AND grn_batch.delivery_date<='" . date2sql($end) . "'";
		}
		if ($grn_batch_id != 0) {
			$sql .= " AND grn_batch.id=" . db_escape($grn_batch_id) . " AND grn_items.grn_batch_id=" . db_escape($grn_batch_id);
		}
		if ($is_invoiced_only) {
			$sql .= " AND grn_items.quantity_inv > 0";
		}
		if ($outstanding_only) {
			$sql .= " AND grn_items.qty_recd - grn_items.quantity_inv > 0";
		}
		if ($supplier_id != "") {
			$sql .= " AND grn_batch.supplier_id =" . db_escape($supplier_id);
		}
		$sql .= " ORDER BY grn_batch.delivery_date, grn_batch.id, grn_items.id";
		return db_query($sql, "Could not retreive GRNS");
	}

	//----------------------------------------------------------------------------------------

	// get the details for a given grn item

	function get_grn_item_detail($grn_item_no) {
		$sql = "SELECT grn_items.*, purch_order_details.unit_price,
    	grn_items.qty_recd - grn_items.quantity_inv AS QtyOstdg,
    	purch_order_details.std_cost_unit
		FROM grn_items, purch_order_details, stock_master
		WHERE grn_items.po_detail_item=purch_order_details.po_detail_item
 			AND stock_master.stock_id=grn_items.item_code
			AND grn_items.id=" . db_escape($grn_item_no);

		$result = db_query($sql, "could not retreive grn item details");
		return db_fetch($result);
	}

	//----------------------------------------------------------------------------------------

	function read_grn_items_to_order($grn_batch, &$order) {
		$result = get_grn_items($grn_batch);

		if (db_num_rows($result) > 0) {

			while ($myrow = db_fetch($result)) {

				if (is_null($myrow["units"])) {
					$units = "";
				} else {
					$units = $myrow["units"];
				}

				$order->add_to_order($order->lines_on_order + 1, $myrow["item_code"], 1, $myrow["description"],
					$myrow["unit_price"], $units, sql2date($myrow["delivery_date"]),
					$myrow["quantity_inv"], $myrow["qty_recd"], $myrow['discount']);

				$order->line_items[$order->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
			} /* line po from purchase order details */
		} //end of checks on returned data set
	}

	//----------------------------------------------------------------------------------------

	// read a grn into an order class

	function read_grn($grn_batch, &$order) {
		$sql = "SELECT *	FROM grn_batch WHERE id=" . db_escape($grn_batch);

		$result = db_query($sql, "The grn sent is not valid");

		$row = db_fetch($result);
		$po_number = $row["purch_order_no"];

		$result = read_po_header($po_number, $order);

		if ($result) {

			$order->orig_order_date = sql2date($row["delivery_date"]);
			$order->location = $row["loc_code"];
			$order->reference = $row["reference"];

			read_grn_items_to_order($grn_batch, $order);
		}
	}

	//----------------------------------------------------------------------------------------------------------

	// get the GRNs (batch info not details) for a given po number

	function get_po_grns($po_number) {
		$sql = "SELECT * FROM grn_batch WHERE purch_order_no=" . db_escape($po_number);

		return db_query($sql, "The grns for the po $po_number could not be retreived");
	}

	//----------------------------------------------------------------------------------------------------------

	function exists_grn($grn_batch) {
		$sql = "SELECT id FROM grn_batch WHERE id=" . db_escape($grn_batch);
		$result = db_query($sql, "Cannot retreive a grn");

		return (db_num_rows($result) > 0);
	}

	//----------------------------------------------------------------------------------------------------------

	function exists_grn_on_invoices($grn_batch) {
		$sql = "SELECT supp_invoice_items.id FROM supp_invoice_items,grn_items
		WHERE supp_invoice_items.grn_item_id=grn_items.id
		AND quantity != 0
		AND grn_batch_id=" . db_escape($grn_batch);
		$result = db_query($sql, "Cannot query GRNs");

		return (db_num_rows($result) > 0);
	}

	//----------------------------------------------------------------------------------------------------------

	function void_grn($grn_batch) {
		// if this grn is references on any invoices/credit notes, then it
		// can't be voided
		if (exists_grn_on_invoices($grn_batch)) {
			return false;
		}

		begin_transaction();

		void_bank_trans(ST_SUPPRECEIVE, $grn_batch, true);
		void_gl_trans(ST_SUPPRECEIVE, $grn_batch, true);

		// clear the quantities of the grn items in the POs and invoices
		$result = get_grn_items($grn_batch);

		if (db_num_rows($result) > 0) {

			while ($myrow = db_fetch($result)) {

				$sql = "UPDATE purch_order_details
                SET quantity_received = quantity_received - " . $myrow["qty_recd"] . "
                WHERE po_detail_item = " . $myrow["po_detail_item"];

				db_query($sql, "a purchase order details record could not be voided.");
			}
		}

		// clear the quantities in the grn items
		$sql = "UPDATE grn_items SET qty_recd=0, quantity_inv=0
		WHERE grn_batch_id=" . db_escape($grn_batch);

		db_query($sql, "A grn detail item could not be voided.");

		// clear the stock move items
		void_stock_move(ST_SUPPRECEIVE, $grn_batch);

		commit_transaction();

		return true;
	}