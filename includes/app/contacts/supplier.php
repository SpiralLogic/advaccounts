<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 4:12 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Contacts_Supplier extends Contacts_Company {

		public static function search($terms) {
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

		protected function delete() {
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
			$sql
			 = "INSERT INTO suppliers (supp_name, supp_ref, address, supp_address, phone, phone2, fax, gst_no, email, website,
				contact, supp_account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
				payment_terms, payable_account, purchase_account, payment_discount_account, notes, tax_group_id)
				VALUES (" . DB::escape($this->name) . ", " . DB::escape($this->supp_ref) . ", " . DB::escape($this->address) . ", " . DB::escape($this->post_address) . ", " . DB::escape($this->phone) . ", " . DB::escape($this->phone2) . ", " . DB::escape($this->fax) . ", " . DB::escape($this->tax_id) . ", "
			 . DB::escape($this->email) . ", " . DB::escape($this->website) . ", " . DB::escape($this->contact_name) . ", " . DB::escape($this->account_no) . ", " . DB::escape($this->bank_account) . ", " . DB::escape($this->credit_limit) . ", " . DB::escape($this->dimension_id) . ", "
			 . DB::escape($this->dimension2_id) . ", " . DB::escape($this->curr_code) . ", " . DB::escape($this->payment_terms) . ", " . DB::escape($this->payable_account) . ", " . DB::escape($this->purchase_account) . ", " . DB::escape($this->payment_discount_account) . ", " . DB::escape($this->notes)
			 . ", " . DB::escape($this->tax_group_id) . ")";
			DB::query($sql, "The supplier could not be added");
			$this->id = DB::insert_id();
			DB::commit();
			$this->_status(true, 'Saving', "A Supplier has been added.");
		}
	}
