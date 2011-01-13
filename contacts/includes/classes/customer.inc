<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:07 PM
 * To change this template use File | Settings | File Templates.
 */
class Customer extends Contact
{


    public $id = 0;
    public $name = 'New Customer';
    public $address = '';
    public $tax_id = '';
    public $curr_code = '';
    public $sales_type;
    public $dimension_id = 0;
    public $dimension2_id = 0;
    public $credit_status;
    public $payment_terms = 1;
    public $discount = '0';
    public $pymt_discount = '0';
    public $credit_limit = 0;
    public $debtor_ref = '';
    public $branches=array();
    public $defaultBranch;
    public $accounts;
    public $transactions;

    function __construct($id = null)
    {
        parent::__construct($id);
        $this->debtor_ref = substr($this->name, 0, 29);
        $this->transactions = $this->getTransactions();
    }

    protected function _canProcess()
    {
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

    protected function setFromArray($changes)
    {
        parent::setFromArray($changes);
        if (isset($changes['accounts']) && is_array($changes['accounts'])) {
            $this->accounts = new Accounts($changes['accounts']);
        }
        if (isset($changes['branches']) && is_array($changes['branches'])) {
            foreach ($changes['branches'] as $branchid => $branch) {
                $this->branches[$branchid] = new Branch($branch);
            }
        }
    }

    public function save($changes = null)
    {
        if (is_array($changes)) {
            $this->setFromArray($changes);
        }
        if (!$this->_canProcess()) {
            return false;
        }
        if ($this->id == 0) {
            $this->_saveNew();
        }
        begin_transaction();

        $sql = "UPDATE " . TB_PREF . "debtors_master SET name=" . db_escape($this->name) . ",
			debtor_ref=" . db_escape(substr($this->name, 0, 29)) . ",
			address=" . db_escape($this->address) . ",
			tax_id=" . db_escape($this->tax_id) . ",
			curr_code=" . db_escape($this->curr_code) . ",
			email=" . db_escape($this->email) . ",
			dimension_id=" . db_escape($this->dimension_id) . ",
			dimension2_id=" . db_escape($this->dimension2_id) . ",
            credit_status=" . db_escape($this->credit_status) . ",
            payment_terms=" . db_escape($this->payment_terms) . ",
            discount=" . user_numeric($this->discount) / 100 . ",
            pymt_discount=" . user_numeric($this->pymt_discount) / 100 . ",
            credit_limit=" . user_numeric($this->credit_limit) . ",
            sales_type = " . db_escape($this->sales_type) . ",
            notes=" . db_escape($this->notes) . "
            WHERE debtor_no = " . db_escape($this->id);
        db_query($sql, "The customer could not be updated");
        update_record_status($this->id, $this->inactive, 'debtors_master', 'debtor_no');
        commit_transaction();
        $this->accounts->save(array('debtor_no' => $this->id));
        foreach ($this->branches as $branch_code => $branch) {
            $branch->save(array('debtor_no' => $this->id));
            if ($branch_code == 0) {
                $this->branches[$branch->branch_code] = $branch;
                unset($this->branches[0]);
            }
        }
        $this->defaultBranch = reset($this->branches)->branch_code;
        return $this->_status(true, 'Processing', "Customer has been updated.");
    }

    protected function _saveNew()
    {
        begin_transaction();
        $sql = "INSERT INTO " . TB_PREF . "debtors_master (name, debtor_ref, address, tax_id, email, dimension_id, dimension2_id,
			curr_code, credit_status, payment_terms, discount, pymt_discount,credit_limit,
			sales_type, notes) VALUES (" . db_escape($this->name) . ", " . substr(db_escape($this->name), 0, 29) . ", " . db_escape($this->address) . ", " . db_escape($this->tax_id) . ","
               . db_escape($this->email) . ", " . db_escape($this->dimension_id) . ", " . db_escape($this->dimension2_id) . ", " . db_escape($this->curr_code) . ", " . db_escape($this->credit_status)
               . ", " . db_escape($this->payment_terms) . ", " . user_numeric($this->discount) / 100 . "," . user_numeric($this->pymt_discount) / 100 . ", " . user_numeric($this->credit_limit) . ", "
               . db_escape($this->sales_type) . ", " . db_escape($this->notes) . ")";
        db_query($sql, "The customer could not be added");
        $this->id = db_insert_id();
        commit_transaction();
        $this->_status(true, 'Saving', "A Customer has been added.");
    }

    protected function _new()
    {
        $this->_defaults();
        $this->accounts = new Accounts();
        $this->branches[0] = new Branch();
        $this->branches[0]->debtor_no = $this->accounts->debtor_no = $this->id;
        $this->defaultBranch = reset($this->branches)->branch_code;
        return $this->_status(true, 'Initialize new customer', 'Now working with a new customer');
    }

    protected function _defaults()
    {
        global $SysPrefs;
        $this->id = $this->dimension_id = $this->dimension2_id = $this->inactive = 0;
        $this->sales_type = $this->credit_status = 1;
        $this->name = $this->address = $this->email = $this->tax_id = $this->payment_terms = $this->notes = $this->debtor_ref = '';
        $this->curr_code = get_company_currency();
        $this->discount = $this->pymt_discount = percent_format(0);
        $this->credit_limit = price_format($SysPrefs->default_credit_limit());
    }

    protected function _read($id = null)
    {
        if ($id == null || empty($id)) {
            return $this->_status(false, 'read', 'No customer ID to read');
        }
        $this->_defaults();
        $this->id = $id;
        $sql = "SELECT * FROM " . TB_PREF . "debtors_master WHERE debtor_no = " . db_escape($id);
        $result = db_query($sql, "check failed");
        if (db_num_rows($result) != 1) {
            $this->_status(false, 'read', "Customer could not be found!");
            return false;
        }
        $result = db_fetch_assoc($result);
        $this->setFromArray($result);
        $this->_getBranches();
        $this->_getAccounts();
        $this->discount = $this->discount * 100;
        $this->pymt_discount = $this->pymt_discount * 100;
        $this->credit_limit = price_format($this->credit_limit);
        return $this->id;
    }

    protected function _countTransactions()
    {
        $sql = "SELECT COUNT(*) FROM " . TB_PREF . "debtor_trans WHERE debtor_no=" . $this->id;
        $result = db_query($sql, "check failed");
        return db_fetch_row($result);
    }

    public function getTransactions()
    {
        $sql = "SELECT " . TB_PREF . "debtor_trans.*, " . TB_PREF . "sales_orders.customer_ref,
				(" . TB_PREF . "debtor_trans.ov_amount + " . TB_PREF . "debtor_trans.ov_gst + " . TB_PREF . "debtor_trans.ov_freight +
				" . TB_PREF . "debtor_trans.ov_freight_tax + " . TB_PREF . "debtor_trans.ov_discount)
				AS TotalAmount, " . TB_PREF . "debtor_trans.alloc AS Allocated
				FROM " . TB_PREF . "debtor_trans LEFT OUTER JOIN 0_sales_orders ON  " . TB_PREF . "debtor_trans.order_ =  " . TB_PREF . "sales_orders.order_no
    			WHERE  " . TB_PREF . "debtor_trans.debtor_no = " . db_escape($this->id) . "
    			 AND " . TB_PREF . "sales_orders.debtor_no = " . db_escape($this->id) . "
    				AND " . TB_PREF . "debtor_trans.type <> " . ST_CUSTDELIVERY . "
    				AND (" . TB_PREF . "debtor_trans.ov_amount + " . TB_PREF . "debtor_trans.ov_gst + " . TB_PREF . "debtor_trans.ov_freight +
				" . TB_PREF . "debtor_trans.ov_freight_tax + " . TB_PREF . "debtor_trans.ov_discount) != 0
    				ORDER BY " . TB_PREF . "debtor_trans.branch_code, " . TB_PREF . "debtor_trans.tran_date";
        $result = db_query($sql, "No transactions were returned");
        $results = array();
        while ($row = db_fetch_assoc($result)) {
            $results[] = $row;
        }
        return $results;
    
    }

    protected function _countOrders()
    {
        $sql = "SELECT COUNT(*) FROM " . TB_PREF . "sales_orders WHERE debtor_no=" . $this->id;
        $result = db_query($sql, "check failed");
        return db_fetch_row($result);
    }

    protected function _countBranches()
    {
        $sql = "SELECT COUNT(*) FROM " . TB_PREF . "cust_branch WHERE debtor_no=" . $this->id;
        $result = db_query($sql, "check failed");
        return db_fetch_row($result);
    }

    public function delete()
    {
        if ($this->_countTransactions() > 0) {
            return $this->_status(false, 'delete', "This customer cannot be deleted because there are transactions that refer to it.");
        }
        if ($this->_countOrders() > 0) {
            return $this->_status(false, 'delete', "Cannot delete the customer record because orders have been created against it.");
        }
        if ($this->_countBranches() > 0) {
            return $this->_status(false, 'delete', "Cannot delete this customer because there are branch records set up against it.");
        }
        $sql = "DELETE FROM " . TB_PREF . "debtors_master WHERE debtor_no=" . $this->id;
        db_query($sql, "cannot delete customer");
        unset($this->id);
        $this->_new();
        return $this->_status(true, 'delete', "Customer deleted.");
    }

    protected function _getAccounts()
    {
        $sql = "SELECT branch_code FROM " . TB_PREF . "cust_branch WHERE debtor_no=" . $this->id . " AND branch_ref = 'accounts'";
        $result = db_query($sql, 'Couldn\'t retrieve any accounts');
        $result = db_fetch_assoc($result);
        $this->accounts = new Accounts($result['branch_code']);
        $this->accounts->debtor_no = $this->id;
        if ($this->accounts->accounts_id == 0 && $this->id > 0 && $this->defaultBranch > 0) {
            $this->accounts->debtor_no = $this->id;
            $this->accounts->email = $this->email;
            $this->accounts->br_address = $this->address;
            $this->accounts->phone = $this->branches[$this->defaultBranch]->phone;
            $this->accounts->phone2 = $this->branches[$this->defaultBranch]->phone2;
            $this->accounts->fax = $this->branches[$this->defaultBranch]->fax;
            $this->accounts->contact_name = $this->branches[$this->defaultBranch]->contact_name;
            $this->accounts->br_name = 'Accounts Department';
            $this->accounts->save();
        }
    }

    protected function _getBranches()
    {
        $sql = "SELECT branch_code, br_name, branch_ref FROM " . TB_PREF . "cust_branch WHERE debtor_no=" . $this->id . " AND branch_ref != 'accounts' AND disable_trans =0";
        $result = db_query($sql, 'Couldn\'t retrieve any branches');
        while ($row = db_fetch_assoc($result)) {
            $this->branches[$row['branch_code']] = new Branch($row['branch_code']);
        }
        $this->defaultBranch = reset($this->branches)->branch_code;
    }

    public function addBranch($details = null)
    {
        $branch = new Branch($details);
        $branch->debtor_no = $this->id;
        $branch->save();
        $this->branches[$branch->branch_code] = $branch;
    }

    public static function search($terms)
    {
        $sql = "SELECT debtor_no as id, debtor_ref as label, debtor_ref as value FROM " . TB_PREF . "debtors_master " . "where debtor_ref LIKE '%" . $terms . "%' LIMIT 20";
        $result = db_query($sql, 'Couldn\'t Get Customers');
        $data = '';
        while ($row = db_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $data;
    }
}
