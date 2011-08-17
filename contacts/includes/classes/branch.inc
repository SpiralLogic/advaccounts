<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 11:52 PM
 * To change this template use File | Settings | File Templates.
 */
  class Branch extends DB_abstract {

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

	 function __construct($id = null) {
		parent::__construct($id);
		$this->id = &$this->branch_code;
		$this->name = &$this->br_name;
		$this->address = &$this->br_address;
	 }

	 protected function delete() {
		$sql = "DELETE FROM cust_branch WHERE branch_code=" . $this->branch_code;
		db_query($sql, "cannot delete branch");
		unset($this->branch_code);
		$this->_new();
		return $this->_status(true, 'delete', "Branch deleted.");
	 }

	 protected function _read($params = false) {
		if (!$params) {
		  $this->_status(false, 'Retrieving branch', 'No parameters provided');
		  return false;
		}
		$this->_defaults();
		if (!is_array($params)) {
		  $params = array('branch_code' => $params);
		}
		$sql = "SELECT " . "b.*, a.description, s.salesman_name, t.name AS tax_group_name
		FROM cust_branch b, debtors_master c, areas a, salesman s, tax_groups t
		WHERE b.debtor_no=c.debtor_no
		AND b.tax_group_id=t.id
		AND b.area=a.area_code
		AND b.salesman=s.salesman_code";
		foreach ($params as $key => $value) {
		  $sql .= " AND b.$key =" . db_escape($value);
		}
		$result = db_query($sql, 'Could not get customer branch.');
		if (db_num_rows($result) < 1) {
		  $this->_status(false, 'Retrieving  Branch', 'No results from query');
		  return false;
		}
		$result = db_fetch_assoc($result);
		$this->setFromArray($result);
		return $this->branch_code;
	 }

	 public function getAddress() {
		$address = $this->br_address . "\n";
		if ($this->city) $address .= $this->city;
		if ($this->state) $address .= ", " . strtoupper($this->state);
		if ($this->postcode) $address .= ", " . $this->postcode;
		return $address;
	 }

	 protected function _canProcess() {
		return true;
	 }

	 public function save($changes = null) {

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

		begin_transaction();
		$sql = "UPDATE cust_branch SET
			br_name=" . db_escape($this->name) . ",
			br_address=" . db_escape($this->address) . ",
			city=" . db_escape($this->city) . ",
			state=" . db_escape($this->state) . ",
			postcode=" . db_escape($this->postcode) . ",
			br_post_address=" . db_escape($this->post_address) . ",
			area=" . db_escape($this->area) . ",
			salesman=" . db_escape($this->salesman) . ",
			phone=" . db_escape($this->phone) . ",
			phone2=" . db_escape($this->phone2) . ",
			fax=" . db_escape($this->fax) . ",
			contact_name=" . db_escape($this->contact_name) . ",
			email=" . db_escape($this->email) . ",
			default_location=" . db_escape($this->default_location) . ",
			tax_group_id=" . db_escape($this->tax_group_id) . ",
			sales_account=" . db_escape($this->sales_account) . ",
			sales_discount_account=" . db_escape($this->sales_discount_account) . ",
			receivables_account=" . db_escape($this->receivables_account) . ",
			payment_discount_account=" . db_escape($this->payment_discount_account) . ",
			default_ship_via=" . db_escape($this->default_ship_via) . ",
			disable_trans=" . db_escape($this->disable_trans) . ",
            group_no=" . db_escape($this->group_no) . ",
            notes=" . db_escape($this->notes) . ",
            inactive=" . db_escape($this->inactive) . ",
            branch_ref=" . db_escape($this->branch_ref) . "
              WHERE branch_code =" . db_escape($this->branch_code) . "
    	        AND debtor_no=" . db_escape($this->debtor_no);
		db_query($sql, "The customer could not be updated");
		commit_transaction();
		return $this->_status(true, 'Processing', "Branch has been updated.");
	 }

	 protected function _saveNew() {
		begin_transaction();
		$sql = "INSERT INTO cust_branch (debtor_no, br_name, branch_ref, br_address, city, state, postcode,
				salesman, phone, phone2, fax, contact_name, area, email, tax_group_id, sales_account, sales_discount_account, receivables_account, payment_discount_account, default_location,
				br_post_address, disable_trans, group_no, default_ship_via, notes,inactive)
				VALUES (" . db_escape($this->debtor_no) . "," . db_escape($this->name) . ", " . db_escape($this->branch_ref) . ", " . db_escape($this->address) . ", " . db_escape($this->city) . ", " . db_escape($this->state) . ", " . db_escape($this->postcode) . ", " . db_escape($this->salesman) . ", " . db_escape($this->phone) . ", " . db_escape($this->phone2) . ", " . db_escape($this->fax) . "," . db_escape($this->contact_name) . ", " . db_escape($this->area) . ", " . db_escape($this->email) . ", " . db_escape($this->tax_group_id) . ", " . db_escape($this->sales_account) . ", " . db_escape($this->sales_discount_account) . ", " . db_escape($this->receivables_account) . ", " . db_escape($this->payment_discount_account) . ", " . db_escape($this->default_location) . ", " . db_escape($this->br_post_address) . "," . db_escape($this->disable_trans) . ", " . db_escape($this->group_no) . ", " . db_escape($this->default_ship_via) . ", " . db_escape($this->notes) . ", " . db_escape($this->inactive) . ")";
		db_query($sql, "The branch could not be added");
		$this->branch_code = db_insert_id();
		commit_transaction();
		$this->_status(true, 'Saving', "New branch has been added");
	 }

	 protected function _defaults() {
		$company_record = get_company_prefs();
		$this->sales_discount_account = $company_record['default_sales_discount_act'];
		$this->receivables_account = $company_record['debtors_act'];
		$this->payment_discount_account = $company_record['default_prompt_payment_act'];
		$this->salesman = ($_SESSION['wa_current_user']) ? $_SESSION['wa_current_user']->salesmanid : 1;
	 }

	 protected function _new() {
		$this->_defaults();
		return $this->_status(true, 'Initialize new Branch', 'Now working with a new Branch');
	 }

	 protected function _countTransactions() {
	 }

  }


