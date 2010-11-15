<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:07 PM
 * To change this template use File | Settings | File Templates.
 */

class Customer extends Contact {


	public $id;
	public $name;
	public $address;
	public $email;
	public $taxId;
	public $curr_code;
	public $sales_type;
	public $dimension_id;
	public $dimension2_id;
	public $credit_status;
	public $payment_terms;
	public $discount;
	public $pymt_discount;
	public $credit_limit;
	public $notes;
	public $inactive;
	public $debtor_ref;

	public function __construct($id = null) {
		if ($id != null) {
			$this->_read($id);
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

	

	public function save($changes=null) {
		if (!$this->_canProcess() || !$this->setFromArray($changes)) {
			return false;
		}
		if ($this->id == 0) {
			return $this->_saveNew();
		}
		begin_transaction();
		$sql = "UPDATE " . TB_PREF . "debtors_master SET name=" . db_escape($_POST['CustName']) . ",
			debtor_ref=" . db_escape($_POST['cust_ref']) . ",
			address=" . db_escape($_POST['address']) . ",
			tax_id=" . db_escape($_POST['tax_id']) . ",
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
		$this->_status(true,'Saving New',"A new customer has been added.");
	    return true;
	}

	protected function _new() {
		global $Ajax, $SysPrefs;
		$this->id = 0;
		$this->name = $this->debtor_ref = $this->address = $this->taxId = '';
		$this->dimension_id = 0;
		$this->dimension2_id = 0;
		$this->sales_type = -1;
		$this->email = '';
		$this->curr_code = get_company_currency();
		$this->credit_status = -1;
		$this->payment_terms = $this->notes = '';
		$this->discount = $this->pymt_discount = percent_format(0);
		$this->credit_limit = price_format($SysPrefs->default_credit_limit());
		$this->inactive = 0;
		$Ajax->activate('_page_body');
	}

	protected function _read($id = null) {
		if (!$id) {
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
}
