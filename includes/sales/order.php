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
			}
			else {
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
					read_sales_order($trans_no[0], $this, $type);
					if ($view) { // prepare for DN/IV entry
						for ($line_no = 0; $line_no < count($this->line_items); $line_no++) {
							$line = &$this->line_items[$line_no];
							$line->src_id = $line->id; // save src line ids for update
							$line->qty_dispatched = $line->quantity - $line->qty_done;
						}
					}
				}
				else { // other type of sales transaction
					read_sales_trans($type, $trans_no, $this);
					if ($this->order_no) { // free hand credit notes have no order_no
						$sodata = get_sales_order_header($this->order_no, ST_SALESORDER);
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
							$srcdetails = get_sales_order_details($this->order_no, ST_SALESORDER);
						}
						else { // get src_data from debtor_trans
							$this->src_docs = Sales_Trans::get_version($src_type, Sales_Trans::get_parent($type, $trans_no[0]));
							$srcdetails = get_customer_trans_details($src_type, array_keys($this->src_docs));
						}
						// calculate & save: qtys on other docs and free qtys on src doc
						for ($line_no = 0; $srcline = DBOld::fetch($srcdetails); $line_no++) {
							$sign = 1; // $type==13 ?  1 : -1; // this is strange debtor_trans atavism
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
						}
						else {
							$cust = get_customer($this->customer_id);
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
					$pos = get_sales_point($this->pos);
					$this->cash = !$pos['credit_sale'];
					if (!$pos['cash_sale'] || !$pos['credit_sale'] || $this->due_date == Dates::Today()) {
						$this->pos = -1;
					} // mark not editable payment type
					else
					{
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
			$this->credit = get_current_cust_credit($this->customer_id);
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
				return write_sales_invoice($this);
			case ST_CUSTCREDIT:
				return write_credit_note($this, $policy);
			case ST_CUSTDELIVERY:
				return write_sales_delivery($this, $policy);
			case ST_SALESORDER:
			case ST_SALESQUOTE:
				$_SESSION['Jobsboard'] = clone($this);
				if ($this->trans_no == 0) // new document
				{
					return add_sales_order($this);
				}
				else {
					return update_sales_order($this);
				}
			}
		}

		function check_cust_ref($cust_ref)
		{
			if (!is_int($this->trans_type)) {
				return false;
			}
			$sql = "SELECT customer_ref,type FROM sales_orders WHERE debtor_no=" . DB::escape($this->customer_id) . " AND customer_ref=" . DB::escape($cust_ref) . " AND type != " . $this->trans_type;
			$result = DBOld::query($sql);
			return (DBOld::num_rows($result) > 0) ? false : true;
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
				$this->credit = get_current_cust_credit($customer_id);
			}
		}

		function set_branch($branch_id, $tax_group_id, $tax_group_name, $phone = '', $email = '', $name = '')
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
				$query = DBOld::query($sql, 'Couldn\'t find current salesman');
				$result = DBOld::fetch_assoc($query);
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
			}
			else {
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
			}
			else {
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
			}
			else {
				return round($this->freight_cost * $tax_rate / 100, User::price_dec());
			}
		}

		function store()
		{
			$serial = serialize($this);
			$sql = "DELETE FROM `user_class_store` WHERE `user_id`=" . $_SESSION['current_user']->user;
			DBOld::query($sql);
			$sql = "INSERT INTO `user_class_store` (`user_id`, `data`) VALUE (" . $_SESSION['current_user']->user . ",'" . $serial . "')";
			DBOld::query($sql);
		}

		static function restore()
		{
			$sql = "SELECT `data` FROM  `user_class_store` WHERE `user_id`=" . $_SESSION['current_user']->user;
			$result = DBOld::query($sql);
			$serial = DBOld::fetch_assoc($result);
			$serial = $serial['data'];
			return unserialize($serial);
		}
	} /* end of class defintion */
?>
