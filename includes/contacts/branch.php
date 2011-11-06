<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 11:52 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Contacts_Branch extends DB_abstract
	{
		public $post_address = '';
		public $branch_code = 0;
		public $br_name = "New Address";
		public $br_address = '';
		public $city = '';
		public $state = '';
		public $postcode = '';
		public $area = DEFAULT_AREA;
		public $br_post_address = "";
		public $debtor_no;
		public $branch_ref = "New";
		public $contact_name = "";
		public $default_location = DEFAULT_LOCATION;
		public $default_ship_via = DEFAULT_SHIP_VIA;
		public $disable_trans = 0;
		public $phone = '';
		public $phone2 = '';
		public $fax = '';
		public $website = '';
		public $email = '';
		public $inactive = 0;
		public $notes = '';
		public $group_no = 1;
		public $payment_discount_account;
		public $receivables_account;
		public $sales_account = "";
		public $sales_discount_account;
		public $salesman;
		public $tax_group_id = DEFAULT_TAX_GROUP;

		function __construct($id = null)
		{
			$this->branch_code = $id;
			$this->id = &$this->branch_code;
			parent::__construct($id);
			$this->name = &$this->br_name;
			$this->address = &$this->br_address;
		}

		protected function delete()
		{
			$sql = "DELETE FROM cust_branch WHERE branch_code=" . $this->branch_code;
			DB::query($sql, "cannot delete branch");
			unset($this->branch_code);
			$this->_new();
			return $this->_status(true, 'delete', "Branch deleted.");
		}

		protected function _read($params = false)
		{
			if (!$params) {
				$this->_status(false, 'Retrieving branch', 'No parameters provided');
				return false;
			}
			$this->_defaults();
			if (!is_array($params)) {
				$params = array('branch_code' => $params);
			}
			$sql = DB::select('b.*', 'a.description', 's.salesman_name', 't.name AS tax_group_name')
			 ->from('cust_branch b, debtors_master c, areas a, salesman s, tax_groups t')
			 ->where(array('b.debtor_no=c.debtor_no', 'b.tax_group_id=t.id', 'b.area=a.area_code', 'b.salesman=s.salesman_code'));
			$sql2
			 = "SELECT b.*, a.description, s.salesman_name, t.name AS tax_group_name
		FROM cust_branch b, debtors_master c, areas a, salesman s, tax_groups t
		WHERE b.debtor_no=c.debtor_no
		AND b.tax_group_id=t.id
		AND b.area=a.area_code
		AND b.salesman=s.salesman_code";
			foreach ($params as $key => $value) {
				$sql->where("b.$key=", $value);
			}
			DB::fetch()->intoClass($this);
			return $this->branch_code;
			/*
		 if (!DB::fetch()->intoClass($this)) {
			 $this->_status(false, 'Retrieving  Branch', 'No results from query');
			 return false;
		 }*/
			//return $this->branch_code;
		}

		public function getAddress()
		{
			$address = $this->br_address . "\n";
			if ($this->city) {
				$address .= $this->city;
			}
			if ($this->state) {
				$address .= ", " . strtoupper($this->state);
			}
			if ($this->postcode) {
				$address .= ", " . $this->postcode;
			}
			return $address;
		}

		protected function _canProcess()
		{
			return true;
		}

		public function save($changes = null)
		{
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if ((int)$this->branch_code == 0) {
				$this->_saveNew();
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if (!empty($this->city)) {
				$this->br_name = $this->city . " " . strtoupper($this->state);
			}
			if ($this->branch_ref != 'accounts') {
				$this->branch_ref = substr($this->br_name, 0, 30);
			}
			DB::begin_transaction();
			$sql
			 = "UPDATE cust_branch SET
			br_name=" . DB::escape($this->name) . ",
			br_address=" . DB::escape($this->address) . ",
			city=" . DB::escape($this->city) . ",
			state=" . DB::escape($this->state) . ",
			postcode=" . DB::escape($this->postcode) . ",
			br_post_address=" . DB::escape($this->post_address) . ",
			area=" . DB::escape($this->area) . ",
			salesman=" . DB::escape($this->salesman) . ",
			phone=" . DB::escape($this->phone) . ",
			phone2=" . DB::escape($this->phone2) . ",
			fax=" . DB::escape($this->fax) . ",
			contact_name=" . DB::escape($this->contact_name) . ",
			email=" . DB::escape($this->email) . ",
			default_location=" . DB::escape($this->default_location) . ",
			tax_group_id=" . DB::escape($this->tax_group_id) . ",
			sales_account=" . DB::escape($this->sales_account) . ",
			sales_discount_account=" . DB::escape($this->sales_discount_account) . ",
			receivables_account=" . DB::escape($this->receivables_account) . ",
			payment_discount_account=" . DB::escape($this->payment_discount_account) . ",
			default_ship_via=" . DB::escape($this->default_ship_via) . ",
			disable_trans=" . DB::escape($this->disable_trans) . ",
            group_no=" . DB::escape($this->group_no) . ",
            notes=" . DB::escape($this->notes) . ",
            inactive=" . DB::escape($this->inactive) . ",
            branch_ref=" . DB::escape($this->branch_ref) . "
              WHERE branch_code =" . DB::escape($this->branch_code) . "
    	        AND debtor_no=" . DB::escape($this->debtor_no);
			DB::query($sql, "The customer could not be updated");
			DB::commit_transaction();
			return $this->_status(true, 'Processing', "Branch has been updated.");
		}

		protected function _saveNew()
		{
			DB::begin_transaction();
			$sql
			 = "INSERT INTO cust_branch (debtor_no, br_name, branch_ref, br_address, city, state, postcode,
				salesman, phone, phone2, fax, contact_name, area, email, tax_group_id, sales_account, sales_discount_account, receivables_account, payment_discount_account, default_location,
				br_post_address, disable_trans, group_no, default_ship_via, notes,inactive)
				VALUES (" . DB::escape($this->debtor_no) . "," . DB::escape($this->name) . ", " . DB::escape($this->branch_ref) . ", " . DB::escape($this->address) . ", " . DB::escape($this->city) . ", " . DB::escape($this->state) . ", " . DB::escape($this->postcode) . ", " . DB::escape($this->salesman)
			 . ", " . DB::escape($this->phone) . ", " . DB::escape($this->phone2) . ", " . DB::escape($this->fax) . "," . DB::escape($this->contact_name) . ", " . DB::escape($this->area) . ", " . DB::escape($this->email) . ", " . DB::escape($this->tax_group_id) . ", " . DB::escape($this->sales_account)
			 . ", " . DB::escape($this->sales_discount_account) . ", " . DB::escape($this->receivables_account) . ", " . DB::escape($this->payment_discount_account) . ", " . DB::escape($this->default_location) . ", " . DB::escape($this->br_post_address) . "," . DB::escape($this->disable_trans) . ", "
			 . DB::escape($this->group_no) . ", " . DB::escape($this->default_ship_via) . ", " . DB::escape($this->notes) . ", " . DB::escape($this->inactive) . ")";
			DB::query($sql, "The branch could not be added");
			$this->branch_code = DB::insert_id();
			DB::commit_transaction();
			$this->_status(true, 'Saving', "New branch has been added");
		}

		protected function _defaults()
		{
			$company_record = DB_Company::get_prefs();
			$this->branch_code = 0;
			$this->sales_discount_account = $company_record['default_sales_discount_act'];
			$this->receivables_account = $company_record['debtors_act'];
			$this->payment_discount_account = $company_record['default_prompt_payment_act'];
			$this->salesman = ($_SESSION['current_user']) ? $_SESSION['current_user']->salesmanid : 1;
		}

		protected function _new()
		{
			$this->_defaults();
			return $this->_status(true, 'Initialize new Branch', 'Now working with a new Branch');
		}

		protected function _countTransactions()
		{
		}
	}


