<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	class DB_Company extends DB_abstract
	{
		public function __construct($id = 0) {
			parent::__construct($id);
			$this->id = &$this->coy_code;
		}
		public function save($changes = null) {
			if (is_array($changes)) {
				$this->setFromArray($changes);
			}
			if (!$this->_canProcess()) {
				return false;
			}
			if ($this->id == 0) {
				$this->_saveNew();
			}
			DB::begin();
			$result = DB::update('company')->values((array)$this)->where('coy_code=', $this->id)->exec();
			DB::commit();
			$_SESSION['config']['company'] = $this;
			return $this->_status(true, 'Processing', "Company has been updated.");
		}
		public function delete() {
			// TODO: Implement delete() method.
		}
		protected function _canProcess() {
			return true;
			// TODO: Implement _canProcess() method.
		}
		protected function _defaults() {
			// TODO: Implement _defaults() method.
		}
		protected function _new() {
			// TODO: Implement _new() method.
		}
		protected function _read($id = 0) {
			$result = DB::select()->from('company')->where('coy_code=', $id)->fetch()->intoObject($this);
		}
		protected function _saveNew() {
			// TODO: Implement _saveNew() method.
		}
		public $id = 0;
		public $coy_code;
		public $coy_name;
		public $gst_no;
		public $coy_no;
		public $tax_prd;
		public $tax_last;
		public $postal_address;
		public $phone;
		public $fax;
		public $email;
		public $coy_logo;
		public $suburb;
		public $curr_default;
		public $debtors_act;
		public $pyt_discount_act;
		public $creditors_act;
		public $bank_charge_act;
		public $exchange_diff_act;
		public $profit_loss_year_act;
		public $retained_earnings_act;
		public $freight_act;
		public $default_sales_act;
		public $default_sales_discount_act;
		public $default_prompt_pament_act;
		public $default_inventory_act;
		public $default_cogs_act;
		public $default_adj_act;
		public $default_inv_sales_act;
		public $default_assembly_act;
		public $payroll_act;
		public $allow_negative_stock;
		public $po_over_receive;
		public $po_over_charge;
		public $default_credit_limit;
		public $default_workorder_required;
		public $default_dim_required;
		public $past_due_days;
		public $use_dimension;
		public $f_year;
		public $no_item_list;
		public $no_customer_list;
		public $no_supplier_list;
		public $base_sales;
		public $foreign_codes;
		public $accumulate_shipping;
		public $legal_text;
		public $default_delivery_required;
		public $version_id;
		public $time_zone;
		public $custom0_name;
		public $custom0_value;
		public $add_pct;
		public $round_to;
		public $login_tout;
		/***
		 * @var DB_Company
		 */
		static protected $i = null;
		/***
		 * @static
		 *
		 * @param null $id
		 *
		 * @return DB_Company
		 */
		static public function i($id = null) {
			$id = $id ? : User::get()->company;
			if (static::$i === null) {
				static::$i = isset($_SESSION['config']['company']) ? $_SESSION['config']['company'] : new static($id);
			}
			return static::$i;
		}
		static public function add_fiscalyear($from_date, $to_date, $closed) {
			$from = Dates::date2sql($from_date);
			$to = Dates::date2sql($to_date);
			$sql = "INSERT INTO fiscal_year (begin, end, closed)
		VALUES (" . DB::escape($from) . "," . DB::escape($to) . ", " . DB::escape($closed) . ")";
			DB::query($sql, "could not add fiscal year");
		}
		static public function add_payment_terms($daysOrFoll, $terms, $dayNumber) {
			if ($daysOrFoll) {
				$sql = "INSERT INTO payment_terms (terms,
					days_before_due, day_in_following_month)
					VALUES (" . DB::escape($terms) . ", " . DB::escape($dayNumber) . ", 0)";
			}
			else {
				$sql = "INSERT INTO payment_terms (terms,
					days_before_due, day_in_following_month)
					VALUES (" . DB::escape($terms) . ",
					0, " . DB::escape($dayNumber) . ")";
			}
			DB::query($sql, "The payment term could not be added");
		}
		static public function delete_fiscalyear($id) {
			DB::begin();
			$sql = "DELETE FROM fiscal_year WHERE id=" . DB::escape($id);
			DB::query($sql, "could not delete fiscal year");
			DB::commit();
		}
		static public function delete_payment_terms($selected_id) {
			DB::query("DELETE FROM payment_terms WHERE terms_indicator=" . DB::escape($selected_id) . " could not delete a payment terms");
		}
		static public function get_all_fiscalyears() {
			$sql = "SELECT * FROM fiscal_year ORDER BY begin";
			return DB::query($sql, "could not get all fiscal years");
		}
		static public function get_base_sales_type() {
			$sql = "SELECT base_sales FROM company WHERE coy_code=1";
			$result = DB::query($sql, "could not get base sales type");
			$myrow = DB::fetch($result);
			return $myrow[0];
		}
		static public function get_company_extensions($id = -1) {
			$file = PATH_TO_ROOT . ($id == -1 ? '' : '/company/' . $id) . '/installed_extensions.php';
			$installed_extensions = array();
			if (is_file($file)) {
				include($file);
			}
			return $installed_extensions;
		}
		static public function get_current_fiscalyear() {
			$year = DB_Company::get_pref('f_year');
			$sql = "SELECT * FROM fiscal_year WHERE id=" . DB::escape($year);
			$result = DB::query($sql, "could not get current fiscal year");
			return DB::fetch($result);
		}
		static public function get_fiscalyear($id) {
			$sql = "SELECT * FROM fiscal_year WHERE id=" . DB::escape($id);
			$result = DB::query($sql, "could not get fiscal year");
			return DB::fetch($result);
		}
		static public function get_pref($pref_name) {
			$prefs = DB_Company::get_prefs();
			return $prefs[$pref_name];
		}
		static public function get_prefs() {
			if (!isset($_SESSION['config']['company'])) {
				$_SESSION['config']['company'] = static::i();
				if (!static::$i) {
					Event::error("FATAL : Could not find company prefs");
				}
			}
			return (array)$_SESSION['config']['company'];
		}
		static public function update_payment_terms($selected_id, $daysOrFoll, $terms, $dayNumber) {
			if ($daysOrFoll) {
				$sql = "UPDATE payment_terms SET terms=" . DB::escape($terms) . ",
			day_in_following_month=0,
			days_before_due=" . DB::escape($dayNumber) . "
			WHERE terms_indicator = " . DB::escape($selected_id);
			}
			else {
				$sql = "UPDATE payment_terms SET terms=" . DB::escape($terms) . ",
			day_in_following_month=" . DB::escape($dayNumber) . ",
			days_before_due=0
			WHERE terms_indicator = " . DB::escape($selected_id);
			}
			DB::query($sql, "The payment term could not be updated");
		}
		static public function get_payment_terms($selected_id) {
			$sql = "SELECT *, (t.days_before_due=0) AND (t.day_in_following_month=0) as cash_sale
	 FROM payment_terms t WHERE terms_indicator=" . DB::escape($selected_id);
			$result = DB::query($sql, "could not get payment term");
			return DB::fetch($result);
		}
		static public function get_payment_terms_all($show_inactive) {
			$sql = "SELECT * FROM payment_terms";
			if (!$show_inactive) {
				$sql .= " WHERE !inactive";
			}
			return DB::query($sql, "could not get payment terms");
		}
		/*
									 Return number of records in tables, where some foreign key $id is used.
									 $id - searched key value
									 $tables - array of table names (without prefix); when table name is used as a key, then
										 value is name of foreign key field. For numeric keys $stdkey field name is used.
									 $stdkey - standard name of foreign key.
								 */
		static public function key_in_foreign_table($id, $tables, $stdkey, $escaped = false) {
			if (!$escaped) {
				$id = DB::escape($id);
			}
			if (!is_array($tables)) {
				$tables = array($tables);
			}
			$sqls = array();
			foreach ($tables as $tbl => $key) {
				if (is_numeric($tbl)) {
					$tbl = $key;
					$key = $stdkey;
				}
				$sqls[] = "(SELECT COUNT(*) as cnt FROM `$tbl` WHERE `$key`=" . DB::escape($id) . ")\n";
			}
			$sql = "SELECT sum(cnt) FROM (" . implode(' UNION ', $sqls) . ") as counts";
			$result = DB::query($sql, "check relations for " . implode(',', $tables) . " failed");
			$count = DB::fetch($result);
			return $count[0];
		}
		static public function update_fiscalyear($id, $closed) {
			$sql = "UPDATE fiscal_year SET closed=" . DB::escape($closed) . "
			WHERE id=" . DB::escape($id);
			DB::query($sql, "could not update fiscal year");
		}
		static public function update_gl_setup(array $data = null) {
			static::i()->save($data);
		}
		static public function update_setup(array $data = null) {
			if (static::i()->f_year == null) {
				static::$i->f_year = 0;
			}
			static::$i->save($data);
		}
	}
