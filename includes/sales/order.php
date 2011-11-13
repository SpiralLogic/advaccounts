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
	/* Definition of the cart class
		this class can hold all the information for:

		i)   a sales order
		ii)  an invoice
		iii) a credit note
		iv)  a delivery note
		*/
	class Sales_Order
	{
		public $trans_type; // invoice, order, quotation, delivery note ...
		public $trans_no = array(); // array (num1=>ver1,..) or 0 for new
		public $so_type = 0; // for sales order: simple=0 template=1
		public $cart_id; // used to detect multi-tab edition conflits
		public $line_items; //array of objects of class Sales_Line
		public $src_docs = array(); // array of arrays(num1=>ver1,...) or 0 for no src
		public $src_date; // src document date (for info only)
		public $document_date;
		public $due_date;
		public $salesman;
		public $sales_type; // set to the customer's sales type
		public $sales_type_name; // set to customer's sales type name
		public $tax_included;
		public $customer_currency; // set to the customer's currency
		public $default_discount; // set to the customer's discount %
		public $customer_name;
		public $customer_id;
		public $Branch;
		public $email;
		public $deliver_to;
		public $delivery_address;
		public $name;
		public $phone;
		public $cust_ref;
		public $reference;
		public $Comments;
		public $Location;
		public $location_name;
		public $order_no; // the original order number
		public $trans_link = 0;
		public $ship_via;
		public $freight_cost = 0;
		public $tax_group_id;
		public $tax_group_name;
		public $tax_group_array = null; // saves db queries
		public $price_factor; // ditto for price calculations
		public $pos; // user assigned POS
		public $cash; // cash transaction
		public $cash_account;
		public $account_name;
		public $dimension_id;
		public $dimension2_id;
		public $payment;
		public $payment_terms; // cached payment terms
		public $credit;

		//-------------------------------------------------------------------------
		//
		//  $trans_no==0 => open new/direct document
		//  $trans_no!=0 && $view==false => read for view
		//  $trans_no!=0 && $view==true => read for edit (qty update from parent doc)
		//
		function __construct($type, $trans_no = 0, $view = false)
		{
			/*Constructor function initialises a new shopping cart */
			$this->line_items = array();
			$this->sales_type = "";
			if ($type == ST_SALESQUOTE) {
				$this->trans_type = $type;
			} else {
				$this->trans_type = ST_SALESORDER;
			}
			$this->dimension_id = 0;
			$this->dimension2_id = 0;
			$this->read($type, $trans_no, $view);
			$this->cart_id = uniqid('');
		}

		//-------------------------------------------------------------------------
		// Reading document into cart
		//
		function read($type, $trans_no = 0, $view = false)
		{
			if (!is_array($trans_no)) {
				$trans_no = array($trans_no);
			}
			if ($trans_no[0]) // read old transaction
			{
				if ($type == ST_SALESORDER || $type == ST_SALESQUOTE) { // sales order || sales quotation
					Sales_Order::get($trans_no[0], $this, $type);
					if ($view) { // prepare for DN/IV entry
						for ($line_no = 0; $line_no < count($this->line_items); $line_no++) {
							$line = &$this->line_items[$line_no];
							$line->src_id = $line->id; // save src line ids for update
							$line->qty_dispatched = $line->quantity - $line->qty_done;
						}
					}
				} else { // other type of sales transaction
					read_sales_trans($type, $trans_no, $this);
					if ($this->order_no) { // free hand credit notes have no order_no
						$sodata = Sales_Order::get_header($this->order_no, ST_SALESORDER);
						$this->cust_ref = $sodata["customer_ref"];
						// currently currency is hard linked to debtor account
						//	$this->customer_currency = $sodata["curr_code"];
						$this->name = $sodata["contact_name"];
						$this->delivery_to = $sodata["deliver_to"];
						$this->delivery_address = $sodata["delivery_address"];
					}
					// old derivative transaction edit
					if (!$view && ($type != ST_CUSTCREDIT || $this->trans_link != 0)) {
						$src_type = get_parent_type($type);
						if ($src_type == ST_SALESORDER) { // get src data from sales_orders
							$this->src_docs = array($sodata['order_no'] => $sodata['version']);
							$srcdetails = Sales_Order::get_details($this->order_no, ST_SALESORDER);
						} else { // get src_data from debtor_trans
							$this->src_docs = Sales_Trans::get_version($src_type, Sales_Trans::get_parent($type, $trans_no[0]));
							$srcdetails = Sales_Debtor_Trans::get($src_type, array_keys($this->src_docs));
						}
						// calculate & save: qtys on other docs and free qtys on src doc
						for ($line_no = 0; $srcline = DB::fetch($srcdetails); $line_no++) {
							$sign = 1; // $type==13 ?  1 : -1; // this is strange debtor_trans atavism
							$line = &$this->line_items[$line_no];
							$line->src_id = $srcline['id']; // save src line ids for update
							$line->qty_old = $line->qty_dispatched = $line->quantity;
							$line->quantity += $sign * ($srcline['quantity'] - $srcline['qty_done']); // add free qty on src doc
						}
					} else { // prepare qtys for derivative document entry (not used in display)
						for ($line_no = 0; $line_no < count($this->line_items); $line_no++) {
							$line = &$this->line_items[$line_no];
							$line->src_id = $line->id; // save src line ids for update
							$line->qty_dispatched = $line->quantity - $line->qty_done;
						}
					}
				}
			} else { // new document
				$this->trans_type = $type;
				$this->trans_no = 0;
				$this->customer_currency = Banking::get_company_currency();
				// set new sales document defaults here
				if (Session::get()->global_customer != ALL_TEXT) {
					$this->customer_id = Session::get()->global_customer;
				} else {
					$this->customer_id = '';
				}
				$this->document_date = Dates::new_doc_date();
				if (!Dates::is_date_in_fiscalyear($this->document_date)) {
					$this->document_date = Dates::end_fiscalyear();
				}
				$this->reference = Refs::get_next($this->trans_type);
				$this->set_salesman();
				if ($type != ST_SALESORDER && $type != ST_SALESQUOTE) // Added 2.1 Joe Hunt 2008-11-12
				{
					$dim = DB_Company::get_pref('use_dimension');
					if ($dim > 0) {
						if ($this->customer_id == '') {
							$this->dimension_id = 0;
						} else {
							$cust = Sales_Debtor::get($this->customer_id);
							$this->dimension_id = $cust['dimension_id'];
						}
						if ($dim > 1) {
							if ($this->customer_id == '') {
								$this->dimension2_id = 0;
							} else {
								$this->dimension2_id = $cust['dimension2_id'];
							}
						}
					}
				}
				if ($type == ST_SALESINVOICE) {
					$this->due_date = get_invoice_duedate($this->customer_id, $this->document_date);
					$this->pos = User::pos();
					$pos = Sales_Point::get($this->pos);
					$this->cash = !$pos['credit_sale'];
					if (!$pos['cash_sale'] || !$pos['credit_sale'] || $this->due_date == Dates::Today()) {
						$this->pos = -1;
					} // mark not editable payment type
					else {
						$this->cash = Dates::date_diff2($this->due_date, Dates::Today(), 'd') < 2;
					}
					if ($this->cash) {
						$this->Location = $pos['pos_location'];
						$this->location_name = $pos['location_name'];
						$this->cash_account = $pos['pos_account'];
						$this->account_name = $pos['bank_account_name'];
					}
				} else {
					$this->due_date = Dates::add_days($this->document_date, SysPrefs::default_delivery_required_by());
				}
			}
			$this->credit = Sales_Debtor::get_credit($this->customer_id);
		}

		//-------------------------------------------------------------------------
		// Writing new/modified sales document to database.
		// Makes parent documents for direct delivery/invoice by recurent call.
		// $policy - 0 or 1:  writeoff/return for IV, back order/cancel for DN
		function write($policy = 0)
		{
			if (count($this->src_docs) == 0 && ($this->trans_type == ST_SALESINVOICE || $this->trans_type == ST_CUSTDELIVERY)) {
				// this is direct document - first add parent
				$src = (PHP_VERSION < 5) ? $this : clone($this); // make local copy of this cart
				$src->trans_type = get_parent_type($src->trans_type);
				$src->reference = 'auto';
				$src->write(1);
				$type = $this->trans_type;
				$ref = $this->reference;
				$date = $this->document_date;
				// re-read document
				$this->read($src->trans_type, key($src->trans_no), true);
				$this->document_date = $date;
				$this->reference = $ref;
				$this->trans_type = $type;
				$this->src_docs = $this->trans_no;
				$this->trans_no = 0;
				$this->order_no = $this->trans_type == ST_CUSTDELIVERY ? key($src->trans_no) : $src->order_no;
			}
			$this->reference = @html_entity_decode($this->reference, ENT_QUOTES);
			$this->Comments = @html_entity_decode($this->Comments, ENT_QUOTES);
			foreach ($this->line_items as $lineno => $line) {
				$this->line_items[$lineno]->stock_id = @html_entity_decode($line->stock_id, ENT_QUOTES);
				$this->line_items[$lineno]->description = @html_entity_decode($line->description, ENT_QUOTES);
			}
			switch ($this->trans_type) {
				case ST_SALESINVOICE:
					return Sales_Invoice::add($this);
				case ST_CUSTCREDIT:
					return Sales_Credits::add($this, $policy);
				case ST_CUSTDELIVERY:
					return Sales_Delivery::add($this, $policy);
				case ST_SALESORDER:
				case ST_SALESQUOTE:
					$_SESSION['Jobsboard'] = clone($this);
					if ($this->trans_no == 0) // new document
					{
						return Sales_Order::add($this);
					} else {
						return Sales_Order::update($this);
					}
			}
		}

		function check_cust_ref($cust_ref)
		{
			if (!is_int($this->trans_type)) {
				return false;
			}
			$sql = "SELECT customer_ref,type FROM sales_orders WHERE debtor_no=" . DB::escape($this->customer_id) . " AND customer_ref=" . DB::escape($cust_ref) . " AND type != " . $this->trans_type;
			$result = DB::query($sql);
			return (DB::num_rows($result) > 0) ? false : true;
		}

		function set_customer($customer_id, $customer_name, $currency, $discount, $payment)
		{
			$this->customer_name = $customer_name;
			$this->customer_id = $customer_id;
			$this->default_discount = $discount;
			$this->customer_currency = $currency;
			$this->payment = $payment;
			$this->payment_terms = DB_Company::get_payment_terms($payment);
			if ($this->payment_terms['cash_sale']) {
				$this->Location = $this->pos['pos_location'];
				$this->location_name = $this->pos['location_name'];
			}
			if ($customer_id > 0) {
				$this->credit = Sales_Debtor::get_credit($customer_id);
			}
		}

		function set_branch($branch_id, $tax_group_id, $tax_group_name = false, $phone = '', $email = '', $name = '')
		{
			$this->Branch = $branch_id;
			$this->phone = $phone;
			$this->email = $email;
			$this->tax_group_id = $tax_group_id;
			$this->tax_group_array = Tax_Groups::get_tax_group_items_as_array($tax_group_id);
		}

		function set_salesman($salesman_code = null)
		{
			if ($salesman_code == null) {
				$salesman_name = $_SESSION['current_user']->name;
				$sql = "SELECT salesman_code FROM salesman WHERE salesman_name = " . DB::escape($salesman_name);
				$query = DB::query($sql, 'Couldn\'t find current salesman');
				$result = DB::fetch_assoc($query);
				if (!empty($result['salesman_code'])) {
					$salesman_code = $result['salesman_code'];
				}
			}
			if ($salesman_code != null) {
				$this->salesman = $salesman_code;
			}
		}

		function set_sales_type($sales_type, $sales_name, $tax_included = 0, $factor = 0)
		{
			$this->sales_type = $sales_type;
			$this->sales_type_name = $sales_name;
			$this->tax_included = $tax_included;
			$this->price_factor = $factor;
		}

		function set_location($id, $name)
		{
			$this->Location = $id;
			$this->location_name = $name;
		}

		function set_delivery($shipper, $destination, $address, $freight_cost = null)
		{
			$this->ship_via = $shipper;
			$this->deliver_to = $destination;
			$this->delivery_address = $address;
			if (isset($freight_cost)) {
				$this->freight_cost = $freight_cost;
			}
		}

		function add_to_cart($line_no, $stock_id, $qty, $price, $disc, $qty_done = 0, $standard_cost = 0, $description = null, $id = 0, $src_no = 0)
		{
			if (isset($stock_id) && $stock_id != "" && isset($qty) /* && $qty > 0*/) {
				$this->line_items[$line_no] = new Sales_Line($stock_id, $qty, $price, $disc, $qty_done, $standard_cost, $description, $id, $src_no);
				return 1;
			} else {
				// shouldn't come here under normal circumstances
				Errors::show_db_error("unexpected - adding an invalid item or null quantity", "", true);
			}
			return 0;
		}

		function update_cart_item($line_no, $qty, $price, $disc, $description = "")
		{
			if ($description != "") {
				$this->line_items[$line_no]->description = $description;
			}
			$this->line_items[$line_no]->quantity = $qty;
			$this->line_items[$line_no]->qty_dispatched = $qty;
			$this->line_items[$line_no]->price = $price;
			$this->line_items[$line_no]->discount_percent = $disc;
		}

		function discount_all($discount)
		{
			foreach ($this->line_items as $line) {
				$line->discount_percent = $discount;
			}
		}

		function update_add_cart_item_qty($line_no, $qty)
		{
			$this->line_items[$line_no]->quantity += $qty;
		}

		function remove_from_cart($line_no)
		{
			array_splice($this->line_items, $line_no, 1);
		}

		function clear_items()
		{
			unset($this->line_items);
			$this->line_items = array();
			$this->sales_type = "";
			$this->trans_no = 0;
			$this->customer_id = $this->order_no = 0;
		}

		function count_items()
		{
			$counter = 0;
			foreach ($this->line_items as $line) {
				if ($line->quantity != $line->qty_done) {
					$counter++;
				}
			}
			return $counter;
		}

		function get_items_total()
		{
			$total = 0;
			foreach ($this->line_items as $ln_itm) {
				$price = $ln_itm->line_price();
				$total += round($ln_itm->quantity * $price * (1 - $ln_itm->discount_percent), User::price_dec());
			}
			return $total;
		}

		function get_items_total_dispatch()
		{
			$total = 0;
			foreach ($this->line_items as $ln_itm) {
				$price = $ln_itm->line_price();
				$total += round(($ln_itm->qty_dispatched * $price * (1 - $ln_itm->discount_percent)), User::price_dec());
			}
			return $total;
		}

		function has_items_dispatch()
		{
			foreach ($this->line_items as $ln_itm) {
				if ($ln_itm->qty_dispatched > 0) {
					return true;
				}
			}
			return false;
		}

		function any_already_delivered()
		{
			/* Checks if there have been any line item processed */
			foreach ($this->line_items as $stock_item) {
				if ($stock_item->qty_done != 0) {
					return 1;
				}
			}
			return 0;
		}

		function some_already_delivered($line_no)
		{
			/* Checks if there have been deliveries of a specific line item */
			if (isset($this->line_items[$line_no]) && $this->line_items[$line_no]->qty_done != 0) {
				return 1;
			}
			return 0;
		}

		function get_taxes($shipping_cost = null)
		{
			$items = array();
			$prices = array();
			if ($shipping_cost == null) {
				$shipping_cost = $this->freight_cost;
			}
			foreach ($this->line_items as $ln_itm) {
				$items[] = $ln_itm->stock_id;
				$prices[] = round(($ln_itm->qty_dispatched * $ln_itm->line_price() * (1 - $ln_itm->discount_percent)), User::price_dec());
			}
			$taxes = Taxes::get_tax_for_items($items, $prices, $shipping_cost, $this->tax_group_id, $this->tax_included, $this->tax_group_array);
			// Adjustment for swiss franken, we always have 5 rappen = 1/20 franken
			if ($this->customer_currency == 'CHF') {
				$val = $taxes['1']['Value'];
				$val1 = (floatval((intval(round(($val * 20), 0))) / 20));
				$taxes['1']['Value'] = $val1;
			}
			return $taxes;
		}

		function get_tax_free_shipping()
		{
			if ($this->tax_included == 0) {
				return $this->freight_cost;
			} else {
				return ($this->freight_cost - $this->get_shipping_tax());
			}
		}

		function get_shipping_tax()
		{
			$tax_items = Tax_Groups::get_shipping_tax_as_array();
			$tax_rate = 0;
			if ($tax_items != null) {
				foreach ($tax_items as $item_tax) {
					$index = $item_tax['tax_type_id'];
					if (isset($this->tax_group_array[$index])) {
						$tax_rate += $item_tax['rate'];
					}
				}
			}
			if ($this->tax_included) {
				return round($this->freight_cost * $tax_rate / ($tax_rate + 100), User::price_dec());
			} else {
				return round($this->freight_cost * $tax_rate / 100, User::price_dec());
			}
		}

		function store()
		{
			$serial = serialize($this);
			$sql = "DELETE FROM `user_class_store` WHERE `user_id`=" . $_SESSION['current_user']->user;
			DB::query($sql);
			$sql = "INSERT INTO `user_class_store` (`user_id`, `data`) VALUE (" . $_SESSION['current_user']->user . ",'" . $serial . "')";
			DB::query($sql);
		}

		static function restore()
		{
			$sql = "SELECT `data` FROM  `user_class_store` WHERE `user_id`=" . $_SESSION['current_user']->user;
			$result = DB::query($sql);
			$serial = DB::fetch_assoc($result);
			$serial = $serial['data'];
			return unserialize($serial);
		}

		public static function add($order)
		{
			DB::begin_transaction();
			$order_no = SysTypes::get_next_trans_no($order->trans_type);
			$del_date = Dates::date2sql($order->due_date);
			$order_type = 0; // this is default on new order
			$sql = "INSERT INTO sales_orders (order_no, type, debtor_no, trans_type, branch_code, customer_ref, reference, salesman, comments, ord_date,
			order_type, ship_via, deliver_to, delivery_address, contact_name, contact_phone,
			contact_email, freight_cost, from_stk_loc, delivery_date)
			VALUES (" . DB::escape($order_no) . "," . DB::escape($order_type) . "," . DB::escape($order->customer_id) . ", " . DB::escape($order->trans_type) . "," . DB::escape($order->Branch) . ", " . DB::escape($order->cust_ref) . "," . DB::escape($order->reference) . "," . DB::escape($order->salesman) . "," . DB::escape($order->Comments) . ",'" . Dates::date2sql($order->document_date) . "', " . DB::escape($order->sales_type) . ", " . DB::escape($order->ship_via) . "," . DB::escape($order->deliver_to) . "," . DB::escape($order->delivery_address) . ", " . DB::escape($order->name) . ", " . DB::escape($order->phone) . ", " . DB::escape($order->email) . ", " . DB::escape($order->freight_cost) . ", " . DB::escape($order->Location) . ", " . DB::escape($del_date) . ")";
			DB::query($sql, "order Cannot be Added");
			$order->trans_no = array($order_no => 0);
			if (Config::get('accounts_stock_emailnotify') == 1) {
				$st_ids = array();
				$st_names = array();
				$st_num = array();
				$st_reorder = array();
			}
			foreach ($order->line_items as $line) {
				if (Config::get('accounts_stock_emailnotify') == 1 && Item::is_inventory_item($line->stock_id)) {
					$sql = "SELECT loc_stock.*, locations.location_name, locations.email
					FROM loc_stock, locations
					WHERE loc_stock.loc_code=locations.loc_code
					AND loc_stock.stock_id = '" . $line->stock_id . "'
					AND loc_stock.loc_code = '" . $order->Location . "'";
					$res = DB::query($sql, "a location could not be retreived");
					$loc = DB::fetch($res);
					if ($loc['email'] != "") {
						$qoh = Item::get_qoh_on_date($line->stock_id, $order->Location);
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
				for ($i = 0; $i < count($st_ids); $i++) {
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
		public static function delete($order_no, $trans_type)
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
		public static function update_version($order)
		{
			foreach ($order as $so_num => $so_ver) {
				$sql = 'UPDATE sales_orders SET version=version+1 WHERE order_no=' . $so_num . ' AND version=' . $so_ver . " AND trans_type=30";
				DB::query($sql, 'Concurrent editing conflict while sales order update');
			}
		}

		//----------------------------------------------------------------------------------------
		public static function update($order)
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
			foreach ($order->line_items as $line) {
				if (Config::get('accounts_stock_emailnotify') == 1 && Item::is_inventory_item($line->stock_id)) {
					$sql = "SELECT loc_stock.*, locations.location_name, locations.email
					FROM loc_stock, locations
					WHERE loc_stock.loc_code=locations.loc_code
					 AND loc_stock.stock_id = " . DB::escape($line->stock_id) . "
					 AND loc_stock.loc_code = " . DB::escape($order->Location);
					$res = DB::query($sql, "a location could not be retreived");
					$loc = DB::fetch($res);
					if ($loc['email'] != "") {
						$qoh = Item::get_qoh_on_date($line->stock_id, $order->Location);
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
				$sql = "INSERT INTO sales_order_details
			 (id, order_no, trans_type, stk_code,  description, unit_price, quantity,
			  discount_percent, qty_sent)
			 VALUES (";
				$sql .= DB::escape($line->id ? $line->id :
														0) . "," . $order_no . "," . $order->trans_type . "," . DB::escape($line->stock_id) . "," . DB::escape($line->description) . ", " . DB::escape($line->price) . ", " . DB::escape($line->quantity) . ", " . DB::escape($line->discount_percent) . ", " . DB::escape($line->qty_done) . " )";
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
				for ($i = 0; $i < count($st_ids); $i++) {
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
		public static function get_header($order_no, $trans_type)
		{
			$sql = "SELECT DISTINCT sales_orders.*,
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
			} else if ($num == 1) {
				return DB::fetch($result);
			} else {
				Errors::show_db_error("FATAL : sales order return nothing - " . DB::num_rows($result), $sql, true);
			}
		}

		//----------------------------------------------------------------------------------------
		public static function get_details($order_no, $trans_type)
		{
			$sql = "SELECT sales_order_details.id, stk_code, unit_price, sales_order_details.description,sales_order_details.quantity,
			  discount_percent,
			  qty_sent as qty_done, stock_master.units,stock_master.tax_type_id,stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost AS standard_cost
		FROM sales_order_details, stock_master
		WHERE sales_order_details.stk_code = stock_master.stock_id
		AND order_no =" . DB::escape($order_no) . " AND trans_type = " . DB::escape($trans_type) . " ORDER BY id";
			return DB::query($sql, "Retreive order Line Items");
		}

		//----------------------------------------------------------------------------------------
		public static function get($order_no, &$order, $trans_type)
		{
			$myrow = Sales_Order::get_header($order_no, $trans_type);
			$order->trans_type = $myrow['trans_type'];
			$order->so_type = $myrow["type"];
			$order->trans_no = array($order_no => $myrow["version"]);
			$order->set_customer($myrow["debtor_no"], $myrow["name"], $myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);
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
			$result = Sales_Order::get_details($order_no, $order->trans_type);
			if (DB::num_rows($result) > 0) {
				$line_no = 0;
				while ($myrow = DB::fetch($result)) {
					$order->add_to_cart($line_no, $myrow["stk_code"], $myrow["quantity"], $myrow["unit_price"], $myrow["discount_percent"], $myrow["qty_done"], $myrow["standard_cost"], $myrow["description"], $myrow["id"]);
					$line_no++;
				}
			}
			return true;
		}

		//----------------------------------------------------------------------------------------
		public static function has_deliveries($order_no)
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
		public static function close($order_no)
		{
			// set the quantity of each item to the already sent quantity. this will mark item as closed.
			$sql = "UPDATE sales_order_details
			SET quantity = qty_sent WHERE order_no = " . DB::escape($order_no) . " AND trans_type=" . ST_SALESORDER . "";
			DB::query($sql, "The sales order detail record could not be updated");
		}

		//---------------------------------------------------------------------------------------------------------------
		public static function get_invoice_duedate($debtorno, $invdate)
		{
			if (!Dates::is_date($invdate)) {
				return Dates::new_doc_date();
			}
			$sql = "SELECT debtors_master.debtor_no, debtors_master.payment_terms, payment_terms.* FROM debtors_master,
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

		public static function get_customer($customer_id)
		{
			// Now check to ensure this account is not on hold */
			$sql = "SELECT debtors_master.name,
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

		public static function get_branch($customer_id, $branch_id)
		{
			// the branch was also selected from the customer selection so default the delivery details from the customer branches table cust_branch. The order process will ask for branch details later anyway
			$sql = "SELECT cust_branch.br_name,
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
	} /* end of class defintion */
?>
