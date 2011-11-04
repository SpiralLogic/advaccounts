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
	include_once(APP_PATH . "sales/includes/db/sales_order_db.php");
	include_once(APP_PATH . "sales/includes/db/sales_credit_db.php");
	include_once(APP_PATH . "sales/includes/db/sales_invoice_db.php");
	include_once(APP_PATH . "sales/includes/db/sales_delivery_db.php");
	include_once(APP_PATH . "sales/includes/db/sales_types_db.php");
	include_once(APP_PATH . "sales/includes/db/sales_points_db.php");
	include_once(APP_PATH . "sales/includes/db/custalloc_db.php");
	include_once(APP_PATH . "sales/includes/db/cust_trans_db.php");
	include_once(APP_PATH . "sales/includes/db/cust_trans_details_db.php");
	include_once(APP_PATH . "sales/includes/db/payment_db.php");
	include_once(APP_PATH . "sales/includes/db/branches_db.php");
	include_once(APP_PATH . "sales/includes/db/customers_db.php");
	//----------------------------------------------------------------------------------------
	// $price in customer's currency
	// $quantity is used as is (if it's neg it's neg, if it's pos it's pos)
	// $std_cost is in home currency
	// $show_or_hide 1 show this item in invoice/credit views, 0 to hide it (used for write-off items)
	// $type is 10 (invoice) or 11 (credit)
	function add_stock_move_customer($type, $stock_id, $trans_id, $location, $date_, $reference,
																	 $quantity, $std_cost, $show_or_hide = 1, $price = 0, $discount_percent = 0) {
		return add_stock_move($type, $stock_id, $trans_id, $location, $date_, $reference,
			$quantity, $std_cost, 0, $show_or_hide, $price, $discount_percent,
			"The customer stock movement record cannot be inserted");
	}

	//----------------------------------------------------------------------------------------
	// add a debtor-related gl transaction
	// $date_ is display date (non-sql)
	// $amount is in CUSTOMER'S currency
	function add_gl_trans_customer($type, $type_no, $date_, $account, $dimension, $dimension2,
																 $amount, $customer_id, $err_msg = "", $rate = 0) {
		if ($err_msg == "") {
			$err_msg = "The customer GL transaction could not be inserted";
		}
		return add_gl_trans($type, $type_no, $date_, $account, $dimension, $dimension2, "", $amount,
			Banking::get_customer_currency($customer_id),
			PT_CUSTOMER, $customer_id, $err_msg, $rate);
	}

	//----------------------------------------------------------------------------------------
	function get_calculated_price($stock_id, $add_pct) {
		$avg = get_standard_cost($stock_id);
		if ($avg == 0) {
			return 0;
		}
		return round2($avg * (1 + $add_pct / 100), user_price_dec());
	}

	function round_to_nearest($price, $round_to) {
		if ($price == 0) {
			return 0;
		}
		$pow = pow(10, user_price_dec());
		if ($pow >= $round_to) {
			$mod = ($pow % $round_to);
		} else {
			$mod = ($round_to % $pow);
		}
		if ($mod != 0) {
			$price = ceil($price) - ($pow - $round_to) / $pow;
		} else {
			$price = ceil($price * ($pow / $round_to)) / ($pow / $round_to);
		}
		return $price;
	}

	function get_price($stock_id, $currency, $sales_type_id, $factor = null, $date = null) {
		if ($date == null) {
			$date = Dates::new_doc_date();
		}
		if ($factor === null) {
			$myrow = get_sales_type($sales_type_id);
			$factor = $myrow['factor'];
		}
		$add_pct = DB_Company::get_pref('add_pct');
		$base_id = DB_Company::get_base_sales_type();
		$home_curr = Banking::get_company_currency();
		//	AND (sales_type_id = $sales_type_id	OR sales_type_id = $base_id)
		$sql
		 = "SELECT price, curr_abrev, sales_type_id
		FROM prices
		WHERE stock_id = " . DBOld::escape($stock_id) . "
			AND (curr_abrev = " . DBOld::escape($currency) . " OR curr_abrev = " . DBOld::escape($home_curr) . ")";
		$result = DBOld::query($sql, "There was a problem retrieving the pricing information for the part $stock_id for customer");
		$num_rows = DBOld::num_rows($result);
		$rate = round2(Banking::get_exchange_rate_from_home_currency($currency, $date),
			user_exrate_dec());
		$round_to = DB_Company::get_pref('round_to');
		$prices = array();
		while ($myrow = DBOld::fetch($result))
		{
			$prices[$myrow['sales_type_id']][$myrow['curr_abrev']] = $myrow['price'];
		}
		$price = false;
		if (isset($prices[$sales_type_id][$currency])) {
			$price = $prices[$sales_type_id][$currency];
		}
		elseif (isset($prices[$base_id][$currency]))
		{
			$price = $prices[$base_id][$currency] * $factor;
		}
		elseif (isset($prices[$sales_type_id][$home_curr]))
		{
			$price = $prices[$sales_type_id][$home_curr] / $rate;
		}
		elseif (isset($prices[$base_id][$home_curr]))
		{
			$price = $prices[$base_id][$home_curr] * $factor / $rate;
		}
			/*
							 if (isset($prices[$sales_type_id][$home_curr]))
							 {
								 $price = $prices[$sales_type_id][$home_curr] / $rate;
							 }
							 elseif (isset($prices[$base_id][$currency]))
							 {
								 $price = $prices[$base_id][$currency] * $factor;
							 }
							 elseif (isset($prices[$base_id][$home_curr]))
							 {
								 $price = $prices[$base_id][$home_curr] * $factor / $rate;
							 }
						 */
		elseif ($num_rows == 0 && $add_pct != -1)
		{
			$price = get_calculated_price($stock_id, $add_pct);
			if ($currency != $home_curr) {
				$price /= $rate;
			}
			if ($factor != 0) {
				$price *= $factor;
			}
		}
		if ($price === false) {
			return 0;
		}
		elseif ($round_to != 1)
		{
			return round_to_nearest($price, $round_to);
		} else {
			return round2($price, user_price_dec());
		}
	}

	//----------------------------------------------------------------------------------------
	//
	//	Get price for given item or kit.
	//  When $std==true price is calculated as a sum of all included stock items,
	//	otherwise all prices set for kits and items are accepted.
	//
	function get_kit_price($item_code, $currency, $sales_type_id, $factor = null,
												 $date = null, $std = false) {
		$kit_price = 0.00;
		if (!$std) {
			$kit_price = get_price($item_code, $currency, $sales_type_id,
				$factor, $date);
			if ($kit_price !== false) {
				return $kit_price;
			}
		}
		// no price for kit found, get total value of all items
		$kit = get_item_kit($item_code);
		while ($item = DBOld::fetch($kit)) {
			if ($item['item_code'] != $item['stock_id']) {
				// foreign/kit code
				$kit_price += $item['quantity'] * get_kit_price($item['stock_id'],
					$currency, $sales_type_id, $factor, $date, $std);
			}
			else {
				// stock item
				$kit_price += $item['quantity'] * get_price($item['stock_id'],
					$currency, $sales_type_id, $factor, $date);
			}
		}
		return $kit_price;
	}

	//-----------------------------------------------------------------------------
	function set_document_parent($cart) {
		$inv_no = key($cart->trans_no);
		if (count($cart->src_docs) == 1) {
			// if this child document has only one parent - update child link
			$src = array_keys($cart->src_docs);
			$del_no = reset($src);
			$sql = 'UPDATE debtor_trans SET trans_link = ' . $del_no .
			 ' WHERE type=' . DBOld::escape($cart->trans_type) . ' AND trans_no=' . $inv_no;
			DBOld::query($sql, 'UPDATE Child document link cannot be updated');
		}
		if ($cart->trans_type != ST_SALESINVOICE) {
			return 0;
		}
		// the rest is batch invoice specific
		foreach ($cart->line_items as $line) {
			if ($line->quantity != $line->qty_dispatched) {
				return 1; // this is partial invoice
			}
		}
		$sql = 'UPDATE debtor_trans SET trans_link = ' . $inv_no .
		 ' WHERE type=' . get_parent_type($cart->trans_type) . ' AND (';
		$deliveries = array_keys($cart->src_docs);
		foreach ($deliveries as $key => $del)
		{
			$deliveries[$key] = 'trans_no=' . $del;
		}
		$sql .= implode(' OR ', $deliveries) . ')';
		DBOld::query($sql, 'Delivery links cannot be updated');
		return 0; // batch or complete invoice
	}

	//--------------------------------------------------------------------------------------------------
	function get_parent_type($type) {
		$parent_types = array(ST_CUSTCREDIT => ST_SALESINVOICE,
			ST_SALESINVOICE => ST_CUSTDELIVERY,
			ST_CUSTDELIVERY => ST_SALESORDER
		);
		return isset($parent_types[$type]) ? $parent_types[$type] : 0;
	}

	//--------------------------------------------------------------------------------------------------
	function update_parent_line($doc_type, $line_id, $qty_dispatched) {
		$doc_type = get_parent_type($doc_type);
		//	echo "update line: $line_id, $doc_type, $qty_dispatched";
		if ($doc_type == 0) {
			return false;
		}
		else {
			if ($doc_type == ST_SALESORDER) {
				$sql
				 = "UPDATE sales_order_details
				SET qty_sent = qty_sent + $qty_dispatched
				WHERE id=" . DBOld::escape($line_id);
} else {
				$sql
				 = "UPDATE debtor_trans_details
				SET qty_done = qty_done + $qty_dispatched
				WHERE id=" . DBOld::escape($line_id);
			}
		}
		DBOld::query($sql, "The parent document detail record could not be updated");
		return true;
	}

	//--------------------------------------------------------------------------------------------------
	// find inventory location for given transaction
	//
	function get_location(&$cart) {
		$sql = "SELECT locations.* FROM stock_moves,"
		 . "locations" .
		 " WHERE type=" . DBOld::escape($cart->trans_type) .
		 " AND trans_no=" . key($cart->trans_no) .
		 " AND qty!=0 " .
		 " AND locations.loc_code=stock_moves.loc_code";
		$result = DBOld::query($sql, 'Retreiving inventory location');
		if (DBOld::num_rows($result)) {
			return DBOld::fetch($result);
		}
		return null;
	}

	//--------------------------------------------------------------------------------------------------
	// Generic read debtor transaction into cart
	//
	//	$trans_no - array of trans nums; special case trans_no==0 - new doc
	//
	function read_sales_trans($doc_type, $trans_no, &$cart) {
		if (!is_array($trans_no) && $trans_no) {
			$trans_no = array($trans_no);
		}
		$cart->trans_type = $doc_type;
		if (!$trans_no) { // new document
			$cart->trans_no = $trans_no;
		}
		else {
			// read header data from first document
			$myrow = get_customer_trans($trans_no[0], $doc_type);
			if (count($trans_no) > 1) {
				$cart->trans_no = get_customer_trans_version($doc_type, $trans_no);
} else {
				$cart->trans_no = array($trans_no[0] => $myrow["version"]);
			}
			$cart->set_sales_type($myrow["tpe"], $myrow["sales_type"], $myrow["tax_included"], 0);
			$cart->set_customer($myrow["debtor_no"], $myrow["DebtorName"],
				$myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);
			$cart->set_branch($myrow["branch_code"], $myrow["tax_group_id"],
				$myrow["tax_group_name"], $myrow["phone"], $myrow["email"]);
			$cart->reference = $myrow["reference"];
			$cart->order_no = $myrow["order_"];
			$cart->trans_link = $myrow["trans_link"];
			$cart->due_date = Dates::sql2date($myrow["due_date"]);
			$cart->document_date = Dates::sql2date($myrow["tran_date"]);
			$cart->dimension_id = $myrow['dimension_id']; // added 2.1 Joe Hunt 2008-11-12
			$cart->dimension2_id = $myrow['dimension2_id'];
			$cart->Comments = '';
			foreach ($trans_no as $trans) {
				$cart->Comments .= ui_view::get_comments_string($doc_type, $trans);
			}
			// FIX this should be calculated sum() for multiply parents
			$cart->set_delivery($myrow["ship_via"], $myrow["br_name"],
				$myrow["br_address"], $myrow["ov_freight"]);
			$location = 0;
			$myrow = get_location($cart); // find location from movement
			if ($myrow != null) {
				$cart->set_location($myrow['loc_code'], $myrow['location_name']);
			}
			$result = get_customer_trans_details($doc_type, $trans_no);
			if (DBOld::num_rows($result) > 0) {
				for ($line_no = 0; $myrow = DBOld::fetch($result); $line_no++) {
					$cart->line_items[$line_no] = new Sales_Line(
						$myrow["stock_id"], $myrow["quantity"],
						$myrow["unit_price"], $myrow["discount_percent"],
						$myrow["qty_done"], $myrow["standard_cost"],
						$myrow["StockDescription"], $myrow["id"], $myrow["debtor_trans_no"]);
				}
			}
		} // !newdoc
		return true;
	}

	//----------------------------------------------------------------------------------------
?>