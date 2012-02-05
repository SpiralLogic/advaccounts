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
	/* Definition of the Supplier Transactions class to hold all the information for an accounts payable invoice or credit note
 */
	class Creditor_Trans
	{
		static protected $_instance = null;
/***
 * @static
 * @param bool $reset_session
 * @return Creditor_Trans
 */
		static public function i($reset_session = false) {
			if (!$reset_session && isset($_SESSION["Creditor_Trans"])) {
				static::$_instance = $_SESSION["Creditor_Trans"];
			}
			elseif (static::$_instance === null) {
				static::$_instance = $_SESSION["Creditor_Trans"] = new static;
			}
			return static::$_instance;
		}

		static public function killInstance() {
			unset($_SESSION["Creditor_Trans"]);
		}

		public $grn_items; /*array of objects of class GRNDetails using the GRN No as the pointer */
		public $gl_codes; /*array of objects of class gl_codes using a counter as the pointer */
		public $supplier_id;
		public $supplier_name;
		public $terms_description;
		public $terms;
		public $tax_description;
		public $tax_group_id;
		public $is_invoice;
		public $Comments;
		public $tran_date;
		public $due_date;
		public $supp_reference;
		public $reference;
		public $ov_amount;
		public $ov_discount;
		public $tax_correction = 0;
		public $total_correction = 0;
		public $gl_codes_counter = 0;

		public function __construct() {
			/*Constructor function initialises a new Supplier Transaction object */
			$this->grn_items = array();
			$this->gl_codes = array();
		}

		public function add_grn_to_trans($grn_item_id, $po_detail_item, $item_code, $description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, $Complete, $std_cost_unit, $gl_code, $discount = 0, $exp_price = null) {
			$this->grn_items[$grn_item_id] = new Purch_GLItem($grn_item_id, $po_detail_item, $item_code, $description, $qty_recd, $prev_quantity_inv, $this_quantity_inv, $order_price, $chg_price, $Complete, $std_cost_unit, $gl_code, $discount, $exp_price);
			return 1;
		}

		public function add_gl_codes_to_trans($gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_) {
			$this->gl_codes[$this->gl_codes_counter] = new Purch_GLCode($this->gl_codes_counter, $gl_code, $gl_act_name, $gl_dim, $gl_dim2, $amount, $memo_);
			$this->gl_codes_counter++;
			return 1;
		}

		public function remove_grn_from_trans($grn_item_id) {
			unset($this->grn_items[$grn_item_id]);
		}

		public function remove_gl_codes_from_trans(&$gl_code_counter) {
			unset($this->gl_codes[$gl_code_counter]);
		}

		public function is_valid_trans_to_post() {
			return (count($this->grn_items) > 0 || count($this->gl_codes) > 0 || ($this->ov_amount != 0) || ($this->ov_discount > 0));
		}

		public function clear_items() {
			unset($this->grn_items);
			unset($this->gl_codes);
			$this->ov_amount = $this->ov_discount = $this->supplier_id = $this->tax_correction = $this->total_correction = 0;
			$this->grn_items = array();
			$this->gl_codes = array();
		}

		public function get_taxes($tax_group_id = null, $shipping_cost = 0, $gl_codes = true) {
			$items = array();
			$prices = array();
			if ($tax_group_id == null) {
				$tax_group_id = $this->tax_group_id;
			}
			$tax_group = Tax_Groups::get_items_as_array($tax_group_id);
			foreach ($this->grn_items as $line) {
				$items[] = $line->item_code;
				$prices[] = round(($line->this_quantity_inv * $line->taxfree_charge_price($tax_group_id, $tax_group)), User::price_dec(), PHP_ROUND_HALF_EVEN);
			}
			if ($tax_group_id == null) {
				$tax_group_id = $this->tax_group_id;
			}
			$taxes = Tax::for_items($items, $prices, $shipping_cost, $tax_group_id);
			///////////////// Joe Hunt 2009.08.18
			if ($gl_codes) {
				foreach ($this->gl_codes as $gl_code) {
					$index = Tax::is_account($gl_code->gl_code);
					if ($index !== false) {
						$taxes[$index]['Value'] += $gl_code->amount;
					}
				}
			}
			////////////////
			return $taxes;
		}

		public function get_total_charged($tax_group_id = null) {
			$total = 0;
			// preload the taxgroup !
			if ($tax_group_id != null) {
				$tax_group = Tax_Groups::get_items_as_array($tax_group_id);
			}
			else {
				$tax_group = null;
			}
			foreach ($this->grn_items as $line) {
				$total += round(($line->this_quantity_inv * $line->taxfree_charge_price($tax_group_id, $tax_group)), User::price_dec(), PHP_ROUND_HALF_EVEN);
			}
			foreach ($this->gl_codes as $gl_line) { //////// 2009-08-18 Joe Hunt
				if (!Tax::is_account($gl_line->gl_code)) {
					$total += $gl_line->amount;
				}
			}
			return $total;
		}

		static public function add($type, $supplier_id, $date_, $due_date, $reference, $supp_reference, $amount, $amount_tax, $discount, $err_msg = "", $rate = 0) {
			$date = Dates::date2sql($date_);
			if ($due_date == "") {
				$due_date = "0000-00-00";
			}
			else {
				$due_date = Dates::date2sql($due_date);
			}
			$trans_no = SysTypes::get_next_trans_no($type);
			$curr = Bank_Currency::for_creditor($supplier_id);
			if ($rate == 0) {
				$rate = Bank_Currency::exchange_rate_from_home($curr, $date_);
			}
			$sql
			 = "INSERT INTO creditor_trans (trans_no, type, supplier_id, tran_date, due_date,
				reference, supp_reference, ov_amount, ov_gst, rate, ov_discount) ";
			$sql .= "VALUES (" . DB::escape($trans_no) . ", " . DB::escape($type) . ", " . DB::escape($supplier_id) . ", '$date', '$due_date',
				" . DB::escape($reference) . ", " . DB::escape($supp_reference) . ", " . DB::escape($amount) . ", " . DB::escape($amount_tax) . ", " . DB::escape($rate) . ", " . DB::escape($discount) . ")";
			if ($err_msg == "") {
				$err_msg = "Cannot insert a supplier transaction record";
			}
			DB::query($sql, $err_msg);
			DB_AuditTrail::add($type, $trans_no, $date_);
			return $trans_no;
		}

		static public function get($trans_no, $trans_type = -1) {

			$sql
			 = "SELECT creditor_trans.*, (creditor_trans.ov_amount+creditor_trans.ov_gst+creditor_trans.ov_discount) AS Total,
				suppliers.supp_name AS supplier_name, suppliers.curr_code AS SupplierCurrCode ";
			if ($trans_type == ST_SUPPAYMENT) {
				// it's a payment so also get the bank account
				$sql
				 .= ", bank_accounts.bank_name, bank_accounts.bank_account_name, bank_accounts.bank_curr_code,
					bank_accounts.account_type AS BankTransType, bank_trans.amount AS BankAmount,
					bank_trans.ref ";
			}
			$sql .= " FROM creditor_trans, suppliers ";
			if ($trans_type == ST_SUPPAYMENT) {
				// it's a payment so also get the bank account
				$sql .= ", bank_trans, bank_accounts";
			}
			$sql .= " WHERE creditor_trans.trans_no=" . DB::escape($trans_no) . "
				AND creditor_trans.supplier_id=suppliers.supplier_id";
			if ($trans_type > 0) {
				$sql .= " AND creditor_trans.type=" . DB::escape($trans_type);
			}
			if ($trans_type == ST_SUPPAYMENT) {
				// it's a payment so also get the bank account
				$sql .= " AND bank_trans.trans_no =" . DB::escape($trans_no) . "
					AND bank_trans.type=" . DB::escape($trans_type) . "
					AND bank_accounts.id=bank_trans.bank_act ";
			}
			$result = DB::query($sql, "Cannot retreive a supplier transaction");
			if (DB::num_rows($result) == 0) {
				// can't return nothing
				Errors::db_error("no supplier trans found for given params", $sql, true);
				exit;
			}
			if (DB::num_rows($result) > 1) {
				// can't return multiple
				Errors::db_error("duplicate supplier transactions found for given params", $sql, true);
				exit;
			}
			return DB::fetch($result);
		}

		static public function exists($type, $type_no) {
			if ($type == ST_SUPPRECEIVE) {
				return Purch_GRN::exists($type_no);
			}
			$sql = "SELECT trans_no FROM creditor_trans WHERE type=" . DB::escape($type) . "
				AND trans_no=" . DB::escape($type_no);
			$result = DB::query($sql, "Cannot retreive a supplier transaction");
			return (DB::num_rows($result) > 0);
		}

		static public function void($type, $type_no) {
			$sql
			 = "UPDATE creditor_trans SET ov_amount=0, ov_discount=0, ov_gst=0,
				alloc=0 WHERE type=" . DB::escape($type) . " AND trans_no=" . DB::escape($type_no);
			DB::query($sql, "could not void supp transactions for type=$type and trans_no=$type_no");
		}

		static public function post_void($type, $type_no) {
			if ($type == ST_SUPPAYMENT) {
				Creditor_Payment::void($type, $type_no);
				return true;
			}
			if ($type == ST_SUPPINVOICE || $type == ST_SUPPCREDIT) {
				Purch_Invoice::void($type, $type_no);
				return true;
			}
			if ($type == ST_SUPPRECEIVE) {
				return Purch_GRN::void($type_no);
			}
			return false;
		}

		// add a supplier-related gl transaction
		// $date_ is display date (non-sql)
		// $amount is in SUPPLIERS'S currency
		static public function add_gl($type, $type_no, $date_, $account, $dimension, $dimension2, $amount, $supplier_id, $err_msg = "", $rate = 0, $memo = "") {
			if ($err_msg == "") {
				$err_msg = "The supplier GL transaction could not be inserted";
			}
			return GL_Trans::add($type, $type_no, $date_, $account, $dimension, $dimension2, $memo, $amount, Bank_Currency::for_creditor($supplier_id), PT_SUPPLIER, $supplier_id, $err_msg, $rate);
		}

		static public function get_conversion_factor($supplier_id, $stock_id) {
			$sql
			 = "SELECT conversion_factor FROM purch_data
					WHERE supplier_id = " . DB::escape($supplier_id) . "
					AND stock_id = " . DB::escape($stock_id);
			$result = DB::query($sql, "The supplier pricing details for " . $stock_id . " could not be retrieved");
			if (DB::num_rows($result) == 1) {
				$myrow = DB::fetch($result);
				return $myrow['conversion_factor'];
			}
			else {
				return 1;
			}
		}

		static public function trans_tax_details($tax_items, $columns, $tax_recorded = 0) {
			$tax_total = 0;
			while ($tax_item = DB::fetch($tax_items)) {
				$tax = Num::format(abs($tax_item['amount']), User::price_dec());
				if ($tax_item['included_in_price']) {
					label_row(_("Included") . " " . $tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%) " . _("Amount") . ": $tax", "colspan=$columns class='right'", "class='right'");
				}
				else {
					label_row($tax_item['tax_type_name'] . " (" . $tax_item['rate'] . "%)", $tax, "colspan=$columns class='right'", "class='right'");
				}
				$tax_total += $tax;
			}
			if ($tax_recorded != 0) {
				$tax_correction = Num::format($tax_recorded - $tax_total, User::price_dec());
				label_row("Tax Correction ", $tax_correction, "colspan=$columns class='right'", "class='right'");
			}
		}

		static public function get_duedate_from_terms($creditor_trans) {
			if (!Dates::is_date($creditor_trans->tran_date)) {
				$creditor_trans->tran_date = Dates::Today();
			}
			if (substr($creditor_trans->terms, 0, 1) == "1") { /*Its a day in the following month when due */
				$creditor_trans->due_date = Dates::add_days(Dates::end_month($creditor_trans->tran_date), (int)substr($creditor_trans->terms, 1));
			}
			else { /*Use the Days Before Due to add to the invoice date */
				$creditor_trans->due_date = Dates::add_days($creditor_trans->tran_date, (int)substr($creditor_trans->terms, 1));
			}
		}
	} /* end of class defintion */
?>
