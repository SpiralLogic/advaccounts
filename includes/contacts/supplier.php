<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 4:12 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Contacts_Supplier extends Contacts_Company {

		public $id, $supplier_id; //
		public $name, $supp_name; //
		public $tax_id, $gst_no; //
		public $contact_name, $contact; //
		public $post_address, $supp_address; //
		/*
								 public $pymt_discount = '0';
								public $credit_limit = 0;
								public $dimension_id = 0;
								public $dimension2_id = 0;
								public $payment_terms = 1;
								public $curr_code = '';

								public $inactive = 0;
								public $notes = '';
	*/
		public $phone = "";
		public $phone2 = "";
		public $fax = "";
		public $website;
		public $email = "";
		public $account_no = '', $supp_account_no = ''; //
		public $bank_account;
		public $tax_group_id = '';
		public $purchase_account;
		public $payable_account;
		public $payment_discount_account;
		public $supp_ref = '';

		function __construct($id = null) {
			$this->supplier_id = &$this->id;
			$this->supp_name =& $this->name;
			$this->gst_no = &$this->tax_id;
			$this->contact = &$this->contact_name;
			$this->supp_address = &$this->post_address;
			$this->supp_account_no = &$this->account_no;
			parent::__construct($id);
			$this->supp_ref = substr($this->name, 0, 29);
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
			$this->credit_limit = price_format(0);
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
			$sql = "SELECT * FROM suppliers WHERE supplier_id = " . DBOld::escape($id);
			$result = DBOld::query($sql, "check failed");
			if (DBOld::num_rows($result) != 1) {
				$this->_status(false, 'read', "Supplier could not be found!");
				return false;
			}
			$result = DBOld::fetch_assoc($result);
			$this->setFromArray($result);
			$this->credit_limit = price_format($this->credit_limit);
			return $this->id;
		}

		protected function _saveNew() {
			DBOld::begin_transaction();
			$sql = "INSERT INTO suppliers (supp_name, supp_ref, address, supp_address, phone, phone2, fax, gst_no, email, website,
				contact, supp_account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
				payment_terms, payable_account, purchase_account, payment_discount_account, notes, tax_group_id)
				VALUES (" . DBOld::escape($this->name) . ", " . DBOld::escape($this->supp_ref) . ", " . DBOld::escape($this->address) . ", " . DBOld::escape($this->post_address) . ", " . DBOld::escape($this->phone) . ", " . DBOld::escape($this->phone2) . ", " . DBOld::escape($this->fax) . ", " . DBOld::escape($this->tax_id) . ", " . DBOld::escape($this->email) . ", " . DBOld::escape($this->website) . ", " . DBOld::escape($this->contact_name) . ", " . DBOld::escape($this->account_no) . ", " . DBOld::escape($this->bank_account) . ", " . DBOld::escape($this->credit_limit) . ", " . DBOld::escape($this->dimension_id) . ", " . DBOld::escape($this->dimension2_id) . ", " . DBOld::escape($this->curr_code) . ", " . DBOld::escape($this->payment_terms) . ", " . DBOld::escape($this->payable_account) . ", " . DBOld::escape($this->purchase_account) . ", " . DBOld::escape($this->payment_discount_account) . ", " . DBOld::escape($this->notes) . ", " . DBOld::escape($this->tax_group_id) . ")";
			DBOld::query($sql, "The supplier could not be added");
			$this->id = DBOld::insert_id();
			DBOld::commit_transaction();
			$this->_status(true, 'Saving', "A Supplier has been added.");
		}

		protected function delete() {
			// TODO: Implement delete() method.
		}

		function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ($this->id == 0) {
				$this->_saveNew();
			}
			DBOld::begin_transaction();
			$sql = "UPDATE suppliers SET name=" . DBOld::escape($this->name) . ",
			supp_ref=" . DBOld::escape(substr($this->name, 0, 29)) . ",
			address=" . DBOld::escape($this->address) . ",
			supp_account_no=" . DBOld::escape($this->account_no) . ",
			tax_id=" . DBOld::escape($this->tax_id) . ",
			bank_account=" . DBOld::escape($this->bank_account) . ",
			purchase_account=" . DBOld::escape($this->purchase_account) . ",
			payable_account=" . DBOld::escape($this->payable_account) . ",
			payment_discount_account=" . DBOld::escape($this->payment_discount_account) . ",
			curr_code=" . DBOld::escape($this->curr_code) . ",
			email=" . DBOld::escape($this->email) . ",
			website=" . DBOld::escape($this->website) . ",
			fax=" . DBOld::escape($this->fax) . ",
			phone=" . DBOld::escape($this->phone) . ",
			phone2=" . DBOld::escape($this->phone2) . ",
			inactive=" . DBOld::escape($this->inactive) . ",
			dimension_id=" . DBOld::escape($this->dimension_id) . ",
			dimension2_id=" . DBOld::escape($this->dimension2_id) . ",
            credit_status=" . DBOld::escape($this->credit_status) . ",
            payment_terms=" . DBOld::escape($this->payment_terms) . ",
            pymt_discount=" . user_numeric($this->pymt_discount) / 100 . ",
            credit_limit=" . user_numeric($this->credit_limit) . ",
            notes=" . DBOld::escape($this->notes) . "
            WHERE debtor_no = " . DBOld::escape($this->id);
			DBOld::query($sql, "The supplier could not be updated");
			DBOld::commit_transaction();
			return $this->_status(true, 'Processing', "Supplier has been updated.");
		}

		public static function search($terms) {
			$sql = "SELECT supplier_id as id, supp_ref as label, supp_ref as value FROM suppliers " . "where supp_ref LIKE '%" . $terms . "%' LIMIT 20";
			$result = DBOld::query($sql, 'Couldn\'t Get Supplier');
			$data = '';
			while ($row = DBOld::fetch_assoc($result)) {
				foreach ($row as &$value) {
					$value = htmlspecialchars_decode($value);
				}
				$data[] = $row;
			}
			return $data;
		}

		public function getEmailAddresses() {
			return array('Accounts' => array($this->id => array($this->name, $this->email)));
		}
	}
