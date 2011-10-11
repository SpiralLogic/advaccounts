<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 15/11/10
 * Time: 4:12 PM
 * To change this template use File | Settings | File Templates.
 */
class Supplier extends Company
{

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

    function __construct($id = null)
    {
        $this->supplier_id = &$this->id;
        $this->supp_name =& $this->name;
        $this->gst_no = &$this->tax_id;
        $this->contact = &$this->contact_name;
        $this->supp_address = &$this->post_address;
        $this->supp_account_no = &$this->account_no;
        parent::__construct($id);
        $this->supp_ref = substr($this->name, 0, 29);
    }

    protected function _canProcess()
    {
        if (empty($this->name)) {
            $this->_status(false, 'Processing', "The supplier name cannot be empty.", 'name');
            return false;
        }
        return true;
    }

    protected function _countTransactions()
    {
        // TODO: Implement _countTransactions() method.
    }

    protected function _defaults()
    {
        $this->credit_limit = price_format(0);
        $company_record = get_company_prefs();
        $this->curr_code = $company_record["curr_default"];
        $this->payable_account = $company_record["creditors_act"];
        $this->purchase_account = $company_record["default_cogs_act"];
        $this->payment_discount_account = $company_record['pyt_discount_act'];

    }

    protected function _new()
    {
        $this->_defaults();
        return $this->_status(true, 'Initialize new supplier', 'Now working with a new supplier');

    }

    protected function _read($id = null)
    {
        if ($id == null || empty($id)) {
            return $this->_status(false, 'read', 'No supplier ID to read');
        }
        $this->_defaults();
        $this->id = $id;
        $sql = "SELECT * FROM suppliers WHERE supplier_id = " . db_escape($id);
        $result = db_query($sql, "check failed");
        if (db_num_rows($result) != 1) {
            $this->_status(false, 'read', "Supplier could not be found!");
            return false;
        }
        $result = db_fetch_assoc($result);
        $this->setFromArray($result);
        $this->credit_limit = price_format($this->credit_limit);
        return $this->id;
    }

    protected function _saveNew()
    {
        begin_transaction();
        $sql = "INSERT INTO suppliers (supp_name, supp_ref, address, supp_address, phone, phone2, fax, gst_no, email, website,
				contact, supp_account_no, bank_account, credit_limit, dimension_id, dimension2_id, curr_code,
				payment_terms, payable_account, purchase_account, payment_discount_account, notes, tax_group_id)
				VALUES (" . db_escape($this->name) . ", " . db_escape($this->supp_ref) . ", " . db_escape($this->address) . ", " . db_escape($this->post_address) . ", " . db_escape($this->phone) . ", " . db_escape($this->phone2) . ", " . db_escape($this->fax) . ", " . db_escape($this->tax_id) . ", " . db_escape($this->email) . ", " . db_escape($this->website) . ", " . db_escape($this->contact_name) . ", " . db_escape($this->account_no) . ", " . db_escape($this->bank_account) . ", " . db_escape($this->credit_limit) . ", " . db_escape($this->dimension_id) . ", " . db_escape($this->dimension2_id) . ", " . db_escape($this->curr_code) . ", " . db_escape($this->payment_terms) . ", " . db_escape($this->payable_account) . ", " . db_escape($this->purchase_account) . ", " . db_escape($this->payment_discount_account) . ", " . db_escape($this->notes) . ", " . db_escape($this->tax_group_id) . ")";
        db_query($sql, "The supplier could not be added");
        $this->id = db_insert_id();
        commit_transaction();
        $this->_status(true, 'Saving', "A Supplier has been added.");
    }

    protected function delete()
    {
        // TODO: Implement delete() method.
    }

    function save($changes = null)
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
        $sql = "UPDATE suppliers SET name=" . db_escape($this->name) . ",
			supp_ref=" . db_escape(substr($this->name, 0, 29)) . ",
			address=" . db_escape($this->address) . ",
			supp_account_no=" . db_escape($this->account_no) . ",
			tax_id=" . db_escape($this->tax_id) . ",
			bank_account=" . db_escape($this->bank_account) . ",
			purchase_account=" . db_escape($this->purchase_account) . ",
			payable_account=" . db_escape($this->payable_account) . ",
			payment_discount_account=" . db_escape($this->payment_discount_account) . ",
			curr_code=" . db_escape($this->curr_code) . ",
			email=" . db_escape($this->email) . ",
			website=" . db_escape($this->website) . ",
			fax=" . db_escape($this->fax) . ",
			phone=" . db_escape($this->phone) . ",
			phone2=" . db_escape($this->phone2) . ",
			inactive=" . db_escape($this->inactive) . ",
			dimension_id=" . db_escape($this->dimension_id) . ",
			dimension2_id=" . db_escape($this->dimension2_id) . ",
            credit_status=" . db_escape($this->credit_status) . ",
            payment_terms=" . db_escape($this->payment_terms) . ",
            pymt_discount=" . user_numeric($this->pymt_discount) / 100 . ",
            credit_limit=" . user_numeric($this->credit_limit) . ",
            notes=" . db_escape($this->notes) . "
            WHERE debtor_no = " . db_escape($this->id);
        db_query($sql, "The supplier could not be updated");
        commit_transaction();
        return $this->_status(true, 'Processing', "Supplier has been updated.");
    }

    public static function search($terms)
    {
        $sql = "SELECT supplier_id as id, supp_ref as label, supp_ref as value FROM suppliers " . "where supp_ref LIKE '%" . $terms . "%' LIMIT 20";
        $result = db_query($sql, 'Couldn\'t Get Supplier');
        $data = '';
        while ($row = db_fetch_assoc($result)) {
            foreach ($row as &$value) {
                $value = htmlspecialchars_decode($value);
            }
            $data[] = $row;
        }
        return $data;
    }

    public function getEmailAddresses()
    {

        return array('Accounts' => array($this->id => array($this->name, $this->email)));
    }
 
    public static function addSupplierInfo($selector, $id=false)
    {
        if ($id) $supplier = new self($id);
        $content = '<div><span class="bold">Shipping Address:</span><br>${address}</br></br>
          		<span class="bold">Mailing Address:</span><br>${post_address}</br></br>
          		<span class="bold">Phone: </span>${phone}</br></br>
          		<span class="bold">Phone2: </span>${phone2}</br></br>
          		<span class="bold">Fax: </span>${fax}</br></br>
          		<span class="bold">Contact: </span>${contact}</br></br>
          		<span class="bold">Email: </span><a href="mailto:${email}">${email}</a></br></br>
          		<span class="bold">Website: </span><a target="_new" href="http://${website}">${website}</a></br></br>
          		<span class="bold">Account #: </span>${supp_account_no}</br></br></div>';
        $supp_details = new Dialog('Supplier Details:', 'supplier_details', $content);
        $supp_details->setTemplateData(($id)?$supplier:''); 
        if ($id) {
            $supp_details->addOpenEvent($selector, 'click');
        } else {
            $action = <<<JS
            $.post('/contacts/suppliers.php',{id:$(this).data('id')},function(data) {Adv.o.supplier_details.render(data.supplier); $('#supplier_details').dialog('open');},'json');
JS;
            JS::addLiveEvent($selector,'click',$action,'wrapper',true);
        }
        $supp_details->addButton('Close', '$(this).dialog("close")');
        $supp_details->show();
    }
}
