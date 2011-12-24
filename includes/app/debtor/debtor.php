<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 4:07 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Debtor extends Contact_Company {
		public $debtor_no = 0;
		public $name = 'New Customer';
		public $sales_type;
		public $debtor_ref = '';
		public $credit_status;
		public $payment_discount = 0;
		public $pymt_discount;
		public $defaultBranch = 0;
		public $defaultContact = 0;
		public $branches = array();
		public $contacts = array();
		public $accounts;
		public $transactions;
		public $webid = null;
		protected $_table = 'debtors_master';
		protected $_id_column = 'debtor_no';

		public function __construct($id = null) {
			$this->id = &$this->debtor_no;
			$this->pymt_discount =& $this->payment_discount;
			parent::__construct($id);
			$this->debtor_ref = substr($this->name, 0, 60);
		}
public function getStatus($string=false) {
	foreach ($this->branches as $branch) {
		$this->_status->append($branch->getStatus());
	}foreach ($this->contacts as $contact) {
			$this->_status->append($contact->getStatus());
	}
	$this->_status->append($this->accounts->getStatus());
	return parent::getStatus();
}
		public function addBranch($details = null) {
			$branch = new Debtor_Branch($details);
			$branch->debtor_no = $this->id;
			$branch->save();
			$this->branches[$branch->branch_code] = $branch;
		}

		public function delete() {
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
			$sql = "DELETE FROM debtors_master WHERE debtor_no=" . $this->id;
			DB::query($sql, "cannot delete customer");
			unset($this->id);
			$this->_new();
			return $this->_status(true, 'delete', "Customer deleted.");
		}

		public function getEmailAddresses() {
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

		public function getTransactions() {
			if ($this->id == 0) {
				return;
			}
			$sql = "SELECT debtor_trans.*, sales_orders.customer_ref,
						(debtor_trans.ov_amount + debtor_trans.ov_gst + debtor_trans.ov_freight +
						debtor_trans.ov_freight_tax + debtor_trans.ov_discount)
						AS TotalAmount, debtor_trans.alloc AS Allocated
						FROM debtor_trans LEFT OUTER JOIN sales_orders ON debtor_trans.order_ = sales_orders.order_no
		 			WHERE debtor_trans.debtor_no = " . DB::escape($this->id) . "
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

		public function save($changes = null) {

			$data['debtor_ref'] = substr($this->name, 0, 29);
			$data['discount'] = User::numeric($this->discount) / 100;
			$data['pymt_discount'] = User::numeric($this->pymt_discount) / 100;
			$data['credit_limit'] = User::numeric($this->credit_limit);
			if (!parent::save($changes)) {
				 $this->_setDefaults();
				return false;
			}
			$this->accounts->save(array('debtor_no' => $this->id));
			foreach ($this->branches as $branch_code => $branch) {
				$branch->save(array('debtor_no' => $this->id));
				if ($branch_code == 0) {
					$this->branches[$branch->branch_code] = $branch;
					unset($this->branches[0]);
				}
			}
			foreach ($this->contacts as $contact) {
				$contact->save(array('parent_id' => $this->id));
				$this->contacts[$contact->id] = $contact;
			}
			return $this->_setDefaults();
		}

		protected function setFromArray($changes = NULL) {
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
					$this->contacts[$id] = new Contact($contact);
				}
			}
		}

		protected function _canProcess() {
			if (strlen($this->name) == 0) {
				return $this->_status(false, 'Processing', "The customer name cannot be empty.", 'name');
			}
			if (strlen($this->debtor_ref) == 0) {
				$data['debtor_ref'] = substr($this->name, 0, 29);
			}
			if (!is_numeric($this->credit_limit)) {
				return $this->_status(false, 'Processing', "The credit limit must be numeric and not less than zero.", 'credit_limit');
			}
			if (!is_numeric($this->pymt_discount)) {
				return $this->_status(false, 'Processing', "The payment discount must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'pymt_discount');
			}
			if (!is_numeric($this->discount)) {
				return $this->_status(false, 'Processing', "The discount percentage must be numeric and is expected to be less than 100% and greater than or equal to 0.", 'discount');
			}
			if (!is_numeric($this->webid)) {
				$this->webid = null;
			}
			if ($this->id != 0) {
				$previous = new Debtor($this->id);
				if (($this->credit_limit != $previous->credit_limit || $this->payment_terms != $previous->payment_terms) && !$_SESSION['current_user']->can_access('SA_CUSTOMER_CREDIT')
				) {
					return $this->_status(false, 'Processing', "You don't have access to alter credit limits", 'credit_limit');
				}
			}
			return true;
		}

		protected function _countBranches() {
			DB::select('COUNT(*)')->from('cust_branch')->where('debtor_no=', $this->id);
			return DB::rowCount();
		}

		protected function _countContacts() {
			DB::select('COUNT(*)')->from('contacts')->where('debtor_no=', $this->id);
			return DB::rowCount();
		}

		protected function _countOrders() {
			DB::select('COUNT(*)')->from('sales_orders')->where('debtor_no=', $this->id);
			return DB::rowCount();
		}

		protected function _countTransactions() {
			DB::select('COUNT(*)')->from('debtor_trans')->where('debtor_no=', $this->id);
			return DB::rowCount();
		}

		protected function _defaults() {
			$this->dimension_id = $this->dimension2_id = $this->inactive = 0;
			$this->sales_type = $this->credit_status = 1;
			$this->name = $this->address = $this->email = $this->tax_id = $this->payment_terms = $this->notes = $this->debtor_ref = '';
			$this->curr_code = Bank_Currency::for_company();
			$this->discount = $this->pymt_discount = Num::percent_format(0);
			$this->credit_limit = Num::price_format(DB_Company::get_pref('default_credit_limit'));
		}

		protected function _getAccounts() {
			DB::select()->from('cust_branch')->where('debtor_no=', $this->debtor_no)->and_where('branch_ref=', 'accounts');
			$this->accounts = DB::fetch()->asClassLate('Debtor_Account')->all();
			if (!$this->accounts && $this->id > 0 && $this->defaultBranch > 0) {
				$this->accounts = clone($this->branches[$this->defaultBranch]);
				$this->accounts->br_name = 'Accounts Department';
				$this->accounts->save();
			} else {
				$this->accounts = $this->accounts[0];
			}
		}

		protected function _getBranches() {
			DB::select()
			 ->from('cust_branch')
			 ->where('debtor_no=', $this->debtor_no)
			 ->where('branch_ref !=', 'accounts');
			$branches = DB::fetch()->asClassLate('Debtor_Branch');
			foreach ($branches as $branch) {
				$this->branches[$branch->branch_code] = $branch;
			}
			$this->defaultBranch = reset($this->branches)->id;
		}

		protected function _getContacts() {
			DB::select()->from('contacts')->where('parent_id=', $this->debtor_no);
			$contacts = DB::fetch()->asClassLate('Contact');
			if (count($contacts)) {
				foreach ($contacts as $contact) {
					$this->contacts[$contact->id] = $contact;
				}
				$this->defaultContact = reset($this->contacts)->id;
			}
			$this->contacts[0] = new Contact(array('parent_id' => $this->id));
		}

		protected function _new() {
			$this->_defaults();
			$this->accounts = new Debtor_Account();
			$this->branches[0] = new Debtor_Branch();
			$this->contacts[0] = new Contact();
			$this->branches[0]->debtor_no = $this->accounts->debtor_no = $this->contacts[0]->parent_id = $this->id = 0;
			$this->_setDefaults();
			return $this->_status(true, 'Initialize', 'Now working with a new customer');
		}

		protected function _read($id = false) {
			if (!parent::_read($id)) {
				return $this->_status->get();
			}
			$this->_getBranches();
			$this->_getAccounts();
			$this->_getContacts();
			$this->discount = $this->discount * 100;
			$this->pymt_discount = $this->pymt_discount * 100;
			$this->credit_limit = Num::price_format($this->credit_limit);
		}

		protected function _setDefaults() {
			$this->defaultBranch = reset($this->branches)->branch_code;
			$this->defaultContact = (count($this->contacts) > 0) ? reset($this->contacts)->id : 0;
			$this->contacts[0] = new Contact(array('parent_id' => $this->id));
		}

		public static function addEditDialog() {
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

		public static function addSearchBox($id, $options = array()) {
			 UI::searchLine($id, '/contacts/search.php', $options);
		}

		public static function search($terms) {
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

		public static function searchOrder($term, $options = array()) {
			$defaults = array('inactive' => false, 'selected' => '');
			$o = array_merge($defaults, $options);
			$term = explode(' ', $term);
			$term1 = DB::escape(trim($term[0]) . '%');
			$term2 = DB::escape('%' . implode(' AND name LIKE ', array_map(function($v) {
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

		public static function get_details($customer_id, $to = null) {
			if ($to == null) {
				$todate = date("Y-m-d");
			} else {
				$todate = Dates::date2sql($to);
			}
			$past1 = DB_Company::get_pref('past_due_days');
			$past2 = 2 * $past1;
			// removed - debtor_trans.alloc from all summations
			$value
			 = "IF(debtor_trans.type=11 OR debtor_trans.type=1 OR debtor_trans.type=12 OR debtor_trans.type=2,
		-1, 1) *" . "(debtor_trans.ov_amount + debtor_trans.ov_gst + " . "debtor_trans.ov_freight + debtor_trans.ov_freight_tax + " . "debtor_trans.ov_discount)";
			$due = "IF (debtor_trans.type=10,debtor_trans.due_date,debtor_trans.tran_date)";
			$sql = "SELECT debtors_master.name, debtors_master.curr_code, payment_terms.terms,		debtors_master.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description,
			Sum(" . $value . ") AS Balance,
			Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= 0,$value,0)) AS Due,
			Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past1,$value,0)) AS Overdue1,
			Sum(IF ((TO_DAYS('$todate') - TO_DAYS($due)) >= $past2,$value,0)) AS Overdue2
			FROM debtors_master,
				 payment_terms,
				 credit_status,
				 debtor_trans
			WHERE
				 debtors_master.payment_terms = payment_terms.terms_indicator
				 AND debtors_master.credit_status = credit_status.id
				 AND debtors_master.debtor_no = " . DB::escape($customer_id) . "
				 AND debtor_trans.tran_date <= '$todate'
				 AND debtor_trans.type <> 13
				 AND debtors_master.debtor_no = debtor_trans.debtor_no
			GROUP BY
				 debtors_master.name,
				 payment_terms.terms,
				 payment_terms.days_before_due,
				 payment_terms.day_in_following_month,
				 debtors_master.credit_limit,
				 credit_status.dissallow_invoices,
				 credit_status.reason_description";
			$result = DB::query($sql, "The customer details could not be retrieved");
			if (DB::num_rows($result) == 0) {
				/* Because there is no balance - so just retrieve the header information about the customer - the choice is do one query to get the balance and transactions for those customers who have a balance and two queries for those who don't have a balance OR always do two queries - I opted for the former */
				$nil_balance = true;
				$sql
				 = "SELECT debtors_master.name, debtors_master.curr_code, debtors_master.debtor_no, payment_terms.terms,
	 		debtors_master.credit_limit, credit_status.dissallow_invoices, credit_status.reason_description
	 		FROM debtors_master,
	 		 payment_terms,
	 		 credit_status

	 		WHERE
	 		 debtors_master.payment_terms = payment_terms.terms_indicator
	 		 AND debtors_master.credit_status = credit_status.id
	 		 AND debtors_master.debtor_no = " . DB::escape($customer_id);
				$result = DB::query($sql, "The customer details could not be retrieved");
			}
			else {
				$nil_balance = false;
			}
			$customer_record = DB::fetch($result);
			if ($nil_balance == true) {
				$customer_record["Balance"] = 0;
				$customer_record["Due"] = 0;
				$customer_record["Overdue1"] = 0;
				$customer_record["Overdue2"] = 0;
			}
			return $customer_record;
		}

		public static function get($customer_id) {
			$sql = "SELECT * FROM debtors_master WHERE debtor_no=" . DB::escape($customer_id);
			$result = DB::query($sql, "could not get customer");
			return DB::fetch($result);
		}

		public static function get_name($customer_id) {
			$sql = "SELECT name FROM debtors_master WHERE debtor_no=" . DB::escape($customer_id);
			$result = DB::query($sql, "could not get customer");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		public static function get_habit($customer_id) {
			$sql
			 = "SELECT debtors_master.pymt_discount,
				 credit_status.dissallow_invoices
				FROM debtors_master, credit_status
				WHERE debtors_master.credit_status = credit_status.id
					AND debtors_master.debtor_no = " . DB::escape($customer_id);
			$result = DB::query($sql, "could not query customers");
			return DB::fetch($result);
		}

		public static function get_area($id) {
			$sql = "SELECT description FROM areas WHERE area_code=" . DB::escape($id);
			$result = DB::query($sql, "could not get sales type");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		public static function get_salesman_name($id) {
			$sql = "SELECT salesman_name FROM salesman WHERE salesman_code=" . DB::escape($id);
			$result = DB::query($sql, "could not get sales type");
			$row = DB::fetch_row($result);
			return $row[0];
		}

		public static function get_credit($customer_id) {
			$custdet = Debtor::get_details($customer_id);
			return ($customer_id > 0 && isset ($custdet['credit_limit'])) ? $custdet['credit_limit'] - $custdet['Balance'] : 0;
		}

		public static function is_new($id) {
			$tables = array('cust_branch', 'debtor_trans', 'recurrent_invoices', 'sales_orders');
			return !DB_Company::key_in_foreign_table($id, $tables, 'debtor_no');
		}

		public static function select($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false) {
			$sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM debtors_master ";
			$mode = DB_Company::get_pref('no_customer_list');
			if ($editkey) {
				Display::set_editor('customer', $name, $editkey);
			}
			return select_box($name, $selected_id, $sql, 'debtor_no', 'name', array(
				'format' => '_format_add_curr', 'order' => array('debtor_ref'), 'search_box' => $mode != 0, 'type' => 1, 'size' => 20, 'spec_option' => $spec_option === true ?
				 _("All Customers") : $spec_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change, 'async' => $async, 'sel_hint' => $mode ?
				 _('Press Space tab to filter by name fragment; F2 - entry new customer') : _('Select customer'), 'show_inactive' => $show_inactive));
		}

		public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td nowrap>";
			echo Debtor::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey, $async);
			echo "</td>\n";
		}

		public static function row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false) {
			echo "<tr><td id='customer_id_label' class='label pointer'>$label</td><td nowrap>";
			echo Debtor::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
			echo "</td>\n</tr>\n";
		}

		public static function trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "sales/view/";
			switch ($type) {
				case ST_SALESINVOICE:
					$viewer .= "view_invoice.php";
					break;
				case ST_CUSTCREDIT:
					$viewer .= "view_credit.php";
					break;
				case ST_CUSTPAYMENT:
					$viewer .= "view_receipt.php";
					break;
				case ST_CUSTREFUND:
					$viewer .= "view_receipt.php";
					break;
				case ST_CUSTDELIVERY:
					$viewer .= "view_dispatch.php";
					break;
				case ST_SALESORDER:
				case ST_SALESQUOTE:
					$viewer .= "view_sales_order.php";
					break;
				default:
					return null;
			}
			if (!is_array($trans_no)) {
				$trans_no = array($trans_no);
			}
			$lbl = $label;
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
	}
