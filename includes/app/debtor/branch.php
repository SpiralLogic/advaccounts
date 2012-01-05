<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: advanced
	 * Date: 15/11/10
	 * Time: 11:52 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Debtor_Branch extends DB_abstract
	{
		public $post_address = '';
		public $branch_code = 0;
		public $br_name = "New Address";
		public $br_address = '';
		public $city = '';
		public $state = '';
		public $postcode = '';
		public $area = DEFAULT_AREA;
		public $br_post_address;
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
		protected $_table = 'branches';
		protected $_id_column = 'branch_code';

		public function __construct($id = null) {
			$this->id = &$this->branch_code;
			parent::__construct($id);
			$this->name = &$this->br_name;
			$this->address = &$this->br_address;
			$this->post_address = &$this->br_post_address;
		}

		public function delete() {

			DB::delete('branches')->where('branch_code=',$this->branch_code)->exec();
			$this->_new();
			return $this->_status(true, 'delete', "Branch deleted.");
		}

		public function getAddress() {
			$address = $this->br_address . "\n";
			if ($this->city) {
				$address .= $this->city;
			}
			if ($this->state) {
				$address .= ", " . strtoupper($this->state);
			}
			if ($this->postcode) {
				$address .= ", " . $this->postcode;
			}
			return $address;
		}

		protected function _canProcess() {
			if (strlen($this->br_name) < 1) {
				return $this->_status(false, 'write', 'Branch name can not be empty');
			}
			return true;
		}

		protected function _countTransactions() {
		}

		protected function _defaults() {
			$company_record = DB_Company::get_prefs();
			$this->branch_code = 0;
			$this->sales_discount_account = $company_record['default_sales_discount_act'];
			$this->receivables_account = $company_record['debtors_act'];
			$this->payment_discount_account = $company_record['default_prompt_payment_act'];
			$this->salesman = ($_SESSION['current_user']) ? $_SESSION['current_user']->salesmanid : 1;
		}

		protected function _new() {
			$this->_defaults();
			return $this->_status(true, 'new', 'Now working with a new Branch');
		}

		protected function setFromArray($changes = null) {
			parent::setFromArray($changes);
			if (!empty($this->city)) {
				$this->br_name = $this->city . " " . strtoupper($this->state);
			}
			if ($this->branch_ref != 'accounts') {
				$this->branch_ref = substr($this->br_name, 0, 30);
			}
		}

		protected function _read($params = false) {
			if (!$params) {
				return $this->_status(false, 'read', 'No Branch parameters provided');
			}
			$this->_defaults();
			if (!is_array($params)) {
				$params = array('branch_code' => $params);
			}
			$sql = DB::select('b.*', 'a.description', 's.salesman_name', 't.name AS tax_group_name')
			 ->from('branches b, debtors c, areas a, salesman s, tax_groups t')->where(array(
																																																'b.debtor_no=c.debtor_no',
																																																'b.tax_group_id=t.id',
																																																'b.area=a.area_code',
																																																'b.salesman=s.salesman_code'
																																													 ));
			foreach ($params as $key => $value) {
				$sql->where("b.$key=", $value);
			}
			DB::fetch()->intoClass($this);
			return $this->_status(true, 'read', 'Read Branch from Database');
		}

		// BRANCHES
		public static function select($customer_id, $name, $selected_id = null, $spec_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
			$sql = "SELECT branch_code, branch_ref FROM branches
			WHERE branch_ref <> 'accounts' AND debtor_no='" . $customer_id . "' ";
			if ($editkey) {
				Display::set_editor('branch', $name, $editkey);
			}
			$where = $enabled ? array("disable_trans = 0") : array();
			return select_box($name, $selected_id, $sql, 'branch_code', 'br_name', array(
																																									'where' => $where,
																																									'order' => array('branch_ref'),
																																									'spec_option' => $spec_option === true ? _('All branches') : $spec_option,
																																									'spec_id' => ALL_TEXT,
																																									'select_submit' => $submit_on_change,
																																									'sel_hint' => _('Select customer branch')
																																						 ));
		}

		public static function cells($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Debtor_Branch::select($customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
			echo "</td>\n";
		}

		public static function row($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
			echo "<tr><td class='label'>$label</td>";
			Debtor_Branch::cells(null, $customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
			echo "</tr>";
		}
	}


