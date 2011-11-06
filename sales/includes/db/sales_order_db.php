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
	function add_sales_order($order)
	{
		DB::begin_transaction();
		$order_no = SysTypes::get_next_trans_no($order->trans_type);
		$del_date = Dates::date2sql($order->due_date);
		$order_type = 0; // this is default on new order
		$sql
		 = "INSERT INTO sales_orders (order_no, type, debtor_no, trans_type, branch_code, customer_ref, reference, salesman, comments, ord_date,
		order_type, ship_via, deliver_to, delivery_address, contact_name, contact_phone,
		contact_email, freight_cost, from_stk_loc, delivery_date)
		VALUES (" . DB::escape($order_no) . "," . DB::escape($order_type) . "," . DB::escape($order->customer_id) . ", " . DB::escape($order->trans_type) . "," . DB::escape($order->Branch) . ", " . DB::escape($order->cust_ref) . "," . DB::escape($order->reference) . ","
		 . DB::escape($order->salesman) . "," . DB::escape($order->Comments) . ",'" . Dates::date2sql($order->document_date) . "', " . DB::escape($order->sales_type) . ", " . DB::escape($order->ship_via) . "," . DB::escape($order->deliver_to) . "," . DB::escape(
			$order->delivery_address
		) . ", " . DB::escape($order->name) . ", " . DB::escape($order->phone) . ", " . DB::escape($order->email) . ", " . DB::escape($order->freight_cost) . ", " . DB::escape($order->Location) . ", " . DB::escape($del_date) . ")";
		DB::query($sql, "order Cannot be Added");
		$order->trans_no = array($order_no => 0);
		if (Config::get('accounts_stock_emailnotify') == 1) {
			$st_ids = array();
			$st_names = array();
			$st_num = array();
			$st_reorder = array();
		}
		foreach (
			$order->line_items as $line
		) {
			if (Config::get('accounts_stock_emailnotify') == 1 && is_inventory_item($line->stock_id)) {
				$sql
				 = "SELECT loc_stock.*, locations.location_name, locations.email
				FROM loc_stock, locations
				WHERE loc_stock.loc_code=locations.loc_code
				AND loc_stock.stock_id = '" . $line->stock_id . "'
				AND loc_stock.loc_code = '" . $order->Location . "'";
				$res = DB::query($sql, "a location could not be retreived");
				$loc = DB::fetch($res);
				if ($loc['email'] != "") {
					$qoh = get_qoh_on_date($line->stock_id, $order->Location);
					$qoh -= Manufacturing::get_demand_qty($line->stock_id, $order->Location);
					$qoh -= Manufacturing::get_demand_asm_qty($line->stock_id, $order->Location);
					$qoh -= $line->quantity;
					if ($qoh < $loc['reorder_level']) {
						$st_ids[] = $line->stock_id;
						$st_names[] = $line->description;
						$st_num[] = $qoh - $loc['reorder_level'];
						$st_reorder[] = $loc['reorder_level'];
					}
				}
			}
			$sql = "INSERT INTO sales_order_details (order_no, trans_type, stk_code, description, unit_price, quantity, discount_percent) VALUES (";
			$sql .= $order_no . "," . $order->trans_type . "," . DB::escape($line->stock_id) . ", " . DB::escape($line->description) . ", $line->price,
				$line->quantity,
				$line->discount_percent)";
			DB::query($sql, "order Details Cannot be Added");
		} /* inserted line items into sales order details */
		DB_AuditTrail::add($order->trans_type, $order_no, $order->document_date);
		Refs::save($order->trans_type, $order_no, $order->reference);
		DB::commit_transaction();
		if (Config::get('accounts_stock_emailnotify') == 1 && count($st_ids) > 0) {
			require_once(APP_PATH . "/reporting/includes/email.php");
			$company = DB_Company::get_prefs();
			$mail = new Reports_Email($company['coy_name'], $company['email']);
			$from = $company['coy_name'] . " <" . $company['email'] . ">";
			$to = $loc['location_name'] . " <" . $loc['email'] . ">";
			$subject = _("Stocks below Re-Order Level at " . $loc['location_name']);
			$msg = "\n";
			for (
				$i = 0; $i < count($st_ids); $i++
			)
			{
				$msg .= $st_ids[$i] . " " . $st_names[$i] . ", " . _("Re-Order Level") . ": " . $st_reorder[$i] . ", " . _("Below") . ": " . $st_num[$i] . "\n";
			}
			$msg .= "\n" . _("Please reorder") . "\n\n";
			$msg .= $company['coy_name'];
			$mail->to($to);
			$mail->subject($subject);
			$mail->text($msg);
			$ret = $mail->send();
		}
		return $order_no;
	}

	//----------------------------------------------------------------------------------------
	function delete_sales_order($order_no, $trans_type)
	{
		DB::begin_transaction();
		$sql = "DELETE FROM sales_orders WHERE order_no=" . DB::escape($order_no) . " AND trans_type=" . DB::escape($trans_type);
		DB::query($sql, "order Header Delete");
		$sql = "DELETE FROM sales_order_details WHERE order_no =" . DB::escape($order_no) . " AND trans_type=" . DB::escape($trans_type);
		DB::query($sql, "order Detail Delete");
		Refs::delete_reference($trans_type, $order_no);
		DB_AuditTrail::add($trans_type, $order_no, Dates::Today(), _("Deleted."));
		DB::commit_transaction();
	}

	//----------------------------------------------------------------------------------------
	// Mark changes in sales_order_details
	//
	function update_sales_order_version($order)
	{
		foreach (
			$order as $so_num => $so_ver
		) {
			$sql = 'UPDATE sales_orders SET version=version+1 WHERE order_no=' . $so_num . ' AND version=' . $so_ver . " AND trans_type=30";
			DB::query($sql, 'Concurrent editing conflict while sales order update');
		}
	}

	//----------------------------------------------------------------------------------------
	function update_sales_order($order)
	{
		$del_date = Dates::date2sql($order->due_date);
		$ord_date = Dates::date2sql($order->document_date);
		$order_no = key($order->trans_no);
		$version = current($order->trans_no);
		DB::begin_transaction();
		$sql = "UPDATE sales_orders SET type =" . DB::escape($order->so_type) . " ,
		debtor_no = " . DB::escape($order->customer_id) . ",
		branch_code = " . DB::escape($order->Branch) . ",
		customer_ref = " . DB::escape($order->cust_ref) . ",
		reference = " . DB::escape($order->reference) . ",
		salesman = " . DB::escape($order->salesman) . ",
		comments = " . DB::escape($order->Comments) . ",
		ord_date = " . DB::escape($ord_date) . ",
		order_type = " . DB::escape($order->sales_type) . ",
		ship_via = " . DB::escape($order->ship_via) . ",
		deliver_to = " . DB::escape($order->deliver_to) . ",
		delivery_address = " . DB::escape($order->delivery_address) . ",
		contact_name = " . DB::escape($order->name) . ",
		contact_phone = " . DB::escape($order->phone) . ",
		contact_email = " . DB::escape($order->email) . ",
		freight_cost = " . DB::escape($order->freight_cost) . ",
		from_stk_loc = " . DB::escape($order->Location) . ",
		delivery_date = " . DB::escape($del_date) . ",
		version = " . ($version + 1) . "
	 WHERE order_no=" . $order_no . "
	 AND trans_type=" . $order->trans_type . " AND version=" . $version;
		DB::query($sql, "order Cannot be Updated, this can be concurrent edition conflict");
		$sql = "DELETE FROM sales_order_details WHERE order_no =" . $order_no . " AND trans_type=" . $order->trans_type;
		DB::query($sql, "Old order Cannot be Deleted");
		if (Config::get('accounts_stock_emailnotify') == 1) {
			$st_ids = array();
			$st_names = array();
			$st_num = array();
			$st_reorder = array();
		}
		foreach (
			$order->line_items as $line
		) {

			if (Config::get('accounts_stock_emailnotify') == 1 && is_inventory_item($line->stock_id)) {
				$sql
				 = "SELECT loc_stock.*, locations.location_name, locations.email
				FROM loc_stock, locations
				WHERE loc_stock.loc_code=locations.loc_code
				 AND loc_stock.stock_id = " . DB::escape($line->stock_id) . "
				 AND loc_stock.loc_code = " . DB::escape($order->Location);
				$res = DB::query($sql, "a location could not be retreived");

					$loc = DB::fetch($res);
				if ($loc['email'] != "") {
					$qoh = get_qoh_on_date($line->stock_id, $order->Location);
					$qoh -= Manufacturing::get_demand_qty($line->stock_id, $order->Location);
					$qoh -= Manufacturing::get_demand_asm_qty($line->stock_id, $order->Location);
					$qoh -= $line->quantity;
					if ($qoh < $loc['reorder_level']) {
						$st_ids[] = $line->stock_id;
						$st_names[] = $line->description;
						$st_num[] = $qoh - $loc['reorder_level'];
						$st_reorder[] = $loc['reorder_level'];
					}
				}
			}
			$sql
			 = "INSERT INTO sales_order_details
		 (id, order_no, trans_type, stk_code,  description, unit_price, quantity,
		  discount_percent, qty_sent)
		 VALUES (";
			$sql .= DB::escape(
				$line->id ? $line->id
				 : 0
			) . "," . $order_no . "," . $order->trans_type . "," . DB::escape($line->stock_id) . "," . DB::escape($line->description) . ", " . DB::escape($line->price) . ", " . DB::escape($line->quantity) . ", " . DB::escape($line->discount_percent) . ", " . DB::escape($line->qty_done)
			 . " )";

			DB::query($sql, "Old order Cannot be Inserted");
		} /* inserted line items into sales order details */
		DB_AuditTrail::add($order->trans_type, $order_no, $order->document_date, _("Updated."));

		Refs::delete($order->trans_type, $order_no);
		Refs::save($order->trans_type, $order_no, $order->reference);

		DB::commit_transaction();

		if (Config::get('accounts_stock_emailnotify') == 1 && count($st_ids) > 0) {
			require_once(APP_PATH . "/reporting/includes/class.mail.php");
			$company = DB_Company::get_prefs();
			$mail = new Reports_Email($company['coy_name'], $company['email']);
			$from = $company['coy_name'] . " <" . $company['email'] . ">";
			$to = $loc['location_name'] . " <" . $loc['email'] . ">";
			$subject = _("Stocks below Re-Order Level at " . $loc['location_name']);
			$msg = "\n";
			for (
				$i = 0; $i < count($st_ids); $i++
			)
			{
				$msg .= $st_ids[$i] . " " . $st_names[$i] . ", " . _("Re-Order Level") . ": " . $st_reorder[$i] . ", " . _("Below") . ": " . $st_num[$i] . "\n";
			}
			$msg .= "\n" . _("Please reorder") . "\n\n";
			$msg .= $company['coy_name'];
			$mail->to($to);
			$mail->subject($subject);
			$mail->text($msg);
			$ret = $mail->send();
		}
	}

	//----------------------------------------------------------------------------------------
	function get_sales_order_header($order_no, $trans_type)
	{
		$sql
		 = "SELECT DISTINCT sales_orders.*,
	  debtors_master.name,
	  debtors_master.curr_code,
	  debtors_master.email AS master_email,
	  locations.location_name,
	  debtors_master.payment_terms,
	 debtors_master.discount,
	 sales_types.sales_type,
	 sales_types.id AS sales_type_id,
	 sales_types.tax_included,
	 shippers.shipper_name,
	 tax_groups.name AS tax_group_name ,
	 tax_groups.id AS tax_group_id
	FROM sales_orders,
	debtors_master,
	sales_types,
	tax_groups,
	cust_branch,
	locations,
	shippers
	WHERE sales_orders.order_type=sales_types.id
		AND cust_branch.branch_code = sales_orders.branch_code
		AND cust_branch.tax_group_id = tax_groups.id
		AND sales_orders.debtor_no = debtors_master.debtor_no
		AND locations.loc_code = sales_orders.from_stk_loc
		AND shippers.shipper_id = sales_orders.ship_via
		AND sales_orders.trans_type = " . DB::escape($trans_type) . "
		AND sales_orders.order_no = " . DB::escape($order_no);
		$result = DB::query($sql, "order Retreival");
		$num = DB::num_rows($result);
		if ($num > 1) {
			Errors::show_db_error("FATAL : sales order query returned a duplicate - " . DB::num_rows($result), $sql, true);
		}
		else if ($num == 1) {
			return DB::fetch($result);
		} else {
			Errors::show_db_error("FATAL : sales order return nothing - " . DB::num_rows($result), $sql, true);
		}
	}

	//----------------------------------------------------------------------------------------
	function get_sales_order_details($order_no, $trans_type)
	{
		$sql
		 = "SELECT sales_order_details.id, stk_code, unit_price, sales_order_details.description,sales_order_details.quantity,
		  discount_percent,
		  qty_sent as qty_done, stock_master.units,stock_master.tax_type_id,stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost AS standard_cost
	FROM sales_order_details, stock_master
	WHERE sales_order_details.stk_code = stock_master.stock_id
	AND order_no =" . DB::escape($order_no) . " AND trans_type = " . DB::escape($trans_type) . " ORDER BY id";
		return DB::query($sql, "Retreive order Line Items");
	}

	//----------------------------------------------------------------------------------------
	function read_sales_order($order_no, &$order, $trans_type)
	{
		$myrow = get_sales_order_header($order_no, $trans_type);
		$order->trans_type = $myrow['trans_type'];
		$order->so_type = $myrow["type"];
		$order->trans_no = array($order_no => $myrow["version"]);
		$order->set_customer(
			$myrow["debtor_no"], $myrow["name"],
			$myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]
		);
		$order->set_branch($myrow["branch_code"], $myrow["tax_group_id"], $myrow["tax_group_name"], $myrow["contact_phone"], $myrow["contact_email"], $myrow["contact_name"]);
		$order->set_sales_type($myrow["sales_type_id"], $myrow["sales_type"], $myrow["tax_included"], 0); // no default price calculations on edit
		$order->set_location($myrow["from_stk_loc"], $myrow["location_name"]);
		$order->set_delivery($myrow["ship_via"], $myrow["deliver_to"], $myrow["delivery_address"], $myrow["freight_cost"]);
		$order->cust_ref = $myrow["customer_ref"];
		$order->name = $myrow["contact_name"];
		$order->sales_type = $myrow["order_type"];
		$order->reference = $myrow["reference"];
		$order->salesman = $myrow["salesman"];
		$order->Comments = $myrow["comments"];
		$order->due_date = Dates::sql2date($myrow["delivery_date"]);
		$order->document_date = Dates::sql2date($myrow["ord_date"]);
		$result = get_sales_order_details($order_no, $order->trans_type);
		if (DB::num_rows($result) > 0) {
			$line_no = 0;
			while ($myrow = DB::fetch($result)) {
				$order->add_to_cart(
					$line_no, $myrow["stk_code"], $myrow["quantity"], $myrow["unit_price"], $myrow["discount_percent"], $myrow["qty_done"], $myrow["standard_cost"], $myrow["description"],
					$myrow["id"]
				);
				$line_no++;
			}
		}
		return true;
	}

	//----------------------------------------------------------------------------------------
	function sales_order_has_deliveries($order_no)
	{
		$sql = "SELECT SUM(qty_sent) FROM sales_order_details WHERE order_no=" . DB::escape($order_no) . " AND trans_type=" . ST_SALESORDER . "";
		$result = DB::query($sql, "could not query for sales order usage");
		$row = DB::fetch_row($result);
		if ($row[0] > 0) {
			return true;
		} // 2010-04-21 added check for eventually voided deliveries, Joe Hunt
		/*$sql = "SELECT order_ FROM debtor_trans WHERE type=" . ST_CUSTDELIVERY . " AND order_=" . DB::escape($order_no);
		$result = DB::query($sql, "The related delivery notes could not be retreived");
		;*/
	}

	//----------------------------------------------------------------------------------------
	function close_sales_order($order_no)
	{
		// set the quantity of each item to the already sent quantity. this will mark item as closed.
		$sql
		 = "UPDATE sales_order_details
		SET quantity = qty_sent WHERE order_no = " . DB::escape($order_no) . " AND trans_type=" . ST_SALESORDER . "";
		DB::query($sql, "The sales order detail record could not be updated");
	}

	//---------------------------------------------------------------------------------------------------------------
	function get_invoice_duedate($debtorno, $invdate)
	{
		if (!Dates::is_date($invdate)) {
			return Dates::new_doc_date();
		}
		$sql
		 = "SELECT debtors_master.debtor_no, debtors_master.payment_terms, payment_terms.* FROM debtors_master,
		payment_terms WHERE debtors_master.payment_terms = payment_terms.terms_indicator AND
		debtors_master.debtor_no = " . DB::escape($debtorno);
		$result = DB::query($sql, "The customer details could not be retrieved");
		$myrow = DB::fetch($result);
		if (DB::num_rows($result) == 0) {
			return $invdate;
		}
		if ($myrow['day_in_following_month'] > 0) {
			$duedate = Dates::add_days(Dates::end_month($invdate), $myrow['day_in_following_month']);
		} else {
			$duedate = Dates::add_days($invdate, $myrow['days_before_due']);
		}
		return $duedate;
	}

	function get_customer_to_order($customer_id)
	{
		// Now check to ensure this account is not on hold */
		$sql
		 = "SELECT debtors_master.name,
	 debtors_master.address,
	 credit_status.dissallow_invoices,
	 debtors_master.sales_type AS salestype,
	 debtors_master.dimension_id,
	 debtors_master.dimension2_id,
	 sales_types.sales_type,
	 sales_types.tax_included,
	 sales_types.factor,
	 debtors_master.curr_code,
	 debtors_master.discount,
	 debtors_master.pymt_discount,
	 debtors_master.payment_terms
		FROM debtors_master, credit_status, sales_types
		WHERE debtors_master.sales_type=sales_types.id
		AND debtors_master.credit_status=credit_status.id
		AND debtors_master.debtor_no = " . DB::escape($customer_id);
		$result = DB::query($sql, "Customer Record Retreive");
		return DB::fetch($result);
	}

	function get_branch_to_order($customer_id, $branch_id)
	{
		// the branch was also selected from the customer selection so default the delivery details from the customer branches table cust_branch. The order process will ask for branch details later anyway
		$sql
		 = "SELECT cust_branch.br_name,
      cust_branch.br_address,
      cust_branch.city, cust_branch.state, cust_branch.postcode, cust_branch.contact_name, cust_branch.br_post_address, cust_branch.phone, cust_branch.email,
			  default_location, location_name, default_ship_via, tax_groups.name AS tax_group_name, tax_groups.id AS tax_group_id
			FROM cust_branch, tax_groups, locations
			WHERE cust_branch.tax_group_id = tax_groups.id
				AND locations.loc_code=default_location
				AND cust_branch.branch_code=" . DB::escape($branch_id) . "
				AND cust_branch.debtor_no = " . DB::escape($customer_id);
		return DB::query($sql, "Customer Branch Record Retreive");
	}

?>