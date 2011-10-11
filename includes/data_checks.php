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

	function db_has_customers() {
		return check_empty_result("SELECT COUNT(*) FROM debtors_master");
	}

	function check_db_has_customers($msg) {

		if (!db_has_customers()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_currencies() {
		return check_empty_result("SELECT COUNT(*) FROM currencies");
	}

	function check_db_has_currencies($msg) {

		if (!db_has_currencies()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_sales_types() {
		return check_empty_result("SELECT COUNT(*) FROM sales_types");
	}

	function check_db_has_sales_types($msg) {

		if (!db_has_sales_types()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_item_tax_types() {
		return check_empty_result("SELECT COUNT(*) FROM item_tax_types");
	}

	function check_db_has_item_tax_types($msg) {

		if (!db_has_item_tax_types()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_tax_types() {
		return check_empty_result("SELECT COUNT(*) FROM tax_types");
	}

	function check_db_has_tax_types($msg) {

		if (!db_has_tax_types()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_tax_groups() {
		return check_empty_result("SELECT COUNT(*) FROM tax_groups");
	}

	function check_db_has_tax_groups($msg) {

		if (!db_has_tax_groups()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_movement_types() {
		return check_empty_result("SELECT COUNT(*) FROM movement_types");
	}

	function check_db_has_movement_types($msg) {

		if (!db_has_movement_types()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_customer_has_branches($customer_id) {
		return check_empty_result("SELECT COUNT(*) FROM cust_branch "
			 . "WHERE debtor_no='$customer_id'");
	}

	function db_has_customer_branches() {
		return check_empty_result("SELECT COUNT(*) FROM "
			 . "cust_branch WHERE !inactive");
	}

	function check_db_has_customer_branches($msg) {

		if (!db_has_customer_branches()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_sales_people() {
		return check_empty_result("SELECT COUNT(*) FROM salesman");
	}

	function check_db_has_sales_people($msg) {

		if (!db_has_sales_people()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_sales_areas() {
		return check_empty_result("SELECT COUNT(*) FROM areas");
	}

	function check_db_has_sales_areas($msg) {

		if (!db_has_sales_areas()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_shippers() {
		return check_empty_result("SELECT COUNT(*) FROM shippers");
	}

	function check_db_has_shippers($msg) {

		if (!db_has_shippers()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_open_workorders() {
		return check_empty_result("SELECT COUNT(*) FROM workorders WHERE closed=0");
	}

	function db_has_workorders() {
		return check_empty_result("SELECT COUNT(*) FROM workorders");
	}

	function check_db_has_workorders($msg) {

		if (!db_has_workorders()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_open_dimensions() {
		return check_empty_result("SELECT COUNT(*) FROM dimensions WHERE closed=0");
	}

	function db_has_dimensions() {
		return check_empty_result("SELECT COUNT(*) FROM dimensions");
	}

	function check_db_has_dimensions($msg) {

		if (!db_has_dimensions()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_suppliers() {
		return check_empty_result("SELECT COUNT(*) FROM suppliers");
	}

	function check_db_has_suppliers($msg) {
		if (!db_has_suppliers()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_stock_items() {
		return check_empty_result("SELECT COUNT(*) FROM stock_master");
	}

	function check_db_has_stock_items($msg) {

		if (!db_has_stock_items()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_bom_stock_items() {
		return check_empty_result("SELECT COUNT(*) FROM stock_master WHERE mb_flag='" . STOCK_MANUFACTURE . "'");
	}

	function check_db_has_bom_stock_items($msg) {

		if (!db_has_bom_stock_items()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_manufacturable_items() {
		return check_empty_result("SELECT COUNT(*) FROM stock_master WHERE (mb_flag='" . STOCK_MANUFACTURE . "')");
	}

	function check_db_has_manufacturable_items($msg) {
		if (!db_has_manufacturable_items()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_purchasable_items() {
		return check_empty_result("SELECT COUNT(*) FROM stock_master WHERE mb_flag='" . STOCK_MANUFACTURE . "'");
	}

	function check_db_has_purchasable_items($msg) {

		if (!db_has_purchasable_items()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_costable_items() {
		return check_empty_result("SELECT COUNT(*) FROM stock_master WHERE mb_flag!='" . STOCK_SERVICE . "'");
	}

	function check_db_has_costable_items($msg) {

		if (!db_has_costable_items()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_stock_categories() {
		return check_empty_result("SELECT COUNT(*) FROM stock_category");
	}

	function check_db_has_stock_categories($msg) {

		if (!db_has_stock_categories()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_workcentres() {
		return check_empty_result("SELECT COUNT(*) FROM workcentres");
	}

	function check_db_has_workcentres($msg) {

		if (!db_has_workcentres()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_locations() {
		return check_empty_result("SELECT COUNT(*) FROM locations");
	}

	function check_db_has_locations($msg) {

		if (!db_has_locations()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_bank_accounts() {
		return check_empty_result("SELECT COUNT(*) FROM bank_accounts");
	}

	function check_db_has_bank_accounts($msg) {

		if (!db_has_bank_accounts()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_cash_accounts() {
		return check_empty_result("SELECT COUNT(*) FROM bank_accounts
		WHERE account_type=3");
	}

	function db_has_gl_accounts() {
		return check_empty_result("SELECT COUNT(*) FROM chart_master");
	}

	function db_has_gl_account_groups() {
		return check_empty_result("SELECT COUNT(*) FROM chart_types");
	}

	function check_db_has_gl_account_groups($msg) {
		if (!db_has_gl_account_groups()) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function db_has_quick_entries() {
		return check_empty_result("SELECT COUNT(*) FROM quick_entries");
	}

	function db_has_tags($type) {
		return check_empty_result("SELECT COUNT(*) FROM tags WHERE type=$type");
	}

	function check_db_has_tags($type, $msg) {
		if (!db_has_tags($type)) {
			ui_msgs::display_error($msg, true);
			end_page();
			exit;
		}
	}

	function check_empty_result($sql) {
		$result = db_query($sql, "could not do check empty query");

		$myrow = db_fetch_row($result);
		return $myrow[0] > 0;
	}

	//
	//	Integer input check
	//	Return 1 if number has proper form and is within <min, max> range
	//
	function check_int($postname, $min = null, $max = null) {
		if (!isset($_POST[$postname]))
			return 0;
		$num = input_num($postname);
		if (!is_int($num))
			return 0;
		if (isset($min) && ($num < $min))
			return 0;
		if (isset($max) && ($num > $max))
			return 0;
		return 1;
	}

	//
	//	Numeric input check.
	//	Return 1 if number has proper form and is within <min, max> range
	//	Empty/not defined fields are defaulted to $dflt value.
	//
	function check_num($postname, $min = null, $max = null, $dflt = 0) {
		if (!isset($_POST[$postname]))
			return 0;
		$num = input_num($postname, $dflt);
		if ($num === false || $num === null)
			return 0;
		if (isset($min) && ($num < $min))
			return 0;
		if (isset($max) && ($num > $max))
			return 0;
		return 1;
	}

?>
