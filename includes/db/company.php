<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	class DB_Company {

		public static function update_gl_setup($retained_act, $profit_loss_act, $debtors_act, $pyt_discount_act, $creditors_act, $freight_act, $exchange_diff_act, $bank_charge_act, $default_sales_act, $default_sales_discount_act, $default_prompt_payment_act, $default_inventory_act, $default_cogs_act,
																					 $default_adj_act, $default_inv_sales_act, $default_assembly_act, $allow_negative_stock, $po_over_receive, $po_over_charge, $accumulate_shipping, $legal_text, $past_due_days, $default_credit_limit, $default_workorder_required, $default_dim_required,
																					 $default_delivery_required) {
			$sql = "UPDATE company SET
		retained_earnings_act=" . DBOld::escape($retained_act) . ", profit_loss_year_act=" . DBOld::escape($profit_loss_act) . ",
		debtors_act=" . DBOld::escape($debtors_act) . ", pyt_discount_act=" . DBOld::escape($pyt_discount_act) . ",
		creditors_act=" . DBOld::escape($creditors_act) . ",
		freight_act=" . DBOld::escape($freight_act) . ",
		exchange_diff_act=" . DBOld::escape($exchange_diff_act) . ",
		bank_charge_act=" . DBOld::escape($bank_charge_act) . ",
		default_sales_act=" . DBOld::escape($default_sales_act) . ",
		default_sales_discount_act=" . DBOld::escape($default_sales_discount_act) . ",
		default_prompt_payment_act=" . DBOld::escape($default_prompt_payment_act) . ",
		default_inventory_act=" . DBOld::escape($default_inventory_act) . ",
		default_cogs_act=" . DBOld::escape($default_cogs_act) . ",
		default_adj_act=" . DBOld::escape($default_adj_act) . ",
		default_inv_sales_act=" . DBOld::escape($default_inv_sales_act) . ",
		default_assembly_act=" . DBOld::escape($default_assembly_act) . ",
		allow_negative_stock=$allow_negative_stock,
		po_over_receive=$po_over_receive,
		po_over_charge=$po_over_charge,
		accumulate_shipping=$accumulate_shipping,
		legal_text=" . DBOld::escape($legal_text) . ",
		past_due_days=$past_due_days,
		default_credit_limit=$default_credit_limit,
		default_workorder_required=$default_workorder_required,
		default_dim_required=$default_dim_required,
		default_delivery_required=$default_delivery_required
		WHERE coy_code=1";
			DBOld::query($sql, "The company gl setup could not be updated ");
		}

		public static function update_setup($coy_name, $coy_no, $gst_no, $tax_prd, $tax_last, $postal_address, $phone, $fax, $email, $coy_logo, $domicile, $Dimension, $curr_default, $f_year, $no_item_list, $no_customer_list, $no_supplier_list, $base_sales, $time_zone, $add_pct, $round_to, $login_tout) {
			if ($f_year == null) {
				$f_year = 0;
			}
			$sql = "UPDATE company SET coy_name=" . DBOld::escape($coy_name) . ",
		coy_no = " . DBOld::escape($coy_no) . ",
		gst_no=" . DBOld::escape($gst_no) . ",
		tax_prd=$tax_prd,
		tax_last=$tax_last,
		postal_address =" . DBOld::escape($postal_address) . ",
		phone=" . DBOld::escape($phone) . ", fax=" . DBOld::escape($fax) . ",
		email=" . DBOld::escape($email) . ",
		coy_logo=" . DBOld::escape($coy_logo) . ",
		domicile=" . DBOld::escape($domicile) . ",
		use_dimension=$Dimension,
		no_item_list=$no_item_list,
		no_customer_list=$no_customer_list,
		no_supplier_list=$no_supplier_list,
		curr_default=" . DBOld::escape($curr_default) . ",
		f_year=$f_year,
		base_sales=$base_sales,
		time_zone=$time_zone,
		add_pct=$add_pct,
		round_to=$round_to,
		login_tout = " . DBOld::escape($login_tout) . "
		WHERE coy_code=1";
			DBOld::query($sql, "The company setup could not be updated ");
			DB_Company::get_prefs();
		}

		public static function get_prefs() {
			if (!isset($_SESSION['company_prefs'])) {
				$sql = "SELECT * FROM company WHERE coy_code=1";
				$result = DBOld::query($sql, "The company preferences could not be retrieved");
				if (DBOld::num_rows($result) == 0) {
					Errors::show_db_error("FATAL : Could not find company prefs", $sql);
				}
				$_SESSION['company_prefs'] = DBOld::fetch($result);
			}
			return $_SESSION['company_prefs'];
		}

		public static function get_pref($pref_name) {
			$prefs = DB_Company::get_prefs();
			return $prefs[$pref_name];
		}

		// fiscal year routines
		public static function add_fiscalyear($from_date, $to_date, $closed) {
			$from = Dates::date2sql($from_date);
			$to = Dates::date2sql($to_date);
			$sql = "INSERT INTO fiscal_year (begin, end, closed)
		VALUES (" . DBOld::escape($from) . "," . DBOld::escape($to) . ", " . DBOld::escape($closed) . ")";
			DBOld::query($sql, "could not add fiscal year");
		}

		public static function update_fiscalyear($id, $closed) {
			$sql = "UPDATE fiscal_year SET closed=" . DBOld::escape($closed) . "
		WHERE id=" . DBOld::escape($id);
			DBOld::query($sql, "could not update fiscal year");
		}

		public static function get_all_fiscalyears() {
			$sql = "SELECT * FROM fiscal_year ORDER BY begin";
			return DBOld::query($sql, "could not get all fiscal years");
		}

		public static function get_fiscalyear($id) {
			$sql = "SELECT * FROM fiscal_year WHERE id=" . DBOld::escape($id);
			$result = DBOld::query($sql, "could not get fiscal year");
			return DBOld::fetch($result);
		}

		public static function get_current_fiscalyear() {
			$year = DB_Company::get_pref('f_year');
			$sql = "SELECT * FROM fiscal_year WHERE id=" . DBOld::escape($year);
			$result = DBOld::query($sql, "could not get current fiscal year");
			return DBOld::fetch($result);
		}

		public static function delete_fiscalyear($id) {
			DBOld::begin_transaction();
			$sql = "DELETE FROM fiscal_year WHERE id=" . DBOld::escape($id);
			DBOld::query($sql, "could not delete fiscal year");
			DBOld::commit_transaction();
		}

		public static function get_base_sales_type() {
			$sql = "SELECT base_sales FROM company WHERE coy_code=1";
			$result = DBOld::query($sql, "could not get base sales type");
			$myrow = DBOld::fetch($result);
			return $myrow[0];
		}

		public static function get_company_extensions($id = -1) {

			$file = PATH_TO_ROOT . ($id == -1 ? '' : '/company/' . $id) . '/installed_extensions.php';
			$installed_extensions = array();
			if (is_file($file)) {
				include($file);
			}
			return $installed_extensions;
		}

		public static function add_payment_terms($daysOrFoll, $terms, $dayNumber) {
			if ($daysOrFoll) {
				$sql = "INSERT INTO  payment_terms (terms,
			days_before_due, day_in_following_month)
			VALUES (" .
				 DBOld::escape($terms) . ", " . DBOld::escape($dayNumber) . ", 0)";
			}
			else
			{
				$sql = "INSERT INTO  payment_terms (terms,
			days_before_due, day_in_following_month)
			VALUES (" . DBOld::escape($terms) . ",
			0, " . DBOld::escape($dayNumber) . ")";
			}
			DBOld::query($sql, "The payment term could not be added");
		}

		public static function update_payment_terms($selected_id, $daysOrFoll, $terms, $dayNumber) {
			if ($daysOrFoll) {
				$sql = "UPDATE  payment_terms SET terms=" . DBOld::escape($terms) . ",
			day_in_following_month=0,
			days_before_due=" . DBOld::escape($dayNumber) . "
			WHERE terms_indicator = " . DBOld::escape($selected_id);
			}
			else
			{
				$sql = "UPDATE payment_terms SET terms=" . DBOld::escape($terms) . ",
			day_in_following_month=" . DBOld::escape($dayNumber) . ",
			days_before_due=0
			WHERE terms_indicator = " . DBOld::escape($selected_id);
			}
			DBOld::query($sql, "The payment term could not be updated");
		}

		public static function delete_payment_terms($selected_id) {
			$sql = "DELETE FROM payment_terms WHERE terms_indicator=" . DBOld::escape($selected_id);
			DBOld::query($sql, "could not delete a payment terms");
		}

		public static function get_payment_terms($selected_id) {
			$sql = "SELECT *, (t.days_before_due=0) AND (t.day_in_following_month=0) as cash_sale
	 FROM payment_terms t WHERE terms_indicator=" . DBOld::escape($selected_id);

			$result = DBOld::query($sql, "could not get payment term");

			return DBOld::fetch($result);
		}

		public static function get_payment_terms_all($show_inactive) {
			$sql = "SELECT * FROM payment_terms";
			if (!$show_inactive) $sql .= " WHERE !inactive";
			return DBOld::query($sql, "could not get payment terms");
		}

		/*
			 Return number of records in tables, where some foreign key $id is used.
			 $id - searched key value
			 $tables - array of table names (without prefix); when table name is used as a key, then
				 value is name of foreign key field. For numeric keys $stdkey field name is used.
			 $stdkey - standard name of foreign key.
		 */
		public static function key_in_foreign_table($id, $tables, $stdkey, $escaped = false) {
			if (!$escaped)
				$id = DBOld::escape($id);

			if (!is_array($tables))
				$tables = array($tables);

			$sqls = array();
			foreach ($tables as $tbl => $key) {
				if (is_numeric($tbl)) {
					$tbl = $key;
					$key = $stdkey;
				}
				$sqls[] = "(SELECT COUNT(*) as cnt FROM `$tbl` WHERE `$key`=" . DBOld::escape($id) . ")\n";
			}

			$sql = "SELECT sum(cnt) FROM (" . implode(' UNION ', $sqls) . ") as counts";

			$result = DBOld::query($sql, "check relations for " . implode(',', $tables) . " failed");
			$count = DBOld::fetch($result);

			return $count[0];
		}
	}

?>