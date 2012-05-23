<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Creditor extends Contact_Company {

    /**
     * @static
     *
     * @param $terms
     *
     * @return array|string
     */
    static public function search($terms) {
      $sql = "SELECT supplier_id as id, supp_ref as label, supp_ref as value FROM suppliers WHERE supp_ref LIKE '%" . $terms . "%' ";
      if (is_numeric($terms)) {
        $sql .= ' OR supplier_id LIKE  ' . DB::quote($terms . '%');
      }
      $sql .= " LIMIT 20";
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
    /**
     * @var
     */
    /**
     * @var
     */
    public $id, $supplier_id; //
    /**
     * @var
     */
    public $name; //
    /**
     * @var
     */
    /**
     * @var
     */
    public $tax_id, $gst_no; //
    /**
     * @var
     */
    /**
     * @var
     */
    public $contact_name, $contact; //
    /**
     * @var
     */
    /**
     * @var
     */
    public $post_address, $address; //

    /**
     * @var string
     */
    public $city = "";
    /**
     * @var string
     */
    public $state = "";
    /**
     * @var string
     */
    public $postcode = "";
    /**
     * @var string
     */
    public $phone = "";
    /**
     * @var string
     */
    public $phone2 = "";
    /**
     * @var string
     */
    public $supp_phone = "";
    /**
     * @var string
     */
    public $fax = "";
    /**
     * @var string
     */
    public $notes = "";
    /**
     * @var int
     */
    public $inactive = 0;
    /**
     * @var
     */
    public $website;
    /**
     * @var string
     */
    public $email = "";
    /**
     * @var string
     */
    /**
     * @var string
     */
    public $account_no = '';
    /**
     * @var
     */
    public $bank_account;
    /**
     * @var string
     */
    public $tax_group_id = '';
    /**
     * @var
     */
    public $purchase_account;
    /**
     * @var
     */
    public $payable_account;
    /**
     * @var
     */
    public $payment_discount_account;
    /**
     * @var string
     */
    public $ref = '';
    /**
     * @var array
     */
    public $contacts = array();
    /**
     * @var
     */
    public $defaultContact;
    /**
     * @var
     */
    public $supp_city;
    /**
     * @var
     */
    public $supp_state;
    /**
     * @var
     */
    public $supp_postcode;
    /**
     * @var string
     */
    protected $_table = 'suppliers';
    /**
     * @var string
     */
    protected $_id_column = 'supplier_id';
    /**
     * @param int|null $id
     */
    public function __construct($id = NULL) {
      $this->gst_no = &$this->tax_id;
      $this->contact = &$this->contact_name;
      $this->address = &$this->post_address;
      $this->phone2 = &$this->phone;
      parent::__construct($id);

      $this->ref = substr($this->name, 0, 29);
    }
    /**
     * @return array
     */
    public function getEmailAddresses() {
      return array('Accounts' => array($this->id => array($this->name, $this->email)));
    }
    /**
     * @param array|null $changes
     *
     * @return array|bool|int|null|void
     */
    public function save($changes = NULL) {
      if (!parent::save($changes)) {
        $this->_setDefaults();
        return FALSE;
      }

      foreach ($this->contacts as $contact) {
        $contact->save(array('parent_id' => $this->id));
        $this->contacts[$contact->id] = $contact;
      }
      return $this->_setDefaults();
    }

    public function delete() {
      // TODO: Implement delete() method.
    }
    /**
     * @param null $changes
     *
     * @return array|null|void
     */
    protected function setFromArray($changes = NULL) {
      parent::setFromArray($changes);
      if (isset($changes['contacts']) && is_array($changes['contacts'])) {
        foreach ($changes['contacts'] as $id => $contact) {
          $this->contacts[$id] = new Contact(CT_SUPPLIER, $contact);
        }
      }
      $this->discount = User::numeric($this->discount) / 100;
      $this->ref = substr($this->name, 0, 29);
      $this->credit_limit = str_replace(',', '', $this->credit_limit);
    }
    /**
     * @return void
     */
    protected function _setDefaults() {
      $this->defaultContact = (count($this->contacts) > 0) ? reset($this->contacts)->id : 0;
      $this->contacts[0] = new Contact(CT_SUPPLIER, array('parent_id' => $this->id));
    }
    /**
     * @return bool
     */
    protected function _canProcess() {
      if (empty($this->name)) {
        $this->_status(FALSE, 'Processing', "The supplier name cannot be empty.", 'name');
        return FALSE;
      }
      return TRUE;
    }
    /**
     * @return mixed|void
     */
    protected function _countTransactions() {
      // TODO: Implement _countTransactions() method.
    }
    /**
     * @return bool|Status
     */
    protected function _defaults() {
      $this->credit_limit = Num::price_format(0);
      $company_record = DB_Company::get_prefs();
      $this->curr_code = $company_record["curr_default"];
      $this->payable_account = $company_record["creditors_act"];
      $this->purchase_account = $company_record["default_cogs_act"];
      $this->payment_discount_account = $company_record['pyt_discount_act'];
      $this->contacts[0] = new Contact(CT_SUPPLIER);
      $this->contacts[0]->parent_id = $this->id = 0;
      $this->_setDefaults();
      return $this->_status(TRUE, 'Initialize', 'Now working with a new customer');
    }
    /**
     * @return bool|Status
     */
    protected function _new() {
      $this->_defaults();
      return $this->_status(TRUE, 'Initialize new supplier', 'Now working with a new supplier');
    }
    /**
     * @return void
     */
    protected function _getContacts() {
      DB::select()->from('contacts')->where('parent_id=', $this->id)->and_where('parent_type=', CT_SUPPLIER);
      $contacts = DB::fetch()->asClassLate('Contact', array(CT_SUPPLIER));
      if (count($contacts)) {
        foreach ($contacts as $contact) {
          $this->contacts[$contact->id] = $contact;
        }
        $this->defaultContact = reset($this->contacts)->id;
      }
      $this->contacts[0] = new Contact(CT_SUPPLIER, array('parent_id' => $this->id));
    }
    /**
     * @param bool|int|null $id
     *
     * @return array|bool
     */
    protected function _read($id = FALSE) {
      if (!parent::_read($id)) {
        return $this->_status->get();
      }
      $this->_getContacts();
      $this->discount = $this->discount * 100;
      $this->credit_limit = Num::price_format($this->credit_limit);
    }
    /**
     * @static
     *
     * @param      $supplier_id
     * @param null $to
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_to_trans($supplier_id, $to = NULL) {
      if ($to == NULL) {
        $todate = date("Y-m-d");
      }
      else {
        $todate = Dates::date2sql($to);
      }
      $past_due1 = DB_Company::get_pref('past_due_days');
      $past_due2 = 2 * $past_due1;
      // removed - creditor_trans.alloc from all summations
      $value = "(creditor_trans.ov_amount + creditor_trans.ov_gst + creditor_trans.ov_discount)";
      $due = "IF (creditor_trans.type=" . ST_SUPPINVOICE . " OR creditor_trans.type=" . ST_SUPPCREDIT . ",creditor_trans.due_date,creditor_trans.tran_date)";
      $sql = "SELECT suppliers.name, suppliers.curr_code, payment_terms.terms,

		Sum($value) AS Balance,

		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due1,$value,0)) AS Overdue1,
		Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due2,$value,0)) AS Overdue2

		FROM suppliers,
			 payment_terms,
			 creditor_trans

		WHERE
			 suppliers.payment_terms = payment_terms.terms_indicator
			 AND suppliers.supplier_id = $supplier_id
			 AND creditor_trans.tran_date <= '$todate'
			 AND suppliers.supplier_id = creditor_trans.supplier_id

		GROUP BY
			 suppliers.name,
			 payment_terms.terms,
			 payment_terms.days_before_due,
			 payment_terms.day_in_following_month";
      $result = DB::query($sql, "The customer details could not be retrieved");
      if (DB::num_rows($result) == 0) {
        /*Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */
        $nil_balance = TRUE;
        $sql = "SELECT suppliers.name, suppliers.curr_code, suppliers.supplier_id, payment_terms.terms
			FROM suppliers,
				 payment_terms
			WHERE
				 suppliers.payment_terms = payment_terms.terms_indicator
				 AND suppliers.supplier_id = " . DB::escape($supplier_id);
        $result = DB::query($sql, "The customer details could not be retrieved");
      }
      else {
        $nil_balance = FALSE;
      }
      $supp = DB::fetch($result);
      if ($nil_balance == TRUE) {
        $supp["Balance"] = 0;
        $supp["Due"] = 0;
        $supp["Overdue1"] = 0;
        $supp["Overdue2"] = 0;
      }
      return $supp;
    }
    /**
     *   Get how much we owe the supplier for the period
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
    /**
     * @static
     *
     * @param $supplier_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($supplier_id) {
      $sql = "SELECT * FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);
      $result = DB::query($sql, "could not get supplier");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $supplier_id
     *
     * @return mixed
     */
    static public function get_name($supplier_id) {
      $sql = "SELECT name AS name FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);
      $result = DB::query($sql, "could not get supplier");
      $row = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $supplier_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_accounts_name($supplier_id) {
      $sql = "SELECT payable_account,purchase_account,payment_discount_account FROM suppliers WHERE supplier_id=" . DB::escape($supplier_id);
      $result = DB::query($sql, "could not get supplier");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_option
     * @param bool $submit_on_change
     * @param bool $all
     * @param bool $editkey
     *
     * @return string
     */
    static public function select($name, $selected_id = NULL, $spec_option = FALSE, $submit_on_change = FALSE, $all = FALSE, $editkey = FALSE) {
      $sql = "SELECT supplier_id, ref, curr_code, inactive FROM suppliers ";
      $mode = DB_Company::get_pref('no_supplier_list');

      return select_box($name, $selected_id, $sql, 'supplier_id', 'name', array(
        'format' => '_format_add_curr',
        'order' => array('supp_ref'),
        'search_box' => $mode != 0,
        'type' => 1,
        'spec_option' => $spec_option === TRUE ? _("All Suppliers") : $spec_option,
        'spec_id' => ALL_TEXT,
        'select_submit' => $submit_on_change,
        'async' => FALSE,
        'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') :
          _('Select supplier'),
        'show_inactive' => $all
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     * @param bool $editkey
     *
     * @return void
     */
    static public function cells($label, $name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE, $all = FALSE, $editkey = FALSE) {
      echo "<td class='label'>";
      if ($label != NULL) {
        echo "<label for='$name'>$label</label>";
      }
      echo Creditor::select($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $all_option
     * @param bool $submit_on_change
     * @param bool $all
     * @param bool $editkey
     *
     * @return void
     */
    static public function row($label, $name, $selected_id = NULL, $all_option = FALSE, $submit_on_change = FALSE, $all = FALSE, $editkey = FALSE) {
      echo "<tr><td class='label' name='name'><label for='$name'>$label</label></td><td>";
      echo Creditor::select($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
      echo "</td></tr>\n";
    }
  }
