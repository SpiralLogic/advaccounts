<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Debtor extends \Contact_Company
  {
    /**
     * @var int
     */
    public $id = 0;
    /**
     * @var string
     */
    public $name = 'New Customer';
    /**
     * @var
     */
    public $sales_type;
    /**
     * @var string
     */
    public $debtor_ref = '';
    /**
     * @var
     */
    public $credit_status;
    /**
     * @var int
     */
    public $payment_discount = 0;
    /**
     * @var int
     */
    public $defaultBranch = 0;
    /**
     * @var int
     */
    public $defaultContact = 0;
    /**
     * @var Debtor_Branch[]
     */
    public $branches = array();
    /**
     * @var Contact[]
     */
    public $contacts = array();
    /**
     * @var Debtor_Account
     */
    public $accounts;
    /**
     * @var
     */
    public $transactions;
    /**
     * @var null
     */
    public $webid = null;
    /**
     * @var
     */
    public $email;
    /**
     * @var
     */
    public $inactive;
    /**
     * @var
     */
    public $debtor_id;
    /**
     * @var
     */
    public $notes;
    /**
     * @var string
     */
    protected $_table = 'debtors';
    /**
     * @var string
     */
    protected $_id_column = 'debtor_id';
    /**
     * @param int|null $id
     */
    public function __construct($id = null)
    {
      $this->debtor_id =& $this->id;
      parent::__construct($id);
      $this->debtor_ref = substr($this->name, 0, 60);
    }
    /**
     * @param bool $string
     *
     * @return array|string
     */
    public function getStatus($string = false)
    {
      foreach ($this->branches as $branch) {
        /** @var Debtor_Branch $branch */
        $this->_status->append($branch->getStatus());
      }
      foreach ($this->contacts as $contact) {
        /** @var Contact $contact */
        $this->_status->append($contact->getStatus());
      }
      $this->_status->append($this->accounts->getStatus());

      return parent::getStatus();
    }
    /**
     * @param null $details
     *
     * @return void
     */
    public function addBranch($details = null)
    {
      $branch            = new Debtor_Branch($details);
      $branch->debtor_id = $this->id;
      $branch->save();
      $this->branches[$branch->branch_id] = $branch;
    }
    /**
     * @return array|null
     */
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
      if ($this->_countContacts() > 0) {
        return $this->_status(false, 'delete', "Cannot delete this customer because there are contact records set up against it.");
      }
      $sql = "DELETE FROM debtors WHERE debtor_id=" . $this->id;
      DB::query($sql, "cannot delete customer");
      unset($this->id);
      $this->_new();

      return $this->_status(true, 'delete', "Customer deleted.");
    }
    /**
     * @return array|bool
     */
    public function getEmailAddresses()
    {
      $emails = array();
      if (!empty($this->accounts->email)) {
        $emails['Accounts'][$this->accounts->id] = array('Accounts', $this->accounts->email);
      }
      foreach ($this->contacts as $id => $contact) {
        if ($id > 0 && !empty($contact->email)) {
          $emails['Contacts'][$id] = array($contact->name, $contact->email);
        }
      }
      foreach ($this->branches as $id => $branch) {
        if ($id > 0 && !empty($branch->email)) {
          $emails['Branches'][$id] = array($branch->name, $branch->email);
        }
      }

      return (count($emails) > 0) ? $emails : false;
    }
    /**
     * @return array
     */
    public function getTransactions()
    {
      if ($this->id == 0) {
        return array();
      }
      $sql
               = "SELECT debtor_trans.*, sales_orders.customer_ref,
                        (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
                        debtor_trans.ov_freight_tax + debtor_trans.ov_discount)
                        AS TotalAmount, debtor_trans.alloc AS Allocated
                        FROM debtor_trans LEFT OUTER JOIN sales_orders ON debtor_trans.order_ = sales_orders.order_no
                     WHERE debtor_trans.debtor_id = " . DB::escape($this->id) . "
                      AND sales_orders.debtor_id = " . DB::escape($this->id) . "
                         AND debtor_trans.type <> " . ST_CUSTDELIVERY . "
                         AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
                        debtor_trans.ov_freight_tax + debtor_trans.ov_discount) != 0
                         ORDER BY debtor_trans.branch_id, debtor_trans.tran_date";
      $result  = DB::query($sql);
      $results = array();
      while ($row = DB::fetch_assoc($result)) {
        $results[] = $row;
      }

      return $results;
    }
    /**
     * @param array|null $changes
     *
     * @return array|bool|int|null|void
     */
    public function save($changes = null)
    {
      $data['debtor_ref']       = substr($this->name, 0, 29);
      $data['discount']         = User::numeric($this->discount) / 100;
      $data['payment_discount'] = User::numeric($this->payment_discount) / 100;
      $data['credit_limit']     = User::numeric($this->credit_limit);
      if (!parent::save($changes)) {
        $this->_setDefaults();

        return false;
      }
      $this->accounts->save(array('debtor_id' => $this->id));
      foreach ($this->branches as $branch_id => $branch) {
        /** @var Debtor_Branch $branch */
        $branch->save(array('debtor_id' => $this->id));
        if ($branch_id == 0) {
          $this->branches[$branch->branch_id] = $branch;
          unset($this->branches[0]);
        }
      }
      $contacts       = $this->contacts;
      $this->contacts = [];
      foreach ($contacts as $contact) {
        $wasnew = $contact->save(array('parent_id' => (int) $this->id));
        if ($wasnew) {
          $this->contacts[] = $contact;
        }
      }

      return $this->_setDefaults();
    }
    /**
     * @param null $changes
     *
     * @return array|null|void
     */
    protected function setFromArray($changes = null)
    {
      parent::setFromArray($changes);
      if (isset($changes['accounts']) && is_array($changes['accounts'])) {
        $this->accounts = new Debtor_Account($changes['accounts']);
      }
      if (isset($changes['branches']) && is_array($changes['branches'])) {
        foreach ($changes['branches'] as $branchid => $branch) {
          $this->branches[$branchid] = new Debtor_Branch($branch);
        }
      }
      if (isset($changes['contacts']) && is_array($changes['contacts'])) {
        foreach ($changes['contacts'] as $id => $contact) {
          $this->contacts[$id] = new Contact(CT_CUSTOMER, $contact);
        }
      }
      $this->credit_limit = str_replace(',', '', $this->credit_limit);
    }
    /**
     * @return array|bool|null
     */
    protected function _canProcess()
    {
      if (strlen($this->name) == 0) {
        return $this->_status(false, 'Processing', "The customer name cannot be empty.", 'name');
      }
      if (strlen($this->debtor_ref) == 0) {
        $data['debtor_ref'] = substr($this->name, 0, 29);
      }
      if (!Validation::is_num($this->credit_limit, 0)) {
        JS::set_focus('credit_limit');

        return $this->_status(false, 'Processing', "The credit limit must be numeric and not less than zero.", 'credit_limit');
      }
      if (!Validation::is_num($this->payment_discount, 0, 100)) {
        JS::set_focus('payment_discount');

        return $this->_status(false, 'Processing', "The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'payment_discount');
      }
      if (!Validation::is_num($this->discount, 0, 100)) {
        JS::set_focus('discount');

        return $this->_status(false, 'Processing', "The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'discount');
      }
      if (Validation::is_num($this->webid, 0)) {
        $this->webid = null;
      }
      if ($this->id != 0) {
        $previous = new Debtor($this->id);
        if ((filter_var($this->credit_limit, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) != filter_var($previous->credit_limit, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) || $this->payment_terms != $previous->payment_terms) && !User::i()
          ->can_access(SA_CUSTOMER_CREDIT)
        ) {
          return $this->_status(false, 'Processing', "You don't have access to alter credit limits", 'credit_limit');
        }
      }

      return true;
    }
    /**
     * @return int
     */
    protected function _countBranches()
    {
      DB::select('COUNT(*)')->from('branches')->where('debtor_id=', $this->id);

      return DB::num_rows();
    }
    /**
     * @return int
     */
    protected function _countContacts()
    {
      DB::select('COUNT(*)')->from('contacts')->where('debtor_id=', $this->id);

      return DB::num_rows();
    }
    /**
     * @return int
     */
    protected function _countOrders()
    {
      DB::select('COUNT(*)')->from('sales_orders')->where('debtor_id=', $this->id);

      return DB::num_rows();
    }
    /**
     * @return int|mixed
     */
    protected function _countTransactions()
    {
      DB::select('COUNT(*)')->from('debtor_trans')->where('debtor_id=', $this->id);

      return (int) DB::num_rows();
    }
    /**
     * @return void
     */
    protected function _defaults()
    {
      $this->payment_terms = $this->dimension_id = $this->dimension2_id = $this->inactive = 0;
      $this->sales_type    = $this->credit_status = 1;
      $this->name          = $this->address = $this->email = $this->tax_id = $this->notes = $this->debtor_ref = '';
      $this->curr_code     = Bank_Currency::for_company();
      $this->discount      = $this->payment_discount = Num::percent_format(0);
      $this->credit_limit  = Num::price_format(DB_Company::get_pref('default_credit_limit'));
    }
    protected function _getAccounts()
    {
      DB::select()->from('branches')->where('debtor_id=', $this->debtor_id)->and_where('branch_ref=', 'accounts');
      $this->accounts = DB::fetch()->asClassLate('Debtor_Account')->one();
      if (!$this->accounts && $this->id > 0 && $this->defaultBranch > 0) {
        $this->accounts          = clone($this->branches[$this->defaultBranch]);
        $this->accounts->br_name = 'Accounts Department';
        $this->accounts->save();
      }
    }
    protected function _getBranches()
    {
      DB::select()->from('branches')->where('debtor_id=', $this->debtor_id)->where('branch_ref !=', 'accounts');
      $branches = DB::fetch()->asClassLate('Debtor_Branch');
      foreach ($branches as $branch) {
        $this->branches[$branch->branch_id] = $branch;
      }
      $this->defaultBranch = reset($this->branches)->id;
    }
    /**
     * @return void
     */
    protected function _getContacts()
    {
      DB::select()->from('contacts')->where('parent_id=', $this->id)->and_where('parent_type =', CT_CUSTOMER)
        ->orderby('name ASC');
      $contacts = DB::fetch()->asClassLate('Contact', array(CT_CUSTOMER));
      if (count($contacts)) {
        foreach ($contacts as $contact) {
          $this->contacts[] = $contact;
        }
        $this->defaultContact = reset($this->contacts)->id;
      }
    }
    /**
     * @return array|null
     */
    protected function _new()
    {
      $this->_defaults();
      $this->accounts               = new Debtor_Account();
      $this->branches[0]            = new Debtor_Branch();
      $this->branches[0]->debtor_id = $this->accounts->debtor_id = $this->id = 0;
      $this->_setDefaults();

      return $this->_status(true, 'Initialize', 'Now working with a new customer');
    }
    /**
     * @param bool|int|null $id
     *
     * @return array|bool
     */
    protected function _read($id = null, $extra = array())
    {
      if (!parent::_read($id)) {
        return $this->_status->get();
      }
      $this->_getBranches();
      $this->_getAccounts();
      $this->_getContacts();
      $this->discount         = $this->discount * 100;
      $this->payment_discount = $this->payment_discount * 100;
      $this->credit_limit     = Num::price_format($this->credit_limit);
      $this->_setDefaults();
    }
    /**
     * @return void
     */
    protected function _setDefaults()
    {
      $this->defaultBranch  = reset($this->branches)->branch_id;
      $this->defaultContact = count($this->contacts) ? reset($this->contacts)->id : 0;
      $this->contacts[]     = new Contact(CT_CUSTOMER, array('parent_id' => $this->id));
    }
    /**
     * @static
     * @return void
     */
    public static function addEditDialog()
    {
      $customerBox = new Dialog('Customer Edit', 'customerBox', '');
      $customerBox->addButtons(array('Close' => '$(this).dialog("close");'));
      $customerBox->addBeforeClose('$("#customer_id").trigger("change")');
      $customerBox->setOptions(array(
                                    'autoOpen'   => false,
                                    'modal'      => true,
                                    'width'      => '850',
                                    'height'     => '715',
                                    'resizeable' => true
                               ));
      $customerBox->show();
      $js
        = <<<JS
                            var val = $("#customer_id").val();
                            $("#customerBox").html("<iframe src='/contacts/customers.php?frame=1&id="+val+"' width='100%' height='595' scrolling='no' style='border:none' frameborder='0'></iframe>").dialog('open');
JS;
      JS::addLiveEvent('#customer_id_label', 'click', $js);
    }
    /**
     * @static
     *
     * @param       $id
     * @param array $options
     *
     * @return void
     */
    public static function addSearchBox($id, $options = array())
    {
      echo UI::searchLine($id, '/contacts/search.php', $options);
    }
    /**
     * @static
     *
     * @param $terms
     *
     * @return array
     */
    public static function search($terms)
    {
      $data  = array();
      $terms = preg_replace("/[^a-zA-Z 0-9]+/", " ", $terms);
      $sql   = DB::select('debtor_id as id', 'name as label', 'name as value', "IF(name LIKE " . DB::quote(trim($terms) . '%') . ",0,5) as weight")
        ->from('debtors')->where('name LIKE ', trim($terms) . "%")->or_where('name LIKE ', trim($terms))
        ->or_where('name LIKE', '%' . str_replace(' ', '%', trim($terms)) . "%");
      if (is_numeric($terms)) {
        $sql->or_where('debtor_id LIKE', "$terms%");
      }
      $sql->orderby('weight,name')->limit(20);
      $results = DB::fetch();
      foreach ($results as $result) {
        $data[] = @array_map('htmlspecialchars_decode', $result);
      }

      return $data;
    }
    /**
     * @static
     *
     * @param       $term
     * @param array $options
     *
     * @return array|string
     */
    public static function searchOrder($term, $options = array())
    {
      $defaults = array('inactive' => false, 'selected' => '');
      $o        = array_merge($defaults, $options);
      $term     = explode(' ', $term);
      $term1    = DB::escape(trim($term[0]) . '%');
      $term2    = DB::escape('%' . implode(' AND name LIKE ', array_map(function($v)
      {
        return trim($v);
      }, $term)) . '%');
      $where    = ($o['inactive'] ? '' : ' AND inactive = 0 ');
      $sql
                = "(SELECT debtor_id as id, name as label, debtor_id as value, name as description FROM debtors WHERE name LIKE $term1 $where ORDER BY name LIMIT 20)
                                    UNION (SELECT debtor_id as id, name as label, debtor_id as value, name as description FROM debtors
                                    WHERE debtor_ref LIKE $term1 OR name LIKE $term2 OR debtor_id LIKE $term1 $where ORDER BY debtor_id, name LIMIT 20)";
      $result   = DB::query($sql, 'Couldn\'t Get Customers');
      $data     = '';
      while ($row = DB::fetch_assoc($result)) {
        foreach ($row as &$value) {
          $value = htmlspecialchars_decode($value);
        }
        $data[] = $row;
      }

      return $data;
    }
    /**
     * @static
     *
     * @param      $customer_id
     * @param null $to
     * @param bool $istimestamp
     *
     * @return Array|\DB_Query_Result
     */
    public static function get_details($customer_id, $to = null, $istimestamp = false)
    {
      if ($to == null) {
        $todate = date("Y-m-d");
      } else {
        $todate = ($istimestamp) ? date("Y-m-d", $to) : Dates::date2sql($to);
      }
      $customer_record["Balance"]  = 0;
      $customer_record["Due"]      = 0;
      $customer_record["Overdue1"] = 0;
      $customer_record["Overdue2"] = 0;
      if ($customer_id == 0) {
        return $customer_record;
      }
      $past_due1 = DB_Company::get_pref('past_due_days');
      $past_due2 = 2 * $past_due1;
      // removed - debtor_trans.alloc from all summations
      $value
           = "IF(debtor_trans.type=11 OR debtor_trans.type=1 OR debtor_trans.type=12 OR debtor_trans.type=2,
        -1, 1) *" . "(debtor_trans.ov_amount + debtor_trans.ov_gst + " . "debtor_trans.ov_freight + debtor_trans.ov_freight_tax + " . "debtor_trans.ov_discount)";
      $due = "IF (debtor_trans.type=10,debtor_trans.due_date,debtor_trans.tran_date)";
      $sql
              = "SELECT debtors.name, debtors.curr_code, payment_terms.terms,		debtors.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description,
            Sum(" . $value . ") AS Balance,
            Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
            Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due1,$value,0)) AS Overdue1,
            Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past_due2,$value,0)) AS Overdue2
            FROM debtors,
                 payment_terms,
                 credit_status,
                 debtor_trans
            WHERE
                 debtors.payment_terms = payment_terms.terms_indicator
                 AND debtors.credit_status = credit_status.id
                 AND debtors.debtor_id = " . DB::escape($customer_id) . "
                 AND debtor_trans.tran_date <= '$todate'
                 AND debtor_trans.type <> 13
                 AND debtors.debtor_id = debtor_trans.debtor_id
            GROUP BY
                 debtors.name,
                 payment_terms.terms,
                 payment_terms.days_before_due,
                 payment_terms.day_in_following_month,
                 debtors.credit_limit,
                 credit_status.dissallow_invoices,
                 credit_status.reason_description";
      $result = DB::query($sql, "The customer details could not be retrieved");
      if (DB::num_rows($result) == 0) {
        /* Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */
        $nil_balance = true;
        $sql
                     = "SELECT debtors.name, debtors.curr_code, debtors.debtor_id, payment_terms.terms,
             debtors.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description
             FROM debtors,
              payment_terms,
              credit_status

             WHERE
              debtors.payment_terms = payment_terms.terms_indicator
              AND debtors.credit_status = credit_status.id
              AND debtors.debtor_id = " . DB::escape($customer_id);
        $result      = DB::query($sql, "The customer details could not be retrieved");
      } else {
        $customer_record = DB::fetch($result);
      }

      return $customer_record;
    }
    /**
     * @static
     *
     * @param $customer_id
     *
     * @return Array|DB_Query_Result
     */
    public static function get($customer_id)
    {
      $sql    = "SELECT * FROM debtors WHERE debtor_id=" . DB::escape($customer_id);
      $result = DB::query($sql, "could not get customer");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $customer_id
     *
     * @return mixed
     */
    public static function get_name($customer_id)
    {
      $sql    = "SELECT name FROM debtors WHERE debtor_id=" . DB::escape($customer_id);
      $result = DB::query($sql, "could not get customer");
      $row    = DB::fetch_row($result);

      return $row[0];
    }

    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_area($id)
    {
      $sql    = "SELECT description FROM areas WHERE area_code=" . DB::escape($id);
      $result = DB::query($sql, "could not get sales type");
      $row    = DB::fetch_row($result);

      return $row[0];
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    public static function get_salesman_name($id)
    {
      $sql    = "SELECT salesman_name FROM salesman WHERE salesman_code=" . DB::escape($id);
      $result = DB::query($sql, "could not get sales type");
      $row    = DB::fetch_row($result);

      return $row[0];
    }
    /**
     * @static
     *
     * @param $customer_id
     *
     * @return int
     */
    public static function get_credit($customer_id)
    {
      $custdet = Debtor::get_details($customer_id);

      return ($customer_id > 0 && isset ($custdet['credit_limit'])) ? $custdet['credit_limit'] - $custdet['Balance'] : 0;
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    public static function is_new($id)
    {
      $tables = array('branches', 'debtor_trans', 'recurrent_invoices', 'sales_orders');

      return !DB_Company::key_in_foreign_table($id, $tables, 'debtor_id');
    }
    /**
     * @static
     *
     * @param null $value
     *
     * @return void
     */
    public static function newselect($value = null)
    {
      echo "<tr><td id='customer_id_label' class='label pointer'>Customer: </td><td class='nowrap'>";
      $focus = false;
      if (!$value && Input::post('customer')) {
        $value = $_POST['customer'];
        JS::set_focus('stock_id');
      } elseif (!$value) {
        $value = Session::i()->getGlobal('debtor');
        if ($value) {
          $_POST['customer_id'] = $value;
          $value                = Debtor::get_name($value);
        } else {
          JS::set_focus('customer');
          $focus = true;
        }
      }
      Forms::hidden('customer_id');
      UI::search('customer', array(
                                  'url'  => '/contacts/customers.php', 'name'  => 'customer', 'focus' => $focus, 'value' => $value
                             ));
      echo "</td>\n</tr>\n";
      JS::beforeload("var Customer = function(data) {
            var id = document.getElementById('customer_id');
            id.value= data.id;
            var customer = document.getElementById('customer');
            customer.value=data.value;
            JsHttpRequest.request(customer)}");
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_option
     * @param bool $submit_on_change
     * @param bool $show_inactive
     * @param bool $editkey
     * @param bool $async
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false)
    {
      $sql  = "SELECT debtor_id, debtor_ref, curr_code, inactive FROM debtors ";
      $mode = DB_Company::get_pref('no_customer_list');

      return Forms::selectBox($name, $selected_id, $sql, 'debtor_id', 'name', array(
                                                                                  'format'        => 'Forms::addCurrFormat',
                                                                                  'order'         => array('debtor_ref'),
                                                                                  'search_box'    => $mode != 0,
                                                                                  'type'          => 1,
                                                                                  'size'          => 20,
                                                                                  'spec_option'   => $spec_option === true ?
                                                                                    _("All Customers") : $spec_option,
                                                                                  'spec_id'       => ALL_TEXT,
                                                                                  'select_submit' => $submit_on_change,
                                                                                  'async'         => $async,
                                                                                  'sel_hint'      => $mode ?
                                                                                    _('Press Space tab to filter by name fragment; F2 - entry new customer') :
                                                                                    _('Select customer'),
                                                                                  'show_inactive' => $show_inactive
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
     * @param bool $show_inactive
     * @param bool $editkey
     * @param bool $async
     *
     * @return void
     */
    public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false)
    {
      echo "<td class='nowrap'>";
      if ($label != null) {
        echo "<label for=\"$name\"> $label</label>";
      }
      echo Debtor::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey, $async);
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
     * @param bool $show_inactive
     * @param bool $editkey
     *
     * @return void
     */
    public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false)
    {
      echo "<tr><td id='customer_id_label' class='label pointer'>$label</td><td class='nowrap'>";
      echo Debtor::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
      echo "</td>\n</tr>\n";
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     *
     * @return null|string
     */
    public static function trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '')
    {
      $viewer = "/sales/view/";
      switch ($type) {
        case ST_SALESINVOICE:
          $viewer .= "view_invoice";
          break;
        case ST_CUSTCREDIT:
          $viewer .= "view_credit";
          break;
        case ST_CUSTPAYMENT:
          $viewer .= "view_receipt";
          break;
        case ST_CUSTREFUND:
          $viewer .= "view_receipt";
          break;
        case ST_CUSTDELIVERY:
          $viewer .= "view_dispatch";
          break;
        case ST_SALESORDER:
        case ST_SALESQUOTE:
          $viewer .= "view_sales_order";
          break;
        default:
          return null;
      }
      if (!is_array($trans_no)) {
        $trans_no = array($trans_no);
      }
      $lbl         = $label;
      $preview_str = '';
      foreach ($trans_no as $trans) {
        if ($label == "") {
          $lbl = $trans;
        }
        if ($preview_str != '') {
          $preview_str .= ',';
        }
        $preview_str .= Display::viewer_link($lbl, $viewer . "?trans_no=$trans&trans_type=$type", $class, $id, $icon);
      }

      return $preview_str;
    }
    /**
     * @param $customer_record
     *
     * @return void
     */
    public static function display_summary($customer_record)
    {
      $past_due1 = DB_Company::get_pref('past_due_days');
      $past_due2 = 2 * $past_due1;
      if (isset($customer_record["dissallow_invoices"]) && $customer_record["dissallow_invoices"] != 0) {
        echo "<div class='center red font4 bold'>" . _("CUSTOMER ACCOUNT IS ON HOLD") . "</div>";
      }
      $txt_now_due   = "1-" . $past_due1 . " " . _('Days');
      $txt_past_due1 = $past_due1 + 1 . "-" . $past_due2 . " " . _('Days');
      $txt_past_due2 = _('Over') . " " . $past_due2 . " " . _('Days');
      Table::start('tablestyle width90');
      $th = array(_("Currency"), _("Terms"), _("Current"), $txt_now_due, $txt_past_due1, $txt_past_due2, _("Total Balance"));
      Table::header($th);
      Row::start();
      if (isset($customer_record["curr_code"])) {
        Cell::label($customer_record["curr_code"]);
      } else {
        unset($th[0]);
      }
      if (isset($customer_record["curr_code"])) {
        Cell::label($customer_record["terms"]);
      } else {
        unset($th[0]);
      }
      Cell::amount($customer_record["Balance"] - $customer_record["Due"]);
      Cell::amount($customer_record["Due"] - $customer_record["Overdue1"]);
      Cell::amount($customer_record["Overdue1"] - $customer_record["Overdue2"]);
      Cell::amount($customer_record["Overdue2"]);
      Cell::amount($customer_record["Balance"]);
      Row::end();
      Table::end();
    }
  }

