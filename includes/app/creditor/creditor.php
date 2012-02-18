<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 4:12 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Creditor extends Contact_Company
	{
		static public function search($terms) {
			$sql = "SELECT supplier_id as id, supp_ref as label, supp_ref as value FROM suppliers where supp_ref LIKE '%" . $terms . "%' LIMIT 20";
			$result = DB::query($sql, 'Couldn\'t Get Supplier');
			$data = '';
			while ($row = DB::fetch_assoc($result)) {
				foreach ($row as &$value) {
					$value = htmlspecialchars_decode($value);
				}
				$data[] = $row;
			}
			return $data;
		}
		public $id, $supplier_id; //
		public $name, $supp_name; //
		public $tax_id, $gst_no; //
		public $contact_name, $contact; //
		public $post_address, $supp_address; //
		public $phone = "";
		public $phone2 = "";
		public $fax = "";
		public $notes = "";
		public $inactive = 0;
		public $website;
		public $email = "";
		public $account_no = '', $supp_account_no = ''; //
		public $bank_account;
		public $tax_group_id = '';
		public $purchase_account;
		public $payable_account;
		public $payment_discount_account;
		public $supp_ref = '';
		public function __construct($id = null) {
			$this->supplier_id = &$this->id;
			$this->supp_name =& $this->name;
			$this->gst_no = &$this->tax_id;
			$this->contact = &$this->contact_name;
			$this->supp_address = &$this->post_address;
			$this->supp_account_no = &$this->account_no;
			parent::__construct($id);
			$this->supp_ref = substr($this->name, 0, 29);
		}
		public function getEmailAddresses() {
			return array('Accounts' => array($this->id => array($this->name, $this->email)));
		}
		public function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ($this->id == 0) {
				$this->_saveNew();
			}
			$sql = "UPDATE suppliers SET name=" . DB::escape($this->name) . ",
							supp_ref=" . DB::escape(substr($this->name, 0, 29)) . ",
							address=" . DB::escape($this->address) . ",
							supp_account_no=" . DB::escape($this->account_no) . ",
							tax_id=" . DB::escape($this->tax_id) . ",
							bank_account=" . DB::escape($this->bank_account) . ",
							purchase_account=" . DB::escape($this->purchase_account) . ",
							payable_account=" . DB::escape($this->payable_account) . ",
							payment_discount_account=" . DB::escape($this->payment_discount_account) . ",
							curr_code=" . DB::escape($this->curr_code) . ",
							email=" . DB::escape($this->email) . ",
							website=" . DB::escape($this->website) . ",
							fax=" . DB::escape($this->fax) . ",
							phone=" . DB::escape($this->phone) . ",
							phone2=" . DB::escape($this->phone2) . ",
							inactive=" . DB::escape($this->inactive) . ",
							dimension_id=" . DB::escape($this->dimension_id) . ",
							dimension2_id=" . DB::escape($this->dimension2_id) . ",
				 credit_status=" . DB::escape($this->credit_status) . ",
				 payment_terms=" . DB::escape($this->payment_terms) . ",
				 pymt_discount=" . User::numeric($this->pymt_discount) / 100 . ",
				 credit_limit=" . User::numeric($this->credit_limit) . ",
				 notes=" . DB::escape($this->notes) . "
				 WHERE debtor_no = " . DB::escape($this->id);
			DB::query($sql, "The supplier could not be updated");
			return $this->_status(true, 'Processing', "Supplier has been updated.");
		}
		public function delete() {
			// TODO: Implement delete() method.
		}
		protected function _canProcess() {
			if (empty($this->name)) {
				$this->_status(false, 'Processing', "The supplier name cannot be empty.", 'name');
				return false;
			}
			return true;
		}
		protected function _countTransactions() {
			// TODO: Implement _countTransactions() method.
		}
		protected function _defaults() {
			$this->credit_limit = Num::price_format(0);
			$company_record = DB_Company::get_prefs();
			$this->curr_code = $company_record["curr_default"];
			$this->payable_account = $company_record["creditors_act"];
			$this->purchase_account = $company_record["default_cogs_act"];
			$this->payment_discount_account = $company_record['pyt_discount_act'];
		}
		protected function _new() {
			$this->_defaults();
			return $this->_status(true, 'Initialize new supplier', 'Now working with a new supplier');
		}
		protected function _read($id = null) {
			if ($id == null || empty($id)) {
				return $this->_status(false, 'read', 'No supplier ID to read');
			}
			$this->_defaults();
			$this->id = $id;
			$sql = "SELECT * FROM suppliers WHERE supplier_id = " . DB::escape($id);
			$result = DB::query($sql, "check failed");
			if (DB::num_rows($result) != 1) {
				$this->_status(false, 'read', "Supplier could not be found!");
				return false;
			}
			$result = DB::fetch_assoc($result);
			$this->setFromArray($result);
			$this->credit_limit = Num::price_format($this->credit_limit);
			return $this->id;
		}
		protected function _saveNew() {
			$sql = "INSERT INTO suppliers (supp_name, supp_ref, address, supp_address, phone, phone2, fax, gst_no, email, website,
				contact, supp_account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
				payment_terms, payable_account, purchase_account, payment_discount_account, notes, tax_group_id)
				VALUES (" . DB::escape($this->name) . ", " . DB::escape($this->supp_ref) . ", " . DB::escape($this->address) . ", " . DB::escape($this->post_address) . ", " . DB::escape($this->phone) . ", " . DB::escape($this->phone2) . ", " . DB::escape($this->fax) . ", " . DB::escape($this->tax_id) . ", " . DB::escape($this->email) . ", " . DB::escape($this->website) . ", " . DB::escape($this->contact_name) . ", " . DB::escape($this->account_no) . ", " . DB::escape($this->bank_account) . ", " . DB::escape($this->credit_limit) . ", " . DB::escape($this->dimension_id) . ", " . DB::escape($this->dimension2_id) . ", " . DB::escape($this->curr_code) . ", " . DB::escape($this->payment_terms) . ", " . DB::escape($this->payable_account) . ", " . DB::escape($this->purchase_account) . ", " . DB::escape($this->payment_discount_account) . ", " . DB::escape($this->notes) . ", " . DB::escape($this->tax_group_id) . ")";
			DB::query($sql, "The supplier could not be added");
			$this->id = DB::insert_id();
			DB::commit();
			$this->_status(true, 'Saving', "A Supplier has been added.");
		}
		static public function get_to_trans($supplier_id, $to = null) {
			if ($to == null) {
				$todate = date("Y-m-d");
			}
			else {
				$todate = Dates::date2sql($to);
			}
			$past1 = DB_Company::get_pref('past_due_days');
			$past2 = 2 * $past1;
			// removed - creditor_trans.alloc from all summations
			$value = "(creditor_trans.ov_amount + creditor_trans.ov_gst + creditor_trans.ov_discount)";
			$due = "IF (creditor_trans.type=" . ST_SUPPINVOICE . " OR creditor_trans.type=" . ST_SUPPCREDIT . ",creditor_trans.due_date,creditor_trans.tran_date)";
			$sql = "SELECT suppliers.supp_name, suppliers.curr_code, payment_terms.terms,

		Sum($value) AS Balance,

		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past2,$value,0)) AS Overdue2

		FROM suppliers,
			 payment_terms,
			 creditor_trans

		WHERE
			 suppliers.payment_terms = payment_terms.terms_indicator
			 AND suppliers.supplier_id = $supplier_id
			 AND creditor_trans.tran_date <= '$todate'
			 AND suppliers.supplier_id = creditor_trans.supplier_id

		GROUP BY
			 suppliers.supp_name,
			 payment_terms.terms,
			 payment_terms.days_before_due,
			 payment_terms.day_in_following_month";
			$result = DB::query($sql, "The customer details could not be retrieved");
			if (DB::num_rows($result) == 0) {
				/*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */
				$nil_balance = true;
				$sql = "SELECT suppliers.supp_name, suppliers.curr_code, suppliers.supplier_id, payment_terms.terms
			FROM suppliers,
				 payment_terms
			WHERE
				 suppliers.payment_terms = payment_terms.terms_indicator
				 AND suppliers.supplier_id = " . DB::escape($supplier_id);
				$result = DB::query($sql, "The customer details could not be retrieved");
			}
			else {
				$nil_balance = false;
			}
			$supp = DB::fetch($result);
			if ($nil_balance == true) {
				$supp["Balance"] = 0;
				$supp["Due"] = 0;
				$supp["Overdue1"] = 0;
				$supp["Overdue2"] = 0;
			}
			return $supp;
		}
		/**
		 *	 Get how much we owe the supplier for the period
		 *
		 * @param $supplier_id
		 * @param $date_from
		 * @param $date_to
		 *
		 * @return mixed
		 */
		static public function get_oweing($supplier_id, $date_from, $date_to) {
			$date_from = Dates::date2sql($date_from);
			$date_to = Dates::date2sql($date_to);
			// Sherifoz 22.06.03 Also get the description
			$sql = "SELECT


 	SUM((trans.ov_amount + trans.ov_gst + trans.ov_discount)) AS Total


 	FROM creditor_trans as trans
 	WHERE trans.ov_amount != 0
		AND trans . tran_date >= '$date_from'
		AND trans . tran_date <= '$date_to'
		AND trans.supplier_id = " . DB::escape($supplier_id) . "
		AND trans.type = " . ST_SUPPINVOICE;
			$result = DB::query($sql);
			$results = DB::fetch($result);
			return $results['Total'];
		}
		static public function get($supplier_id) {
			$sql = "SELECT * FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);
			$result = DB::query($sql, "could not get supplier");
			return DB::fetch($result);
		}
		static public function get_name($supplier_id) {
			$sql = "SELECT supp_name AS name FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);
			$result = DB::query($sql, "could not get supplier");
			$row = DB::fetch_row($result);
			return $row[0];
		}
		static public function get_accounts_name($supplier_id) {
			$sql = "SELECT payable_account,purchase_account,payment_discount_account FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);
			$result = DB::query($sql, "could not get supplier");
			return DB::fetch($result);
		}
		static public function select($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $all = false, $editkey = false) {
			$sql = "SELECT supplier_id, supp_ref, curr_code, inactive FROM suppliers ";
			$mode = DB_Company::get_pref('no_supplier_list');
			if ($editkey) {
				Display::set_editor('supplier', $name, $editkey);
			}
			return select_box($name, $selected_id, $sql, 'supplier_id', 'supp_name', array(
																																										'format' => '_format_add_curr',
																																										'order' => array('supp_ref'),
																																										'search_box' => $mode != 0,
																																										'type' => 1,
																																										'spec_option' => $spec_option === true ? _("All Suppliers") : $spec_option,
																																										'spec_id' => ALL_TEXT,
																																										'select_submit' => $submit_on_change,
																																										'async' => false,
																																										'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') :
																																										 _('Select supplier'),
																																										'show_inactive' => $all
																																							 ));
		}
		static public function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false) {
			if ($label != null) {
				echo "<td class='label'>$label</td><td>\n";
			}
			echo Creditor::select($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
			echo "</td>\n";
		}
		static public function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false) {
			echo "<tr><td class='label' name='supplier_name'>$label</td><td>";
			echo Creditor::select($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
			echo "</td></tr>\n";
		}
	}
