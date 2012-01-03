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
	/* Definition of the order class
		this class can hold all the information for:

		i) a sales order
		ii) an invoice
		iii) a credit note
		iv) a delivery note
		*/
	class Sales_Order
	{
		public $trans_type; // invoice, order, quotation, delivery note ...
		public $trans_no = array(); // array (num1=>ver1,..) or 0 for new
		public $so_type = 0; // for sales order: simple=0 template=1
		public $order_id; // used to detect multi-tab edition conflits
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
		protected $uniqueid;
		//
		// $trans_no==0 => open new/direct document
		// $trans_no!=0 && $view==false => read for view
		// $trans_no!=0 && $view==true => read for edit (qty update from parent doc)
		//
		/***
		 * @param						$type
		 * @param int|array	$trans_no
		 * @param bool			 $view
		 */
		public function __construct($type, $trans_no = 0, $view = false) {
			/*Constructor function initialises a new shopping order */
			$this->line_items = array();
			$this->sales_type = "";
			if ($type == ST_SALESQUOTE) {
				$this->trans_type = $type;
			}
			else {
				$this->trans_type = ST_SALESORDER;
			}
			$this->dimension_id = 0;
			$this->dimension2_id = 0;
			$this->read($type, $trans_no, $view);
			$this->uniqueid = uniqid();
			$this->order_id = $this->trans_type . '.' . sha1($this->trans_type . serialize($this->trans_no));
		}
		// Reading document into order
		//
		/**
		 * @param			$type
		 * @param int	$trans_no
		 * @param bool $view
		 */
		public function read($type, $trans_no = 0, $view = false) {
			if (!is_array($trans_no)) {
				$trans_no = array($trans_no);
			}
			if ($trans_no[0]) // read old transaction
			{
				if ($type == ST_SALESORDER || $type == ST_SALESQUOTE) { // sales order || sales quotation
					$this->get($trans_no[0], $type);
					if ($view) { // prepare for DN/IV entry
						for ($line_no = 0; $line_no < count($this->line_items); $line_no++) {
							$line = &$this->line_items[$line_no];
							$line->src_id = $line->id; // save src line ids for update
							$line->qty_dispatched = $line->quantity - $line->qty_done;
						}
					}
				}
				else { // other type of sales transaction
					Debtor_Trans::read($type, $trans_no, $this);
					if ($this->order_no) { // free hand credit notes have no order_no
						$sodata = Sales_Order::get_header($this->order_no, ST_SALESORDER);
						$this->cust_ref = $sodata["customer_ref"];
						// currently currency is hard linked to debtor account
						//	$this->customer_currency = $sodata["curr_code"];
						$this->name = $sodata["contact_name"];
						$this->deliver_to = $sodata["deliver_to"];
						$this->delivery_address = $sodata["delivery_address"];
					}
					// old derivative transaction edit
					if (!$view && ($type != ST_CUSTCREDIT || $this->trans_link != 0)) {
						$src_type = Debtor_Trans::get_parent_type($type);
						if ($src_type == ST_SALESORDER && isset($sodata)) { // get src data from sales_orders
							$this->src_docs = array($sodata['order_no'] => $sodata['version']);
							$srcdetails = Sales_Order::get_details($this->order_no, ST_SALESORDER);
						}
						else { // get src_data from debtor_trans
							$this->src_docs = Debtor_Trans::get_version($src_type, Debtor_Trans::get_parent($type, $trans_no[0]));
							$srcdetails = Debtor_TransDetail::get($src_type, array_keys($this->src_docs));
						}
						// calculate & save: qtys on other docs and free qtys on src doc
						for ($line_no = 0; $srcline = DB::fetch($srcdetails); $line_no++) {
							$sign = 1; // $type==13 ? 1 : -1; // this is strange debtor_trans atavism
							$line = &$this->line_items[$line_no];
							$line->src_id = $srcline['id']; // save src line ids for update
							$line->qty_old = $line->qty_dispatched = $line->quantity;
							$line->quantity += $sign * ($srcline['quantity'] - $srcline['qty_done']); // add free qty on src doc
						}
					}
					else { // prepare qtys for derivative document entry (not used in display)
						for ($line_no = 0; $line_no < count($this->line_items); $line_no++) {
							$line = &$this->line_items[$line_no];
							$line->src_id = $line->id; // save src line ids for update
							$line->qty_dispatched = $line->quantity - $line->qty_done;
						}
					}
				}
			}
			else { // new document
				$this->trans_type = $type;
				$this->trans_no = 0;
				$this->customer_currency = Bank_Currency::for_company();
				// set new sales document defaults here
				if (Session::i()->global_customer != ALL_TEXT) {
					$this->customer_id = Session::i()->global_customer;
				}
				else {
					$this->customer_id = '';
				}
				$this->document_date = Dates::new_doc_date();
				if (!Dates::is_date_in_fiscalyear($this->document_date)) {
					$this->document_date = Dates::end_fiscalyear();
				}
				$this->reference = Ref::get_next($this->trans_type);
				$this->set_salesman();
				if ($type == ST_SALESINVOICE) {
					$this->due_date = Sales_Order::get_invoice_duedate($this->customer_id, $this->document_date);
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
				}
				else {
					$this->due_date = Dates::add_days($this->document_date, DB_Company::get_pref('default_delivery_required'));
				}
			}
			$this->credit = Debtor::get_credit($this->customer_id);
		}
		// Writing new/modified sales document to database.
		// Makes parent documents for direct delivery/invoice by recurent call.
		// $policy - 0 or 1: writeoff/return for IV, back order/cancel for DN
		/**
		 * @param int $policy
		 *
		 * @return int|void
		 */
		public function write($policy = 0) {
			if (count($this->src_docs) == 0 && ($this->trans_type == ST_SALESINVOICE || $this->trans_type == ST_CUSTDELIVERY)) {
				// this is direct document - first add parent
				$src = (PHP_VERSION < 5) ? $this : clone($this); // make local copy of this order
				$src->trans_type = Debtor_Trans::get_parent_type($src->trans_type);
				$src->reference = Ref::get_next($src->trans_type);
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
			Orders::session_delete($this->order_id);
			switch ($this->trans_type) {
				case ST_SALESINVOICE:
					return Sales_Invoice::add($this);
				case ST_CUSTCREDIT:
					return Sales_Credit::add($this, $policy);
				case ST_CUSTDELIVERY:
					return Sales_Delivery::add($this, $policy);
				case ST_SALESORDER:
					$_SESSION['Jobsboard'] = clone($this);
				case ST_SALESQUOTE:
				default:
					if ($this->trans_no == 0) // new document
					{
						return $this->add();
					}
					else {
						return $this->update();
					}
			}
		}
		/**
		 * @param $cust_ref
		 *
		 * @return bool
		 */
		public function check_cust_ref($cust_ref) {
			if (!is_int($this->trans_type)) {
				return false;
			}
			$sql = "SELECT customer_ref,type FROM sales_orders WHERE debtor_no=" . DB::escape($this->customer_id) . " AND customer_ref=" . DB::escape($cust_ref) . " AND type != " . $this->trans_type;
			$result = DB::query($sql);
			return (DB::num_rows($result) > 0) ? false : true;
		}
		/**
		 * @param $customer_id
		 * @param $customer_name
		 * @param $currency
		 * @param $discount
		 * @param $payment
		 */
		public function set_customer($customer_id, $customer_name, $currency, $discount, $payment) {
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
				$this->credit = Debtor::get_credit($customer_id);
			}
		}
		/**
		 * @param				$branch_id
		 * @param				$tax_group_id
		 * @param bool	 $tax_group_name
		 * @param string $phone
		 * @param string $email
		 * @param string $name
		 */
		public function set_branch($branch_id, $tax_group_id, $tax_group_name = false, $phone = '', $email = '', $name = '') {
			$this->Branch = $branch_id;
			$this->phone = $phone;
			$this->email = $email;
			$this->tax_group_id = $tax_group_id;
			$this->tax_group_array = Tax_Groups::get_items_as_array($tax_group_id);
		}
		/**
		 * @param null $salesman_code
		 */
		public function set_salesman($salesman_code = null) {
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
		/**
		 * @param		 $sales_type
		 * @param		 $sales_name
		 * @param int $tax_included
		 * @param int $factor
		 */
		public function set_sales_type($sales_type, $sales_name, $tax_included = 0, $factor = 0) {
			$this->sales_type = $sales_type;
			$this->sales_type_name = $sales_name;
			$this->tax_included = $tax_included;
			$this->price_factor = $factor;
		}
		/**
		 * @param $id
		 * @param $name
		 */
		public function set_location($id, $name) {
			$this->Location = $id;
			$this->location_name = $name;
		}
		/**
		 * @param			$shipper
		 * @param			$destination
		 * @param			$address
		 * @param null $freight_cost
		 */
		public function set_delivery($shipper, $destination, $address, $freight_cost = null) {
			$this->ship_via = $shipper;
			$this->deliver_to = $destination;
			$this->delivery_address = $address;
			if (isset($freight_cost)) {
				$this->freight_cost = $freight_cost;
			}
		}
		/**
		 * @param			$line_no
		 * @param			$stock_id
		 * @param			$qty
		 * @param			$price
		 * @param			$disc
		 * @param int	$qty_done
		 * @param int	$standard_cost
		 * @param null $description
		 * @param int	$id
		 * @param int	$src_no
		 *
		 * @return int
		 */
		public function add_to_order($line_no, $stock_id, $qty, $price, $disc, $qty_done = 0, $standard_cost = 0, $description = null, $id = 0, $src_no = 0) {
			if (isset($stock_id) && $stock_id != "" && isset($qty) /* && $qty > 0*/) {
				$this->line_items[$line_no] = new Sales_Line($stock_id, $qty, $price, $disc, $qty_done, $standard_cost, $description, $id, $src_no);
				return 1;
			}
			else {
				// shouldn't come here under normal circumstances
				Errors::show_db_error("unexpected - adding an invalid item or null quantity", "", true);
			}
			return 0;
		}
		/**
		 * @param				$line_no
		 * @param				$qty
		 * @param				$price
		 * @param				$disc
		 * @param string $description
		 */
		public function update_order_item($line_no, $qty, $price, $disc, $description = "") {
			if ($description != "") {
				$this->line_items[$line_no]->description = $description;
			}
			$this->line_items[$line_no]->quantity = $qty;
			$this->line_items[$line_no]->qty_dispatched = $qty;
			$this->line_items[$line_no]->price = $price;
			$this->line_items[$line_no]->discount_percent = $disc;
		}
		/**
		 * @param $discount
		 */
		public function discount_all($discount) {
			foreach ($this->line_items as $line) {
				$line->discount_percent = $discount;
			}
		}
		/**
		 * @param $line_no
		 * @param $qty
		 */
		public function update_add_order_item_qty($line_no, $qty) {
			$this->line_items[$line_no]->quantity += $qty;
		}
		/**
		 * @param $line_no
		 */
		public function remove_from_order($line_no) {
			array_splice($this->line_items, $line_no, 1);
		}
		/**
		 *
		 */
		public function clear_items() {
			unset($this->line_items);
			$this->line_items = array();
			$this->sales_type = "";
			$this->trans_no = 0;
			$this->customer_id = $this->order_no = 0;
		}
		/**
		 * @return int
		 */
		public function count_items() {
			$counter = 0;
			foreach ($this->line_items as $line) {
				if ($line->quantity != $line->qty_done) {
					$counter++;
				}
			}
			return $counter;
		}
		/**
		 * @return float|int
		 */
		public function get_items_total() {
			$total = 0;
			foreach ($this->line_items as $line) {
				/* @var Sales_Line $line */
				$price = $line->line_price();
				$total += round($line->quantity * $price * (1 - $line->discount_percent), User::price_dec());
			}
			return $total;
		}
		/**
		 *
		 * @return float|int
		 */
		public function get_items_total_dispatch() {
			$total = 0;
			foreach ($this->line_items as $line) {
				/* @var Sales_Line $line */
				$price = $line->line_price();
				$total += round(($line->qty_dispatched * $price * (1 - $line->discount_percent)), User::price_dec());
			}
			return $total;
		}
		/**
		 * @return bool
		 */
		public function has_items_dispatch() {
			foreach ($this->line_items as $line) {
				if ($line->qty_dispatched > 0) {
					return true;
				}
			}
			return false;
		}
		/**
		 * @return int
		 */
		public function any_already_delivered() {
			/* Checks if there have been any line item processed */
			foreach ($this->line_items as $stock_item) {
				if ($stock_item->qty_done != 0) {
					return 1;
				}
			}
			return 0;
		}
		/**
		 * @param $line_no
		 *
		 * @return int
		 */
		public function some_already_delivered($line_no) {
			/* Checks if there have been deliveries of a specific line item */
			if (isset($this->line_items[$line_no]) && $this->line_items[$line_no]->qty_done != 0) {
				return 1;
			}
			return 0;
		}
		/**
		 * @param null $shipping_cost
		 *
		 * @return array|null
		 */
		public function get_taxes_for_order($shipping_cost = null) {
			$items = array();
			$prices = array();
			if ($shipping_cost == null) {
				$shipping_cost = $this->freight_cost;
			}
			foreach ($this->line_items as $ln_item) {
				/* @var Sales_Line $ln_item */
				$items[] = $ln_item->stock_id;
				$prices[] = round(($ln_item->quantity * $ln_item->line_price() * (1 - $ln_item->discount_percent)), User::price_dec());
			}
			$taxes = Tax::for_items($items, $prices, $shipping_cost, $this->tax_group_id, $this->tax_included, $this->tax_group_array);
			// Adjustment for swiss franken, we always have 5 rappen = 1/20 franken
			if ($this->customer_currency == 'CHF') {
				$val = $taxes['1']['Value'];
				$val1 = (floatval((intval(round(($val * 20), 0))) / 20));
				$taxes['1']['Value'] = $val1;
			}
			return $taxes;
		}
		/**
		 * @param null $shipping_cost
		 *
		 * @return array|null
		 */
		public function get_taxes($shipping_cost = null) {
			$items = array();
			$prices = array();
			if ($shipping_cost == null) {
				$shipping_cost = $this->freight_cost;
			}
			foreach ($this->line_items as $line) {
				/* @var Sales_Line $line */
				$items[] = $line->stock_id;
				$prices[] = round(($line->qty_dispatched * $line->line_price() * (1 - $line->discount_percent)), User::price_dec());
			}
			$taxes = Tax::for_items($items, $prices, $shipping_cost, $this->tax_group_id, $this->tax_included, $this->tax_group_array);
			// Adjustment for swiss franken, we always have 5 rappen = 1/20 franken
			if ($this->customer_currency == 'CHF') {
				$val = $taxes['1']['Value'];
				$val1 = (floatval((intval(round(($val * 20), 0))) / 20));
				$taxes['1']['Value'] = $val1;
			}
			return $taxes;
		}
		/**
		 * @return int
		 */
		public function get_tax_free_shipping() {
			if ($this->tax_included == 0) {
				return $this->freight_cost;
			}
			else {
				return ($this->freight_cost - $this->get_shipping_tax());
			}
		}
		/**
		 * @return float
		 */
		public function get_shipping_tax() {
			$tax_items = Tax_Groups::for_shipping_as_array();
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
			}
			else {
				return round($this->freight_cost * $tax_rate / 100, User::price_dec());
			}
		}
		/**
		 *
		 */
		public function store() {
			$serial = serialize($this);
			$sql = "DELETE FROM `user_class_store` WHERE `user_id`=" . $_SESSION['current_user']->user;
			DB::query($sql);
			$sql = "INSERT INTO `user_class_store` (`user_id`, `data`) VALUE (" . $_SESSION['current_user']->user . ",'" . $serial . "')";
			DB::query($sql);
		}
		/**
		 * @return int
		 */
		public function add() {
			DB::begin();
			$order_no = SysTypes::get_next_trans_no($this->trans_type);
			$del_date = Dates::date2sql($this->due_date);
			$order_type = 0; // this is default on new order
			$sql = "INSERT INTO sales_orders (order_no, type, debtor_no, trans_type, branch_code, customer_ref, reference, salesman, comments, ord_date,
			order_type, ship_via, deliver_to, delivery_address, contact_name, contact_phone,
			contact_email, freight_cost, from_stk_loc, delivery_date)
			VALUES (" . DB::escape($order_no) . "," . DB::escape($order_type) . "," . DB::escape($this->customer_id) . ", " . DB::escape($this->trans_type) . "," . DB::escape($this->Branch) . ", " . DB::escape($this->cust_ref) . "," . DB::escape($this->reference) . "," . DB::escape($this->salesman) . "," . DB::escape($this->Comments) . ",'" . Dates::date2sql($this->document_date) . "', " . DB::escape($this->sales_type) . ", " . DB::escape($this->ship_via) . "," . DB::escape($this->deliver_to) . "," . DB::escape($this->delivery_address) . ", " . DB::escape($this->name) . ", " . DB::escape($this->phone) . ", " . DB::escape($this->email) . ", " . DB::escape($this->freight_cost) . ", " . DB::escape($this->Location) . ", " . DB::escape($del_date) . ")";
			DB::query($sql, "order Cannot be Added");
			$this->trans_no = array($order_no => 0);
			$st_ids = array();
			if (Config::get('accounts_stock_emailnotify') == 1) {
				$st_names = array();
				$st_num = array();
				$st_reorder = array();
			}
			foreach ($this->line_items as $line) {
				if (Config::get('accounts_stock_emailnotify') == 1 && Item::is_inventory_item($line->stock_id)) {
					$sql = "SELECT loc_stock.*, locations.location_name, locations.email
					FROM loc_stock, locations
					WHERE loc_stock.loc_code=locations.loc_code
					AND loc_stock.stock_id = '" . $line->stock_id . "'
					AND loc_stock.loc_code = '" . $this->Location . "'";
					$res = DB::query($sql, "a location could not be retreived");
					$loc = DB::fetch($res);
					if ($loc['email'] != "") {
						$qoh = Item::get_qoh_on_date($line->stock_id, $this->Location);
						$qoh -= Item::get_demand($line->stock_id, $this->Location);
						$qoh -= WO::get_demand_asm_qty($line->stock_id, $this->Location);
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
				$sql .= $order_no . "," . $this->trans_type . "," . DB::escape($line->stock_id) . ", " . DB::escape($line->description) . ", $line->price,
					$line->quantity,
					$line->discount_percent)";
				DB::query($sql, "order Details Cannot be Added");
			} /* inserted line items into sales order details */
			DB_AuditTrail::add($this->trans_type, $order_no, $this->document_date);
			Ref::save($this->trans_type, $this->reference);
			DB::commit();
			if (isset($loc, $st_names, $st_reorder) && Config::get('accounts_stock_emailnotify') == 1 && count($st_ids) > 0) {
				$company = DB_Company::get_prefs();
				$mail = new Reports_Email($company['coy_name'], $company['email']);
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
				$mail->send();
			}
			Orders::session_delete($this->order_id);
			return $order_no;
		}
		/**
		 * @param $order_no
		 * @param $trans_type
		 *
		 * @return bool
		 */
		public function get($order_no, $trans_type) {
			$myrow = $this->get_header($order_no, $trans_type);
			$this->trans_type = $myrow['trans_type'];
			$this->so_type = $myrow["type"];
			$this->trans_no = array($order_no => $myrow["version"]);
			$this->set_customer($myrow["debtor_no"], $myrow["name"], $myrow["curr_code"], $myrow["discount"], $myrow["payment_terms"]);
			$this->set_branch($myrow["branch_code"], $myrow["tax_group_id"], $myrow["tax_group_name"], $myrow["contact_phone"], $myrow["contact_email"], $myrow["contact_name"]);
			$this->set_sales_type($myrow["sales_type_id"], $myrow["sales_type"], $myrow["tax_included"], 0); // no default price calculations on edit
			$this->set_location($myrow["from_stk_loc"], $myrow["location_name"]);
			$this->set_delivery($myrow["ship_via"], $myrow["deliver_to"], $myrow["delivery_address"], $myrow["freight_cost"]);
			$this->cust_ref = $myrow["customer_ref"];
			$this->name = $myrow["contact_name"];
			$this->sales_type = $myrow["order_type"];
			$this->reference = $myrow["reference"];
			$this->salesman = $myrow["salesman"];
			$this->Comments = $myrow["comments"];
			$this->due_date = Dates::sql2date($myrow["delivery_date"]);
			$this->document_date = Dates::sql2date($myrow["ord_date"]);
			$result = Sales_Order::get_details($order_no, $this->trans_type);
			if (DB::num_rows($result) > 0) {
				$line_no = 0;
				while ($myrow = DB::fetch($result)) {
					$this->add_to_order($line_no, $myrow["stk_code"], $myrow["quantity"], $myrow["unit_price"], $myrow["discount_percent"], $myrow["qty_done"], $myrow["standard_cost"], $myrow["description"], $myrow["id"]);
					$line_no++;
				}
			}
			return true;
		}
		/**
		 * @param			$new_item
		 * @param			$new_item_qty
		 * @param			$price
		 * @param			$discount
		 * @param null $description
		 * @param bool $no_errors
		 *
		 * @return mixed
		 */
		public function add_line($new_item, $new_item_qty, $price, $discount, $description = null, $no_errors = false) {
			// calculate item price to sum of kit element prices factor for
			// value distribution over all exploded kit items
			$item = Item_Code::is_kit($new_item);
			if (DB::num_rows($item) == 1) {
				$item = DB::fetch($item);
				if (!$item['is_foreign'] && $item['item_code'] == $item['stock_id']) {
					foreach ($this->line_items as $order_item) {
						if (strcasecmp($order_item->stock_id, $item['stock_id']) == 0 && !$no_errors) {
							Errors::warning(_("For Part: '") . $item['stock_id'] . "' " . _("This item is already on this document. You have been warned."));
							break;
						}
					}
					$this->add_to_order(count($this->line_items), $item['stock_id'], $new_item_qty * $item['quantity'], $price, $discount, 0, 0, $description);
					return;
				}
			}
			$std_price = Item_Price::get_kit($new_item, $this->customer_currency, $this->sales_type, $this->price_factor, get_post('OrderDate'), true);
			if ($std_price == 0) {
				$price_factor = 0;
			}
			else {
				$price_factor = $price / $std_price;
			}
			$kit = Item_Code::get_kit($new_item);
			$item_num = DB::num_rows($kit);
			while ($item = DB::fetch($kit)) {
				$std_price = Item_Price::get_kit($item['stock_id'], $this->customer_currency, $this->sales_type, $this->price_factor, get_post('OrderDate'), true);
				// rounding differences are included in last price item in kit
				$item_num--;
				if ($item_num) {
					$price -= $item['quantity'] * $std_price * $price_factor;
					$item_price = $std_price * $price_factor;
				}
				else {
					if ($item['quantity']) {
						$price = $price / $item['quantity'];
					}
					$item_price = $price;
				}
				$item_price = round($item_price, User::price_dec());
				if (!$item['is_foreign'] && $item['item_code'] != $item['stock_id']) { // this is sales kit - recurse
					$this->add_line($item['stock_id'], $new_item_qty * $item['quantity'], $item_price, $discount, $std_price);
				}
				else { // stock item record eventually with foreign code
					// check duplicate stock item
					foreach ($this->line_items as $this_item) {
						if (strcasecmp($this_item->stock_id, $item['stock_id']) == 0) {
							Errors::warning(_("For Part: '") . $item['stock_id'] . "' " . _("This item is already on this document. You have been warned."));
							break;
						}
					}
					$this->add_to_order(count($this->line_items), $item['stock_id'], $new_item_qty * $item['quantity'], $item_price, $discount);
				}
			}
		}
		/**
		 *
		 */
		public function start() {
			Orders::session_start($this);
		}
		/**
		 *
		 */
		public function finish() {
			if (is_object($this) && Orders::session_exists($this)) {
				Orders::session_delete($this->order_id);
			}
		}
		/**
		 * @return bool
		 */
		public function active() {
			return Orders::session_exists($this->order_id);
		}
		/**
		 *
		 */
		public static function update_version($order) {
			foreach ($order as $so_num => $so_ver) {
				$sql = 'UPDATE sales_orders SET version=version+1 WHERE order_no=' . $so_num . ' AND version=' . $so_ver . " AND trans_type=30";
				DB::query($sql, 'Concurrent editing conflict while sales order update');
			}
		}
		/**
		 *
		 */
		public function update() {
			$del_date = Dates::date2sql($this->due_date);
			$ord_date = Dates::date2sql($this->document_date);
			$order_no = key($this->trans_no);
			$version = current($this->trans_no);
			DB::begin();
			$sql = "UPDATE sales_orders SET type =" . DB::escape($this->so_type) . " ,
					debtor_no = " . DB::escape($this->customer_id) . ",
					branch_code = " . DB::escape($this->Branch) . ",
					customer_ref = " . DB::escape($this->cust_ref) . ",
					reference = " . DB::escape($this->reference) . ",
					salesman = " . DB::escape($this->salesman) . ",
					comments = " . DB::escape($this->Comments) . ",
					ord_date = " . DB::escape($ord_date) . ",
					order_type = " . DB::escape($this->sales_type) . ",
					ship_via = " . DB::escape($this->ship_via) . ",
					deliver_to = " . DB::escape($this->deliver_to) . ",
					delivery_address = " . DB::escape($this->delivery_address) . ",
					contact_name = " . DB::escape($this->name) . ",
					contact_phone = " . DB::escape($this->phone) . ",
					contact_email = " . DB::escape($this->email) . ",
					freight_cost = " . DB::escape($this->freight_cost) . ",
					from_stk_loc = " . DB::escape($this->Location) . ",
					delivery_date = " . DB::escape($del_date) . ",
					version = " . ($version + 1) . "
				 WHERE order_no=" . $order_no . "
				 AND trans_type=" . $this->trans_type . " AND version=" . $version;
			DB::query($sql, "order Cannot be Updated, this can be concurrent edition conflict");
			$sql = "DELETE FROM sales_order_details WHERE order_no =" . $order_no . " AND trans_type=" . $this->trans_type;
			DB::query($sql, "Old order Cannot be Deleted");
			if (Config::get('accounts_stock_emailnotify') == 1) {
				$st_ids = array();
				$st_names = array();
				$st_num = array();
				$st_reorder = array();
			}
			foreach ($this->line_items as $line) {
				if (Config::get('accounts_stock_emailnotify') == 1 && Item::is_inventory_item($line->stock_id)) {
					$sql = "SELECT loc_stock.*, locations.location_name, locations.email
							FROM loc_stock, locations
							WHERE loc_stock.loc_code=locations.loc_code
							 AND loc_stock.stock_id = " . DB::escape($line->stock_id) . "
							 AND loc_stock.loc_code = " . DB::escape($this->Location);
					$res = DB::query($sql, "a location could not be retreived");
					$loc = DB::fetch($res);
					if ($loc['email'] != "") {
						$qoh = Item::get_qoh_on_date($line->stock_id, $this->Location);
						$qoh -= Item::get_demand($line->stock_id, $this->Location);
						$qoh -= WO::get_demand_asm_qty($line->stock_id, $this->Location);
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
					 (id, order_no, trans_type, stk_code, description, unit_price, quantity,
					 discount_percent, qty_sent)
					 VALUES (";
				$sql .= DB::escape($line->id ? $line->id : 0) . "," . $order_no . "," . $this->trans_type . "," . DB::escape($line->stock_id) . ",
						" . DB::escape($line->description) . ", " . DB::escape($line->price) . ", " . DB::escape($line->quantity) . ", " . DB::escape($line->discount_percent) . ", " . DB::escape($line->qty_done) . " )";
				DB::query($sql, "Old order Cannot be Inserted");
			} /* inserted line items into sales order details */
			DB_AuditTrail::add($this->trans_type, $order_no, $this->document_date, _("Updated."));
			Ref::delete($this->trans_type, $order_no);
			Ref::save($this->trans_type, $this->reference);
			DB::commit();
			if (Config::get('accounts_stock_emailnotify') == 1 && count($st_ids) > 0) {
				$company = DB_Company::get_prefs();
				$mail = new Reports_Email($company['coy_name'], $company['email']);
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
				$mail->send();
			}
		}
		/**
		 * @param $customer_id
		 * @param $branch_id
		 *
		 * @return mixed|string
		 */
		public function customer_to_order($customer_id, $branch_id) {
			$ret_error = "";
			$myrow = Sales_Order::get_customer($customer_id);
			$name = $myrow['name'];
			if ($myrow['dissallow_invoices'] == 1) {
				$ret_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
			}
			$this->set_customer($customer_id, $name, $myrow['curr_code'], $myrow['discount'], $myrow['payment_terms'], $myrow['pymt_discount']); // the sales type determines the price list to be used by default
			$this->set_sales_type($myrow['salestype'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
			if ($this->trans_type != ST_SALESORDER && $this->trans_type != ST_SALESQUOTE) {
				$this->dimension_id = $myrow['dimension_id'];
				$this->dimension2_id = $myrow['dimension2_id'];
			}
			$result = Sales_Order::get_branch($customer_id, $branch_id);
			if (DB::num_rows($result) == 0) {
				return _("The selected customer and branch are not valid, or the customer does not have any branches.");
			}
			$myrow = DB::fetch($result);
			$this->set_branch($branch_id, $myrow["tax_group_id"], $myrow["tax_group_name"], $myrow["phone"], $myrow["email"]);
			//$address = trim($myrow["br_post_address"]) != '' ? $myrow["br_post_address"] : (trim($myrow["br_address"]) != '' ?		$myrow["br_address"] : $deliver);
			$address = $myrow['br_address'] . "\n";
			if ($myrow['city']) {
				$address .= $myrow['city'];
			}
			if ($myrow['state']) {
				$address .= ", " . strtoupper($myrow['state']);
			}
			if ($myrow['postcode']) {
				$address .= ", " . $myrow['postcode'];
			}
			$this->set_delivery($myrow["default_ship_via"], $name, $address);
			if ($this->trans_type == ST_SALESINVOICE) {
				$this->due_date = Sales_Order::get_invoice_duedate($customer_id, $this->document_date);
				if ($this->pos != -1) {
					$this->cash = Dates::date_diff2($this->due_date, Dates::Today(), 'd') < 2;
				}
				if ($this->due_date == Dates::Today()) {
					$this->pos == -1;
				}
			}
			if ($this->cash) {
				if ($this->pos != -1) {
					$paym = Sales_Point::get($this->pos);
					$this->set_location($paym["pos_location"], $paym["location_name"]);
				}
			}
			else {
				$this->set_location($myrow["default_location"], $myrow["location_name"]);
			}
			return $ret_error;
		}
		/**
		 * @param			$title
		 * @param bool $editable_items
		 */
		public function summary($title, $editable_items = false) {
			Display::heading($title);
			Display::div_start('items_table');
			if (count($this->line_items) > 0) {
				start_outer_table('center width90');
				table_section(1);
				Display::link_params_separate("/purchases/po_entry_items.php", _("Create PO from this order"),
																			"NewOrder=Yes&UseOrder=" . $this->order_id . "' class='button'", true, true);
				table_section(2);
				Display::link_params_separate("/purchases/po_entry_items.php", _("Dropship this order"),
																			"NewOrder=Yes&UseOrder=" . $this->order_id . "&DRP=1' class='button'", true, true);
				end_outer_table(1);
			}
			start_table('tablestyle ');
			$th = array(
				_("Item Code"), _("Item Description"), _("Quantity"), _("Delivered"), _("Unit"), _("Price"), _("Discount %"), _("Total"), ""
			);
			if ($this->trans_no == 0) {
				unset($th[3]);
			}
			if (count($this->line_items)) {
				$th[] = '';
			}
			table_header($th);
			$total_discount = $total = 0;
			$k = 0; //row colour counter
			$id = find_submit(MODE_EDIT);
			$has_marked = false;
			foreach ($this->line_items as $line_no => $stock_item) {
				$line_total = round($stock_item->qty_dispatched * $stock_item->price * (1 - $stock_item->discount_percent), User::price_dec());
				$line_discount = round($stock_item->qty_dispatched * $stock_item->price, User::price_dec()) - $line_total;
				$qoh_msg = '';
				if (!$editable_items || $id != $line_no) {
					if (!DB_Company::get_pref('allow_negative_stock') && Item::is_inventory_item($stock_item->stock_id)) {
						$qoh = Item::get_qoh_on_date($stock_item->stock_id, $_POST['Location'], $_POST['OrderDate']);
						if ($stock_item->qty_dispatched > $qoh) {
							// oops, we don't have enough of one of the component items
							start_row("class='stockmankobg'");
							$qoh_msg .= $stock_item->stock_id . " - " . $stock_item->description . ": " . _("Quantity On Hand") . " = " . Num::format($qoh, Item::qty_dec($stock_item->stock_id)) . '<br>';
							$has_marked = true;
						}
						else {
							alt_table_row_color($k);
						}
					}
					else {
						alt_table_row_color($k);
					}
					label_cell($stock_item->stock_id, "class='stock pointer' data-stock_id='{$stock_item->stock_id}'");
					//label_cell($stock_item->description, "nowrap" );
					description_cell($stock_item->description);
					$dec = Item::qty_dec($stock_item->stock_id);
					qty_cell($stock_item->qty_dispatched, false, $dec);
					if ($this->trans_no != 0) {
						qty_cell($stock_item->qty_done, false, $dec);
					}
					label_cell($stock_item->units);
					amount_cell($stock_item->price);
					percent_cell($stock_item->discount_percent * 100);
					amount_cell($line_total);
					if ($editable_items) {
						edit_button_cell("Edit$line_no", _("Edit"), _('Edit document line'));
						delete_button_cell("Delete$line_no", _("Delete"), _('Remove line from document'));
					}
					end_row();
				}
				else {
					$this->item_controls($k, $line_no);
				}
				$total += $line_total;
				$total_discount += $line_discount;
			}
			if ($id == -1 && $editable_items) {
				$this->item_controls($k);
			}
			$colspan = 6;
			if ($this->trans_no != 0) {
				++$colspan;
			}
			start_row();
			label_cell(_("Shipping Charge"), "colspan=$colspan class='right'");
			small_amount_cells(null, 'freight_cost', Num::price_format(get_post('freight_cost', 0)));
			label_cell('', 'colspan=2');
			end_row();
			$display_sub_total = Num::price_format($total + Validation::input_num('freight_cost'));
			start_row();
			label_cells(_("Total Discount"), $total_discount, "colspan=$colspan style='background:inherit; text-align:right;'", "class=right");
			HTML::td(true)->button('discountall', 'Discount All', array('name' => 'discountall'), false);
			hidden('_discountall', '0', true);
			HTML::td();
			$action = "var discount = prompt('Discount Percent?',''); if (!discount) return false;
				$(\"[name='_discountall']\").val(Number(discount));e=$(this);save_focus(e);JsHttpRequest.request(this);return false;";
			JS::addLiveEvent('#discountall', 'click', $action);
			end_row();
			label_row(_("Sub-total"), $display_sub_total, "colspan=$colspan style='background:inherit; text-align:right;'", "class=right", 2);
			$taxes = $this->get_taxes(Validation::input_num('freight_cost'));
			$tax_total = Tax::edit_items($taxes, $colspan, $this->tax_included, 2);
			$display_total = Num::price_format(($total + Validation::input_num('freight_cost') + $tax_total));
			start_row();
			label_cells(_("Amount Total"), $display_total, "colspan=$colspan style='background:inherit; text-align:right;'", "class=right");
			submit_cells('update', _("Update"), "colspan=2", _("Refresh"), true);
			end_row();
			end_table();
			if ($has_marked) {
				Errors::notice(_("Marked items have insufficient quantities in stock as on day of delivery."), 0, 1, "class='stockmankofg'");
			}
			if ($this->trans_type != 30 && !DB_Company::get_pref('allow_negative_stock')) {
				Errors::error(_("The delivery cannot be processed because there is an insufficient quantity for item:") . '<br>' . $qoh_msg);
			}
			Display::div_end();
		}
		/**
		 * @param			$date_text
		 * @param bool $display_tax_group
		 *
		 * @return mixed|string
		 */
		public function header($date_text, $display_tax_group = false) {
			$editable = ($this->any_already_delivered() == 0);
			start_outer_table('tablestyle2 width90');
			table_section(1);
			$customer_error = "";
			$change_prices = 0;
			if (!$editable) {
				if (isset($this)) {
					// can't change the customer/branch if items already received on this order
					//echo $this->customer_name . " - " . $this->deliver_to;
					label_row(_('Customer:'), $this->customer_name . " - " . $this->deliver_to, "id='customer_id_label' class='label pointer'");
					hidden('customer_id', $this->customer_id);
					hidden('branch_id', $this->Branch);
					hidden('sales_type', $this->sales_type);
					//		if ($this->trans_type != ST_SALESORDER && $this->trans_type != ST_SALESQUOTE) {
					hidden('dimension_id', $this->dimension_id); // 2008-11-12 Joe Hunt
					hidden('dimension2_id', $this->dimension2_id);
					//		}
				}
			}
			else {
				//Debtor::row(_("Customer:"), 'customer_id', null, false, true, false, true);
				Debtor::newselect();
				if ($this->customer_id != get_post('customer_id', -1)) {
					// customer has changed
					Ajax::i()->activate('branch_id');
				}
				Debtor_Branch::row(_("Branch:"), $_POST['customer_id'], 'branch_id', null, false, true, true, true);
				if (($this->Branch != get_post('branch_id', -1))) {
					if (!isset($_POST['branch_id']) || $_POST['branch_id'] == "") {
						// ignore errors on customer search box call
						if ($_POST['customer_id'] == '') {
							$customer_error = _("No customer found for entered text.");
						}
						else {
							$customer_error = _("The selected customer does not have any branches. Please create at least one branch.");
						}
						unset($_POST['branch_id']);
						$this->Branch = 0;
					}
					else {
						$old_order = clone($this);
						$customer_error = $this->customer_to_order($_POST['customer_id'], $_POST['branch_id']);
						$_POST['Location'] = $this->Location;
						$_POST['deliver_to'] = $this->deliver_to;
						$_POST['delivery_address'] = $this->delivery_address;
						$_POST['name'] = $this->name;
						$_POST['phone'] = $this->phone;
						if (get_post('cash') !== $this->cash) {
							$_POST['cash'] = $this->cash;
							Ajax::i()->activate('delivery');
							Ajax::i()->activate('cash');
						}
						else {
							if ($this->trans_type == ST_SALESINVOICE) {
								$_POST['delivery_date'] = $this->due_date;
								Ajax::i()->activate('delivery_date');
							}
							Ajax::i()->activate('Location');
							Ajax::i()->activate('deliver_to');
							Ajax::i()->activate('name');
							Ajax::i()->activate('phone');
							Ajax::i()->activate('delivery_address');
						}
						// change prices if necessary
						// what about discount in template case?
						if ($old_order->customer_currency != $this->customer_currency) {
							$change_prices = 1;
						}
						if ($old_order->sales_type != $this->sales_type) {
							// || $old_order->default_discount!=$this->default_discount
							$_POST['sales_type'] = $this->sales_type;
							Ajax::i()->activate('sales_type');
							$change_prices = 1;
						}
						if ($old_order->dimension_id != $this->dimension_id) {
							$_POST['dimension_id'] = $this->dimension_id;
							Ajax::i()->activate('dimension_id');
						}
						if ($old_order->dimension2_id != $this->dimension2_id) {
							$_POST['dimension2_id'] = $this->dimension2_id;
							Ajax::i()->activate('dimension2_id');
						}
						unset($old_order);
					}
					Session::i()->global_customer = $_POST['customer_id'];
				} // changed branch
				else {
					$row = Sales_Order::get_customer($_POST['customer_id']);
					if ($row['dissallow_invoices'] == 1) {
						$customer_error = _("The selected customer account is currently on hold. Please contact the credit control personnel to discuss.");
					}
				}
			}
			if ($editable) {
				ref_row(_("Reference") . ':', 'ref', _('Reference number unique for this document type'), null, '');
			}
			else {
				hidden('ref', $this->reference);
				label_row(_("Reference:"), $this->reference);
			}
			if (!Bank_Currency::is_company($this->customer_currency)) {
				table_section(2);
				label_row(_("Customer Currency:"), $this->customer_currency);
				GL_ExchangeRate::display($this->customer_currency, Bank_Currency::for_company(), ($editable && Input::post('OrderDate') ? $_POST['OrderDate'] :
				 $this->document_date));
			}
			table_section(3);
			Debtor_Payment::credit_row($_POST['customer_id'], $this->credit);
			if ($editable) {
				Sales_Type::row(_("Price List"), 'sales_type', null, true);
			}
			else {
				label_row(_("Price List:"), $this->sales_type_name);
			}
			if ($this->sales_type != $_POST['sales_type']) {
				$myrow = Sales_Type::get($_POST['sales_type']);
				$this->set_sales_type($myrow['id'], $myrow['sales_type'], $myrow['tax_included'], $myrow['factor']);
				Ajax::i()->activate('sales_type');
				$change_prices = 1;
			}
			label_row(_("Customer Discount:"), ($this->default_discount * 100) . "%");
			table_section(4);
			if ($editable) {
				if (!isset($_POST['OrderDate']) || $_POST['OrderDate'] == "") {
					$_POST['OrderDate'] = $this->document_date;
				}
				date_row($date_text, 'OrderDate', null, $this->trans_no == 0, 0, 0, 0, null, true);
				if (isset($_POST['_OrderDate_changed'])) {
					if (!Bank_Currency::is_company($this->customer_currency) && (DB_Company::get_base_sales_type() > 0)) {
						$change_prices = 1;
					}
					Ajax::i()->activate('_ex_rate');
					if ($this->trans_type == ST_SALESINVOICE) {
						$_POST['delivery_date'] = Sales_Order::get_invoice_duedate(get_post('customer_id'), get_post('OrderDate'));
					}
					else {
						$_POST['delivery_date'] = Dates::add_days(get_post('OrderDate'), DB_Company::get_pref('default_delivery_required'));
					}
					Ajax::i()->activate('items_table');
					Ajax::i()->activate('delivery_date');
				}
				if ($this->trans_type != ST_SALESORDER && $this->trans_type != ST_SALESQUOTE) { // 2008-11-12 Joe Hunt added dimensions
					$dim = DB_Company::get_pref('use_dimension');
					if ($dim > 0) {
						Dimensions::select_row(_("Dimension") . ":", 'dimension_id', null, true, ' ', false, 1, false);
					}
					else {
						hidden('dimension_id', 0);
					}
					if ($dim > 1) {
						Dimensions::select_row(_("Dimension") . " 2:", 'dimension2_id', null, true, ' ', false, 2, false);
					}
					else {
						hidden('dimension2_id', 0);
					}
				}
			}
			else {
				label_row($date_text, $this->document_date);
				hidden('OrderDate', $this->document_date);
			}
			if ($display_tax_group) {
				label_row(_("Tax Group:"), $this->tax_group_name);
				hidden('tax_group_id', $this->tax_group_id);
			}
			Sales_UI::persons_row(_("Sales Person:"), 'salesman', (isset($this->salesman)) ? $this->salesman : $_SESSION['current_user']->salesmanid);
			end_outer_table(1); // outer table
			if ($change_prices != 0) {
				foreach ($this->line_items as $line) {
					$line->price = Item_Price::get_kit($line->stock_id, $this->customer_currency, $this->sales_type, $this->price_factor, get_post('OrderDate'));
				}
				Ajax::i()->activate('items_table');
			}
			return $customer_error;
		}
		/**
		 * @param $rowcounter
		 * @param $line_no
		 */
		public function item_controls(&$rowcounter, $line_no = -1) {
			alt_table_row_color($rowcounter);
			$id = find_submit(MODE_EDIT);
			if ($line_no != -1 && $line_no == $id) // edit old line
			{
				$_POST['stock_id'] = $this->line_items[$id]->stock_id;
				$dec = Item::qty_dec($_POST['stock_id']);
				$_POST['qty'] = Num::format($this->line_items[$id]->qty_dispatched, $dec);
				$_POST['price'] = Num::price_format($this->line_items[$id]->price);
				$_POST['Disc'] = Num::percent_format($this->line_items[$id]->discount_percent * 100);
				$_POST['description'] = $this->line_items[$id]->description;
				$units = $this->line_items[$id]->units;
				hidden('stock_id', $_POST['stock_id']);
				label_cell($_POST['stock_id'], 'class="stock"');
				textarea_cells(null, 'description', null, 50, 5);
				Ajax::i()->activate('items_table');
			}
			else // prepare new line
			{
				Sales_UI::items_cells(null, 'stock_id', null, false, false, array('description' => ''));
				if (list_updated('stock_id')) {
					Ajax::i()->activate('price');
					Ajax::i()->activate('description');
					Ajax::i()->activate('units');
					Ajax::i()->activate('qty');
					Ajax::i()->activate('line_total');
				}
				$item_info = Item::get_edit_info(Input::post('stock_id'));
				$units = $item_info["units"];
				$dec = $item_info['decimals'];
				$_POST['qty'] = Num::format(1, $dec);
				$price = Item_Price::get_kit(Input::post('stock_id'), $this->customer_currency, $this->sales_type, $this->price_factor, get_post('OrderDate'));
				$_POST['price'] = Num::price_format($price);
				$_POST['Disc'] = Num::percent_format($this->default_discount * 100);
			}
			qty_cells(null, 'qty', $_POST['qty'], null, null, $dec);
			if ($this->trans_no != 0) {
				qty_cell($line_no == -1 ? 0 : $this->line_items[$line_no]->qty_done, false, $dec);
			}
			label_cell($units, '', 'units');
			amount_cells(null, 'price');
			small_amount_cells(null, 'Disc', Num::percent_format($_POST['Disc']), null, null, User::percent_dec());
			$line_total = Validation::input_num('qty') * Validation::input_num('price') * (1 - Validation::input_num('Disc') / 100);
			amount_cell($line_total, false, '', 'line_total');
			if ($id != -1) {
				button_cell('UpdateItem', _("Update"), _('Confirm changes'), ICON_UPDATE);
				button_cell('CancelItemChanges', _("Cancel"), _('Cancel changes'), ICON_CANCEL);
				hidden('LineNo', $line_no);
				JS::set_focus('qty');
			}
			else {
				submit_cells('AddItem', _("Add Item"), "colspan=2", _('Add new item to document'), true);
			}
			end_row();
		}
		/**
		 *
		 */
		public function display_delivery_details() {
			Display::div_start('delivery');
			if (get_post('cash', 0)) { // Direct payment sale
				Ajax::i()->activate('items_table');
				Display::heading(_('Cash payment'));
				start_table('tablestyle2 width60');
				label_row(_("Deliver from Location:"), $this->location_name);
				hidden('Location', $this->Location);
				label_row(_("Cash account:"), $this->account_name);
				textarea_row(_("Comments:"), "Comments", $this->Comments, 31, 5);
				end_table();
			}
			else {
				if ($this->trans_type == ST_SALESINVOICE) {
					$title = _("Delivery Details");
					$delname = _("Due Date") . ':';
				}
				elseif ($this->trans_type == ST_CUSTDELIVERY) {
					$title = _("Invoice Delivery Details");
					$delname = _("Invoice before") . ':';
				}
				elseif ($this->trans_type == ST_SALESQUOTE) {
					$title = _("Quotation Delivery Details");
					$delname = _("Valid until") . ':';
				}
				else {
					$title = _("Order Delivery Details");
					$delname = _("Required Delivery Date") . ':';
				}
				Display::heading($title);
				start_outer_table('tablestyle2 width90');
				table_section(1);
				Inv_Location::row(_("Deliver from Location:"), 'Location', null, false, true);
				if (list_updated('Location')) {
					Ajax::i()->activate('items_table');
				}
				date_row($delname, 'delivery_date', $this->trans_type == ST_SALESORDER ? _('Enter requested day of delivery') :
				 $this->trans_type == ST_SALESQUOTE ? _('Enter Valid until Date') : '');
				text_row(_("Deliver To:"), 'deliver_to', $this->deliver_to, 40, 40, _('Additional identifier for delivery e.g. name of receiving person'));
				textarea_row("<a href='#'>Address:</a>", 'delivery_address', $this->delivery_address, 35, 5, _('Delivery address. Default is address of customer branch'), null, 'id="address_map"');
				if (strlen($this->delivery_address) > 10) {
					//JS::gmap("#address_map", $this->delivery_address, $this->deliver_to);
				}
				table_section(2);
				text_row(_("Person ordering:"), 'name', $this->name, 25, 25, 'Ordering person&#39;s name');
				text_row(_("Contact Phone Number:"), 'phone', $this->phone, 25, 25, _('Phone number of ordering person. Defaults to branch phone number'));
				text_row(_("Customer Purchase Order #:"), 'cust_ref', $this->cust_ref, 25, 25, _('Customer reference number for this order (if any)'));
				textarea_row(_("Comments:"), "Comments", $this->Comments, 31, 5);
				Sales_UI::shippers_row(_("Shipping Company:"), 'ship_via', $this->ship_via);
				end_outer_table(1);
			}
			Display::div_end();
		}
		/**
		 * @static
		 * @return mixed
		 */
		public static function restore() {
			$sql = "SELECT `data` FROM `user_class_store` WHERE `user_id`=" . $_SESSION['current_user']->user;
			$result = DB::query($sql);
			$serial = DB::fetch_assoc($result);
			$serial = $serial['data'];
			return unserialize($serial);
		}
		/**
		 * @static
		 *
		 * @param $order_no
		 * @param $trans_type
		 */
		public static function delete($order_no, $trans_type) {
			DB::begin();
			$sql = "DELETE FROM sales_orders WHERE order_no=" . DB::escape($order_no) . " AND trans_type=" . DB::escape($trans_type);
			DB::query($sql, "order Header Delete");
			$sql = "DELETE FROM sales_order_details WHERE order_no =" . DB::escape($order_no) . " AND trans_type=" . DB::escape($trans_type);
			DB::query($sql, "order Detail Delete");
			Ref::delete($trans_type, $order_no);
			DB_AuditTrail::add($trans_type, $order_no, Dates::Today(), _("Deleted."));
			DB::commit();
		}
		/**
		 * @static
		 *
		 * @param $order_no
		 * @param $trans_type
		 *
		 * @return DB_Query_Result|void
		 * @throws DBException
		 */
		public static function get_header($order_no, $trans_type) {
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
				throw new DBException("FATAL : sales order query returned a duplicate - " . DB::num_rows($result), E_ERROR);
			}
			if ($num == 1) {
				return DB::fetch($result);
			}
			return Errors::error("Database returned nothing!", E_USER_ERROR);
		}
		/**
		 * @static
		 *
		 * @param $order_no
		 * @param $trans_type
		 *
		 * @return null|PDOStatement
		 */
		public static function get_details($order_no, $trans_type) {
			$sql = "SELECT sales_order_details.id, stk_code, unit_price, sales_order_details.description,sales_order_details.quantity,
			 discount_percent,
			 qty_sent as qty_done, stock_master.units,stock_master.tax_type_id,stock_master.material_cost + stock_master.labour_cost + stock_master.overhead_cost AS standard_cost
		FROM sales_order_details, stock_master
		WHERE sales_order_details.stk_code = stock_master.stock_id
		AND order_no =" . DB::escape($order_no) . " AND trans_type = " . DB::escape($trans_type) . " ORDER BY id";
			return DB::query($sql, "Retreive order Line Items");
		}
		/**
		 * @static
		 *
		 * @param $order_no
		 *
		 * @return bool
		 */
		public static function has_deliveries($order_no) {
			$sql = "SELECT SUM(qty_sent) FROM sales_order_details WHERE order_no=" . DB::escape($order_no) . " AND trans_type=" . ST_SALESORDER . "";
			$result = DB::query($sql, "could not query for sales order usage");
			$row = DB::fetch_row($result);
			return ($row[0] > 0);
		}
		/**
		 * @static
		 *
		 * @param $order_no
		 */
		public static function close($order_no) {
			// set the quantity of each item to the already sent quantity. this will mark item as closed.
			$sql = "UPDATE sales_order_details
			SET quantity = qty_sent WHERE order_no = " . DB::escape($order_no) . " AND trans_type=" . ST_SALESORDER . "";
			DB::query($sql, "The sales order detail record could not be updated");
		}
		/**
		 * @static
		 *
		 * @param $debtorno
		 * @param $invdate
		 *
		 * @return string
		 */
		public static function	get_invoice_duedate($debtorno, $invdate) {
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
			}
			else {
				$duedate = Dates::add_days($invdate, $myrow['days_before_due']);
			}
			return $duedate;
		}
		/**
		 * @static
		 *
		 * @param $customer_id
		 *
		 * @return DB_Query_Result
		 */
		public static function get_customer($customer_id) {
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
		/**
		 * @static
		 *
		 * @param $customer_id
		 * @param $branch_id
		 *
		 * @return null|PDOStatement
		 */
		public static function get_branch($customer_id, $branch_id) {
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
		/**
		 * @static
		 *
		 * @param $order
		 *
		 * @return false|Purch_Order|Sales_Order
		 */
		public static function check_edit_conflicts($order) {
			if (!isset($_POST['order_id'])) {
				$_POST['order_id'] = $order->order_id;
			}
			$session_order = Orders::session_get();
			if ($session_order && $session_order->uniqueid != $order->uniqueid) {
				if (!$session_order->trans_no && count($session_order->line_items) > 0) {
					Errors::warning(_('You were in the middle of creating a new order, this order has been continued. If you would like to start a completely new order, push the cancel changes button at the bottom of the page'));
				}
				elseif ($session_order->trans_no) {
					Errors::warning(_('You were previously editing this order in another tab, those changes have been applied to this tab'));
				}
				return $session_order;
			}
			return $order;
		}
		/**
		 * @static
		 *
		 * @param $doc_type
		 * @param $line_id
		 * @param $qty_dispatched
		 *
		 * @return bool
		 */
		public static function update_parent_line($doc_type, $line_id, $qty_dispatched) {
			$doc_type = Debtor_Trans::get_parent_type($doc_type);
			//	echo "update line: $line_id, $doc_type, $qty_dispatched";
			if ($doc_type == 0) {
				return false;
			}
			else {
				if ($doc_type == ST_SALESORDER) {
					$sql = "UPDATE sales_order_details
								SET qty_sent = qty_sent + $qty_dispatched
								WHERE id=" . DB::escape($line_id);
				}
				else {
					$sql = "UPDATE debtor_trans_details
								SET qty_done = qty_done + $qty_dispatched
								WHERE id=" . DB::escape($line_id);
				}
			}
			DB::query($sql, "The parent document detail record could not be updated");
			return true;
		}
	}

	/* end of class defintion */
?>
