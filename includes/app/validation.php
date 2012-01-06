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
	class Validation
	{
		const CUSTOMERS = "debtors";
		const CURRENCIES = "currencies";
		const SALES_TYPES = "sales_types";
		const ITEM_TAX_TYPES = "item_tax_types";
		const TAX_TYPES = "tax_types";
		const TAX_GROUP = "tax_groups";
		const MOVEMENT_TYPES = "movement_types";
		const BRANCHES = "branches WHERE debtor_no=";
		const BRANCHES_ACTIVE = "branches WHERE !inactive";
		const SALESPERSONS = "salesman";
		const SALES_AREA = "areas";
		const SHIPPERS = "shippers";
		const OPEN_WORKORDERS = "workorders WHERE closed=0";
		const WORKORDERS = "workorders";
		const OPEN_DIMENSIONS = "dimensions WHERE closed=0";
		const DIMENSIONS = "dimensions";
		const SUPPLIERS = "suppliers";
		const STOCK_ITEMS = "stock_master";
		const BOM_ITEMS = "stock_master WHERE mb_flag=";
		const MANUFACTURE_ITEMS = "stock_master WHERE mb_flag=";
		const PURCHASE_ITEMS = "stock_master WHERE mb_flag=";
		const COST_ITEMS = "stock_master WHERE mb_flag!=";
		const STOCK_CATEGORIES = "stock_category";
		const WORKCENTRES = "workcentres";
		const LOCATIONS = "locations";
		const BANK_ACCOUNTS = "bank_accounts";
		const CASH_ACCOUNTS = "bank_accounts";
		const	GL_ACCOUNTS = "chart_master";
		const GL_ACCOUNT_GROUPS = "chart_types";
		const QUICK_ENTRIES = "quick_entries";
		const TAGS = "FROM tags WHERE type=";
		const EMPTY_RESULT = "";
		static public function check($validate, $msg = '', $extra = null) {
			if ($extra === false) {
				return 0;
			}
			if (Cache::get('Validation' . $validate)) {
				return 1;
			}
			if ($extra !== null) {
				if (empty($extra)) {
					throw new Adv_Exception("Extra information not provided for " . $validate);
				}
				if (is_string($extra)) {
					$extra = DB::escape($extra);
				}
			}
			else {
				$extra = '';
			}
			$result = DB::query('SELECT COUNT(*) FROM ' . $validate . ' ' . $extra, 'Could not do check empty query');
			$myrow = DB::fetch_row($result);
			if (!($myrow[0] > 0)) {
				throw new Adv_Exception($msg);
			}
			else {
				Cache::set('Validation' . $validate, true);
				return $myrow[0];
			}
		}
		//
		//	Integer input check
		//	Return 1 if number has proper form and is within <min, max> range
		//
		static public function is_int($postname, $min = null, $max = null) {
			if (!isset($_POST) || !isset($_POST[$postname])) {
				return 0;
			}
			$options = array();
			if ($min !== null) {
				$options['min_range'] = $min;
			}
			if ($max !== null) {
				$options['max_range'] = $max;
			}
			$result = filter_var($_POST[$postname], FILTER_VALIDATE_INT, $options);
			return ($result === false || $result === null) ? false : 1;
		}
		//
		//	Numeric input check.
		//	Return 1 if number has proper form and is within <min, max> range
		//	Empty/not defined fields are defaulted to $dflt value.
		//
		static public function is_num($postname, $min = null, $max = null, $default = 0) {
			if (!isset($_POST) || !isset($_POST[$postname])) {
				$_POST[$postname] = $default;
			}
			$result = filter_var($_POST[$postname], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
			if ($min !== null && $result < $min) {
				$result = false;
			}
			if ($max !== null && $result > $max) {
				$result = false;
			}
			return ($result === false || $result === null) ? $default : 1;
		}
		/**
		 *
		 *	 Read numeric value from user formatted input
		 *
		 * @param null $postname
		 * @param int	$default
		 *
		 * @internal param int $dflt
		 *
		 * @return bool|float|int|mixed|string
		 */
		static public function input_num($postname = null, $default = 0, $min = null, $max = null) {
			if (!isset($_POST) || !isset($_POST[$postname])) {
				$_POST[$postname] = $default;
			}
			$result = filter_var($_POST[$postname], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
			if ($min !== null && $result < $min) {
				$result = false;
			}
			if ($max !== null && $result > $max) {
				$result = false;
			}
			return ($result === false || $result === null) ? 0 : User::numeric($result);
		}
	}