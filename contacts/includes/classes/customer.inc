<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:07 PM
 * To change this template use File | Settings | File Templates.
 */

class Customer extends Contact {

	public $id = 0;
	public $name = '';
	public $address = '';
	public $email = '';
	public $taxId = '';
	public $curr_code = '';
	public $sales_type = -1;
	public $dimension_id = 0;
	public $dimension2_id = 0;
	public $credit_status = -1;
	public $payment_terms = '';
	public $discount = 0;
	public $pymt_discount = 0;
	public $credit_limit = 0;
	public $notes = '';
	public $inactive = 0;
	public $debtor_ref = '';
	public $branches = array();

	public $acc_contact_name='';

	
	public function __construct($id = null) {
		if ($id != null) {
			$this->_read($id);
		} else {
			$this->_new();
		}
	}

	protected function _canProcess() {
		if (strlen($_POST['name']) == 0) {
			$this->_status(false, 'Processing', "The customer name cannot be empty.", 'name');
			return false;
		}
		if (strlen($_POST['debtor_ref']) == 0) {
			$this->_status(false, 'Processing', "The customer short name cannot be empty.", 'debtor_ref');
			return false;
		}
		if (!check_num('credit_limit', 0)) {
			$this->_status(false, 'Processing', "The credit limit must be numeric and not less than zero.", 'credit_limit');
			return false;
		}
		if (!check_num('pymt_discount', 0, 100)) {
			$this->_status(false, 'Processing', "The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'pymt_discount');
			return false;
		}
		if (!check_num('discount', 0, 100)) {
			$this->_status(false, 'Processing', "The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'discount');
			return false;
		}
		return true;
	}

	public function save($changes = null) {
		if (!$this->setFromArray($changes) || !$this->_canProcess()) {
			return false;
		}
		if ($this->id == 0) {
			return $this->_saveNew();
		}
		begin_transaction();
		$sql = "UPDATE " . TB_PREF . "debtors_master SET name=" . db_escape($_POST['name']) . ",
			debtor_ref=" . db_escape($_POST['debtor_ref']) . ",
			address=" . db_escape($_POST['address']) . ",
			tax_id=" . db_escape($_POST['taxId']) . ",
			curr_code=" . db_escape($_POST['curr_code']) . ",
			email=" . db_escape($_POST['email']) . ",
			dimension_id=" . db_escape($_POST['dimension_id']) . ",
			dimension2_id=" . db_escape($_POST['dimension2_id']) . ",
            credit_status=" . db_escape($_POST['credit_status']) . ",
            payment_terms=" . db_escape($_POST['payment_terms']) . ",
            discount=" . input_num('discount') / 100 . ",
            pymt_discount=" . input_num('pymt_discount') / 100 . ",
            credit_limit=" . input_num('credit_limit') . ",
            sales_type = " . db_escape($_POST['sales_type']) . ",
            notes=" . db_escape($_POST['notes']) . "
            WHERE debtor_no = " . db_escape($_POST['customer_id']);

		db_query($sql, "The customer could not be updated");
		update_record_status($this->id, $this->inactive, 'debtors_master', 'debtor_no');
		commit_transaction();
		$this->_status(true, 'Processing', "Customer has been updated.");
		return true;
	}

	protected function _saveNew() {
		begin_transaction();
		$sql = "INSERT INTO " . TB_PREF . "debtors_master (name, debtor_ref, address, tax_id, email, dimension_id, dimension2_id,
			curr_code, credit_status, payment_terms, discount, pymt_discount,credit_limit,
			sales_type, notes) VALUES (" .
		       db_escape($this->name) . ", " .
		       db_escape($this->debtor_ref) . ", " .
		       db_escape($this->address) . ", " .
		       db_escape($this->taxId) . "," .
		       db_escape($this->email) . ", " .
		       db_escape($this->dimension_id) . ", " .
		       db_escape($this->dimension2_id) . ", " .
		       db_escape($this->curr_code) . ", " .
		       db_escape($this->credit_status) . ", " .
		       db_escape($this->payment_terms) . ", " .
		       input_num($this->discount) / 100 . "," .
		       input_num($this->pymt_discount) / 100 . ", " .
		       input_num($this->credit_limit) . ", " .
		       db_escape($this->sales_type) . ", " .
		       db_escape($this->notes) . ")";
		db_query($sql, "The customer could not be added");
		$this->id = db_insert_id();
		commit_transaction();
		$this->_status(true, 'Saving New', "A new customer has been added.");
		return true;
	}

	protected function _new() {
		global $SysPrefs;
		$this->id = $this->dimension_id = $this->dimension2_id = $this->inactive = 0;
		$this->sales_type = $this->credit_status = -1;
		$this->name = $this->address = $this->email = $this->taxId = $this->payment_terms = $this->notes = $this->debtor_ref = '';
		$this->curr_code = get_company_currency();
		$this->discount = $this->pymt_discount = percent_format(0);
		$this->credit_limit = price_format($SysPrefs->default_credit_limit());
		$this->_status(true, '_new', 'Now working with a new customer');
	}

	protected function _read($id = null) {
		global $SysPrefs;
		if ($id == null || empty($id)) {
			$this->_status(false, 'read', 'No customer ID to read');
			return false;
		}
		$sql = "SELECT * FROM " . TB_PREF . "debtors_master WHERE debtor_no = " . db_escape($id);
		$result = db_query($sql, "check failed");
		if (db_num_rows($result) != 1) {
			$this->_status(false, 'read', "Customer could not be found!");
			return false;
		}
		$myrow = db_fetch($result);
		$this->id = $id;
		$this->setFromArray($myrow);
		$this->branches = $this->_getBranches();
		$this->curr_code = get_company_currency();
		$this->discount = $_POST['discount'] = $this->discount * 100;
		$this->pymt_discount = $_POST['pymt_discount'] = $this->pymt_discount * 100;
		$this->credit_limit = price_format($this->credit_limit);
		return $id;
	}

	protected function _countTransactions() {
		$sql = "SELECT COUNT(*) FROM " . TB_PREF . "debtor_trans WHERE debtor_no=" . $this->id;
		$result = db_query($sql, "check failed");
		return db_fetch_row($result);
	}

	protected function _countOrders() {
		$sql = "SELECT COUNT(*) FROM " . TB_PREF . "sales_orders WHERE debtor_no=" . $this->id;
		$result = db_query($sql, "check failed");
		return db_fetch_row($result);
	}

	protected function _countBranches() {
		$sql = "SELECT COUNT(*) FROM " . TB_PREF . "cust_branch WHERE debtor_no=" . $this->id;
		$result = db_query($sql, "check failed");
		return db_fetch_row($result);
	}

	protected function delete() {
		global $Ajax;
		if ($this->_countTransactions() > 0) {
			$this->_status(false, 'delete', "This customer cannot be deleted because there are transactions that refer to it.");
			return false;
		}
		if ($this->_countOrders() > 0) {
			$this->_status(false, 'delete', "Cannot delete the customer record because orders have been created against it.");
			return false;
		}
		if ($this->_countBranches() > 0) {
			$this->_status(false, 'delete', "Cannot delete this customer because there are branch records set up against it.");
			return false;
		}
		$sql = "DELETE FROM " . TB_PREF . "debtors_master WHERE debtor_no=" . $this->id;
		db_query($sql, "cannot delete customer");
		unset($this->id);
		$this->_new();
		return true;
	}

	public function search() {

	}

	protected function _getBranches() {
		$sql = "SELECT "
		       . "b.branch_code, "
		       . "b.branch_ref, "
		       . "b.br_name, "
		       . "b.contact_name, "
		       . "s.salesman_name, "
		       . "a.description, "
		       . "b.phone, "
		       . "b.fax, "
		       . "b.email, "
		       . "t.name AS tax_group_name, "
		       . "b.inactive
		FROM " . TB_PREF . "cust_branch b, "
		       . TB_PREF . "debtors_master c, "
		       . TB_PREF . "areas a, "
		       . TB_PREF . "salesman s, "
		       . TB_PREF . "tax_groups t
		WHERE b.debtor_no=c.debtor_no
		AND b.tax_group_id=t.id
		AND b.area=a.area_code
		AND b.salesman=s.salesman_code
		AND b.debtor_no = " . db_escape($this->id);
		$result = db_query($sql, 'Could not get customer branches.');
		while ($row = db_fetch_assoc($result)) {
			$this->branches[] = $row;
		}
		return $this->branches;
	}
}
