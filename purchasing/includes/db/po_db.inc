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
	//----------------------------------------------------------------------------------------

	function delete_po($po) {
		$sql = "DELETE FROM purch_orders WHERE order_no=" . DBOld::escape($po);
		DBOld::query($sql, "The order header could not be deleted");

		$sql = "DELETE FROM purch_order_details WHERE order_no =" . DBOld::escape($po);
		DBOld::query($sql, "The order detail lines could not be deleted");
	}

	//----------------------------------------------------------------------------------------

	function add_po(&$po_obj) {

		DBOld::begin_transaction();

		/*Insert to purchase order header record */
		$sql = "INSERT INTO purch_orders (supplier_id, Comments, ord_date, reference, requisition_no, into_stock_location, delivery_address, freight, salesman) VALUES(";
		$sql .= DBOld::escape($po_obj->supplier_id) . "," .
		 DBOld::escape($po_obj->Comments) . ",'" .
		 Dates::date2sql($po_obj->orig_order_date) . "', " .
		 DBOld::escape($po_obj->reference) . ", " .
		 DBOld::escape($po_obj->requisition_no) . ", " .
		 DBOld::escape($po_obj->Location) . ", " .
		 DBOld::escape($po_obj->delivery_address) . ", " .
		 DBOld::escape($po_obj->freight) . ", " .
		 DBOld::escape($po_obj->salesman) . ")";
		DBOld::query($sql, "The purchase order header record could not be inserted");

		/*Get the auto increment value of the order number created from the sql above */
		$po_obj->order_no = DBOld::insert_id();

		/*Insert the purchase order detail records */
		foreach ($po_obj->line_items as $po_line)
		{
			if ($po_line->Deleted == false) {
				$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
				$sql .= $po_obj->order_no . ", " . DBOld::escape($po_line->stock_id) . "," .
				 DBOld::escape($po_line->description) . ",'" .
				 Dates::date2sql($po_line->req_del_date) . "'," .
				 DBOld::escape($po_line->price) . ", " .
				 DBOld::escape($po_line->quantity) . ", " .
				 DBOld::escape($po_line->discount) . ")";
				DBOld::query($sql, "One of the purchase order detail records could not be inserted");
			}
		}

		Refs::save(ST_PURCHORDER, $po_obj->order_no, $po_obj->reference);

		//DB_Comments::add(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date, $po_obj->Comments);

		DB_AuditTrail::add(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date);
		DBOld::commit_transaction();

		return $po_obj->order_no;
	}

	//----------------------------------------------------------------------------------------

	function update_po(&$po_obj) {
		DBOld::begin_transaction();

		/*Update the purchase order header with any changes */
		$sql = "UPDATE purch_orders SET Comments=" . DBOld::escape($po_obj->Comments) . ",
		requisition_no= " . DBOld::escape($po_obj->requisition_no) . ",
		into_stock_location=" . DBOld::escape($po_obj->Location) . ",
		ord_date='" . Dates::date2sql($po_obj->orig_order_date) . "',
		delivery_address=" . DBOld::escape($po_obj->delivery_address) . ",
		freight=" . DBOld::escape($po_obj->freight) . ",
		salesman=" . DBOld::escape($po_obj->salesman);
		$sql .= " WHERE order_no = " . $po_obj->order_no;
		DBOld::query($sql, "The purchase order could not be updated");

		/*Now Update the purchase order detail records */
		foreach ($po_obj->line_items as $po_line)
		{

			if ($po_line->Deleted == True) {
				// Sherifoz 21.06.03 Handle deleting existing lines
				if ($po_line->po_detail_rec != '') {
					$sql = "DELETE FROM purch_order_details WHERE po_detail_item=" . DBOld::escape($po_line->po_detail_rec);
					DBOld::query($sql, "could not query purch order details");
				}
			}
			else if ($po_line->po_detail_rec == '') {
				// Sherifoz 21.06.03 Handle adding new lines vs. updating. if no key(po_detail_rec) then it's a new line
				$sql = "INSERT INTO purch_order_details (order_no, item_code, description, delivery_date, unit_price, quantity_ordered, discount) VALUES (";
				$sql .= $po_obj->order_no . "," .
				 DBOld::escape($po_line->stock_id) . "," .
				 DBOld::escape($po_line->description) . ",'" .
				 Dates::date2sql($po_line->req_del_date) . "'," .
				 DBOld::escape($po_line->price) . ", " .
				 DBOld::escape($po_line->quantity) . ", " .
				 DBOld::escape($po_line->discount) .
				 ")";
			}
			else
			{
				$sql = "UPDATE purch_order_details SET item_code=" . DBOld::escape($po_line->stock_id) . ",
				description =" . DBOld::escape($po_line->description) . ",
				delivery_date ='" . Dates::date2sql($po_line->req_del_date) . "',
				unit_price=" . DBOld::escape($po_line->price) . ",
				quantity_ordered=" . DBOld::escape($po_line->quantity) . ",
				discount=" . DBOld::escape($po_line->discount) . "
				WHERE po_detail_item=" . DBOld::escape($po_line->po_detail_rec);
			}
			DBOld::query($sql, "One of the purchase order detail records could not be updated");
		}

		//DB_Comments::add(ST_PURCHORDER, $po_obj->order_no, $po_obj->orig_order_date, $po_obj->Comments);

		DBOld::commit_transaction();

		return $po_obj->order_no;
	}

	//----------------------------------------------------------------------------------------

	function read_po_header($order_no, &$order) {
		$sql = "SELECT purch_orders.*, suppliers.supp_name,
   		suppliers.curr_code, locations.location_name
		FROM purch_orders, suppliers, locations
		WHERE purch_orders.supplier_id = suppliers.supplier_id
		AND locations.loc_code = into_stock_location
		AND purch_orders.order_no = " . DBOld::escape($order_no);

		$result = DBOld::query($sql, "The order cannot be retrieved");

		if (DBOld::num_rows($result) == 1) {

			$myrow = DBOld::fetch($result);

			$order->order_no = $order_no;
			$order->supplier_id = $myrow["supplier_id"];
			$order->supplier_name = $myrow["supp_name"];
			$order->curr_code = $myrow["curr_code"];
			$order->orig_order_date = Dates::sql2date($myrow["ord_date"]);
			$order->Comments = $myrow["comments"];
			$order->Location = $myrow["into_stock_location"];
			$order->requisition_no = $myrow["requisition_no"];
			$order->reference = $myrow["reference"];
			$order->delivery_address = $myrow["delivery_address"];
			$order->freight = $myrow["freight"];
			$order->salesman = $myrow['salesman'];
			return true;
		}

		Errors::show_db_error("FATAL : duplicate purchase order found", "", true);
		return false;
	}

	//----------------------------------------------------------------------------------------

	function read_po_items($order_no, &$order, $open_items_only = false) {
		/*now populate the line po array with the purchase order details records */

		$sql = "SELECT purch_order_details.*, units
		FROM purch_order_details
		LEFT JOIN stock_master
		ON purch_order_details.item_code=stock_master.stock_id
		WHERE order_no =" . DBOld::escape($order_no);

		if ($open_items_only)
			$sql .= " AND (purch_order_details.quantity_ordered > purch_order_details.quantity_received) ";

		$sql .= " ORDER BY po_detail_item";

		$result = DBOld::query($sql, "The lines on the purchase order cannot be retrieved");

		if (DBOld::num_rows($result) > 0) {

			while ($myrow = DBOld::fetch($result))
			{

				$data = get_purchase_data($order->supplier_id, $myrow['item_code']);
				if ($data !== false) {

					if ($data['supplier_description'] != "")
						$myrow['supplier_description'] = $data['supplier_description'];
					if ($data['suppliers_uom'] != "")
						$myrow['units'] = $data['suppliers_uom'];
				}
				if (is_null($myrow["units"])) {
					$units = "";
				}
				else
				{
					$units = $myrow["units"];
				}

				if ($order->add_to_order($order->lines_on_order + 1, $myrow["item_code"],
					$myrow["quantity_ordered"], $myrow["description"],
					$myrow["unit_price"], $units, Dates::sql2date($myrow["delivery_date"]),
					$myrow["qty_invoiced"], $myrow["quantity_received"], $myrow["discount"])
				) {
					$order->line_items[$order->lines_on_order]->po_detail_rec = $myrow["po_detail_item"];
					$order->line_items[$order->lines_on_order]->standard_cost =
					 $myrow["std_cost_unit"]; /*Needed for receiving goods and GL interface */
				}
			} /* line po from purchase order details */
		} //end of checks on returned data set
	}

	//----------------------------------------------------------------------------------------

	function read_po($order_no, &$order, $open_items_only = false) {
		$result = read_po_header($order_no, $order);

		if ($result)
			read_po_items($order_no, $order, $open_items_only);
	}

	//----------------------------------------------------------------------------------------

?>