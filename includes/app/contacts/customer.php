<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 4:07 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Contacts_Customer extends Contacts_Company
	{
		public $debtor_no = 0;
		public $name = 'New Customer';
		public $sales_type;
		public $debtor_ref = '';
		public $credit_status;
		public $payment_discount = '0';
		public $defaultBranch = 0;
		public $defaultContact = 0;
		public $branches = array();
		public $contacts = array();
		public $accounts;
		public $transactions;

		function __construct($id = null)
			{
				$this->debtor_no = $id;
				$this->id = &$this->debtor_no;
				parent::__construct($id);
				$this->pymt_discount =& $this->payment_discount;
				$this->debtor_ref = substr($this->name, 0, 60);
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
				if (!Validation::is_num('credit_limit', 0)) {
					$this->_status(false, 'Processing', "The credit limit must be numeric and not less than zero.", 'credit_limit');
					return false;
				}
				if (!Validation::is_num('pymt_discount', 0, 100)) {
					$this->_status(false, 'Processing',
						"The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0.",
						'pymt_discount');
					return false;
				}
				if (!Validation::is_num('discount', 0, 100)) {
					$this->_status(false, 'Processing',
						"The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0.",
						'discount');
					return false;
				}
				if ($this->id != 0) {
					$previous = new Contacts_Customer($this->id);
					if (($this->credit_limit != $previous->credit_limit || $this->payment_terms != $previous->payment_terms) && !$_SESSION['current_user']->can_access('SA_CUSTOMER_CREDIT')
					) {
						$this->_status(false, 'Processing', "You don't have access to alter credit limits", 'credit_limit');
						return false;
					}
				}
				return true;
			}

		protected function setFromArray($changes = NULL)
			{
				parent::setFromArray($changes);
				if (isset($changes['accounts']) && is_array($changes['accounts'])) {
					$this->accounts = new Contacts_Accounts($changes['accounts']);
				}
				if (isset($changes['branches']) && is_array($changes['branches'])) {
					foreach ($changes['branches'] as $branchid => $branch) {
						$this->branches[$branchid] = new Contacts_Branch($branch);
					}
				}
				if (isset($changes['contacts']) && is_array($changes['contacts'])) {
					foreach ($changes['contacts'] as $id => $contact) {
						$this->contacts[$id] = new Contacts_Contact($contact);
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
					$status = $this->_saveNew();
				} else {
					DB::begin_transaction();
					$data = (array)$this;
					$data['debtor_ref'] = substr($this->name, 0, 29);
					$data['discount'] = User::numeric($this->discount) / 100;
					$data['pymt_discount'] = User::numeric($this->pymt_discount) / 100;
					$data['credit_limit'] = User::numeric($this->credit_limit);
					DB::update('debtors_master')->values($data)->where('debtor_no=', $this->id)->exec();
					DB::update_record_status($this->id, $this->inactive, 'debtors_master', 'debtor_no');
					DB::commit_transaction();
					$status = "Customer has been updated.";
				}
				$this->accounts->save(array('debtor_no' => $this->id));
				foreach ($this->branches as $branch_code => $branch) {
					$branch->save(array('debtor_no' => $this->id));
					if ($branch_code == 0) {
						$this->branches[$branch->branch_code] = $branch;
						unset($this->branches[0]);
					}
				}
				foreach ($this->contacts as $id => &$contact) {
					$contact->save(array('parent_id' => $this->id));
					$this->contacts[$contact->id] = $contact;
				}
				$this->_setDefaults();
				return $this->_status(true, 'Processing', $status);
			}

		protected
		function _setDefaults()
			{
				$this->defaultBranch = reset($this->branches)->branch_code;
				$this->defaultContact = (count($this->contacts) > 0) ? reset($this->contacts)->id : 0;
				$this->contacts[0] = new Contacts_Contact(array('parent_id' => $this->id));
			}

		protected
		function _saveNew()
			{
				DB::begin_transaction();
				$sql
				 = "INSERT INTO debtors_master (name, debtor_ref, address, tax_id, email, dimension_id, dimension2_id,
			curr_code, credit_status, payment_terms, discount, pymt_discount,credit_limit,
			sales_type, notes) VALUES (" . DB::escape($this->name) . ", " . DB::escape(substr($this->name, 0,
					29)) . ", " . DB::escape($this->address) . ", " . DB::escape($this->tax_id) . ",
			" . DB::escape($this->email) . ", " . DB::escape($this->dimension_id) . ", " . DB::escape($this->dimension2_id) . ", " . DB::escape($this->curr_code) . ", " . DB::escape($this->credit_status) . ", " . DB::escape($this->payment_terms) . ", " . User::numeric($this->discount) / 100 . "," .
				 User::numeric($this->pymt_discount) / 100 . ", " . User::numeric($this->credit_limit) . ", " . DB::escape($this->sales_type) . ", " . DB::escape($this->notes) . ")";
				DB::query($sql, "The customer could not be added");
				$this->id = DB::insert_id();
				DB::commit_transaction();
				foreach ($this->branches as $branch) {
					if ($branch->name == 'New Address') {
						$branch->name = $this->name;
					}
				}
				return "A Customer has been added.";
			}

		protected
		function _new()
			{
				$this->_defaults();
				$this->accounts = new Contacts_Accounts();
				$this->branches[0] = new Contacts_Branch();
				$this->contacts[0] = new Contacts_Contact();
				$this->branches[0]->debtor_no = $this->accounts->debtor_no = $this->contacts[0]->parent_id = $this->id = 0;
				$this->_setDefaults();
				return $this->_status(true, 'Initialize new customer', 'Now working with a new customer');
			}

		protected
		function _defaults()
			{
				$this->dimension_id = $this->dimension2_id = $this->inactive = 0;
				$this->sales_type = $this->credit_status = 1;
				$this->name = $this->address = $this->email = $this->tax_id = $this->payment_terms = $this->notes = $this->debtor_ref = '';
				$this->curr_code = Banking::get_company_currency();
				$this->discount = $this->pymt_discount = Num::percent_format(0);
				$this->credit_limit = Num::price_format(DB_Company::get_pref('default_credit_limit'));
			}

		protected
		function _read($id = false)
			{
				if ($id === false) {
					return $this->_status(false, 'read', 'No customer ID to read');
				}
				$this->_defaults();
				DB::select()->from('debtors_master')->where('debtor_no=', $id);
				DB::fetch()->intoClass($this);
				$this->_getBranches();
				$this->_getAccounts();
				$this->_getContacts();
				$this->discount = $this->discount * 100;
				$this->pymt_discount = $this->pymt_discount * 100;
				$this->credit_limit = Num::price_format($this->credit_limit);
			}

		function _countTransactions()
			{
				DB::select('COUNT(*)')->from('debtor_trans')->where('debtor_no=', $this->id);
				return DB::rowCount();
			}

		public
		function getTransactions()
			{
				if ($this->id == 0) {
					return;
				}
				$sql
				 = "SELECT debtor_trans.*, sales_orders.customer_ref,
				(debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount)
				AS TotalAmount, debtor_trans.alloc AS Allocated
				FROM debtor_trans LEFT OUTER JOIN sales_orders ON  debtor_trans.order_ =  sales_orders.order_no
    			WHERE  debtor_trans.debtor_no = " . DB::escape($this->id) . "
    			 AND sales_orders.debtor_no = " . DB::escape($this->id) . "
    				AND debtor_trans.type <> " . ST_CUSTDELIVERY . "
    				AND (debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
				debtor_trans.ov_freight_tax + debtor_trans.ov_discount) != 0
    				ORDER BY debtor_trans.branch_code, debtor_trans.tran_date";
				$result = DB::query($sql);
				$results = array();
				while ($row = DB::fetch_assoc($result)) {
					$results[] = $row;
				}
				return $results;
			}

		protected
		function _countOrders()
			{
				DB::select('COUNT(*)')->from('sales_orders')->where('debtor_no=', $this->id);
				return DB::rowCount();
			}

		protected
		function _countBranches()
			{
				DB::select('COUNT(*)')->from('cust_branch')->where('debtor_no=', $this->id);
				return DB::rowCount();
			}

		protected
		function _countContacts()
			{
				DB::select('COUNT(*)')->from('contacts')->where('debtor_no=', $this->id);
				return DB::rowCount();
			}

		public
		function delete()
			{
				if ($this->_countTransactions() > 0) {
					return $this->_status(false, 'delete',
						"This customer cannot be deleted because there are transactions that refer to it.");
				}
				if ($this->_countOrders() > 0) {
					return $this->_status(false, 'delete',
						"Cannot delete the customer record because orders have been created against it.");
				}
				if ($this->_countBranches() > 0) {
					return $this->_status(false, 'delete',
						"Cannot delete this customer because there are branch records set up against it.");
				}
				if ($this->_countContacts() > 0) {
					return $this->_status(false, 'delete',
						"Cannot delete this customer because there are contact records set up against it.");
				}
				$sql = "DELETE FROM debtors_master WHERE debtor_no=" . $this->id;
				DB::query($sql, "cannot delete customer");
				unset($this->id);
				$this->_new();
				return $this->_status(true, 'delete', "Customer deleted.");
			}

		protected
		function _getAccounts()
			{
				DB::select()->from('cust_branch')->where('debtor_no=', $this->debtor_no)->and_where('branch_ref=', 'accounts');
				$this->accounts = DB::fetch()->asClassLate('Contacts_Accounts')->all();
				if (!$this->accounts && $this->id > 0 && $this->defaultBranch > 0) {
					$this->accounts = clone($this->branches[$this->defaultBranch]);
					$this->accounts->br_name = 'Accounts Department';
					$this->accounts->save();
				} else {
					$this->accounts = $this->accounts[0];
				}
			}

		protected
		function _getBranches()
			{
				DB::select()
				 ->from('cust_branch')
				 ->where('debtor_no=', $this->debtor_no)
				 ->where('branch_ref !=', 'accounts');
				$branches = DB::fetch()->asClassLate('Contacts_Branch');
				foreach ($branches as $branch) {
					$this->branches[$branch->branch_code] = $branch;
				}
				$this->defaultBranch = reset($this->branches)->id;
			}

		protected
		function _getContacts()
			{
				DB::select()->from('contacts')->where('parent_id=', $this->debtor_no);
				$contacts = DB::fetch()->asClassLate('Contacts_Contact');
				if (count($contacts)) {
					foreach ($contacts as $contact) {
						$this->contacts[$contact->id] = $contact;
					}
					$this->defaultContact = reset($this->contacts)->id;
				}
				$this->contacts[0] = new Contacts_Contact(array('parent_id' => $this->id));
			}

		public
		function addBranch($details = null)
			{
				$branch = new Contacts_Branch($details);
				$branch->debtor_no = $this->id;
				$branch->save();
				$this->branches[$branch->branch_code] = $branch;
			}

		public
		function getEmailAddresses()
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

		public static function search($terms)
			{
				$data = array();
				DB::select('debtor_no as id', 'name as label', 'name as value')
				 ->from('debtors_master')->where('name LIKE ', "$terms%")->limit(20)
				 ->union()->select('debtor_no as id', 'name as label', 'name as value')
				 ->from('debtors_master')->where('debtor_ref LIKE', "%$terms%")
				 ->or_where('name LIKE', "%" . str_replace(' ', "%' AND name LIKE '%", trim($terms)) . "%")
				 ->or_where('debtor_no LIKE', "%$terms%")->limit(20)->union();
				$results = DB::fetch();
				foreach ($results as $result) {
					$data[] = @array_map('htmlspecialchars_decode', $result);
				}
				return $data;
			}

		static function searchOrder($term, $options = array())
			{
				$defaults = array('inactive' => false, 'selected' => '');
				$o = array_merge($defaults, $options);
				$term = explode(' ', $term);
				$term1 = DB::escape(trim($term[0]) . '%');
				$term2 = DB::escape('%' . implode(' AND name LIKE ', array_map(function($v)
					{
						return trim($v);
					}, $term)) . '%');
				$where = ($o['inactive'] ? '' : ' AND inactive = 0 ');
				$sql
				 = "(SELECT debtor_no as id, name as label, debtor_no as value, name as description FROM debtors_master WHERE name LIKE $term1 $where ORDER BY name LIMIT 20)
						UNION (SELECT debtor_no as id, name as label, debtor_no as value, name as description FROM debtors_master
						WHERE debtor_ref LIKE $term1 OR name LIKE $term2 OR debtor_no LIKE $term1 $where ORDER BY debtor_no, name LIMIT 20)";
				$result = DB::query($sql, 'Couldn\'t Get Customers');
				$data = '';
				while ($row = DB::fetch_assoc($result)) {
					foreach ($row as &$value) {
						$value = htmlspecialchars_decode($value);
					}
					$data[] = $row;
				}
				return $data;
			}

		static function addSearchBox($id, $options = array())
			{
				echo UI::searchLine($id, '/contacts/search.php', $options);
			}

		static function addEditDialog()
			{
				$customerBox = new Dialog('Customer Edit', 'customerBox', '');
				$customerBox->addButtons(array('Close' => '$(this).dialog("close");'));
				$customerBox->addBeforeClose('$("#customer_id").trigger("change")');
				$customerBox->setOptions(array(
																			'autoOpen' => false, 'modal' => true, 'width' => '850', 'height' => '715', 'resizeable' => true));
				$customerBox->show();
				$js = <<<JS
				var val = $("#customer_id").val();
				$("#customerBox").html("<iframe src='/contacts/customers.php?popup=1&id="+val+"' width='100%' height='595' scrolling='no' style='border:none' frameborder='0'></iframe>").dialog('open');
JS;
				JS::addLiveEvent('#customer_id_label', 'click', $js);
			}
	}
