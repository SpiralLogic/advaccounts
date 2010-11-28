<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 11:52 PM
 * To change this template use File | Settings | File Templates.
 */

class Branch extends Contact {


	public $branch_code = 0;
	public $br_name;
	public $br_address;
	public $area;
	public $br_post_address;
	public $debtor_no;
	public $branch_ref;
	public $contact_name;
	public $default_location;
	public $default_ship_via;
	public $disable_trans;
	public $email;
	public $fax;
	public $group_no;
	public $inactive;
	public $notes;
	public $payment_discount_account;
	public $phone;
	public $phone2;
	public $receivables_account;
	public $sales_account;
	public $sales_discount_account;
	public $salesman;
	public $tax_group_id;

	public function __construct($id = null) {
		if ($id !== null) {
			$result = $this->_read($id);

			if (is_array($result)) {
				return $result;
			} else {
				return true;
			}
		} else {
			$this->_new();
		}
	}

	protected function delete() {
	}

	protected function _read($params = false) {
		if (!$params) {
			$this->_status(false, 'Retrieving branch', 'No search parameters provided');
			return false;
		}
		if (!is_array($params)) {
			$params = array('branch_code' => $params);
		}

		$sql = "SELECT "
				. "b.*, "
				. "a.description, "
				. "s.salesman_name, "
				. "t.name AS tax_group_name
		FROM " . TB_PREF . "cust_branch b, "
				. TB_PREF . "debtors_master c, "
				. TB_PREF . "areas a, "
				. TB_PREF . "salesman s, "
				. TB_PREF . "tax_groups t
		WHERE b.debtor_no=c.debtor_no
		AND b.tax_group_id=t.id
		AND b.area=a.area_code
		AND b.salesman=s.salesman_code";
		foreach ($params as $key => $value) {
			$sql .= " AND b.$key =" . db_escape($value);
		}
		$result = db_query($sql, 'Could not get customer branches.');
		if (db_num_rows($result)<1) {
			$this->_status(false,'Retrieving  Branch','No results from query');
			return false;
		}
     
		$myrow = db_fetch_assoc($result);
		$this->setFromArray($myrow);
	    return true;
	}

	protected function _canProcess() {
		return true;
	}


	public function save($changes = null) {
		if (!$this->setFromArray($changes) || !$this->_canProcess()) {
			return false;
		}

		if ($this->branch_code == 0) {
			return $this->_saveNew();
		}
		begin_transaction();
		$sql = "UPDATE " . TB_PREF . "cust_branch SET
			br_name=" . db_escape($this->br_name) . ",
			br_address=" . db_escape($this->br_address) . ",
			br_post_address=" . db_escape($this->br_post_address).", 
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
		$this->_status(true, 'Processing', "Branch has been updated.");
		return true;
	}

	protected function _saveNew() {
		begin_transaction();

		$sql = "INSERT INTO " . TB_PREF . "cust_branch (debtor_no, br_name, branch_ref, br_address,
				salesman, phone, phone2, fax, contact_name, area, email, tax_group_id, sales_account, sales_discount_account, receivables_account, payment_discount_account, default_location,
				br_post_address, disable_trans, group_no, default_ship_via, notes,inactive)
				VALUES ("
				. db_escape($this->debtor_no) . ","
				. db_escape($this->br_name) . ", "
				. db_escape($this->branch_ref) . ", "
				. db_escape($this->br_address) . ", "
				. db_escape($this->salesman) . ", "
				. db_escape($this->phone) . ", "
				. db_escape($this->phone2) . ", "
				. db_escape($this->fax) . ","
				. db_escape($this->contact_name) . ", "
				. db_escape($this->area) . ", "
				. db_escape($this->email) . ", "
				. db_escape($this->tax_group_id) . ", "
				. db_escape($this->sales_account) . ", "
				. db_escape($this->sales_discount_account) . ", "
				. db_escape($this->receivables_account) . ", "
				. db_escape($this->payment_discount_account) . ", "
				. db_escape($this->default_location) . ", "
				. db_escape($this->br_post_address) . ","
				. db_escape($this->disable_trans) . ", "
				. db_escape($this->group_no) . ", "
				. db_escape($this->default_ship_via) . ", "
				. db_escape($this->notes) . ", "
				. db_escape($this->inactive) . ")";

		$result = db_query($sql, "The branch could not be added");
		$this->branch_code = db_insert_id();
		commit_transaction();
		$this->_status(true, 'Processing', "New customer branch has been added");
		return true;
	}

	protected function _new($params = array()) {
		$company_record = get_company_prefs();
		$default['sales_discount_account'] = $company_record['default_sales_discount_act'];
		$default['receivables_account'] = $company_record['debtors_act'];
		$default['payment_discount_account'] = $company_record['default_prompt_payment_act'];
		$params = array_merge($default, $params);
		$this->setFromArray($params);
	}

	protected function _countTransactions() {
	}
}


